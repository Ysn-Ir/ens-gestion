<?php
require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthController {
    private $model;
    private $response;

    public function __construct() {
        $this->model = new AuthModel();
        $this->response = new Response();
    }

    public function login() {
        session_start();

        // Vérifier le Content-Type
        if (!isset($_SERVER['CONTENT_TYPE']) || stripos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
            $this->response->send(415, ['status' => 'error', 'message' => 'Unsupported Media Type: JSON expected']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Invalid JSON format']);
            return;
        }

        if (!isset($data['username'], $data['password'])) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Username and password are required']);
            return;
        }

        $username = trim($data['username']);
        $password = $data['password'];

        if (empty($username) || empty($password)) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Username and password cannot be empty']);
            return;
        }

        $user = $this->model->authenticate($username, $password);

        if (!$user) {
            sleep(1);
            $this->response->send(401, ['status' => 'error', 'message' => 'Invalid credentials']);
            return;
        }

        // S'assurer que le rôle est défini
        if (!isset($user['role'])) {
            $user['role'] = 'etudiant';
        }

        // Stocker les infos en session
        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];

        $this->response->send(200, [
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $_SESSION['user']
        ]);
    }

    public function logout() {
        session_start();
        session_unset();
        session_destroy();

        $this->response->send(200, [
            'status' => 'success',
            'message' => 'Logout successful'
        ]);
    }

    public function me() {
        session_start();

        if (!isset($_SESSION['user'])) {
            $this->response->send(401, ['status' => 'error', 'message' => 'Not authenticated']);
            return;
        }

        $this->response->send(200, [
            'status' => 'success',
            'user' => $_SESSION['user']
        ]);
    }
}
