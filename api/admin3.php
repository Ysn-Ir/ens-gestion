<?php
require_once __DIR__ . '/config/constants.php';

require_once __DIR__ . '/controllers/AdminController3.php';


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $controller = new AdminController3();

    // Get request method and action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    $field_id = filter_input(INPUT_GET, 'field_id', FILTER_VALIDATE_INT);
    $section_id = filter_input(INPUT_GET, 'section_id', FILTER_VALIDATE_INT);
    $group_id = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);
    $etape_id = filter_input(INPUT_GET, 'etape_id', FILTER_VALIDATE_INT);
    $semestre_id = filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
    $annee_id = filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
    $department_id = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;
    $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);


    // Récupération et nettoyage des paramètres
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
    $fieldId = filter_input(INPUT_GET, 'field_id', FILTER_VALIDATE_INT);
    $sectionId = filter_input(INPUT_GET, 'section_id', FILTER_VALIDATE_INT);
    $groupId = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);
    $depart_id=filter_input(INPUT_GET, 'depart_id', FILTER_VALIDATE_INT);
    $prof_id=filter_input(INPUT_GET, 'prof_id', FILTER_VALIDATE_INT);
    $cycle_id=filter_input(INPUT_GET, 'cycle_id', FILTER_VALIDATE_INT);
    $nomFili = filter_input(INPUT_GET, 'nomFili', FILTER_SANITIZE_STRING);
    $anneeAccreditation=filter_input(INPUT_GET, 'annee', FILTER_SANITIZE_STRING);


  
    





    // Route based on action
    switch ($action) {

        case 'getAllyears':
            validateMethod($method, 'GET');
            $controller->getAllYears();
            break;

        case 'getAllDepartments':
            validateMethod($method, 'GET');
            $controller->getAllDepartments();
            break;

        case 'getFilieres':
            validateMethod($method, 'GET');
            $controller->getAllFilieres();
            break;


        // Professor Routes
        case 'getProfessors':
            validateMethod($method, 'GET');
            $controller->getAllProfessors();
            break;

        case 'selectProfByYear':
            validateMethod($method, 'GET');
            validateRequiredParam($annee_id, 'annee_id');
            $controller->selectProfByYear($annee_id);
            break;

        case 'createProfessor':
            validateMethod($method, 'POST');
            $controller->createProfessor();
            break;

        case 'updateProfessor':
            validateMethod($method, 'PUT');
            validateRequiredParam($id, 'id');
            $controller->updateProfessor($id);
            break;

        case 'deleteProfessor':
            validateMethod($method, 'DELETE');
            validateRequiredParam($id, 'id');
            $controller->deleteProfessor($id);
            break;

        case 'getProfessorByDepartmentAndYear':
            validateMethod($method, 'GET');
            validateRequiredParam($department_id, 'department_id');
            validateRequiredParam($annee_id, 'annee_id');
            $controller->getProfessorByDepartment($department_id, $annee_id);
            break;

        case 'getProfessorDetails':
            validateMethod($method, 'GET');
            validateRequiredParam($id, 'id');
            $controller->getProfessorDetails($id);
            break;

        case 'getFilteredProfessors':
            validateMethod($method, 'GET');
            $controller->getFilteredProfessors();
            break;

        case 'importProfessors':
    validateMethod($method, 'POST');
    $controller->importProfessors();


        // Profile Routes
        case 'get_profile':
            validateMethod($method, 'GET');
            $controller->getProfile();
            break;

        case 'change_password':
            validateMethod($method, 'POST');
            $controller->changePassword();
            break;

        // Superadmin Routes
        case 'getAdmins':
            validateMethod($method, 'GET');
            $controller->getAllAdmins();
            break;

        case 'addAdmin':
            validateMethod($method, 'POST');
            $controller->addAdmin();
            break;

        case 'deleteAdmin':
            validateMethod($method, 'POST');
            $controller->deleteAdmin();
            break;

        case 'updateAdmin':
            validateMethod($method, 'POST');
            $controller->updateAdmin();
            break;

        default:
            throw new Exception('Action non reconnue', 404);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode() ?: 500
    ]);
}

/**
 * Validate HTTP method
 * @param string $method Actual method
 * @param string|array $expected Expected method(s)
 * @throws Exception if method does not match
 */
function validateMethod($method, $expected) {
    if (is_array($expected)) {
        if (!in_array($method, $expected)) {
            throw new Exception('Method Not Allowed: Expected ' . implode(' or ', $expected), 405);
        }
    } elseif ($method !== $expected) {
        throw new Exception('Method Not Allowed: Expected ' . $expected, 405);
    }
}

/**
 * Validate required parameter
 * @param mixed $param Parameter value
 * @param string $paramName Parameter name
 * @throws Exception if parameter is missing or invalid
 */
function validateRequiredParam($param, $paramName) {
    if ($param === false || $param === null) {
        throw new Exception("Paramètre \"$paramName\" requis", 400);
    }
}
?>