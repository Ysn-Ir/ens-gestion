<?php
require_once __DIR__ . '/../utils/Database.php';

class NoteModel
{
    private $db;

    /**
     * Initialize the NoteModel with a database connection.
     */
    public function __construct()
    {
        $this->db = (new Database())->getConnection();
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

            // Check required columns in note-related tables
            $requiredColumns = [
                'note_modules' => ['student_id', 'module_id', 'semestre_id', 'annee_id', 'note_module', 'decision'],
                'notes' => ['student_id', 'element_id', 'semestre_id', 'annee_id', 'note_tp', 'note_cc', 'note_exam', 'note_rattrapage', 'note_finale', 'decision', 'decision_ratt'],
                'note_semestres' => ['student_id', 'semestre_id', 'annee_id', 'note_semestre', 'decision', 'nv_module_count'],
                'note_annees' => ['student_id', 'annee_id', 'note_annee', 'decision_annee']
            ];
            foreach ($requiredColumns as $table => $columns) {
                $columnCheckQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()";
                $columnStmt = $this->db->prepare($columnCheckQuery);
                $columnStmt->execute([$table]);
                $existingColumns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);
                $missingColumns = array_diff($columns, $existingColumns);
                if (!empty($missingColumns)) {
                    throw new Exception("Missing columns in $table: " . implode(', ', $missingColumns), 400);
                }
            }

            // Fetch students enrolled in the given semester and year with their field_id
            $studentQuery = "
                SELECT DISTINCT se.student_id, e.field_id
                FROM student_enrollments se
                JOIN etudiants e ON se.student_id = e.user_id
                WHERE se.semestre_id = :semestre_id AND se.annee_id = :annee_id
            ";
            $studentStmt = $this->db->prepare($studentQuery);
            $studentStmt->execute(['semestre_id' => $semestre_id, 'annee_id' => $annee_id]);
            $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($students)) {
                return ['success' => true, 'message' => "No students enrolled for semestre_id=$semestre_id, annee_id=$annee_id"];
            }

            foreach ($students as $student) {
                $student_id = $student['student_id'];
                $field_id = $student['field_id'];

                // Fetch modules and their elements for the semester, year, and student's field
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
                    continue; // Skip to next student if no modules for their field
                }

                // Generate rows for each student
                foreach ($modules as $module) {
                    if (!empty($module['element_id'])) {
                        $noteQuery = "
                            INSERT IGNORE INTO notes (
                                student_id, element_id, semestre_id, annee_id,
                                note_tp, note_cc, note_exam, note_rattrapage,
                                note_finale, decision, decision_ratt
                            )
                            VALUES (
                                :student_id, :element_id, :semestre_id, :annee_id,
                                NULL, NULL, NULL, NULL,
                                NULL, NULL, NULL
                            )
                        ";
                        $noteStmt = $this->db->prepare($noteQuery);
                        $noteStmt->execute([
                            'student_id' => $student_id,
                            'element_id' => $module['element_id'],
                            'semestre_id' => $semestre_id,
                            'annee_id' => $annee_id
                        ]);
                    }

                    // Generate rows in note_modules table
                    $moduleNoteQuery = "
                        INSERT IGNORE INTO note_modules (
                            student_id, module_id, semestre_id, annee_id,
                            note_module, decision
                        )
                        VALUES (
                            :student_id, :module_id, :semestre_id, :annee_id,
                            NULL, NULL
                        )
                    ";
                    $moduleNoteStmt = $this->db->prepare($moduleNoteQuery);
                    $moduleNoteStmt->execute([
                        'student_id' => $student_id,
                        'module_id' => $module['module_id'],
                        'semestre_id' => $semestre_id,
                        'annee_id' => $annee_id
                    ]);
                }

                // Generate row in note_semestres table
                $semesterNoteQuery = "
                    INSERT IGNORE INTO note_semestres (
                        student_id, semestre_id, annee_id,
                        note_semestre, decision, nv_module_count
                    )
                    VALUES (
                        :student_id, :semestre_id, :annee_id,
                        NULL, NULL, NULL
                    )
                ";
                $semesterNoteStmt = $this->db->prepare($semesterNoteQuery);
                $semesterNoteStmt->execute([
                    'student_id' => $student_id,
                    'semestre_id' => $semestre_id,
                    'annee_id' => $annee_id
                ]);

                // Generate row in note_annees table
                $yearNoteQuery = "
                    INSERT IGNORE INTO note_annees (
                        student_id, annee_id, note_annee, decision_annee
                    )
                    VALUES (
                        :student_id, :annee_id, NULL, NULL
                    )
                ";
                $yearNoteStmt = $this->db->prepare($yearNoteQuery);
                $yearNoteStmt->execute([
                    'student_id' => $student_id,
                    'annee_id' => $annee_id
                ]);
            }

            return ['success' => true, 'message' => "Empty note rows generated for semestre_id=$semestre_id, annee_id=$annee_id"];
        } catch (PDOException $e) {
            error_log("PDO Error in generateEmptyNoteRows: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => 'Database error in generateEmptyNoteRows: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error in generateEmptyNoteRows: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
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

            // Check if student_enrollments table and required columns exist
            $requiredColumns = [
                'student_enrollments' => ['student_id', 'semestre_id', 'annee_id'],
                'notes' => ['student_id', 'element_id', 'semestre_id', 'annee_id', 'note_finale', 'decision', 'decision_ratt'],
                'note_modules' => ['student_id', 'module_id', 'semestre_id', 'annee_id', 'note_module', 'decision'],
                'note_semestres' => ['student_id', 'semestre_id', 'annee_id', 'note_semestre', 'decision', 'nv_module_count']
            ];
            foreach ($requiredColumns as $table => $columns) {
                $columnCheckQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()";
                $columnStmt = $this->db->prepare($columnCheckQuery);
                $columnStmt->execute([$table]);
                $existingColumns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);
                $missingColumns = array_diff($columns, $existingColumns);
                if (!empty($missingColumns)) {
                    throw new Exception("Missing columns in $table: " . implode(', ', $missingColumns), 400);
                }
            }

            // Fetch students for the semester and year
            $studentQuery = "
                SELECT DISTINCT student_id
                FROM student_enrollments
                WHERE semestre_id = :semestre_id AND annee_id = :annee_id
            ";
            $studentStmt = $this->db->prepare($studentQuery);
            error_log("Executing query in calculateAllFinalNotes: SELECT DISTINCT student_id FROM student_enrollments WHERE semestre_id = $semestre_id AND annee_id = '$annee_id'");
            $studentStmt->execute(['semestre_id' => $semestre_id, 'annee_id' => $annee_id]);
            $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($students)) {
                return ['success' => true, 'message' => "No students found for semestre_id=$semestre_id, annee_id=$annee_id"];
            }

            foreach ($students as $student) {
                $student_id = $student['student_id'];

                // Calculate element final notes
                $elementQuery = "
                    SELECT n.note_id, n.note_tp, n.note_cc, n.note_exam, n.note_rattrapage,
                           e.coeff_element, e.coeff_tp, e.coeff_cc, e.coeff_ecrit
                    FROM notes n
                    JOIN elements e ON n.element_id = e.element_id
                    WHERE n.student_id = :student_id AND n.semestre_id = :semestre_id AND n.annee_id = :annee_id
                ";
                $elementStmt = $this->db->prepare($elementQuery);
                $elementStmt->execute(['student_id' => $student_id, 'semestre_id' => $semestre_id, 'annee_id' => $annee_id]);
                $elements = $elementStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($elements as $element) {
                    $note_finale = null;
                    $decision = null;
                    $decision_ratt = null;

                    // Normalize coefficients
                    $total_coeff = $element['coeff_tp'] + $element['coeff_cc'] + $element['coeff_ecrit'];
                    if ($total_coeff > 0) {
                        $coeff_tp = $element['coeff_tp'] / $total_coeff;
                        $coeff_cc = $element['coeff_cc'] / $total_coeff;
                        $coeff_ecrit = $element['coeff_ecrit'] / $total_coeff;
                    } else {
                        // Default to equal weighting if coefficients are zero
                        $coeff_tp = $coeff_cc = $coeff_ecrit = 1/3;
                    }

                    // Calculate final note if all required grades are present
                    if (isset($element['note_exam'], $element['note_tp'], $element['note_cc']) && !isset($element['note_rattrapage'])) {
                        $note_finale = ($element['note_tp'] * $coeff_tp) + ($element['note_cc'] * $coeff_cc) + ($element['note_exam'] * $coeff_ecrit);
                        $decision = ($note_finale >= 10) ? 'V' : 'R';
                    } elseif (isset($element['note_rattrapage'], $element['note_tp'], $element['note_cc'])) {
                        $note_ratt = ($element['note_tp'] * $coeff_tp) + ($element['note_cc'] * $coeff_cc) + ($element['note_rattrapage'] * $coeff_ecrit);
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

                // Calculate module notes
                $moduleQuery = "
                    SELECT m.module_id, m.coefficient, AVG(n.note_finale * e.coeff_element / 100) as avg_note
                    FROM note_modules nm
                    JOIN modules m ON nm.module_id = m.module_id
                    JOIN elements e ON m.module_id = e.module_id
                    JOIN notes n ON e.element_id = n.element_id AND n.student_id = nm.student_id
                    WHERE nm.student_id = :student_id AND nm.semestre_id = :semestre_id AND nm.annee_id = :annee_id
                    GROUP BY m.module_id
                ";
                $moduleStmt = $this->db->prepare($moduleQuery);
                $moduleStmt->execute(['student_id' => $student_id, 'semestre_id' => $semestre_id, 'annee_id' => $annee_id]);
                $modules = $moduleStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($modules as $module) {
                    $note_module = $module['avg_note'];
                    $decision = ($note_module >= 10) ? 'V' : 'NV';

                    $updateModuleQuery = "
                        UPDATE note_modules
                        SET note_module = :note_module, decision = :decision
                        WHERE student_id = :student_id AND module_id = :module_id AND semestre_id = :semestre_id AND annee_id = :annee_id
                    ";
                    $updateModuleStmt = $this->db->prepare($updateModuleQuery);
                    $updateModuleStmt->execute([
                        'note_module' => $note_module,
                        'decision' => $decision,
                        'student_id' => $student_id,
                        'module_id' => $module['module_id'],
                        'semestre_id' => $semestre_id,
                        'annee_id' => $annee_id
                    ]);
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
                    if ($note_semestre >= 10 && $nv_count == 0) {
                        $decision = 'V';
                    } elseif ($note_semestre >= 10 && $nv_count <= 2) {
                        $decision = 'VPC';
                    } elseif ($note_semestre < 8) {
                        $decision = 'F';
                    } else {
                        $decision = 'NV';
                    }
                }

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

            return ['success' => true, 'message' => "Semester notes calculated successfully for semestre_id=$semestre_id"];
        } catch (PDOException $e) {
            error_log("PDO Error in calculateAllFinalNotes: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => 'Database error in calculateAllFinalNotes: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error in calculateAllFinalNotes: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
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

            // Check if student_enrollments and note_annees tables and required columns exist
            $requiredColumns = [
                'student_enrollments' => ['student_id', 'annee_id'],
                'note_annees' => ['student_id', 'annee_id', 'note_annee', 'decision_annee']
            ];
            foreach ($requiredColumns as $table => $columns) {
                $columnCheckQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()";
                $columnStmt = $this->db->prepare($columnCheckQuery);
                $columnStmt->execute([$table]);
                $existingColumns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);
                $missingColumns = array_diff($columns, $existingColumns);
                if (!empty($missingColumns)) {
                    throw new Exception("Missing columns in $table: " . implode(', ', $missingColumns), 400);
                }
            }

            // Fetch students for the year
            $studentQuery = "
                SELECT DISTINCT student_id
                FROM student_enrollments
                WHERE annee_id = :annee_id
            ";
            $studentStmt = $this->db->prepare($studentQuery);
            error_log("Executing query in calculateYearFinalNotes: SELECT DISTINCT student_id FROM student_enrollments WHERE annee_id = '$annee_id'");
            $studentStmt->execute(['annee_id' => $annee_id]);
            $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($students)) {
                return ['success' => true, 'message' => "No students found for annee_id=$annee_id"];
            }

            foreach ($students as $student) {
                $student_id = $student['student_id'];

                // Calculate year note (equal weighting for semesters)
                $yearQuery = "
                    SELECT AVG(ns.note_semestre) as note_annee,
                           COUNT(CASE WHEN ns.decision IN ('NV', 'F') THEN 1 END) as nv_count
                    FROM note_semestres ns
                    WHERE ns.student_id = :student_id AND ns.annee_id = :annee_id
                ";
                $yearStmt = $this->db->prepare($yearQuery);
                error_log("Executing year query for student_id=$student_id, annee_id=$annee_id");
                $yearStmt->execute(['student_id' => $student_id, 'annee_id' => $annee_id]);
                $year = $yearStmt->fetch(PDO::FETCH_ASSOC);

                $note_annee = $year['note_annee'] ?? null;
                $nv_count = $year['nv_count'] ?? 0;
                $decision_annee = null;
                if ($note_annee !== null) {
                    if ($note_annee >= 10 && $nv_count == 0) {
                        $decision_annee = 'V';
                    } elseif ($note_annee >= 10 && $nv_count <= 1) {
                        $decision_annee = 'VPC';
                    } elseif ($note_annee < 8) {
                        $decision_annee = 'F';
                    } else {
                        $decision_annee = 'NV';
                    }
                }

                $updateYearQuery = "
                    UPDATE note_annees
                    SET note_annee = :note_annee, decision_annee = :decision_annee
                    WHERE student_id = :student_id AND annee_id = :annee_id
                ";
                $updateYearStmt = $this->db->prepare($updateYearQuery);
                $updateYearStmt->execute([
                    'note_annee' => $note_annee,
                    'decision_annee' => $decision_annee,
                    'student_id' => $student_id,
                    'annee_id' => $annee_id
                ]);
            }

            return ['success' => true, 'message' => "Year notes calculated successfully for annee_id=$annee_id"];
        } catch (PDOException $e) {
            error_log("PDO Error in calculateYearFinalNotes: annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => 'Database error in calculateYearFinalNotes: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error in calculateYearFinalNotes: annee_id=$annee_id, error=" . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>