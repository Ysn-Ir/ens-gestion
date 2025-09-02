<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/AdminMiddleware.php';
require_once __DIR__ . '/../models/SectionModel.php';
require_once __DIR__ . '/../utils/Response.php';

class SectionController {
    private $model;
    private $authMiddleware;
    private $adminMiddleware;
    private $response;

    public function __construct() {
        $this->model = new SectionGroupModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->adminMiddleware = new AdminMiddleware();
        $this->response = new Response();
    }

    public function getSections() {
                $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->response->send(403, ['status' => 'error', 'message' => 'Accès refusé']);
            return;
        }

        try {
            $sections = $this->model->getSections();
            $this->response->send(200, [
                'status' => 'success',
                'data' => ['sections' => $sections]
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des sections: ' . $e->getMessage()
            ]);
        }
    }

    public function addSection() {
                $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->response->send(403, ['status' => 'error', 'message' => 'Accès refusé']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Données JSON invalides']);
            return;
        }

        $required_fields = ['nom', 'etape_id'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->response->send(400, ['status' => 'error', 'message' => "Le champ $field est requis"]);
                return;
            }
        }

        try {
            $result = $this->model->addSection(
                $data['nom'],
                $data['field_id'] ?? null,
                $data['etape_id']
            );
            $this->response->send($result['status'] === 'success' ? 201 : 400, [
                'status' => $result['status'],
                'message' => $result['message'],
                'section_id' => $result['section_id'] ?? null
            ]);
        } catch (Exception $e) {
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateSection($section_id) {
                $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->response->send(403, ['status' => 'error', 'message' => 'Accès refusé']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Données JSON invalides']);
            return;
        }

        $required_fields = ['nom', 'etape_id'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->response->send(400, ['status' => 'error', 'message' => "Le champ $field est requis"]);
                return;
            }
        }

        try {
            $result = $this->model->updateSection(
                $section_id,
                $data['nom'],
                $data['field_id'] ?? null,
                $data['etape_id']
            );
            $this->response->send($result['status'] === 'success' ? 200 : 400, [
                'status' => $result['status'],
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getGroups() {
                $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->response->send(403, ['status' => 'error', 'message' => 'Accès refusé']);
            return;
        }

        try {
            $groups = $this->model->getGroups();
            $this->response->send(200, [
                'status' => 'success',
                'data' => ['groups' => $groups]
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des groupes: ' . $e->getMessage()
            ]);
        }
    }

    public function addGroup() {
                $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->response->send(403, ['status' => 'error', 'message' => 'Accès refusé']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Données JSON invalides']);
            return;
        }

        $required_fields = ['nom', 'section_id'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->response->send(400, ['status' => 'error', 'message' => "Le champ $field est requis"]);
                return;
            }
        }

        try {
            $result = $this->model->addGroup(
                $data['nom'],
                $data['field_id'] ?? null,
                $data['section_id']
            );
            $this->response->send($result['status'] === 'success' ? 201 : 400, [
                'status' => $result['status'],
                'message' => $result['message'],
                'group_id' => $result['group_id'] ?? null
            ]);
        } catch (Exception $e) {
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateGroup($group_id) {
                $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->response->send(403, ['status' => 'error', 'message' => 'Accès refusé']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Données JSON invalides']);
            return;
        }

        $required_fields = ['nom', 'section_id'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->response->send(400, ['status' => 'error', 'message' => "Le champ $field est requis"]);
                return;
            }
        }

        try {
            $result = $this->model->updateGroup(
                $group_id,
                $data['nom'],
                $data['field_id'] ?? null,
                $data['section_id']
            );
            $this->response->send($result['status'] === 'success' ? 200 : 400, [
                'status' => $result['status'],
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
public function deleteSection($section_id) {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        $this->response->send(403, ['status' => 'error', 'message' => 'Accès refusé']);
        return;
    }

    try {
        $result = $this->model->deleteSection($section_id);
        $this->response->send($result['status'] === 'success' ? 200 : 400, [
            'status' => $result['status'],
            'message' => $result['message']
        ]);
    } catch (Exception $e) {
        $this->response->send($e->getCode() ?: 500, [
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

public function deleteGroup($group_id) {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        $this->response->send(403, ['status' => 'error', 'message' => 'Accès refusé']);
        return;
    }

    try {
        $result = $this->model->deleteGroup($group_id);
        $this->response->send($result['status'] === 'success' ? 200 : 400, [
            'status' => $result['status'],
            'message' => $result['message']
        ]);
    } catch (Exception $e) {
        $this->response->send($e->getCode() ?: 500, [
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}
    public function getOptions() {
                $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->response->send(403, ['status' => 'error', 'message' => 'Accès refusé']);
            return;
        }

        try {
            $options = $this->model->getOptions();
            $this->response->send(200, [
                'status' => 'success',
                'data' => $options
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des options: ' . $e->getMessage()
            ]);
        }
    }
}
?>