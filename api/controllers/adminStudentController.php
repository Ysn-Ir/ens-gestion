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
