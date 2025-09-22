<?php
require_once __DIR__ . '/../utils/Response.php';

class AdminMiddleware {
    private $response;

    public function __construct() {
        $this->response = new Response();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function verifyAdmin() {
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
            $this->response->error(403, 'Admin privileges required');
            exit;
        }
    }

    // Verify if user is superadmin only
    public function verifySuperAdmin() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
            $this->response->error(403, 'Superadmin privileges required');
            exit;
        }
    }
}
