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
        return $semesters ? (int)ceil($semesters / 2) : 0;
    }

    private function isLastStepOfCycle(int $studentId, string $academicYear): bool {
        $stmt = $this->conn->prepare(
            "SELECT se.etape_id, se.cycle_id
             FROM student_enrollments se
             WHERE se.student_id = :sid AND se.annee_id = :ay
             ORDER BY se.enrollment_id DESC LIMIT 1"
        );
        $stmt->execute([':sid' => $studentId, ':ay' => $academicYear]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) return false;

        $cycleDuration = $this->getCycleDurationInYears((int)$enrollment['cycle_id']);
        return ((int)$enrollment['etape_id'] >= $cycleDuration);
    }

    // --- Fonctions de récupération (GET) ---

    public function getStudentsToReEnroll(): array {
        $current_year = $this->getCurrentAcademicYear();
        if (!$current_year) throw new Exception("Année académique actuelle non définie.");

        $query = "
            SELECT e.user_id, e.nom, e.prenom, e.cne, et.nom_etape AS current_etape_nom, na.decision_annee,
                   (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :cy AND decision <> 'V') as modules_en_dette,
                   GROUP_CONCAT(DISTINCT CASE WHEN nm.decision <> 'V' THEN m.nom ELSE NULL END SEPARATOR ', ') AS modules_nv_noms
            FROM etudiants e
            INNER JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :cy
            LEFT JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = :cy
            LEFT JOIN etapes et ON se.etape_id = et.etape_id
            LEFT JOIN note_modules nm ON e.user_id = nm.student_id AND nm.annee_id = :cy
            LEFT JOIN modules m ON nm.module_id = m.module_id
            WHERE e.actuel = 1 AND (na.decision_annee IN ('F', 'NV') OR (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :cy AND decision <> 'V') > 0)
            GROUP BY e.user_id ORDER BY na.decision_annee DESC, e.nom;
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':cy' => $current_year]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $classified = ['annee_non_valide' => [], 'modules_restants' => []];
        foreach ($students as $student) {
            if (in_array($student['decision_annee'], ['F', 'NV'])) {
                $classified['annee_non_valide'][] = $student;
            } else {
                $classified['modules_restants'][] = $student;
            }
        }
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
            AND (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :current_year AND decision <> 'V') = 0
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

    /*
    // La fonction est maintenant en commentaire et ne sera pas exécutée.
    private function setStudentAsGraduated(int $studentId, int $cycleId, int $fieldId, string $currentAcademicYear): void {
        // 1. Identifier le diplôme correspondant à la filière et au cycle de l'étudiant
        $stmt_diplome = $this->conn->prepare(
            "SELECT diplome_id FROM diplomes WHERE cycle_id = ? AND field_id = ?"
        );
        $stmt_diplome->execute([$cycleId, $fieldId]);
        $diplomeId = $stmt_diplome->fetchColumn();

        if (!$diplomeId) {
            error_log("Avertissement : Aucun diplôme trouvé pour l'étudiant ID {$studentId} (cycle: {$cycleId}, filière: {$fieldId}). L'étudiant sera seulement désactivé.");
            $stmt_update = $this->conn->prepare("UPDATE etudiants SET actuel = 0 WHERE user_id = ?");
            $stmt_update->execute([$studentId]);
            return;
        }

        // 2. Calculer la note finale du diplôme (moyenne des notes annuelles du cycle)
        $stmt_notes = $this->conn->prepare(
            "SELECT na.note_annee 
             FROM note_annees na
             JOIN student_enrollments se ON na.student_id = se.student_id AND na.annee_id = se.annee_id
             WHERE na.student_id = ? AND se.cycle_id = ?"
        );
        $stmt_notes->execute([$studentId, $cycleId]);
        $annualNotes = $stmt_notes->fetchAll(PDO::FETCH_COLUMN);
        
        $finalGrade = 0;
        if (count($annualNotes) > 0) {
            $finalGrade = array_sum($annualNotes) / count($annualNotes);
        }

        // 3. Déterminer la mention en utilisant les règles de la base de données
        $stmt_mention = $this->conn->prepare("SELECT rule_value FROM grading_rules WHERE rule_name = 'mention_thresholds' LIMIT 1");
        $stmt_mention->execute();
        $mentionRules = json_decode($stmt_mention->fetchColumn() ?: '{}', true);
        
        $mention = 'Passable'; // Par défaut
        if (isset($mentionRules['Très Bien']) && $finalGrade >= $mentionRules['Très Bien']) {
            $mention = 'tres bien';
        } elseif (isset($mentionRules['Bien']) && $finalGrade >= $mentionRules['Bien']) {
            $mention = 'bien';
        }

        // 4. Enregistrer le diplôme dans la table `student_diplomas`
        $stmt_insert_diploma = $this->conn->prepare(
            "INSERT INTO student_diplomas (student_id, diplome_id, note, decision, mention, date_obtention, annee_id)
             VALUES (?, ?, ?, 'V', ?, CURDATE(), ?)
             ON DUPLICATE KEY UPDATE note = VALUES(note), decision = VALUES(decision), mention = VALUES(mention), date_obtention = VALUES(date_obtention)"
        );
        $stmt_insert_diploma->execute([$studentId, $diplomeId, $finalGrade, $mention, $currentAcademicYear]);

        // 5. Archiver l'étudiant en le désactivant
        $stmt_update_student = $this->conn->prepare("UPDATE etudiants SET actuel = 0 WHERE user_id = ?");
        $stmt_update_student->execute([$studentId]);
    }
    */


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
              AND (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :ay AND decision <> 'V') = 0
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
                   (SELECT GROUP_CONCAT(m.nom SEPARATOR ', ') FROM note_modules nm JOIN modules m ON nm.module_id = m.module_id WHERE nm.student_id = na.student_id AND nm.annee_id = na.annee_id AND nm.decision <> 'V') as modules_en_dette
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

    /**
     * Fonction principale de réinscription qui gère tous les cas.
     */
    public function reenrollStudent(int $studentId, ?int $newFieldId = null): array {
        $this->conn->beginTransaction();
        try {
            $currentAcademicYear = $this->getCurrentAcademicYear();
            $nextAcademicYear = $this->getNextAcademicYear($currentAcademicYear);

            // 1. Vérifications initiales
            $stmt_check = $this->conn->prepare("SELECT COUNT(*) FROM student_enrollments WHERE student_id = ? AND annee_id = ?");
            $stmt_check->execute([$studentId, $nextAcademicYear]);
            if ($stmt_check->fetchColumn() > 0) {
                throw new Exception("Opération bloquée : Cet étudiant est déjà inscrit pour l'année {$nextAcademicYear}.");
            }

            // 2. Récupération des informations complètes de l'étudiant
            $stmt_info = $this->conn->prepare(
                "SELECT e.field_id, e.cycle_id as student_cycle_id, na.decision_annee, na.fail_count, se.cycle_id as enrollment_cycle_id, c.nom as cycle_nom, se.etape_id AS current_etape_id,
                (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :cy AND decision <> 'V') as modules_en_dette
                FROM etudiants e JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :cy LEFT JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = na.annee_id LEFT JOIN cycles c ON se.cycle_id = c.cycle_id
                WHERE e.user_id = :sid ORDER BY se.enrollment_id DESC LIMIT 1"
            );
            $stmt_info->execute([':cy' => $currentAcademicYear, ':sid' => $studentId]);
            $studentInfo = $stmt_info->fetch(PDO::FETCH_ASSOC);
            if (!$studentInfo) {
                throw new Exception("Données de base de l'étudiant (notes, etc.) manquantes pour l'année en cours.");
            }

            // 3. Logique de décision pour déterminer la prochaine étape
            $hasPassedYear = $studentInfo['decision_annee'] === 'V';
            $hasDebt = (int)$studentInfo['modules_en_dette'] > 0;
            $cycleId = $studentInfo['enrollment_cycle_id'] ?? $studentInfo['student_cycle_id'];
            $currentEtape = (int)($studentInfo['current_etape_id'] ?? 1);
            $fieldId = (int)$studentInfo['field_id'];
            $cycleName = strtoupper(trim($studentInfo['cycle_nom'] ?: ''));

            $nextEtapeId = $currentEtape;
            $nextCycleId = $cycleId;
            $nextFieldId = $fieldId;
            $isRedoublement = false;
            $redoublementType = 'complet';

            if (!$hasPassedYear) {
                $isRedoublement = true;
            } else {
                // Logique spécifique aux cycles si l'année est validée
                if ($cycleName === 'DEUG') {
                    if ($currentEtape === 1) { $nextEtapeId = 2; } 
                    elseif ($currentEtape === 2) {
                        if (!$hasDebt) {
                            if ($newFieldId === null) throw new Exception("Le choix d'une nouvelle filière de Licence est obligatoire.");
                            $nextCycleId = 2; $nextEtapeId = 1; $nextFieldId = $newFieldId;
                        } else { $isRedoublement = true; $redoublementType = 'partiel'; }
                    }
                } elseif ($cycleName === 'LICENSE') {
                    if (!$hasDebt) {
                        if ($newFieldId === null) throw new Exception("Le choix d'une nouvelle filière de Master est obligatoire.");
                        $nextCycleId = 3; $nextEtapeId = 1; $nextFieldId = $newFieldId;
                    } else { $isRedoublement = true; $redoublementType = 'partiel'; }
                } elseif ($cycleName === 'MASTER') {
                    if ($currentEtape === 1) { $nextEtapeId = 2; } 
                    elseif ($currentEtape === 2) {
                        if (!$hasDebt) {
                            $this->conn->commit();
                            return ['success' => true, 'message' => "Félicitations ! L'étudiant est prêt pour la diplomation."];
                        } else { $isRedoublement = true; $redoublementType = 'partiel'; }
                    }
                }
            }
            
            if ($isRedoublement) {
                if ((int)$studentInfo['fail_count'] >= 2) {
                    throw new Exception("Réinscription bloquée : nombre maximum de redoublements atteint.");
                }
                $nextEtapeId = $currentEtape; // On reste à la même étape
            }

            // 4. Inscription administrative
            $stmt_semestre = $this->conn->prepare("SELECT semestre_id FROM semestres WHERE etape_id = ? AND field_id = ? ORDER BY semestre_id ASC LIMIT 1");
            $stmt_semestre->execute([$nextEtapeId, $nextFieldId]);
            $reEnrollSemesterId = $stmt_semestre->fetchColumn();
            if (!$reEnrollSemesterId) {
                throw new Exception("Configuration BD incomplète : semestre non trouvé pour l'étape {$nextEtapeId} et la filière {$nextFieldId}.");
            }
            $stmt_insert_enrollment = $this->conn->prepare("INSERT INTO student_enrollments (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt_insert_enrollment->execute([$studentId, $nextAcademicYear, $reEnrollSemesterId, $nextCycleId, $nextFieldId, $nextEtapeId]);
            
            // 5. Mise à jour du statut de l'étudiant
            $newFailCount = $isRedoublement ? (int)$studentInfo['fail_count'] + 1 : 0;
            $stmt_upsert_note_annee = $this->conn->prepare("INSERT INTO note_annees (student_id, annee_id, fail_count) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE fail_count = VALUES(fail_count)");
            $stmt_upsert_note_annee->execute([$studentId, $nextAcademicYear, $newFailCount]);
            if ($nextCycleId !== $cycleId || $nextFieldId !== $fieldId) {
                $stmt_update_student = $this->conn->prepare("UPDATE etudiants SET cycle_id = ?, field_id = ? WHERE user_id = ?");
                $stmt_update_student->execute([$nextCycleId, $nextFieldId, $studentId]);
            }

            // 6. Création des enregistrements de notes pour la nouvelle année
            $modulesForNewStep = [];
            $modulesToRetake = [];

            if ($isRedoublement && $redoublementType === 'complet') {
                $modulesForNewStep = $this->getModulesForStep($nextFieldId, $nextEtapeId);
            } else {
                $modulesForNewStep = $this->getModulesForStep($nextFieldId, $nextEtapeId);
                if ($hasDebt) {
                    $stmt_failed_modules = $this->conn->prepare("SELECT module_id, semestre_id FROM note_modules WHERE student_id = ? AND annee_id = ? AND decision <> 'V'");
                    $stmt_failed_modules->execute([$studentId, $currentAcademicYear]);
                    $modulesToRetake = $stmt_failed_modules->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            $this->createInitialGradeRecords($studentId, $nextAcademicYear, $modulesForNewStep, $modulesToRetake);

            $this->conn->commit();
            return ['success' => true, 'message' => "Inscription réussie pour l'année {$nextAcademicYear}."];

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
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

    // On récupère juste les informations essentielles pour un passage simple
    $stmt_info = $this->conn->prepare(
        "SELECT field_id, cycle_id, etape_id FROM student_enrollments 
         WHERE student_id = :sid AND annee_id = :ay 
         ORDER BY enrollment_id DESC LIMIT 1"
    );
    $stmt_info->execute([':sid' => $studentId, ':ay' => $currentAcademicYear]);
    $enrollmentInfo = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$enrollmentInfo) {
        // On saute cet étudiant s'il a un problème de données, mais on ne fait pas planter toute l'opération
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

    // On insère la nouvelle inscription
    $stmt_insert = $this->conn->prepare("INSERT INTO student_enrollments (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt_insert->execute([$studentId, $nextAcademicYear, $nextSemesterId, $enrollmentInfo['cycle_id'], $enrollmentInfo['field_id'], $nextEtapeId]);

    // On crée une note d'année vierge pour la nouvelle année
    $stmt_note = $this->conn->prepare("INSERT INTO note_annees (student_id, annee_id, fail_count) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE fail_count = 0");
    $stmt_note->execute([$studentId, $nextAcademicYear]);
}

    public function enrollAllPassingStudents(): array {
    $this->conn->beginTransaction();
    try {
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
        return ['success' => true, 'message' => "{$count} étudiant(s) ont été inscrits avec succès à l'étape suivante."];

    } catch (Exception $e) {
        $this->conn->rollBack();
        throw $e;
    }
}

    //** Crée les enregistrements de notes initiaux pour un étudiant dans une nouvelle année.

    private function createInitialGradeRecords(int $studentId, string $academicYear, array $modulesForNewStep, array $modulesToRetake = []): void {
        $stmt_insert_note_semestre = $this->conn->prepare("INSERT IGNORE INTO note_semestres (student_id, semestre_id, annee_id, note_semestre) VALUES (?, ?, ?, 0.00)");
        $stmt_insert_note_module = $this->conn->prepare("INSERT IGNORE INTO note_modules (student_id, module_id, semestre_id, annee_id, note_module) VALUES (?, ?, ?, ?, 0.00)");
        $stmt_insert_note_element = $this->conn->prepare("INSERT IGNORE INTO notes (student_id, element_id, semestre_id, annee_id) VALUES (?, ?, ?, ?)");

        // 1. Créer les enregistrements pour les modules de la nouvelle étape
        foreach ($modulesForNewStep as $module) {
            $stmt_insert_note_semestre->execute([$studentId, $module['semestre_id'], $academicYear]);
            $stmt_insert_note_module->execute([$studentId, $module['module_id'], $module['semestre_id'], $academicYear]);

            $stmt_elements = $this->conn->prepare("SELECT element_id FROM elements WHERE module_id = ?");
            $stmt_elements->execute([$module['module_id']]);
            $elements = $stmt_elements->fetchAll(PDO::FETCH_COLUMN);
            foreach ($elements as $elementId) {
                $stmt_insert_note_element->execute([$studentId, $elementId, $module['semestre_id'], $academicYear]);
            }
        }

        // 2. Reporter les modules en dette
        if (!empty($modulesToRetake)) {
            $stmt_report_module = $this->conn->prepare("INSERT INTO note_modules (student_id, module_id, semestre_id, annee_id, retake_status) VALUES (?, ?, ?, ?, 'pending') ON DUPLICATE KEY UPDATE retake_status = 'pending'");
            foreach ($modulesToRetake as $module) {
                $stmt_report_module->execute([$studentId, $module['module_id'], $module['semestre_id'], $academicYear]);
            }
        }
    }

    /**
     * Récupère tous les modules pour une étape et une filière données.
     */
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
}