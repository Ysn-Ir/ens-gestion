<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/AdminController.php';
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

    // Route based on action
    switch ($action) {
        // Note Routes
        case 'generateEmptyNoteRows':
            validateMethod($method, ['GET', 'POST']);
            $noteController->generateEmptyNoteRows();
            break;

        case 'calculateSemesterNotes':
            validateMethod($method, ['GET', 'POST']);
            $noteController->calculateAllFinalNotes();
            break;

        case 'calculateYearNotes':
            validateMethod($method, ['GET', 'POST']);
            $noteController->calculateYearFinalNotes();
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
