<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/AdminMiddleware.php';
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../utils/Response.php';

class AdminController {
    private $model;
    private $authMiddleware;
    private $adminMiddleware;
    private $response;

    public function __construct() {
        $this->model = new AdminModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->adminMiddleware = new AdminMiddleware();
        $this->response = new Response();
    }

///////////////////////////////////////STUDENT///////////////////////////////////////////////////
    

    public function getStudents() {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    $students = $this->model->getAllStudents();
    $this->response->send(200, [
        'success' => true,
        'count'   => count($students),
        'students'=> $students
    ]);
}
public function getStudentDetail($user_id) {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    $students = $this->model->getStudentDetail($user_id);
    $this->response->send(200, [
        'success' => true,
        'students'=> $students
    ]);
}

    public function createStudent() {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    $data = json_decode(file_get_contents('php://input'), true);

    try {
        $result = $this->model->createStudent($data);
        if ($result) {
            $this->response->send(201, ['message' => 'Student created successfully']);
        } else {
            $this->response->send(500, ['message' => 'Failed to create student']);
        }
    } catch (Exception $e) {
        $this->response->send(400, ['message' => $e->getMessage()]);
    }
}

    public function updateStudent($id) {
        $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();

        $data = json_decode(file_get_contents('php://input'), true);
        $result = $this->model->updateStudent($id, $data);
        
        if ($result) {
            $this->response->send(200, ['message' => 'Student updated successfully']);
        } else {
            $this->response->send(500, ['message' => 'Failed to update student']);
        }
    }

    public function deleteStudent($id) {
        $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();

        $result = $this->model->deleteStudent($id);
        
        if ($result) {
            $this->response->send(200, ['message' => 'Student deleted successfully']);
        } else {    
            $this->response->send(500, ['message' => 'Failed to delete student']);
        }
    }
    public function getFilteredStudents() {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    
    $fieldId = filter_input(INPUT_GET, 'field_id', FILTER_VALIDATE_INT);
    $sectionId = filter_input(INPUT_GET, 'section_id', FILTER_VALIDATE_INT);
    $groupId = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);
    $anneeId = filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
    $etapeId = filter_input(INPUT_GET, 'etape_id', FILTER_VALIDATE_INT);
    $semestreId = filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
    $cycleId  = filter_input(INPUT_GET, 'cycle_id', FILTER_VALIDATE_INT);
    $departementId =filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);
    $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
    $students = (new AdminModel())->getFilteredStudents(
    $anneeId,
    $fieldId,
    $semestreId,
    $sectionId,
    $groupId,
    $cycleId,
    $departementId,
    false, 
    $search 
);

    
    // Ensure we always return an array, even if empty
    $students = is_array($students) ? $students : [];
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 200,
        'data' => [
            'success' => true,
            'count' => count($students),
            'students' => array_values($students) // Ensure numeric array
        ]
    ]);
    }
public function assignModule($studentId, $moduleIds) {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();

    try {
        $result = $this->model->assignModules($studentId, $moduleIds);
        
        http_response_code($result['status']);
        header('Content-Type: application/json');
        echo json_encode($result['data']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
    exit;
}
    public function getAllModule() {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $modules = $this->model->getAllModule();
        $this->response->send(200, $modules);
    }
   public function getAllFilieres() {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    try {
        $filieres = $this->model->getAllFilieres();
        // Send data directly, don't wrap inside 'status' and 'data' keys
        $this->response->send(200, $filieres);
    } catch (Exception $e) {
        $this->response->send(500, ['message' => 'Erreur lors du chargement des filières', 'error' => $e->getMessage()]);
    }
}
    public function getFilteredFilieres(){
        $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    try {
        $departementId =filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);
        $filieres = $this->model->getFilierByDepartement($departementId);
        // Send data directly, don't wrap inside 'status' and 'data' keys
        $this->response->send(200, $filieres);
    } catch (Exception $e) {
        $this->response->send(500, ['message' => 'Erreur lors du chargement des filières', 'error' => $e->getMessage()]);
    }
    }
 public function getAllCycles() {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    try {
        $cycles = $this->model->getAllCycles();
        // Send data directly, don't wrap inside 'status' and 'data' keys
        $this->response->send(200, $cycles);
    } catch (Exception $e) {
        $this->response->send(500, ['message' => 'Erreur lors du chargement des cycles', 'error' => $e->getMessage()]);
    }
}

public function getAllSections() {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    try {
        $sections = $this->model->getAllSections();
        $this->response->send(200, $sections);   //  ← send RAW array

    } catch (Exception $e) {
        $this->response->send(500, ['status' => 'error', 'message' => 'Erreur lors du chargement des Sections', 'error' => $e->getMessage()]);
    }
}

public function getAllGroups() {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    try {
        $groups = $this->model->getAllGroups();
        $this->response->send(200, $groups);   //  ← send RAW array

    } catch (Exception $e) {
        $this->response->send(500, ['status' => 'error', 'message' => 'Erreur lors du chargement des Groups', 'error' => $e->getMessage()]);
    }
}
public function getAllEtapes() {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    try {
        $sections = $this->model->getAllEtapes();
        $this->response->send(200, $sections);   //  ← send RAW array

    } catch (Exception $e) {
        $this->response->send(500, ['status' => 'error', 'message' => 'Erreur lors du chargement des Etapes', 'error' => $e->getMessage()]);
    }
}
public function getAllSemestres() {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    try {
        $sections = $this->model->getAllSemesteres();
        $this->response->send(200, $sections);   //  ← send RAW array

    } catch (Exception $e) {
        $this->response->send(500, ['status' => 'error', 'message' => 'Erreur lors du chargement des Semesters', 'error' => $e->getMessage()]);
    }
}

public function getSectionsByFiliere($fieldId) {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();

    if (!is_numeric($fieldId)) {
        $this->response->send(400, ['status' => 'error', 'message' => 'ID filière invalide']);
        return;
    }

    try {
        $sections = $this->model->getSectionsByFiliere($fieldId);
        $this->response->send(200, $sections);   //  ← send RAW array

    } catch (Exception $e) {
        $this->response->send(500, ['status' => 'error', 'message' => 'Erreur lors du chargement des sections']);
    }
}

public function getGroupesBySection($sectionId) {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();

    if (!is_numeric($sectionId)) {
        $this->response->send(400, ['status' => 'error', 'message' => 'ID section invalide']);
        return;
    }

    try {
        $groupes = $this->model->getGroupesBySection($sectionId);
        $this->response->send(200, $groupes);   //  ← send RAW array

    } catch (Exception $e) {
        $this->response->send(500, ['status' => 'error', 'message' => 'Erreur lors du chargement des groupes']);
    }
}

public function getGroupesByFiliere($fieldId) {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();

    if (!is_numeric($fieldId)) {
        $this->response->send(400, ['status' => 'error', 'message' => 'ID filière invalide']);
        return;
    }

    try {
        $groupes = $this->model->getGroupesByFiliere($fieldId);
        $this->response->send(200, $groupes);   //  ← send RAW array

    } catch (Exception $e) {
        $this->response->send(500, ['status' => 'error', 'message' => 'Erreur lors du chargement des groupes']);
    }
}
    public function getAllYears() {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();

        try {
            $years = $this->model->getAllYears();
            $this->response->send(200, [
                'status' => 'success',
                'data' => $years
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Error loading years',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getAllDepartments() {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();

        try {
            $departments = $this->model->getAllDepartments();
            $this->response->send(200,$departments);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Error loading departments',
                'error' => $e->getMessage()
            ]);
        }
    }
    public function getAllModules() {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $modules = $this->model->getAllModules();
        $this->response->send(200, $modules);
    }
    public function getAllElements() {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $elements = $this->model->getAllElements();
        $this->response->send(200, $elements);
    }

    
    ////////////////////////////////////professor///////////////////////////////////////////////////
    public function getAllProfessors()
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
        try {
            $professors = $this->model->getAllProfessors($search);
            $this->response->send(200, $professors);
        } catch (Exception $e) {
            error_log("getAllProfessors: " . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Error loading professors',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function createProfessor() {
        $this->authMiddleware->verifySession(); 
        $this->adminMiddleware->verifyAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $userData = $data['userData'] ?? null;
        $profData = $data['profData'] ?? null;
        $roles = $data['roles'] ?? [];
        if (!$userData || !$profData) {
            $this->response->send(400, ['success' => false, 'message' => 'Missing professor data']);
            return;
        }
        $result = $this->model->addProfessor($userData, $profData, $roles);
        $this->response->send($result['status'], $result['data']);
    }

    public function updateProfessor($id)
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $profData = $data['profData'] ?? null;
        $roles = $data['roles'] ?? [];
        if (!$profData) {
            $this->response->send(400, ['message' => 'Missing professor data']);
            return;
        }
        $result = $this->model->updateProfessor($id, $profData, $roles);
        $this->response->send($result['status'], $result['data']);
    }

    public function deleteProfessor($id)
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $result = $this->model->deleteProfessor($id);
        if ($result) {
            $this->response->send(200, ['message' => 'Professor deleted successfully']);
        } else {
            $this->response->send(500, ['message' => 'Failed to delete professor']);
        }
    }

    public function getProfessorByDepartment($departmentId, $anneeId)
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        if (!is_numeric($departmentId) || empty($anneeId)) {
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Invalid or missing parameters'
            ]);
            return;
        }
        try {
            $professors = $this->model->getProfessorsByDepartmentAndYear($departmentId, $anneeId);
            $this->response->send(200, $professors);
        } catch (Exception $e) {
            error_log("getProfessorByDepartment[departmentId=$departmentId,anneeId=$anneeId]: " . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Error loading professors',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function selectProfByYear($yearId)
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
        if (empty($yearId)) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Invalid year ID']);
            return;
        }
        try {
            $result = $this->model->getProfessorsByYear($yearId);
            $this->response->send(200, ['data' => $result['data'], 'total' => $result['total']]);
        } catch (Exception $e) {
            error_log("selectProfByYear[yearId=$yearId]: " . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Error loading professors',
                'error' => $e->getMessage()
            ]);
        }
    }



    public function getFilieres()
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        try {
            $filieres = $this->model->getAllFilieres();
            $this->response->send(200, ['data' => $filieres]);
        } catch (Exception $e) {
            error_log("getFilieres: " . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Error loading filieres',
                'error' => $e->getMessage()
            ]);
        }
    }

    

  
    public function getProfessorDetails($id)
{
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    try {
        $details = $this->model->getProfessorDetails($id);
        // Check if 'success' key exists to avoid undefined key warning
        if (isset($details['success']) && $details['success']) {
            $this->response->send(200, [
                'success' => true,
                'data' => $details['data']
            ]);
        } else {
            $this->response->send(404, [
                'success' => false,
                'message' => $details['message'] ?? 'Professeur non trouvé'
            ]);
        }
    } catch (Exception $e) {
        error_log("getProfessorDetails[id=$id]: " . $e->getMessage());
        $this->response->send(500, [
            'success' => false,
            'message' => 'Erreur serveur lors du chargement des détails',
            'error' => $e->getMessage()
        ]);
    }
}

    public function getFilteredProfessors()
{
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();

    $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
    $anneeId = filter_input(INPUT_GET, 'annee_id', FILTER_VALIDATE_INT);
    $departmentId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;

    try {
        $result = $this->model->getAllProfessorsCombined($search, $anneeId, $departmentId, $page, $limit);
        $this->response->send(200, [
            'status' => 'success',
            'data' => $result['data'],
            'total' => $result['total']
        ]);
    } catch (Exception $e) {
        error_log("getFilteredProfessors: " . $e->getMessage());
        $this->response->send(500, [
            'status' => 'error',
            'message' => 'Erreur lors du chargement des professeurs',
            'error' => $e->getMessage()
        ]);
    }
}

 ////////////////////////////////////professor details ////////////////////////////////////////

    public function getProfile()
{
    try {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();

        $userId = $_SESSION['user']['user_id'];
        $profile = $this->model->getAdminProfile($userId);

        if ($profile) {
            $this->response->send(200, [
                'status' => 'success',
                'data' => $profile
            ]);
        } else {
            $this->response->send(404, [
                'status' => 'error',
                'message' => 'Profil introuvable'
            ]);
        }
    } catch (Exception $e) {
        error_log("getProfile: " . $e->getMessage());
        $this->response->send(500, [
            'status' => 'error',
            'message' => 'Erreur serveur lors du chargement du profil',
            'error' => $e->getMessage()
        ]);
    }
}


public function changePassword()
{
    try {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();

        $data = json_decode(file_get_contents("php://input"), true);
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword || strlen($newPassword) < 6) {
            return $this->response->send(400, [
                'status' => 'error',
                'message' => 'Mot de passe invalide ou non confirmé.'
            ]);
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $userId = $_SESSION['user']['user_id'];

        if ($this->model->updatePassword($userId, $hashedPassword)) {
            $this->response->send(200, [
                'status' => 'success',
                'message' => 'Mot de passe mis à jour.'
            ]);
        } else {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du mot de passe.'
            ]);
        }
    } catch (Exception $e) {
        error_log("changePassword: " . $e->getMessage());
        $this->response->send(500, [
            'status' => 'error',
            'message' => 'Erreur serveur.',
            'error' => $e->getMessage()
        ]);
    }
}


  ///////////////////////////superadmin////////////////////////////////////////////////////

    public function getAllAdmins() {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        try {
            $admins = $this->model->getAllAdmins();
            $this->response->send(200, [
                'success' => true,
                'count' => count($admins),
                'admins' => $admins
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'success' => false,
                'message' => 'Erreur lors de la récupération des administrateurs: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Add a new admin (superadmin only)
     */
    public function addAdmin()
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifySuperAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            error_log('Données JSON invalides reçues dans addAdmin');
            $this->response->send(400, [
                'success' => false,
                'message' => 'Données JSON invalides'
            ]);
            return;
        }

        $required_fields = ['username', 'password', 'email', 'nom', 'prenom', 'date_naissance', 'cin'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                error_log("Champ manquant ou vide: $field");
                $this->response->send(400, [
                    'success' => false,
                    'message' => "Le champ $field est requis"
                ]);
                return;
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            error_log('Format d\'email invalide: ' . $data['email']);
            $this->response->send(400, [
                'success' => false,
                'message' => 'Format d\'email invalide'
            ]);
            return;
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_naissance'])) {
            error_log('Format de date de naissance invalide: ' . $data['date_naissance']);
            $this->response->send(400, [
                'success' => false,
                'message' => 'Format de date de naissance invalide (AAAA-MM-JJ requis)'
            ]);
            return;
        }

        try {
            $result = $this->model->addAdmin(
                $data['username'],
                $data['password'],
                $data['email'],
                $data['nom'],
                $data['prenom'],
                $data['telephone'] ?? '',
                $data['adresse'] ?? '',
                $data['date_naissance'],
                $data['nationalite'] ?? '',
                $data['cin'],
                $data['fonction'] ?? 'Administrateur'
            );
            $this->response->send($result['status'] === 'success' ? 201 : 400, [
                'success' => $result['status'] === 'success',
                'message' => $result['message'],
                'user_id' => $result['user_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log('Erreur dans AdminController::addAdmin: ' . $e->getMessage());
            $this->response->send(500, [
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ]);
        }
    }
    /**
     * Delete an admin by setting actuel = 0 (superadmin only)
     */
   public function deleteAdmin() {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifySuperAdmin();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['user_id']) || empty($data['motif'])) {
        $this->response->send(400, [
            'success' => false,
            'message' => 'user_id et motif requis'
        ]);
        return;
    }

    try {
        $result = $this->model->deleteAdmin($data['user_id'], $data['motif']);
        $this->response->send($result['status'] === 'success' ? 200 : 400, [
            'success' => $result['status'] === 'success',
            'message' => $result['message']
        ]);
    } catch (Exception $e) {
        $this->response->send(500, [
            'success' => false,
            'message' => 'Erreur serveur: ' . $e->getMessage()
        ]);
    }
}

    /**
     * Update an admin's details (superadmin only)
     */
    public function updateAdmin() {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifySuperAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        $required_fields = ['user_id', 'username', 'email', 'nom', 'prenom', 'date_naissance', 'cin'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $this->response->send(400, [
                    'success' => false,
                    'message' => "Le champ $field est requis"
                ]);
                return;
            }
        }

        try {
            $result = $this->model->updateAdmin(
                $data['user_id'],
                $data['username'],
                $data['password'] ?? null,
                $data['email'],
                $data['nom'],
                $data['prenom'],
                $data['telephone'] ?? '',
                $data['adresse'] ?? '',
                $data['date_naissance'],
                $data['nationalite'] ?? '',
                $data['cin'],
                $data['fonction'] ?? 'Administrateur'
            );
            $this->response->send($result['status'] === 'success' ? 200 : 400, [
                'success' => $result['status'] === 'success',
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ]);
        }
    }
    public function importStudents()
    {
        // Check if a file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->sendResponse(400, ['message' => 'No file uploaded']);
            return;
        }

        $file = $_FILES['csv_file'];
        
        // Validate file type
        $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            $this->sendResponse(400, ['message' => 'Invalid file type. Please upload a CSV file']);
            return;
        }

        // Validate file size (e.g., max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $maxSize) {
            $this->sendResponse(400, ['message' => 'File size exceeds 5MB limit']);
            return;
        }

        try {
            $result = $this->model->importStudentsFromCSV($file['tmp_name']);
            
            // Prepare response
            $response = [
                'message' => sprintf(
                    'Import completed: %d students imported successfully',
                    $result['success_count']
                ),
                'success_count' => $result['success_count'],
                'errors' => $result['errors']
            ];

            $status = $result['success_count'] > 0 ? 200 : 400;
            if (!empty($result['errors'])) {
                $status = 207; // Partial success
            }

            $this->sendResponse($status, $response);
        } catch (Exception $e) {
            error_log("Student import error: " . $e->getMessage());
            $this->sendResponse(500, ['message' => 'Server error during student import: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle professor CSV import
     * @return void
     */
    public function importProfessors()
    {
        // Check if a file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->sendResponse(400, ['message' => 'No file uploaded']);
            return;
        }

        $file = $_FILES['csv_file'];
        
        // Validate file type
        $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            $this->sendResponse(400, ['message' => 'Invalid file type. Please upload a CSV file']);
            return;
        }

        // Validate file size (e.g., max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $maxSize) {
            $this->sendResponse(400, ['message' => 'File size exceeds 5MB limit']);
            return;
        }

        try {
            $result = $this->model->importProfessorsFromCSV($file['tmp_name']);
            
            // Prepare response
            $response = [
                'message' => sprintf(
                    'Import completed: %d professors imported successfully',
                    $result['success_count']
                ),
                'success_count' => $result['success_count'],
                'errors' => $result['errors']
            ];

            $status = $result['success_count'] > 0 ? 200 : 400;
            if (!empty($result['errors'])) {
                $status = 207; // Partial success
            }

            $this->sendResponse($status, $response);
        } catch (Exception $e) {
            error_log("Professor import error: " . $e->getMessage());
            $this->sendResponse(500, ['message' => 'Server error during professor import: ' . $e->getMessage()]);
        }
    }

    /**
     * Send JSON response
     * @param int $status HTTP status code
     * @param array $data Response data
     * @return void
     */
    private function sendResponse($status, $data)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

}

