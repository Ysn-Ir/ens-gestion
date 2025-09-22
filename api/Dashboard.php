<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/dashboardController.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Validate the HTTP method for the requested action.
 *
 * @param string $method The actual HTTP method.
 * @param string $expectedMethod The expected HTTP method.
 * @throws Exception If the method is not allowed.
 */
function validateMethod($method, $expectedMethod) {
    if ($method !== $expectedMethod) {
        throw new Exception("Method not allowed. Expected $expectedMethod, got $method.", 405);
    }
}

try {
    $controller = new DashboardController();

    $method = $_SERVER['REQUEST_METHOD'];
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);

    switch ($action) {
        case 'getAllSettings':
            validateMethod($method, 'GET');
            $controller->getAllSettings();
            break;

        case 'getDashboardStats':
            validateMethod($method, 'GET');
            $controller->getDashboardStats();
            break;

        default:
            throw new Exception("Invalid action: $action", 400);
    }
} catch (Exception $e) {
    $response = new Response();
    $response->sendError($e->getMessage(), $e->getCode() ?: 500);
}