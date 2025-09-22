

<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/AdminController2.php';


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $controller = new AdminController2();

    // Get request method and action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);



//-------------**Douaa Parts   


    // Récupération et nettoyage des paramètres
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
    $fieldId = filter_input(INPUT_GET, 'field_id', FILTER_VALIDATE_INT);
    $sectionId = filter_input(INPUT_GET, 'section_id', FILTER_VALIDATE_INT);
    $groupId = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);
    $depart_id=filter_input(INPUT_GET, 'depart_id', FILTER_VALIDATE_INT);
    $prof_id=filter_input(INPUT_GET, 'prof_id', FILTER_VALIDATE_INT);
    $cycle_id=filter_input(INPUT_GET, 'cycle_id', FILTER_VALIDATE_INT);
    $nomFili = filter_input(INPUT_GET, 'nomFili', FILTER_SANITIZE_SPECIAL_CHARS);
    $anneeAccreditation=filter_input(INPUT_GET, 'annee', FILTER_SANITIZE_SPECIAL_CHARS);
    $nomDepart= filter_input(INPUT_GET, 'nomDepart', FILTER_SANITIZE_SPECIAL_CHARS);

    $module_id=filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT);
    $codeMod=filter_input(INPUT_GET, 'codeMod', FILTER_SANITIZE_SPECIAL_CHARS);
    $nomMod= filter_input(INPUT_GET, 'nomMod', FILTER_SANITIZE_SPECIAL_CHARS);
    $element_id=filter_input(INPUT_GET, 'element_id', FILTER_VALIDATE_INT);

    $coeff_cc=filter_input(INPUT_GET, 'coeff_cc', FILTER_VALIDATE_FLOAT);
    $coeff_ecrit=filter_input(INPUT_GET, 'coeff_ecrit', FILTER_VALIDATE_FLOAT);
    $coeff_element=filter_input(INPUT_GET, 'coeff_element', FILTER_VALIDATE_FLOAT);
    $coeff_tp=filter_input(INPUT_GET, 'coeff_tp', FILTER_VALIDATE_FLOAT);
    $ref_filiere = filter_input(INPUT_GET, 'filiere', FILTER_SANITIZE_SPECIAL_CHARS);

    $dateDebut=filter_input(INPUT_GET, 'dateDebut', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateFin=filter_input(INPUT_GET, 'dateFin', FILTER_SANITIZE_SPECIAL_CHARS);
    $role=filter_input(INPUT_GET, 'role', FILTER_SANITIZE_SPECIAL_CHARS);

    // $jsonData = filter_input(INPUT_GET, 'data', FILTER_SANITIZE_SPECIAL_CHARS);
    // $decodedData = json_decode($jsonData, true); // true = associative array

    // if (json_last_error() !== JSON_ERROR_NONE) {
    //     throw new Exception('Erreur lors du décodage JSON : ' . json_last_error_msg(), 400);
    // }

    if ($ref_filiere === 'null') {
        $ref_filiere = null;
    }
    $ref_semestre = filter_input(INPUT_GET, 'semestre', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($ref_semestre === 'null') {
        $ref_semestre = null;
    }
    $ref_prof_element = filter_input(INPUT_GET, 'prof_element', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($ref_prof_element === 'null') {
        $ref_prof_element = null;
    }
    $ref_prof_tp = filter_input(INPUT_GET, 'prof_tp', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($ref_prof_tp === 'null') {
        $ref_prof_tp = null;
    }






    // Route based on action
    switch ($action) {
        
        case "getFilieres":
                    $cycle_id = (!empty($_GET['cycle_id'])) ? $_GET['cycle_id'] : null;
                    $diplome_id = (!empty($_GET['diplome_id'])) ? $_GET['diplome_id'] : null;
                    $departement_id = (!empty($_GET['departement_id'])) ? $_GET['departement_id'] : null;
                    $status = (!empty($_GET['status'])) ? $_GET['status'] : null;

                    $result = $controller->getFilieres($cycle_id, $diplome_id, $departement_id, $status);
                    // error_log(print_r($result, true));
                    // exit;

                    echo json_encode($result);
                    break;


       case 'getFilieresByCycle':
            if (!$cycle_id) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Paramètre "cycle_id" requis']);
                exit;
            }
            $result = $controller->getFilieresByCycle($cycle_id);
            echo json_encode($result);
            break;

            case 'deleteFili':
                if (!$fieldId) {
                    throw new Exception('Paramètre "field_id" requis', 400);
                }
                $controller->deleteFiliere($fieldId);
                break ;
            case 'GetAllCycle':
                $controller->GetAllCycle();
                break ;
            case 'GetAllRegularProffessors':
                $controller->GetAllRegularProffessors($role,$depart_id);
                break ;
            case 'GetAllDepartment':
                $controller->GetAllDepartment();
                break ;
           

            case 'getFiliereById': 
                if (!$fieldId) {
                    throw new Exception('Paramètre "field_id" requis', 400);
                }
                $controller->getFiliereById($fieldId);  
                break; 

            case 'YEARS': 
                $controller->YEARS();  
                break; 
            case 'GetAllDiplome': 
                $controller->GetAllDiplome();  
                break; 
             
            
            case "getAllDepart" :  
                $controller->getAllDepart();
                break ; 
            case "deleteDepart" :
                if (!$depart_id ) {
                    throw new Exception("Paramètres requis pour l'ajout d'une département manquants", 400);
                }
                $controller->deleteDepart($depart_id);
                break ; 
            case "AjouterDepart" : 
                if (!$nomDepart) {
                    throw new Exception("Paramètres requis pour l'ajout d'une filière manquants", 400);
                }
                $controller->AjouterDepart($nomDepart);
                break ; 
            case "updateDepart":
                if (!$depart_id || !$nomDepart || !$prof_id || !$anneeAccreditation || !$dateDebut || !$dateFin) {
                    throw new Exception("Paramètres requis pour la modification du département manquants", 400);
                }
                $controller->updateDepart($depart_id, $nomDepart, $prof_id, $anneeAccreditation,$dateDebut,$dateFin);
                break;
            case "infoModules" :
                $controller->infoModules();
                break ; 
            case 'getModules':
                $controller->getModules($anneeAccreditation, $fieldId, $ref_semestre);
                break;
            case 'getFilieresByYear':
                $controller->getFilieresByYear($anneeAccreditation);
                break;

            
            case 'GetAllProffessors':
                $controller->GetAllProffessors();
                break ;
            case "getNombreSemestresByFiliere":
                $controller->getNombreSemestresByFiliere($fieldId);
                break;
           
           
            case 'deleteModule': 
                if (!$module_id) {
                    throw new Exception("Paramètres requis manquants", 400);
                }
                $controller->deleteModule($module_id);
                break ; 
            case 'deleteElement': 
                if (!$element_id ) {
                    throw new Exception("Paramètres requis manquants", 400);
                }
                $controller->deleteElement($element_id);
                break ; 
        

          
            case 'AjouterFiliere':
                    $jsonData = file_get_contents('php://input');
                    $decodedData = json_decode($jsonData, true);

                    if (!$decodedData || json_last_error() !== JSON_ERROR_NONE) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Erreur JSON : ' . json_last_error_msg()]);
                        exit;
                    }

                    $controller->AjouterFiliere1($decodedData);
                    break;
            case 'UpdateFiliere':
                    $jsonData = file_get_contents('php://input');
                    $decodedData = json_decode($jsonData, true);

                    if (!$decodedData || json_last_error() !== JSON_ERROR_NONE) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Erreur JSON : ' . json_last_error_msg()]);
                        exit;
                    }

                    $controller->UpdateFiliere($decodedData);
                    break;
            case 'AjouterModules':
                    $input = json_decode(file_get_contents("php://input"), true);
                    if (!$input || empty($input['filiere_id']) || !isset($input['modules'])) {
                        echo json_encode(["status" => "error", "message" => "Paramètres manquants"]);
                        exit;
                    }
                    $controller->AjouterModules($input);
                    echo json_encode(["status" => "success", "message" => "Modules ajoutés avec succès",
                        ]);
                    break;

            case 'updateModule':
                    $raw = json_decode(file_get_contents("php://input"), true);

                    // Unwrap module object if it's sent inside "modules"
                    if (!empty($raw['modules']) && is_array($raw['modules'])) {
                        $data = $raw['modules'][0];
                        $data['filiere_id'] = $raw['filiere_id']; // merge filiere_id
                    } else {
                        $data = $raw;
                    }

                    $result = $controller->updateModule($data);
                    echo json_encode($result);
                    break;




           


        default:
            throw new Exception('Action non reconnue', 404);
    }
} catch (Exception $e) {
    // http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode() ?: 500
    ]);
}


?>
