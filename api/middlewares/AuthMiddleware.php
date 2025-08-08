<?php
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware {
    private $response;

    public function __construct() {
        $this->response = new Response();
        // Démarrer la session uniquement si elle n'existe pas déjà
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function verifySession() {
        if (!isset($_SESSION['user'])) {
            $this->response->send(401, ['status' => 'error', 'message' => 'Non authentifié']);
            exit;
        }
    }
    public function verifyApiKey() {
        $headers = getallheaders();
        $key = $headers['X-API-KEY'] ?? '';

        if ($key !== API_KEY) {
            $this->response->error(401, 'Clé API invalide');
            exit;
        }
    }
}
