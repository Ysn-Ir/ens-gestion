<?php
session_start();
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/StudentController.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");
header("Access-Control-Allow-Credentials: true"); // Allow cookies
header("Content-Type: application/json; charset=UTF-8");

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit;
}
// Sanitize input parameters
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$etapeId = filter_input(INPUT_GET, 'etape_id', FILTER_VALIDATE_INT);

try {
    $controller = new StudentController();

    switch ($action) {
        case 'me':
            $controller->getCurrentStudentInfo();
            break;

        case 'getYearStudied':
            if (!$id) throw new Exception('Missing student ID', 400);
            $controller->getYearStudied($id);
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

        case 'getAllNotesByUserIdAndEtapeAndSemester':
            $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
            $etapeId = filter_input(INPUT_GET, 'etape_id', FILTER_VALIDATE_INT);
            $semesterId = filter_input(INPUT_GET, 'semester_id', FILTER_VALIDATE_INT);
            if (!$userId || !$etapeId || !$semesterId) {
                throw new Exception('Missing user ID, etape ID or semester ID', 400);
            }
            $controller->getAllNotesByUserIdAndEtapeAndSemester($userId, $etapeId, $semesterId);
            break;

        case 'getAnnualNoteAndRanking':
            $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
            $anneeId = filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_SPECIAL_CHARS);
            if (!$userId || !$anneeId) {
                throw new Exception('Missing user ID or annee ID', 400);
            }
            $controller->getAnnualNoteAndRanking($userId, $anneeId);
            break;

        case 'getEtapes':
            $controller->getAllEtapes();
            break;

        case 'getAnnees':
            $controller->getAllAnnees();
            break;

        case 'changePassword':
            $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $newPassword = filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_SPECIAL_CHARS);
            if (!$userId || !$newPassword) {
                throw new Exception('Missing user ID or new password', 400);
            }
            $controller->changePassword($userId,$newPassword);
            break;

        case 'getCycleOfStudent':
            if (!$id) throw new Exception('Missing student ID', 400);
            $controller->getCycleOfStudent($id);
            break;

        case 'getSemestresOfStudentByCycle':
            if (!$id) throw new Exception('Missing student ID', 400);
            $cycleId = filter_input(INPUT_GET, 'cycle_id', FILTER_VALIDATE_INT);
            if (!$cycleId) throw new Exception('Missing cycle ID', 400);
            $controller->getSemestresOfStudentByCycle($id, $cycleId);
            break;

        case 'getNoteOfStudentBySemester':
            if (!$id) throw new Exception('Missing student ID', 400);
            $semesterId = filter_input(INPUT_GET, 'semester_id', FILTER_VALIDATE_INT);
            if (!$semesterId) throw new Exception('Missing semester ID', 400);
            $controller->getNoteOfStudentBysemestre($id, $semesterId);
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
?>