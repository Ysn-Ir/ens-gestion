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
        // Convertit les semestres en années (ex: 4 semestres = 2 ans)
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
                   (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :cy AND retake_status = 'scheduled') as modules_en_dette,
                   GROUP_CONCAT(DISTINCT CASE WHEN nm.retake_status = 'scheduled' THEN m.nom ELSE NULL END SEPARATOR ', ') AS modules_nv_noms
            FROM etudiants e
            INNER JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :cy
            LEFT JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = :cy
            LEFT JOIN etapes et ON se.etape_id = et.etape_id
            LEFT JOIN note_modules nm ON e.user_id = nm.student_id AND nm.annee_id = :cy
            LEFT JOIN modules m ON nm.module_id = m.module_id
            WHERE e.actuel = 1 AND (na.decision_annee IN ('F', 'NV') OR (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :cy AND retake_status = 'scheduled') > 0)
            GROUP BY e.user_id ORDER BY na.decision_annee DESC, e.nom;
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':cy' => $current_year]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $classified = ['annee_non_valide' => [], 'modules_restants' => []];
        foreach ($students as $student) {
            $isFail = in_array($student['decision_annee'], ['F', 'NV']);
            $hasDebt = (int)$student['modules_en_dette'] > 0;
            $isLastStep = $this->isLastStepOfCycle($student['user_id'], $current_year);

            if ($isFail || ($isLastStep && $hasDebt)) {
                $classified['annee_non_valide'][] = $student;
            } elseif ($hasDebt) {
                $classified['modules_restants'][] = $student;
            }
        }
        return $classified;
    }

    public function getStudentsReadyForNextCycle(): array {
    $current_year = $this->getCurrentAcademicYear();
    if (!$current_year) return [];

    $query = "
        SELECT 
            e.user_id, e.nom, e.prenom, e.cne,
            c.nom as current_cycle_nom,
            se.cycle_id as current_cycle_id -- On prend le cycle de l'inscription
        FROM etudiants e
        -- On s'assure que toutes les jointures sont valides
        INNER JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = :current_year
        INNER JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :current_year
        -- *** CORRECTION ICI : On joint sur le cycle de l'inscription (se.cycle_id) ***
        INNER JOIN cycles c ON se.cycle_id = c.cycle_id 
        WHERE e.actuel = 1
        -- Condition 1: Année validée
        AND na.decision_annee NOT IN ('F', 'NV')
        -- Condition 2: Aucune dette
        AND (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :current_year AND retake_status = 'scheduled') = 0
        -- Condition 3: Dernière étape du cycle atteinte (logique déplacée en PHP pour plus de fiabilité)
        GROUP BY e.user_id
        ORDER BY e.nom;
    ";
    $stmt = $this->conn->prepare($query);
    $stmt->execute([':current_year' => $current_year]);
    $potentialStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // On filtre en PHP pour être certain de la logique de durée de cycle
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
        $allStudents = $this->getAllStudentsWithEnrollment($current_year);
        $passingStudents = [];
        foreach ($allStudents as $student) {
            $cycleDuration = $this->getCycleDurationInYears((int)$student['cycle_id']);
            if (
                !in_array($student['decision_annee'], ['F', 'NV']) &&
                (int)$student['modules_en_dette'] === 0 &&
                (int)$student['etape_id'] < $cycleDuration
            ) {
                $passingStudents[] = [
                    'user_id' => $student['user_id'],
                    'nom' => $student['nom'],
                    'prenom' => $student['prenom'],
                    'cne' => $student['cne'],
                    'etape_actuelle' => $student['etape_nom'],
                    'prochaine_etape' => ((int)$student['etape_id'] + 1) . 'e année'
                ];
            }
        }
        return $passingStudents;
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
                   (SELECT GROUP_CONCAT(m.nom SEPARATOR ', ') FROM note_modules nm JOIN modules m ON nm.module_id = m.module_id WHERE nm.student_id = na.student_id AND nm.annee_id = na.annee_id AND nm.retake_status = 'scheduled') as modules_en_dette
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
 * Gère tous les cas de réinscription : simple, redoublement, changement de cycle.
 * Utilise la logique de durée de cycle corrigée.
 */
/**
     * CORRECTION FINALE : La logique de réinscription est rendue plus robuste
     * pour gérer les étudiants avec des données d'inscription incomplètes.
     */
    public function reenrollStudent(int $studentId, ?int $newFieldId = null): array {
        $this->conn->beginTransaction();
        try {
            $currentAcademicYear = $this->getCurrentAcademicYear();
            $nextAcademicYear = $this->getNextAcademicYear($currentAcademicYear);

            // 1. Vérifier si une inscription existe déjà pour l'année suivante
            $stmt_check = $this->conn->prepare("SELECT COUNT(*) FROM student_enrollments WHERE student_id = ? AND annee_id = ?");
            $stmt_check->execute([$studentId, $nextAcademicYear]);
            if ($stmt_check->fetchColumn() > 0) {
                throw new Exception("Opération bloquée : Cet étudiant est déjà inscrit pour l'année {$nextAcademicYear}.");
            }

            // 2. Récupérer les informations complètes de l'étudiant
            // CORRECTION : Utilisation de LEFT JOIN pour ne pas planter si l'inscription est manquante
            $stmt_info = $this->conn->prepare(
                "SELECT 
                    e.field_id, e.cycle_id as student_cycle_id,
                    na.decision_annee, na.fail_count,
                    se.cycle_id as enrollment_cycle_id, c.nom as cycle_nom,
                    se.etape_id AS current_etape_id,
                    (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :cy AND decision <> 'V') as modules_en_dette
                FROM etudiants e
                JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :cy
                LEFT JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = na.annee_id
                LEFT JOIN cycles c ON se.cycle_id = c.cycle_id
                WHERE e.user_id = :sid ORDER BY se.enrollment_id DESC LIMIT 1"
            );
            $stmt_info->execute([':cy' => $currentAcademicYear, ':sid' => $studentId]);
            $studentInfo = $stmt_info->fetch(PDO::FETCH_ASSOC);

            if (!$studentInfo) {
                throw new Exception("Données de base de l'étudiant (notes, etc.) manquantes pour l'année en cours.");
            }

            // 3. Appliquer les règles de gestion (avec valeurs par défaut pour les redoublants)
            $hasPassedYear = $studentInfo['decision_annee'] === 'V';
            $hasDebt = (int)$studentInfo['modules_en_dette'] > 0;
            
            // CORRECTION : Gérer le cas où l'inscription est incomplète
            $cycleId = $studentInfo['enrollment_cycle_id'] ?? $studentInfo['student_cycle_id'];
            $currentEtape = (int)($studentInfo['current_etape_id'] ?? 1); // On suppose la 1ère année si non défini
            $fieldId = (int)$studentInfo['field_id'];
            
            // On récupère le nom du cycle pour la logique
            $stmt_cycle_nom = $this->conn->prepare("SELECT nom FROM cycles WHERE cycle_id = ?");
            $stmt_cycle_nom->execute([$cycleId]);
            $cycleName = strtoupper(trim($stmt_cycle_nom->fetchColumn() ?: ''));

            $nextEtapeId = $currentEtape;
            $nextCycleId = $cycleId;
            $nextFieldId = $fieldId;
            $isRedoublement = false;
            $redoublementType = 'complet';

            // --- LOGIQUE SPÉCIFIQUE PAR CYCLE ET ANNÉE ---
            if (!$hasPassedYear) {
                $isRedoublement = true; // Si l'année n'est pas validée, c'est toujours un redoublement complet.
            } else {
                // La logique s'applique seulement si l'année est validée
                if ($cycleName === 'DEUG') {
                    if ($currentEtape === 1) { // DEUG 1 -> DEUG 2 (même avec dette)
                        $nextEtapeId = 2;
                    } elseif ($currentEtape === 2) { // DEUG 2
                        if (!$hasDebt) { // -> Licence 1
                            if ($newFieldId === null) throw new Exception("Le choix d'une nouvelle filière de Licence est obligatoire.");
                            $nextCycleId = 2; $nextEtapeId = 1; $nextFieldId = $newFieldId;
                        } else { // -> Redoublement partiel
                            $isRedoublement = true; $redoublementType = 'partiel';
                        }
                    }
                } elseif ($cycleName === 'LICENSE') {
                    if (!$hasDebt) { // -> Master 1
                        if ($newFieldId === null) throw new Exception("Le choix d'une nouvelle filière de Master est obligatoire.");
                        $nextCycleId = 3; $nextEtapeId = 1; $nextFieldId = $newFieldId;
                    } else { // -> Redoublement partiel
                        $isRedoublement = true; $redoublementType = 'partiel';
                    }
                } elseif ($cycleName === 'MASTER') {
                    if ($currentEtape === 1) { // Master 1 -> Master 2 (même avec dette)
                        $nextEtapeId = 2;
                    } elseif ($currentEtape === 2) { // Master 2
                        if (!$hasDebt) { // -> Diplômé
                            //$this->setStudentAsGraduated($studentId, $currentAcademicYear);
                            $this->conn->commit();
                            return ['success' => true, 'message' => "Félicitations ! L'étudiant est prêt pour la diplomation."];
                        } else { // -> Redoublement partiel
                            $isRedoublement = true; $redoublementType = 'partiel';
                        }
                    }
                }
            }
            
            // 4. Gérer le redoublement
            if ($isRedoublement) {
                if ((int)$studentInfo['fail_count'] >= 2) {
                    throw new Exception("Réinscription bloquée : nombre maximum de redoublements atteint.");
                }
                $nextEtapeId = $currentEtape; // On reste à la même étape
            }

            // 5. Inscrire l'étudiant pour la nouvelle année
            $stmt_semestre = $this->conn->prepare("SELECT semestre_id FROM semestres WHERE etape_id = ? AND field_id = ? ORDER BY semestre_id ASC LIMIT 1");
            $stmt_semestre->execute([$nextEtapeId, $nextFieldId]);
            $reEnrollSemesterId = $stmt_semestre->fetchColumn();

            if (!$reEnrollSemesterId) {
                throw new Exception("Configuration BD incomplète : semestre non trouvé pour l'étape {$nextEtapeId} et la filière {$nextFieldId}.");
            }

            $stmt_insert_enrollment = $this->conn->prepare("INSERT INTO student_enrollments (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, status) VALUES (?, ?, ?, ?, ?, ?, 'inscrit')");
            $stmt_insert_enrollment->execute([$studentId, $nextAcademicYear, $reEnrollSemesterId, $nextCycleId, $nextFieldId, $nextEtapeId]);

            // 6. Mettre à jour le compteur de redoublement et le statut de l'étudiant
            $newFailCount = $isRedoublement ? (int)$studentInfo['fail_count'] + 1 : 0;
            $stmt_upsert_note_annee = $this->conn->prepare("INSERT INTO note_annees (student_id, annee_id, fail_count) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE fail_count = VALUES(fail_count)");
            $stmt_upsert_note_annee->execute([$studentId, $nextAcademicYear, $newFailCount]);
            
            if ($nextCycleId !== $cycleId) {
                $stmt_update_student = $this->conn->prepare("UPDATE etudiants SET cycle_id = ?, field_id = ? WHERE user_id = ?");
                $stmt_update_student->execute([$nextCycleId, $nextFieldId, $studentId]);
            }

            // 7. Reporter les modules en dette
            if ($hasDebt && !($isRedoublement && $redoublementType === 'complet')) {
                $stmt_failed_modules = $this->conn->prepare("SELECT module_id, semestre_id FROM note_modules WHERE student_id = ? AND annee_id = ? AND decision <> 'V'");
                $stmt_failed_modules->execute([$studentId, $currentAcademicYear]);
                $modulesToRetake = $stmt_failed_modules->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($modulesToRetake)) {
                    $stmt_insert_note_module = $this->conn->prepare("INSERT INTO note_modules (student_id, module_id, semestre_id, annee_id, retake_status) VALUES (?, ?, ?, ?, 'pending')");
                    foreach ($modulesToRetake as $module) {
                        $stmt_insert_note_module->execute([$studentId, $module['module_id'], $module['semestre_id'], $nextAcademicYear]);
                    }
                }
            }

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
}