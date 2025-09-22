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
        $this->authMiddleware = new AuthMiddleware();
        $this->studentMiddleware = new StudentMiddleware();
        $this->response = new Response();
    }

    public function getCurrentStudentInfo() {
        // Verify session and student role
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        // Session is already started by StudentMiddleware
        $userId = $_SESSION['user']['user_id'];

        try {
            $student = $this->model->getStudentByUserId($userId);
            if (!$student) {
                $this->response->send(404, [
                    'status' => 'error',
                    'message' => 'Étudiant non trouvé'
                ]);
                return;
            }
            $this->response->send(200, [
                'status' => 'success',
                'student' => $student
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des informations de l\'étudiant : ' . $e->getMessage()
            ]);
        }
    }

    // Get basic student profile + email
    public function getStudent($id) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $student = $this->model->getStudent($id);
            if (!$student) {
                $this->response->send(404, [
                    'status' => 'error',
                    'message' => 'Étudiant non trouvé'
                ]);
                return;
            }
            $this->response->send(200, [
                'status' => 'success',
                'data' => $student
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du profil étudiant : ' . $e->getMessage()
            ]);
        }
    }

    // Get student info for a specific etape
    public function getStudentInfo($id, $etapeId) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $info = $this->model->getStudentInfo($id, $etapeId);
            if (!$info) {
                $this->response->send(404, [
                    'status' => 'error',
                    'message' => 'Informations non trouvées pour cet étudiant et cette étape'
                ]);
                return;
            }
            $this->response->send(200, [
                'status' => 'success',
                'data' => $info
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des informations : ' . $e->getMessage()
            ]);
        }
    }

    // Get diplomas
    public function getDiplomas($id) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $diplomas = $this->model->getDiplomes($id);
            $this->response->send(200, [
                'status' => 'success',
                'data' => $diplomas
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des diplômes : ' . $e->getMessage()
            ]);
        }
    }

    // Get module notes
    public function getNoteModules($id) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $notes = $this->model->getStudentNoteModule($id);
            $this->response->send(200, [
                'status' => 'success',
                'data' => $notes
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des notes de modules : ' . $e->getMessage()
            ]);
        }
    }

    // Get all notes grouped by etape and semester
    public function getAllNotes($id) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $notes = $this->model->getAllNotesByEtape($id);
            $this->response->send(200, [
                'status' => 'success',
                'data' => $notes
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des notes : ' . $e->getMessage()
            ]);
        }
    }

    // Get student notes by user ID, etape, and semester
    public function getAllNotesByUserIdAndEtapeAndSemester($userId, $etapeId, $semesterId) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $notes = $this->model->getAllNotesByEtapeAndSemester($userId, $etapeId, $semesterId);
            $this->response->send(200, [
                'status' => 'success',
                'data' => $notes
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des notes : ' . $e->getMessage()
            ]);
        }
    }

    public function getAnnualNoteAndRanking($userId, $anneeId) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        // Validate input parameters
        if (empty($userId) || empty($anneeId)) {
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Missing required parameters: userId or anneeId'
            ]);
            return;
        }

        try {
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
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des notes annuelles : ' . $e->getMessage()
            ]);
        }
    }

    public function getAllEtapes() {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $etapes = $this->model->getAllEtapes();
            $this->response->send(200, [
                'status' => 'success',
                'data' => $etapes
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des étapes : ' . $e->getMessage()
            ]);
        }
    }

    public function getSemestresByEtapeCycleFiliere($etapeId, $cycleId, $fieldId) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        // Validate input parameters
        if (empty($etapeId) || empty($cycleId) || empty($fieldId)) {
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Missing required parameters: etape_id, cycle_id, or field_id'
            ]);
            return;
        }

        try {
            // Call the model method to fetch semesters
            $semestres = $this->model->getSemestresByEtapeCycleFiliere($etapeId, $cycleId, $fieldId);

            // Check if any semesters were found
            if (empty($semestres)) {
                $this->response->send(404, [
                    'status' => 'error',
                    'message' => 'No semesters found for the provided etape, cycle, and filiere'
                ]);
                return;
            }

            // Return success response with semesters
            $this->response->send(200, [
                'status' => 'success',
                'data' => $semestres
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des semestres : ' . $e->getMessage()
            ]);
        }
    }

    public function getAllAnnees() {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $annees = $this->model->getAllAnnees();
            $this->response->send(200, [
                'status' => 'success',
                'data' => $annees
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des années académiques : ' . $e->getMessage()
            ]);
        }
    }

    public function getYearStudied($student_id) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $stmt = $this->model->getYearStudied($student_id);
            $this->response->send(200, [
                'status' => 'success',
                'data' => $stmt
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des années étudiées : ' . $e->getMessage()
            ]);
        }
    }

    public function changePassword($userId, $newPassword) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        if (empty($userId) || empty($newPassword)) {
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Missing required parameters'
            ]);
            return;
        }

        try {
            $result = $this->model->changePassword($userId, $newPassword);

            if ($result === true) {
                $this->response->send(200, [
                    'status' => 'success',
                    'message' => 'Password updated successfully'
                ]);
            } else {
                $this->response->send(400, [
                    'status' => 'error',
                    'message' => 'Failed to update password'
                ]);
            }
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Error changing password: ' . $e->getMessage()
            ]);
        }
    }

    public function getCycleOfStudent($student_id) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $stmt = $this->model->getCycleOfStudent($student_id);
            $this->response->send(200, [
                'status' => 'success',
                'data' => $stmt
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du cycle : ' . $e->getMessage()
            ]);
        }
    }

    public function getSemestresOfStudentByCycle($student_id, $cycle_id) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        try {
            $stmt = $this->model->getSemestresOfStudentByCycle($student_id, $cycle_id);
            $this->response->send(200, [
                'status' => 'success',
                'data' => $stmt
            ]);
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des semestres : ' . $e->getMessage()
            ]);
        }
    }

    public function getNoteOfStudentBySemestre($student_id, $semestre_id) {
        $this->authMiddleware->verifySession();
        $this->studentMiddleware->verifyStudent();

        // Validation des paramètres
        if (empty($student_id) || empty($semestre_id) || !is_numeric($student_id) || !is_numeric($semestre_id)) {
            $this->response->send(400, [
                'status' => 'error',
                'message' => 'Paramètres invalides : student_id et semestre_id doivent être numériques.'
            ]);
            return;
        }

        try {
            $notes = $this->model->getNoteOfStudentBySemestre($student_id, $semestre_id);

            if (empty($notes)) {
                $this->response->send(404, [
                    'status' => 'error',
                    'message' => 'Aucune note trouvée pour cet étudiant dans ce semestre.'
                ]);
            } else {
                $this->response->send(200, [
                    'status' => 'success',
                    'data' => $notes
                ]);
            }
        } catch (Exception $e) {
            $this->response->send(500, [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des notes : ' . $e->getMessage()
            ]);
        }
    }
}