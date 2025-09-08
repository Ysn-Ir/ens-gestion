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
        $this->model = new SectionModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->adminMiddleware = new AdminMiddleware();
        $this->response = new Response();
    }

    private function checkAccess() {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();

        if (!isset($_SESSION['user']) ||( $_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'admin')) {
            $this->response->send(403, [
                'status' => 'error',
                'message' => 'Accès refusé'
            ]);
            exit;
        }
    }

    /** =========================
     *   SECTIONS
     *  ========================= */
   public function getSections($filiereId = null) {
    $this->checkAccess();
    try {
        $sections = $this->model->getSections($filiereId);
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
        $this->checkAccess();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Données JSON invalides']);
            return;
        }

        if (empty($data['nom']) || empty($data['etape_id'])) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Le nom et l\'étape sont requis']);
            return;
        }

        try {
            $result = $this->model->addSection(
                $data['nom'],
                $data['field_id'] ?? null,
                $data['etape_id']
            );
            $this->response->send(201, $result);
        } catch (Exception $e) {
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateSection($section_id) {
        $this->checkAccess();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Données JSON invalides']);
            return;
        }

        if (empty($data['nom']) || empty($data['etape_id'])) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Le nom et l\'étape sont requis']);
            return;
        }

        try {
            $result = $this->model->updateSection(
                $section_id,
                $data['nom'],
                $data['field_id'] ?? null,
                $data['etape_id']
            );
            $this->response->send(200, $result);
        } catch (Exception $e) {
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function deleteSection($section_id) {
        $this->checkAccess();
        try {
            $result = $this->model->deleteSection($section_id);
            $this->response->send(200, $result);
        } catch (Exception $e) {
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /** =========================
     *   GROUPES
     *  ========================= */
    public function getGroups($filiereId = null) {
    $this->checkAccess();
    try {
        $groups = $this->model->getGroups($filiereId);
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
        $this->checkAccess();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Données JSON invalides']);
            return;
        }

        if (empty($data['nom']) || empty($data['section_id'])) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Le nom et la section sont requis']);
            return;
        }

        try {
            $result = $this->model->addGroup(
                $data['nom'],
                $data['field_id'] ?? null,
                $data['section_id']
            );
            $this->response->send(201, $result);
        } catch (Exception $e) {
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateGroup($group_id) {
        $this->checkAccess();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Données JSON invalides']);
            return;
        }

        if (empty($data['nom']) || empty($data['section_id'])) {
            $this->response->send(400, ['status' => 'error', 'message' => 'Le nom et la section sont requis']);
            return;
        }

        try {
            $result = $this->model->updateGroup(
                $group_id,
                $data['nom'],
                $data['field_id'] ?? null,
                $data['section_id']
            );
            $this->response->send(200, $result);
        } catch (Exception $e) {
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function deleteGroup($group_id) {
        $this->checkAccess();
        try {
            $result = $this->model->deleteGroup($group_id);
            $this->response->send(200, $result);
        } catch (Exception $e) {
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /** =========================
     *   OPTIONS
     *  ========================= */
    public function getOptions() {
        $this->checkAccess();
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
