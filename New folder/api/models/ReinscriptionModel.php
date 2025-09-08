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
              AND (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :ay AND retake_status = 'scheduled') = 0
              AND NOT EXISTS (SELECT 1 FROM student_enrollments se2 WHERE se2.student_id = e.user_id AND se2.annee_id = :next_ay)
              AND (se.etape_id * 2) < c.Nombre_semestre
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

    /**
     * Fonction principale de réinscription qui gère tous les cas.
     */
    public function reenrollStudent(int $studentId, ?int $newFieldId = null): array {
    $this->conn->beginTransaction();
    try {
        $currentAcademicYear = $this->getCurrentAcademicYear();
        if (!$currentAcademicYear) throw new Exception("Année académique actuelle non définie.");
        $nextAcademicYear = $this->getNextAcademicYear($currentAcademicYear);

            // 1. Vérification : L'étudiant n'est-il pas déjà inscrit ?
           $stmt_check = $this->conn->prepare("SELECT COUNT(*) FROM student_enrollments WHERE student_id = ? AND annee_id = ?");
            $stmt_check->execute([$studentId, $nextAcademicYear]);
            if ($stmt_check->fetchColumn() > 0) {
                throw new Exception("Opération bloquée : Cet étudiant est déjà inscrit pour l'année {$nextAcademicYear}.");
            }

            // 2. Récupération des informations complètes de l'étudiant pour l'année en cours
            $stmt_info = $this->conn->prepare(
                "SELECT e.field_id, e.cycle_id as student_cycle_id, na.decision_annee, na.fail_count, se.cycle_id as enrollment_cycle_id, c.nom as cycle_nom, se.etape_id AS current_etape_id,
                (SELECT COUNT(*) FROM note_modules WHERE student_id = e.user_id AND annee_id = :cy AND retake_status = 'scheduled') as modules_en_dette
                FROM etudiants e JOIN note_annees na ON e.user_id = na.student_id AND na.annee_id = :cy LEFT JOIN student_enrollments se ON e.user_id = se.student_id AND se.annee_id = na.annee_id LEFT JOIN cycles c ON se.cycle_id = c.cycle_id
                WHERE e.user_id = :sid ORDER BY se.enrollment_id DESC LIMIT 1"
            );
            $stmt_info->execute([':cy' => $currentAcademicYear, ':sid' => $studentId]);
            $studentInfo = $stmt_info->fetch(PDO::FETCH_ASSOC);
            if (!$studentInfo) {
                throw new Exception("Données de base de l'étudiant (notes, etc.) manquantes pour l'année en cours.");
            }


            // 3. Initialisation des variables pour la logique de décision
             $hasPassedYear = in_array($studentInfo['decision_annee'], ['V', 'VPC']);
            $hasDebt = (int)$studentInfo['modules_en_dette'] > 0;
            $cycleId = $studentInfo['enrollment_cycle_id'] ?? $studentInfo['student_cycle_id'];
            $currentEtape = (int)($studentInfo['current_etape_id'] ?? 1);
            $fieldId = (int)$studentInfo['field_id']; // La filière ACTUELLE de l'étudiant
            $cycleName = strtoupper(trim($studentInfo['cycle_nom'] ?: ''));

            $nextEtapeId = $currentEtape;
            $nextCycleId = $cycleId;
            $nextFieldId = $fieldId; // Par défaut, on reste dans la même filière
            $isRedoublement = false;
            $message = "Inscription réussie pour l'année {$nextAcademicYear}.";


            // ====================================================================
            // DÉBUT DE LA LOGIQUE DE DÉCISION SPÉCIFIQUE PAR CYCLE (CORRIGÉE)
            // ====================================================================
            if (!$hasPassedYear) {
                // Cas simple : l'année n'est pas validée, c'est un redoublement complet.
                $isRedoublement = true;
            } else {
                // L'année est validée (V ou VPC), on analyse la situation.
                switch ($cycleName) {
                    case 'DEUG':
                        if ($currentEtape === 1) {
                            $nextEtapeId = 2; // Passage simple en 2ème année de DEUG
                        } elseif ($currentEtape === 2) {
                            if (!$hasDebt) {
                                // Fin du DEUG sans dette -> Passage en Licence
                                if ($newFieldId === null) throw new Exception("Le choix d'une filière de Licence est obligatoire.");
                                $nextCycleId = 2; // ID du cycle Licence
                                $nextEtapeId = 1; // 1ère étape de Licence
                                $nextFieldId = $newFieldId;
                            } else {
                                // Fin du DEUG avec dette -> Redoublement
                                $isRedoublement = true;
                            }
                        }
                        break;

                    case 'LICENSE':
                        // La licence n'a qu'une seule étape dans ce modèle
                        if (!$hasDebt) {
                            // Fin de la Licence sans dette -> Passage en Master
                            if ($newFieldId === null) throw new Exception("Le choix d'une filière de Master est obligatoire.");
                            $nextCycleId = 3; // ID du cycle Master
                            $nextEtapeId = 1; // 1ère étape de Master
                            $nextFieldId = $newFieldId;
                        } else {
                            // Fin de la Licence avec dette -> Redoublement
                            $isRedoublement = true;
                        }
                        break;

                    case 'MASTER':
                        if ($currentEtape === 1) {
                            $nextEtapeId = 2; // Passage simple en 2ème année de Master
                        } elseif ($currentEtape === 2) {
                            if (!$hasDebt) {
                                // Fin du Master sans dette -> Diplomation
                                $this->conn->commit(); // On valide la transaction avant de sortir
                                return ['success' => true, 'message' => "Félicitations ! L'étudiant a terminé son cursus et est prêt pour la diplomation."];
                            } else {
                                // Fin du Master avec dette -> Redoublement
                                $isRedoublement = true;
                            }
                        }
                        break;
                    
                    default:
                        // Logique générique pour d'autres cycles (ex: Doctorat)
                        if ($this->isLastStepOfCycle($studentId, $currentAcademicYear) && !$hasDebt) {
                            throw new Exception("Fin de cycle non gérée pour '{$cycleName}'. L'étudiant est diplômé ?");
                        } else if ($hasDebt) {
                            $isRedoublement = true;
                        } else {
                            $nextEtapeId = $currentEtape + 1;
                        }
                        break;
                }
            }
            
            if ($isRedoublement) {
                if ((int)$studentInfo['fail_count'] >= 2) { // Seuil de redoublement
                    throw new Exception("Réinscription bloquée : nombre maximum de redoublements atteint.");
                }
                $nextEtapeId = $currentEtape; // On reste à la même étape
            }
            // ====================================================================
            // FIN DE LA LOGIQUE DE DÉCISION
            // ====================================================================

            // 4. Inscription administrative
            $stmt_semestres = $this->conn->prepare("SELECT semestre_id FROM semestres WHERE etape_id = ? AND field_id = ?");
            $stmt_semestres->execute([$nextEtapeId, $nextFieldId]);
            $semestersToEnroll = $stmt_semestres->fetchAll(PDO::FETCH_COLUMN);

            // On vérifie si la liste est vide
            if (empty($semestersToEnroll)) {
                throw new Exception("Configuration BD incomplète : Aucun semestre n'est défini pour l'étape '{$nextEtapeId}' dans la filière '{$nextFieldId}'.");
            }

            // On prépare la requête d'insertion une seule fois
            $stmt_insert_enrollment = $this->conn->prepare("INSERT INTO student_enrollments (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, status) VALUES (?, ?, ?, ?, ?, ?, 'inscrit')");
            
            // On boucle sur chaque semestre trouvé et on insère une ligne
            foreach ($semestersToEnroll as $semesterId) {
                $stmt_insert_enrollment->execute([$studentId, $nextAcademicYear, $semesterId, $nextCycleId, $nextFieldId, $nextEtapeId]);
            }
            
             // 5. Mise à jour du statut de l'étudiant et de sa note d'année pour l'année suivante
            $newFailCount = $isRedoublement ? (int)$studentInfo['fail_count'] + 1 : 0;
            $stmt_upsert_note_annee = $this->conn->prepare("INSERT INTO note_annees (student_id, annee_id, fail_count) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE fail_count = VALUES(fail_count)");
            $stmt_upsert_note_annee->execute([$studentId, $nextAcademicYear, $newFailCount]);
            if ($nextCycleId !== $cycleId || $nextFieldId !== $fieldId) {
                $stmt_update_student = $this->conn->prepare("UPDATE etudiants SET cycle_id = ?, field_id = ? WHERE user_id = ?");
                $stmt_update_student->execute([$nextCycleId, $nextFieldId, $studentId]);
            }

             // 6. Récupérer les modules pour la nouvelle situation de l'étudiant.
            $modulesForNewStep = $this->getModulesForStep($nextFieldId, $nextEtapeId);
            // **VÉRIFICATION CRUCIALE** : On vérifie si on a bien trouvé des modules.
            if (empty($modulesForNewStep)) {
                // Si aucun module n'est trouvé, c'est une erreur de configuration qui doit bloquer l'inscription.
                // On annule toute la transaction pour ne pas laisser une inscription administrative orpheline.
                throw new Exception(
                    "Erreur critique de configuration : Aucun module n'a été trouvé pour la filière ID '{$nextFieldId}' et l'étape ID '{$nextEtapeId}'. L'inscription a été annulée pour garantir la cohérence des données."
                );
            }
            
            // 7. Si des modules sont trouvés, on crée les enregistrements de notes.
            $this->createInitialGradeRecords($studentId, $nextAcademicYear, $modulesForNewStep);
            
            // 8. Report des modules en dette (si l'année a été validée par compensation)
            if ($hasPassedYear && $hasDebt) {
             $stmt_failed_modules = $this->conn->prepare(
                "SELECT module_id, semestre_id FROM note_modules 
                WHERE student_id = ? AND annee_id = ? AND retake_status = 'scheduled'"
            );
             $stmt_failed_modules->execute([$studentId, $currentAcademicYear]);
             $modulesToRetake = $stmt_failed_modules->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($modulesToRetake)) {
                // Le reste du code est déjà correct et n'a pas besoin de changer.
                $stmt_report_module = $this->conn->prepare(
                    "INSERT INTO note_modules (student_id, module_id, semestre_id, annee_id, note_module, retake_status) 
                    VALUES (?, ?, ?, ?, 0.00, 'pending') 
                    ON DUPLICATE KEY UPDATE retake_status = 'pending'"
                );
                
                $stmt_get_elements = $this->conn->prepare("SELECT element_id FROM elements WHERE module_id = ?");
                $stmt_insert_note_element = $this->conn->prepare("INSERT IGNORE INTO notes (student_id, element_id, semestre_id, annee_id) VALUES (?, ?, ?, ?)");

                foreach ($modulesToRetake as $module) {
                    // On crée l'enregistrement dans note_modules pour le module à repasser
                    $stmt_report_module->execute([$studentId, $module['module_id'], $module['semestre_id'], $nextAcademicYear]);

                    // On crée aussi les enregistrements dans la table `notes` pour chaque élément de ce module en dette.
                    $stmt_get_elements->execute([$module['module_id']]);
                    $elements = $stmt_get_elements->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($elements as $elementId) {
                        $stmt_insert_note_element->execute([$studentId, $elementId, $module['semestre_id'], $nextAcademicYear]);
                    }
                }
            }
        }

            $this->conn->commit();
            return ['success' => true, 'message' => $message];

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

   /**
     * Crée les enregistrements de notes vierges pour les nouveaux modules et les modules en dette.
     * MODIFIÉ : La signature de la fonction accepte maintenant un tableau de modules en dette.
     */
    private function createInitialGradeRecords(int $studentId, string $academicYear, array $modulesForNewStep, array $modulesToRetake = []): void {
        $allModules = array_merge($modulesForNewStep, $modulesToRetake);
        if (empty($allModules)) {
             // Ce n'est plus une erreur critique si on reporte des notes, on peut juste logger un avertissement.
            error_log("Avertissement: Aucun nouveau module ou module en dette à inscrire pour l'étudiant {$studentId} pour l'année {$academicYear}. Seuls les modules validés seront reportés.");
            return;
        }

        $stmt_insert_note_semestre = $this->conn->prepare("INSERT IGNORE INTO note_semestres (student_id, semestre_id, annee_id) VALUES (?, ?, ?)");
        $stmt_insert_note_module = $this->conn->prepare("INSERT IGNORE INTO note_modules (student_id, module_id, semestre_id, annee_id) VALUES (?, ?, ?, ?)");
        $stmt_insert_note_element = $this->conn->prepare("INSERT IGNORE INTO notes (student_id, element_id, semestre_id, annee_id) VALUES (?, ?, ?, ?)");
        $stmt_get_elements = $this->conn->prepare("SELECT element_id FROM elements WHERE module_id = ?");

        $processedSemesters = [];
        foreach ($allModules as $module) {
            $moduleId = $module['module_id'];
            $semestreId = $module['semestre_id'];

            if (!in_array($semestreId, $processedSemesters)) {
                $stmt_insert_note_semestre->execute([$studentId, $semestreId, $academicYear]);
                $processedSemesters[] = $semestreId;
            }
            $stmt_insert_note_module->execute([$studentId, $moduleId, $semestreId, $academicYear]);
            $stmt_get_elements->execute([$moduleId]);
            $elements = $stmt_get_elements->fetchAll(PDO::FETCH_COLUMN);
            foreach ($elements as $elementId) {
                $stmt_insert_note_element->execute([$studentId, $elementId, $semestreId, $academicYear]);
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

    public function enrollPassingStudentsInBulk(array $studentIds): array {
    $this->conn->beginTransaction();
    try {
        $currentAcademicYear = $this->getCurrentAcademicYear();
        $nextAcademicYear = $this->getNextAcademicYear($currentAcademicYear);
        
        $successCount = 0;
        $errors = [];

        // Préparez les requêtes une seule fois
        $stmt_info = $this->conn->prepare(
            "SELECT field_id, cycle_id, etape_id FROM student_enrollments 
             WHERE student_id = :sid AND annee_id = :ay ORDER BY enrollment_id DESC LIMIT 1"
        );
        $stmt_semestre = $this->conn->prepare("SELECT semestre_id FROM semestres WHERE etape_id = ? AND field_id = ? ORDER BY semestre_id ASC LIMIT 1");
        $stmt_insert = $this->conn->prepare("INSERT INTO student_enrollments (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, status) VALUES (?, ?, ?, ?, ?, ?, 'inscrit')");
        $stmt_note = $this->conn->prepare("INSERT INTO note_annees (student_id, annee_id, fail_count) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE fail_count = 0");

        foreach ($studentIds as $studentId) {
            try {
                // 1. Vérifier si l'étudiant est déjà inscrit
                $stmt_check = $this->conn->prepare("SELECT COUNT(*) FROM student_enrollments WHERE student_id = ? AND annee_id = ?");
                $stmt_check->execute([$studentId, $nextAcademicYear]);
                if ($stmt_check->fetchColumn() > 0) {
                    throw new Exception("Déjà inscrit pour l'année suivante.");
                }

                // 2. Récupérer les informations d'inscription actuelles
                $stmt_info->execute([':sid' => $studentId, ':ay' => $currentAcademicYear]);
                $enrollmentInfo = $stmt_info->fetch(PDO::FETCH_ASSOC);

                if (!$enrollmentInfo) {
                    throw new Exception("Aucune inscription trouvée pour l'année en cours.");
                }
                
                // 3. Vérifier si c'est la dernière étape du cycle
                if ($this->isLastStepOfCycle($studentId, $currentAcademicYear)) {
                    throw new Exception("Changement de cycle requis, ne peut être traité en masse.");
                }

                // 4. Calculer la prochaine étape
                $nextEtapeId = (int)$enrollmentInfo['etape_id'] + 1;

                // 5. Trouver le semestre correspondant
                $stmt_semestre->execute([$nextEtapeId, $enrollmentInfo['field_id']]);
                $nextSemesterId = $stmt_semestre->fetchColumn();

                if (!$nextSemesterId) {
                    throw new Exception("Configuration BD incomplète: semestre non trouvé pour l'étape {$nextEtapeId}.");
                }

                // 6. Inscrire l'étudiant
                $stmt_insert->execute([$studentId, $nextAcademicYear, $nextSemesterId, $enrollmentInfo['cycle_id'], $enrollmentInfo['field_id'], $nextEtapeId]);
                $stmt_note->execute([$studentId, $nextAcademicYear]);
                
                $successCount++;

            } catch (Exception $e) {
                $errors[] = "Étudiant ID {$studentId}: " . $e->getMessage();
            }
        }

        $this->conn->commit();
        return ['success_count' => $successCount, 'errors' => $errors];

    } catch (Exception $e) {
        $this->conn->rollBack();
        // Relancer l'exception pour qu'elle soit capturée par le contrôleur
        throw new Exception("Transaction échouée: " . $e->getMessage());
    }
}

public function processStudentProgression(int $studentId, ?int $newFieldId = null): array {
        $this->conn->beginTransaction();
        try {
            $currentAcademicYear = $this->getCurrentAcademicYear();
            if (!$currentAcademicYear) throw new Exception("Année académique actuelle non définie.");

            $studentInfo = $this->getStudentState($studentId, $currentAcademicYear);
            if (!$studentInfo) {
                throw new Exception("Données de base de l'étudiant manquantes pour l'année en cours.");
            }

            $hasPassedYear = in_array($studentInfo['decision_annee'], ['V', 'VPC']);
            $hasDebt = $studentInfo['modules_en_dette'] > 0;
            $isLastStep = $this->isLastStepOfCycle($studentId, $currentAcademicYear);

            $message = "";

            if (!$hasPassedYear) {
                $message = $this->enrollRedoublement($studentId, $studentInfo);
            } elseif ($isLastStep) {
                if ($hasDebt) {
                    $message = $this->enrollRedoublement($studentId, $studentInfo);
                } else {
                    if ($newFieldId === null && $studentInfo['cycle_id'] < 4) { // Ne pas demander pour le doctorat
                        throw new Exception("Le choix d'une nouvelle filière est obligatoire pour passer au cycle suivant.");
                    }
                    $message = $this->enrollNextCycle($studentId, $studentInfo, $newFieldId);
                }
            } else {
                $message = $this->enrollSimplePassage($studentId, $studentInfo);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => $message];

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * CAS 1 & 2 : REDOUBLEMENT
     * MODIFIÉ : Appelle copyValidatedResults pour reporter les notes déjà acquises.
     */
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
        $this->createInitialGradeRecords($studentId, $nextAcademicYear, $modules);
        
        // **AJOUT CLÉ** : Copie les résultats déjà validés de l'année précédente.
        $this->copyValidatedResults($studentId, $currentAcademicYear, $nextAcademicYear);

        $newFailCount = $studentInfo['fail_count'] + 1;
        $this->createOrUpdateNoteAnnee($studentId, $nextAcademicYear, $newFailCount);

        return "Étudiant inscrit en redoublement pour l'année {$nextAcademicYear}. Les modules déjà validés ont été reportés.";
    }

    /**
     * CAS 3 : PASSAGE AU CYCLE SUIVANT
     */
    private function enrollNextCycle(int $studentId, array $studentInfo, ?int $newFieldId): string {
        $nextAcademicYear = $this->getNextAcademicYear($studentInfo['annee_id']);
        $nextCycleId = $studentInfo['cycle_id'] + 1;
        $nextEtapeId = 1;

        if ($nextCycleId > 4) { // Supposons que 4 est le dernier cycle (Doctorat)
             return "Félicitations ! L'étudiant a terminé son cursus et est prêt pour la diplomation.";
        }

        $this->createEnrollmentRecords($studentId, $nextAcademicYear, $nextEtapeId, $newFieldId, $nextCycleId);
        $modules = $this->getModulesForStep($newFieldId, $nextEtapeId);
        $this->createInitialGradeRecords($studentId, $nextAcademicYear, $modules);
        $this->createOrUpdateNoteAnnee($studentId, $nextAcademicYear);

        $stmt = $this->conn->prepare("UPDATE etudiants SET cycle_id = ?, field_id = ? WHERE user_id = ?");
        $stmt->execute([$nextCycleId, $newFieldId, $studentId]);

        return "Étudiant inscrit avec succès dans le nouveau cycle pour l'année {$nextAcademicYear}.";
    }

   /**
     * CAS 4 : PASSAGE SIMPLE (AVEC OU SANS DETTE)
     * MODIFIÉ : Appelle copyValidatedResults pour reporter les notes déjà acquises.
     */
    private function enrollSimplePassage(int $studentId, array $studentInfo): string {
        $currentAcademicYear = $studentInfo['annee_id'];
        $nextAcademicYear = $this->getNextAcademicYear($currentAcademicYear);
        $nextEtapeId = $studentInfo['current_etape_id'] + 1;
        $fieldId = $studentInfo['field_id'];
        $cycleId = $studentInfo['cycle_id'];

        $this->createEnrollmentRecords($studentId, $nextAcademicYear, $nextEtapeId, $fieldId, $cycleId);

        $modulesNewStep = $this->getModulesForStep($fieldId, $nextEtapeId);
        
        $modulesInDebt = [];
        if ($studentInfo['modules_en_dette'] > 0) {
            $stmt = $this->conn->prepare("SELECT module_id, semestre_id FROM note_modules WHERE student_id = ? AND annee_id = ? AND retake_status = 'scheduled'");
            $stmt->execute([$studentId, $currentAcademicYear]);
            $modulesInDebt = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->createInitialGradeRecords($studentId, $nextAcademicYear, $modulesNewStep, $modulesInDebt);
        
        // **AJOUT CLÉ** : Copie les résultats déjà validés de l'année précédente.
        $this->copyValidatedResults($studentId, $currentAcademicYear, $nextAcademicYear);
        
        $this->createOrUpdateNoteAnnee($studentId, $nextAcademicYear);

        $message = "Étudiant inscrit à l'étape suivante pour l'année {$nextAcademicYear}.";
        if (!empty($modulesInDebt)) {
            $message .= " Les modules en dette et les résultats validés ont été reportés.";
        }
        return $message;
    }

    /**
     * Copie les résultats validés (modules et éléments) de l'année précédente vers la nouvelle.
     */
    private function copyValidatedResults(int $studentId, string $fromYear, string $toYear): void {
        // 1. Copier les notes des ÉLÉMENTS validés
        $sql_elements = "
            INSERT INTO notes (student_id, element_id, semestre_id, annee_id, note_tp, note_td, note_cc, note_exam, note_rattrapage, note_finale, decision, decision_ratt)
            SELECT 
                student_id, element_id, semestre_id, :to_year, note_tp, note_td, note_cc, note_exam, note_rattrapage, note_finale, decision, decision_ratt
            FROM notes
            WHERE student_id = :student_id AND annee_id = :from_year AND (decision = 'V' OR decision_ratt = 'VR')
            ON DUPLICATE KEY UPDATE
                note_finale = VALUES(note_finale), decision = VALUES(decision), decision_ratt = VALUES(decision_ratt),
                note_tp = VALUES(note_tp), note_td = VALUES(note_td), note_cc = VALUES(note_cc), note_exam = VALUES(note_exam), note_rattrapage = VALUES(note_rattrapage);
        ";
        $stmt_elements = $this->conn->prepare($sql_elements);
        $stmt_elements->execute([':student_id' => $studentId, ':from_year' => $fromYear, ':to_year' => $toYear]);

        // 2. Copier les notes des MODULES validés
        $sql_modules = "
            INSERT INTO note_modules (student_id, module_id, semestre_id, annee_id, note_module, decision, note_ratt, decision_ratt, retake_status)
            SELECT 
                student_id, module_id, semestre_id, :to_year, note_module, decision, note_ratt, decision_ratt, 'completed'
            FROM note_modules
            WHERE student_id = :student_id AND annee_id = :from_year AND (decision IN ('V', 'VPC') OR decision_ratt = 'VR') AND retake_status IS NULL
            ON DUPLICATE KEY UPDATE
                note_module = VALUES(note_module), decision = VALUES(decision), decision_ratt = VALUES(decision_ratt), retake_status = VALUES(retake_status);
        ";
        $stmt_modules = $this->conn->prepare($sql_modules);
        $stmt_modules->execute([':student_id' => $studentId, ':from_year' => $fromYear, ':to_year' => $toYear]);
    }

     /**
     * Récupère l'état complet d'un étudiant pour une année donnée.
     */
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

    /**
     * Crée les lignes d'inscription pour tous les semestres d'une étape.
     */
    private function createEnrollmentRecords(int $studentId, string $academicYear, int $etapeId, int $fieldId, int $cycleId): void {
        $stmt_semestres = $this->conn->prepare("SELECT semestre_id FROM semestres WHERE etape_id = ? AND field_id = ?");
        $stmt_semestres->execute([$etapeId, $fieldId]);
        $semestersToEnroll = $stmt_semestres->fetchAll(PDO::FETCH_COLUMN);

        if (empty($semestersToEnroll)) {
            throw new Exception("Configuration BD incomplète : Aucun semestre n'est défini pour l'étape '{$etapeId}' dans la filière '{$fieldId}'.");
        }

        $stmt_insert = $this->conn->prepare("INSERT INTO student_enrollments (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, status) VALUES (?, ?, ?, ?, ?, ?, 'inscrit')");
        foreach ($semestersToEnroll as $semesterId) {
            $stmt_insert->execute([$studentId, $academicYear, $semesterId, $cycleId, $fieldId, $etapeId]);
        }
    }

    /**
     * Crée ou met à jour la ligne dans note_annees pour la nouvelle année.
     */
    private function createOrUpdateNoteAnnee(int $studentId, string $academicYear, int $failCount = 0): void {
        $stmt = $this->conn->prepare("INSERT INTO note_annees (student_id, annee_id, fail_count) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE fail_count = VALUES(fail_count)");
        $stmt->execute([$studentId, $academicYear, $failCount]);
    }

}