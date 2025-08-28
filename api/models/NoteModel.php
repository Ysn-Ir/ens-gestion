    <?php
    require_once __DIR__ . '/../utils/Database.php';

    class NoteModel
    {
        private $db;
        private $logger;

        /**
         * Initialize the NoteModel with a database connection.
         */
        public function __construct()
        {
            $this->db = (new Database())->getConnection();
            if (!$this->db) {
                error_log("NoteModel: Failed to initialize database connection");
                throw new Exception("Database connection failed", 500);
            }
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->logger = function ($message) {
                error_log(date('[Y-m-d H:i:s] ') . $message);
            };
        }

        /**
         * Check if the database connection is active and supports transactions.
         *
         * @return bool
         */
        private function isDbConnected()
        {
            try {
                $this->db->query("SELECT 1");
                $engineCheck = $this->db->query("SHOW TABLE STATUS WHERE Name = 'notes'");
                $engine = $engineCheck->fetch(PDO::FETCH_ASSOC)['Engine'] ?? '';
                if (strtolower($engine) !== 'innodb') {
                    ($this->logger)("NoteModel: Database engine '$engine' does not support transactions");
                    return false;
                }
                return true;
            } catch (PDOException $e) {
                ($this->logger)("NoteModel: Database connection check failed: " . $e->getMessage());
                return false;
            }
        }

        /**
         * Start a transaction with validation.
         *
         * @throws PDOException If transaction cannot be started
         */
        private function startTransaction()
        {
            if (!$this->isDbConnected()) {
                throw new PDOException("Cannot start transaction: No active database connection or unsupported engine");
            }
            if (!$this->db->inTransaction()) {
                if (!$this->db->beginTransaction()) {
                    ($this->logger)("NoteModel: Failed to start transaction");
                    throw new PDOException("Failed to start transaction");
                }
                ($this->logger)("NoteModel: Transaction started");
            }
        }

        /**
         * Validate notes data for a given semester and year.
         *
         * @param int $semestre_id
         * @param string $annee_id
         * @return array List of invalid note_ids
         */
        private function validateNotesData($semestre_id, $annee_id)
        {
            $validationQuery = "
                SELECT n.note_id, n.student_id, n.element_id
                FROM notes n
                LEFT JOIN elements e ON n.element_id = e.element_id
                WHERE n.semestre_id = :semestre_id AND n.annee_id = :annee_id
                AND (e.element_id IS NULL OR n.element_id IS NULL)
            ";
            $validationStmt = $this->db->prepare($validationQuery);
            $validationStmt->execute(['semestre_id' => $semestre_id, 'annee_id' => $annee_id]);
            $invalidNotes = $validationStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($invalidNotes as $note) {
                ($this->logger)("Invalid note detected: note_id={$note['note_id']}, student_id={$note['student_id']}, element_id=" . ($note['element_id'] ?? 'NULL'));
            }

            return array_column($invalidNotes, 'note_id');
        }

        /**
         * Generate empty rows in note-related tables for all students for a given semester and year.
         *
         * @param int $semestre_id The semester ID
         * @param string $annee_id The academic year in YYYY-YYYY format
         * @return array Success status and message
         * @throws Exception If inputs are invalid or database errors occur
         */
        public function generateEmptyNoteRows($semestre_id, $annee_id)
        {
            try {
                // Validate inputs
                if (!filter_var($semestre_id, FILTER_VALIDATE_INT)) {
                    throw new Exception("Invalid semestre_id: must be an integer", 400);
                }
                if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                    throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
                }
                [$start_year, $end_year] = explode('-', $annee_id);
                if ($end_year - $start_year !== 1) {
                    throw new Exception("Invalid annee_id: second year must be one more than first year", 400);
                }

                $this->startTransaction();

                // Batch insert preparation
                $noteInserts = [];
                $moduleInserts = [];
                $semesterInserts = [];
                $yearInserts = [];

                // Fetch students enrolled in the given semester and year with their field_id
                $studentQuery = "
                    SELECT DISTINCT se.student_id, e.field_id, CONCAT(e.nom, ' ', e.prenom) as student_name
                    FROM student_enrollments se
                    JOIN etudiants e ON se.student_id = e.user_id
                    WHERE se.semestre_id = :semestre_id AND se.annee_id = :annee_id
                    AND EXISTS (SELECT 1 FROM modules m WHERE m.semestre_id = :semestre_id AND m.annee_id = :annee_id AND m.field_id = e.field_id)
                ";
                $studentStmt = $this->db->prepare($studentQuery);
                $studentStmt->execute(['semestre_id' => $semestre_id, 'annee_id' => $annee_id]);
                $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($students)) {
                    $this->db->commit();
                    ($this->logger)("No students enrolled for semestre_id=$semestre_id, annee_id=$annee_id");
                    return ['success' => true, 'message' => "No students enrolled for semestre_id=$semestre_id, annee_id=$annee_id"];
                }

                foreach ($students as $student) {
                    $student_id = $student['student_id'];
                    $field_id = $student['field_id'];

                    // Fetch modules and their elements
                    $moduleQuery = "
                        SELECT m.module_id, m.semestre_id, e.element_id
                        FROM modules m
                        LEFT JOIN elements e ON m.module_id = e.module_id
                        WHERE m.semestre_id = :semestre_id AND m.annee_id = :annee_id AND m.field_id = :field_id
                    ";
                    $moduleStmt = $this->db->prepare($moduleQuery);
                    $moduleStmt->execute(['semestre_id' => $semestre_id, 'annee_id' => $annee_id, 'field_id' => $field_id]);
                    $modules = $moduleStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($modules)) {
                        ($this->logger)("No modules found for student_id=$student_id, field_id=$field_id");
                        continue;
                    }

                    foreach ($modules as $module) {
                        if (!empty($module['element_id'])) {
                            $noteInserts[] = "($student_id, {$module['element_id']}, $semestre_id, '$annee_id', NULL, NULL, NULL, NULL, NULL, NULL, NULL)";
                        }
                        $moduleInserts[] = "($student_id, {$module['module_id']}, $semestre_id, '$annee_id', NULL, NULL, NULL)";
                    }

                    $semesterInserts[] = "($student_id, $semestre_id, '$annee_id', NULL, NULL, NULL)";
                    $yearInserts[] = "($student_id, '$annee_id', NULL, NULL, 0)";
                }

                // Batch insert notes
                if (!empty($noteInserts)) {
                    $noteQuery = "INSERT IGNORE INTO notes (student_id, element_id, semestre_id, annee_id, note_tp, note_cc, note_exam, note_rattrapage, note_finale, decision, decision_ratt) VALUES " . implode(',', $noteInserts);
                    $this->db->exec($noteQuery);
                }

                // Batch insert module notes
                if (!empty($moduleInserts)) {
                    $moduleQuery = "INSERT IGNORE INTO note_modules (student_id, module_id, semestre_id, annee_id, note_module, decision, retake_status) VALUES " . implode(',', $moduleInserts);
                    $this->db->exec($moduleQuery);
                }

                // Batch insert semester notes
                if (!empty($semesterInserts)) {
                    $semesterQuery = "INSERT IGNORE INTO note_semestres (student_id, semestre_id, annee_id, note_semestre, decision, nv_module_count) VALUES " . implode(',', $semesterInserts);
                    $this->db->exec($semesterQuery);
                }

                // Batch insert year notes
                if (!empty($yearInserts)) {
                    $yearQuery = "INSERT IGNORE INTO note_annees (student_id, annee_id, note_annee, decision_annee, fail_count) VALUES " . implode(',', $yearInserts);
                    $this->db->exec($yearQuery);
                }

                $this->db->commit();
                ($this->logger)("Generated empty note rows for " . count($students) . " students");
                return ['success' => true, 'message' => "Empty note rows generated for semestre_id=$semestre_id, annee_id=$annee_id"];
            } catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                ($this->logger)("PDO Error in generateEmptyNoteRows: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                ($this->logger)("Error in generateEmptyNoteRows: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        /**
         * Calculate final notes for elements, modules, and semesters for a given semester and year.
         *
         * @param int $semestre_id The semester ID
         * @param string $annee_id The academic year in YYYY-YYYY format
         * @return array Success status and message
         * @throws Exception If inputs are invalid or database errors occur
         */
        public function calculateAllFinalNotes($semestre_id, $annee_id)
        {
            try {
                // Validate inputs
                if (!filter_var($semestre_id, FILTER_VALIDATE_INT)) {
                    throw new Exception("Invalid semestre_id: must be an integer", 400);
                }
                if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                    throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
                }
                [$start_year, $end_year] = explode('-', $annee_id);
                if ($end_year - $start_year !== 1) {
                    throw new Exception("Invalid annee_id: second year must be one more than first year", 400);
                }

                // Validate notes data
                $invalidNotes = $this->validateNotesData($semestre_id, $annee_id);
                if (!empty($invalidNotes)) {
                    ($this->logger)("Found " . count($invalidNotes) . " invalid notes for semestre_id=$semestre_id, annee_id=$annee_id");
                    throw new Exception("Invalid notes data detected: " . count($invalidNotes) . " records with missing or invalid element_id", 400);
                }

                $this->startTransaction();

                // Fetch students
                $studentQuery = "
                    SELECT DISTINCT se.student_id, e.field_id, CONCAT(e.nom, ' ', e.prenom) as student_name
                    FROM student_enrollments se
                    JOIN etudiants e ON se.student_id = e.user_id
                    WHERE se.semestre_id = :semestre_id AND se.annee_id = :annee_id
                ";
                $studentStmt = $this->db->prepare($studentQuery);
                $studentStmt->execute(['semestre_id' => $semestre_id, 'annee_id' => $annee_id]);
                $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($students)) {
                    $this->db->commit();
                    ($this->logger)("No students found for semestre_id=$semestre_id, annee_id=$annee_id");
                    return ['success' => true, 'message' => "No students found for semestre_id=$semestre_id, annee_id=$annee_id"];
                }

                foreach ($students as $student) {
                    $student_id = $student['student_id'];
                    $field_id = $student['field_id'];

                    // Fetch elements with element_id and decision_ratt
                    $elementQuery = "
                        SELECT n.note_id, n.note_tp, n.note_cc, n.note_exam, n.note_rattrapage, n.decision_ratt,
                            e.element_id, e.coeff_element, e.coeff_tp, e.coeff_cc, e.coeff_ecrit, e.module_id
                        FROM notes n
                        LEFT JOIN elements e ON n.element_id = e.element_id
                        WHERE n.student_id = :student_id AND n.semestre_id = :semestre_id AND n.annee_id = :annee_id
                    ";
                    $elementStmt = $this->db->prepare($elementQuery);
                    $elementStmt->execute(['student_id' => $student_id, 'semestre_id' => $semestre_id, 'annee_id' => $annee_id]);
                    $elements = $elementStmt->fetchAll(PDO::FETCH_ASSOC);

                    // Log and skip invalid elements
                    $validElements = [];
                    foreach ($elements as $element) {
                        if (!isset($element['element_id']) || $element['element_id'] === null) {
                            ($this->logger)("Skipping invalid element: note_id={$element['note_id']}, student_id=$student_id, element_id=" . ($element['element_id'] ?? 'NULL'));
                            continue;
                        }
                        $validElements[] = $element;
                    }
                    $elements = $validElements;

                    // Group elements by module
                    $elementsByModule = [];
                    foreach ($elements as $element) {
                        $elementsByModule[$element['module_id']][] = $element;
                    }

                    foreach ($elementsByModule as $module_id => $moduleElements) {
                        $elementCount = count($moduleElements);
                        if ($elementCount === 0) {
                            ($this->logger)("No valid elements for student_id=$student_id, module_id=$module_id");
                            continue;
                        }

                        foreach ($moduleElements as $element) {
                            $note_finale = null;
                            $decision = null;
                            $decision_ratt = null;

                            // Normalize coefficients
                            $total_coeff = ($element['coeff_tp'] ?? 0) + ($element['coeff_cc'] ?? 0) + ($element['coeff_ecrit'] ?? 0);
                            if ($total_coeff > 0) {
                                $coeff_tp = $element['coeff_tp'] / $total_coeff;
                                $coeff_cc = $element['coeff_cc'] / $total_coeff;
                                $coeff_ecrit = $element['coeff_ecrit'] / $total_coeff;
                            } else {
                                $coeff_tp = $coeff_cc = $coeff_ecrit = 1/3;
                            }

                            $note_tp = $element['note_tp'] ?? 0;
                            $note_cc = $element['note_cc'] ?? 0;
                            $note_exam = $element['note_exam'] ?? 0;
                            $note_rattrapage = $element['note_rattrapage'] ?? null;

                            // Calculate normal final note if all required grades are present
                            if (isset($element['note_exam'], $element['note_tp'], $element['note_cc']) && !isset($element['note_rattrapage'])) {
                                $note_finale = ($note_tp * $coeff_tp) + ($note_cc * $coeff_cc) + ($note_exam * $coeff_ecrit);
                                $decision = ($note_finale >= 10) ? 'V' : 'R';
                            } elseif (isset($element['note_rattrapage'], $element['note_tp'], $element['note_cc'])) {
                                $note_ratt = ($note_tp * $coeff_tp) + ($note_cc * $coeff_cc) + ($note_rattrapage * $coeff_ecrit);
                                $note_finale = max($note_ratt, $note_finale ?? 0);
                                $decision_ratt = ($note_finale >= 10) ? 'VR' : 'NV';
                            }

                            // Update notes table
                            $updateQuery = "
                                UPDATE notes
                                SET note_finale = :note_finale, decision = :decision, decision_ratt = :decision_ratt
                                WHERE note_id = :note_id
                            ";
                            $updateStmt = $this->db->prepare($updateQuery);
                            $updateStmt->execute([
                                'note_finale' => $note_finale,
                                'decision' => $decision,
                                'decision_ratt' => $decision_ratt,
                                'note_id' => $element['note_id']
                            ]);
                        }

                        // Calculate module note
                        $moduleQuery = "
                            SELECT m.module_id, m.coefficient, AVG(n.note_finale * e.coeff_element / 100) as avg_note
                            FROM note_modules nm
                            JOIN modules m ON nm.module_id = m.module_id
                            JOIN elements e ON m.module_id = e.module_id
                            JOIN notes n ON e.element_id = n.element_id AND n.student_id = nm.student_id
                            WHERE nm.student_id = :student_id AND nm.semestre_id = :semestre_id AND nm.annee_id = :annee_id AND m.module_id = :module_id
                            GROUP BY m.module_id
                        ";
                        $moduleStmt = $this->db->prepare($moduleQuery);
                        $moduleStmt->execute([
                            'student_id' => $student_id,
                            'semestre_id' => $semestre_id,
                            'annee_id' => $annee_id,
                            'module_id' => $module_id
                        ]);
                        $module = $moduleStmt->fetch(PDO::FETCH_ASSOC);

                        if ($module) {
                            $note_module = $module['avg_note'];
                            $note_module_ratt =$module['avg_note'];
                            // Safely check for decision_ratt with isset
                            $has_ratt = count(array_filter($moduleElements, fn($e) => isset($e['decision_ratt']) && $e['decision_ratt'] !== null)) > 0;
                            $decision = ($note_module >= 10) ? 'V' : 'R';
                            $decision_ratt=null;
                            if ($has_ratt && $note_module >= 10) {
                                $note_module = min($note_module, 10); // Cap retake note
                                $decision_ratt = 'VR';
                            }
                            else if ($has_ratt && $note_module<10)
                            {
                                $decision_ratt = 'NV';
                            }

                            // Log elements without decision_ratt for debugging
                            foreach ($moduleElements as $e) {
                                if (!isset($e['decision_ratt'])) {
                                    $element_id = isset($e['element_id']) ? $e['element_id'] : 'unknown';
                                    $note_id = isset($e['note_id']) ? $e['note_id'] : 'unknown';
                                    ($this->logger)("Missing decision_ratt for student_id=$student_id, module_id=$module_id, element_id=$element_id, note_id=$note_id");
                                }
                            }

                            // Update module
                            $updateModuleQuery = "
                                UPDATE note_modules
                                SET note_module = :note_module, decision = :decision,decision_ratt = :decision_ratt ,retake_status = :retake_status
                                WHERE student_id = :student_id AND module_id = :module_id AND semestre_id = :semestre_id AND annee_id = :annee_id
                            ";
                            $updateModuleStmt = $this->db->prepare($updateModuleQuery);
                            $retake_status = ($decision_ratt == 'NV') ? 'pending' : 'completed';
                            $updateModuleStmt->execute([
                                'note_module' => $note_module,
                                'decision' => $decision,
                                'decision_ratt'=>$decision_ratt,
                                'retake_status' => $retake_status,
                                'student_id' => $student_id,
                                'module_id' => $module_id,
                                'semestre_id' => $semestre_id,
                                'annee_id' => $annee_id
                            ]);
                        }
                    }

                    // Calculate semester note and decision
                    $semesterQuery = "
                        SELECT SUM(m.coefficient * nm.note_module) / SUM(m.coefficient) as note_semestre,
                            COUNT(CASE WHEN nm.decision = 'NV' THEN 1 END) as nv_count
                        FROM note_modules nm
                        JOIN modules m ON nm.module_id = m.module_id
                        WHERE nm.student_id = :student_id AND nm.semestre_id = :semestre_id AND nm.annee_id = :annee_id
                    ";
                    $semesterStmt = $this->db->prepare($semesterQuery);
                    $semesterStmt->execute(['student_id' => $student_id, 'semestre_id' => $semestre_id, 'annee_id' => $annee_id]);
                    $semester = $semesterStmt->fetch(PDO::FETCH_ASSOC);

                    $note_semestre = $semester['note_semestre'] ?? null;
                    $nv_count = $semester['nv_count'] ?? 0;
                    $decision = null;
                    if ($note_semestre !== null) {
                        if ($nv_count > 2) {
                            $decision = 'F';
                        } elseif ($note_semestre >= 10 && $nv_count == 0) {
                            $decision = 'V';
                        } elseif ($note_semestre >= 10 && $nv_count <= 2) {
                            $decision = 'VPC';
                        } else {
                            $decision = 'NV';
                        }
                    }

                    // Update semester
                    $updateSemesterQuery = "
                        UPDATE note_semestres
                        SET note_semestre = :note_semestre, decision = :decision, nv_module_count = :nv_count
                        WHERE student_id = :student_id AND semestre_id = :semestre_id AND annee_id = :annee_id
                    ";
                    $updateSemesterStmt = $this->db->prepare($updateSemesterQuery);
                    $updateSemesterStmt->execute([
                        'note_semestre' => $note_semestre,
                        'decision' => $decision,
                        'nv_count' => $nv_count,
                        'student_id' => $student_id,
                        'semestre_id' => $semestre_id,
                        'annee_id' => $annee_id
                    ]);
                }

                $this->db->commit();
                ($this->logger)("Calculated final notes for " . count($students) . " students");
                return ['success' => true, 'message' => "Semester notes calculated successfully for semestre_id=$semestre_id"];
            } catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                ($this->logger)("PDO Error in calculateAllFinalNotes: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                ($this->logger)("Error in calculateAllFinalNotes: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        /**
         * Calculate final notes for a given academic year.
         *
         * @param string $annee_id The academic year in YYYY-YYYY format
         * @return array Success status and message
         * @throws Exception If input is invalid or database errors occur
         */
        public function calculateYearFinalNotes($annee_id)
        {
            try {
                // Validate input
                if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                    throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
                }
                [$start_year, $end_year] = explode('-', $annee_id);
                if ($end_year - $start_year !== 1) {
                    throw new Exception("Invalid annee_id: second year must be one more than first year", 400);
                }

                $this->startTransaction();

                // Fetch students
                $studentQuery = "
                    SELECT DISTINCT se.student_id, CONCAT(e.nom, ' ', e.prenom) as student_name
                    FROM student_enrollments se
                    JOIN etudiants e ON se.student_id = e.user_id
                    WHERE se.annee_id = :annee_id
                ";
                $studentStmt = $this->db->prepare($studentQuery);
                $studentStmt->execute(['annee_id' => $annee_id]);
                $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($students)) {
                    $this->db->commit();
                    ($this->logger)("No students found for annee_id=$annee_id");
                    return ['success' => true, 'message' => "No students found for annee_id=$annee_id"];
                }

                foreach ($students as $student) {
                    $student_id = $student['student_id'];

                    // Check for semesters with excessive NV modules
                    $semesterNvQuery = "
                        SELECT nv_module_count
                        FROM note_semestres
                        WHERE student_id = :student_id AND annee_id = :annee_id
                    ";
                    $semesterNvStmt = $this->db->prepare($semesterNvQuery);
                    $semesterNvStmt->execute(['student_id' => $student_id, 'annee_id' => $annee_id]);
                    $semesterNvCounts = $semesterNvStmt->fetchAll(PDO::FETCH_COLUMN);

                    $yearFail = false;
                    foreach ($semesterNvCounts as $nv_count) {
                        if ($nv_count > 2) {
                            $yearFail = true;
                            break;
                        }
                    }

                    // Calculate year note
                    $yearQuery = "
                        SELECT AVG(ns.note_semestre) as note_annee,
                            COUNT(CASE WHEN ns.decision IN ('NV', 'F') THEN 1 END) as nv_count
                        FROM note_semestres ns
                        WHERE ns.student_id = :student_id AND ns.annee_id = :annee_id
                    ";
                    $yearStmt = $this->db->prepare($yearQuery);
                    $yearStmt->execute(['student_id' => $student_id, 'annee_id' => $annee_id]);
                    $year = $yearStmt->fetch(PDO::FETCH_ASSOC);

                    $note_annee = $year['note_annee'] ?? null;
                    $nv_count = $year['nv_count'] ?? 0;
                    $decision_annee = null;
                    if ($note_annee !== null) {
                        if ($yearFail) {
                            $decision_annee = 'F';
                        } elseif ($note_annee >= 10 && $nv_count == 0) {
                            $decision_annee = 'V';
                        } elseif ($note_annee >= 10 && $nv_count <= 1) {
                            $decision_annee = 'VPC';
                        } else {
                            $decision_annee = 'NV';
                        }
                    }

                    // Update fail_count
                    $failCountQuery = "SELECT fail_count FROM note_annees WHERE student_id = :student_id AND annee_id = :annee_id";
                    $failCountStmt = $this->db->prepare($failCountQuery);
                    $failCountStmt->execute(['student_id' => $student_id, 'annee_id' => $annee_id]);
                    $fail_count = $failCountStmt->fetchColumn() ?: 0;

                    if ($decision_annee == 'F') {
                        $fail_count++;
                    }

                    // Handle expulsion
                    if ($fail_count >= 3) {
                        $updateExpulsion = "UPDATE etudiants SET actuel = 0 WHERE user_id = :student_id";
                        $expulsionStmt = $this->db->prepare($updateExpulsion);
                        $expulsionStmt->execute(['student_id' => $student_id]);
                        ($this->logger)("Student $student_id expelled after $fail_count fails in $annee_id");
                    }

                    // Update note_annees
                    $updateYearQuery = "
                        UPDATE note_annees
                        SET note_annee = :note_annee, decision_annee = :decision_annee, fail_count = :fail_count
                        WHERE student_id = :student_id AND annee_id = :annee_id
                    ";
                    $updateYearStmt = $this->db->prepare($updateYearQuery);
                    $updateYearStmt->execute([
                        'note_annee' => $note_annee,
                        'decision_annee' => $decision_annee,
                        'fail_count' => $fail_count,
                        'student_id' => $student_id,
                        'annee_id' => $annee_id
                    ]);

                    // Schedule retakes if year passes
                    if ($decision_annee == 'V' || $decision_annee == 'VPC') {
                        $updateRetake = "
                            UPDATE note_modules
                            SET retake_status = 'scheduled'
                            WHERE student_id = :student_id AND annee_id = :annee_id AND decision_ratt = 'NV' AND retake_status = 'pending'
                        ";
                        $retakeStmt = $this->db->prepare($updateRetake);
                        $retakeStmt->execute(['student_id' => $student_id, 'annee_id' => $annee_id]);
                    }
                }

                $this->db->commit();
                ($this->logger)("Calculated year notes for " . count($students) . " students");
                return ['success' => true, 'message' => "Year notes calculated successfully for annee_id=$annee_id"];
            } catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                ($this->logger)("PDO Error in calculateYearFinalNotes: annee_id=$annee_id, error=" . $e->getMessage());
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                ($this->logger)("Error in calculateYearFinalNotes: annee_id=$annee_id, error=" . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        /**
         * Generate diplomas for students completing a cycle in the given year.
         *
         * @param string $annee_id The academic year in YYYY-YYYY format
         * @return array Success status and message
         * @throws Exception If input is invalid or database errors occur
         */
        public function generateDiplomas($annee_id)
        {
            try {
                // Validate input
                if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                    throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
                }
                [$start_year, $end_year] = explode('-', $annee_id);
                if ($end_year - $start_year !== 1) {
                    throw new Exception("Invalid annee_id: second year must be one more than first year", 400);
                }

                $this->startTransaction();

                // Define mention thresholds
                $mentionThresholds = [
                    'Passable' => 10,
                    'Bien' => 14,
                    'Très Bien' => 16
                ];

                // Fetch students
                $studentQuery = "
                    SELECT DISTINCT se.student_id, e.field_id, e.department_id, e.cycle_id, c.Nombre_semestre AS cycle_semestres,
                        CONCAT(e.nom, ' ', e.prenom) as student_name
                    FROM student_enrollments se
                    JOIN etudiants e ON se.student_id = e.user_id
                    JOIN cycles c ON e.cycle_id = c.cycle_id
                    WHERE se.annee_id = :annee_id AND e.actuel = 1
                ";
                $studentStmt = $this->db->prepare($studentQuery);
                $studentStmt->execute(['annee_id' => $annee_id]);
                $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($students)) {
                    $this->db->commit();
                    ($this->logger)("No eligible students found for diplomas in annee_id=$annee_id");
                    return ['success' => true, 'message' => "No eligible students found for diplomas in annee_id=$annee_id"];
                }

                $diplomasCreated = 0;
                foreach ($students as $student) {
                    $student_id = $student['student_id'];
                    $field_id = $student['field_id'];
                    $department_id = $student['department_id'];
                    $cycle_id = $student['cycle_id'];
                    $cycle_semestres = $student['cycle_semestres'];

                    // Fetch all years for the student in this cycle
                    $yearQuery = "
                        SELECT note_annee, decision_annee, annee_id
                        FROM note_annees
                        WHERE student_id = :student_id AND decision_annee IN ('V', 'VPC')
                    ";
                    $yearStmt = $this->db->prepare($yearQuery);
                    $yearStmt->execute(['student_id' => $student_id]);
                    $years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

                    // Check if all modules are validated
                    $moduleQuery = "
                        SELECT COUNT(*) as pending_count
                        FROM note_modules nm
                        WHERE nm.student_id = :student_id
                        AND nm.decision = 'NV'
                        AND (nm.retake_status IS NULL OR nm.retake_status = 'pending')
                    ";
                    $moduleStmt = $this->db->prepare($moduleQuery);
                    $moduleStmt->execute(['student_id' => $student_id]);
                    $pendingModules = $moduleStmt->fetchColumn();

                    // Skip if not enough validated years or pending modules exist
                    $required_years = ceil($cycle_semestres / 2);
                    if (count($years) < $required_years || $pendingModules > 0) {
                        ($this->logger)("Student $student_id ineligible: " . count($years) . " validated years, $pendingModules pending modules");
                        continue;
                    }

                    // Calculate diploma note
                    $cycle_note = 0;
                    $validated_years = 0;
                    foreach ($years as $year) {
                        $cycle_note += $year['note_annee'];
                        $validated_years++;
                    }
                    $cycle_note = $validated_years > 0 ? $cycle_note / $validated_years : 0;

                    // Determine mention
                    $mention = 'Passable';
                    if ($cycle_note >= $mentionThresholds['Très Bien']) {
                        $mention = 'Très Bien';
                    } elseif ($cycle_note >= $mentionThresholds['Bien']) {
                        $mention = 'Bien';
                    }

                    // Find corresponding diplome
                    $diplomeQuery = "
                        SELECT diplome_id
                        FROM diplomes
                        WHERE cycle_id = :cycle_id AND field_id = :field_id AND department_id = :department_id
                        LIMIT 1
                    ";
                    $diplomeStmt = $this->db->prepare($diplomeQuery);
                    $diplomeStmt->execute([
                        'cycle_id' => $cycle_id,
                        'field_id' => $field_id,
                        'department_id' => $department_id
                    ]);
                    $diplome = $diplomeStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$diplome) {
                        ($this->logger)("No diplome found for student_id=$student_id, cycle_id=$cycle_id, field_id=$field_id, department_id=$department_id");
                        continue;
                    }
                    // In generateDiplomas, after fetching validated years
                    $latest_year = '0000-0000';
                    foreach ($years as $year) {
                        if ($year['annee_id'] > $latest_year) {
                            $latest_year = $year['annee_id'];
                        }
                    }
                    $date_awarded = $latest_year === '0000-0000' ? ($end_year . '-06-30') : (explode('-', $latest_year)[1] . '-06-30');                


                    // Insert or update student_diplomas
                    $diplomaQuery = "
                        INSERT INTO student_diplomas (student_id, diplome_id, note, decision, mention, date_awarded)
                        VALUES (:student_id, :diplome_id, :note, :decision, :mention, :date_awarded)
                        ON DUPLICATE KEY UPDATE
                            note = :note,
                            decision = :decision,
                            mention = :mention,
                            date_awarded = :date_awarded
                    ";
                    $diplomaStmt = $this->db->prepare($diplomaQuery);
                    $diplomaStmt->execute([
                        'student_id' => $student_id,
                        'diplome_id' => $diplome['diplome_id'],
                        'note' => $cycle_note,
                        'decision' => 'V',
                        'mention' => $mention,
                        'date_awarded' => $date_awarded
                    ]);

                    $diplomasCreated++;
                }

                $this->db->commit();
                ($this->logger)("Generated $diplomasCreated diplomas for annee_id=$annee_id");
                return ['success' => true, 'message' => "Generated $diplomasCreated diplomas for annee_id=$annee_id"];
            } catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                ($this->logger)("PDO Error in generateDiplomas: annee_id=$annee_id, error=" . $e->getMessage());
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                ($this->logger)("Error in generateDiplomas: annee_id=$annee_id, error=" . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }
         public function getElementNotes($semestre_id = null, $annee_id)
    {
        try {
            if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
            }
            if ($semestre_id !== null && !filter_var($semestre_id, FILTER_VALIDATE_INT)) {
                throw new Exception("Invalid semestre_id: must be an integer", 400);
            }

            $query = "
                SELECT n.note_id, n.student_id, n.element_id, n.semestre_id, n.annee_id,
                       n.note_tp, n.note_cc, n.note_exam, n.note_rattrapage, n.note_finale,
                       n.decision, n.decision_ratt, CONCAT(e.nom, ' ', e.prenom) as student_name,
                       el.nom as element_name
                FROM notes n
                JOIN etudiants e ON n.student_id = e.user_id
                JOIN elements el ON n.element_id = el.element_id
                WHERE n.annee_id = :annee_id
            ";
            if ($semestre_id !== null) {
                $query .= " AND n.semestre_id = :semestre_id";
            }
            $stmt = $this->db->prepare($query);
            $params = ['annee_id' => $annee_id];
            if ($semestre_id !== null) {
                $params['semestre_id'] = $semestre_id;
            }
            $stmt->execute($params);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $notes];
        } catch (Exception $e) {
            ($this->logger)("Error in getElementNotes: annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch module notes for a given semester and year.
     *
     * @param int|null $semestre_id The semester ID (optional)
     * @param string $annee_id The academic year in YYYY-YYYY format
     * @return array List of module notes
     * @throws Exception If inputs are invalid or database errors occur
     */
    public function getModuleNotes($semestre_id = null, $annee_id)
    {
        try {
            if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
            }
            if ($semestre_id !== null && !filter_var($semestre_id, FILTER_VALIDATE_INT)) {
                throw new Exception("Invalid semestre_id: must be an integer", 400);
            }

            $query = "
                SELECT nm.student_id, nm.module_id, nm.semestre_id, nm.annee_id,
                       nm.note_module, nm.decision, nm.retake_status,
                       CONCAT(e.nom, ' ', e.prenom) as student_name,
                       m.nom as module_name
                FROM note_modules nm
                JOIN etudiants e ON nm.student_id = e.user_id
                JOIN modules m ON nm.module_id = m.module_id
                WHERE nm.annee_id = :annee_id
            ";
            if ($semestre_id !== null) {
                $query .= " AND nm.semestre_id = :semestre_id";
            }
            $stmt = $this->db->prepare($query);
            $params = ['annee_id' => $annee_id];
            if ($semestre_id !== null) {
                $params['semestre_id'] = $semestre_id;
            }
            $stmt->execute($params);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $notes];
        } catch (Exception $e) {
            ($this->logger)("Error in getModuleNotes: annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch semester notes for a given semester and year.
     *
     * @param int|null $semestre_id The semester ID (optional)
     * @param string $annee_id The academic year in YYYY-YYYY format
     * @return array List of semester notes
     * @throws Exception If inputs are invalid or database errors occur
     */
    public function getSemesterNotes($semestre_id = null, $annee_id)
    {
        try {
            if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
            }
            if ($semestre_id !== null && !filter_var($semestre_id, FILTER_VALIDATE_INT)) {
                throw new Exception("Invalid semestre_id: must be an integer", 400);
            }

            $query = "
                SELECT ns.student_id, ns.semestre_id, ns.annee_id,
                       ns.note_semestre, ns.decision, ns.nv_module_count,
                       CONCAT(e.nom, ' ', e.prenom) as student_name
                FROM note_semestres ns
                JOIN etudiants e ON ns.student_id = e.user_id
                WHERE ns.annee_id = :annee_id
            ";
            if ($semestre_id !== null) {
                $query .= " AND ns.semestre_id = :semestre_id";
            }
            $stmt = $this->db->prepare($query);
            $params = ['annee_id' => $annee_id];
            if ($semestre_id !== null) {
                $params['semestre_id'] = $semestre_id;
            }
            $stmt->execute($params);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $notes];
        } catch (Exception $e) {
            ($this->logger)("Error in getSemesterNotes: annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch year notes for a given year.
     *
     * @param string $annee_id The academic year in YYYY-YYYY format
     * @return array List of year notes
     * @throws Exception If inputs are invalid or database errors occur
     */
    public function getYearNotes($annee_id)
    {
        try {
            if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
            }

            $query = "
                SELECT na.student_id, na.annee_id, na.note_annee,
                       na.decision_annee, na.fail_count,
                       CONCAT(e.nom, ' ', e.prenom) as student_name
                FROM note_annees na
                JOIN etudiants e ON na.student_id = e.user_id
                WHERE na.annee_id = :annee_id
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['annee_id' => $annee_id]);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $notes];
        } catch (Exception $e) {
            ($this->logger)("Error in getYearNotes: annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}