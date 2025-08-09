<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/AdminController2.php';

require_once __DIR__ . '/controllers/NoteController.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $controller = new AdminController2();

    // Get request method and action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);



//-------------**********************Douaa Parts   


    // Récupération et nettoyage des paramètres
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
    $fieldId = filter_input(INPUT_GET, 'field_id', FILTER_VALIDATE_INT);
    $sectionId = filter_input(INPUT_GET, 'section_id', FILTER_VALIDATE_INT);
    $groupId = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);
    $depart_id=filter_input(INPUT_GET, 'depart_id', FILTER_VALIDATE_INT);
    $prof_id=filter_input(INPUT_GET, 'prof_id', FILTER_VALIDATE_INT);
    $cycle_id=filter_input(INPUT_GET, 'cycle_id', FILTER_VALIDATE_INT);
    $nomFili = filter_input(INPUT_GET, 'nomFili', FILTER_SANITIZE_STRING);
    $anneeAccreditation=filter_input(INPUT_GET, 'annee', FILTER_SANITIZE_STRING);
    $nomDepart= filter_input(INPUT_GET, 'nomDepart', FILTER_SANITIZE_STRING);

    $module_id=filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT);
    $codeMod=filter_input(INPUT_GET, 'codeMod', FILTER_SANITIZE_STRING);
    $nomMod= filter_input(INPUT_GET, 'nomMod', FILTER_SANITIZE_STRING);
    $element_id=filter_input(INPUT_GET, 'element_id', FILTER_VALIDATE_INT);

    $coeff_cc=filter_input(INPUT_GET, 'coeff_cc', FILTER_VALIDATE_FLOAT);
    $coeff_ecrit=filter_input(INPUT_GET, 'coeff_ecrit', FILTER_VALIDATE_FLOAT);
    $coeff_element=filter_input(INPUT_GET, 'coeff_element', FILTER_VALIDATE_FLOAT);
    $coeff_tp=filter_input(INPUT_GET, 'coeff_tp', FILTER_VALIDATE_FLOAT);
    $ref_filiere = filter_input(INPUT_GET, 'filiere', FILTER_SANITIZE_STRING);

    $dateDebut=filter_input(INPUT_GET, 'dateDebut', FILTER_SANITIZE_STRING);
    $dateFin=filter_input(INPUT_GET, 'dateFin', FILTER_SANITIZE_STRING);
    // $jsonData = filter_input(INPUT_GET, 'data', FILTER_SANITIZE_STRING);
    // $decodedData = json_decode($jsonData, true); // true = associative array

    // if (json_last_error() !== JSON_ERROR_NONE) {
    //     throw new Exception('Erreur lors du décodage JSON : ' . json_last_error_msg(), 400);
    // }

    if ($ref_filiere === 'null') {
        $ref_filiere = null;
    }
    $ref_semestre = filter_input(INPUT_GET, 'semestre', FILTER_SANITIZE_STRING);
    if ($ref_semestre === 'null') {
        $ref_semestre = null;
    }
    $ref_prof_element = filter_input(INPUT_GET, 'prof_element', FILTER_SANITIZE_STRING);
    if ($ref_prof_element === 'null') {
        $ref_prof_element = null;
    }
    $ref_prof_tp = filter_input(INPUT_GET, 'prof_tp', FILTER_SANITIZE_STRING);
    if ($ref_prof_tp === 'null') {
        $ref_prof_tp = null;
    }






    // Route based on action
    switch ($action) {
        case 'getFilieres':
            // validateMethod($method, 'GET');
            $controller->getAllFilieres();
            break;

        case 'Filiere':
                $controller->Filiere();
                break;

            case 'getSections':
                if (!$fieldId) {
                    throw new Exception('Paramètre "field_id" requis', 400);
                }
                $controller->getSectionsByFiliere($fieldId);
                break;

            case 'getGroupesBySection':
                if (!$sectionId) {
                    throw new Exception('Paramètre "section_id" requis', 400);
                }
                $controller->getGroupesBySection($sectionId);
                break;

            case 'getGroupesByFiliere':
                if (!$fieldId) {
                    throw new Exception('Paramètre "field_id" requis', 400);
                }
                $controller->getGroupesByFiliere($fieldId);
                break;

            case 'getStudents':
                $controller->getFilteredStudents($fieldId, $sectionId, $groupId);
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
                $controller->GetAllRegularProffessors();
                break ;
            case 'GetAllDepartment':
                $controller->GetAllDepartment();
                break ;
            case 'AjouterFiliere':
                $jsonData = file_get_contents('php://input');
                $decodedData = json_decode($jsonData, true);

                if (!$decodedData || json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Erreur JSON : ' . json_last_error_msg()]);
                    exit;
                }

                $nomFili = $decodedData['nom'] ?? null;
                $depart_id = $decodedData['depart_id'] ?? null;
                $prof_id = $decodedData['prof_id'] ?? null;
                $cycle_id = $decodedData['cycle_id'] ?? null;

                if (!$nomFili || !$depart_id || !$cycle_id || !$prof_id) {
                    throw new Exception("Champs obligatoires manquants", 400);
                }

                // Tu peux aussi envoyer $decodedData['sections'] ici
                $controller->AjouterFiliere($nomFili, $depart_id, $cycle_id, $prof_id, $decodedData['sections']);
                break;

            case 'getFiliereById': 
                if (!$fieldId) {
                    throw new Exception('Paramètre "field_id" requis', 400);
                }
                $controller->getFiliereById($fieldId);  // ici $fieldId, pas $field_id
                break; 

            case 'YEARS': 
                $controller->YEARS();  
                break; 
            case "updateFiliere":
                if (!$nomFili || !$depart_id || !$cycle_id || !$prof_id) {
                    throw new Exception("Paramètres requis pour l'ajout d'une filière manquants", 400);
                }
                $controller->updateFiliere($fieldId, $nomFili, $depart_id, $prof_id, $cycle_id, $anneeAccreditation);
                break ; 
            
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
                if (!$nomDepart && ! $prof_id && !$dateDebut && !$dateFin) {
                    throw new Exception("Paramètres requis pour l'ajout d'une filière manquants", 400);
                }
                $controller->AjouterDepart($nomDepart,$prof_id,$dateDebut,$dateFin);
                break ; 
            case "updateDepart":
                if (!$depart_id || !$nomDepart || !$prof_id || !$anneeAccreditation) {
                    throw new Exception("Paramètres requis pour la modification du département manquants", 400);
                }
                $controller->updateDepart($depart_id, $nomDepart, $prof_id, $anneeAccreditation);
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

            case "AjouterModuleM1":
                // Attention : 0.0 est "falsey" donc on teste explicitement === false || === null
                if (
                    !$codeMod || !$nomMod ||
                    $coeff_cc === false || $coeff_cc === null ||
                    $coeff_ecrit === false || $coeff_ecrit === null ||
                    $coeff_element === false || $coeff_element === null ||
                    $coeff_tp === false || $coeff_tp === null ||
                    $ref_filiere === null || $ref_semestre === null || $ref_prof_element === null 
                ){
                    throw new Exception("Paramètres manquants ou invalides", 400);
                }

                // Vérifier que coefficients sont entre 0 et 1
                foreach ([$coeff_cc, $coeff_ecrit, $coeff_element, $coeff_tp] as $c) {
                    if ($c < 0 || $c > 1) {
                        throw new Exception("Chaque coefficient doit être entre 0 et 1", 400);
                    }
                }

                $controller->AjouterModuleM1($codeMod, $nomMod, $coeff_cc, $coeff_ecrit, $coeff_element, $coeff_tp, $ref_filiere, $ref_semestre, $ref_prof_element, $ref_prof_tp);
                break;
            case 'GetAllProffessors':
                $controller->GetAllProffessors();
                break ;
            case "getNombreSemestresByFiliere":
                $controller->getNombreSemestresByFiliere($fieldId);
                break;
            case 'AjouterModuleAvecElements':
                    $jsonData = file_get_contents('php://input');
                    $decodedData = json_decode($jsonData, true);

                    if (!$decodedData || json_last_error() !== JSON_ERROR_NONE) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Erreur lors du décodage JSON : ' . json_last_error_msg()]);
                        exit;
                    }

                    $controller->AjouterModuleAvecElements($decodedData);
                    break;
           case 'ModifierModuleAvecElement':
                    $jsonData = file_get_contents('php://input');
                    $decodedData = json_decode($jsonData, true);

                    if (!$decodedData || json_last_error() !== JSON_ERROR_NONE) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Erreur lors du décodage JSON : ' . json_last_error_msg()]);
                        exit;
                    }

                    $controller->ModifierModuleAvecElement($decodedData);
                    break;

            case 'deleteModule': 
                if (!$module_id ) {
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
        

            case 'UpdateModuleSansElements':
                    try {
                        if (!$module_id || !$codeMod || !$nomMod || $coeff_cc === false || $coeff_ecrit === false ||
                            $coeff_element === false || $coeff_tp === false || !$ref_filiere || !$ref_semestre || !$ref_prof_element) {
                            throw new Exception("Paramètres manquants ou invalides", 400);
                        }

                        // Traitement du prof TP
                        if ($ref_prof_tp === 'null' || $ref_prof_tp === null || $ref_prof_tp === '') {
                            $ref_prof_tp = null;
                        } else {
                            $ref_prof_tp = intval($ref_prof_tp);
                        }

                        $controller->UpdateModuleSansElements(
                            $module_id,
                            $codeMod,
                            $nomMod,
                            $coeff_cc,
                            $coeff_ecrit,
                            $coeff_element,
                            $coeff_tp,
                            $ref_filiere,
                            $ref_semestre,
                            $ref_prof_element,
                            $ref_prof_tp
                        );

                        // Réponse JSON
                        echo json_encode(["status" => "success", "message" => "Module modifié avec succès"]);
                        exit;

                    } catch (Exception $e) {
                        http_response_code(400);
                        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
                        exit;
                    }

           
            case 'UpdateModuleAvecElements':
                $controller->UpdateModuleAvecElements();
                break;
            case 'getFilieresByCycle':
                if ($cycle_id) {
                    $result = $controller->getFilieresByCycle($cycle_id);
                    echo json_encode($result);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'ID du cycle manquant ou invalide']);
                }
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
