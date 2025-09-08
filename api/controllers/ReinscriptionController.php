
<?php
require_once __DIR__ . "/../models/ReinscriptionModel.php";
require_once __DIR__ . "/../utils/Response.php";

class ReinscriptionController {
    private $model;

    public function __construct() {
        $this->model = new ReinscriptionModel();
    }

    public function handleRequest() {
        $method = $_SERVER["REQUEST_METHOD"];
        
        if ($method === "GET") {
            // L'action est maintenant le principal routeur pour les requêtes GET
            $action = $_GET['action'] ?? null;

            switch ($action) {
                case 'get_reinscription_data': // Action appelée par le JS
                    $this->getStudentsToReEnroll();
                    break;
                
                case 'get_next_cycle_students': // Action appelée par le JS
                    $this->getStudentsReadyForNextCycle();
                    break;

                case 'get_fields_for_cycle':
                    $this->getFieldsForCycle((int)($_GET['cycle_id'] ?? 0));
                    break;

                case 'get_student_history':
                    $this->getStudentHistory((int)($_GET['student_id'] ?? 0));
                    break;
                
                case 'get_passing_students':
                    $this->getPassingStudents();
                    break;
                
                case 'get_graduated_students':
                    $this->getGraduatedStudents();
                    break;

                default:
                    Response::sendError("Action GET non valide ou manquante.", 400);
                    break;
            }

        } elseif ($method === "POST") {
            $data = json_decode(file_get_contents("php://input"), true);
            $action = $data['action'] ?? null; // On lit l'action depuis le corps de la requête POST

            switch ($action) {
                case 'reenroll_student': // Action appelée par le JS
                    $this->processReEnrollment($data);
                    break;
                
                case 'enroll_all_passing': // Nouvelle action pour l'inscription en masse
                    $this->processBulkEnrollment($data);
                    break;

                default:
                    Response::sendError("Action POST non valide ou manquante.", 400);
                    break;
            }
        } else {
            Response::sendError("Method Not Allowed", 405);
        }
    }

    // --- NOUVELLES FONCTIONS DU CONTRÔLEUR ---
    private function getPassingStudents() {
        try {
            $data = $this->model->getPassingStudents();
            Response::sendSuccess("Étudiants passants récupérés.", $data);
        } catch (Exception $e) {
            Response::sendError($e->getMessage(), 500);
        }
    }

    private function getGraduatedStudents() {
        $searchNom = $_GET['nom'] ?? null;
        $searchCne = $_GET['cne'] ?? null;
        try {
            $data = $this->model->getGraduatedStudents($searchNom, $searchCne);
            Response::sendSuccess("Étudiants diplômés récupérés.", $data);
        } catch (Exception $e) {
            Response::sendError($e->getMessage(), 500);
        }
    }
    // --- Fonctions du contrôleur (certaines sont nouvelles ou modifiées) ---

    private function getStudentsToReEnroll() {
        try {
            $data = $this->model->getStudentsToReEnroll();
            // Le JS attend un objet `data` contenant les deux listes
            Response::sendSuccess("Étudiants à réinscrire récupérés.", $data);
        } catch (Exception $e) {
            Response::sendError($e->getMessage(), 500);
        }
    }

    private function getStudentsReadyForNextCycle() {
        try {
            $students = $this->model->getStudentsReadyForNextCycle();
            Response::sendSuccess("Étudiants prêts pour le cycle suivant récupérés.", $students);
        } catch (Exception $e) {
            Response::sendError($e->getMessage(), 500);
        }
    }

    private function getFieldsForCycle(int $cycleId) {
        if ($cycleId === 0) { Response::sendError("ID de cycle manquant.", 400); return; }
        try {
            $data = $this->model->getFieldsForCycle($cycleId);
            Response::sendSuccess("Filières récupérées.", $data);
        } catch (Exception $e) {
            Response::sendError($e->getMessage(), 500);
        }
    }

    private function getStudentHistory(int $studentId) {
        if ($studentId === 0) { Response::sendError("ID de l'étudiant manquant.", 400); return; }
        try {
            $data = $this->model->getStudentHistory($studentId);
            Response::sendSuccess("Historique de l'étudiant récupéré.", $data);
        } catch (Exception $e) {
            Response::sendError($e->getMessage(), 500);
        }
    }

    private function processReEnrollment(array $data) {
        $studentId = $data["student_id"] ?? null;
        $newFieldId = $data["new_field_id"] ?? null;

        if (!$studentId) {
            Response::sendError("ID de l'étudiant manquant.", 400);
            return;
        }

        try {
            // On passe les IDs à la fonction du modèle
            $result = $this->model->processStudentProgression((int)$studentId, $newFieldId ? (int)$newFieldId : null);
            Response::sendSuccess($result['message'], $result);
        } catch (Exception $e) {
            Response::sendError($e->getMessage(), 500);
        }
    }

    // NOUVELLE FONCTION pour gérer l'inscription en masse
    private function processBulkEnrollment(array $data) {
    $studentIds = $data['student_ids'] ?? [];
    if (empty($studentIds)) {
        Response::sendError("Aucun ID d'étudiant fourni.", 400);
        return;
    }

    try {
        // On appelle une nouvelle fonction dédiée dans le modèle
        $result = $this->model->enrollPassingStudentsInBulk($studentIds);
        
        if ($result['success_count'] > 0) {
            $message = "{$result['success_count']} étudiant(s) inscrits avec succès.";
            if (!empty($result['errors'])) {
                $message .= " Erreurs: " . count($result['errors']);
            }
            Response::sendSuccess($message, ['errors' => $result['errors']]);
        } else {
            Response::sendError("Aucun étudiant n'a pu être inscrit.", 500, ['errors' => $result['errors']]);
        }

    } catch (Exception $e) {
        // Erreur générale si la transaction échoue
        Response::sendError("Une erreur critique est survenue: " . $e->getMessage(), 500);
    }
}
}
