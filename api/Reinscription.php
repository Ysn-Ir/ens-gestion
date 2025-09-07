<?php
// --- Point d'entrée pour la ressource "reinscription" ---

// ===================================================================
// DÉBUT DE LA CORRECTION : Garantir une sortie JSON propre
// ===================================================================

// Désactive l'affichage des erreurs directement dans la réponse.
// C'est crucial pour ne pas corrompre la sortie JSON.
// Les erreurs seront toujours enregistrées dans les logs du serveur si configuré.
error_reporting(0);
ini_set('display_errors', 0);

// ===================================================================
// FIN DE LA CORRECTION
// ========================================== =========================


// Headers pour autoriser les requêtes cross-origin (CORS) et définir le type de contenu
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// ... (le reste des headers est inchangé)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200 );
    exit();
}

// ... (le reste du fichier est inchangé)
require_once __DIR__ . '/controllers/ReinscriptionController.php';
require_once __DIR__ . '/models/ReinscriptionModel.php';
require_once __DIR__ . '/utils/Database.php';
require_once __DIR__ . '/utils/Response.php';

try {
    $controller = new ReinscriptionController();
    $controller->handleRequest();
} catch (Exception $e) {
    // Cette partie est maintenant encore plus importante
    Response::sendError("Une erreur interne est survenue: " . $e->getMessage(), 500);
    error_log("Erreur API reinscription.php: " . $e->getMessage());
}
