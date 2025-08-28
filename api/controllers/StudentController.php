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
}
