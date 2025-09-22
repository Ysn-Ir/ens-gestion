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

    /**
     * Initialize the NoteController with dependencies.
     */
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
     * Get and sanitize input from JSON or POST/GET.
     *
     * @return array Array containing semestre_id and annee_id
     */
    private function getInput()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            ($this->logger)("Invalid JSON input: " . json_last_error_msg());
            $input = null;
        }

        if (is_array($input)) {
            return [
                'semestre_id' => isset($input['semestre_id']) ? filter_var($input['semestre_id'], FILTER_VALIDATE_INT) : null,
                'annee_id' => isset($input['annee_id']) ? filter_var($input['annee_id'], FILTER_SANITIZE_SPECIAL_CHARS) : null
            ];
        }

        return [
            'semestre_id' => filter_input(INPUT_POST, 'semestre_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT),
            'annee_id' => filter_input(INPUT_POST, 'annee_id', FILTER_SANITIZE_SPECIAL_CHARS) ?: filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_SPECIAL_CHARS)
        ];
    }

    /**
     * Validate input parameters.
     *
     * @param int|null $semestre_id The semester ID
     * @param string $annee_id The academic year in YYYY-YYYY format
     * @param bool $requireSemestre Whether semestre_id is required
     * @return bool True if valid, false if invalid (sends response on failure)
     */
    private function validateInputs($semestre_id = null, $annee_id, $requireSemestre = false)
    {
        if ($requireSemestre && !filter_var($semestre_id, FILTER_VALIDATE_INT)) {
            ($this->logger)("Invalid or missing semestre_id");
            $this->response->send(400, [
                'status' => 'error',
                'code' => 'INVALID_SEMESTRE_ID',
                'message' => 'Invalid or missing semestre_id. Must be an integer.',
                'data' => []
            ]);
            return false;
        }

        if (!$annee_id || !preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
            ($this->logger)("Invalid or missing annee_id: $annee_id");
            $this->response->send(400, [
                'status' => 'error',
                'code' => 'INVALID_ANNEE_ID',
                'message' => 'Invalid or missing annee_id. Expected YYYY-YYYY format.',
                'data' => []
            ]);
            return false;
        }

        [$start_year, $end_year] = explode('-', $annee_id);
        if ($end_year - $start_year !== 1) {
            ($this->logger)("Invalid annee_id range: $annee_id");
            $this->response->send(400, [
                'status' => 'error',
                'code' => 'INVALID_ANNEE_RANGE',
                'message' => 'Invalid annee_id: second year must be one more than first year.',
                'data' => []
            ]);
            return false;
        }

        return true;
    }

    /**
     * Validate required parameters.
     *
     * @param mixed $param The parameter value
     * @param string $paramName The parameter name
     * @return void Exits with error response if validation fails
     */
    private function validateRequiredParam($param, $paramName)
    {
        if (empty($param)) {
            ($this->logger)("Missing required parameter: $paramName");
            $this->response->send(400, [
                'status' => 'error',
                'code' => 'MISSING_' . strtoupper($paramName),
                'message' => "Missing required parameter: $paramName",
                'data' => []
            ]);
            exit;
        }
    }

    /**
     * Generate empty note rows for all students in a given semester and academic year.
     *
     * @return void Outputs JSON response with status, code, message, and data
     * @throws Exception If authentication fails, user is not admin, or database errors occur
     */
    public function generateEmptyNoteRows()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = $this->getInput();
            $semestre_id = $input['semestre_id'];
            $annee_id = $input['annee_id'];

            if (!$this->validateInputs($semestre_id, $annee_id, true)) {
                return;
            }

            $result = $this->model->generateEmptyNoteRows($semestre_id, $annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'code' => $result['success'] ? 'NOTES_GENERATED' : 'GENERATION_FAILED',
                'message' => $result['message'],
                'data' => []
            ]);
        } catch (Exception $e) {
            ($this->logger)("generateEmptyNoteRows: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'code' => $e->getCode() ? strtoupper(str_replace(' ', '_', $e->getMessage())) : 'SERVER_ERROR',
                'message' => 'Error generating empty note rows: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Calculate final notes for all students in a given semester and academic year.
     *
     * @return void Outputs JSON response with status, code, message, and data
     * @throws Exception If authentication fails, user is not admin, or database errors occur
     */
    public function calculateAllFinalNotes()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = $this->getInput();
            $semestre_id = $input['semestre_id'];
            $annee_id = $input['annee_id'];

            if (!$this->validateInputs($semestre_id, $annee_id, true)) {
                return;
            }

            $result = $this->model->calculateAllFinalNotes($semestre_id, $annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'code' => $result['success'] ? 'NOTES_CALCULATED' : 'CALCULATION_FAILED',
                'message' => $result['message'],
                'data' => []
            ]);
        } catch (Exception $e) {
            ($this->logger)("calculateAllFinalNotes: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'code' => $e->getCode() ? strtoupper(str_replace(' ', '_', $e->getMessage())) : 'SERVER_ERROR',
                'message' => 'Error calculating semester notes: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Calculate final year notes for all students in a given academic year.
     *
     * @return void Outputs JSON response with status, code, message, and data
     * @throws Exception If authentication fails, user is not admin, or database errors occur
     */
    public function calculateYearFinalNotes()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = $this->getInput();
            $annee_id = $input['annee_id'];

            if (!$this->validateInputs(null, $annee_id, false)) {
                return;
            }

            $result = $this->model->calculateYearFinalNotes($annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'code' => $result['success'] ? 'YEAR_NOTES_CALCULATED' : 'YEAR_CALCULATION_FAILED',
                'message' => $result['message'],
                'data' => []
            ]);
        } catch (Exception $e) {
            ($this->logger)("calculateYearFinalNotes: annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'code' => $e->getCode() ? strtoupper(str_replace(' ', '_', $e->getMessage())) : 'SERVER_ERROR',
                'message' => 'Error calculating year notes: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Generate diplomas for all students in a given academic year.
     *
     * @return void Outputs JSON response with status, code, message, and data
     * @throws Exception If authentication fails, user is not admin, or database errors occur
     */
    public function generateDiplomas()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = $this->getInput();
            $annee_id = $input['annee_id'];

            if (!$this->validateInputs(null, $annee_id, false)) {
                return;
            }

            $result = $this->model->generateDiplomas($annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'code' => $result['success'] ? 'DIPLOMAS_GENERATED' : 'DIPLOMA_GENERATION_FAILED',
                'message' => $result['message'],
                'data' => []
            ]);
        } catch (Exception $e) {
            ($this->logger)("generateDiplomas: annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'code' => $e->getCode() ? strtoupper(str_replace(' ', '_', $e->getMessage())) : 'SERVER_ERROR',
                'message' => 'Error generating diplomas: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Fetch element notes for a given semester and academic year.
     *
     * @return void Outputs JSON response with status, code, message, and data
     * @throws Exception If authentication fails, user is not admin, or database errors occur
     */
    public function getElementNotes()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = $this->getInput();
            $semestre_id = $input['semestre_id'];
            $annee_id = $input['annee_id'];

            $this->validateRequiredParam($annee_id, 'annee_id');
            if (!$this->validateInputs($semestre_id, $annee_id, false)) {
                return;
            }

            $result = $this->model->getElementNotes($semestre_id, $annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'code' => $result['success'] ? 'ELEMENT_NOTES_RETRIEVED' : 'ELEMENT_NOTES_FAILED',
                'message' => $result['message'] ?? ($result['success'] ? 'Element notes retrieved successfully' : 'Failed to retrieve element notes'),
                'data' => $result['data'] ?? []
            ]);
        } catch (Exception $e) {
            ($this->logger)("getElementNotes: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'code' => $e->getCode() ? strtoupper(str_replace(' ', '_', $e->getMessage())) : 'SERVER_ERROR',
                'message' => 'Error retrieving element notes: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Fetch module notes for a given semester and academic year.
     *
     * @return void Outputs JSON response with status, code, message, and data
     * @throws Exception If authentication fails, user is not admin, or database errors occur
     */
    public function getModuleNotes()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = $this->getInput();
            $semestre_id = $input['semestre_id'];
            $annee_id = $input['annee_id'];

            $this->validateRequiredParam($annee_id, 'annee_id');
            if (!$this->validateInputs($semestre_id, $annee_id, false)) {
                return;
            }

            $result = $this->model->getModuleNotes($semestre_id, $annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'code' => $result['success'] ? 'MODULE_NOTES_RETRIEVED' : 'MODULE_NOTES_FAILED',
                'message' => $result['message'] ?? ($result['success'] ? 'Module notes retrieved successfully' : 'Failed to retrieve module notes'),
                'data' => $result['data'] ?? []
            ]);
        } catch (Exception $e) {
            ($this->logger)("getModuleNotes: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'code' => $e->getCode() ? strtoupper(str_replace(' ', '_', $e->getMessage())) : 'SERVER_ERROR',
                'message' => 'Error retrieving module notes: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Fetch semester notes for a given semester and academic year.
     *
     * @return void Outputs JSON response with status, code, message, and data
     * @throws Exception If authentication fails, user is not admin, or database errors occur
     */
    public function getSemesterNotes()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = $this->getInput();
            $semestre_id = $input['semestre_id'];
            $annee_id = $input['annee_id'];

            $this->validateRequiredParam($annee_id, 'annee_id');
            if (!$this->validateInputs($semestre_id, $annee_id, false)) {
                return;
            }

            $result = $this->model->getSemesterNotes($semestre_id, $annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'code' => $result['success'] ? 'SEMESTER_NOTES_RETRIEVED' : 'SEMESTER_NOTES_FAILED',
                'message' => $result['message'] ?? ($result['success'] ? 'Semester notes retrieved successfully' : 'Failed to retrieve semester notes'),
                'data' => $result['data'] ?? []
            ]);
        } catch (Exception $e) {
            ($this->logger)("getSemesterNotes: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'code' => $e->getCode() ? strtoupper(str_replace(' ', '_', $e->getMessage())) : 'SERVER_ERROR',
                'message' => 'Error retrieving semester notes: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Fetch year notes for a given academic year.
     *
     * @return void Outputs JSON response with status, code, message, and data
     * @throws Exception If authentication fails, user is not admin, or database errors occur
     */
    public function getYearNotes()
    {
        try {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();

            $input = $this->getInput();
            $annee_id = $input['annee_id'];

            $this->validateRequiredParam($annee_id, 'annee_id');
            if (!$this->validateInputs(null, $annee_id, false)) {
                return;
            }

            $result = $this->model->getYearNotes($annee_id);
            $this->response->send($result['success'] ? 200 : 400, [
                'status' => $result['success'] ? 'success' : 'error',
                'code' => $result['success'] ? 'YEAR_NOTES_RETRIEVED' : 'YEAR_NOTES_FAILED',
                'message' => $result['message'] ?? ($result['success'] ? 'Year notes retrieved successfully' : 'Failed to retrieve year notes'),
                'data' => $result['data'] ?? []
            ]);
        } catch (Exception $e) {
            ($this->logger)("getYearNotes: annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send($e->getCode() ?: 500, [
                'status' => 'error',
                'code' => $e->getCode() ? strtoupper(str_replace(' ', '_', $e->getMessage())) : 'SERVER_ERROR',
                'message' => 'Error retrieving year notes: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
}