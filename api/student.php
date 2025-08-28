<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/StudentController.php';

header("Content-Type: application/json");

// Sanitize input parameters
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$etapeId = filter_input(INPUT_GET, 'etape_id', FILTER_VALIDATE_INT);

try {
    $controller = new StudentController();

    switch ($action) {
        case 'me':
    $controller->getCurrentStudentInfo();
    break;

        case 'getStudent':
            if (!$id) throw new Exception('Missing student ID', 400);
            $controller->getStudent($id);
            break;

        case 'getStudentInfo':
            if (!$id || !$etapeId) throw new Exception('Missing student ID or etape ID', 400);
            $controller->getStudentInfo($id, $etapeId);
            break;

        case 'getDiplomas':
            if (!$id) throw new Exception('Missing student ID', 400);
            $controller->getDiplomas($id);
            break;

        case 'getNoteModules':
            if (!$id) throw new Exception('Missing student ID', 400);
            $controller->getNoteModules($id);
            break;

        case 'getAllNotes':
            if (!$id) throw new Exception('Missing student ID', 400);
            $controller->getAllNotes($id);
            break;

        default:
            throw new Exception('Action non reconnue', 404);
    }

} catch (Exception $e) {
    $code = $e->getCode();
http_response_code((is_numeric($code) && $code >= 100 && $code < 600) ? (int)$code : 500);

    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'error',
        'code' => $e->getCode()
    ]);
}
