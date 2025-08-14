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

    public function __construct()
    {
        $this->model = new NoteModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->adminMiddleware = new AdminMiddleware();
        $this->response = new Response();
    }

    /**
     * Generate empty note rows for all students in a given semester and academic year
     */
    public function generateEmptyNoteRows()
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();

        // Try JSON input first
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $semestre_id = isset($input['semestre_id']) ? filter_var($input['semestre_id'], FILTER_VALIDATE_INT) : null;
            $annee_id = isset($input['annee_id']) ? filter_var($input['annee_id'], FILTER_SANITIZE_STRING) : null;
        } else {
            // Fallback to POST or GET parameters
            $semestre_id = filter_input(INPUT_POST, 'semestre_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
            $annee_id = filter_input(INPUT_POST, 'annee_id', FILTER_SANITIZE_STRING) ?: filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
        }

        // Validate inputs
        if (!$semestre_id) {
            error_log("generateEmptyNoteRows: Invalid or missing semestre_id");
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Invalid or missing semestre_id. Must be an integer.'
            ]);
            return;
        }

        if (!$annee_id) {
            error_log("generateEmptyNoteRows: Invalid or missing annee_id");
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Invalid or missing annee_id.'
            ]);
            return;
        }

        // Validate annee_id format (YYYY-YYYY)
        if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
            error_log("generateEmptyNoteRows: Invalid annee_id format: $annee_id");
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Invalid annee_id format. Expected YYYY-YYYY'
            ]);
            return;
        }

        try {
            $result = $this->model->generateEmptyNoteRows($semestre_id, $annee_id);
            if ($result['success']) {
                $this->response->send(200, [
                    'status' => 'success',
                    'message' => $result['message']
                ]);
            } else {
                error_log("generateEmptyNoteRows: Model failed for semestre_id=$semestre_id, annee_id=$annee_id, error=" . $result['message']);
                $this->response->send(400, [
                    'status' => 'error',
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            error_log("generateEmptyNoteRows: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Error generating empty note rows: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate final notes for all students in a given semester and academic year
     */
    public function calculateAllFinalNotes()
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();

        // Try JSON input first
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $semestre_id = isset($input['semestre_id']) ? filter_var($input['semestre_id'], FILTER_VALIDATE_INT) : null;
            $annee_id = isset($input['annee_id']) ? filter_var($input['annee_id'], FILTER_SANITIZE_STRING) : null;
        } else {
            // Fallback to POST or GET parameters
            $semestre_id = filter_input(INPUT_POST, 'semestre_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'semestre_id', FILTER_VALIDATE_INT);
            $annee_id = filter_input(INPUT_POST, 'annee_id', FILTER_SANITIZE_STRING) ?: filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
        }

        // Validate inputs
        if (!$semestre_id) {
            error_log("calculateAllFinalNotes: Invalid or missing semestre_id");
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Invalid or missing semestre_id. Must be an integer.'
            ]);
            return;
        }

        if (!$annee_id) {
            error_log("calculateAllFinalNotes: Invalid or missing annee_id");
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Invalid or missing annee_id.'
            ]);
            return;
        }

        // Validate annee_id format (YYYY-YYYY)
        if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
            error_log("calculateAllFinalNotes: Invalid annee_id format: $annee_id");
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Invalid annee_id format. Expected YYYY-YYYY'
            ]);
            return;
        }

        try {
            $result = $this->model->calculateAllFinalNotes($semestre_id, $annee_id);
            if ($result['success']) {
                $this->response->send(200, [
                    'status' => 'success',
                    'message' => $result['message']
                ]);
            } else {
                error_log("calculateAllFinalNotes: Model failed for semestre_id=$semestre_id, annee_id=$annee_id, error=" . $result['message']);
                $this->response->send(400, [
                    'status' => 'error',
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            error_log("calculateAllFinalNotes: semestre_id=$semestre_id, annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Error calculating semester notes: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate final year notes for all students in a given academic year
     */
    public function calculateYearFinalNotes()
    {
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();

        // Try JSON input first
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $annee_id = isset($input['annee_id']) ? filter_var($input['annee_id'], FILTER_SANITIZE_STRING) : null;
        } else {
            // Fallback to POST or GET parameters
            $annee_id = filter_input(INPUT_POST, 'annee_id', FILTER_SANITIZE_STRING) ?: filter_input(INPUT_GET, 'annee_id', FILTER_SANITIZE_STRING);
        }

        // Validate inputs
        if (!$annee_id) {
            error_log("calculateYearFinalNotes: Invalid or missing annee_id");
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Invalid or missing annee_id.'
            ]);
            return;
        }

        // Validate annee_id format (YYYY-YYYY)
        if (!preg_match('/^\d{4}-\d{4}$/', $annee_id)) {
            error_log("calculateYearFinalNotes: Invalid annee_id format: $annee_id");
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Invalid annee_id format. Expected YYYY-YYYY'
            ]);
            return;
        }

        try {
            $result = $this->model->calculateYearFinalNotes($annee_id);
            if ($result['success']) {
                $this->response->send(200, [
                    'status' => 'success',
                    'message' => $result['message']
                ]);
            } else {
                error_log("calculateYearFinalNotes: Model failed for annee_id=$annee_id, error=" . $result['message']);
                $this->response->send(400, [
                    'status' => 'error',
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            error_log("calculateYearFinalNotes: annee_id=$annee_id, error=" . $e->getMessage());
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Error calculating year notes: ' . $e->getMessage()
            ]);
        }
    }
}
?>
