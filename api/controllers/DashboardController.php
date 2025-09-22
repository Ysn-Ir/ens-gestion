<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/AdminMiddleware.php';
require_once __DIR__ . '/../models/DashboardModel.php';
require_once __DIR__ . '/../utils/Response.php';

class DashboardController {
    private $model;
    private $authMiddleware;
    private $adminMiddleware;
    private $response;

    public function __construct()
    {
        $this->model = new DashboardModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->adminMiddleware = new AdminMiddleware();
        $this->response = new Response();
    }

    /**
     * Retrieve all dashboard statistics including settings, counts, and students per filière.
     */
    public function getDashboardStats()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $stats = [
                'settings' => $this->model->getAllSettings(),
                'student_count' => $this->model->getStudentCount(),
                'professor_count' => $this->model->getProfessorCount(),
                'module_count' => $this->model->getModuleCount(),
                'note_count' => $this->model->getNoteCount(),
                'students_per_filiere' => $this->model->getStudentsPerFiliere()
            ];

            $this->response->sendSuccess($stats, 'Dashboard statistics retrieved successfully');
        } catch (PDOException $e) {
            $this->response->sendError('Database error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            $this->response->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Retrieve all system settings.
     */
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
}