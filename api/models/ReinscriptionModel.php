<?php
require_once __DIR__ . '/../utils/Database.php';

class ReinscriptionModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    private function getCurrentAcademicYear(): ?string {
        $stmt = $this->conn->prepare("SELECT annee_id FROM annees_academiques WHERE current_flag = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetchColumn() ?: null;
    }

    private function getNextAcademicYear(string $currentYear): ?string {
        $parts = explode('-', $currentYear);
        return count($parts) === 2 ? ((int)$parts[0] + 1) . '-' . ((int)$parts[1] + 1) : null;
    }

    private function getCycleDurationInYears(int $cycleId): int {
        $stmt = $this->conn->prepare("SELECT Nombre_semestre FROM cycles WHERE cycle_id = ?");
        $stmt->execute([$cycleId]);
        $semesters = $stmt->fetchColumn();

        if ($semesters === false || $semesters === null) {
            throw new Exception("Cycle ID {$cycleId} not found in the database.");
        }
        if ($semesters <= 0) {
            throw new Exception("Invalid semester count ({$semesters}) for cycle ID {$cycleId}.");
        }

        return (int)ceil($semesters / 2);
    }

    private function isLastStepOfCycle(int $studentId, string $academicYear): bool {
        $stmt = $this->conn->prepare(
            "SELECT se.etape_id, se.cycle_id,
                    (SELECT COUNT(*) FROM note_modules nm 
                     WHERE nm.student_id = se.student_id 
                     AND nm.annee_id = se.annee_id 
                     AND nm.retake_status = 'scheduled') as modules_en_dette
             FROM student_enrollments se
             WHERE se.student_id = :sid AND se.annee_id = :ay
             ORDER BY se.enrollment_id DESC LIMIT 1"
        );
        $stmt->execute([':sid' => $studentId, ':ay' => $academicYear]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            error_log("No enrollment found for student {$studentId} in year {$academicYear}.");
            return false;
        }

        try {
            $cycleDuration = $this->getCycleDurationInYears((int)$enrollment['cycle_id']);
        } catch (Exception $e) {
            error_log("Error getting cycle duration for student {$studentId}: " . $e->getMessage());
            return false;
        }

        $isLastStep = (int)$enrollment['etape_id'] >= $cycleDuration;

        if ($enrollment['modules_en_dette'] > 0) {
            return false;
        }

        return $isLastStep;
    }

    private function ensureAcademicYearExists(string $academicYear): void {
        try {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO annees_academiques (annee_id) VALUES (?)");
            $stmt->execute([$academicYear]);
        } catch (PDOException $e) {
            error_log("Failed to create academic year {$academicYear}: " . $e->getMessage());
            throw $e;
        }
    }

    public function getStudentsToReEnroll(): array {
        $current_year = $this->getCurrentAcademicYear();
        if (!$current_year) throw new Exception("Année académique actuelle non définie.");
        $classified = ['annee_non_valide' => [], 'modules_restants' => []];
        $query_failed = "
            SELECT e.user_id, e.nom, e.prenom, e.cne, et.nom_etape AS current_etape_nom,
                   GROUP_CONCAT(DISTINCT m.nom SEPARATOR ', ') AS modules_nv_noms
            FROM etudiants e
            INNER JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :cy
            LEFT JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = :cy
            LEFT JOIN etapes et ON se.etape_id = et.etape_id
            LEFT JOIN note_modules nm ON e.user_id = nm.student_id AND nm.annee_id = :cy AND nm.decision <> 'V'
            LEFT JOIN modules m ON nm.module_id = m.module_id
            WHERE e.actuel = 1 AND na.decision_annee IN ('F', 'NV')
            GROUP BY e.user_id ORDER BY e.nom;
        ";
        $stmt_failed = $this->conn->prepare($query_failed);
        $stmt_failed->execute([':cy' => $current_year]);
        $classified['annee_non_valide'] = $stmt_failed->fetchAll(PDO::FETCH_ASSOC);
        $query_debt = "
            SELECT e.user_id, e.nom, e.prenom, e.cne, et.nom_etape AS current_etape_nom,
                   GROUP_CONCAT(DISTINCT m.nom SEPARATOR ', ') AS modules_nv_noms
            FROM etudiants e
            INNER JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :cy
            LEFT JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = :cy
            LEFT JOIN etapes et ON se.etape_id = et.etape_id
            INNER JOIN note_modules nm ON e.user_id = nm.student_id AND nm.annee_id = :cy AND nm.retake_status = 'scheduled'
            INNER JOIN modules m ON nm.module_id = m.module_id
            WHERE e.actuel = 1 AND na.decision_annee IN ('V', 'VPC')
            GROUP BY e.user_id
            ORDER BY e.nom;
        ";
        $stmt_debt = $this->conn->prepare($query_debt);
        $stmt_debt->execute([':cy' => $current_year]);
        $classified['modules_restants'] = $stmt_debt->fetchAll(PDO::FETCH_ASSOC);
        return $classified;
    }

    public function getStudentsReadyForNextCycle(): array {
        $current_year = $this->getCurrentAcademicYear();
        if (!$current_year) return [];
        $query = "
            SELECT e.user_id, e.nom, e.prenom, e.cne, c.nom as current_cycle_nom, se.cycle_id as current_cycle_id
            FROM etudiants e
            INNER JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = :current_year
            INNER JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :current_year
            INNER JOIN cycles c ON se.cycle_id = c.cycle_id 
            WHERE e.actuel = 1
            AND na.decision_annee NOT IN ('F', 'NV')
            AND (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :current_year AND retake_status = 'scheduled') = 0
            GROUP BY e.user_id ORDER BY e.nom;
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':current_year' => $current_year]);
        $potentialStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $readyStudents = [];
        foreach ($potentialStudents as $student) {
            if ($this->isLastStepOfCycle($student['user_id'], $current_year)) {
                $readyStudents[] = $student;
            }
        }
        return $readyStudents;
    }

    public function getPassingStudents(): array {
        $current_year = $this->getCurrentAcademicYear();
        if (!$current_year) return [];
        
        $query = "
            SELECT e.user_id, e.nom, e.prenom, e.cne, et.nom_etape as etape_actuelle,
                   (SELECT nom_etape FROM etapes WHERE etape_id = se.etape_id + 1) as prochaine_etape
            FROM etudiants e
            INNER JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = :ay
            INNER JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :ay
            INNER JOIN etapes et ON se.etape_id = et.etape_id
            WHERE e.actuel = 1
              AND na.decision_annee NOT IN ('F', 'NV')
              AND (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :ay AND retake_status = 'scheduled') = 0
              AND NOT EXISTS (SELECT 1 FROM student_enrollments se2 WHERE se2.student_id = e.user_id AND se2.annee_id = :next_ay)
            GROUP BY e.user_id;
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':ay' => $current_year, ':next_ay' => $this->getNextAcademicYear($current_year)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGraduatedStudents(string $searchNom = null, string $searchCne = null): array {
        $params = [];
        $searchSql = "";
        if ($searchNom) {
            $searchSql .= " AND (e.nom LIKE :nom OR e.prenom LIKE :nom)";
            $params[':nom'] = '%' . $searchNom . '%';
        }
        if ($searchCne) {
            $searchSql .= " AND e.cne LIKE :cne";
            $params[':cne'] = '%' . $searchCne . '%';
        }
        $query = "
            SELECT e.user_id, e.nom, e.prenom, e.cne, c.nom as cycle_nom, d.nom as diplome_nom
            FROM etudiants e
            INNER JOIN cycles c ON e.cycle_id = c.cycle_id
            LEFT JOIN diplomes d ON e.field_id = d.field_id
            WHERE e.actuel = 1 
              AND e.user_id NOT IN (SELECT student_id FROM student_enrollments WHERE annee_id = :current_year)
              {$searchSql}
            GROUP BY e.user_id ORDER BY e.nom;
        ";
        $params[':current_year'] = $this->getCurrentAcademicYear();
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentHistory(int $studentId): array {
        $query = "
            SELECT na.annee_id, et.nom_etape, c.nom as nom_cycle, na.note_annee, na.decision_annee, na.fail_count,
                   (SELECT GROUP_CONCAT(m.nom SEPARATOR ', ') 
                    FROM note_modules nm 
                    JOIN modules m ON nm.module_id = m.module_id 
                    WHERE nm.student_id = na.student_id 
                      AND nm.annee_id = na.annee_id 
                      AND nm.retake_status = 'scheduled') as modules_en_dette
            FROM note_annees na
            LEFT JOIN student_enrollments se ON na.student_id = se.student_id AND na.annee_id = se.annee_id
            LEFT JOIN etapes et ON se.etape_id = et.etape_id
            LEFT JOIN cycles c ON se.cycle_id = c.cycle_id
            WHERE na.student_id = :student_id
            GROUP BY na.annee_id ORDER BY na.annee_id DESC;
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':student_id' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFieldsForCycle(int $cycleId): array {
        $stmt = $this->conn->prepare("SELECT field_id, nom FROM filieres WHERE cycle_id = ?");
        $stmt->execute([$cycleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAllStudentsWithEnrollment(string $academicYear): array {
        $query = "
            SELECT 
                e.user_id, e.nom, e.prenom, e.cne, se.etape_id, et.nom_etape, se.cycle_id, c.nom as cycle_nom,
                na.decision_annee,
                (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :ay AND retake_status = 'scheduled') as modules_en_dette
            FROM etudiants e
            INNER JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = :ay
            INNER JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :ay
            INNER JOIN cycles c ON se.cycle_id = c.cycle_id
            INNER JOIN etapes et ON se.etape_id = et.etape_id
            WHERE e.actuel = 1
            GROUP BY e.user_id;
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':ay' => $academicYear]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function enrollSimplePassingStudent(int $studentId): void {
        $currentAcademicYear = $this->getCurrentAcademicYear();
        $nextAcademicYear = $this->getNextAcademicYear($currentAcademicYear);

        $stmt_info = $this->conn->prepare(
            "SELECT field_id, cycle_id, etape_id FROM student_enrollments 
             WHERE student_id = :sid AND annee_id = :ay 
             ORDER BY enrollment_id DESC LIMIT 1"
        );
        $stmt_info->execute([':sid' => $studentId, ':ay' => $currentAcademicYear]);
        $enrollmentInfo = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$enrollmentInfo) {
            error_log("Skipping student {$studentId}: no current enrollment found.");
            return;
        }

        $nextEtapeId = (int)$enrollmentInfo['etape_id'] + 1;

        $stmt_semestre = $this->conn->prepare("SELECT semestre_id FROM semestres WHERE etape_id = ? AND field_id = ? ORDER BY semestre_id ASC LIMIT 1");
        $stmt_semestre->execute([$nextEtapeId, $enrollmentInfo['field_id']]);
        $nextSemesterId = $stmt_semestre->fetchColumn();

        if (!$nextSemesterId) {
            error_log("Skipping student {$studentId}: no semester found for next step {$nextEtapeId}.");
            return;
        }

        $stmt_insert = $this->conn->prepare("INSERT INTO student_enrollments (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt_insert->execute([$studentId, $nextAcademicYear, $nextSemesterId, $enrollmentInfo['cycle_id'], $enrollmentInfo['field_id'], $nextEtapeId]);

        $this->createOrUpdateNoteAnnee($studentId, $nextAcademicYear, $nextEtapeId);
    }

    public function enrollAllPassingStudents(): array {
        $this->conn->beginTransaction();
        try {
            $currentAcademicYear = $this->getCurrentAcademicYear();
            $nextAcademicYear = $this->getNextAcademicYear($currentAcademicYear);
            $this->ensureAcademicYearExists($nextAcademicYear);

            $students = $this->getPassingStudents();
            if (empty($students)) {
                return ['success' => true, 'message' => 'Aucun étudiant à inscrire.'];
            }

            $count = 0;
            foreach ($students as $student) {
                $this->enrollSimplePassingStudent($student['user_id']);
                $count++;
            }

            $this->conn->commit();
            return ['success' => true, 'message' => "{$count} étudiant(s) ont été réinscrits avec succès à l'étape suivante."];

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function createInitialGradeRecords(int $studentId, string $academicYear, int $etapeId, array $modulesForStep, array $modulesToRetake = []): void {
        $allModules = array_merge($modulesForStep, $modulesToRetake);
        if (empty($allModules)) {
            error_log("Avertissement: Aucun nouveau module ou module en dette à inscrire pour l'étudiant {$studentId} pour l'année {$academicYear}. Seuls les modules validés seront reportés.");
            return;
        }

        $stmt_insert_note_semestre = $this->conn->prepare("INSERT IGNORE INTO note_semestres (student_id, semestre_id, annee_id, etape_id) VALUES (?, ?, ?, ?)");
        $stmt_insert_note_module = $this->conn->prepare("INSERT IGNORE INTO note_modules (student_id, module_id, semestre_id, annee_id, etape_id) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert_note_element = $this->conn->prepare("INSERT IGNORE INTO notes (student_id, element_id, semestre_id, annee_id, etape_id) VALUES (?, ?, ?, ?, ?)");
        $stmt_get_elements = $this->conn->prepare("SELECT element_id FROM elements WHERE module_id = ?");

        $processedSemesters = [];
        foreach ($allModules as $module) {
            $moduleId = $module['module_id'];
            $semestreId = $module['semestre_id'];

            if (!in_array($semestreId, $processedSemesters)) {
                $stmt_insert_note_semestre->execute([$studentId, $semestreId, $academicYear, $etapeId]);
                $processedSemesters[] = $semestreId;
            }
            $stmt_insert_note_module->execute([$studentId, $moduleId, $semestreId, $academicYear, $etapeId]);
            $stmt_get_elements->execute([$moduleId]);
            $elements = $stmt_get_elements->fetchAll(PDO::FETCH_COLUMN);
            foreach ($elements as $elementId) {
                $stmt_insert_note_element->execute([$studentId, $elementId, $semestreId, $academicYear, $etapeId]);
            }
        }
    }

    private function getModulesForStep(int $fieldId, int $etapeId): array {
        $stmt = $this->conn->prepare(
            "SELECT m.module_id, m.semestre_id 
             FROM modules m
             JOIN semestres s ON m.semestre_id = s.semestre_id
             WHERE s.field_id = ? AND s.etape_id = ?"
        );
        $stmt->execute([$fieldId, $etapeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function enrollPassingStudentsInBulk(array $studentIds): array {
        $this->conn->beginTransaction();
        try {
            $currentAcademicYear = $this->getCurrentAcademicYear();
            $nextAcademicYear = $this->getNextAcademicYear($currentAcademicYear);
            $this->ensureAcademicYearExists($nextAcademicYear);
            
            $successCount = 0;
            $errors = [];

            $stmt_info = $this->conn->prepare(
                "SELECT field_id, cycle_id, etape_id FROM student_enrollments 
                 WHERE student_id = :sid AND annee_id = :ay ORDER BY enrollment_id DESC LIMIT 1"
            );
            $stmt_semestre = $this->conn->prepare("SELECT semestre_id FROM semestres WHERE etape_id = ? AND field_id = ? ORDER BY semestre_id ASC LIMIT 1");
            $stmt_insert = $this->conn->prepare("INSERT INTO student_enrollments (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt_note = $this->conn->prepare(
                "INSERT INTO note_annees (student_id, annee_id, etape_id, fail_count) 
                 VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE fail_count = 0"
            );

            foreach ($studentIds as $studentId) {
                try {
                    $stmt_check = $this->conn->prepare("SELECT COUNT(*) FROM student_enrollments WHERE student_id = ? AND annee_id = ?");
                    $stmt_check->execute([$studentId, $nextAcademicYear]);
                    if ($stmt_check->fetchColumn() > 0) {
                        throw new Exception("Déjà réinscrit pour l'année suivante.");
                    }

                    $stmt_info->execute([':sid' => $studentId, ':ay' => $currentAcademicYear]);
                    $enrollmentInfo = $stmt_info->fetch(PDO::FETCH_ASSOC);

                    if (!$enrollmentInfo) {
                        throw new Exception("Aucune inscription trouvée pour l'année en cours.");
                    }
                    
                    if ($this->isLastStepOfCycle($studentId, $currentAcademicYear)) {
                        throw new Exception("Changement de cycle requis, ne peut être traité en masse.");
                    }

                    $nextEtapeId = (int)$enrollmentInfo['etape_id'] + 1;

                    $stmt_semestre->execute([$nextEtapeId, $enrollmentInfo['field_id']]);
                    $nextSemesterId = $stmt_semestre->fetchColumn();

                    if (!$nextSemesterId) {
                        throw new Exception("Configuration BD incomplète: semestre non trouvé pour l'étape {$nextEtapeId}.");
                    }

                    $stmt_insert->execute([$studentId, $nextAcademicYear, $nextSemesterId, $enrollmentInfo['cycle_id'], $enrollmentInfo['field_id'], $nextEtapeId]);
                    $stmt_note->execute([$studentId, $nextAcademicYear, $nextEtapeId]);
                    
                    
                    $successCount++;

                } catch (Exception $e) {
                    $errors[] = "Étudiant ID {$studentId}: " . $e->getMessage();
                }
            }

            $this->conn->commit();
            return ['success_count' => $successCount, 'errors' => $errors];

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Transaction échouée: " . $e->getMessage());
        }
    }

    public function processStudentProgression(int $studentId, ?int $newFieldId = null): array {
        $this->conn->beginTransaction();
        try {
            $currentAcademicYear = $this->getCurrentAcademicYear();
            if (!$currentAcademicYear) throw new Exception("Année académique actuelle non définie.");

            $this->ensureAcademicYearExists($this->getNextAcademicYear($currentAcademicYear));

            $studentInfo = $this->getStudentState($studentId, $currentAcademicYear);
            if (!$studentInfo) {
                throw new Exception("Données de base de l'étudiant manquantes pour l'année en cours.");
            }

            $hasPassedYear = in_array($studentInfo['decision_annee'], ['V', 'VPC']);
            $hasDebt = $studentInfo['modules_en_dette'] > 0;
            $isLastStep = $this->isLastStepOfCycle($studentId, $currentAcademicYear);

            $message = "";

            if (!$hasPassedYear) {
                $message = $this->enrollRedoublement($studentId, $studentInfo)."nigga1";
            } elseif ($isLastStep) {
                if ($hasDebt) {
                    $message = $this->enrollRedoublement($studentId, $studentInfo)."nigga2";
                } else {
                    if ($newFieldId === null && $studentInfo['cycle_id'] < 4) {
                        throw new Exception("Le choix d'une nouvelle filière est obligatoire pour passer au cycle suivant.");
                    }
                    $message = $this->enrollNextCycle($studentId, $studentInfo, $newFieldId)."nigga3";
                }
            } else {
                $message = $this->enrollSimplePassage($studentId, $studentInfo)."nigga4";
            }

            $this->conn->commit();
            return ['success' => true, 'message' => $message];

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function enrollRedoublement(int $studentId, array $studentInfo): string {
        if ($studentInfo['fail_count'] >= 2) {
            throw new Exception("Réinscription bloquée : nombre maximum de redoublements atteint.");
        }
        
        $currentAcademicYear = $studentInfo['annee_id'];
        $nextAcademicYear = $this->getNextAcademicYear($currentAcademicYear);
        $etapeId = $studentInfo['current_etape_id'];
        $fieldId = $studentInfo['field_id'];
        $cycleId = $studentInfo['cycle_id'];

        $this->createEnrollmentRecords($studentId, $nextAcademicYear, $etapeId, $fieldId, $cycleId);

        $modules = $this->getModulesForStep($fieldId, $etapeId);
        $this->createInitialGradeRecords($studentId, $nextAcademicYear, $etapeId, $modules);
        
        $this->copyValidatedResults($studentId, $currentAcademicYear, $nextAcademicYear);

        $stmt = $this->conn->prepare("SELECT fail_count FROM note_annees WHERE student_id = ? AND annee_id = ? AND etape_id = ?");
        $stmt->execute([$studentId, $currentAcademicYear, $etapeId]);
        $currentFailCount = $stmt->fetchColumn() ?: 0;
        $newFailCount = $currentFailCount + 1;

        $this->createOrUpdateNoteAnnee($studentId, $nextAcademicYear, $etapeId, $newFailCount);

        return "Étudiant réinscrit en redoublement pour l'année {$nextAcademicYear}. Les modules déjà validés ont été reportés.";
    }

    private function enrollNextCycle(int $studentId, array $studentInfo, ?int $newFieldId): string {
        $nextAcademicYear = $this->getNextAcademicYear($studentInfo['annee_id']);
        $nextCycleId = $studentInfo['cycle_id'] + 1;
        $nextEtapeId = 1;

        if ($nextCycleId > 4) {
            return "Félicitations ! L'étudiant a terminé son cursus et est prêt pour la diplomation.";
        }

        $this->createEnrollmentRecords($studentId, $nextAcademicYear, $nextEtapeId, $newFieldId, $nextCycleId);
        $modules = $this->getModulesForStep($newFieldId, $nextEtapeId);
        $this->createInitialGradeRecords($studentId, $nextAcademicYear, $nextEtapeId, $modules);
        $this->createOrUpdateNoteAnnee($studentId, $nextAcademicYear, $nextEtapeId);

        $stmt = $this->conn->prepare("UPDATE etudiants SET cycle_id = ?, field_id = ? WHERE user_id = ?");
        $stmt->execute([$nextCycleId, $newFieldId, $studentId]);

        return "Étudiant réinscrit avec succès dans le nouveau cycle pour l'année {$nextAcademicYear}.";
    }

    private function enrollSimplePassage(int $studentId, array $studentInfo): string {
        $currentAcademicYear = $studentInfo['annee_id'];
        $nextAcademicYear = $this->getNextAcademicYear($currentAcademicYear);
        $nextEtapeId = $studentInfo['current_etape_id'] + 1;
        $currentEtapeId = $studentInfo['current_etape_id'];
        $fieldId = $studentInfo['field_id'];
        $cycleId = $studentInfo['cycle_id'];

        $this->createEnrollmentRecords($studentId, $nextAcademicYear, $nextEtapeId, $fieldId, $cycleId);
        $this->createEnrollmentRecords($studentId, $nextAcademicYear, $currentEtapeId, $fieldId, $cycleId);

        $modulesNewStep = $this->getModulesForStep($fieldId, $nextEtapeId);
        $modulesOldStep = $this->getModulesForStep($fieldId, $currentEtapeId);
        
        $modulesInDebt = [];
        if ($studentInfo['modules_en_dette'] > 0) {
            $stmt = $this->conn->prepare("SELECT module_id, semestre_id FROM note_modules WHERE student_id = ? AND annee_id = ? AND retake_status = 'scheduled'");
            $stmt->execute([$studentId, $currentAcademicYear]);
            $modulesInDebt = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->createInitialGradeRecords($studentId, $nextAcademicYear, $nextEtapeId, $modulesNewStep);
        $this->createInitialGradeRecords($studentId, $nextAcademicYear, $currentEtapeId, $modulesOldStep);
        
        $this->copyValidatedResults($studentId, $currentAcademicYear, $nextAcademicYear);
        
        $this->createOrUpdateNoteAnnee($studentId, $nextAcademicYear, $nextEtapeId);
        $this->createOrUpdateNoteAnnee($studentId, $nextAcademicYear, $currentEtapeId);

        $message = "Étudiant réinscrit à l'étape suivante pour l'année {$nextAcademicYear}.";
        if (!empty($modulesInDebt)) {
            $message .= " Les modules en dette et les résultats validés ont été reportés.";
        }
        return $message;
    }

    private function copyValidatedResults(int $studentId, string $fromYear, string $toYear): void {
        $sql_elements = "
            INSERT INTO notes (student_id, element_id, semestre_id, annee_id, etape_id, note_tp, note_td, note_cc, note_exam, note_rattrapage, note_finale, decision, decision_ratt)
            SELECT 
                student_id, element_id, semestre_id, :to_year, etape_id, note_tp, note_td, note_cc, note_exam, note_rattrapage, note_finale, decision, decision_ratt
            FROM notes
            WHERE student_id = :student_id AND annee_id = :from_year AND (decision = 'V' OR decision_ratt = 'VR')
            ON DUPLICATE KEY UPDATE
                note_finale = VALUES(note_finale), decision = VALUES(decision), decision_ratt = VALUES(decision_ratt),
                note_tp = VALUES(note_tp), note_td = VALUES(note_td), note_cc = VALUES(note_cc), note_exam = VALUES(note_exam), note_rattrapage = VALUES(note_rattrapage);
        ";
        $stmt_elements = $this->conn->prepare($sql_elements);
        $stmt_elements->execute([':student_id' => $studentId, ':from_year' => $fromYear, ':to_year' => $toYear]);

        $sql_modules = "
            INSERT INTO note_modules (student_id, module_id, semestre_id, annee_id, etape_id, note_module, decision, note_ratt, decision_ratt, retake_status)
            SELECT 
                student_id, module_id, semestre_id, :to_year, etape_id, note_module, decision, note_ratt, decision_ratt, 'completed'
            FROM note_modules
            WHERE student_id = :student_id AND annee_id = :from_year AND (decision IN ('V', 'VPC') OR decision_ratt = 'VR') AND retake_status IS NULL
            ON DUPLICATE KEY UPDATE
                note_module = VALUES(note_module), decision = VALUES(decision), decision_ratt = VALUES(decision_ratt), retake_status = VALUES(retake_status);
        ";
        $stmt_modules = $this->conn->prepare($sql_modules);
        $stmt_modules->execute([':student_id' => $studentId, ':from_year' => $fromYear, ':to_year' => $toYear]);
    }

    private function getStudentState(int $studentId, string $academicYear): ?array {
        $stmt = $this->conn->prepare(
            "SELECT 
                e.field_id, se.cycle_id, se.etape_id AS current_etape_id,
                na.annee_id, na.decision_annee, na.fail_count,
                (SELECT COUNT(*) FROM note_modules WHERE student_id = :sid AND annee_id = :ay AND retake_status = 'scheduled') as modules_en_dette
            FROM etudiants e
            LEFT JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = :ay
            LEFT JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :ay
            WHERE e.user_id = :sid
            ORDER BY se.enrollment_id DESC LIMIT 1"
        );
        $stmt->execute([':sid' => $studentId, ':ay' => $academicYear]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    private function createEnrollmentRecords(int $studentId, string $academicYear, int $etapeId, int $fieldId, int $cycleId): void {
        $stmt_semestres = $this->conn->prepare("SELECT semestre_id FROM semestres WHERE etape_id = ? AND field_id = ?");
        $stmt_semestres->execute([$etapeId, $fieldId]);
        $semestersToEnroll = $stmt_semestres->fetchAll(PDO::FETCH_COLUMN);

        if (empty($semestersToEnroll)) {
            throw new Exception("Configuration BD incomplète : Aucun semestre n'est défini pour l'étape '{$etapeId}' dans la filière '{$fieldId}'.");
        }

        $stmt_insert = $this->conn->prepare("INSERT INTO student_enrollments (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        foreach ($semestersToEnroll as $semesterId) {
            $stmt_insert->execute([$studentId, $academicYear, $semesterId, $cycleId, $fieldId, $etapeId]);
        }
    }

    private function createOrUpdateNoteAnnee(int $studentId, string $academicYear, int $etapeId, int $failCount = 0): void {
        try {
            $stmt = $this->conn->prepare("SELECT 1 FROM etudiants WHERE user_id = ?");
            $stmt->execute([$studentId]);
            if (!$stmt->fetchColumn()) {
                throw new Exception("Student ID {$studentId} not found in etudiants.");
            }

            $stmt = $this->conn->prepare("SELECT 1 FROM etapes WHERE etape_id = ?");
            $stmt->execute([$etapeId]);
            if (!$stmt->fetchColumn()) {
                throw new Exception("Etape ID {$etapeId} not found in etapes.");
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO note_annees (student_id, annee_id, etape_id, note_annee, decision_annee, fail_count) 
                 VALUES (?, ?, ?, 0.00, NULL, ?) 
                 ON DUPLICATE KEY UPDATE fail_count = VALUES(fail_count)"
            );
            $stmt->execute([$studentId, $academicYear, $etapeId, $failCount]);
        } catch (PDOException $e) {
            error_log("Failed to create/update note_annees for student {$studentId}, year {$academicYear}, etape {$etapeId}: " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            error_log("Validation error in createOrUpdateNoteAnnee: " . $e->getMessage());
            throw $e;
        }
    }

}