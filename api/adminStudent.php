<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/AdminStudentController.php';
require_once __DIR__ . '/controllers/NoteController.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400"); // Cache preflight response for 24 hours

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $controller = new AdminController();
    $noteController = new NoteController();

    // Get request method and action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

    // Input validation for common parameters
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

    // Route based on action
    switch ($action) {
        // Academic Structure Routes
        case 'getCycles':
            validateMethod($method, 'GET');
            $controller->getAllCycles();
            break;

        case 'getFilieres':
            validateMethod($method, 'GET');
            $controller->getAllFilieres();
            break;

        case 'getFilteredFiliers':
            validateMethod($method, 'GET');
            if ($field_id) {
                $controller->getFilteredFilieres();
            } else {
                $controller->getAllFilieres();
            }
            break;

        case 'getSections':
            validateMethod($method, 'GET');
            if ($field_id) {
                $controller->getSectionsByFiliere($field_id);
            } else {
                $controller->getAllSections();
            }
            break;

        case 'getGroupesBySection':
            validateMethod($method, 'GET');
            if ($section_id) {
                $controller->getGroupesBySection($section_id);
            } else {
                $controller->getAllGroups();
            }
            break;

        case 'getGroupesByFiliere':
            validateMethod($method, 'GET');
            validateRequiredParam($field_id, 'field_id');
            $controller->getGroupesByFiliere($field_id);
            break;

        case 'getAllyears':
            validateMethod($method, 'GET');
            $controller->getAllYears();
            break;

        case 'getAllDepartments':
            validateMethod($method, 'GET');
            $controller->getAllDepartments();
            break;

        case 'getAllSections':
            validateMethod($method, 'GET');
            $controller->getAllSections();
            break;

        case 'getAllGroupes':
            validateMethod($method, 'GET');
            $controller->getAllGroups();
            break;

        case 'getAllSemesters':
            validateMethod($method, 'GET');
            $controller->getAllSemestres();
            break;

        case 'getAllEtapes':
            validateMethod($method, 'GET');
            $controller->getAllEtapes();
            break;

        case 'getAllModules':
            validateMethod($method, 'GET');
            $controller->getAllModules();
            break;

        case 'getAllElements':
            validateMethod($method, 'GET');
            $controller->getAllElements();
            break;

        // Student Routes
        case 'getStudents':
            validateMethod($method, 'GET');
            $controller->getFilteredStudents();
            break;

        case 'getStudentDetails':
            validateMethod($method, 'GET');
            validateRequiredParam($user_id, 'user_id');
            $controller->getStudentDetail($user_id);
            break;

        case 'createStudent':
            validateMethod($method, 'POST');
            $controller->createStudent();
            break;

        case 'updateStudent':
            validateMethod($method, 'PUT');
            validateRequiredParam($id, 'id');
            $controller->updateStudent($id);
            break;

        case 'deleteStudent':
            validateMethod($method, 'DELETE');
            validateRequiredParam($id, 'id');
            $controller->deleteStudent($id);
            break;

        case 'getStudentModules':
            validateMethod($method, 'GET');
            $controller->getAllModule();
            break;

        case 'assignModules':
            validateMethod($method, 'POST');
            validateRequiredParam($id, 'id');
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($input['module_ids'])) {
                throw new Exception('Invalid module data format', 400);
            }
            $controller->assignModule($id, $input['module_ids']);
            break;

        case 'importStudents':
            validateMethod($method, 'POST');
            $controller->importStudents();
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
