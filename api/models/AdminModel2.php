<?php
require_once __DIR__ . '/../utils/Database.php';

class AdminModel2
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }



  


    // ----------------what i added   Douaaaaaaaaaaa /////////////////////


    public function GetAllDepartment(){

        $stmt = $this->db->prepare("
            SELECT *
            FROM departements 
        ");
        $stmt->execute();
        return $stmt->fetchAll();

    }

    public function GetAllCycle(){
        $stmt = $this->db->prepare("
            SELECT *
            FROM cycles 
        ");
        $stmt->execute();
        return $stmt->fetchAll();

    }

    
    public function GetAllRegularProffessors(){
        $stmt = $this->db->prepare("
            SELECT p.*
            FROM professeurs p
            WHERE p.user_id NOT IN (
                SELECT user_id FROM professor_roles WHERE role = 'Chef_de_Departement'
            )

        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }


    public function YEARS(){
        $stmt = $this->db->prepare("
            SELECT *
            FROM annees_academiques
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function GetAllDiplome(){
        $stmt = $this->db->prepare("
            SELECT *
            FROM diplomes
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }


           public function getFiliereById($fieldId) {
                    $sql = "SELECT * FROM filieres WHERE field_id = :field_id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':field_id', $fieldId, PDO::PARAM_INT);
                    $stmt->execute();
                    $filiere = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($filiere) {
                        return [
                            'status' => 'success',
                            'data' => $filiere
                        ];
                    } else {
                        return [
                            'status' => 'error',
                            'message' => 'Filière non trouvée'
                        ];
                    }
           }

// public function updateFiliere($fieldId, $nom, $departementId, $professeurId, $cycleId, $anneeAccreditation) {
//     try {
//         $stmt = $this->db->prepare("
//             UPDATE filieres 
//             SET 
//                 nom = :nom,
//                 department_id = :department_id,
//                 head_professor_id = :head_professor_id,
//                 cycle_id = :cycle_id,
//                 annee_accreditation = :annee_accreditation
//             WHERE field_id = :field_id
//         ");

//         $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
//         $stmt->bindParam(':department_id', $departementId, PDO::PARAM_INT);
//         $stmt->bindParam(':head_professor_id', $professeurId, PDO::PARAM_INT);
//         $stmt->bindParam(':cycle_id', $cycleId, PDO::PARAM_INT);
//         $stmt->bindParam(':annee_accreditation', $anneeAccreditation, PDO::PARAM_STR);
//         $stmt->bindParam(':field_id', $fieldId, PDO::PARAM_INT);

//         return $stmt->execute();
//     } catch (PDOException $e) {
//         error_log("Error updating filière: " . $e->getMessage());
//         return false;
//     }

// }


                public function GetTeachersWithoutRole(){
                       $stmt=" select * from professeurs p JOIN filieres f ON f.head_professor_id = p.user_id   where " ;
                }


                public function getAllDepart(){


                    return $this->db->query("
                        SELECT 
                            d.date_debut,
                            d.date_fin,
                            d.department_id,
                            d.nom AS nom_departement,
                            CONCAT(chef.nom, ' ', chef.prenom) AS nom_chef_departement,
                            GROUP_CONCAT(DISTINCT f.nom SEPARATOR ', ') AS filieres_associees,
                            d.annee_accreditation
                        FROM departements d
                        LEFT JOIN professeurs chef ON chef.user_id = d.head_professor_id
                        LEFT JOIN filieres f ON f.department_id = d.department_id
                        LEFT JOIN professeurs p ON p.department_id = d.department_id
                        WHERE d.prof_actuel = 1
                        GROUP BY d.department_id, d.nom, chef.nom, chef.prenom
                        ORDER BY d.nom;
                    ")->fetchAll();

        }






        // public function getFilieres(){


        //             return $this->db->query("
        //                 SELECT 
        //                     f.field_id, 
        //                     f.nom AS nom_filiere,
        //                     d.department_id,
        //                     d.nom AS nom_departement, 
        //                     CONCAT(chef.nom, ' ', chef.prenom) AS nom_professeur_responsable, 
        //                     d.annee_accreditation,
        //                     c.nom  AS nom_cycle
        //                     FROM filieres f 
        //                     LEFT JOIN professeurs chef ON chef.user_id = f.head_professor_id
        //                     LEFT JOIN departements d ON d.department_id = f.department_id
        //                     LEFT JOIN cycles c ON c.cycle_id = f.`cycle_id`
        //                     ORDER BY f.nom;
        //             ")->fetchAll();

        // }


        public function getFilieresByCycle($cycle_id) {
                   $sql = 'SELECT 
                                f.field_id, 
                                f.nom AS nom_filiere,
                                d.department_id,
                                d.nom AS nom_departement, 
                                CONCAT(chef.nom, " ", chef.prenom) AS nom_professeur_responsable, 
                                f.annee_accreditation,
                                c.nom AS nom_cycle
                            FROM filieres f 
                            LEFT JOIN professeurs chef ON chef.user_id = f.head_professor_id
                            LEFT JOIN departements d ON d.department_id = f.department_id
                            LEFT JOIN cycles c ON c.cycle_id = f.cycle_id
                            WHERE f.cycle_id = :cycle_id
                            ORDER BY f.nom';

                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([':cycle_id' => $cycle_id]);
                    $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    return ['status' => 'success', 'data' => $filieres];
                }


        public function deleteDepart($depart_id) {
            $this->db->beginTransaction();

            try {
                $stmt = $this->db->prepare("DELETE FROM departements WHERE department_id = ?");
                $stmt->execute([$depart_id]);

                if ($stmt->rowCount() === 0) {
                    throw new PDOException("No filière found with department_id = $depart_id");
                }

                $this->db->commit();
                return true;
            } catch (PDOException $e) {
                $this->db->rollBack();
                error_log("Error deleting field: " . $e->getMessage());
                return false;
            }
    }

            public function AjouterDepart($nom,$profId,$dateDebut,$dateFin) {

                $stmt = $this->db->prepare("UPDATE professor_roles SET `role`='Chef_de_Departement' WHERE `user_id`= :user_id");
                $stmt->bindParam(':user_id', $profId, PDO::PARAM_INT);
                $stmt->execute();
                
                $stmt = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag = 1 LIMIT 1");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$row || !isset($row['annee_id'])) {
                    error_log("No current academic year found.");
                    return false;
                }

                $anneeId = $row['annee_id'];  // This is a string like "2023-2024"

                $sql = "INSERT INTO departements (nom, head_professor_id, annee_accreditation , date_debut, date_fin, prof_actuel)
                        VALUES (:nom,  :head_professor_id,  :annee_accreditation, :date_debut , :date_fin , 1 )";
                $stmt = $this->db->prepare($sql);

                $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
                $stmt->bindParam(':head_professor_id', $profId, PDO::PARAM_INT);
                $stmt->bindParam(':annee_accreditation', $anneeId, PDO::PARAM_STR);  // <-- bind as string
                $stmt->bindParam(':date_debut', $dateDebut, PDO::PARAM_STR);  // <-- bind as string
                $stmt->bindParam(':date_fin', $dateFin, PDO::PARAM_STR);  // <-- bind as string

                return $stmt->execute();
            }

            public function updateDepart($departementId, $nom, $profId, $anneeAccreditation) {
                    try {
                        $stmt = $this->db->prepare("
                            UPDATE departements 
                            SET 
                                nom = :nom,
                                head_professor_id = :head_professor_id,
                                annee_accreditation = :annee_accreditation
                            WHERE department_id = :department_id
                        ");

                        $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
                        $stmt->bindParam(':department_id', $departementId, PDO::PARAM_INT);
                        $stmt->bindParam(':head_professor_id', $profId, PDO::PARAM_INT);
                        $stmt->bindParam(':annee_accreditation', $anneeAccreditation, PDO::PARAM_STR);

                        return $stmt->execute();
                    } catch (PDOException $e) {
                        error_log("Error updating department: " . $e->getMessage());
                        return false;
                    }
                }

                

       
    public function AjouterModuleM1(
    $codeMod, $nomMod, $coeff_cc, $coeff_ecrit, $coeff_element, $coeff_tp,
    $ref_filiere, $ref_semestre, $ref_prof_element, $ref_prof_tp
) {
    // 1. Récupérer l'année académique courante
    $stmt = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !isset($row['annee_id'])) {
        error_log("Aucune année académique courante trouvée.");
        return false;
    }

    $anneeId = $row['annee_id'];

    // 2. Insérer le module avec Ref_filiere et Ref_semestre
    $sqlModule = "INSERT INTO modules (code, nom, annee_id, field_id, semestre_id) 
                  VALUES (:code, :nom, :annee_id, :filiere, :semestre)";
    $stmtModule = $this->db->prepare($sqlModule);
    $stmtModule->bindParam(':code', $codeMod);
    $stmtModule->bindParam(':nom', $nomMod);
    $stmtModule->bindParam(':annee_id', $anneeId);
    $stmtModule->bindParam(':filiere', $ref_filiere);
    $stmtModule->bindParam(':semestre', $ref_semestre);

    if (!$stmtModule->execute()) {
        error_log("Échec de l'insertion du module.");
        return false;
    }

    $moduleId = $this->db->lastInsertId();

    // 3. Insérer l'élément lié avec les profs
    $sqlElement = "INSERT INTO elements (
                        nom, module_id, coeff_element, coeff_ecrit, coeff_cc, coeff_tp,
                        Ref_prof_element, Ref_prof_tp
                    ) 
                    VALUES (
                        :nom, :module_id, :coeff_element, :coeff_ecrit, :coeff_cc, :coeff_tp,
                        :prof_element, :prof_tp
                    )";
    $stmtElement = $this->db->prepare($sqlElement);
    $stmtElement->bindParam(':nom', $nomMod); // même nom que le module
    $stmtElement->bindParam(':module_id', $moduleId);
    $stmtElement->bindParam(':coeff_element', $coeff_element);
    $stmtElement->bindParam(':coeff_ecrit', $coeff_ecrit);
    $stmtElement->bindParam(':coeff_cc', $coeff_cc);
    $stmtElement->bindParam(':coeff_tp', $coeff_tp);
    $stmtElement->bindParam(':prof_element', $ref_prof_element);
    $stmtElement->bindParam(':prof_tp', $ref_prof_tp);

    return $stmtElement->execute();
}


                public function GetAllProffessors(){

                    $stmt = $this->db->prepare("
                            SELECT *
                            FROM professeurs 
                        ");
                        $stmt->execute();
                        return $stmt->fetchAll();

                }
               public function getNombreSemestresByFiliere($filiere_id)
                    {
                        $query = "SELECT c.Nombre_semestre FROM filieres f 
                                  JOIN cycles c ON c.cycle_id = f.cycle_id
                                  WHERE f.field_id = :filiere_id";

                        $stmt = $this->db->prepare($query);
                        $stmt->bindParam(':filiere_id', $filiere_id, PDO::PARAM_INT);
                        $stmt->execute();

                        return $stmt->fetch(PDO::FETCH_ASSOC);
                    }


                   public function getFilieresByYear($anneeAccreditation)
                        {
                            $query = "SELECT * FROM filieres WHERE annee_accreditation = :annee_id";
                            $stmt = $this->db->prepare($query);

                            $stmt->bindParam(':annee_id', $anneeAccreditation, PDO::PARAM_STR);
                            $stmt->execute();

                            return $stmt->fetchAll(PDO::FETCH_ASSOC);  // récupérer toutes les filières
                        }



                       public function AjouterModuleAvecElements(
    $codeMod, $nomMod, $ref_filiere, $ref_semestre,
    $elements = []
) {
    // Récupération année courante
    $stmt = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;

    $anneeId = $row['annee_id'];

    // Insertion module
    $sqlModule = "INSERT INTO modules (code, nom, annee_id, field_id, semestre_id   ) 
                  VALUES (:code, :nom, :annee_id, :filiere, :semestre)";
    $stmtModule = $this->db->prepare($sqlModule);
    $stmtModule->execute([
        ':code' => $codeMod,
        ':nom' => $nomMod,
        ':annee_id' => $anneeId,
        ':filiere' => $ref_filiere,
        ':semestre' => $ref_semestre
    ]);
    $moduleId = $this->db->lastInsertId();

    // Insertion des éléments
    $sqlElement = "INSERT INTO elements (
        nom, module_id, coeff_element, coeff_ecrit, coeff_cc, coeff_tp,
        Ref_prof_element, Ref_prof_tp
    ) VALUES (
        :nom, :module_id, :coeff_element, :coeff_ecrit, :coeff_cc, :coeff_tp,
        :prof_element, :prof_tp
    )";
    $stmtElement = $this->db->prepare($sqlElement);

    foreach ($elements as $el) {
        // Ici on utilise les valeurs spécifiques à chaque élément
        $stmtElement->execute([
            ':nom' => $el['nom'],
            ':module_id' => $moduleId,
            ':coeff_element' => $el['coeff_element'],
            ':coeff_ecrit' => $el['coeff_ecrit'],
            ':coeff_cc' => $el['coeff_cc'],
            ':coeff_tp' => $el['coeff_tp'],
            ':prof_element' => $el['prof_element'],  // <- modif
            ':prof_tp' => $el['prof_tp']             // <- modif
        ]);
    }

    return true;
}


 public function deleteModule($module_id) {
            $this->db->beginTransaction();

            try {
                $stmt = $this->db->prepare("DELETE FROM modules WHERE module_id = ?");
                $stmt->execute([$module_id]);

                if ($stmt->rowCount() === 0) {
                    throw new PDOException("No module found with module_id = $module_id");
                }

                $this->db->commit();
                return true;
            } catch (PDOException $e) {
                $this->db->rollBack();
                error_log("Error deleting  module:  " . $e->getMessage());
                return false;
            }
    }

    public function deleteElement($element_id) {
            $this->db->beginTransaction();

            try {
                $stmt = $this->db->prepare("DELETE FROM elements WHERE element_id = ?");
                $stmt->execute([$element_id]);

                if ($stmt->rowCount() === 0) {
                    throw new PDOException("No element found with element_id = $element_id");
                }

                $this->db->commit();
                return true;
            } catch (PDOException $e) {
                $this->db->rollBack();
                error_log("Error deleting  element:  " . $e->getMessage());
                return false;
            }
    }


// public function updateModule($id, $code, $nom)
// {
//     $sql = "UPDATE modules SET code_module = :code, nom_module = :nom WHERE module_id = :id";
//     $stmt = $this->db->prepare($sql);
//     $stmt->bindParam(':code', $code);
//     $stmt->bindParam(':nom', $nom);
//     $stmt->bindParam(':id', $id);
//     $stmt->execute();
// }

// public function deleteModuleElements($moduleId)
// {
//     // Get all element IDs linked to the module
//     $query = "SELECT element_id FROM elements WHERE module_id = :moduleId";
//     $stmt = $this->db->prepare($query);
//     $stmt->bindParam(':moduleId', $moduleId);
//     $stmt->execute();
//     $elementIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

//     // Delete from element_filiere
//     $del1 = $this->db->prepare("DELETE FROM element_filiere WHERE Ref_element = :id");
//     foreach ($elementIds as $eid) {
//         $del1->execute([':id' => $eid]);
//     }

//     // Delete elements
//     $del2 = $this->db->prepare("DELETE FROM elements WHERE module_id = :moduleId");
//     $del2->bindParam(':moduleId', $moduleId);
//     $del2->execute();
// }

public function addElementToModule($moduleId, $nom, $coeff_element, $coeff_ecrit, $coeff_cc, $coeff_tp, $filiereId, $semestreId, $profElement, $profTP)
{
    // Insert into elements
    $sql = "INSERT INTO elements (nom, module_id, coeff_element, coeff_ecrit, coeff_cc, coeff_tp)
            VALUES (:nom, :moduleId, :ce, :ecrit, :cc, :tp)";
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':moduleId', $moduleId);
    $stmt->bindParam(':ce', $coeff_element);
    $stmt->bindParam(':ecrit', $coeff_ecrit);
    $stmt->bindParam(':cc', $coeff_cc);
    $stmt->bindParam(':tp', $coeff_tp);
    $stmt->execute();

    $elementId = $this->db->lastInsertId();

    // Insert into element_filiere
    $sql2 = "INSERT INTO element_filiere (Ref_element, Ref_filiere, Ref_semestre, Ref_prof_element, Ref_prof_tp)
             VALUES (:eid, :fid, :sid, :pid1, :pid2)";
    $stmt2 = $this->db->prepare($sql2);
    $stmt2->bindParam(':eid', $elementId);
    $stmt2->bindParam(':fid', $filiereId);
    $stmt2->bindParam(':sid', $semestreId);
    $stmt2->bindParam(':pid1', $profElement);
    $stmt2->bindParam(':pid2', $profTP);
    $stmt2->execute();
}


public function updateModuleSansElements($module_id, $code, $nom, $coeff_cc, $coeff_ecrit, $coeff_element, $coeff_tp, $filiere_id, $semestre_id, $prof_element_id, $prof_tp_id = null)
{
    // Get current academic year
    $stmt = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;

    $anneeId = $row['annee_id'];

    // Update the module itself
    $sql = "UPDATE modules SET code = :code, nom = :nom, annee_id = :annee_id WHERE module_id = :module_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':code' => $code,
        ':nom' => $nom,
        ':annee_id' => $anneeId,
        ':module_id' => $module_id
    ]);

    // Delete all elements linked to this module
    $del = $this->db->prepare("DELETE FROM elements WHERE module_id = :module_id");
    $del->execute([':module_id' => $module_id]);

    // Insert new element for this module
    $sqlInsert = "INSERT INTO elements (
        nom, module_id, coeff_element, coeff_cc, coeff_ecrit, coeff_tp, Ref_prof_element, Ref_prof_tp
    ) VALUES (
        :nom_element, :module_id, :coeff_element, :coeff_cc, :coeff_ecrit, :coeff_tp, :prof_element_id, :prof_tp_id
    )";
    $stmtInsert = $this->db->prepare($sqlInsert);
    $stmtInsert->execute([
        ':nom_element' => $nom,
        ':module_id' => $module_id,
        ':coeff_element' => $coeff_element,
        ':coeff_cc' => $coeff_cc,
        ':coeff_ecrit' => $coeff_ecrit,
        ':coeff_tp' => $coeff_tp,
        ':prof_element_id' => $prof_element_id,
        ':prof_tp_id' => $prof_tp_id,
    ]);

    return true;
}





 public function ModifierModuleAvecElement(
    $module_id, $codeMod, $nomMod, $ref_filiere, $ref_semestre,
    $elements = []
) {
    // Update du module
    $sqlUpdateModule = "UPDATE modules SET code = :code, nom = :nom,field_id = :filiere, semestre_id = :semestre
                        WHERE module_id = :module_id";
    $stmtModule = $this->db->prepare($sqlUpdateModule);
    $stmtModule->execute([
        ':code' => $codeMod,
        ':nom' => $nomMod,
        ':filiere' => $ref_filiere,
        ':semestre' => $ref_semestre,
        ':module_id' => $module_id
    ]);

    // Supprimer les anciens éléments
    $stmtDelete = $this->db->prepare("DELETE FROM elements WHERE module_id = :module_id");
    $stmtDelete->execute([':module_id' => $module_id]);

    // Réinsérer les éléments
    $sqlElement = "INSERT INTO elements (
        nom, module_id, coeff_element, coeff_ecrit, coeff_cc, coeff_tp,
        Ref_prof_element, Ref_prof_tp
    ) VALUES (
        :nom, :module_id, :coeff_element, :coeff_ecrit, :coeff_cc, :coeff_tp,
        :prof_element, :prof_tp
    )";

    $stmtElement = $this->db->prepare($sqlElement);

    foreach ($elements as $el) {
        $stmtElement->execute([
            ':nom' => $el['nom'],
            ':module_id' => $module_id,
            ':coeff_element' => $el['coeff_element'],
            ':coeff_ecrit' => $el['coeff_ecrit'],
            ':coeff_cc' => $el['coeff_cc'],
            ':coeff_tp' => $el['coeff_tp'],
            ':prof_element' => $el['prof_element'],
            ':prof_tp' => $el['prof_tp']
        ]);
    }

    return true;
}



            public function deleteField($fieldId) {
                error_log("Attempting to delete filière with ID = $fieldId");

                $this->db->beginTransaction();

                try {
                    $stmt = $this->db->prepare("DELETE FROM filieres WHERE field_id = ?");
                    $stmt->execute([$fieldId]);

                    $rowCount = $stmt->rowCount();
                    error_log("🔧 DELETE rowCount = $rowCount");

                    if ($rowCount === 0) {
                        $this->db->rollBack();
                        error_log("🔧 Rollback: filière not found");
                        return false;
                    }

                    $this->db->commit();
                    error_log("🔧 Commit successful");
                    return true;
                } catch (PDOException $e) {
                    $this->db->rollBack();
                    error_log("🔧 Exception during delete: " . $e->getMessage());
                    return false;
                }
            }





public function AjouterFiliere($nom, $depart_id, $cycle_id, $prof_id, $sections = []) {
    // 1. Créer la filière
    $stmt = $this->db->prepare("INSERT INTO filieres (nom, department_id, cycle_id, head_professor_id, annee_accreditation) VALUES (:nom, :depart, :cycle, :prof, :annee)");
    $anneeActuelle = $this->getAnneeAcademiqueActuelle();
    $stmt->execute([
        ':nom' => $nom,
        ':depart' => $depart_id,
        ':cycle' => $cycle_id,
        ':prof' => $prof_id,
        ':annee' => $anneeActuelle
    ]);

    $filiere_id = $this->db->lastInsertId();

    foreach ($sections as $sectionInfo) {
        $etape = $sectionInfo['etape'];
        $semestre = $sectionInfo['semestre'];
        $sectionCount = max(1, intval($sectionInfo['section_count']));

        for ($i = 0; $i < $sectionCount; $i++) {
            $sectionName = 'Section ' . chr(65 + $i); // A, B, C, etc.
            $stmt = $this->db->prepare("INSERT INTO sections (nom, field_id, etape) VALUES (:nom, :filiere, :etape)");
            $stmt->execute([
                ':nom' => $sectionName,
                ':filiere' => $filiere_id,
                ':etape' => $etape
            ]);

            $section_id = $this->db->lastInsertId();

            $groupCount = 1; // Valeur par défaut
            if (!empty($sectionInfo['groupes'])) {
                foreach ($sectionInfo['groupes'] as $grp) {
                    if ($grp['section_num'] == $i + 1) {
                        $groupCount = max(1, intval($grp['groupe_count']));
                    }
                }
            }

            for ($j = 1; $j <= $groupCount; $j++) {
                $groupName = "Groupe " . chr(65 + $i) . "-" . $j;
                $stmt = $this->db->prepare("INSERT INTO groupes (nom, field_id, section_id) VALUES (:nom, :filiere, :section)");
                $stmt->execute([
                    ':nom' => $groupName,
                    ':filiere' => $filiere_id,
                    ':section' => $section_id
                ]);
            }
        }
    }

    return true;
}

private function getAnneeAcademiqueActuelle() {
    $stmt = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['annee_id'] : null;
}

// Update filière info basique
    public function updateFiliereBasicInfo($fieldId, $nomFili, $depart_id, $prof_id, $cycle_id, $annee) {
        $sql = "UPDATE filieres SET nom = :nom, department_id = :depart_id, head_professor_id = :prof_id,
                cycle_id = :cycle_id, annee_accreditation = :annee WHERE field_id = :field_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $nomFili,
            ':depart_id' => $depart_id,
            ':prof_id' => $prof_id,
            ':cycle_id' => $cycle_id,
            ':annee' => $annee,
            ':field_id' => $fieldId
        ]);
    }

    // Récupérer toutes les sections d'une filière
    public function SectionsByFiliere($fieldId) {
        $sql = "SELECT * FROM sections WHERE field_id = :field_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':field_id' => $fieldId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer tous les groupes d'une section
    public function getGroupsBySection($sectionId) {
        $sql = "SELECT * FROM groupes WHERE section_id = :section_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':section_id' => $sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Met à jour ou insère une section (si id existant, update sinon insert)
    public function saveSection($section) {
        if (isset($section['section_id']) && !empty($section['section_id'])) {
            // Update section
            $sql = "UPDATE sections SET nom = :nom, etape = :etape WHERE section_id = :section_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom' => $section['nom'],
                ':etape' => $section['etape'],
                ':section_id' => $section['section_id']
            ]);
            return $section['section_id'];
        } else {
            // Insert section
            $sql = "INSERT INTO sections (nom, field_id, etape) VALUES (:nom, :field_id, :etape)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom' => $section['nom'],
                ':field_id' => $section['field_id'],
                ':etape' => $section['etape']
            ]);
            return $this->db->lastInsertId();
        }
    }



    // Supprime des sections par IDs (array)
    public function deleteSections($sectionIds) {
        if (empty($sectionIds)) return;
        $in  = str_repeat('?,', count($sectionIds) - 1) . '?';
        $sql = "DELETE FROM sections WHERE section_id IN ($in)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($sectionIds);
    }

    // Met à jour ou insère un groupe
    public function saveGroup($group) {
        if (isset($group['group_id']) && !empty($group['group_id'])) {
            $sql = "UPDATE groupes SET nom = :nom, section_id = :section_id WHERE group_id = :group_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom' => $group['nom'],
                ':section_id' => $group['section_id'],
                ':group_id' => $group['group_id']
            ]);
            return $group['group_id'];
        } else {
            $sql = "INSERT INTO groupes (nom, field_id, section_id) VALUES (:nom, :field_id, :section_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom' => $group['nom'],
                ':field_id' => $group['field_id'],
                ':section_id' => $group['section_id']
            ]);
            return $this->db->lastInsertId();
        }
    }

    // Supprime groupes par IDs
    public function deleteGroups($groupIds) {
        if (empty($groupIds)) return;
        $in  = str_repeat('?,', count($groupIds) - 1) . '?';
        $sql = "DELETE FROM groupes WHERE group_id IN ($in)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($groupIds);
    }

    // Mise à jour complète des sections et groupes
    public function updateSectionsAndGroups($fieldId, $sectionsData) {
        // 1. Récupérer toutes les sections existantes et groupes pour comparaison
        $existingSections = $this->SectionsByFiliere($fieldId);
        $existingSectionIds = array_column($existingSections, 'section_id');

        $existingGroups = [];
        foreach ($existingSections as $section) {
            $groups = $this->getGroupsBySection($section['section_id']);
            $existingGroups = array_merge($existingGroups, $groups);
        }
        $existingGroupIds = array_column($existingGroups, 'group_id');

        // 2. Collecter IDs reçus dans la requête (ceux à conserver / modifier)
        $incomingSectionIds = [];
        $incomingGroupIds = [];

        // 3. Traiter les sections reçues
        foreach ($sectionsData as $section) {
            $section['field_id'] = $fieldId;
            $sectionId = $this->saveSection($section);
            $incomingSectionIds[] = $sectionId;

            // Traiter les groupes de la section
            if (!empty($section['groupes'])) {
                foreach ($section['groupes'] as $group) {
                    $group['field_id'] = $fieldId;
                    $group['section_id'] = $sectionId;
                    $groupId = $this->saveGroup($group);
                    $incomingGroupIds[] = $groupId;
                }
            }
        }

        // 4. Supprimer sections non présentes dans incomingSectionIds
        $sectionsToDelete = array_diff($existingSectionIds, $incomingSectionIds);
        $this->deleteSections($sectionsToDelete);

        // 5. Supprimer groupes non présents dans incomingGroupIds
        $groupsToDelete = array_diff($existingGroupIds, $incomingGroupIds);
        $this->deleteGroups($groupsToDelete);
    }

                                          
                                            


public function getModules($annee   = null,  $fieldId = null, $semestreId = null)
    {
        $sql = "
            SELECT 
            -- === MODULES ===
            m.module_id,
            m.code AS code_module, 
            m.nom AS nom_module,
            m.coefficient AS coeff_module,
            m.annee_id AS annee,
            m.semestre_id AS id_semestre,
            m.field_id AS id_filiere,
            m.responsible_professor_id AS id_prof_responsable,
            f.nom AS nom_filiere, 
            s.nom AS semestre,
            m.type,

            -- Prof responsable du module
            CONCAT(pm.nom, ' ', pm.prenom) AS prof_responsable,

            -- === ELEMENTS ===
            e.element_id,
            e.nom AS nom_element,
            e.coeff_ecrit, 
            e.coeff_cc, 
            e.coeff_tp,
            e.coeff_element,
            e.presentiel,
            e.a_distance,
            e.en_alternance,
            e.horraire_tp,
            e.horraire_td,
            e.horraire_cours,
            e.horraire_evaluation,
            e.horraire_activite_pratique,
            e.coeff_projet,
            e.Chef_element,

            -- IDs des profs liés aux éléments
            e.Ref_prof_element AS id_prof_element,
            e.Ref_prof_tp AS id_prof_tp,
            e.Ref_prof_cours AS id_prof_cours,
            e.Ref_prof_td AS id_prof_td,

            -- Noms concaténés des profs liés
            CONCAT(pe.nom, ' ', pe.prenom) AS prof_element, 
            CONCAT(pt.nom, ' ', pt.prenom) AS prof_tp,
            CONCAT(pc.nom, ' ', pc.prenom) AS prof_cours,
            CONCAT(pd.nom, ' ', pd.prenom) AS prof_td

        FROM modules m 
        LEFT JOIN elements e 
            ON e.module_id = m.module_id
        LEFT JOIN filieres f 
            ON f.field_id = m.field_id
        LEFT JOIN semestres s 
            ON s.semestre_id = m.semestre_id
        LEFT JOIN professeurs pm 
            ON pm.user_id = m.responsible_professor_id
        LEFT JOIN professeurs pe 
            ON pe.user_id = e.Ref_prof_element
        LEFT JOIN professeurs pt 
            ON pt.user_id = e.Ref_prof_tp
        LEFT JOIN professeurs pc 
            ON pc.user_id = e.Ref_prof_cours
        LEFT JOIN professeurs pd 
            ON pd.user_id = e.Ref_prof_td

            WHERE 1 = 1
        ";

        $params = [];

        if ($annee !== null) {
            $sql     .= " AND m.annee_id = :annee";
            $params[':annee'] = $annee;
        }
        if ($fieldId !== null) {
            $sql     .= " AND m.field_id = :fieldId";
            $params[':fieldId'] = $fieldId;
        }
        if ($semestreId !== null) {
            $sql     .= " AND m.semestre_id = :semestreId";
            $params[':semestreId'] = $semestreId;
        }

        $sql .= " ORDER BY m.code, e.nom, f.nom, s.nom";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


public function infoModules(){
                        return $this->db->query("
                        SELECT 
            -- === MODULES ===
            m.module_id,
            m.code AS code_module, 
            m.langue, 
            m.nom AS nom_module,
            m.coefficient AS coeff_module,
            m.annee_id AS annee,
            m.semestre_id AS id_semestre,
            m.field_id AS id_filiere,
            m.responsible_professor_id AS id_prof_responsable,
            f.nom AS nom_filiere, 
            s.nom AS semestre,
            m.type,

            -- Prof responsable du module
            CONCAT(pm.nom, ' ', pm.prenom) AS prof_responsable,

            -- === ELEMENTS ===
            e.element_id,
            e.nom AS nom_element,
            e.coeff_ecrit, 
            e.coeff_cc, 
            e.coeff_tp,
            e.coeff_td,

            e.coeff_element,
            e.presentiel,
            e.a_distance,
            e.en_alternance,
            e.horraire_tp,
            e.horraire_td,
            e.horraire_cours,
            e.horraire_evaluation,
            e.horraire_activite_pratique,
            e.coeff_projet,
            e.Chef_element,

            -- IDs des profs liés aux éléments
            e.Ref_prof_element AS id_prof_element,
            e.Ref_prof_tp AS id_prof_tp,
            e.Ref_prof_cours AS id_prof_cours,
            e.Ref_prof_td AS id_prof_td,

            -- Noms concaténés des profs liés
            CONCAT(pe.nom, ' ', pe.prenom) AS prof_element, 
            CONCAT(pt.nom, ' ', pt.prenom) AS prof_tp,
            CONCAT(pc.nom, ' ', pc.prenom) AS prof_cours,
            CONCAT(pd.nom, ' ', pd.prenom) AS prof_td

        FROM modules m 
        LEFT JOIN elements e 
            ON e.module_id = m.module_id
        LEFT JOIN filieres f 
            ON f.field_id = m.field_id
        LEFT JOIN semestres s 
            ON s.semestre_id = m.semestre_id
        LEFT JOIN professeurs pm 
            ON pm.user_id = m.responsible_professor_id
        LEFT JOIN professeurs pe 
            ON pe.user_id = e.Ref_prof_element
        LEFT JOIN professeurs pt 
            ON pt.user_id = e.Ref_prof_tp
        LEFT JOIN professeurs pc 
            ON pc.user_id = e.Ref_prof_cours
        LEFT JOIN professeurs pd 
            ON pd.user_id = e.Ref_prof_td

        ORDER BY m.code, e.nom, f.nom, s.nom;

                    ")->fetchAll();
                }



            //    public function AjouterFiliere1($data) {
            //      try {
            //                             // Validation des données requises
            //                             if (empty($data['nom']) || empty($data['depart_id']) || empty($data['prof_id']) || 
            //                                 empty($data['cycle_id']) || empty($data['diplome']) || 
            //                                 empty($data['date_debut_affectation']) || empty($data['date_fin_affectation']) || 
            //                                 empty($data['annee_debut_accreditation']) || empty($data['annee_fin_accreditation']) || 
            //                                 empty($data['statut'])) {
                                            
            //                                 http_response_code(400);
            //                                 echo json_encode(['status' => 'error', 'message' => 'Paramètres manquants']);
            //                                 return;
            //                             }
            
            //                             $success = $this->model->AjouterFiliere1($data);
                                        
            //                             if ($success) {
            //                                 echo json_encode(['status' => 'success', 'message' => 'Filière créée avec succès']);
            //                             } else {
            //                                 http_response_code(500);
            //                                 echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la création de la filière']);
            //                             }
                                        
            //                         } catch (PDOException $e) {
            //                             http_response_code(500);
            //                             echo json_encode([
            //                                 'status' => 'error',
            //                                 'message' => 'Erreur base de données: ' . $e->getMessage()
            //                             ]);
            //                         } catch (Exception $e) {
            //                             http_response_code(500);
            //                             echo json_encode([
            //                                 'status' => 'error',
            //                                 'message' => 'Erreur: ' . $e->getMessage()
            //                             ]);
            //                         }
                                
            //                     $this->db->beginTransaction();
                                
            //                     try {
            //                         // 1. Insérer la filière
            //                         $sqlFiliere = "INSERT INTO filieres (nom, department_id, head_professor_id, annee_accreditation, cycle_id, debut_affectaion, fin_affectaion, annee_fin_accrediation, status, diplome) 
            //                                     VALUES (:nom, :depart_id, :prof_id, :annee_debut, :cycle_id, :date_debut, :date_fin, :annee_fin, :statut, :diplome)";
                                    
            //                         $stmtFiliere = $this->db->prepare($sqlFiliere);
            //                         $stmtFiliere->execute([
            //                             ':nom' => $data['nom'],
            //                             ':depart_id' => $data['depart_id'],
            //                             ':prof_id' => $data['prof_id'],
            //                             ':annee_debut' => $data['annee_debut_accreditation'],
            //                             ':cycle_id' => $data['cycle_id'],
            //                             ':date_debut' => $data['date_debut_affectation'],
            //                             ':date_fin' => $data['date_fin_affectation'],
            //                             ':annee_fin' => $data['annee_fin_accreditation'],
            //                             ':statut' => $data['statut'],
            //                             ':diplome' => $data['diplome']
            //                         ]);
        
            //                         $filiereId = $this->db->lastInsertId();
                                    
            //                         // 2. Récupérer l'année académique courante
            //                         $stmtAnnee = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag = 1 LIMIT 1");
            //                         $annee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
                                    
            //                         if (!$annee) {
            //                             throw new Exception("Aucune année académique courante trouvée");
            //                         }
                                    
            //                         $anneeId = $annee['annee_id'];
                                    
            //                         // 3. Traiter les modules (si présents)
            //                         if (isset($data['modules']) && is_array($data['modules'])) {
            //                             foreach ($data['modules'] as $module) {
            //                                 // Créer le semestre si nécessaire (basé sur etape et semestre)
            //                                 $semestreId = $this->getOrCreateSemestre($module['etape'], $module['semestre'], $anneeId);
                                            
            //                                 // Insérer le module
            //                                 $sqlModule = "INSERT INTO modules (code, nom, semestre_id, field_id, responsible_professor_id, annee_id, type) 
            //                                             VALUES (:code, :nom, :semestre_id, :field_id, :responsible_professor_id, :annee_id, :type)";
                                            
            //                                 $stmtModule = $this->db->prepare($sqlModule);
            //                                 $stmtModule->execute([
            //                                     ':code' => $module['code'],
            //                                     ':nom' => $module['nom'],
            //                                     ':semestre_id' => $semestreId,
            //                                     ':field_id' => $filiereId,
            //                                     ':responsible_professor_id' => $module['chef_id'],
            //                                     ':annee_id' => $anneeId,
            //                                     ':type' => $module['type']
            //                                 ]);
                                            
            //                                 $moduleId = $this->db->lastInsertId();
                                            
            //                                 // 4. Traiter les éléments ou créer un élément unique si le module n'a pas d'éléments
            //                                 if (!$module['has_elements']) {
            //                                     // Le module est lui-même un élément
            //                                     $this->insertElement([
            //                                         'nom' => $module['nom'],
            //                                         'module_id' => $moduleId,
            //                                         'coeff_element' => 1, // Puisque c'est le seul élément
            //                                         'coeff_ecrit' => $module['coeff_ecrit'] ?? 0,
            //                                         'coeff_tp' => $module['coeff_tp'] ?? 0,
            //                                         'coeff_cc' => $module['coeff_cc'] ?? 0,
            //                                         'coeff_projet' => $module['coeff_projet'] ?? 0,
            //                                         'Ref_prof_element' => $module['chef_element'] ?? null,
            //                                         'Ref_prof_tp' => $module['prof_tp'] ?? null,
            //                                         'Ref_prof_td' => $module['prof_td'] ?? null,
            //                                         'Ref_prof_cours' => $module['prof_cours'] ?? null,
            //                                         'presentiel' => $module['volume_presentiel'] ?? 0,
            //                                         'a_distance' => $module['volume_distance'] ?? 0,
            //                                         'en_alternance' => $module['volume_alternance'] ?? 0,
            //                                         'horraire_tp' => $module['volume_tp'] ?? 0,
            //                                         'horraire_td' => $module['volume_td'] ?? 0,
            //                                         'horraire_cours' => $module['volume_cours'] ?? 0,
            //                                         'horraire_evaluation' => $module['volume_evaluation'] ?? 0,
            //                                         'horraire_activite_pratique' => $module['volume_pratique'] ?? 0
            //                                     ]);
            //                                 } else {
            //                                     // Le module a des éléments
            //                                     foreach ($module['elements'] as $element) {
            //                                         $this->insertElement(array_merge($element, ['module_id' => $moduleId]));
            //                                     }
            //                                 }
            //                             }
            //                         }
                                    
            //                         $this->db->commit();
            //                         return true;
                                    
            //                     } catch (Exception $e) {
            //                         $this->db->rollBack();
            //                         error_log("Erreur dans AjouterFiliere: " . $e->getMessage());
            //                                                         http_response_code(500);
            //                         echo json_encode([
            //                             'status' => 'error',
            //                             'message' => 'Erreur base de données: ' . $e->getMessage(),
            //                             'trace' => $e->getTraceAsString()
            //                         ]);
            //                         exit;
            //                         throw $e;
            //                     }
            //                 }

            //                 // Méthode helper pour insérer un élément
            //                 private function insertElement($elementData) {
            //                     $sqlElement = "INSERT INTO elements 
            //                                 (nom, module_id, coeff_element, coeff_ecrit, coeff_tp, coeff_cc, coeff_projet, 
            //                                 Ref_prof_element, Ref_prof_tp, Ref_prof_td, Ref_prof_cours,
            //                                 presentiel, a_distance, en_alternance, 
            //                                 horraire_tp, horraire_td, horraire_cours, horraire_evaluation, horraire_activite_pratique) 
            //                                 VALUES 
            //                                 (:nom, :module_id, :coeff_element, :coeff_ecrit, :coeff_tp, :coeff_cc, :coeff_projet, 
            //                                 :Ref_prof_element, :Ref_prof_tp, :Ref_prof_td, :Ref_prof_cours,
            //                                 :presentiel, :a_distance, :en_alternance, 
            //                                 :horraire_tp, :horraire_td, :horraire_cours, :horraire_evaluation, :horraire_activite_pratique)";
                                
            //                     $stmtElement = $this->db->prepare($sqlElement);
                                
            //                     // Gérer les valeurs NULL pour les professeurs
            //                     $professeurFields = ['Ref_prof_element', 'Ref_prof_tp', 'Ref_prof_td', 'Ref_prof_cours'];
            //                     foreach ($professeurFields as $field) {
            //                         if (empty($elementData[$field])) {
            //                             $elementData[$field] = null;
            //                         }
            //                     }
                                
            //                     $stmtElement->execute([
            //                         ':nom' => $elementData['nom'],
            //                         ':module_id' => $elementData['module_id'],
            //                         ':coeff_element' => $elementData['coefficient'] ?? $elementData['coeff_element'] ?? 0,
            //                         ':coeff_ecrit' => $elementData['coeff_ecrit'] ?? 0,
            //                         ':coeff_tp' => $elementData['coeff_tp'] ?? 0,
            //                         ':coeff_cc' => $elementData['coeff_cc'] ?? 0,
            //                         ':coeff_projet' => $elementData['coeff_projet'] ?? 0,
            //                         ':Ref_prof_element' => $elementData['chef_element'] ?? $elementData['Ref_prof_element'] ?? null,
            //                         ':Ref_prof_tp' => $elementData['prof_tp'] ?? $elementData['Ref_prof_tp'] ?? null,
            //                         ':Ref_prof_td' => $elementData['prof_td'] ?? $elementData['Ref_prof_td'] ?? null,
            //                         ':Ref_prof_cours' => $elementData['prof_cours'] ?? $elementData['Ref_prof_cours'] ?? null,
            //                         ':presentiel' => $elementData['volume_presentiel'] ?? $elementData['presentiel'] ?? 0,
            //                         ':a_distance' => $elementData['volume_distance'] ?? $elementData['a_distance'] ?? 0,
            //                         ':en_alternance' => $elementData['volume_alternance'] ?? $elementData['en_alternance'] ?? 0,
            //                         ':horraire_tp' => $elementData['volume_tp'] ?? $elementData['horraire_tp'] ?? 0,
            //                         ':horraire_td' => $elementData['volume_td'] ?? $elementData['horraire_td'] ?? 0,
            //                         ':horraire_cours' => $elementData['volume_cours'] ?? $elementData['horraire_cours'] ?? 0,
            //                         ':horraire_evaluation' => $elementData['volume_evaluation'] ?? $elementData['horraire_evaluation'] ?? 0,
            //                         ':horraire_activite_pratique' => $elementData['volume_pratique'] ?? $elementData['horraire_activite_pratique'] ?? 0
            //                     ]);
            //                 }

            //                 // Méthode helper pour obtenir ou créer un semestre
            //                 private function getOrCreateSemestre($etape, $semestreNum, $anneeId) {
            //                     // Vérifier si le semestre existe déjà
            //                     $sql = "SELECT semestre_id FROM semestres WHERE etape = :etape AND numero = :numero AND annee_id = :annee_id";
            //                     $stmt = $this->db->prepare($sql);
            //                     $stmt->execute([
            //                         ':etape' => $etape,
            //                         ':numero' => $semestreNum,
            //                         ':annee_id' => $anneeId
            //                     ]);
                                
            //                     $semestre = $stmt->fetch(PDO::FETCH_ASSOC);
                                
            //                     if ($semestre) {
            //                         return $semestre['semestre_id'];
            //                     }
                                
            //                     // Créer le semestre s'il n'existe pas
            //                     $sqlInsert = "INSERT INTO semestres (etape, numero, annee_id) VALUES (:etape, :numero, :annee_id)";
            //                     $stmtInsert = $this->db->prepare($sqlInsert);
            //                     $stmtInsert->execute([
            //                         ':etape' => $etape,
            //                         ':numero' => $semestreNum,
            //                         ':annee_id' => $anneeId
            //                     ]);
                                
            //                     return $this->db->lastInsertId();

                                
            //                 }


          public function AjouterFiliere1($data) {
    $this->db->beginTransaction();
    try {
        // --- 1. Insert Filière ---
        $sql = "INSERT INTO filieres 
                (nom, department_id, head_professor_id, annee_accreditation, cycle_id, 
                 debut_affectaion, fin_affectaion, annee_fin_accrediation, status, diplome)
                VALUES 
                (:nom, :depart_id, :prof_id, :annee_debut, :cycle_id,
                 :date_debut, :date_fin, :annee_fin, :statut, :diplome)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom'        => $data['nom'],
            ':depart_id'  => $data['depart_id'],
            ':prof_id'    => $data['prof_id'],
            ':annee_debut'=> $data['annee_debut_accreditation'],
            ':cycle_id'   => $data['cycle_id'],
            ':date_debut' => $data['date_debut_affectation'] ?? null,
            ':date_fin'   => $data['date_fin_affectation'] ?? null,
            ':annee_fin'  => $data['annee_fin_accreditation'] ?? null,
            ':statut'     => $data['statut'] ?? 'active',
            ':diplome'    => $data['diplome'] ?? null
        ]);

        $filiereId = $this->db->lastInsertId();

        // --- Helper function for inserting roles ---
        $insertRole = function($userId, $role, $start = null, $end = null) {
            $sqlRole = "INSERT INTO professor_roles (user_id, role, date_debut_affectation, date_fin_affectation)
                        VALUES (:user_id, :role, :date_debut, :date_fin)";
            $stmtRole = $this->db->prepare($sqlRole);
            $stmtRole->execute([
                ':user_id' => $userId,
                ':role'    => $role,
                ':date_debut' => $start,
                ':date_fin'   => $end
            ]);
        };

        // --- Assign Filière Head Role ---
        $insertRole($data['prof_id'], "Chef_De_Filiere", $data['date_debut_affectation'] ?? null, $data['date_fin_affectation'] ?? null);

        // --- 2. Get Current Academic Year ---
        $stmtAnnee = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag=1 LIMIT 1");
        $annee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        if (!$annee) throw new Exception("Aucune année académique courante trouvée");
        $anneeId = $annee['annee_id'];

        // --- 3. Insert Modules & Elements ---
        if (!empty($data['modules']) && is_array($data['modules'])) {
            foreach ($data['modules'] as $module) {
                $semestreGlobal = ($module['etape'] - 1) * 2 + $module['semestre'];

                // Insert module
                $sqlModule = "INSERT INTO modules 
                              (code,langue, nom, coefficient, semestre_id, field_id, responsible_professor_id, annee_id, type) 
                              VALUES 
                              (:code,:langue, :nom, :coefficient, :semestre_id, :field_id, :responsible_professor_id, :annee_id, :type)";
                $stmtModule = $this->db->prepare($sqlModule);
                $stmtModule->execute([
                    ':code' => $module['code'],
                    ':langue' => $module['langue'],
                    ':nom'  => $module['nom'],
                    ':coefficient' => $module['coefficient'] ?? 1,
                    ':semestre_id' => $semestreGlobal,
                    ':field_id'    => $filiereId,
                    ':responsible_professor_id' => $module['chef_id'] ?? null,
                    ':annee_id' => $anneeId,
                    ':type'     => $module['type'] ?? 'standard'
                ]);
                $moduleId = $this->db->lastInsertId();

                // Role chef de module
                if (!empty($module['chef_id'])) {
                    $insertRole($module['chef_id'], "Chef_De_Module");
                }

                // Déterminer les éléments
                $elements = !empty($module['has_elements']) ? $module['elements'] : [[
                    'nom' => $module['nom'],
                    'coeff_element' => 1,
                    'coeff_td' => $module['coeff_td'] ?? 0,
                    'coeff_ecrit' => $module['coeff_ecrit'] ?? 0,
                    'coeff_tp' => $module['coeff_tp'] ?? 0,
                    'coeff_cc' => $module['coeff_cc'] ?? 0,
                    'coeff_projet' => $module['coeff_projet'] ?? 0,
                    'Ref_prof_element' => $module['chef_element'] ?? $module['chef_id'] ?? null,
                    'Ref_prof_tp' => $module['prof_tp'] ?? null,
                    'Ref_prof_td' => $module['prof_td'] ?? null,
                    'Ref_prof_cours' => $module['prof_cours'] ?? null,
                    'presentiel' => $module['volume_presentiel'] ?? 0,
                    'a_distance' => $module['volume_distance'] ?? 0,
                    'en_alternance' => $module['volume_alternance'] ?? 0,
                    'horraire_tp' => $module['volume_tp'] ?? 0,
                    'horraire_td' => $module['volume_td'] ?? 0,
                    'horraire_cours' => $module['volume_cours'] ?? 0,
                    'horraire_evaluation' => $module['volume_evaluation'] ?? 0,
                    'horraire_activite_pratique' => $module['volume_pratique'] ?? 0
                ]];

                foreach ($elements as $element) {
                    $this->insertElement(array_merge($element, ['module_id' => $moduleId]));

                    // --- Assign Element Roles ---
                    if (!empty($element['Ref_prof_element'])) $insertRole($element['Ref_prof_element'], "Chef_De_Element");
                    if (!empty($element['Ref_prof_tp'])) $insertRole($element['Ref_prof_tp'], "Regular");
                    if (!empty($element['Ref_prof_td'])) $insertRole($element['Ref_prof_td'], "Regular");
                    if (!empty($element['Ref_prof_cours'])) $insertRole($element['Ref_prof_cours'], "Regular");
                }

                
            }
        }

        $this->db->commit();
        return true;

    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Erreur AjouterFiliere: " . $e->getMessage());
        throw $e;
    }
}



 public function UpdateFiliere($data) {
    $this->db->beginTransaction();
    try {
        // --- 1. Update Filière ---
        $sql = "UPDATE filieres 
                SET nom = :nom,
                    department_id = :depart_id,
                    head_professor_id = :prof_id,
                    annee_accreditation = :annee_debut,
                    cycle_id = :cycle_id,
                    debut_affectaion = :date_debut,
                    fin_affectaion = :date_fin,
                    annee_fin_accrediation = :annee_fin,
                    status = :statut,
                    diplome = :diplome
                WHERE field_id = :fieldId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom'        => $data['nom'],
            ':depart_id'  => $data['depart_id'],
            ':prof_id'    => $data['prof_id'],
            ':annee_debut'=> $data['annee_debut_accreditation'],
            ':cycle_id'   => $data['cycle_id'],
            ':date_debut' => $data['date_debut_affectation'] ?? null,
            ':date_fin'   => $data['date_fin_affectation'] ?? null,
            ':annee_fin'  => $data['annee_fin_accreditation'] ?? null,
            ':statut'     => $data['statut'] ?? 'active',
            ':diplome'    => $data['diplome'] ?? null,
            ':fieldId'    => $data['fieldId']
        ]);

        $filiereId = $data['fieldId'];

        // --- Helper function for inserting roles ---
        $insertRole = function($userId, $role, $start = null, $end = null) {
            $sqlRole = "INSERT INTO professor_roles (user_id, role, date_debut_affectation, date_fin_affectation)
                        VALUES (:user_id, :role, :date_debut, :date_fin)";
            $stmtRole = $this->db->prepare($sqlRole);
            $stmtRole->execute([
                ':user_id' => $userId,
                ':role'    => $role,
                ':date_debut' => $start,
                ':date_fin'   => $end
            ]);
        };

        // --- (optionnel) Réassigner le rôle de chef filière ---
        $insertRole($data['prof_id'], "Chef_De_Filiere", $data['date_debut_affectation'] ?? null, $data['date_fin_affectation'] ?? null);

        // --- 2. Get Current Academic Year ---
        $stmtAnnee = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag=1 LIMIT 1");
        $annee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        if (!$annee) throw new Exception("Aucune année académique courante trouvée");
        $anneeId = $annee['annee_id'];

        // --- 3. Ajouter les nouveaux modules (sans supprimer les anciens) ---
        if (!empty($data['modules']) && is_array($data['modules'])) {
            foreach ($data['modules'] as $module) {
                $semestreGlobal = ($module['etape'] - 1) * 2 + $module['semestre'];

                // Insert module
                $sqlModule = "INSERT INTO modules 
                              (code,langue, nom, coefficient, semestre_id, field_id, responsible_professor_id, annee_id, type) 
                              VALUES 
                              (:code,:langue, :nom, :coefficient, :semestre_id, :field_id, :responsible_professor_id, :annee_id, :type)";
                $stmtModule = $this->db->prepare($sqlModule);
                $stmtModule->execute([
                    ':code' => $module['code'],
                    ':langue' => $module['langue'],
                    ':nom'  => $module['nom'],
                    ':coefficient' => $module['coefficient'] ?? 1,
                    ':semestre_id' => $semestreGlobal,
                    ':field_id'    => $filiereId,
                    ':responsible_professor_id' => $module['chef_id'] ?? null,
                    ':annee_id' => $anneeId,
                    ':type'     => $module['type'] ?? 'standard'
                ]);
                $moduleId = $this->db->lastInsertId();

                // Role chef de module
                if (!empty($module['chef_id'])) {
                    $insertRole($module['chef_id'], "Chef_De_Module");
                }

                // Déterminer les éléments
                $elements = !empty($module['has_elements']) ? $module['elements'] : [[
                    'nom' => $module['nom'],
                    'coeff_element' => 1,
                    'coeff_td' => $module['coeff_td'] ?? 0,
                    'coeff_ecrit' => $module['coeff_ecrit'] ?? 0,
                    'coeff_tp' => $module['coeff_tp'] ?? 0,
                    'coeff_cc' => $module['coeff_cc'] ?? 0,
                    'coeff_projet' => $module['coeff_projet'] ?? 0,
                    'Ref_prof_element' => $module['chef_element'] ?? $module['chef_id'] ?? null,
                    'Ref_prof_tp' => $module['prof_tp'] ?? null,
                    'Ref_prof_td' => $module['prof_td'] ?? null,
                    'Ref_prof_cours' => $module['prof_cours'] ?? null,
                    'presentiel' => $module['volume_presentiel'] ?? 0,
                    'a_distance' => $module['volume_distance'] ?? 0,
                    'en_alternance' => $module['volume_alternance'] ?? 0,
                    'horraire_tp' => $module['volume_tp'] ?? 0,
                    'horraire_td' => $module['volume_td'] ?? 0,
                    'horraire_cours' => $module['volume_cours'] ?? 0,
                    'horraire_evaluation' => $module['volume_evaluation'] ?? 0,
                    'horraire_activite_pratique' => $module['volume_pratique'] ?? 0
                ]];

                foreach ($elements as $element) {
                    $this->insertElement(array_merge($element, ['module_id' => $moduleId]));

                    // --- Assign Element Roles ---
                    if (!empty($element['Ref_prof_element'])) $insertRole($element['Ref_prof_element'], "Chef_De_Element");
                    if (!empty($element['Ref_prof_tp'])) $insertRole($element['Ref_prof_tp'], "Regular");
                    if (!empty($element['Ref_prof_td'])) $insertRole($element['Ref_prof_td'], "Regular");
                    if (!empty($element['Ref_prof_cours'])) $insertRole($element['Ref_prof_cours'], "Regular");
                }

               
            }
        }

        $this->db->commit();
        return true;

    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Erreur UpdateFiliere: " . $e->getMessage());
        throw $e;
    }
}




public function getFilieres($cycle_id = null, $diplome_id = null, $departement_id = null, $status = null) {
$sql = "                 SELECT 
                            f.field_id, 
                            f.nom AS nom_filiere,
                            d.department_id,
                            d.nom AS nom_departement, 
                            f.head_professor_id,
                            CONCAT(chef.nom, ' ', chef.prenom) AS nom_professeur_responsable, 
                            f.annee_accreditation,
                            c.nom  AS nom_cycle,
                            f.cycle_id,
                            f.debut_affectaion,
                            f.fin_affectaion,
                            f.annee_fin_accrediation,
                            f.status,         
                            f.diplome AS diplome_id,
                            dpl.nom AS nom_diplome
                            FROM filieres f 
                            LEFT JOIN professeurs chef ON chef.user_id = f.head_professor_id
                            LEFT JOIN departements d ON d.department_id = f.department_id
                            LEFT JOIN cycles c ON c.cycle_id = f.`cycle_id`
                            LEFT JOIN diplomes dpl ON dpl.diplome_id = f.diplome
                            WHERE  1=1 
                            "; 

    $params = [];

    if ($cycle_id) {
        $sql .= " AND f.cycle_id = :cycle_id";
        $params[':cycle_id'] = $cycle_id;
    }
    if ($diplome_id) {
        $sql .= " AND f.diplome = :diplome_id";
        $params[':diplome_id'] = $diplome_id;
    }
    if ($departement_id) {
        $sql .= " AND f.department_id = :departement_id";
        $params[':departement_id'] = $departement_id;
    }
    if ($status) {
        $sql .= " AND f.status = :status";
        $params[':status'] = $status;
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        "status" => "success",
        "data" => $filieres
    ];

}




public function AjouterModules($data) {
    $this->db->beginTransaction();
    try {
        $filiereId = $data['filiere_id'];

        // --- 1. Get Current Academic Year ---
        $stmtAnnee = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag=1 LIMIT 1");
        $annee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        if (!$annee) throw new Exception("Aucune année académique courante trouvée");
        $anneeId = $annee['annee_id'];


        $db = $this->db;

        $insertRole = function($userId, $role, $start = null, $end = null) use ($db) {
            if (empty($userId)) return;
            $sqlRole = "INSERT INTO professor_roles 
                        (user_id, role, date_debut_affectation, date_fin_affectation)
                        VALUES (:user_id, :role, :date_debut, :date_fin)";
            $stmtRole = $db->prepare($sqlRole);
            $stmtRole->execute([
                ':user_id'   => $userId,
                ':role'      => $role,
                ':date_debut'=> $start,
                ':date_fin'  => $end
            ]);
        };

        // --- 2. Insert Modules & Elements ---
        if (!empty($data['modules']) && is_array($data['modules'])) {
            foreach ($data['modules'] as $module) {
                $semestreGlobal = ($module['etape'] - 1) * 2 + $module['semestre'];

                // Insert module
                $sqlModule = "INSERT INTO modules 
                              (code,langue, nom, coefficient, semestre_id, field_id, responsible_professor_id, annee_id, type) 
                              VALUES 
                              (:code,:langue, :nom, :coefficient, :semestre_id, :field_id, :responsible_professor_id, :annee_id, :type)";
                $stmtModule = $this->db->prepare($sqlModule);
                $stmtModule->execute([
                    ':code' => $module['code'],
                    ':langue' => $module['langue'],
                    ':nom'  => $module['nom'],
                    ':coefficient' => $module['coefficient'] ?? 1,
                    ':semestre_id' => $semestreGlobal,
                    ':field_id'    => $filiereId,
                    ':responsible_professor_id' => $module['chef_id'] ?? null,
                    ':annee_id' => $anneeId,
                    ':type'     => $module['type'] ?? 'standard'
                ]);
                $moduleId = $this->db->lastInsertId();

                // Role chef de module
                if (!empty($module['chef_id'])) {
                    $insertRole($module['chef_id'], "Chef_De_Module");
                }

                // Déterminer les éléments
                $elements = !empty($module['has_elements']) ? $module['elements'] : [[
                    'nom' => $module['nom'],
                    'coeff_element' => 1,
                    'coeff_td' => $module['coeff_td'] ?? 0,
                    'coeff_ecrit' => $module['coeff_ecrit'] ?? 0,
                    'coeff_tp' => $module['coeff_tp'] ?? 0,
                    'coeff_cc' => $module['coeff_cc'] ?? 0,
                    'coeff_projet' => $module['coeff_projet'] ?? 0,
                    'Ref_prof_element' => $module['chef_element'] ?? $module['chef_id'] ?? null,
                    'Ref_prof_tp' => $module['prof_tp'] ?? null,
                    'Ref_prof_td' => $module['prof_td'] ?? null,
                    'Ref_prof_cours' => $module['prof_cours'] ?? null,
                    'presentiel' => $module['volume_presentiel'] ?? 0,
                    'a_distance' => $module['volume_distance'] ?? 0,
                    'en_alternance' => $module['volume_alternance'] ?? 0,
                    'horraire_tp' => $module['volume_tp'] ?? 0,
                    'horraire_td' => $module['volume_td'] ?? 0,
                    'horraire_cours' => $module['volume_cours'] ?? 0,
                    'horraire_evaluation' => $module['volume_evaluation'] ?? 0,
                    'horraire_activite_pratique' => $module['volume_pratique'] ?? 0
                ]];

                foreach ($elements as $element) {
                    $this->insertElement(array_merge($element, ['module_id' => $moduleId]));

                    // --- Assign Element Roles ---
                    if (!empty($element['Ref_prof_element'])) $insertRole($element['Ref_prof_element'], "Chef_De_Element");
                    if (!empty($element['Ref_prof_tp'])) $insertRole($element['Ref_prof_tp'], "Regular");
                    if (!empty($element['Ref_prof_td'])) $insertRole($element['Ref_prof_td'], "Regular");
                    if (!empty($element['Ref_prof_cours'])) $insertRole($element['Ref_prof_cours'], "Regular");
                }

                
            }
        }

        $this->db->commit();
        return true; 

    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Erreur AjouterModules: " . $e->getMessage());
        throw $e;
    }
}


// helper: insérer un élément
private function insertElement($elementData) {
    $sql = "INSERT INTO elements 
            (nom,module_id,coeff_element,coeff_td,coeff_ecrit,coeff_tp,coeff_cc,coeff_projet,
             Ref_prof_element,Ref_prof_tp,Ref_prof_td,Ref_prof_cours,
             presentiel,a_distance,en_alternance,
             horraire_tp,horraire_td,horraire_cours,horraire_evaluation,horraire_activite_pratique)
            VALUES
            (:nom,:module_id,:coeff_element,:coeff_td,:coeff_ecrit,:coeff_tp,:coeff_cc,:coeff_projet,
             :Ref_prof_element,:Ref_prof_tp,:Ref_prof_td,:Ref_prof_cours,
             :presentiel,:a_distance,:en_alternance,
             :horraire_tp,:horraire_td,:horraire_cours,:horraire_evaluation,:horraire_activite_pratique)";
    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        ':nom'=>$elementData['nom'],
        ':module_id'=>$elementData['module_id'],
        ':coeff_element'=>$elementData['coeff_element'] ?? 0,
        ':coeff_td'=>$elementData['coeff_td'] ?? 0,
        ':coeff_ecrit'=>$elementData['coeff_ecrit'] ?? 0,
        ':coeff_tp'=>$elementData['coeff_tp'] ?? 0,
        ':coeff_cc'=>$elementData['coeff_cc'] ?? 0,
        ':coeff_projet'=>$elementData['coeff_projet'] ?? 0,
        ':Ref_prof_element'=>$elementData['Ref_prof_element'] ?? null,
        ':Ref_prof_tp'=>$elementData['Ref_prof_tp'] ?? null,
        ':Ref_prof_td'=>$elementData['Ref_prof_td'] ?? null,
        ':Ref_prof_cours'=>$elementData['Ref_prof_cours'] ?? null,
        ':presentiel'=>$elementData['presentiel'] ?? 0,
        ':a_distance'=>$elementData['a_distance'] ?? 0,
        ':en_alternance'=>$elementData['en_alternance'] ?? 0,
        ':horraire_tp'=>$elementData['horraire_tp'] ?? 0,
        ':horraire_td'=>$elementData['horraire_td'] ?? 0,
        ':horraire_cours'=>$elementData['horraire_cours'] ?? 0,
        ':horraire_evaluation'=>$elementData['horraire_evaluation'] ?? 0,
        ':horraire_activite_pratique'=>$elementData['horraire_activite_pratique'] ?? 0
    ]);
}



public function updateModule($data) {
    $this->db->beginTransaction();
    try {
        if (empty($data['module_id'])) {
            throw new Exception("Module ID manquant pour la mise à jour");
        }

        $moduleId = $data['module_id'];
        $filiereId = $data['filiere_id'];

        // --- 1. Get Current Academic Year ---
        $stmtAnnee = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag=1 LIMIT 1");
        $annee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        if (!$annee) throw new Exception("Aucune année académique courante trouvée");
        $anneeId = $annee['annee_id'];

        // --- 2. Update Module ---
        $sqlModule = "UPDATE modules SET 
                        code = :code,
                        langue = :langue,
                        nom = :nom,
                        type = :type,
                        coefficient = :coefficient,
                        semestre_id = :semestre_id,
                        field_id = :field_id,
                        responsible_professor_id = :responsible_professor_id,
                        annee_id = :annee_id
                      WHERE module_id = :module_id";
        $stmtModule = $this->db->prepare($sqlModule);

        $semestreGlobal = $data['semestre']; // tu calcules si nécessaire avec etape comme dans ajout
        $stmtModule->execute([
            ':code' => $data['code'],
            ':langue' => $data['langue'],
            ':nom' => $data['nom'],
            ':type' => $data['type'] ?? 'standard',
            ':coefficient' => $data['coefficient'] ?? 1,
            ':semestre_id' => $semestreGlobal,
            ':field_id' => $filiereId,
            ':responsible_professor_id' => $data['chef_id'] ?? null,
            ':annee_id' => $anneeId,
            ':module_id' => $moduleId
        ]);

        // --- 3. Update Elements ---
        if (!empty($data['has_elements']) && !empty($data['elements']) && is_array($data['elements'])) {
    foreach ($data['elements'] as $element) {
        if (!empty($element['element_id'])) {
            // Mettre à jour un élément existant
            $sqlElement = "UPDATE elements SET
                nom = :nom,
                coeff_element = :coeff_element,
                coeff_td = :coeff_td,
                coeff_ecrit = :coeff_ecrit,
                coeff_tp = :coeff_tp,
                coeff_cc = :coeff_cc,
                coeff_projet = :coeff_projet,
                Ref_prof_element = :Ref_prof_element,
                Ref_prof_tp = :Ref_prof_tp,
                Ref_prof_td = :Ref_prof_td,
                Ref_prof_cours = :Ref_prof_cours,
                presentiel = :presentiel,
                a_distance = :a_distance,
                en_alternance = :en_alternance,
                horraire_tp = :horraire_tp,
                horraire_td = :horraire_td,
                horraire_cours = :horraire_cours,
                horraire_evaluation = :horraire_evaluation,
                horraire_activite_pratique = :horraire_activite_pratique
                WHERE element_id = :element_id AND module_id = :module_id";

            $stmtElement = $this->db->prepare($sqlElement);
            $stmtElement->execute([
                ':nom' => $element['nom'],
                ':coeff_element' => $element['coeff_element'] ?? 1,
                ':coeff_td' => $element['coeff_td'] ?? 0,
                ':coeff_ecrit' => $element['coeff_ecrit'] ?? 0,
                ':coeff_tp' => $element['coeff_tp'] ?? 0,
                ':coeff_cc' => $element['coeff_cc'] ?? 0,
                ':coeff_projet' => $element['coeff_projet'] ?? 0,
                ':Ref_prof_element' => $element['Ref_prof_element'] ?? null,
                ':Ref_prof_tp' => $element['Ref_prof_tp'] ?? null,
                ':Ref_prof_td' => $element['Ref_prof_td'] ?? null,
                ':Ref_prof_cours' => $element['Ref_prof_cours'] ?? null,
                ':presentiel' => $element['presentiel'] ?? 0,
                ':a_distance' => $element['a_distance'] ?? 0,
                ':en_alternance' => $element['en_alternance'] ?? 0,
                ':horraire_tp' => $element['horraire_tp'] ?? 0,
                ':horraire_td' => $element['horraire_td'] ?? 0,
                ':horraire_cours' => $element['horraire_cours'] ?? 0,
                ':horraire_evaluation' => $element['horraire_evaluation'] ?? 0,
                ':horraire_activite_pratique' => $element['horraire_activite_pratique'] ?? 0,
                ':element_id' => $element['element_id'],
                ':module_id' => $moduleId
            ]);
        } else {
            // Créer un nouvel élément si element_id est vide ou null
            $this->insertElement(array_merge($element, [
                'module_id' => $moduleId,
                'coeff_element' => $element['coeff_element'] ?? 1,
                'nom' => $element['nom']
            ]));
        }
    }
}
else {
    // Module sans éléments -> récupérer l'élément existant pour ce module
    $stmtGetElement = $this->db->prepare("SELECT element_id FROM elements WHERE module_id = :module_id LIMIT 1");
    $stmtGetElement->execute([':module_id' => $moduleId]);
    $existingElement = $stmtGetElement->fetch(PDO::FETCH_ASSOC);

    if ($existingElement) {
        $elementId = $existingElement['element_id'];

        $sqlUpdate = "UPDATE elements SET
            nom = :nom,
            coeff_element = :coeff_element,
            coeff_td = :coeff_td,
            coeff_tp = :coeff_tp,
            coeff_cc = :coeff_cc,
            coeff_projet = :coeff_projet,
            coeff_ecrit = :coeff_ecrit,
            Ref_prof_element = :Ref_prof_element,
            Ref_prof_tp = :Ref_prof_tp,
            Ref_prof_td = :Ref_prof_td,
            Ref_prof_cours = :Ref_prof_cours,
            presentiel = :presentiel,
            a_distance = :a_distance,
            en_alternance = :en_alternance,
            horraire_tp = :horraire_tp,
            horraire_td = :horraire_td,
            horraire_cours = :horraire_cours,
            horraire_evaluation = :horraire_evaluation,
            horraire_activite_pratique = :horraire_activite_pratique
            WHERE module_id = :module_id";

        $stmtUpdate = $this->db->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':nom' => $data['nom'], // nom du module
            ':coeff_element' => 1,
            ':coeff_td' => $data['coeff_td'] ?? 0,
            ':coeff_tp' => $data['coeff_tp'] ?? 0,
            ':coeff_cc' => $data['coeff_cc'] ?? 0,
            ':coeff_projet' => $data['coeff_projet'] ?? 0,
            ':coeff_ecrit' => $data['coeff_ecrit'] ?? 0,
            ':Ref_prof_element' => $data['chef_element'] ?? null,
            ':Ref_prof_tp' => $data['prof_tp'] ?? null,
            ':Ref_prof_td' => $data['prof_td'] ?? null,
            ':Ref_prof_cours' => $data['prof_cours'] ?? null,
            ':presentiel' => $data['volume_presentiel'] ?? 0,
            ':a_distance' => $data['volume_distance'] ?? 0,
            ':en_alternance' => $data['volume_alternance'] ?? 0,
            ':horraire_tp' => $data['volume_tp'] ?? 0,
            ':horraire_td' => $data['volume_td'] ?? 0,
            ':horraire_cours' => $data['volume_cours'] ?? 0,
            ':horraire_evaluation' => $data['volume_evaluation'] ?? 0,
            ':horraire_activite_pratique' => $data['volume_pratique'] ?? 0,
            ':module_id' => $moduleId     
   ]);
    } else {
        // Optionnel : insérer si jamais aucun élément trouvé
        $this->insertElement(array_merge($data, ['module_id' => $moduleId, 'coeff_element' => 1, 'nom' => $data['nom']]));
    }
}


        $this->db->commit();
        return ["status" => "success", "message" => "Module mis à jour avec succès", "module_id" => $moduleId];
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Erreur UpdateModule: " . $e->getMessage());
        throw $e;
    }
}




}
