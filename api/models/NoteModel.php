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
     * Resolve the actual semestre_id from the numeric part of nom, field_id, and etape_id.
     *
     * @param int $semestre_number Numeric part of nom (1-6)
     * @param int $field_id
     * @param int $etape_id
     * @return int|null Actual semestre_id or null if not found
     */
    private function resolveSemesterId($semestre_number, $field_id, $etape_id)
    {
        if (!is_numeric($semestre_number) || $semestre_number < 1 || $semestre_number > 6) {
            ($this->logger)("Invalid semestre_number: $semestre_number");
            return null;
        }

        $semestre_nom = "Semestre $semestre_number";
        $query = "
            SELECT semestre_id
            FROM semestres
            WHERE nom = :nom AND field_id = :field_id AND etape_id = :etape_id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'nom' => $semestre_nom,
            'field_id' => $field_id,
            'etape_id' => $etape_id
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            ($this->logger)("Semester not found: nom=$semestre_nom, field_id=$field_id, etape_id=$etape_id");
            return null;
        }

        return $result['semestre_id'];
    }

    /**
     * Enroll a student in the next semester based on the current semester's nom.
     *
     * @param int $student_id
     * @param int $current_semestre_number Numeric part of current semester nom (1-6)
     * @param string $annee_id
     * @param int $cycle_id
     * @param int $field_id
     * @param int $etape_id
     * @param int|null $group_id
     * @param int|null $section_id
     * @param string $status
     * @param string|null $decision
     * @return bool Success status
     */
    private function enrollInNextSemester($student_id, $current_semestre_number, $annee_id, $cycle_id, $field_id, $etape_id, $group_id, $section_id, $status, $decision)
    {
        if ($decision === 'F' || $decision === null) {
            ($this->logger)("Cannot enroll student_id=$student_id: Invalid decision ($decision)");
            return false;
        }

        if ($current_semestre_number >= 6) {
            ($this->logger)("Cannot enroll student_id=$student_id: Current semester number ($current_semestre_number) is at maximum");
            return false;
        }

        $current_semestre_id = $this->resolveSemesterId($current_semestre_number, $field_id, $etape_id);
        if (!$current_semestre_id) {
            ($this->logger)("Current semester not resolved: number=$current_semestre_number, etape_id=$etape_id, field_id=$field_id for student_id=$student_id");
            return false;
        }

        $next_semestre_number = $current_semestre_number + 1;
        $next_semestre_id = $this->resolveSemesterId($next_semestre_number, $field_id, $etape_id);
        if (!$next_semestre_id) {
            ($this->logger)("Next semester not found: number=$next_semestre_number, etape_id=$etape_id, field_id=$field_id for student_id=$student_id");
            return false;
        }

        // Check if the student is already enrolled
        $checkEnrollmentQuery = "
            SELECT COUNT(*) as count
            FROM student_enrollments
            WHERE student_id = :student_id AND semestre_id = :next_semestre_id AND annee_id = :annee_id AND etape_id = :etape_id
        ";
        $checkEnrollmentStmt = $this->db->prepare($checkEnrollmentQuery);
        $checkEnrollmentStmt->execute([
            'student_id' => $student_id,
            'next_semestre_id' => $next_semestre_id,
            'annee_id' => $annee_id,
            'etape_id' => $etape_id
        ]);
        $enrollmentCount = $checkEnrollmentStmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($enrollmentCount > 0) {
            ($this->logger)("Student_id=$student_id already enrolled in semestre_id=$next_semestre_id, nom=Semestre $next_semestre_number, annee_id=$annee_id, etape_id=$etape_id");
            return false;
        }

        // Enroll the student
        $enrollQuery = "
            INSERT INTO student_enrollments (student_id, semestre_id, annee_id, cycle_id, field_id, etape_id, group_id, section_id, status)
            VALUES (:student_id, :next_semestre_id, :annee_id, :cycle_id, :field_id, :etape_id, :group_id, :section_id, :status)
        ";
        $enrollStmt = $this->db->prepare($enrollQuery);
        $enrollStmt->execute([
            'student_id' => $student_id,
            'next_semestre_id' => $next_semestre_id,
            'annee_id' => $annee_id,
            'cycle_id' => $cycle_id,
            'field_id' => $field_id,
            'etape_id' => $etape_id,
            'group_id' => $group_id,
            'section_id' => $section_id,
            'status' => $status
        ]);
        ($this->logger)("Enrolled student_id=$student_id in semestre_id=$next_semestre_id, nom=Semestre $next_semestre_number, annee_id=$annee_id, etape_id=$etape_id");
        return true;
    }

    /**
     * Validate notes data for a given semester and year.
     *
     * @param int $semestre_number Numeric part of semester nom (1-6)
     * @param string $annee_id
     * @param int $field_id
     * @param int $etape_id
     * @return array List of invalid note_ids
     */
    private function validateNotesData($semestre_number, $annee_id, $field_id, $etape_id)
    {
        $semestre_id = $this->resolveSemesterId($semestre_number, $field_id, $etape_id);
        if (!$semestre_id) {
            return [];
        }

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
     * @param int $semestre_number Numeric part of semester nom (1-6)
     * @param string $annee_id The academic year in YYYY-YYYY format
     * @return array Success status and message
     * @throws Exception If inputs are invalid or database errors occur
     */
    public function generateEmptyNoteRows($semestre_number, $annee_id)
    {
        try {
            // Validate inputs
            if (!is_numeric($semestre_number) || $semestre_number < 1 || $semestre_number > 6) {
                throw new Exception("Invalid semestre_number: must be an integer between 1 and 6", 400);
            }
            if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
            }
            [$start_year, $end_year] = explode('-', $annee_id);
            if ($end_year - $start_year !== 1) {
                throw new Exception("Invalid annee_id: second year must be one more than first year", 400);
            }

            $this->startTransaction();

            // Fetch students with field_id and etape_id
            $studentQuery = "
                SELECT DISTINCT se.student_id, e.field_id, se.etape_id, CONCAT(e.nom, ' ', e.prenom) as student_name
                FROM student_enrollments se
                JOIN etudiants e ON se.student_id = e.user_id
                WHERE se.annee_id = :annee_id
            ";
            $studentStmt = $this->db->prepare($studentQuery);
            $studentStmt->execute(['annee_id' => $annee_id]);
            $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($students)) {
                $this->db->commit();
                ($this->logger)("No students enrolled for annee_id=$annee_id");
                return ['success' => true, 'message' => "No students enrolled for annee_id=$annee_id"];
            }

            $noteInserts = [];
            $moduleInserts = [];
            $semesterInserts = [];
            $yearInserts = [];
            $skippedStudents = [];

            foreach ($students as $student) {
                $student_id = $student['student_id'];
                $field_id = $student['field_id'];
                $etape_id = $student['etape_id'];
                error_log("Processing student_id=$student_id, field_id=$field_id, etape_id=$etape_id");

                $semestre_id = $this->resolveSemesterId($semestre_number, $field_id, $etape_id);
                if (!$semestre_id) {
                    ($this->logger)("Skipping student_id=$student_id: Could not resolve semestre_id for number=$semestre_number, field_id=$field_id, etape_id=$etape_id");
                    $skippedStudents[] = $student['student_name'];
                    continue;
                }

                // Fetch modules and elements
                $moduleQuery = "
                    SELECT m.module_id, m.semestre_id, e.element_id
                    FROM modules m
                    LEFT JOIN elements e ON m.module_id = e.module_id
                    WHERE m.semestre_id = :semestre_id AND m.field_id = :field_id 
                ";
                $moduleStmt = $this->db->prepare($moduleQuery);
                $moduleStmt->execute(['semestre_id' => $semestre_id, 'field_id' => $field_id]);
                $modules = $moduleStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Found " . count($modules) . " modules for student_id=$student_id");

                if (empty($modules)) {
                    ($this->logger)("No modules found for student_id=$student_id, field_id=$field_id, semestre_id=$semestre_id");
                    $skippedStudents[] = $student['student_name'];
                    continue;
                }

                foreach ($modules as $module) {
                    if (!empty($module['element_id'])) {
                        $noteInserts[] = "($student_id, {$module['element_id']}, $semestre_id, '$annee_id', $etape_id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)";
                    }
                    $moduleInserts[] = "($student_id, {$module['module_id']}, $semestre_id, '$annee_id', $etape_id, NULL, NULL, NULL, NULL, NULL)";
                }
                $semesterInserts[] = "($student_id, $semestre_id, '$annee_id', $etape_id, NULL, NULL, NULL)";
                $yearInserts[] = "($student_id, '$annee_id', $etape_id, NULL, NULL, 0)";
            }

            // Batch inserts with error handling
            $successCount = 0;
            if (!empty($noteInserts)) {
                try {
                    $noteQuery = "INSERT IGNORE INTO notes (student_id, element_id, semestre_id, annee_id, etape_id, note_tp, note_td, note_cc, note_exam, note_rattrapage, note_finale, decision, decision_ratt) VALUES " . implode(',', $noteInserts);
                    $successCount += $this->db->exec($noteQuery);
                    error_log("Inserted $successCount note rows");
                } catch (PDOException $e) {
                    error_log("Note insert error: " . $e->getMessage());
                }
            }
            if (!empty($moduleInserts)) {
                try {
                    $moduleQuery = "INSERT IGNORE INTO note_modules (student_id, module_id, semestre_id, annee_id, etape_id, note_module, decision, note_ratt, decision_ratt, retake_status) VALUES " . implode(',', $moduleInserts);
                    $successCount += $this->db->exec($moduleQuery);
                    error_log("Inserted module rows");
                } catch (PDOException $e) {
                    error_log("Module insert error: " . $e->getMessage());
                }
            }
            if (!empty($semesterInserts)) {
                try {
                    $semesterQuery = "INSERT IGNORE INTO note_semestres (student_id, semestre_id, annee_id, etape_id, note_semestre, decision, nv_module_count) VALUES " . implode(',', $semesterInserts);
                    $successCount += $this->db->exec($semesterQuery);
                    error_log("Inserted semester rows");
                } catch (PDOException $e) {
                    error_log("Semester insert error: " . $e->getMessage());
                }
            }
            if (!empty($yearInserts)) {
                try {
                    $yearQuery = "INSERT IGNORE INTO note_annees (student_id, annee_id, etape_id, note_annee, decision_annee, fail_count) VALUES " . implode(',', $yearInserts);
                    $successCount += $this->db->exec($yearQuery);
                    error_log("Inserted year rows");
                } catch (PDOException $e) {
                    error_log("Year insert error: " . $e->getMessage());
                }
            }

            $this->db->commit();
            $message = "Generated $successCount note rows for " . (count($students) - count($skippedStudents)) . " students";
            if (!empty($skippedStudents)) {
                $message .= ". Skipped students: " . implode(', ', $skippedStudents);
            }
            ($this->logger)($message);
            return ['success' => count($skippedStudents) === 0, 'message' => $message];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            ($this->logger)("PDO Error in generateEmptyNoteRows: semestre_number=$semestre_number, annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            ($this->logger)("Error in generateEmptyNoteRows: semestre_number=$semestre_number, annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Calculate final notes for elements, modules, and semesters for a given semester and year.
     *
     * @param int $semestre_number Numeric part of semester nom (1-6)
     * @param string $annee_id The academic year in YYYY-YYYY format
     * @return array Success status and message
     * @throws Exception If inputs are invalid or database errors occur
     */
    public function calculateAllFinalNotes($semestre_number, $annee_id)
    {
        try {
            // Validate inputs
            if (!is_numeric($semestre_number) || $semestre_number < 1 || $semestre_number > 6) {
                throw new Exception("Invalid semestre_number: must be an integer between 1 and 6", 400);
            }
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
                SELECT DISTINCT se.student_id, se.cycle_id, se.field_id, se.etape_id, se.group_id, se.section_id, se.status,
                    CONCAT(e.nom, ' ', e.prenom) as student_name
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
                $cycle_id = $student['cycle_id'];
                $field_id = $student['field_id'];
                $etape_id = $student['etape_id'] ?? null;
                $group_id = $student['group_id'] ?? null;
                $section_id = $student['section_id'] ?? null;
                $status = $student['status'] ?? 'active';

                if ($etape_id === null) {
                    ($this->logger)("Skipping student_id=$student_id: Missing etape_id in student_enrollments");
                    continue;
                }

                $semestre_id = $this->resolveSemesterId($semestre_number, $field_id, $etape_id);
                if (!$semestre_id) {
                    ($this->logger)("Skipping student_id=$student_id: Could not resolve semestre_id for number=$semestre_number, field_id=$field_id, etape_id=$etape_id");
                    continue;
                }

                // Validate notes data
                $invalidNotes = $this->validateNotesData($semestre_number, $annee_id, $field_id, $etape_id);
                if (!empty($invalidNotes)) {
                    ($this->logger)("Found " . count($invalidNotes) . " invalid notes for semestre_number=$semestre_number, semestre_id=$semestre_id, annee_id=$annee_id");
                    continue; // Skip student instead of throwing to process others
                }

                // Fetch elements with element_id and decision_ratt
                $elementQuery = "
                    SELECT n.note_id, n.note_tp, n.note_cc, n.note_exam, n.note_rattrapage, n.decision_ratt,
                        e.element_id, e.coeff_element, e.coeff_tp, e.coeff_cc, e.coeff_ecrit, e.module_id
                    FROM notes n
                    LEFT JOIN elements e ON n.element_id = e.element_id
                    WHERE n.student_id = :student_id AND n.semestre_id = :semestre_id AND n.annee_id = :annee_id AND n.etape_id = :etape_id
                ";
                $elementStmt = $this->db->prepare($elementQuery);
                $elementStmt->execute(['student_id' => $student_id, 'semestre_id' => $semestre_id, 'annee_id' => $annee_id, 'etape_id' => $etape_id]);
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
                        SELECT 
                            m.module_id, 
                            m.coefficient, 
                            AVG(n.note_finale * e.coeff_element) AS avg_note,
                            AVG(
                                CASE 
                                    WHEN n.note_rattrapage IS NOT NULL 
                                    THEN n.note_rattrapage * e.coeff_element 
                                END
                            ) AS avg_note_rattrapage
                        FROM note_modules nm
                        JOIN modules m ON nm.module_id = m.module_id
                        JOIN elements e ON m.module_id = e.module_id
                        JOIN notes n ON e.element_id = n.element_id 
                                    AND n.student_id = nm.student_id
                        WHERE nm.student_id = :student_id
                        AND nm.semestre_id = :semestre_id
                        AND nm.annee_id = :annee_id
                        AND nm.etape_id = :etape_id
                        AND m.module_id = :module_id
                        GROUP BY m.module_id, m.coefficient;
                    ";
                    $moduleStmt = $this->db->prepare($moduleQuery);
                    $moduleStmt->execute([
                        'student_id' => $student_id,
                        'semestre_id' => $semestre_id,
                        'annee_id' => $annee_id,
                        'etape_id' => $etape_id,
                        'module_id' => $module_id
                    ]);
                    $module = $moduleStmt->fetch(PDO::FETCH_ASSOC);

                    if ($module) {
                        $note_module = $module['avg_note'];
                        $note_module_ratt = $module['avg_note_rattrapage'];
                        $has_ratt = count(array_filter($moduleElements, fn($e) => isset($e['decision_ratt']) && $e['decision_ratt'] !== null)) > 0;
                        $decision = ($note_module >= 10) ? 'V' : 'R';
                        $decision_ratt = null;
                        if ($has_ratt && $note_module >= 10) {
                            $note_module = min($note_module, 10);
                            $decision_ratt = 'VR';
                        } else if ($has_ratt && $note_module < 10) {
                            $note_module = max($note_module_ratt, $note_module);
                            $decision_ratt = 'NV';
                            $decision = 'NV';
                        }

                        // Update module
                        $updateModuleQuery = "
                            UPDATE note_modules
                            SET note_module = :note_module, decision = :decision, decision_ratt = :decision_ratt, retake_status = :retake_status
                            WHERE student_id = :student_id AND module_id = :module_id AND semestre_id = :semestre_id AND annee_id = :annee_id AND etape_id = :etape_id
                        ";
                        $updateModuleStmt = $this->db->prepare($updateModuleQuery);
                        $retake_status = ($decision_ratt == 'NV') ? 'scheduled' : 'completed';
                        $updateModuleStmt->execute([
                            'note_module' => $note_module,
                            'decision' => $decision,
                            'decision_ratt' => $decision_ratt,
                            'retake_status' => $retake_status,
                            'student_id' => $student_id,
                            'module_id' => $module_id,
                            'semestre_id' => $semestre_id,
                            'annee_id' => $annee_id,
                            'etape_id' => $etape_id
                        ]);
                    }
                }

                // Calculate semester note and decision
                $semesterQuery = "
                    SELECT SUM(m.coefficient * nm.note_module) / SUM(m.coefficient) as note_semestre,
                        COUNT(CASE WHEN nm.decision = 'NV' THEN 1 END) as nv_count
                    FROM note_modules nm
                    JOIN modules m ON nm.module_id = m.module_id
                    WHERE nm.student_id = :student_id AND nm.semestre_id = :semestre_id AND nm.annee_id = :annee_id AND nm.etape_id = :etape_id
                ";
                $semesterStmt = $this->db->prepare($semesterQuery);
                $semesterStmt->execute(['student_id' => $student_id, 'semestre_id' => $semestre_id, 'annee_id' => $annee_id, 'etape_id' => $etape_id]);
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
                } else {
                    ($this->logger)("Cannot calculate semester decision for student_id=$student_id, semestre_id=$semestre_id: note_semestre is NULL");
                }

                // Update semester
                $updateSemesterQuery = "
                    UPDATE note_semestres
                    SET note_semestre = :note_semestre, decision = :decision, nv_module_count = :nv_count
                    WHERE student_id = :student_id AND semestre_id = :semestre_id AND annee_id = :annee_id AND etape_id = :etape_id
                ";
                $updateSemesterStmt = $this->db->prepare($updateSemesterQuery);
                $updateSemesterStmt->execute([
                    'note_semestre' => $note_semestre,
                    'decision' => $decision,
                    'nv_count' => $nv_count,
                    'student_id' => $student_id,
                    'semestre_id' => $semestre_id,
                    'annee_id' => $annee_id,
                    'etape_id' => $etape_id
                ]);

                // Enroll in next semester for semesters 1, 3, or 5
                if (in_array($semestre_number, [1, 3, 5])) {
                    $this->enrollInNextSemester($student_id, $semestre_number, $annee_id, $cycle_id, $field_id, $etape_id, $group_id, $section_id, $status, $decision);
                }
            }

            $this->db->commit();
            ($this->logger)("Calculated final notes for " . count($students) . " students");
            return ['success' => true, 'message' => "Semester notes calculated successfully for semestre_number=$semestre_number"];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            ($this->logger)("PDO Error in calculateAllFinalNotes: semestre_number=$semestre_number, annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            ($this->logger)("Error in calculateAllFinalNotes: semestre_number=$semestre_number, annee_id=$annee_id, error=" . $e->getMessage());
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
   public function calculateYearFinalNotes($annee_id) {
    try {
        if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
            throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
        }
        [$start_year, $end_year] = explode('-', $annee_id);
        if ($end_year - $start_year !== 1) {
            throw new Exception("Invalid annee_id: second year must be one more than first year", 400);
        }

        $this->startTransaction();

        $studentQuery = "
            SELECT DISTINCT se.student_id, se.etape_id, se.cycle_id, se.field_id, se.group_id, se.section_id, se.status,
                   CONCAT(e.nom, ' ', e.prenom) as student_name
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
            $etape_id = $student['etape_id'];
            $cycle_id = $student['cycle_id'];
            $field_id = $student['field_id'];
            $group_id = $student['group_id'] ?? null;
            $section_id = $student['section_id'] ?? null;
            $status = $student['status'] ?? 'active';

            if ($etape_id === null) {
                ($this->logger)("Skipping student_id=$student_id: Missing etape_id in student_enrollments");
                continue;
            }

            // Check for semesters with excessive NV modules
            $semesterNvQuery = "
                SELECT nv_module_count
                FROM note_semestres
                WHERE student_id = :student_id AND annee_id = :annee_id AND etape_id = :etape_id
            ";
            $semesterNvStmt = $this->db->prepare($semesterNvQuery);
            $semesterNvStmt->execute(['student_id' => $student_id, 'annee_id' => $annee_id, 'etape_id' => $etape_id]);
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
                WHERE ns.student_id = :student_id AND ns.annee_id = :annee_id AND ns.etape_id = :etape_id
            ";
            $yearStmt = $this->db->prepare($yearQuery);
            $yearStmt->execute(['student_id' => $student_id, 'annee_id' => $annee_id, 'etape_id' => $etape_id]);
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
            } else {
                ($this->logger)("Cannot calculate year note for student_id=$student_id, annee_id=$annee_id, etape_id=$etape_id: note_annee is NULL");
                continue;
            }

            // Fetch existing fail_count and decision_annee
            $failCountQuery = "
                SELECT fail_count, decision_annee
                FROM note_annees
                WHERE student_id = :student_id AND annee_id = :annee_id AND etape_id = :etape_id
            ";
            $failCountStmt = $this->db->prepare($failCountQuery);
            $failCountStmt->execute(['student_id' => $student_id, 'annee_id' => $annee_id, 'etape_id' => $etape_id]);
            $existing = $failCountStmt->fetch(PDO::FETCH_ASSOC);
            $fail_count = $existing['fail_count'] ?? 0;
            $previous_decision = $existing['decision_annee'] ?? null;

            // Increment fail_count for 'F' or 'NV' decisions
            if (in_array($decision_annee, ['F', 'NV']) && !in_array($previous_decision, ['F', 'NV'])) {
                $fail_count++;
            }

            // Create or update note_annees
            $updateYearQuery = "
                INSERT INTO note_annees (student_id, annee_id, etape_id, note_annee, decision_annee, fail_count)
                VALUES (:student_id, :annee_id, :etape_id, :note_annee, :decision_annee, :fail_count)
                ON DUPLICATE KEY UPDATE
                    note_annee = :note_annee,
                    decision_annee = :decision_annee,
                    fail_count = :fail_count
            ";
            $updateYearStmt = $this->db->prepare($updateYearQuery);
            $updateYearStmt->execute([
                'student_id' => $student_id,
                'annee_id' => $annee_id,
                'etape_id' => $etape_id,
                'note_annee' => $note_annee,
                'decision_annee' => $decision_annee,
                'fail_count' => $fail_count
            ]);

            // Handle expulsion
            if ($fail_count >= 2) { // Align with max_year_fails from grading_rules
                $updateExpulsion = "UPDATE etudiants SET actuel = 0 WHERE user_id = :student_id";
                $expulsionStmt = $this->db->prepare($updateExpulsion);
                $expulsionStmt->execute(['student_id' => $student_id]);
                ($this->logger)("Student $student_id expelled after $fail_count fails in $annee_id");
            }

            // Schedule retakes if year passes
            if (in_array($decision_annee, ['V', 'VPC'])) {
                $updateRetake = "
                    UPDATE note_modules
                    SET retake_status = 'scheduled'
                    WHERE student_id = :student_id AND annee_id = :annee_id AND etape_id = :etape_id AND decision_ratt = 'NV' AND retake_status = 'pending'
                ";
                $retakeStmt = $this->db->prepare($updateRetake);
                $retakeStmt->execute(['student_id' => $student_id, 'annee_id' => $annee_id, 'etape_id' => $etape_id]);

                $next_year = (int)$end_year . '-' . ((int)$end_year + 1);
                $this->enrollInNextSemester(
                    $student_id,
                    0,
                    $next_year,
                    $cycle_id,
                    $field_id,
                    $etape_id,
                    $group_id,
                    $section_id,
                    $status,
                    $decision_annee
                );
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

            // Fetch students with etape_id
            $studentQuery = "
                SELECT DISTINCT se.student_id, se.etape_id, e.field_id, e.department_id, e.cycle_id, c.Nombre_semestre AS cycle_semestres,
                    CONCAT(e.nom, ' ', e.prenom) as student_name, se.group_id, se.section_id, se.status
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
                $etape_id = $student['etape_id'];
                $field_id = $student['field_id'];
                $department_id = $student['department_id'];
                $cycle_id = $student['cycle_id'];
                $cycle_semestres = $student['cycle_semestres'];
                $group_id = $student['group_id'] ?? null;
                $section_id = $student['section_id'] ?? null;
                $status = $student['status'] ?? 'active';

                if ($etape_id === null) {
                    ($this->logger)("Skipping student_id=$student_id: Missing etape_id in student_enrollments");
                    continue;
                }

                // Fetch validated years for this etape_id
                $yearQuery = "
                    SELECT note_annee, decision_annee, annee_id
                    FROM note_annees
                    WHERE student_id = :student_id AND etape_id = :etape_id AND decision_annee IN ('V', 'VPC')
                ";
                $yearStmt = $this->db->prepare($yearQuery);
                $yearStmt->execute(['student_id' => $student_id, 'etape_id' => $etape_id]);
                $years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

                // Check if all modules are validated
                $moduleQuery = "
                    SELECT COUNT(*) as pending_count
                    FROM note_modules nm
                    WHERE nm.student_id = :student_id AND nm.etape_id = :etape_id
                    AND nm.decision = 'NV'
                    AND (nm.retake_status IS NULL OR nm.retake_status = 'pending')
                ";
                $moduleStmt = $this->db->prepare($moduleQuery);
                $moduleStmt->execute(['student_id' => $student_id, 'etape_id' => $etape_id]);
                $pendingModules = $moduleStmt->fetchColumn();

                // Skip if not enough validated years or pending modules exist
                $required_years = ceil($cycle_semestres / 2);
                if (count($years) < $required_years || $pendingModules > 0) {
                    ($this->logger)("Student $student_id, etape_id=$etape_id ineligible: " . count($years) . " validated years, $pendingModules pending modules");
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
                    ($this->logger)("No diplome found for student_id=$student_id, etape_id=$etape_id, cycle_id=$cycle_id, field_id=$field_id, department_id=$department_id");
                    continue;
                }

                // Determine date_awarded
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

                // Enroll in the first semester of the next cycle if applicable
                $nextCycleQuery = "
                    SELECT cycle_id
                    FROM cycles
                    WHERE cycle_id > :current_cycle_id
                    ORDER BY cycle_id ASC
                    LIMIT 1
                ";
                $nextCycleStmt = $this->db->prepare($nextCycleQuery);
                $nextCycleStmt->execute(['current_cycle_id' => $cycle_id]);
                $nextCycle = $nextCycleStmt->fetch(PDO::FETCH_ASSOC);

                if ($nextCycle) {
                    $next_cycle_id = $nextCycle['cycle_id'];
                    $next_year = (int)$end_year . '-' . ((int)$end_year + 1);
                    $this->enrollInNextSemester(
                        $student_id,
                        0, // Semester 0 to resolve to Semestre 1
                        $next_year,
                        $next_cycle_id,
                        $field_id,
                        $etape_id,
                        $group_id,
                        $section_id,
                        $status,
                        'V' // Diploma implies validation
                    );
                }
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
/**
 * Fetch element notes for a given semester and year.
 *
 * @param int|null $semestre_number Numeric part of semester nom (1-6) or null
 * @param string $annee_id The academic year in YYYY-YYYY format
 * @return array List of element notes
 * @throws Exception If inputs are invalid or database errors occur
 *//**
 * Fetch element notes for a given semester and year.
 *
 * @param int|null $semestre_number Numeric part of semester nom (1-6) or null
 * @param string $annee_id The academic year in YYYY-YYYY format
 * @return array List of element notes
 * @throws Exception If inputs are invalid or database errors occur
 */
public function getElementNotes($semestre_number, $annee_id)
{
    try {
        if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
            throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
        }
        if ($semestre_number !== null && (!is_numeric($semestre_number) || $semestre_number < 1 || $semestre_number > 6)) {
            throw new Exception("Invalid semestre_number: must be an integer between 1 and 6", 400);
        }

        $query = "
            SELECT n.note_id, n.student_id, n.element_id, n.semestre_id, n.annee_id, n.etape_id,
                   n.note_tp, n.note_cc, n.note_exam, n.note_rattrapage, n.note_finale,
                   n.decision, n.decision_ratt, CONCAT(e.nom, ' ', e.prenom) as student_name,
                   el.nom as element_name
            FROM notes n
            JOIN etudiants e ON n.student_id = e.user_id
            JOIN elements el ON n.element_id = el.element_id
            WHERE n.annee_id = :annee_id
        ";
        $params = ['annee_id' => $annee_id];

        if ($semestre_number !== null) {
            $semesterQuery = "
                SELECT DISTINCT se.semestre_id, se.field_id, se.etape_id
                FROM student_enrollments se
                WHERE se.annee_id = :annee_id
            ";
            $semesterStmt = $this->db->prepare($semesterQuery);
            $semesterStmt->execute(['annee_id' => $annee_id]);
            $semesters = $semesterStmt->fetchAll(PDO::FETCH_ASSOC);
            ($this->logger)("Found " . count($semesters) . " enrollments for annee_id=$annee_id, semestre_number=$semestre_number");

            if (empty($semesters)) {
                ($this->logger)("No enrollments found for annee_id=$annee_id, semestre_number=$semestre_number");
                return ['success' => true, 'data' => []];
            }

            $semestre_ids = [];
            foreach ($semesters as $semester) {
                $semestre_id = $this->resolveSemesterId($semestre_number, $semester['field_id'], $semester['etape_id']);
                if ($semestre_id) {
                    $semestre_ids[] = $semestre_id;
                    ($this->logger)("Resolved semestre_id=$semestre_id for semestre_number=$semestre_number, field_id={$semester['field_id']}, etape_id={$semester['etape_id']}");
                } else {
                    ($this->logger)("Failed to resolve semestre_id for semestre_number=$semestre_number, field_id={$semester['field_id']}, etape_id={$semester['etape_id']}");
                }
            }

            if (empty($semestre_ids)) {
                ($this->logger)("No semesters found for semestre_number=$semestre_number, annee_id=$annee_id");
                return ['success' => true, 'data' => []];
            }

            $placeholders = [];
            foreach ($semestre_ids as $index => $semestre_id) {
                $placeholder = ":semestre_id$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $semestre_id;
            }
            $query .= " AND n.semestre_id IN (" . implode(',', $placeholders) . ")";
            ($this->logger)("Querying notes with semestre_ids: " . implode(',', $semestre_ids));
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ($this->logger)("Retrieved " . count($notes) . " element notes for annee_id=$annee_id" . ($semestre_number !== null ? ", semestre_number=$semestre_number" : ""));

        return ['success' => true, 'data' => $notes];
    } catch (Exception $e) {
        ($this->logger)("Error in getElementNotes: semestre_number=$semestre_number, annee_id=$annee_id, error=" . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Fetch module notes for a given semester and year.
 *
 * @param int|null $semestre_number Numeric part of semester nom (1-6) or null
 * @param string $annee_id The academic year in YYYY-YYYY format
 * @return array List of module notes
 * @throws Exception If inputs are invalid or database errors occur
 */
public function getModuleNotes($semestre_number, $annee_id)
{
    try {
        if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
            throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
        }
        if ($semestre_number !== null && (!is_numeric($semestre_number) || $semestre_number < 1 || $semestre_number > 6)) {
            throw new Exception("Invalid semestre_number: must be an integer between 1 and 6", 400);
        }

        $query = "
            SELECT nm.student_id, nm.module_id, nm.semestre_id, nm.annee_id, nm.etape_id,
                   nm.note_module, nm.decision, nm.retake_status,
                   CONCAT(e.nom, ' ', e.prenom) as student_name,
                   m.nom as module_name
            FROM note_modules nm
            JOIN etudiants e ON nm.student_id = e.user_id
            JOIN modules m ON nm.module_id = m.module_id
            WHERE nm.annee_id = :annee_id
        ";
        $params = ['annee_id' => $annee_id];

        if ($semestre_number !== null) {
            $semesterQuery = "
                SELECT DISTINCT se.semestre_id, se.field_id, se.etape_id
                FROM student_enrollments se
                WHERE se.annee_id = :annee_id
            ";
            $semesterStmt = $this->db->prepare($semesterQuery);
            $semesterStmt->execute(['annee_id' => $annee_id]);
            $semesters = $semesterStmt->fetchAll(PDO::FETCH_ASSOC);

            $semestre_ids = [];
            foreach ($semesters as $semester) {
                $semestre_id = $this->resolveSemesterId($semestre_number, $semester['field_id'], $semester['etape_id']);
                if ($semestre_id) {
                    $semestre_ids[] = $semestre_id;
                }
            }

            if (empty($semestre_ids)) {
                ($this->logger)("No semesters found for semestre_number=$semestre_number, annee_id=$annee_id");
                return ['success' => true, 'data' => []];
            }

            $placeholders = [];
            foreach ($semestre_ids as $index => $semestre_id) {
                $placeholder = ":semestre_id$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $semestre_id;
            }
            $query .= " AND nm.semestre_id IN (" . implode(',', $placeholders) . ")";
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $notes];
    } catch (Exception $e) {
        ($this->logger)("Error in getModuleNotes: semestre_number=$semestre_number, annee_id=$annee_id, error=" . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Fetch semester notes for a given semester and year.
 *
 * @param int|null $semestre_number Numeric part of semester nom (1-6) or null
 * @param string $annee_id The academic year in YYYY-YYYY format
 * @return array List of semester notes
 * @throws Exception If inputs are invalid or database errors occur
 */
public function getSemesterNotes($semestre_number, $annee_id)
{
    try {
        if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
            throw new Exception("Invalid annee_id: must be in YYYY-YYYY format", 400);
        }
        if ($semestre_number !== null && (!is_numeric($semestre_number) || $semestre_number < 1 || $semestre_number > 6)) {
            throw new Exception("Invalid semestre_number: must be an integer between 1 and 6", 400);
        }

        $query = "
            SELECT ns.student_id, ns.semestre_id, ns.annee_id, ns.etape_id,
                   ns.note_semestre, ns.decision, ns.nv_module_count,
                   CONCAT(e.nom, ' ', e.prenom) as student_name
            FROM note_semestres ns
            JOIN etudiants e ON ns.student_id = e.user_id
            WHERE ns.annee_id = :annee_id
        ";
        $params = ['annee_id' => $annee_id];

        if ($semestre_number !== null) {
            $semesterQuery = "
                SELECT DISTINCT se.semestre_id, se.field_id, se.etape_id
                FROM student_enrollments se
                WHERE se.annee_id = :annee_id
            ";
            $semesterStmt = $this->db->prepare($semesterQuery);
            $semesterStmt->execute(['annee_id' => $annee_id]);
            $semesters = $semesterStmt->fetchAll(PDO::FETCH_ASSOC);

            $semestre_ids = [];
            foreach ($semesters as $semester) {
                $semestre_id = $this->resolveSemesterId($semestre_number, $semester['field_id'], $semester['etape_id']);
                if ($semestre_id) {
                    $semestre_ids[] = $semestre_id;
                }
            }

            if (empty($semestre_ids)) {
                ($this->logger)("No semesters found for semestre_number=$semestre_number, annee_id=$annee_id");
                return ['success' => true, 'data' => []];
            }

            $placeholders = [];
            foreach ($semestre_ids as $index => $semestre_id) {
                $placeholder = ":semestre_id$index";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $semestre_id;
            }
            $query .= " AND ns.semestre_id IN (" . implode(',', $placeholders) . ")";
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $notes];
    } catch (Exception $e) {
        ($this->logger)("Error in getSemesterNotes: semestre_number=$semestre_number, annee_id=$annee_id, error=" . $e->getMessage());
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
            SELECT na.student_id, na.annee_id, na.etape_id, na.note_annee,
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