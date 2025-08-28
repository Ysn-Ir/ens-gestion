<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/AdminMiddleware.php';
require_once __DIR__ . '/../models/NoteModel.php';
require_once __DIR__ . '/../utils/Response.php';

class NoteController
{
    private $model;
    private $authMiddleware;
    private $adminMiddleware;
    private $response;
    private $logger;

    public function __construct()
    {
        $this->model = new NoteModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->adminMiddleware = new AdminMiddleware();
        $this->response = new Response();
        $this->logger = function ($message) {
            error_log(date('[Y-m-d H:i:s] ') . $message);
        };
    }

    /**
     * Generate empty note rows for all students in a given semester and academic year
     */
    public function generateEmptyNoteRows()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            // Get input (JSON or POST/GET)
            $input = json_decode(file_get_contents('php://input'), true);
            if (is_array($input)) {
                $semestre_id = isset($input['semestre_id']) ? filter_var($input['semestre_id'], FILTER_VALIDATE_INT) : null;
                $annee_id = isset($input['annee_id']) ? filter_var($input['annee_id'], FILTER_SANITIZE_STRING) : null;
            } else {
                $semestre_id = filter_input(INPUT_POST, 'semestre_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
                $annee_id = filter_input(INPUT_POST, 'annee_id', FILTER_SANITIZE_STRING) ?: filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
            }

            // Validate inputs
            if (!$semestre_id) {
                ($this->logger)("generateEmptyNoteRows: Invalid or missing semestre_id");
                $this->response->send(400, [
                    'status' => 'error',
                    'code' => 'INVALID_SEMESTRE_ID',
                    'message' => 'Invalid or missing semestre_id. Must be an integer.'
                ]);
                return;
            }

            if (!$annee_id || !preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                ($this->logger)("generateEmptyNoteRows: Invalid or missing annee_id: $annee_id");
                $this->response->send(400, [
                    'status' => 'error',
                    'code' => 'INVALID_ANNEE_ID',
                    'message' => 'Invalid or missing annee_id. Expected YYYY-YYYY format.'
                ]);
                return;
            }

            [$start_year, $end_year] = explode('-', $annee_id);
            if ($end_year - $start_year !== 1) {
                ($this->logger)("generateEmptyNoteRows: Invalid annee_id range: $annee_id");
                $this->response->send(400, [
                    'status' => 'error',
                    'code' => 'INVALID_ANNEE_RANGE',
                    'message' => 'Invalid annee_id: second year must be one more than first year.'
                ]);
                return;
            }

            $result = $this->model->generateEmptyNoteRows($semestre_id, $annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            ($this->logger)("generateEmptyNoteRows: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'code' => 'SERVER_ERROR',
                'message' => 'Error generating empty note rows: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate final notes for all students in a given semester and academic year
     */
    public function calculateAllFinalNotes()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            // Get input
            $input = json_decode(file_get_contents('php://input'), true);
            if (is_array($input)) {
                $semestre_id = isset($input['semestre_id']) ? filter_var($input['semestre_id'], FILTER_VALIDATE_INT) : null;
                $annee_id = isset($input['annee_id']) ? filter_var($input['annee_id'], FILTER_SANITIZE_STRING) : null;
            } else {
                $semestre_id = filter_input(INPUT_POST, 'semestre_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
                $annee_id = filter_input(INPUT_POST, 'annee_id', FILTER_SANITIZE_STRING) ?: filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
            }

            // Validate inputs
            if (!$semestre_id) {
                ($this->logger)("calculateAllFinalNotes: Invalid or missing semestre_id");
                $this->response->send(400, [
                    'status' => 'error',
                    'code' => 'INVALID_SEMESTRE_ID',
                    'message' => 'Invalid or missing semestre_id. Must be an integer.'
                ]);
                return;
            }

            if (!$annee_id || !preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                ($this->logger)("calculateAllFinalNotes: Invalid or missing annee_id: $annee_id");
                $this->response->send(400, [
                    'status' => 'error',
                    'code' => 'INVALID_ANNEE_ID',
                    'message' => 'Invalid or missing annee_id. Expected YYYY-YYYY format.'
                ]);
                return;
            }

            [$start_year, $end_year] = explode('-', $annee_id);
            if ($end_year - $start_year !== 1) {
                ($this->logger)("calculateAllFinalNotes: Invalid annee_id range: $annee_id");
                $this->response->send(400, [
                    'status' => 'error',
                    'code' => 'INVALID_ANNEE_RANGE',
                    'message' => 'Invalid annee_id: second year must be one more than first year.'
                ]);
                return;
            }

            $result = $this->model->calculateAllFinalNotes($semestre_id, $annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            ($this->logger)("calculateAllFinalNotes: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'code' => 'SERVER_ERROR',
                'message' => 'Error calculating semester notes: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate final year notes for all students in a given academic year
     */
    public function calculateYearFinalNotes()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            // Get input
            $input = json_decode(file_get_contents('php://input'), true);
            if (is_array($input)) {
                $annee_id = isset($input['annee_id']) ? filter_var($input['annee_id'], FILTER_SANITIZE_STRING) : null;
            } else {
                $annee_id = filter_input(INPUT_POST, 'annee_id', FILTER_SANITIZE_STRING) ?: filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
            }

            // Validate inputs
            if (!$annee_id || !preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                ($this->logger)("calculateYearFinalNotes: Invalid or missing annee_id: $annee_id");
                $this->response->send(400, [
                    'status' => 'error',
                    'code' => 'INVALID_ANNEE_ID',
                    'message' => 'Invalid or missing annee_id. Expected YYYY-YYYY format.'
                ]);
                return;
            }

            [$start_year, $end_year] = explode('-', $annee_id);
            if ($end_year - $start_year !== 1) {
                ($this->logger)("calculateYearFinalNotes: Invalid annee_id range: $annee_id");
                $this->response->send(400, [
                    'status' => 'error',
                    'code' => 'INVALID_ANNEE_RANGE',
                    'message' => 'Invalid annee_id: second year must be one more than first year.'
                ]);
                return;
            }

            $result = $this->model->calculateYearFinalNotes($annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            ($this->logger)("calculateYearFinalNotes: annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'code' => 'SERVER_ERROR',
                'message' => 'Error calculating year notes: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate diplomas for all students in a given academic year
     */
    public function generateDiplomas()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            // Get input
            $input = json_decode(file_get_contents('php://input'), true);
            if (is_array($input)) {
                $annee_id = isset($input['annee_id']) ? filter_var($input['annee_id'], FILTER_SANITIZE_STRING) : null;
            } else {
                $annee_id = filter_input(INPUT_POST, 'annee_id', FILTER_SANITIZE_STRING) ?: filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
            }

            // Validate inputs
            if (!$annee_id || !preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
                ($this->logger)("generateDiplomas: Invalid or missing annee_id: $annee_id");
                $this->response->send(400, [
                    'status' => 'error',
                    'code' => 'INVALID_ANNEE_ID',
                    'message' => 'Invalid or missing annee_id. Expected YYYY-YYYY format.'
                ]);
                return;
            }

            [$start_year, $end_year] = explode('-', $annee_id);
            if ($end_year - $start_year !== 1) {
                ($this->logger)("generateDiplomas: Invalid annee_id range: $annee_id");
                $this->response->send(400, [
                    'status' => 'error',
                    'code' => 'INVALID_ANNEE_RANGE',
                    'message' => 'Invalid annee_id: second year must be one more than first year.'
                ]);
                return;
            }

            $result = $this->model->generateDiplomas($annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            ($this->logger)("generateDiplomas: annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'code' => 'SERVER_ERROR',
                'message' => 'Error generating diplomas: ' . $e->getMessage()
            ]);
        }
    }
     public function getElementNotes()
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $semestre_id = filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
        $annee_id = filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
        validateRequiredParam($annee_id, 'annee_id');

        $result = $this->model->getElementNotes($semestre_id, $annee_id);
        $this->response->send($result['success'] ? 200 : 400, [
            'status' => $result['success'] ? 'success' : 'error',
            'data' => $result['data'] ?? [],
            'message' => $result['message'] ?? ($result['success'] ? 'Element notes retrieved successfully' : 'Failed to retrieve element notes')
        ]);
    }

    public function getModuleNotes()
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $semestre_id = filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
        $annee_id = filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
        validateRequiredParam($annee_id, 'annee_id');

        $result = $this->model->getModuleNotes($semestre_id, $annee_id);
        $this->response->send($result['success'] ? 200 : 400, [
            'status' => $result['success'] ? 'success' : 'error',
            'data' => $result['data'] ?? [],
            'message' => $result['message'] ?? ($result['success'] ? 'Module notes retrieved successfully' : 'Failed to retrieve module notes')
        ]);
    }

    public function getSemesterNotes()
    {
                    $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
        $semestre_id = filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
        $annee_id = filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
        validateRequiredParam($annee_id, 'annee_id');

        $result = $this->model->getSemesterNotes($semestre_id, $annee_id);
        $this->response->send($result['success'] ? 200 : 400, [
            'status' => $result['success'] ? 'success' : 'error',
            'data' => $result['data'] ?? [],
            'message' => $result['message'] ?? ($result['success'] ? 'Semester notes retrieved successfully' : 'Failed to retrieve semester notes')
        ]);
    }

    public function getYearNotes()
    {
                    $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
        $annee_id = filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
        validateRequiredParam($annee_id, 'annee_id');

        $result = $this->model->getYearNotes($annee_id);
        $this->response->send($result['success'] ? 200 : 400, [
            'status' => $result['success'] ? 'success' : 'error',
            'data' => $result['data'] ?? [],
            'message' => $result['message'] ?? ($result['success'] ? 'Year notes retrieved successfully' : 'Failed to retrieve year notes')
        ]);
    }
}