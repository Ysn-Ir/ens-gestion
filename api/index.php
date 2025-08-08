<?php
require_once __DIR__.'/config/constants.php';
require_once __DIR__.'/utils/Response.php';

// Mode debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");
header("Content-Type: application/json; charset=UTF-8");

// Gestion des requêtes OPTIONS (pré-vol CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit;
}

// Récupération de la requête
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = trim($request_uri, '/');

// Debug
error_log("[" . date('Y-m-d H:i:s') . "] Request: " . $request);

// Supprimer le préfixe du chemin (ex: "ens-gestion/api/")
$base_path = 'ens-gestion/api/';
if (strpos($request, $base_path) === 0) {
    $request = substr($request, strlen($base_path));
}

// Routeur principal
try {
    switch (true) {
        // 🧪 Endpoint de test
        case $request === 'test':
            (new Response())->send(200, ['status' => 'API operational']);
            break;

        // 🔐 Authentification (pas besoin de clé API)
        case $request === 'auth/login' && $_SERVER['REQUEST_METHOD'] === 'POST':
            require_once __DIR__.'/controllers/AuthController.php';
            (new AuthController())->login();
            break;

       case 'auth/logout':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             require_once __DIR__.'/controllers/AuthController.php';
            (new AuthController())->logout();
        } else {
            $response->send(405, ['status' => 'error', 'message' => 'Method Not Allowed']);
        }
        break;

        case $request === 'auth/me' && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/controllers/AuthController.php';
            (new AuthController())->me();
            break;

        //////////////////////////////////////////////////////////////////////////////////////

        // 🔐 Professeur - Obtenir le profil du professeur
        case $request === 'professor/profile' && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie si l'utilisateur est un prof ou chef
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getProfessorProfile();
            break;

        // 🔐 Professeur - Obtenir les éléments enseignés
        case $request === 'professor/elements' && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor();
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getTeachingElements();
            break;

        // 🔐 Professeur - Obtenir les notes d'un élément spécifique
        case preg_match('/^professor\/notes\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor();
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getElementNotes($matches[1]);
            break;

        // 🔐 Professeur - Mettre à jour une note
        case $request === 'professor/notes' && $_SERVER['REQUEST_METHOD'] === 'PUT':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor();
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->updateNote();
            break;
        
        // 🔐 Professeur - Mettre à jour le statut de la période de saisie des notes
        case $request === 'professor/grade-period' && $_SERVER['REQUEST_METHOD'] === 'PUT':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie si l'utilisateur est un prof ou chef
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->updateGradePeriodStatus();
            break;

        // 🔐 Professeur - Obtenir le statut de la période de saisie des notes
        case $request === 'professor/grade-period-status' && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie si l'utilisateur est un prof ou chef
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getGradePeriodStatus();
            break;

        // NOUVELLE ROUTE : 🔐 Professeur - Mettre à jour le statut de la période de saisie des notes de rattrapage
        case $request === 'professor/resit-grade-period' && $_SERVER['REQUEST_METHOD'] === 'PUT':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie si l'utilisateur est un prof ou chef
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->updateResitGradePeriodStatus();
            break;

        // NOUVELLE ROUTE : 🔐 Professeur - Obtenir le statut de la période de saisie des notes de rattrapage
        case $request === 'professor/resit-grade-period-status' && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie si l'utilisateur est un prof ou chef
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getResitGradePeriodStatus();
            break;

        // 🔐 Professeur - Obtenir les notes d'une filière (pour chef de filière)
        case preg_match('/^professor\/field-notes\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); // Vérifie si l'utilisateur est un prof ou chef
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getFieldNotes($matches[1]);
            break;

        // 🔐 Professeur - Obtenir les modules d'une filière
        case preg_match('/^professor\/modules-by-field\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); 
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getModulesByField($matches[1]);
            break;

        // 🔐 Professeur - Obtenir les notes d'un module spécifique
        case preg_match('/^professor\/module-notes\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); 
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getModuleNotes($matches[1]);
            break;
            
        // 🔐 Professeur - Obtenir les notes d'un département (pour chef de département)
        case preg_match('/^professor\/department-notes\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor();// Vérifie si l'utilisateur est un prof ou chef
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorModel())->getDepartmentNotes($matches[1]);
            break;

        // 🔐 Professeur - Obtenir les modules d'un département
        case preg_match('/^professor\/modules-by-department\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); 
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getModulesByDepartment($matches[1]);
            break;

        // NOUVELLE ROUTE : 🔐 Professeur - Obtenir les filières d'un département
        case preg_match('/^professor\/fields-by-department\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); 
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getFieldsByDepartment($matches[1]);
            break;
        // Route non reconnue
        default:
            (new Response())->send(404, ['error' => 'Endpoint not found']);
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    (new Response())->send(500, [
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

