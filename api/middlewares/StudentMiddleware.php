<?php
require_once __DIR__ . '/../utils/Response.php';

class StudentMiddleware {
    private $response;

    public function __construct() {
        $this->response = new Response();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function verifyStudent() {
        if (!isset($_SESSION['user'])) {
            $this->response->send(401, ['status' => 'error', 'message' => 'Non authentifié']);
            exit;
        }

        if ($_SESSION['user']['role'] !== 'student' && $_SESSION['user']['role'] !== 'student') {
            $this->response->send(403, ['status' => 'error', 'message' => 'Privilèges étudiant requis']);
            exit;
        }

        // L'utilisateur est bien un étudiant
        return true;
    }
}
