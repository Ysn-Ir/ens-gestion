<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/StudentMiddleware.php';
require_once __DIR__ . '/../models/StudentModel.php';
require_once __DIR__ . '/../utils/Response.php';

class StudentController {
    private $model;
    private $authMiddleware;
    private $studentMiddleware;
    private $response;

    public function __construct() {
        $this->model = new StudentModel();
        $this->response = new Response();
    }

    public function getCurrentStudentInfo() {
    session_start();

    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
        $this->response->send(403, ['status' => 'error', 'message' => 'Accès refusé']);
        return;
    }

    $userId = $_SESSION['user']['user_id'];

    $student = $this->model->getStudentByUserId($userId);
    $this->response->send(200, ['status' => 'success', 'student' => $student]);
}


    // Get basic student profile + email
    public function getStudent($id) {

        $student = $this->model->getStudent($id);
        $this->response->send(200, $student);
    }
    
    // Get student info for a specific etape
    public function getStudentInfo($id, $etapeId) {


        $info = $this->model->getStudentInfo($id, $etapeId);
        $this->response->send(200, $info);
    }

    // Get diplomas
    public function getDiplomas($id) {

        $diplomas = $this->model->getDiplomes($id);
        $this->response->send(200, $diplomas);
    }

    // Get module notes
    public function getNoteModules($id) {
  
        $notes = $this->model->getStudentNoteModule($id);
        $this->response->send(200, $notes);
    }

    // Get all notes grouped by etape and semester
    public function getAllNotes($id) {

        $notes = $this->model->getAllNotesByEtape($id);
        $this->response->send(200, $notes);
    }

    // Get student by user ID
    public function getAllNotesByUserIdAndEtapeAndSemester($userId, $etapeId, $semesterId) {
        $etapeId = isset($_GET['etape_id']) ? $_GET['etape_id'] : null;
        $notes = $this->model->getAllNotesByEtapeAndSemester($userId, $etapeId, $semesterId);
        $this->response->send(200, $notes);
    }

    public function getAnnualNoteAndRanking($userId, $anneeId) {
        // Validate input parameters
        if (empty($userId) || empty($anneeId)) {
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Missing required parameters: userId or anneeId'
            ]);
            return;
        }

        // Call the model function to get annual note and ranking
        $result = $this->model->getAnnualNoteAndRanking($userId, $anneeId);

        // Handle the response based on the result
        if ($result['status'] === 'success') {
            $this->response->send(200, $result);
        } elseif ($result['status'] === 'incomplete') {
            $this->response->send(400, [
                'status' => 'error',
                'message' => $result['message']
            ]);
        } else {
            $this->response->send(404, [
                'status' => 'error',
                'message' => $result['message']
            ]);
        }
    }

    public function getAllEtapes() {
        try {
            $etapes = $this->model->getAllEtapes();
            $this->response->send(200, [
                'status' => 'success',
                'data' => $etapes
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des étapes: ' . $e->getMessage()
            ]);
        }
    }

    public function getSemestresByEtape($etapeId) {
        if (empty($etapeId)) {
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Missing etape ID'
            ]);
            return;
        }

        try {
            $semestres = $this->model->getSemestresByEtape($etapeId);
            $this->response->send(200, [
                'status' => 'success',
                'data' => $semestres
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des semestres: ' . $e->getMessage()
            ]);
        }
    }

    public function getAllAnnees() {
        try {
            $annees = $this->model->getAllAnnees();
            $this->response->send(200, [
                'status' => 'success',
                'data' => $annees
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des années académiques: ' . $e->getMessage()
            ]);
        }
    }

    

}
