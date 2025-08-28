<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/settingController.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $controller = new settingController();

    $method = $_SERVER['REQUEST_METHOD'];
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

    switch ($action) {
        case 'getAllSettings':
            validateMethod($method, 'GET');
            $controller->getAllSettings();
            break;

        case 'addSetting':
            validateMethod($method, 'POST');
            $controller->addSetting();
            break;

        case 'updateSetting':
            validateMethod($method, 'PUT');
            $controller->updateSetting();
            break;

        case 'deleteSetting':
            validateMethod($method, 'DELETE');
            $controller->deleteSetting();
            break;

        case 'getAllConstraints':
            validateMethod($method, 'GET');
            $controller->getAllConstraints();
            break;

        case 'addConstraint':
            validateMethod($method, 'POST');
            $controller->addConstraint();
            break;

        case 'updateConstraint':
            validateMethod($method, 'PUT');
            $controller->updateConstraint();
            break;

        case 'deleteConstraint':
            validateMethod($method, 'DELETE');
            $controller->deleteConstraint();
            break;

        default:
            throw new Exception('Action non reconnue', 404);
    }
} catch (Exception $e) {
    $response = new Response();
    $response->sendError($e->getMessage(), $e->getCode() ?: 500);
}

function validateMethod($method, $expected) {
    if (is_array($expected)) {
        if (!in_array($method, $expected)) {
            throw new Exception('Method Not Allowed: Expected ' . implode(' or ', $expected), 405);
        }
    } elseif ($method !== $expected) {
        throw new Exception('Method Not Allowed: Expected ' . $expected, 405);
    }
}

function validateRequiredParam($param, $paramName) {
    if ($param === false || $param === null) {
        throw new Exception("Paramètre \"$paramName\" requis", 400);
    }
}