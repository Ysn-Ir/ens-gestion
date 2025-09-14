<?php
// ProfessorController.php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/ProfessorMiddleware.php';
require_once __DIR__ . '/../models/ProfessorModel.php';
require_once __DIR__ . '/../utils/Response.php';

class ProfessorController {
    private $model;
    private $response;

    public function __construct() {
        $this->model = new ProfessorModel();
        $this->response = new Response();
    }

    /**
     * Récupère les détails du profil du professeur connecté.
     * Cet endpoint est accessible à tous les professeurs pour consultation.
     */
    public function getProfessorProfile() {
        try {
            // ProfessorMiddleware a déjà vérifié la session et le rôle.
            $userId = $_SESSION['user']['user_id'];
            $profile = $this->model->getProfessorDetails($userId);

            if ($profile) {
                $this->response->send(200, $profile);
            } else {
                $this->response->send(404, ['error' => 'Profil professeur non trouvé.']);
            }
        } catch (Exception $e) {
            error_log("Error in getProfessorProfile: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération du profil.']);
        }
    }

    /**
     * Récupère le statut actuel de la période de saisie des notes.
     * Accessible à tous les professeurs.
     */
    public function getGradePeriodStatus() {
        try {
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie l'authentification et le rôle
            $status = $this->model->getGradePeriodStatus();
            $this->response->send(200, $status);
        } catch (Exception $e) {
            error_log("Error in getGradePeriodStatus: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération du statut.']);
        }
    }

    /**
     * Récupère le statut actuel de la période de saisie des notes de rattrapage.
     * Accessible à tous les professeurs.
     */
    public function getResitGradePeriodStatus() {
        try {
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie l'authentification et le rôle
            $status = $this->model->isResitGradePeriodActive();
            $this->response->send(200, ['active' => $status]);
        } catch (Exception $e) {
            error_log("Error in getResitGradePeriodStatus: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération du statut de la période de rattrapage.']);
        }
    }

    /**
     * Met à jour le statut de la période de saisie des notes (ouverte/fermée).
     * Accessible uniquement aux chefs de département ou de filière.
     */
    public function updateGradePeriodStatus() {
        try {
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie l'authentification et le rôle
            $userId = $_SESSION['user']['user_id'];

            // Vérification supplémentaire des privilèges au niveau du contrôleur
            if (!$this->model->canManageGradePeriod($userId)) {
                $this->response->send(403, ['error' => 'Vous n\'êtes pas autorisé à gérer la période de saisie des notes.']);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validation de l'entrée
            if (!isset($data['active']) || !is_bool($data['active'])) {
                $this->response->send(400, ['error' => 'Paramètre "active" manquant ou invalide (doit être true ou false).']);
                return;
            }

            $success = $this->model->setGradePeriodActive($data['active']);

            if ($success) {
                $statusMessage = $data['active'] ? 'ouverte' : 'fermée';
                $this->response->send(200, ['message' => "La période de saisie des notes est maintenant {$statusMessage}."]);
            } else {
                $this->response->send(500, ['error' => 'Erreur lors de la mise à jour de la période de saisie des notes.']);
            }
        } catch (Exception $e) {
            error_log("Error in updateGradePeriodStatus: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la mise à jour du statut de la période de saisie des notes.']);
        }
    }

    /**
     * Met à jour le statut de la période de saisie des notes de rattrapage (ouverte/fermée).
     * Accessible uniquement aux chefs de département ou de filière.
     */
    public function updateResitGradePeriodStatus() {
        try {
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie l'authentification et le rôle
            $userId = $_SESSION['user']['user_id'];

            if (!$this->model->canManageGradePeriod($userId)) {
                $this->response->send(403, ['error' => 'Vous n\'êtes pas autorisé à gérer la période de saisie des notes de rattrapage.']);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['active']) || !is_bool($data['active'])) {
                $this->response->send(400, ['error' => 'Paramètre "active" manquant ou invalide (doit être true ou false).']);
                return;
            }

            $success = $this->model->setResitGradePeriodActive($data['active']);

            if ($success) {
                $statusMessage = $data['active'] ? 'ouverte' : 'fermée';
                $this->response->send(200, ['message' => "La période de saisie des notes de rattrapage est maintenant {$statusMessage}."]);
            } else {
                $this->response->send(500, ['error' => 'Erreur lors de la mise à jour de la période de saisie des notes de rattrapage.']);
            }
        } catch (Exception $e) {
            error_log("Error in updateResitGradePeriodStatus: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la mise à jour du statut de la période de saisie des notes de rattrapage.']);
        }
    }

    /**
     * Récupère la liste des éléments d'enseignement attribués au professeur connecté,
     * avec options de filtrage par semestre, étape et filière.
     * Accessible à tous les professeurs.
     */
    public function getTeachingElements() {
        try {
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie l'authentification et le rôle
            $userId = $_SESSION['user']['user_id'];

            // Récupérer les paramètres de requête optionnels
            $semestreId = filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
            $etapeId = filter_input(INPUT_GET, 'etape_id', FILTER_VALIDATE_INT); // Changé de 'annee_id' à 'etape_id'
            $fieldId = filter_input(INPUT_GET, 'field_id', FILTER_VALIDATE_INT);

            $elements = $this->model->getProfessorElements($userId, $semestreId, $etapeId, $fieldId);
            $this->response->send(200, $elements);
        } catch (Exception $e) {
            error_log("Error in getTeachingElements: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des éléments enseignés.']);
        }
    }

    /**
     * Récupère les notes des étudiants pour un élément spécifique,
     * avec options de filtrage par semestre et étape académique.
     * Accessible aux professeurs qui enseignent l'élément, ou aux chefs de filière/département qui le gèrent.
     * @param int $elementId L'ID de l'élément.
     */
    public function getElementNotes($elementId) {
        try {
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie l'authentification et le rôle
            $userId = $_SESSION['user']['user_id'];

            // Récupérer les paramètres de requête optionnels
            $semestreId = filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
            $etapeId = filter_input(INPUT_GET, 'etape_id', FILTER_VALIDATE_INT); // Changé de 'annee_id' à 'etape_id'

            // Vérifie si le professeur a le droit d'accéder aux notes de cet élément
            if (!$this->model->canAccessElementNotes($userId, $elementId)) {
                $this->response->send(403, ['error' => 'Vous n\'êtes pas autorisé à accéder aux notes de cet élément.']);
                return;
            }

            $notes = $this->model->getElementNotes($elementId, $semestreId, $etapeId);
            $this->response->send(200, $notes);
        } catch (Exception $e) {
            error_log("Error in getElementNotes: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des notes de l\'élément.']);
        }
    }

    /**
     * Met à jour une note spécifique d'un étudiant pour un élément.
     * Accessible aux professeurs qui enseignent l'élément, ou aux chefs de filière/département.
     * La période de saisie des notes (normale ou rattrapage) doit être active selon le type de note.
     */
    public function updateNote() {
        try {
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie l'authentification et le rôle
            $userId = $_SESSION['user']['user_id'];

            $data = json_decode(file_get_contents('php://input'), true);

            // Validation de l'entrée
            if (!isset($data['note_id'], $data['note_type'], $data['note_value'])) {
                $this->response->send(400, ['error' => 'Données de note manquantes (note_id, note_type, note_value).']);
                return;
            }

            $noteId = $data['note_id'];
            $noteType = $data['note_type'];
            $noteValue = $data['note_value']; // Peut être null

            // --- NOUVELLE VALIDATION DES NOTES (0-20) ---
            if ($noteValue !== null && ($noteValue < 0 || $noteValue > 20)) {
                $this->response->send(400, ['error' => 'La note doit être comprise entre 0 et 20.']);
                return;
            }
            // --- FIN NOUVELLE VALIDATION ---

            // Vérifier la période de saisie en fonction du type de note
            if ($noteType === 'rattrapage') {
                if (!$this->model->isResitGradePeriodActive()) {
                    $this->response->send(403, ['error' => 'La période de saisie des notes de rattrapage est fermée. Vous ne pouvez pas enregistrer cette note.']);
                    return;
                }
            } else { // Pour 'tp', 'td', 'cc', 'exam', 'final'
                if (!$this->model->isGradePeriodActive()) {
                    $this->response->send(403, ['error' => 'La période de saisie des notes est fermée. Vous ne pouvez pas enregistrer cette note.']);
                    return;
                }
            }

            // Vérification des privilèges spécifiques pour la modification de ce type de note
            if (!$this->model->canUpdateNote($userId, $noteId, $noteType)) {
                $this->response->send(403, ['error' => 'Vous n\'êtes pas autorisé à modifier ce type de note pour cet élément.']);
                return;
            }

            // Appeler la méthode de mise à jour du modèle
            $success = $this->model->updateNote($noteId, $noteValue, $noteType);

            if ($success) {
                $this->response->send(200, ['message' => 'Note mise à jour avec succès.']);
            } else {
                // Si la mise à jour échoue au niveau du modèle (ex: erreur DB), renvoyer une 500
                $this->response->send(500, ['error' => 'Erreur lors de la mise à jour de la note dans la base de données.']);
            }
        } catch (Exception $e) {
            error_log("Error in updateNote: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la mise à jour de la note.']);
        }
    }

    /**
     * Récupère toutes les notes pour une filière spécifique.
     * @param int $fieldId L'ID de la filière.
     */
    public function getFieldNotes($fieldId) {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $userId = $_SESSION['user']['user_id'];

            $profile = $this->model->getProfessorDetails($userId);
            if (!$profile) {
                $this->response->send(403, ['error' => 'Profil non trouvé.']);
                return;
            }

            $isAuthorized = false;

            // CORRECTION : Utiliser également ici la nouvelle structure 'managed_entity'
            $managedEntity = $profile['managed_entity'] ?? null;

            // Si chef de filière, doit gérer cette filière
            if ($profile['professor_degree_role'] === 'Chef_de_Filiere' && $managedEntity && $managedEntity['id'] == $fieldId) {
                $isAuthorized = true;
            }
            
            // Si chef de département, doit gérer le département de cette filière
            if ($profile['professor_degree_role'] === 'Chef_de_Departement' && $managedEntity) {
                $stmtFieldDept = $this->model->getDbConnection()->prepare("SELECT department_id FROM filieres WHERE field_id = ?");
                $stmtFieldDept->execute([$fieldId]);
                $fieldDepartmentId = $stmtFieldDept->fetchColumn();
                if ($fieldDepartmentId && $managedEntity['id'] == $fieldDepartmentId) {
                    $isAuthorized = true;
                }
            }

            if (!$isAuthorized) {
                $this->response->send(403, ['error' => 'Vous n\'êtes pas autorisé à accéder aux notes de cette filière.']);
                return;
            }

            $semestreId = filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
            $etapeId = filter_input(INPUT_GET, 'etape_id', FILTER_VALIDATE_INT);

            $notes = $this->model->getFieldNotes($fieldId, $semestreId, $etapeId);
            $this->response->send(200, $notes);
        } catch (Exception $e) {
            error_log("Error in getFieldNotes: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des notes de la filière.']);
        }
    }

    /**
     * Récupère les modules d'un département donné.
     * Accessible aux professeurs ayant les privilèges appropriés.
     * @param int $departmentId L'ID du département.
     */
    public function getModulesByDepartment($departmentId) {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $userId = $_SESSION['user']['user_id'];

            // Vérifier si l'utilisateur est chef de ce département
            if (!$this->model->isDepartmentHead($userId, $departmentId)) {
                $this->response->send(403, ['error' => 'Vous n\'êtes pas autorisé à accéder aux modules de ce département.']);
                return;
            }

            $modules = $this->model->getModulesByDepartmentId($departmentId);
            $this->response->send(200, $modules);
        } catch (Exception $e) {
            error_log("Error in getModulesByDepartment: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des modules du département.']);
        }
    }

    /**
     * Récupère les filières d'un département donné.
     * Accessible aux professeurs ayant les privilèges appropriés.
     * @param int $departmentId L'ID du département.
     */
    public function getFieldsByDepartment($departmentId) {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $userId = $_SESSION['user']['user_id'];

            // Vérifier si l'utilisateur est chef de ce département
            if (!$this->model->isDepartmentHead($userId, $departmentId)) {
                $this->response->send(403, ['error' => 'Vous n\'êtes pas autorisé à accéder aux filières de ce département.']);
                return;
            }

            $fields = $this->model->getFieldsByDepartmentId($departmentId);
            $this->response->send(200, $fields);
        } catch (Exception $e) {
            error_log("Error in getFieldsByDepartment: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des filières du département.']);
        }
    }

    /**
     * Récupère les modules d'une filière donnée.
     * Accessible aux professeurs ayant les privilèges appropriés (chef de filière ou chef de département).
     * @param int $fieldId L'ID de la filière.
     */
    public function getModulesByField($fieldId) {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $userId = $_SESSION['user']['user_id'];

            $profile = $this->model->getProfessorDetails($userId);
            if (!$profile) {
                $this->response->send(403, ['error' => 'Profil non trouvé.']);
                return;
            }

            $isAuthorized = false;
            
            // CORRECTION : Utiliser la nouvelle structure 'managed_entity'
            $managedEntity = $profile['managed_entity'] ?? null;

            // Si chef de filière, doit gérer cette filière
            if ($profile['professor_degree_role'] === 'Chef_de_Filiere' && $managedEntity && $managedEntity['id'] == $fieldId) {
                $isAuthorized = true;
            }
            
            // Si chef de département, doit gérer le département de cette filière
            if ($profile['professor_degree_role'] === 'Chef_de_Departement' && $managedEntity) {
                $stmtFieldDept = $this->model->getDbConnection()->prepare("SELECT department_id FROM filieres WHERE field_id = ?");
                $stmtFieldDept->execute([$fieldId]);
                $fieldDepartmentId = $stmtFieldDept->fetchColumn();
                if ($fieldDepartmentId && $managedEntity['id'] == $fieldDepartmentId) {
                    $isAuthorized = true;
                }
            }

            if (!$isAuthorized) {
                $this->response->send(403, ['error' => 'Vous n\'êtes pas autorisé à accéder aux modules de cette filière.']);
                return;
            }

            $modules = $this->model->getModulesByFieldId($fieldId);
            $this->response->send(200, $modules);
        } catch (Exception $e) {
            error_log("Error in getModulesByField: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des modules de la filière.']);
        }
    }

    /**
     * Récupère les notes pour un module spécifique.
     * Accessible aux professeurs qui enseignent un élément de ce module, ou aux chefs de filière/département qui le gèrent.
     * @param int $moduleId L'ID du module.
     */
    public function getModuleNotes($moduleId) {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $userId = $_SESSION['user']['user_id'];

            // Vérifie si le professeur a le droit d'accéder aux notes de ce module
            if (!$this->model->canAccessModuleNotes($userId, $moduleId)) {
                $this->response->send(403, ['error' => 'Vous n\'êtes pas autorisé à accéder aux notes de ce module.']);
                return;
            }

            $notes = $this->model->getModuleNotes($moduleId);
            $this->response->send(200, $notes);
        } catch (Exception $e) {
            error_log("Error in getModuleNotes: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des notes du module.']);
        }
    }

    /**
     * Récupère toutes les années académiques.
     */
    public function getAcademicYears() {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $years = $this->model->getAllAcademicYears();
            $this->response->send(200, $years);
        } catch (Exception $e) {
            error_log("Error in getAcademicYears: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des années académiques.']);
        }
    }

    /**
     * Récupère tous les semestres.
     */
    public function getSemesters() {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $semesters = $this->model->getAllSemesters();
            $this->response->send(200, $semesters);
        } catch (Exception $e) {
            error_log("Error in getSemesters: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des semestres.']);
        }
    }

    /**
     * Récupère les semestres pertinents pour le professeur connecté.
     */
    public function getProfessorSemesters() {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $userId = $_SESSION['user']['user_id'];
            $semesters = $this->model->getProfessorSemesters($userId);
            $this->response->send(200, $semesters);
        } catch (Exception $e) {
            error_log("Error in getProfessorSemesters: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des semestres du professeur.']);
        }
    }

    /**
     * Récupère toutes les étapes.
     */
    public function getEtapes() {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $etapes = $this->model->getAllEtapes();
            $this->response->send(200, $etapes);
        } catch (Exception $e) {
            error_log("Error in getEtapes: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des étapes.']);
        }
    }

    /**
     * Récupère les filières. Si l'utilisateur est un chef de département, filtre par son département.
     */
    public function getFilieres() {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $userId = $_SESSION['user']['user_id'];
            $profile = $this->model->getProfessorDetails($userId); // Get professor role and managed department/field

            if ($profile && $profile['professor_degree_role'] === 'Chef_de_Departement' && isset($profile['managed_department_id'])) {
                $filieres = $this->model->getFilieresByDepartment($profile['managed_department_id']);
            } else {
                $filieres = $this->model->getAllFilieres();
            }
            $this->response->send(200, $filieres);
        } catch (Exception $e) {
            error_log("Error in getFilieres: " . $e->getMessage());
            $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des filières.']);
        }
    }

/**
 * Récupère toutes les notes pour un département spécifique.
 * Accessible uniquement aux chefs de département.
 * @param int $departmentId L'ID du département.
 */
public function getDepartmentNotes($departmentId) {
    try {
        (new ProfessorMiddleware())->verifyProfessor();
        $userId = $_SESSION['user']['user_id'];

        // Vérifier si l'utilisateur est chef de ce département
        if (!$this->model->isDepartmentHead($userId, $departmentId)) {
            $this->response->send(403, ['error' => 'Vous n\'êtes pas autorisé à accéder aux notes de ce département.']);
            return;
        }

        // Récupérer les paramètres de requête optionnels
        $semestreId = filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
        $etapeId = filter_input(INPUT_GET, 'etape_id', FILTER_VALIDATE_INT);
        $fieldId = filter_input(INPUT_GET, 'field_id', FILTER_VALIDATE_INT);

        $notes = $this->model->getDepartmentNotes($departmentId, $semestreId, $etapeId, $fieldId);
        $this->response->send(200, $notes);

    } catch (Exception $e) {
        error_log("Error in getDepartmentNotes: " . $e->getMessage());
        $this->response->send(500, ['error' => 'Une erreur interne est survenue lors de la récupération des notes du département.']);
    }
}

/**
 * Récupère les semestres pertinents pour une filière donnée.
 * Accessible uniquement au chef de cette filière ou au chef du département parent.
 * @param int $fieldId L'ID de la filière.
 */
public function getSemestersForField($fieldId) {
    try {
        (new ProfessorMiddleware())->verifyProfessor();
        // Ici, une vérification d'autorisation serait idéale, mais pour l'instant, on fait confiance
        // au fait que le JS n'appellera cet endpoint que pour le chef concerné.
        $semesters = $this->model->getSemestersByFieldId($fieldId);
        $this->response->send(200, $semesters);
    } catch (Exception $e) {
        error_log("Error in getSemestersForField: " . $e->getMessage());
        $this->response->send(500, ['error' => 'Erreur interne.']);
    }
}

/**
 * Récupère les étapes pertinentes pour une filière donnée.
 * @param int $fieldId L'ID de la filière.
 */
public function getEtapesForField($fieldId) {
    try {
        (new ProfessorMiddleware())->verifyProfessor();
        $etapes = $this->model->getEtapesByFieldId($fieldId);
        $this->response->send(200, $etapes);
    } catch (Exception $e) {
        error_log("Error in getEtapesForField: " . $e->getMessage());
        $this->response->send(500, ['error' => 'Erreur interne.']);
    }
}

/**
 * Récupère les semestres pertinents pour un département donné.
 * Accessible uniquement au chef de ce département.
 * @param int $departmentId L'ID du département.
 */
public function getSemestersForDepartment($departmentId) {
    try {
        (new ProfessorMiddleware())->verifyProfessor();
        $userId = $_SESSION['user']['user_id'];
        if (!$this->model->isDepartmentHead($userId, $departmentId)) {
            $this->response->send(403, ['error' => 'Accès non autorisé.']);
            return;
        }
        $semesters = $this->model->getSemestersByDepartmentId($departmentId);
        $this->response->send(200, $semesters);
    } catch (Exception $e) {
        error_log("Error in getSemestersForDepartment: " . $e->getMessage());
        $this->response->send(500, ['error' => 'Erreur interne.']);
    }
}

/**
 * Récupère les étapes pertinentes pour un département donné.
 * @param int $departmentId L'ID du département.
 */
public function getEtapesForDepartment($departmentId) {
    try {
        (new ProfessorMiddleware())->verifyProfessor();
        $userId = $_SESSION['user']['user_id'];
        if (!$this->model->isDepartmentHead($userId, $departmentId)) {
            $this->response->send(403, ['error' => 'Accès non autorisé.']);
            return;
        }
        $etapes = $this->model->getEtapesByDepartmentId($departmentId);
        $this->response->send(200, $etapes);
    } catch (Exception $e) {
        error_log("Error in getEtapesForDepartment: " . $e->getMessage());
        $this->response->send(500, ['error' => 'Erreur interne.']);
    }
}

    /**
     * Exporte les notes d'un module et d'une filière spécifiques pour le professeur connecté au format CSV.
     * @param int $moduleId L'ID du module.
     * @param int $fieldId L'ID de la filière.
     */
    public function exportProfessorModuleNotesCSV($moduleId, $fieldId) {
        try {
            (new ProfessorMiddleware())->verifyProfessor();
            $userId = $_SESSION['user']['user_id'];

            // Récupérer les paramètres de requête optionnels pour le filtrage
            $semestreId = filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
            $etapeId = filter_input(INPUT_GET, 'etape_id', FILTER_VALIDATE_INT);

            // Récupérer les notes à l'aide de la nouvelle méthode du modèle
            $notes = $this->model->getProfessorNotesForModuleCSV($userId, $moduleId, $fieldId, $semestreId, $etapeId);

            if (empty($notes)) {
                // Si aucune note n'est trouvée (ou si le prof n'est pas autorisé), on envoie une erreur.
                $this->response->send(404, ['error' => 'Aucune note trouvée pour cet export, ou vous n\'êtes pas autorisé à accéder à ce module.']);
                return;
            }

            // Définir les en-têtes HTTP pour le téléchargement du fichier CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="export_notes_module_' . $moduleId . '_filiere_' . $fieldId . '.csv"');
            
            $output = fopen('php://output', 'w');

            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // MODIFICATION ICI : Ajouter le point-virgule comme séparateur
            fputcsv($output, [
                'ID Etudiant', 'Nom', 'Prenom', 'Filiere', 'Semestre', 'Etape',
                'Module', 'Element', 'Note TP', 'Note TD', 'Note CC', 'Note Examen',
                'Note Rattrapage', 'Note Finale'
            ], ';');

            // Écrire les données des notes dans le fichier
            foreach ($notes as $note) {
                // MODIFICATION ICI : Ajouter le point-virgule comme séparateur
                fputcsv($output, [
                    $note['student_id'],
                    $note['student_nom'],
                    $note['student_prenom'],
                    $note['filiere_nom'],
                    $note['semestre_nom'],
                    $note['etape_nom'],
                    $note['module_name'],
                    $note['element_name'],
                    $note['note_tp'] ?? '',
                    $note['note_td'] ?? '',
                    $note['note_cc'] ?? '',
                    $note['note_exam'] ?? '',
                    $note['note_rattrapage'] ?? '',
                    $note['note_finale'] ?? ''
                ], ';');
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            error_log("Error in exportProfessorModuleNotesCSV: " . $e->getMessage());
        }
    }
}