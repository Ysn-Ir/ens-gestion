<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/AdminMiddleware.php';
require_once __DIR__ . '/../models/settingModel.php';
require_once __DIR__ . '/../utils/Response.php';

class settingController {
    private $model;
    private $authMiddleware;
    private $adminMiddleware;
    private $response;

    public function __construct()
    {
        $this->model = new settingModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->adminMiddleware = new AdminMiddleware();
        $this->response = new Response();
    }

    public function getAllSettings()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $settings = $this->model->getAllSettings();
            $this->response->sendSuccess($settings, 'Settings retrieved successfully');
        } catch (PDOException $e) {
            $this->response->sendError('Database error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            $this->response->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function addSetting()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = json_decode(file_get_contents('php://input'), true);
            if (!isset($input['name']) || !isset($input['value'])) {
                throw new Exception('Missing required fields', 400);
            }

            $success = $this->model->addSetting($input['name'], $input['value']);
            if ($success) {
                $this->response->sendSuccess([], 'Setting added successfully', 201);
            } else {
                throw new Exception('Failed to add setting', 500);
            }
        } catch (Exception $e) {
            $this->response->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function updateSetting()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = json_decode(file_get_contents('php://input'), true);
            if (!isset($input['name']) || !isset($input['value'])) {
                throw new Exception('Missing required fields', 400);
            }

            $success = $this->model->updateSetting($input['name'], $input['value']);
            if ($success) {
                $this->response->sendSuccess([], 'Setting updated successfully');
            } else {
                throw new Exception('Failed to update setting', 500);
            }
        } catch (Exception $e) {
            $this->response->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function deleteSetting()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = json_decode(file_get_contents('php://input'), true);
            if (!isset($input['name'])) {
                throw new Exception('Missing name', 400);
            }

            $success = $this->model->deleteSetting($input['name']);
            if ($success) {
                $this->response->sendSuccess([], 'Setting deleted successfully');
            } else {
                throw new Exception('Failed to delete setting', 500);
            }
        } catch (Exception $e) {
            $this->response->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function getAllConstraints()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $constraints = $this->model->getAllConstraints();
            $this->response->sendSuccess($constraints, 'Constraints retrieved successfully');
        } catch (PDOException $e) {
            $this->response->sendError('Database error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            $this->response->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function addConstraint()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = json_decode(file_get_contents('php://input'), true);
            $required = ['scope', 'rule_name', 'rule_value'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    throw new Exception("Missing $field", 400);
                }
            }
            $input['semestre_id'] = $input['semestre_id'] ?? null;
            $input['annee_id'] = $input['annee_id'] ?? null;
            $input['field_id'] = $input['field_id'] ?? null;

            $success = $this->model->addConstraint($input);
            if ($success) {
                $this->response->sendSuccess([], 'Constraint added successfully', 201);
            } else {
                throw new Exception('Failed to add constraint', 500);
            }
        } catch (Exception $e) {
            $this->response->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function updateConstraint()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = json_decode(file_get_contents('php://input'), true);
            if (!isset($input['rule_id'])) {
                throw new Exception('Missing rule_id', 400);
            }
            $required = ['scope', 'rule_name', 'rule_value'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    throw new Exception("Missing $field", 400);
                }
            }
            $input['semestre_id'] = $input['semestre_id'] ?? null;
            $input['annee_id'] = $input['annee_id'] ?? null;
            $input['field_id'] = $input['field_id'] ?? null;

            $success = $this->model->updateConstraint($input['rule_id'], $input);
            if ($success) {
                $this->response->sendSuccess([], 'Constraint updated successfully');
            } else {
                throw new Exception('Failed to update constraint', 500);
            }
        } catch (Exception $e) {
            $this->response->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function deleteConstraint()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = json_decode(file_get_contents('php://input'), true);
            if (!isset($input['rule_id'])) {
                throw new Exception('Missing rule_id', 400);
            }

            $success = $this->model->deleteConstraint($input['rule_id']);
            if ($success) {
                $this->response->sendSuccess([], 'Constraint deleted successfully');
            } else {
                throw new Exception('Failed to delete constraint', 500);
            }
        } catch (Exception $e) {
            $this->response->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}