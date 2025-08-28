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

public function updateFiliere($fieldId, $nom, $departementId, $professeurId, $cycleId, $anneeAccreditation) {
    try {
        $stmt = $this->db->prepare("
            UPDATE filieres 
            SET 
                nom = :nom,
                department_id = :department_id,
                head_professor_id = :head_professor_id,
                cycle_id = :cycle_id,
                annee_accreditation = :annee_accreditation
            WHERE field_id = :field_id
        ");

        $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
        $stmt->bindParam(':department_id', $departementId, PDO::PARAM_INT);
        $stmt->bindParam(':head_professor_id', $professeurId, PDO::PARAM_INT);
        $stmt->bindParam(':cycle_id', $cycleId, PDO::PARAM_INT);
        $stmt->bindParam(':annee_accreditation', $anneeAccreditation, PDO::PARAM_STR);
        $stmt->bindParam(':field_id', $fieldId, PDO::PARAM_INT);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error updating filière: " . $e->getMessage());
        return false;
    }

}


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






        public function getFilieres(){


                    return $this->db->query("
                        SELECT 
                            f.field_id, 
                            f.nom AS nom_filiere,
                            d.department_id,
                            d.nom AS nom_departement, 
                            CONCAT(chef.nom, ' ', chef.prenom) AS nom_professeur_responsable, 
                            d.annee_accreditation,
                            c.nom  AS nom_cycle
                            FROM filieres f 
                            LEFT JOIN professeurs chef ON chef.user_id = f.head_professor_id
                            LEFT JOIN departements d ON d.department_id = f.department_id
                            LEFT JOIN cycles c ON c.cycle_id = f.department_id 
                            ORDER BY f.nom;
                    ")->fetchAll();

        }


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


public function updateModule($id, $code, $nom)
{
    $sql = "UPDATE modules SET code_module = :code, nom_module = :nom WHERE module_id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':code', $code);
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
}

public function deleteModuleElements($moduleId)
{
    // Get all element IDs linked to the module
    $query = "SELECT element_id FROM elements WHERE module_id = :moduleId";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':moduleId', $moduleId);
    $stmt->execute();
    $elementIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Delete from element_filiere
    $del1 = $this->db->prepare("DELETE FROM element_filiere WHERE Ref_element = :id");
    foreach ($elementIds as $eid) {
        $del1->execute([':id' => $eid]);
    }

    // Delete elements
    $del2 = $this->db->prepare("DELETE FROM elements WHERE module_id = :moduleId");
    $del2->bindParam(':moduleId', $moduleId);
    $del2->execute();
}

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
                m.module_id,
                m.code              AS code_module,
                m.nom               AS nom_module,
                m.annee_id            AS annee,
                f.nom               AS nom_filiere,
                s.nom               AS semestre,
                e.element_id,
                e.nom               AS nom_element,
                e.coeff_ecrit,
                e.coeff_cc,
                e.coeff_tp,
                e.coeff_element,
                CONCAT(pe.nom,' ',pe.prenom) AS prof_element,
                CONCAT(pt.nom,' ',pt.prenom) AS prof_tp
            FROM modules m
            LEFT JOIN filieres   f  ON f.field_id   = m.field_id
            LEFT JOIN semestres   s  ON s.semestre_id= m.semestre_id
            LEFT JOIN elements   e  ON e.module_id  = m.module_id
            LEFT JOIN professeurs pe ON pe.user_id  = e.Ref_prof_element
            LEFT JOIN professeurs pt ON pt.user_id  = e.Ref_prof_tp
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
    e.element_id,
    m.module_id,
    m.code AS code_module, 
    m.nom AS nom_module,
    m.annee_id AS annee,
    e.nom AS nom_element,
    e.coeff_ecrit, 
    e.coeff_cc, 
    e.coeff_tp,
    e.coeff_element,
    f.nom AS nom_filiere, 
    s.nom AS semestre, 
    CONCAT(pe.nom, ' ', pe.prenom) AS prof_element, 
    CONCAT(pt.nom, ' ', pt.prenom) AS prof_tp 
FROM modules m 
LEFT JOIN elements e 
    ON e.module_id = m.module_id
LEFT JOIN filieres f 
    ON f.field_id = m.field_id
LEFT JOIN semestres s 
    ON s.semestre_id = m.semestre_id
LEFT JOIN professeurs pe 
    ON pe.user_id = e.Ref_prof_element
LEFT JOIN professeurs pt 
    ON pt.user_id = e.Ref_prof_tp
ORDER BY m.code, e.nom, f.nom, s.nom;

                    ")->fetchAll();
                }


}
