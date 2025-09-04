
<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/AdminMiddleware.php';
require_once __DIR__ . '/../models/AdminModel2.php';
require_once __DIR__ . '/../models/AdminModel.php';

require_once __DIR__ . '/../utils/Response.php';

class AdminController2 {
    private $model;
    private $authMiddleware;
    private $adminMiddleware;
    private $response;

    public function __construct() {
        $this->model = new AdminModel2();
        $this->authMiddleware = new AuthMiddleware();
        $this->adminMiddleware = new AdminMiddleware();
        $this->response = new Response();
    }


/////////////////////////////////////////////////////////////////////////Douaaa Parts ////////////////////////////////
/******************------------------!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!DOUAAAAAAAAAAAAAAAAAAAAAAAAA */
    

    

    public function getAllFilieres(){
            $this->authMiddleware->verifySession();
            $result= $this->adminMiddleware->verifyAdmin();
            $filieres = $this->model->getFilieres();
            $this->response->sende(200, [
                "user"=>$result || null,
                'status' => 'success',
                'data' => $filieres
            ]);
    }
    public function AjouterFiliere($nom, $depart_id, $cycle_id, $prof_id, $sections = []) {
        $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
                    try {
                        $result = $this->model->AjouterFiliere($nom, $depart_id, $cycle_id, $prof_id, $sections);

                        if ($result) {
                            echo json_encode(["message" => "Filière créée avec succès"]);
                        } else {
                            http_response_code(500);
                            echo json_encode(["message" => "Erreur lors de la création de la filière"]);
                        }
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(["message" => $e->getMessage()]);
                    }
                }
    

            public function deleteFiliere($fieldId){
                $this->authMiddleware->verifySession();
                        $this->adminMiddleware->verifyAdmin();
                error_log(" deleteFiliere called with ID = $fieldId");

                $this->model = new AdminModel2();
                $result = $this->model->deleteField($fieldId);

                error_log(" Result of deleteField = " . var_export($result, true));

                if ($result) {
                    $this->response->sende(200, ['success' => true, 'message' => 'Filière supprimée avec succès']);
                } else {
                    $this->response->sende(500, ['success' => false, 'message' => 'Échec de la suppression de la filière']);
                }
            }
public function getNombreSemestresByFiliere($filiere_id)
{
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    $this->model = new AdminModel2();

    if (!$filiere_id || !is_numeric($filiere_id)) {
        return $this->response->sende(400, [
            'status' => 'error',
            'message' => 'ID de filière invalide'
        ]);
    }

    $result = $this->model->getNombreSemestresByFiliere($filiere_id);

    if ($result && isset($result['Nombre_semestre'])) {
        return $this->response->sende(200, [
            'status' => 'success',
            'nombre_semestre' => intval($result['Nombre_semestre'])
        ]);
    } else {
        return $this->response->sende(404, [
            'status' => 'error',
            'message' => 'Aucun nombre de semestres trouvé pour cette filière'
        ]);
    }
}

            public function getFilieresByCycle($cycle_id) {
                $this->authMiddleware->verifySession();
                $this->adminMiddleware->verifyAdmin();
                return $this->model->getFilieresByCycle($cycle_id);
            }





            public function GetAllProffessors()
            {   $this->authMiddleware->verifySession();
                        $this->adminMiddleware->verifyAdmin();
                $this->model = new AdminModel2();
                $profs = $this->model->GetAllProffessors();

                if ($profs && count($profs) > 0) {
                    return $this->response->sende(200, [
                        'status' => 'success',
                        'data' => $profs
                    ]);
                } else {
                    return $this->response->sende(404, [
                        'status' => 'error',
                        'message' => 'Aucun professeur trouvé'
                    ]);
                }
            }




        public function deleteModule($module_id) {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
            $result = $this->model->deleteModule($module_id);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Module supprimé avec succès.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du module.']);
            }
        }



public function deleteElement($element_id) {
    $this->authMiddleware->verifySession();
    $this->adminMiddleware->verifyAdmin();
    $result = $this->model->deleteElement($element_id);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Élément supprimé avec succès.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'élément.']);
    }
}

    public function GetAllRegularProffessors($role){
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $professeurs = $this->model->GetAllRegularProffessors($role);
        $this->response->sende(200, $professeurs);
    }

    public function GetAllDepartment(){
        $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
        $departments = $this->model->GetAllDepartment();
        $this->response->sende(200, $departments);
    }

    public function GetAllCycle(){
        $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
        $cycles = $this->model->GetAllCycle();
        $this->response->sende(200, $cycles);
    }

    public function YEARS(){
        $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
        $years = $this->model->YEARS();
        $this->response->sende(200, $years);
    }
    

     public function GetAllDiplome(){
        $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
        $diplomes = $this->model->GetAllDiplome();
        $this->response->sende(200, $diplomes);
    }

    public function getFiliereById($fieldId) {
        $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
        return $this->model->getFiliereById($fieldId);
    }

    public function getAllDepart(){
        $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
        $departments = $this->model->getAllDepart();
        $this->response->sende(200, [
            'status' => 'success',
            'data' => $departments
        ]);
    }

    public function deleteDepart($depart_id){
        $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
        $this->model = new AdminModel2();
        $result = $this->model->deleteDepart($depart_id);

        if ($result) {
            $this->response->sende(200, ['success' => true, 'message' => 'Département supprimé avec succès']);
        } else {
            $this->response->sende(500, ['success' => false, 'message' => 'Echec de la suppression du département']);
        }
    }

    public function AjouterDepart($nom,$profId,$dateDebut,$dateFin) {
                    $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
                    if (empty($dateDebut) || empty($dateFin) || empty($nom) || !is_numeric($profId)) {
                        $this->response->sende(400, ['message' => $dateDebut.$dateFin.'Données invalides']);
                        return;
                    }

                    $result = $this->model->AjouterDepart($nom,$profId,$dateDebut,$dateFin);

                    if ($result) {
                        $this->response->sende(201, ['message' => 'Département créée avec succès']);
                    } else {
                        $this->response->sende(500, ['message' => 'Erreur lors de la création du département']);
                    }
                            }

    public function updateDepart($departementId ,$nom,$profId,$anneeAccreditation,$dateDebut,$dateFin){
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $result = $this->model->updateDepart(
            $departementId,
            $nom,
            $profId,
            $anneeAccreditation,
            $dateDebut,
            $dateFin
        );

        if ($result) {
            $this->response->sende(201, ['message' => 'Département modifié avec succès']);
        } else {
            $this->response->sende(500, ['message' => 'Erreur lors de la modification du département']);
        }
    }

    public function infoModules(){
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $modules = $this->model->infoModules();
        $this->response->sende(200, [
            'status' => 'success',
            'data' => $modules
        ]);
    }

    

    public function getModules($annee = null, $fieldId = null, $semestreId = null){
        $this->authMiddleware->verifySession();
        $this->adminMiddleware->verifyAdmin();
        $modules = $this->model->getModules($annee, $fieldId, $semestreId);
        $this->response->sende(200, [
            'status' => 'success',
            'data' => $modules
        ]);
    }

        public function getFilieresByYear($anneeAccreditation){
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
            $filieres = $this->model->getFilieresByYear($anneeAccreditation);

            if ($filieres && count($filieres) > 0) {
                echo json_encode([
                    'status' => 'success',
                    'data' => $filieres
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Aucune filière trouvée pour cette année'
                ]);
            }
        }



        public function AjouterFiliere1($data) {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
            // Vérifier les champs obligatoires
            $required = ['nom','depart_id','prof_id','cycle_id','diplome',
                        'date_debut_affectation','date_fin_affectation',
                        'annee_debut_accreditation','annee_fin_accreditation','statut'];

            foreach ($required as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode(['status'=>'error','message'=>"Champ manquant : $field"]);
                    return;
                }
            }

            try {
                $success = $this->model->AjouterFiliere1($data);

                if ($success) {
                    http_response_code(200);
                    echo json_encode(['status'=>'success','message'=>'Filière créée avec succès']);
                } else {
                    http_response_code(500);
                    echo json_encode(['status'=>'error','message'=>'Erreur lors de la création']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['status'=>'error','message'=>'Erreur BD : '.$e->getMessage()]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['status'=>'error','message'=>'Erreur : '.$e->getMessage()]);
            }
        }

        public function UpdateFiliere($data) {
            // Vérifier les champs obligatoires
            $required = ['fieldId','nom','depart_id','prof_id','cycle_id','diplome',
                        'date_debut_affectation','date_fin_affectation',
                        'annee_debut_accreditation','annee_fin_accreditation','statut'];

            foreach ($required as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode(['status'=>'error','message'=>"Champ manquant : $field"]);
                    return;
                }
            }

            try {
                $success = $this->model->UpdateFiliere($data);

                if ($success) {
                    http_response_code(200);
                    echo json_encode(['status'=>'success','message'=>'Filière modifié avec succès']);
                } else {
                    http_response_code(500);
                    echo json_encode(['status'=>'error','message'=>'Erreur lors de la modification']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['status'=>'error','message'=>'Erreur BD : '.$e->getMessage()]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['status'=>'error','message'=>'Erreur : '.$e->getMessage()]);
            }
        }


    public function getFilieres($cycle_id = null, $diplome_id = null, $departement_id = null, $status = null) {
                $this->authMiddleware->verifySession();
                $this->adminMiddleware->verifyAdmin();
                
        return $this->model->getFilieres($cycle_id, $diplome_id, $departement_id, $status);
    }

    public function AjouterModules($data) {
            $this->authMiddleware->verifySession();
                $this->adminMiddleware->verifyAdmin();
            return $this->model->AjouterModules($data);
        }


    public function updateModule($data) {
            $this->authMiddleware->verifySession();
            $this->adminMiddleware->verifyAdmin();
        return $this->model->updateModule($data);
    }

    


}




?>
