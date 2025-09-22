<?php
//session_start();
require_once __DIR__.'/config/constants.php';
require_once __DIR__.'/utils/Response.php';

// Mode debug - IMPORTANT: Set display_errors to 0 in production
ini_set('display_errors', 0); // Change to 0 for production to prevent HTML output
error_reporting(E_ALL); // Keep E_ALL for logging all errors

// Set a custom error handler to always return JSON for API errors
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // This will catch warnings and notices as well
    if (!(error_reporting() & $errno)) {
        // Error reporting is off for this error type
        return false;
    }
    // Clear any previous output that might have been buffered
    if (ob_get_length()) {
        ob_clean();
    }
    // Send a JSON error response
    header("Content-Type: application/json; charset=UTF-8");
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Une erreur interne du serveur est survenue.',
        'details' => "Error: [$errno] $errstr in $errfile on line $errline" // For debugging, remove in production
    ]);
    exit; // Terminate script execution
});


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

// Start output buffering to catch any unexpected output
ob_start();

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

        case $request === 'auth/logout' && $_SERVER['REQUEST_METHOD'] === 'POST':
            require_once __DIR__.'/controllers/AuthController.php';
            (new AuthController())->logout();
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
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getDepartmentNotes($matches[1]); 
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

        // NOUVELLES ROUTES POUR LES FILTRES (inchangé, mais pertinent pour le contexte)
        case $request === 'professor/academic-years' && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor();
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getAcademicYears();
            break;

        case $request === 'professor/semesters' && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor();
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getSemesters();
            break;
        
        // NOUVELLE ROUTE : Obtenir les semestres spécifiques au professeur
        case $request === 'professor/my-semesters' && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor();
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getProfessorSemesters();
            break;

        case $request === 'professor/etapes' && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor();
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getEtapes();
            break;

        case $request === 'professor/filieres' && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor();
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getFilieres();
            break;

        // 🔐 Professeur - Obtenir les semestres pour une filière spécifique
        case preg_match('/^professor\/semesters-for-field\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); 
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getSemestersForField($matches[1]);
            break;

        // 🔐 Professeur - Obtenir les étapes pour une filière spécifique
        case preg_match('/^professor\/etapes-for-field\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); 
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getEtapesForField($matches[1]);
            break;

        // 🔐 Professeur - Obtenir les semestres pour un département spécifique
        case preg_match('/^professor\/semesters-for-department\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); 
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getSemestersForDepartment($matches[1]);
            break;

        // 🔐 Professeur - Obtenir les étapes pour un département spécifique
        case preg_match('/^professor\/etapes-for-department\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            require_once __DIR__.'/middlewares/ProfessorMiddleware.php';
            (new ProfessorMiddleware())->verifyProfessor(); 
            require_once __DIR__.'/controllers/ProfessorController.php';
            (new ProfessorController())->getEtapesForDepartment($matches[1]);
            break;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        // AJOUTEZ CE BLOC À LA PLACE (par exemple, après la route /professor/module-notes/{id})

        case preg_match('/^professor\/export\/module\/(\d+)\/field\/(\d+)$/', $request, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
            // $matches[0] contiendra l'URL complète
            // $matches[1] contiendra le premier (\d+), soit l'ID du module
            // $matches[2] contiendra le second (\d+), soit l'ID de la filière
            
            $moduleId = (int)$matches[1];
            $fieldId = (int)$matches[2];
            
            // On doit instancier le contrôleur ici, car il n'est pas défini globalement
            require_once __DIR__.'/controllers/ProfessorController.php';
            $controller = new ProfessorController();
            
            // Appeler la fonction du contrôleur avec les bons paramètres
            $controller->exportModuleNotes($moduleId, $fieldId);
            break;

        //////////////////////////////////////////////////////////////////////////////////////

        case 'getAdminProfile':
            require_once __DIR__ . '/controllers/AdminController3.php';
            $adminController = new AdminController3();
            $adminController->getProfile();
            break;

        // Route non reconnue
        default:
            (new Response())->send(404, ['error' => 'Endpoint not found', 'request' => $request]);
    }

} catch (Exception $e) {
    // This catch block handles exceptions, not parse errors or warnings
    // The custom error handler above will handle those.
    error_log("API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    // Clear any previous output that might have been buffered
    if (ob_get_length()) {
        ob_clean();
    }
    (new Response())->send(500, [
        'status' => 'error',
        'message' => 'Une erreur inattendue est survenue.',
        'details' => $e->getMessage() // Pour le débogage, à retirer en production
    ]);
} finally {
    // Ensure any buffered output is discarded if an error occurred,
    // otherwise, flush the buffer (if no error was handled by custom handler)
    if (ob_get_length() > 0 && !headers_sent()) {
        ob_end_clean(); // Discard buffer if headers haven't been sent (meaning an error occurred)
    } else if (ob_get_length() > 0) {
        ob_end_flush(); // Flush if headers were already sent (meaning a successful response was built)
    }
}
