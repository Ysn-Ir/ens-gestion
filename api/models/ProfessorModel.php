<?php
// ProfessorModel.php
// Ce fichier gère toutes les interactions avec la base de données
// concernant les professeurs, les éléments, les modules, les filières,
// les départements et les notes, ainsi que les paramètres système.

require_once __DIR__ . '/../utils/Database.php'; // Assurez-vous que le chemin est correct

class ProfessorModel {
    private $db; // Instance de la connexion PDO à la base de données

    public function __construct() {
        // Lors de l'instanciation du modèle, obtenir la connexion à la base de données
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtenir la connexion à la base de données.
     * Utile pour les contrôleurs qui ont besoin d'exécuter des requêtes directes
     * ou pour d'autres modèles qui pourraient en avoir besoin.
     * @return PDO L'objet de connexion à la base de données PDO.
     */
    public function getDbConnection() {
        return $this->db;
    }

    /**
     * Vérifie si la période de saisie des notes est active.
     * Interroge la table `system_settings`.
     * @return bool True si la période est active, False sinon.
     */
    public function isGradePeriodActive() {
        try {
            // Prépare et exécute une requête pour récupérer la valeur du paramètre 'grade_period_active'
            $stmt = $this->db->prepare("SELECT value FROM system_settings WHERE name = 'grade_period_active'");
            $stmt->execute();
            $result = $stmt->fetch(); // Récupère le résultat
            // Retourne true si le paramètre existe et que sa valeur est '1'
            return $result && $result['value'] == '1';
        } catch (PDOException $e) {
            error_log("Database error in isGradePeriodActive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si la période de saisie des notes de rattrapage est active.
     * Interroge la table `system_settings`.
     * @return bool True si la période est active, False sinon.
     */
    public function isResitGradePeriodActive() {
        try {
            $stmt = $this->db->prepare("SELECT value FROM system_settings WHERE name = 'resit_grade_period_active'");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result && $result['value'] == '1';
        } catch (PDOException $e) {
            error_log("Database error in isResitGradePeriodActive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère le statut actuel de la période de saisie des notes.
     * @return array Un tableau associatif avec la clé 'active' (booléen).
     */
    public function getGradePeriodStatus() {
        try {
            return ['active' => $this->isGradePeriodActive()];
        } catch (Exception $e) {
            error_log("Error in getGradePeriodStatus (model): " . $e->getMessage());
            return ['active' => false]; // Retourner une valeur par défaut sûre
        }
    }

    /**
     * Récupère le statut actuel de la période de saisie des notes de rattrapage.
     * @return array Un tableau associatif avec la clé 'active' (booléen).
     */
    public function getResitGradePeriodStatus() {
        try {
            return ['active' => $this->isResitGradePeriodActive()];
        } catch (Exception $e) {
            error_log("Error in getResitGradePeriodStatus (model): " . $e->getMessage());
            return ['active' => false]; // Retourner une valeur par défaut sûre
        }
    }

    /**
     * Récupère les éléments directement enseignés par un professeur spécifique,
     * incluant les IDs des professeurs principaux et de TP pour chaque élément,
     * avec options de filtrage par semestre, étape et filière.
     * @param int $userId L'ID de l'utilisateur (professeur).
     * @param int|null $semestreId Optionnel: ID du semestre pour filtrer.
     * @param int|null $etapeId Optionnel: ID de l'étape pour filtrer.
     * @param int|null $fieldId Optionnel: ID de la filière pour filtrer (pertinent pour les chefs).
     * @return array Les éléments enseignés avec les rôles associés.
     */
    public function getProfessorElements($userId, $semestreId = null, $etapeId = null, $fieldId = null) {
        try {
            $sql = "
                SELECT
                    e.element_id,
                    e.nom,
                    m.code AS module_code,
                    m.nom AS module_name,
                    s.nom AS semestre_nom,
                    aa.annee_id AS annee_academique,
                    f.nom AS filiere_nom,
                    eta.nom_etape AS etape_nom, -- Ajout du nom de l'étape
                    e.Ref_prof_element,
                    e.Ref_prof_tp
                FROM elements e
                JOIN modules m ON e.module_id = m.module_id
                LEFT JOIN semestres s ON m.semestre_id = s.semestre_id
                LEFT JOIN annees_academiques aa ON m.annee_id = aa.annee_id
                LEFT JOIN filieres f ON m.field_id = f.field_id
                LEFT JOIN etapes eta ON s.etape_id = eta.etape_id -- Jointure avec la table etapes
                WHERE (e.Ref_prof_element = :userId OR e.Ref_prof_tp = :userId)
            ";
            $params = [':userId' => $userId];

            if ($semestreId !== null && $semestreId !== '') {
                $sql .= " AND m.semestre_id = :semestreId";
                $params[':semestreId'] = $semestreId;
            }
            if ($etapeId !== null && $etapeId !== '') { // Filtrage par étape
                $sql .= " AND s.etape_id = :etapeId";
                $params[':etapeId'] = $etapeId;
            }
            if ($fieldId !== null && $fieldId !== '') {
                $sql .= " AND m.field_id = :fieldId";
                $params[':fieldId'] = $fieldId;
            }

            $sql .= " ORDER BY m.nom, e.nom";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getProfessorElements: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les rôles d'enseignement d'un professeur pour un élément donné.
     * @param int $userId L'ID de l'utilisateur (professeur).
     * @param int $elementId L'ID de l'élément.
     * @return array Un tableau associatif indiquant si le professeur est le prof principal ou le prof de TP.
     * Ex: ['is_main_prof' => true, 'is_tp_prof' => false]
     */
    public function getProfessorElementRoles($userId, $elementId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    (e.Ref_prof_element = ?) AS is_main_prof,
                    (e.Ref_prof_tp = ?) AS is_tp_prof
                FROM elements e
                WHERE e.element_id = ?
            ");
            $stmt->execute([$userId, $userId, $elementId]);
            $roles = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($roles) {
                // Convertit les valeurs booléennes de la DB (0/1) en vrais booléens PHP
                $roles['is_main_prof'] = (bool)$roles['is_main_prof'];
                $roles['is_tp_prof'] = (bool)$roles['is_tp_prof'];
            } else {
                $roles = ['is_main_prof' => false, 'is_tp_prof' => false];
            }
            return $roles;
        } catch (PDOException $e) {
            error_log("Database error in getProfessorElementRoles: " . $e->getMessage());
            return ['is_main_prof' => false, 'is_tp_prof' => false];
        }
    }

    /**
     * Vérifie si un professeur enseigne un élément spécifique.
     * Interroge la table `professor_element`.
     * @param int $userId L'ID de l'utilisateur (professeur).
     * @param int $elementId L'ID de l'élément.
     * @return bool True si le professeur enseigne l'élément, False sinon.
     */
    public function isTeachingElement($userId, $elementId) {
        // Cette fonction peut être simplifiée ou remplacée par getProfessorElementRoles
        // si l'on considère qu'enseigner signifie être Ref_prof_element ou Ref_prof_tp
        try {
            $stmt = $this->db->prepare("
                SELECT 1 FROM elements
                WHERE element_id = ? AND (Ref_prof_element = ? OR Ref_prof_tp = ?)
            ");
            $stmt->execute([$elementId, $userId, $userId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Database error in isTeachingElement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les notes pour un élément donné, avec options de filtrage par semestre et étape académique.
     * Jointures sur `notes` et `etudiants`.
     * @param int $elementId L'ID de l'élément.
     * @param int|null $semestreId Optionnel: ID du semestre pour filtrer.
     * @param int|null $etapeId Optionnel: ID de l'étape pour filtrer.
     * @return array Les notes des étudiants pour cet élément.
     */
    public function getElementNotes($elementId, $semestreId = null, $etapeId = null) {
        try {
            $sql = "
                SELECT
                    n.note_id,
                    n.student_id,
                    e.nom AS student_nom,
                    e.prenom AS student_prenom,
                    n.note_tp,
                    n.note_td,
                    n.note_cc,
                    n.note_exam,
                    n.note_rattrapage,
                    n.note_finale,
                    sm.nom AS semestre_nom,
                    aa.annee_id AS annee_academique_nom,
                    modu.nom AS module_name,
                    el.nom AS element_name,
                    eta.nom_etape AS etape_nom -- Ajout du nom de l'étape
                FROM notes n
                JOIN etudiants e ON n.student_id = e.user_id
                JOIN elements el ON n.element_id = el.element_id
                JOIN modules modu ON el.module_id = modu.module_id
                LEFT JOIN semestres sm ON modu.semestre_id = sm.semestre_id
                LEFT JOIN annees_academiques aa ON modu.annee_id = aa.annee_id
                LEFT JOIN etapes eta ON sm.etape_id = eta.etape_id -- Jointure avec la table etapes
                WHERE n.element_id = :elementId
            ";
            $params = [':elementId' => $elementId];

            if ($semestreId !== null && $semestreId !== '') {
                $sql .= " AND modu.semestre_id = :semestreId";
                $params[':semestreId'] = $semestreId;
            }
            if ($etapeId !== null && $etapeId !== '') { // Filtrage par étape
                $sql .= " AND sm.etape_id = :etapeId";
                $params[':etapeId'] = $etapeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getElementNotes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si un professeur peut modifier une note spécifique d'un type donné.
     * La logique prend en compte le rôle d'enseignement direct (prof principal/TP)
     * et les rôles de chef (filière/département).
     * @param int $userId L'ID de l'utilisateur.
     * @param int $noteId L'ID de la note.
     * @param string $noteType Le type de note à modifier ('tp', 'td', 'cc', 'exam', 'rattrapage', 'final').
     * @return bool True si l'utilisateur peut modifier la note, False sinon.
     */
    public function canUpdateNote($userId, $noteId, $noteType) {
        try {
            // 1. Obtenir l'element_id associé à la note
            $stmtElementId = $this->db->prepare("SELECT element_id FROM notes WHERE note_id = ?");
            $stmtElementId->execute([$noteId]);
            $elementId = $stmtElementId->fetchColumn();

            if (!$elementId) {
                return false; // Note ou élément non trouvé
            }

            // 2. Obtenir les rôles spécifiques du professeur pour cet élément
            $elementRoles = $this->getProfessorElementRoles($userId, $elementId);
            $isMainProf = $elementRoles['is_main_prof'];
            $isTpProf = $elementRoles['is_tp_prof'];

            // 3. Vérifier les permissions basées sur le rôle direct d'enseignement
            if ($noteType === 'tp') {
                if ($isTpProf) {
                    return true; // Prof de TP peut modifier la note TP
                }
            } elseif ($noteType === 'rattrapage') {
                // Seul le professeur principal ou un chef peut modifier la note de rattrapage
                if ($isMainProf) {
                    return true;
                }
            } else { // 'td', 'cc', 'exam', 'final'
                if ($isMainProf) {
                    return true; // Prof principal peut modifier les autres notes
                }
            }

            // 4. Si pas de rôle direct, vérifier les permissions de chef (chef de filière/département)
            // Un chef de filière ou de département peut modifier toutes les notes des éléments qu'il gère.
            if ($this->canAccessElementNotes($userId, $elementId)) {
                 return true;
            }

            return false; // Privilèges insuffisants pour ce type de note
        } catch (PDOException $e) {
            error_log("Database error in canUpdateNote: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error in canUpdateNote: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour un type de note spécifique (TP, TD, CC, Examen, Rattrapage, Finale).
     * Met à jour la table `notes`.
     * @param int $noteId L'ID de la note à mettre à jour.
     * @param float|null $value La nouvelle valeur de la note (peut être null).
     * @param string $type Le type de note ('tp', 'td', 'cc', 'exam', 'rattrapage', 'final').
     * @return bool True en cas de succès, False sinon.
     */
    public function updateNote($noteId, $value, $type) {
        try {
            $columnMap = [
                'tp' => 'note_tp',
                'td' => 'note_td',
                'cc' => 'note_cc',
                'exam' => 'note_exam',
                'rattrapage' => 'note_rattrapage', // Nouvelle colonne
                'final' => 'note_finale'
            ];

            if (!isset($columnMap[$type])) {
                return false; // Type de note invalide
            }

            $column = $columnMap[$type];
            // Utilise une requête préparée pour mettre à jour la colonne spécifiée
            $stmt = $this->db->prepare("UPDATE notes SET {$column} = ? WHERE note_id = ?");
            return $stmt->execute([$value, $noteId]);
        } catch (PDOException $e) {
            error_log("Database error in updateNote: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un utilisateur est chef de filière pour une filière donnée.
     * Interroge la table `filieres`.
     * @param int $userId L'ID de l'utilisateur.
     * @param int $fieldId L'ID de la filière.
     * @return bool True si l'utilisateur est chef de filière, False sinon.
     */
    public function isFieldHead($userId, $fieldId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 1 FROM filieres
                WHERE field_id = ? AND head_professor_id = ?
            ");
            $stmt->execute([$fieldId, $userId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Database error in isFieldHead: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un utilisateur est chef de département pour un département donné.
     * Interroge la table `departements`.
     * @param int $userId L'ID de l'utilisateur.
     * @param int $departmentId L'ID du département.
     * @return bool True si l'utilisateur est chef de département, False sinon.
     */
    public function isDepartmentHead($userId, $departmentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 1 FROM departements
                WHERE department_id = ? AND head_professor_id = ?
            ");
            $stmt->execute([$departmentId, $userId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Database error in isDepartmentHead: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les notes pour une filière (pour les chefs de filière/département),
     * avec options de filtrage par semestre et étape académique.
     * Jointures sur `notes`, `etudiants`, `elements`, `modules`.
     * @param int $fieldId L'ID de la filière.
     * @param int|null $semestreId Optionnel: ID du semestre pour filtrer.
     * @param int|null $etapeId Optionnel: ID de l'étape pour filtrer.
     * @return array Les notes des étudiants pour cette filière.
     */
    public function getFieldNotes($fieldId, $semestreId = null, $etapeId = null) {
        try {
            $sql = "
                SELECT
                    n.note_id,
                    et.nom AS student_nom,
                    et.prenom AS student_prenom,
                    el.nom AS element_name,
                    m.nom AS module_name,
                    n.note_tp,
                    n.note_td,
                    n.note_cc,
                    n.note_exam,
                    n.note_rattrapage,
                    n.note_finale,
                    -- CORRECTION : Ajout des jointures et sélection des noms
                    s.nom AS semestre_nom,
                    f.nom AS filiere_nom,
                    eta.nom_etape AS etape_nom
                FROM notes n
                JOIN etudiants et ON n.student_id = et.user_id
                JOIN elements el ON n.element_id = el.element_id
                JOIN modules m ON el.module_id = m.module_id
                JOIN filieres f ON m.field_id = f.field_id
                LEFT JOIN semestres s ON m.semestre_id = s.semestre_id
                LEFT JOIN etapes eta ON s.etape_id = eta.etape_id
                WHERE m.field_id = :fieldId
            ";
            $params = [':fieldId' => $fieldId];

            if ($semestreId !== null && $semestreId !== '') {
                $sql .= " AND m.semestre_id = :semestreId";
                $params[':semestreId'] = $semestreId;
            }
            if ($etapeId !== null && $etapeId !== '') { // Filtrage par étape
                $sql .= " AND s.etape_id = :etapeId";
                $params[':etapeId'] = $etapeId;
            }

            $sql .= " ORDER BY student_nom, student_prenom, module_name, element_name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getFieldNotes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les modules appartenant à une filière spécifique.
     * Interroge la table `modules`.
     * @param int $fieldId L'ID de la filière.
     * @return array Les modules de la filière.
     */
    public function getModulesByFieldId($fieldId) {
        try {
            $stmt = $this->db->prepare("
                SELECT module_id, nom, code
                FROM modules
                WHERE field_id = ?
                ORDER BY nom
            ");
            $stmt->execute([$fieldId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getModulesByFieldId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les notes d'étudiants pour un module donné.
     * Jointures sur `notes`, `etudiants`, `elements`, `modules`.
     * @param int $moduleId L'ID du module.
     * @return array Les notes des étudiants pour ce module.
     */
    public function getModuleNotes($moduleId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    n.note_id,
                    et.nom AS student_nom,
                    et.prenom AS student_prenom,
                    el.nom AS element_name,
                    m.nom AS module_name,
                    n.note_tp,
                    n.note_td,
                    n.note_cc,
                    n.note_exam,
                    n.note_rattrapage,
                    n.note_finale,
                    -- CORRECTION : Ajout des informations contextuelles
                    s.nom AS semestre_nom,
                    f.nom AS filiere_nom,
                    eta.nom_etape AS etape_nom
                FROM notes n
                JOIN etudiants et ON n.student_id = et.user_id
                JOIN elements el ON n.element_id = el.element_id
                JOIN modules m ON el.module_id = m.module_id
                JOIN filieres f ON m.field_id = f.field_id
                LEFT JOIN semestres s ON m.semestre_id = s.semestre_id
                LEFT JOIN etapes eta ON s.etape_id = eta.etape_id
                WHERE m.module_id = ?
                ORDER BY student_nom, student_prenom, element_name
            ");
            $stmt->execute([$moduleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getModuleNotes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les notes d'étudiants pour un département donné,
     * avec options de filtrage par semestre, étape académique et filière.
     * Jointures complexes sur `notes`, `etudiants`, `elements`, `modules`, `filieres`, `departements`.
     * @param int $departmentId L'ID du département.
     * @param int|null $semestreId Optionnel: ID du semestre pour filtrer.
     * @param int|null $etapeId Optionnel: ID de l'étape pour filtrer.
     * @param int|null $fieldId Optionnel: ID de la filière pour filtrer.
     * @return array Les notes des étudiants pour ce département.
     */
    public function getDepartmentNotes($departmentId, $semestreId = null, $etapeId = null, $fieldId = null) {
        try {
            $sql = "
                SELECT
                    n.note_id,
                    et.nom AS student_nom,
                    et.prenom AS student_prenom,
                    el.nom AS element_name,
                    m.nom AS module_name,
                    f.nom AS filiere_name, -- Ajouter le nom de la filière
                    d.nom AS department_name, -- Ajouter le nom du département
                    n.note_tp,
                    n.note_td,
                    n.note_cc,
                    n.note_exam,
                    n.note_rattrapage,
                    n.note_finale,
                    s.nom AS semestre_nom,
                    aa.annee_id AS annee_academique_nom,
                    eta.nom_etape AS etape_nom -- Ajout du nom de l'étape
                FROM notes n
                JOIN etudiants et ON n.student_id = et.user_id
                JOIN elements el ON n.element_id = el.element_id
                JOIN modules m ON el.module_id = m.module_id
                LEFT JOIN semestres s ON m.semestre_id = s.semestre_id
                LEFT JOIN annees_academiques aa ON m.annee_id = aa.annee_id
                JOIN filieres f ON m.field_id = f.field_id
                JOIN departements d ON f.department_id = d.department_id
                LEFT JOIN etapes eta ON s.etape_id = eta.etape_id -- Jointure avec la table etapes
                WHERE d.department_id = :departmentId
            ";
            $params = [':departmentId' => $departmentId];

            if ($semestreId !== null && $semestreId !== '') {
                $sql .= " AND m.semestre_id = :semestreId";
                $params[':semestreId'] = $semestreId;
            }
            if ($etapeId !== null && $etapeId !== '') { // Filtrage par étape
                $sql .= " AND s.etape_id = :etapeId";
                $params[':etapeId'] = $etapeId;
            }
            if ($fieldId !== null && $fieldId !== '') {
                $sql .= " AND m.field_id = :fieldId";
                $params[':fieldId'] = $fieldId;
            }

            $sql .= " ORDER BY student_nom, student_prenom, department_name, filiere_name, module_name, element_name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getDepartmentNotes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les modules appartenant à un département spécifique.
     * Jointures sur `modules` et `filieres`.
     * @param int $departmentId L'ID du département.
     * @return array Les modules du département.
     */
    public function getModulesByDepartmentId($departmentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.module_id, m.nom, m.code, f.nom AS field_name
                FROM modules m
                JOIN filieres f ON m.field_id = f.field_id
                WHERE f.department_id = ?
                ORDER BY f.nom, m.nom
            ");
            $stmt->execute([$departmentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getModulesByDepartmentId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les filières appartenant à un département spécifique.
     * Interroge la table `filieres`.
     * @param int $departmentId L'ID du département.
     * @return array Les filières du département.
     */
    public function getFieldsByDepartmentId($departmentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT field_id, nom
                FROM filieres
                WHERE department_id = ?
                ORDER BY nom
            ");
            $stmt->execute([$departmentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getFieldsByDepartmentId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si un professeur (chef de filière/département) peut accéder aux notes d'un élément donné.
     * Logique de vérification d'autorisation.
     * @param int $userId L'ID de l'utilisateur.
     * @param int $elementId L'ID de l'élément.
     * @return bool True si l'utilisateur est autorisé, False sinon.
     */
    public function canAccessElementNotes($userId, $elementId) {
        try {
            // 1. Vérifier si l'utilisateur enseigne directement cet élément
            if ($this->isTeachingElement($userId, $elementId)) {
                return true;
            }

            // 2. Obtenir le profil du professeur pour connaître son rôle de degré et les entités gérées
            $profile = $this->getProfessorDetails($userId);
            if (!$profile) {
                return false; // Profil non trouvé
            }

            $professorDegreeRole = $profile['professor_degree_role'];

            // 3. Obtenir les informations sur l'élément (module, filière, département)
            $stmtElementInfo = $this->db->prepare("
                SELECT e.element_id, e.module_id, m.field_id, f.department_id
                FROM elements e
                JOIN modules m ON e.module_id = m.module_id
                JOIN filieres f ON m.field_id = f.field_id
                WHERE e.element_id = ?
            ");
            $stmtElementInfo->execute([$elementId]);
            $elementInfo = $stmtElementInfo->fetch(PDO::FETCH_ASSOC);

            if (!$elementInfo) {
                return false; // Élément non trouvé
            }

            $elementFieldId = $elementInfo['field_id'];
            $elementDepartmentId = $elementInfo['department_id'];

            // 4. Vérifier l'autorisation en fonction du rôle de degré
            if ($professorDegreeRole === 'Chef_de_Filiere') {
                // Si c'est un chef de filière, vérifier s'il gère la filière de cet élément
                return $this->isFieldHead($userId, $elementFieldId);
            } elseif ($professorDegreeRole === 'Chef_de_Departement') {
                // Si c'est un chef de département, vérifier s'il gère le département de cet élément
                return $this->isDepartmentHead($userId, $elementDepartmentId);
            }

            return false; // Rôle non autorisé ou non pertinent pour cet élément
        } catch (PDOException $e) {
            error_log("Database error in canAccessElementNotes: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error in canAccessElementNotes: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un professeur (chef de filière/département) peut accéder aux notes d'un module donné.
     * Logique de vérification d'autorisation.
     * @param int $userId L'ID de l'utilisateur.
     * @param int $moduleId L'ID du module.
     * @return bool True si l'utilisateur est autorisé, False sinon.
     */
    public function canAccessModuleNotes($userId, $moduleId) {
        try {
            $profile = $this->getProfessorDetails($userId);
            if (!$profile) {
                return false; // Profil non trouvé
            }

            $professorDegreeRole = $profile['professor_degree_role'];

            // Obtenir les informations sur le module (filière, département)
            $stmtModuleInfo = $this->db->prepare("
                SELECT m.module_id, m.field_id, f.department_id
                FROM modules m
                JOIN filieres f ON m.field_id = f.field_id
                WHERE m.module_id = ?
            ");
            $stmtModuleInfo->execute([$moduleId]);
            $moduleInfo = $stmtModuleInfo->fetch(PDO::FETCH_ASSOC);

            if (!$moduleInfo) {
                return false; // Module non trouvé
            }

            $moduleFieldId = $moduleInfo['field_id'];
            $moduleDepartmentId = $moduleInfo['department_id'];

            // Vérifier l'autorisation en fonction du rôle de degré
            if ($professorDegreeRole === 'Chef_de_Filiere') {
                // Si c'est un chef de filière, vérifier s'il gère la filière de ce module
                return $this->isFieldHead($userId, $moduleFieldId);
            } elseif ($professorDegreeRole === 'Chef_de_Departement') {
                // Si c'est un chef de département, vérifier s'il gère le département de ce module
                return $this->isDepartmentHead($userId, $moduleDepartmentId);
            }

            return false; // Rôle non autorisé
        } catch (PDOException $e) {
            error_log("Database error in canAccessModuleNotes: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error in canAccessModuleNotes: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un utilisateur a le droit de gérer la période de saisie des notes (chef de département ou chef de filière).
     * Interroge les tables `departements` et `filieres`.
     * @param int $userId L'ID de l'utilisateur.
     * @return bool Vrai si l'utilisateur est chef de département ou chef de filière pour au moins une entité, faux sinon.
     */
    public function canManageGradePeriod($userId) {
        try {
            // Vérifier s'il est chef de département
            $stmtDep = $this->db->prepare("SELECT 1 FROM departements WHERE head_professor_id = ? LIMIT 1");
            $stmtDep->execute([$userId]);
            if ($stmtDep->fetch()) {
                return true;
            }

            // Vérifier s'il est chef de filière
            $stmtFill = $this->db->prepare("SELECT 1 FROM filieres WHERE head_professor_id = ? LIMIT 1");
            $stmtFill->execute([$userId]);
            if ($stmtFill->fetch()) {
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Database error in canManageGradePeriod: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour l'état de la période de saisie des notes.
     * Met à jour la table `system_settings`.
     * @param bool $active True pour activer, False pour désactiver.
     * @return bool Vrai en cas de succès, faux sinon.
     */
    public function setGradePeriodActive($active) {
        try {
            $status = $active ? '1' : '0'; // Convertit le booléen en '1' ou '0' pour la base de données
            $stmt = $this->db->prepare("
                UPDATE system_settings
                SET value = ?
                WHERE name = 'grade_period_active'
            ");
            return $stmt->execute([$status]);
        } catch (PDOException $e) {
            error_log("Database error in setGradePeriodActive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour l'état de la période de saisie des notes de rattrapage.
     * Met à jour la table `system_settings`.
     * @param bool $active True pour activer, False pour désactiver.
     * @return bool Vrai en cas de succès, faux sinon.
     */
    public function setResitGradePeriodActive($active) {
        try {
            $status = $active ? '1' : '0';
            $stmt = $this->db->prepare("
                UPDATE system_settings
                SET value = ?
                WHERE name = 'resit_grade_period_active'
            ");
            return $stmt->execute([$status]);
        } catch (PDOException $e) {
            error_log("Database error in setResitGradePeriodActive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les détails COMPLETS du profil du professeur.
     * Jointures sur `utilisateurs`, `professeurs`, `departements`, et `professor_roles`.
     * Calcule également des statistiques comme le nombre d'éléments et de modules enseignés.
     * @param int $userId L'ID de l'utilisateur (professeur).
     * @return array|false Les détails complets du professeur, ou false si non trouvé.
     */
    public function getProfessorDetails($userId) {
        try {
            // --- Requête principale pour les informations de base ---
            $stmt = $this->db->prepare("
                SELECT
                    u.user_id,
                    p.nom,
                    p.prenom,
                    u.email,
                    u.role AS user_role, -- Renommé pour éviter la confusion
                    p.telephone,
                    p.cin,
                    d.nom AS department_name, -- Nom du département d'attache
                    pr.role AS professor_degree_role -- Rôle spécifique (Chef, etc.)
                FROM utilisateurs u
                JOIN professeurs p ON u.user_id = p.user_id
                LEFT JOIN departements d ON p.department_id = d.department_id
                LEFT JOIN professor_roles pr ON u.user_id = pr.user_id
                WHERE u.user_id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return false; // Si l'utilisateur n'est pas trouvé, on arrête ici.
            }

            // --- Requêtes supplémentaires pour les statistiques ---

            // Compter le nombre d'éléments enseignés (principal ou TP)
            $stmtElements = $this->db->prepare("
                SELECT COUNT(DISTINCT element_id) as element_count
                FROM elements
                WHERE Ref_prof_element = ? OR Ref_prof_tp = ?
            ");
            $stmtElements->execute([$userId, $userId]);
            $user['teaching_stats']['elements_count'] = $stmtElements->fetchColumn();

            // Compter le nombre de modules uniques dans lesquels le professeur enseigne
            $stmtModules = $this->db->prepare("
                SELECT COUNT(DISTINCT m.module_id) as module_count
                FROM modules m
                JOIN elements e ON m.module_id = e.module_id
                WHERE e.Ref_prof_element = ? OR e.Ref_prof_tp = ?
            ");
            $stmtModules->execute([$userId, $userId]);
            $user['teaching_stats']['modules_count'] = $stmtModules->fetchColumn();

            // --- Logique pour les rôles de chef (inchangée mais importante) ---
            $user['managed_entity'] = null; // Une seule clé pour simplifier côté JS

            if ($user['professor_degree_role'] === 'Chef_de_Filiere') {
                $stmtField = $this->db->prepare("SELECT field_id, nom FROM filieres WHERE head_professor_id = ? LIMIT 1");
                $stmtField->execute([$userId]);
                $fieldHead = $stmtField->fetch(PDO::FETCH_ASSOC);
                if ($fieldHead) {
                    $user['managed_entity'] = [
                        'type' => 'Filière',
                        'id' => $fieldHead['field_id'],
                        'name' => $fieldHead['nom']
                    ];
                }
            } elseif ($user['professor_degree_role'] === 'Chef_de_Departement') {
                $stmtDept = $this->db->prepare("SELECT department_id, nom FROM departements WHERE head_professor_id = ? LIMIT 1");
                $stmtDept->execute([$userId]);
                $deptHead = $stmtDept->fetch(PDO::FETCH_ASSOC);
                if ($deptHead) {
                    $user['managed_entity'] = [
                        'type' => 'Département',
                        'id' => $deptHead['department_id'],
                        'name' => $deptHead['nom']
                    ];
                }
            }

            return $user;

        } catch (PDOException $e) {
            error_log("Database error in getProfessorDetails: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Récupère toutes les années académiques disponibles.
     * @return array Liste des années académiques.
     */
    public function getAllAcademicYears() {
        try {
            $stmt = $this->db->prepare("SELECT annee_id FROM annees_academiques ORDER BY annee_id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAllAcademicYears: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les semestres uniques associés aux modules d'une filière spécifique.
     * Utile pour peupler les filtres d'un chef de filière.
     * @param int $fieldId L'ID de la filière.
     * @return array Liste des semestres pertinents.
     */
    public function getSemestersByFieldId($fieldId) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT s.semestre_id, s.nom
                FROM semestres s
                JOIN modules m ON s.semestre_id = m.semestre_id
                WHERE m.field_id = ?
                ORDER BY s.nom ASC
            ");
            $stmt->execute([$fieldId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getSemestersByFieldId: " . $e->getMessage());
            return [];
        }
    } 

    /**
     * Récupère les étapes uniques associées aux modules d'une filière spécifique.
     * @param int $fieldId L'ID de la filière.
     * @return array Liste des étapes pertinentes.
     */
    public function getEtapesByFieldId($fieldId) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT e.etape_id, e.nom_etape
                FROM etapes e
                JOIN semestres s ON e.etape_id = s.etape_id
                JOIN modules m ON s.semestre_id = m.semestre_id
                WHERE m.field_id = ?
                ORDER BY e.etape_id ASC
            ");
            $stmt->execute([$fieldId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getEtapesByFieldId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les semestres uniques associés aux modules d'un département spécifique.
     * Utile pour peupler les filtres d'un chef de département.
     * @param int $departmentId L'ID du département.
     * @return array Liste des semestres pertinents.
     */
    public function getSemestersByDepartmentId($departmentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT s.semestre_id, s.nom
                FROM semestres s
                JOIN modules m ON s.semestre_id = m.semestre_id
                JOIN filieres f ON m.field_id = f.field_id
                WHERE f.department_id = ?
                ORDER BY s.nom ASC
            ");
            $stmt->execute([$departmentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getSemestersByDepartmentId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les étapes uniques associées aux modules d'un département spécifique.
     * @param int $departmentId L'ID du département.
     * @return array Liste des étapes pertinentes.
     */
    public function getEtapesByDepartmentId($departmentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT e.etape_id, e.nom_etape
                FROM etapes e
                JOIN semestres s ON e.etape_id = s.etape_id
                JOIN modules m ON s.semestre_id = m.semestre_id
                JOIN filieres f ON m.field_id = f.field_id
                WHERE f.department_id = ?
                ORDER BY e.etape_id ASC
            ");
            $stmt->execute([$departmentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getEtapesByDepartmentId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les semestres disponibles.
     * @return array Liste des semestres.
     */
    public function getAllSemesters() {
        try {
            $stmt = $this->db->prepare("SELECT semestre_id, nom FROM semestres ORDER BY nom ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAllSemesters: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les semestres associés aux modules ou éléments enseignés par un professeur.
     * @param int $userId L'ID du professeur.
     * @return array Liste des semestres pertinents pour le professeur.
     */
    public function getProfessorSemesters($userId) {
        try {
            $sql = "
                SELECT DISTINCT s.semestre_id, s.nom
                FROM semestres s
                JOIN modules m ON s.semestre_id = m.semestre_id
                JOIN elements e ON m.module_id = e.module_id
                WHERE e.Ref_prof_element = :userId OR e.Ref_prof_tp = :userId
                ORDER BY s.nom ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':userId' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getProfessorSemesters: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les étapes (années d'étude) disponibles.
     * @return array Liste des étapes.
     */
    public function getAllEtapes() {
        try {
            $stmt = $this->db->prepare("SELECT etape_id, nom_etape FROM etapes ORDER BY etape_id ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAllEtapes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les filières.
     * @return array Liste des filières.
     */
    public function getAllFilieres() {
        try {
            $stmt = $this->db->prepare("SELECT field_id, nom FROM filieres ORDER BY nom ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAllFilieres: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les filières gérées par un chef de département.
     * @param int $departmentId L'ID du département.
     * @return array Liste des filières du département.
     */
    public function getFilieresByDepartment($departmentId) {
        try {
            $stmt = $this->db->prepare("SELECT field_id, nom FROM filieres WHERE department_id = ? ORDER BY nom ASC");
            $stmt->execute([$departmentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getFilieresByDepartment: " . $e->getMessage());
            return [];
        }
    }

    // Dans ProfessorModel.php
public function getAllCycles() {
    try {
        $stmt = $this->db->prepare("SELECT cycle_id, nom FROM cycles ORDER BY nom ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getAllCycles: " . $e->getMessage());
        return [];
    }
}
public function getProfessorNotesForModuleCSV($userId, $moduleId, $fieldId, $semestreId = null, $etapeId = null) {
        try {
           

            // Vérifier que le module appartient à la filière spécifiée
            $stmtCheckField = $this->db->prepare("SELECT 1 FROM modules WHERE module_id = ? AND field_id = ?");
            $stmtCheckField->execute([$moduleId, $fieldId]);
            if ($stmtCheckField->fetch() === false) {
                return []; // Le module n'appartient pas à cette filière
            }

            $sql = "
                SELECT
                    et.nom AS student_nom,
                    et.prenom AS student_prenom,
                    et.user_id AS student_id, -- ID étudiant pour référence
                    el.nom AS element_name,
                    m.nom AS module_name,
                    f.nom AS filiere_nom,
                    n.note_tp,
                    n.note_td,
                    n.note_cc,
                    n.note_exam,
                    n.note_rattrapage,
                    n.note_finale,
                    s.nom AS semestre_nom,
                    eta.nom_etape AS etape_nom
                FROM notes n
                JOIN etudiants et ON n.student_id = et.user_id
                JOIN elements el ON n.element_id = el.element_id
                JOIN modules m ON el.module_id = m.module_id
                JOIN filieres f ON m.field_id = f.field_id
                LEFT JOIN semestres s ON m.semestre_id = s.semestre_id
                LEFT JOIN etapes eta ON s.etape_id = eta.etape_id
                WHERE m.module_id = :moduleId
                  AND m.field_id = :fieldId
                  AND (el.Ref_prof_element = :userId OR el.Ref_prof_tp = :userId)
            ";
            $params = [
                ':moduleId' => $moduleId,
                ':fieldId' => $fieldId,
                ':userId' => $userId
            ];

            if ($semestreId !== null && $semestreId !== '') {
                $sql .= " AND m.semestre_id = :semestreId";
                $params[':semestreId'] = $semestreId;
            }
            if ($etapeId !== null && $etapeId !== '') {
                $sql .= " AND s.etape_id = :etapeId";
                $params[':etapeId'] = $etapeId;
            }

            $sql .= " ORDER BY student_nom, student_prenom, element_name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getProfessorNotesForModuleCSV: " . $e->getMessage());
            return [];
        }
    }
    
}
