<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/SectionController.php';
require_once __DIR__ . '/utils/Response.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");

// Gérer la requête OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$section_id = filter_input(INPUT_GET, 'section_id', FILTER_VALIDATE_INT);
$group_id = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);
$filiereId = filter_input(INPUT_GET, 'filiereId', FILTER_VALIDATE_INT);

$response = new Response();

try {
    $controller = new SectionController();

    switch ($action) {
        case 'getSections':
            $controller->getSections($filiereId);
            break;

        case 'addSection':
            $controller->addSection();
            break;

        case 'updateSection':
            if (!$section_id) throw new Exception('Missing section ID', 400);
            $controller->updateSection($section_id);
            break;

        case 'deleteSection':
            if (!$section_id) throw new Exception('Missing section ID', 400);
            $controller->deleteSection($section_id);
            break;

        case 'getGroups':
            $controller->getGroups($filiereId);
            break;

        case 'addGroup':
            $controller->addGroup();
            break;

        case 'updateGroup':
            if (!$group_id) throw new Exception('Missing group ID', 400);
            $controller->updateGroup($group_id);
            break;

        case 'deleteGroup':
            if (!$group_id) throw new Exception('Missing group ID', 400);
            $controller->deleteGroup($group_id);
            break;

        case 'getOptions':
            $controller->getOptions();
            break;

        default:
            throw new Exception('Action non reconnue', 404);
    }
} catch (Exception $e) {
    $code = ($e->getCode() >= 100 && $e->getCode() < 600) ? $e->getCode() : 500;
    $response->send($code, [
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
