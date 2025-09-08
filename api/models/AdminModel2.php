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

    
    public function GetAllRegularProffessors($role){
        $stmt = $this->db->prepare("
            SELECT p.*
            FROM professeurs p
            WHERE p.user_id NOT IN (
                SELECT user_id FROM professor_roles WHERE role = :role
            )

        ");
        $stmt->execute([":role"=>$role]);
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




                public function GetTeachersWithoutRole(){
                       $stmt=" select * from professeurs p JOIN filieres f ON f.head_professor_id = p.user_id   where " ;
                }


                public function getAllDepart(){


                    return $this->db->query("
                        SELECT 
                            d.date_debut,
                            d.date_fin,
                            d.department_id,
                            d.head_professor_id,
                            d.nom AS nom_departement,
                            CONCAT(chef.nom, ' ', chef.prenom) AS nom_Chef_departement,
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
        // 1. Get the old chef info from the department
        $stmt = $this->db->prepare("
            SELECT head_professor_id, date_debut, date_fin 
            FROM departements 
            WHERE department_id = ?
        ");
        $stmt->execute([$depart_id]);
        $oldChef = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($oldChef) {
            $profId = $oldChef['head_professor_id'];
            $dateDebut = $oldChef['date_debut'];
            $dateFin   = $oldChef['date_fin'];

            // 2. Check if professor_roles contains the same record
            $stmtRole = $this->db->prepare("
                SELECT role_id 
                FROM professor_roles 
                WHERE user_id = :profId
                  AND role = 'Chef_de_Departement'
                  AND (date_debut_affectation = :debut OR (:debut IS NULL AND date_debut_affectation IS NULL))
                  AND (date_fin_affectation = :fin OR (:fin IS NULL AND date_fin_affectation IS NULL))
                LIMIT 1
            ");
            $stmtRole->execute([
                ':profId' => $profId,
                ':debut'  => $dateDebut,
                ':fin'    => $dateFin
            ]);
            $role = $stmtRole->fetch(PDO::FETCH_ASSOC);

            // 3. If found, delete it
            if ($role) {
                $stmtDel = $this->db->prepare("DELETE FROM professor_roles WHERE role_id = :role_id");
                $stmtDel->execute([':role_id' => $role['role_id']]);
            }
        }

        // 4. Delete the department
        $stmtDelDep = $this->db->prepare("DELETE FROM departements WHERE department_id = ?");
        $stmtDelDep->execute([$depart_id]);

        if ($stmtDelDep->rowCount() === 0) {
            throw new PDOException("No department found with department_id = $depart_id");
        }

        $this->db->commit();
        return true;

    } catch (PDOException $e) {
        $this->db->rollBack();
        error_log("Error deleting department: " . $e->getMessage());
        return false;
    }
}


            public function AjouterDepart($nom,$profId,$dateDebut,$dateFin) {

                $stmt = $this->db->prepare("
                    INSERT INTO professor_roles (`role`, `user_id`,`date_debut_affectation`, `date_fin_affectation`) 
                    VALUES ('Chef_de_Departement', :user_id , :debut ,:fin)
                ");
                $stmt->bindParam(':user_id', $profId, PDO::PARAM_INT);
                $stmt->bindParam(':debut', $dateDebut, PDO::PARAM_STR);
                $stmt->bindParam(':fin', $dateFin, PDO::PARAM_STR);
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

          
            public function updateDepart($departementId, $nom, $profId, $anneeAccreditation, $dateDebut, $dateFin) {
                    try {
                        $this->db->beginTransaction();

                        // 1. Get the old chef + dates from departements
                        $stmtOld = $this->db->prepare("
                            SELECT head_professor_id, date_debut, date_fin
                            FROM departements
                            WHERE department_id = :id
                        ");
                        $stmtOld->execute([':id' => $departementId]);
                        $old = $stmtOld->fetch(PDO::FETCH_ASSOC);

                        if ($old && !empty($old['head_professor_id'])) {
                            // 2. Try updating existing professor_roles row
                            $stmtRole = $this->db->prepare("
                                UPDATE professor_roles
                                SET user_id = :new_user,
                                    date_debut_affectation = :new_debut,
                                    date_fin_affectation   = :new_fin
                                WHERE user_id = :old_user
                                AND role = 'Chef_de_Departement'
                                AND date_debut_affectation = :oldD
                                AND date_fin_affectation   = :oldF
                               
                            ");
                            $stmtRole->execute([
                                ':new_user'  => $profId,
                                ':new_debut' => $dateDebut,
                                ':new_fin'   => $dateFin,
                                ':oldD'=>  $old["date_debut"],
                                ':oldF'=>$old["date_fin"],
                                ':old_user'  => $old['head_professor_id'],
                                
                            ]);

                            // 3. If no rows were updated → insert new professor_roles row
                            if ($stmtRole->rowCount() === 0) {
                                $stmtInsert = $this->db->prepare("
                                    INSERT INTO professor_roles (`role`, `user_id`, `date_debut_affectation`, `date_fin_affectation`)
                                    VALUES ('Chef_de_Departement', :user_id, :debut, :fin)
                                ");
                                $stmtInsert->execute([
                                    ':user_id' => $profId,
                                    ':debut'   => $dateDebut,
                                    ':fin'     => $dateFin
                                ]);
                            }
                        } else {
                            // 4. If no old chef → directly insert new one
                            $stmtInsert = $this->db->prepare("
                                INSERT INTO professor_roles (`role`, `user_id`, `date_debut_affectation`, `date_fin_affectation`)
                                VALUES ('Chef_de_Departement', :user_id, :debut, :fin)
                            ");
                            $stmtInsert->execute([
                                ':user_id' => $profId,
                                ':debut'   => $dateDebut,
                                ':fin'     => $dateFin
                            ]);
                        }

                        // 5. Update the departement itself
                        $stmt = $this->db->prepare("
                            UPDATE departements 
                            SET 
                                nom = :nom,
                                head_professor_id = :head_professor_id,
                                annee_accreditation = :annee_accreditation,
                                date_debut = :debut,
                                date_fin = :fin
                            WHERE department_id = :department_id
                        ");
                        $stmt->execute([
                            ':nom'                 => $nom,
                            ':head_professor_id'   => $profId,
                            ':annee_accreditation' => $anneeAccreditation,
                            ':debut'               => $dateDebut,
                            ':fin'                 => $dateFin,
                            ':department_id'       => $departementId
                        ]);

                        $this->db->commit();
                        return true;

                    } catch (PDOException $e) {
                        $this->db->rollBack();
                        error_log("Error updating department: " . $e->getMessage());
                        return false;
                    }
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




public function deleteModule($module_id) {
    $this->db->beginTransaction();

    try {
        // 1. Get elements (ignore if none)
        $stmt = $this->db->prepare("
            SELECT Ref_prof_element, Ref_prof_tp, Ref_prof_cours, Ref_prof_td
            FROM elements
            WHERE module_id = ?
        ");
        $stmt->execute([$module_id]);
        $profs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 2. Delete roles
        $roleMap = [
            "Ref_prof_element" => "Chef_de_Element",
            "Ref_prof_tp"      => "Regular",
            "Ref_prof_cours"   => "Regular",
            "Ref_prof_td"      => "Regular"
        ];

        foreach ($profs as $row) {
            foreach ($roleMap as $col => $role) {
                if (!empty($row[$col])) {
                    $stmtRole = $this->db->prepare("
                        DELETE FROM professor_roles
                        WHERE user_id = :user_id AND role = :role
                    ");
                    $stmtRole->execute([':user_id' => $row[$col], ':role' => $role]);
                }
            }
        }

        // 3. Delete Chef de Module
        $stmt = $this->db->prepare("
            SELECT responsible_professor_id
            FROM modules
            WHERE module_id = ?
        ");
        $stmt->execute([$module_id]);
        $chef = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($chef['responsible_professor_id'])) {
            $stmtRole = $this->db->prepare("
                DELETE FROM professor_roles
                WHERE user_id = :user_id AND role = 'Chef_de_Module'
            ");
            $stmtRole->execute([':user_id' => $chef['responsible_professor_id']]);
        }

        // 4. Delete elements
        $stmt = $this->db->prepare("DELETE FROM elements WHERE module_id = ?");
        $stmt->execute([$module_id]);

        // 5. Delete module
        $stmt = $this->db->prepare("DELETE FROM modules WHERE module_id = ?");
        $stmt->execute([$module_id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("No module found with id $module_id");
        }

        $this->db->commit();
        return true;

    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Error deleting module: " . $e->getMessage());
        return false;
    }
}


public function deleteElement($element_id) {
    $this->db->beginTransaction();

    try {
        // 1. Get professors linked to this element
        $stmt = $this->db->prepare("
            SELECT Ref_prof_element, Ref_prof_tp, Ref_prof_cours, Ref_prof_td
            FROM elements
            WHERE element_id = ?
        ");
        $stmt->execute([$element_id]);
        $prof = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($prof) {
            // 2. Define mapping of columns => role
            $roleMap = [
                "Ref_prof_element" => "Chef_de_Element",
                "Ref_prof_tp"      => "Regular",
                "Ref_prof_cours"   => "Regular",
                "Ref_prof_td"      => "Regular"
            ];

            foreach ($roleMap as $col => $role) {
                if (!empty($prof[$col])) {
                    $stmtRole = $this->db->prepare("
                        DELETE FROM professor_roles
                        WHERE user_id = :user_id AND role = :role
                    ");
                    $stmtRole->execute([
                        ':user_id' => $prof[$col],
                        ':role'    => $role
                    ]);
                }
            }
        }

        // 3. Delete the element itself
        $stmt = $this->db->prepare("DELETE FROM elements WHERE element_id = ?");
        $stmt->execute([$element_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("No element found with id $element_id");
        }

        $this->db->commit();
        return true;

    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Error deleting element: " . $e->getMessage());
        return false;
    }
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









          public function deleteField($fieldId) {
    error_log("Attempting to delete filière with ID = $fieldId");

    $this->db->beginTransaction();

    try {
        // 1. Get head_professor_id + dates from filiere
        $stmt = $this->db->prepare("
            SELECT head_professor_id, debut_affectation, fin_affectation
            FROM filieres
            WHERE field_id = ?
        ");
        $stmt->execute([$fieldId]);
        $filiere = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$filiere) {
            $this->db->rollBack();
            error_log("Rollback: filière not found");
            return false;
        }

        $headProfessorId = $filiere['head_professor_id'];
        $dateDebut       = $filiere['debut_affectation'];
        $dateFin         = $filiere['fin_affectation'];

        error_log("Head professor = $headProfessorId, debut = $dateDebut, fin = $dateFin");

        // 2. Check if the exact role exists
        if (!empty($headProfessorId)) {
            $stmtCheck = $this->db->prepare("
                SELECT Role_ID
                FROM professor_roles
                WHERE user_id = :user_id
                  AND role = 'Chef_de_Filiere'
                  AND date_debut_affectation = :dateDebut
                  AND date_fin_affectation = :dateFin
                LIMIT 1
            ");
            $stmtCheck->execute([
                ':user_id'   => $headProfessorId,
                ':dateDebut' => $dateDebut,
                ':dateFin'   => $dateFin
            ]);

            $role = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($role) {
                // Delete the exact matching role
                $stmtRole = $this->db->prepare("DELETE FROM professor_roles WHERE Role_ID = ?");
                $stmtRole->execute([$role['Role_ID']]);
                error_log("Deleted matching professor_role, rows = " . $stmtRole->rowCount());
            } else {
                error_log("No matching professor_role found → skipping role deletion");
            }
        }

        // 3. Always delete the filiere
        $stmt = $this->db->prepare("DELETE FROM filieres WHERE field_id = ?");
        $stmt->execute([$fieldId]);

        $deletedRows = $stmt->rowCount();
        error_log("filieres delete affected rows = $deletedRows");

        $this->db->commit();
        error_log("Commit successful");

        return true;

    } catch (PDOException $e) {
        $this->db->rollBack();
        error_log("Exception during delete: " . $e->getMessage());
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


    
  
          public function AjouterFiliere1($data) {
    $this->db->beginTransaction();
    try {
        // --- 1. Insert Filière ---
        $sql = "INSERT INTO filieres 
                (nom, department_id, head_professor_id, annee_accreditation, cycle_id, 
                 debut_affectation, fin_affectation, annee_fin_accrediation, status)
                VALUES 
                (:nom, :depart_id, :prof_id, :annee_debut, :cycle_id,
                 :date_debut, :date_fin, :annee_fin, :statut)";
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
        ]);

        $filiereId = $this->db->lastInsertId();
        // --- 2. Get Current Academic Year ---
        $stmtAnnee = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag=1 LIMIT 1");
        $annee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        if (!$annee) throw new Exception("Aucune année académique courante trouvée");
        $anneeId = $annee['annee_id'];

        $sql = "INSERT INTO semestres 
                (nom, annee_id, cycle_id, field_id, etape_id)
                VALUES (:nom, :annee_id, :cycle_id, :field_id, :etape_id)";
        $stmt = $this->db->prepare($sql);

        $nombre_semestre = $this->getNombreSemestresByFiliere($filiereId);
        error_log("Nombre_semestre = " . $nombre_semestre["Nombre_semestre"]);

            for ($i = 1; $i < $nombre_semestre["Nombre_semestre"] + 1; $i++) {
                $etape = ceil($i / 2); // étape dépend du semestre

                $stmt->execute([
                    ':nom'       => "Semestre $i",
                    ':annee_id'  => $anneeId,
                    ':cycle_id'  => $data['cycle_id'],
                    ':field_id'  => $filiereId,
                    ':etape_id'  => $etape,
                ]);
            }

        // 5. Insert diplôme
            $sql = "INSERT INTO diplomes 
                    (nom, department_id, field_id, cycle_id)
                    VALUES (:nom, :depart_id, :field_id, :cycle_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom'       => $data['diplome'],
                ':depart_id' => $data['depart_id'],
                ':field_id'  => $filiereId,
                ':cycle_id'  => $data['cycle_id'],
            ]);

            // --- Helper function for inserting roles ---
            $insertRole = function($userId, $role, $start = null, $end = null) {
                    // Check if this role already exists for the user
                    $checkSql = "SELECT 1 FROM professor_roles WHERE user_id = :user_id AND role = :role LIMIT 1";
                    $stmtCheck = $this->db->prepare($checkSql);
                    $stmtCheck->execute([
                        ':user_id' => $userId,
                        ':role'    => $role
                    ]);

                    // If not exists, insert
                    if (!$stmtCheck->fetchColumn()) {
                        $sqlRole = "INSERT INTO professor_roles (user_id, role, date_debut_affectation, date_fin_affectation)
                                    VALUES (:user_id, :role, :date_debut, :date_fin)";
                        $stmtRole = $this->db->prepare($sqlRole);
                        $stmtRole->execute([
                            ':user_id' => $userId,
                            ':role'    => $role,
                            ':date_debut' => $start,
                            ':date_fin'   => $end
                        ]);
                    }
            };

            // --- Assign Filière Head Role ---
            $insertRole($data['prof_id'], "Chef_de_Filiere", $data['date_debut_affectation'] ?? null, $data['date_fin_affectation'] ?? null);

        

        // --- 3. Insert Modules & Elements ---
        if (!empty($data['modules']) && is_array($data['modules'])) {
                                                        // Get all semestres once
                                    $sql = "SELECT * FROM semestres WHERE field_id = :field_id";
                                    $stmt = $this->db->prepare($sql);
                                    $stmt->execute([':field_id' => $filiereId]);
                                    $semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($data['modules'] as $module) {
                                        $semestreGlobal = ($module['etape'] - 1) * 2 + $module['semestre'];

                                        // Find the matching semestre
                                        $matchedSemestre = null;
                                        foreach ($semestres as $row) {
                                            if ($row["nom"] === "Semestre " . $semestreGlobal) {
                                                $matchedSemestre = $row;
                                                break;
                                            }
                                        }

                                        if (!$matchedSemestre) {
                                            throw new Exception("No matching semestre found for module '{$module['nom']}'");
                                        }

                                        // Insert module
                                        $sqlModule = "INSERT INTO modules 
                                                    (code, langue, nom, coefficient, semestre_id, field_id, responsible_professor_id, annee_id, type) 
                                                    VALUES 
                                                    (:code, :langue, :nom, :coefficient, :semestre_id, :field_id, :responsible_professor_id, :annee_id, :type)";
                                        $stmtModule = $this->db->prepare($sqlModule);
                                        $stmtModule->execute([
                                            ':code' => $module['code'],
                                            ':langue' => $module['langue'],
                                            ':nom' => $module['nom'],
                                            ':coefficient' => $module['coefficient'] ?? 1,
                                            ':semestre_id' => $matchedSemestre["semestre_id"],
                                            ':field_id' => $filiereId,
                                            ':responsible_professor_id' => $module['chef_id'] ?? null,
                                            ':annee_id' => $anneeId,
                                            ':type' => $module['type'] ?? 'standard'
                                        ]);
                                        $moduleId = $this->db->lastInsertId();

                                        // Role chef de module
                                        if (!empty($module['chef_id'])) {
                                            $insertRole($module['chef_id'], "Chef_de_Module");
                                        }

                                        // Elements
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

                                            if (!empty($element['Ref_prof_element'])) $insertRole($element['Ref_prof_element'], "Chef_de_Element");
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
        // Récupération de l'ancien chef depuis filieres
        $sql = "SELECT head_professor_id, debut_affectation, fin_affectation ,cycle_id
                FROM filieres 
                WHERE field_id = :fieldId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':fieldId' => $data['fieldId']]);
        $prvs_role = $stmt->fetch(PDO::FETCH_ASSOC);

        // 1. Update filiere
        $sql = "UPDATE filieres 
                SET nom = :nom,
                    department_id = :depart_id,
                    head_professor_id = :prof_id,
                    annee_accreditation = :annee_debut,
                    cycle_id = :cycle_id,
                    debut_affectation = :date_debut,
                    fin_affectation = :date_fin,
                    annee_fin_accrediation = :annee_fin,
                    status = :statut
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
            ':fieldId'    => $data['fieldId']
        ]);
        // First, check if a diplome exists for this field
        $check = $this->db->prepare("SELECT COUNT(*) FROM diplomes WHERE field_id = :field_id");
        $check->execute([':field_id' => $data['fieldId']]);
        $count = $check->fetchColumn();

        if ($count > 0) {
            // Update existing diplome
            $sql = "UPDATE diplomes 
                    SET nom = :nom,
                        department_id = :depart_id,
                        cycle_id = :cycle_id
                    WHERE field_id = :field_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom'       => $data['diplome'],
                ':depart_id' => $data['depart_id'],
                ':cycle_id'  => $data['cycle_id'],
                ':field_id'  => $data['fieldId']
            ]);
        } else {
            // Insert new diplome
            $sql = "INSERT INTO diplomes (nom, department_id, field_id, cycle_id)
                    VALUES (:nom, :depart_id, :field_id, :cycle_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom'       => $data['diplome'],
                ':depart_id' => $data['depart_id'],
                ':field_id'  => $data['fieldId'],
                ':cycle_id'  => $data['cycle_id']
            ]);
        }


        // --- 2. Get Current Academic Year ---
        $stmtAnnee = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag=1 LIMIT 1");
        $annee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        if (!$annee) throw new Exception("Aucune année académique courante trouvée");
        $anneeId = $annee['annee_id'];

        // Only rebuild semestres if cycle has changed
        if ($prvs_role["cycle_id"] != $data["cycle_id"]) {

    // 1. Delete old semestres (only if exist)
    $sql = "DELETE FROM semestres WHERE field_id = :fieldId";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':fieldId' => $data['fieldId']]);

    // 2. Get the number of semestres for the new cycle
    $nombre_semestre = $this->getNombreSemestresByFiliere($data['fieldId']);
    error_log("Nombre_semestre (new cycle) = " . $nombre_semestre["Nombre_semestre"]);

    // 3. Insert new semestres
    $sql = "INSERT INTO semestres 
            (nom, annee_id, cycle_id, field_id, etape_id)
            VALUES (:nom, :annee_id, :cycle_id, :field_id, :etape_id)";
    $stmt = $this->db->prepare($sql);

    for ($i = 1; $i <= $nombre_semestre["Nombre_semestre"]; $i++) {
        $etape = ceil($i / 2);

        $stmt->execute([
            ':nom'       => "Semestre $i",
            ':annee_id'  => $anneeId, 
            ':cycle_id'  => $data['cycle_id'],
            ':field_id'  => $data['fieldId'],
            ':etape_id'  => $etape,
        ]);
    }

} else {
    // First check if semestres exist for this field_id
    $check = $this->db->prepare("SELECT COUNT(*) FROM semestres WHERE field_id = :fieldId");
    $check->execute([':fieldId' => $data['fieldId']]);
    $count = $check->fetchColumn();
    $nombre_semestre = $this->getNombreSemestresByFiliere($data['fieldId']);

    if ($count === $nombre_semestre["Nombre_semestre"] ) {

        // Update existing ones
        $sql = "UPDATE semestres SET annee_id = :annee_id WHERE field_id = :field_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':annee_id'  => $anneeId,
            ':field_id'  => $data['fieldId'],
        ]);
    } else {
        // Insert fresh if no semestres exist
        $nombre_semestre = $this->getNombreSemestresByFiliere($data['fieldId']);
        error_log("Nombre_semestre (no existing) = " . $nombre_semestre["Nombre_semestre"]);

        $sql = "INSERT INTO semestres 
                (nom, annee_id, cycle_id, field_id, etape_id)
                VALUES (:nom, :annee_id, :cycle_id, :field_id, :etape_id)";
        $stmt = $this->db->prepare($sql);

        for ($i = 1; $i <= $nombre_semestre["Nombre_semestre"]; $i++) {
            $etape = ceil($i / 2);

            $stmt->execute([
                ':nom'       => "Semestre $i",
                ':annee_id'  => $anneeId, 
                ':cycle_id'  => $data['cycle_id'],
                ':field_id'  => $data['fieldId'],
                ':etape_id'  => $etape,
            ]);
        }
    }
}


        

        // 2. Essayer un UPDATE exact
        $stmtRole = $this->db->prepare("
            UPDATE professor_roles
            SET user_id = :user_id,
                role = :role,
                date_debut_affectation = :date_debut,
                date_fin_affectation = :date_fin
            WHERE user_id = :old_user_id
            AND role = 'Chef_de_Filiere'
            
        ");

        $insertRole = function($userId, $role, $start = null, $end = null) {
            // Check if this role already exists for the user
            $checkSql = "SELECT 1 FROM professor_roles WHERE user_id = :user_id AND role = :role LIMIT 1";
            $stmtCheck = $this->db->prepare($checkSql);
            $stmtCheck->execute([
                ':user_id' => $userId,
                ':role'    => $role
            ]);

            // If not exists, insert
            if (!$stmtCheck->fetchColumn()) {
                $sqlRole = "INSERT INTO professor_roles (user_id, role, date_debut_affectation, date_fin_affectation)
                            VALUES (:user_id, :role, :date_debut, :date_fin)";
                $stmtRole = $this->db->prepare($sqlRole);
                $stmtRole->execute([
                    ':user_id' => $userId,
                    ':role'    => $role,
                    ':date_debut' => $start,
                    ':date_fin'   => $end
                ]);
            }
        };

        // --- Check if a Chef_de_Filiere exists for the old professor ---
        $stmtCheck = $this->db->prepare("
            SELECT 1 FROM professor_roles
            WHERE user_id = :old_user_id AND role = 'Chef_de_Filiere'
        ");
        $stmtCheck->execute([':old_user_id' => $prvs_role['head_professor_id'] ?? 0]);
        $exists = $stmtCheck->fetchColumn();

        // --- Update if exists, else insert ---
        if ($exists) {
            $stmtRole = $this->db->prepare("
                UPDATE professor_roles
                SET user_id = :user_id,
                    role = :role,
                    date_debut_affectation = :date_debut,
                    date_fin_affectation = :date_fin
                WHERE user_id = :old_user_id
                AND role = 'Chef_de_Filiere'
                AND  date_debut_affectation = :oldD
                AND   date_fin_affectation = :oldF
            ");
            $stmtRole->execute([
                ':user_id'     => $data['prof_id'],
                ':role'        => 'Chef_de_Filiere',
                ':date_debut'  => $data['date_debut_affectation'] ?? null,
                ':date_fin'    => $data['date_fin_affectation'] ?? null,
                ':old_user_id' => $prvs_role['head_professor_id'] ?? 0,
                ':oldD'=>$prvs_role['debut_affectation'] ?? 0,
                ':oldF'=>$prvs_role['fin_affectation'] ?? 0
            ]);
        } else {
        

            $insertRole($data['prof_id'], "Chef_de_Filiere", $data['date_debut_affectation'] ?? null, $data['date_fin_affectation'] ?? null);
        }
        // --- 3. Ajouter les nouveaux modules (sans supprimer les anciens) ---
        if (!empty($data['modules']) && is_array($data['modules'])) {
                                                       // Get all semestres once
                                    $sql = "SELECT * FROM semestres WHERE field_id = :field_id";
                                    $stmt = $this->db->prepare($sql);
                                    $stmt->execute([':field_id' => $filiereId]);
                                    $semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($data['modules'] as $module) {
                                        $semestreGlobal = ($module['etape'] - 1) * 2 + $module['semestre'];

                                        // Find the matching semestre
                                        $matchedSemestre = null;
                                        foreach ($semestres as $row) {
                                            if ($row["nom"] === "Semestre " . $semestreGlobal) {
                                                $matchedSemestre = $row;
                                                break;
                                            }
                                        }

                                        if (!$matchedSemestre) {
                                            throw new Exception("No matching semestre found for module '{$module['nom']}'");
                                        }

                                        // Insert module
                                        $sqlModule = "INSERT INTO modules 
                                                    (code, langue, nom, coefficient, semestre_id, field_id, responsible_professor_id, annee_id, type) 
                                                    VALUES 
                                                    (:code, :langue, :nom, :coefficient, :semestre_id, :field_id, :responsible_professor_id, :annee_id, :type)";
                                        $stmtModule = $this->db->prepare($sqlModule);
                                        $stmtModule->execute([
                                            ':code' => $module['code'],
                                            ':langue' => $module['langue'],
                                            ':nom' => $module['nom'],
                                            ':coefficient' => $module['coefficient'] ?? 1,
                                            ':semestre_id' => $matchedSemestre["semestre_id"],
                                            ':field_id' => $filiereId,
                                            ':responsible_professor_id' => $module['chef_id'] ?? null,
                                            ':annee_id' => $anneeId,
                                            ':type' => $module['type'] ?? 'standard'
                                        ]);
                                        $moduleId = $this->db->lastInsertId();

                                        // Role chef de module
                                        if (!empty($module['chef_id'])) {
                                            $insertRole($module['chef_id'], "Chef_de_Module");
                                        }

                                        // Elements
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

                                            if (!empty($element['Ref_prof_element'])) $insertRole($element['Ref_prof_element'], "Chef_de_Element");
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
                            c.Nombre_semestre,
                            f.cycle_id,
                            f.debut_affectation,
                            f.fin_affectation,
                            f.annee_fin_accrediation,
                            f.status,         
                            dpl.nom AS nom_diplome
                            FROM filieres f 
                            LEFT JOIN professeurs chef ON chef.user_id = f.head_professor_id
                            LEFT JOIN departements d ON d.department_id = f.department_id
                            LEFT JOIN cycles c ON c.cycle_id = f.`cycle_id`
                            LEFT JOIN diplomes dpl ON dpl.field_id= f.field_id
                            WHERE  1=1 
                            "; 

    $params = [];

    if ($cycle_id) {
        $sql .= " AND f.cycle_id = :cycle_id";
        $params[':cycle_id'] = $cycle_id;
    }
    if ($diplome_id) {
        $sql .= " AND dpl.nom  = :diplome_id";
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

        $insertRole = function($userId, $role, $start = null, $end = null) {
            // Check if this role already exists for the user
            $checkSql = "SELECT 1 FROM professor_roles WHERE user_id = :user_id AND role = :role LIMIT 1";
            $stmtCheck = $this->db->prepare($checkSql);
            $stmtCheck->execute([
                ':user_id' => $userId,
                ':role'    => $role
            ]);

            // If not exists, insert
            if (!$stmtCheck->fetchColumn()) {
                $sqlRole = "INSERT INTO professor_roles (user_id, role, date_debut_affectation, date_fin_affectation)
                            VALUES (:user_id, :role, :date_debut, :date_fin)";
                $stmtRole = $this->db->prepare($sqlRole);
                $stmtRole->execute([
                    ':user_id' => $userId,
                    ':role'    => $role,
                    ':date_debut' => $start,
                    ':date_fin'   => $end
                ]);
            }
        };

        // --- 2. Insert Modules & Elements ---
        if (!empty($data['modules']) && is_array($data['modules'])) {
                                            // Get all semestres once
                                    $sql = "SELECT * FROM semestres WHERE field_id = :field_id";
                                    $stmt = $this->db->prepare($sql);
                                    $stmt->execute([':field_id' => $filiereId]);
                                    $semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($data['modules'] as $module) {
                                        $semestreGlobal = ($module['etape'] - 1) * 2 + $module['semestre'];

                                        // Find the matching semestre
                                        $matchedSemestre = null;
                                        foreach ($semestres as $row) {
                                            if ($row["nom"] === "Semestre " . $semestreGlobal) {
                                                $matchedSemestre = $row;
                                                break;
                                            }
                                        }

                                        if (!$matchedSemestre) {
                                            throw new Exception("No matching semestre found for module '{$module['nom']}'");
                                        }

                                        // Insert module
                                        $sqlModule = "INSERT INTO modules 
                                                    (code, langue, nom, coefficient, semestre_id, field_id, responsible_professor_id, annee_id, type) 
                                                    VALUES 
                                                    (:code, :langue, :nom, :coefficient, :semestre_id, :field_id, :responsible_professor_id, :annee_id, :type)";
                                        $stmtModule = $this->db->prepare($sqlModule);
                                        $stmtModule->execute([
                                            ':code' => $module['code'],
                                            ':langue' => $module['langue'],
                                            ':nom' => $module['nom'],
                                            ':coefficient' => $module['coefficient'] ?? 1,
                                            ':semestre_id' => $matchedSemestre["semestre_id"],
                                            ':field_id' => $filiereId,
                                            ':responsible_professor_id' => $module['chef_id'] ?? null,
                                            ':annee_id' => $anneeId,
                                            ':type' => $module['type'] ?? 'standard'
                                        ]);
                                        $moduleId = $this->db->lastInsertId();

                                        // Role chef de module
                                        if (!empty($module['chef_id'])) {
                                            $insertRole($module['chef_id'], "Chef_de_Module");
                                        }

                                        // Elements
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

                                            if (!empty($element['Ref_prof_element'])) $insertRole($element['Ref_prof_element'], "Chef_de_Element");
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

        $moduleId  = $data['module_id'];
        $filiereId = $data['filiere_id'];

        // --- 1) Année académique courante
        $stmtAnnee = $this->db->query("SELECT annee_id FROM annees_academiques WHERE current_flag = 1 LIMIT 1");
        $annee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
        if (!$annee) throw new Exception("Aucune année académique courante trouvée");
        $anneeId = $annee['annee_id'];

        // Helpers (consistent with AjouterModules)
        $db = $this->db;

        $insertRole = function($userId, $role, $start = null, $end = null) {
            // Check if this role already exists for the user
            $checkSql = "SELECT 1 FROM professor_roles WHERE user_id = :user_id AND role = :role LIMIT 1";
            $stmtCheck = $this->db->prepare($checkSql);
            $stmtCheck->execute([
                ':user_id' => $userId,
                ':role'    => $role
            ]);

            // If not exists, insert
            if (!$stmtCheck->fetchColumn()) {
                $sqlRole = "INSERT INTO professor_roles (user_id, role, date_debut_affectation, date_fin_affectation)
                            VALUES (:user_id, :role, :date_debut, :date_fin)";
                $stmtRole = $this->db->prepare($sqlRole);
                $stmtRole->execute([
                    ':user_id' => $userId,
                    ':role'    => $role,
                    ':date_debut' => $start,
                    ':date_fin'   => $end
                ]);
            }
        };

        $deleteRole = function($userId, $role) use ($db) {
            if (empty($userId)) return;
            $sql = "DELETE FROM professor_roles WHERE user_id = :user_id AND role = :role";
            $st = $db->prepare($sql);
            $st->execute([
                ':user_id' => $userId,
                ':role'    => $role
            ]);
        };

        // --- 2) Avant toute mise à jour: nettoyer les rôles existants

        // 2.a) Chef de module actuel -> supprimer son rôle
        $stmt = $this->db->prepare("SELECT responsible_professor_id FROM modules WHERE module_id = :mid");
        $stmt->execute([':mid' => $moduleId]);
        $oldChef = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($oldChef && !empty($oldChef['responsible_professor_id'])) {
            $deleteRole($oldChef['responsible_professor_id'], "Chef_de_Module");
        }

        // 2.b) Rôles profs d'éléments actuels du module -> supprimer
        $stmt = $this->db->prepare("
            SELECT Ref_prof_element, Ref_prof_tp, Ref_prof_cours, Ref_prof_td
            FROM elements
            WHERE module_id = :mid
        ");
        $stmt->execute([':mid' => $moduleId]);
        $oldElementProfs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($oldElementProfs) {
            $roleMapDelete = [
                'Ref_prof_element' =>'Chef_de_Element', // keep exact casing as in AjouterModules
                'Ref_prof_tp'      => 'Regular',
                'Ref_prof_cours'   => 'Regular',
                'Ref_prof_td'      => 'Regular',
            ];
            foreach ($oldElementProfs as $row) {
                foreach ($roleMapDelete as $col => $role) {
                    if (!empty($row[$col])) {
                        $deleteRole($row[$col], $role);
                    }
                }
            }
        }

        // --- 3) Mettre à jour le module
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

        // Si tu dois recalcule: ($data['etape'] - 1) * 2 + $data['semestre_local']
        $semestreGlobal = $data['semestre'];
        // Get all semestres once
        $sql = "SELECT * FROM semestres WHERE field_id = :field_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':field_id' => $filiereId]);
        $semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $matchedSemestre = null;
        foreach ($semestres as $row) {
            if ($row["nom"] === "Semestre " . $semestreGlobal) {
                $matchedSemestre = $row;
                break;
            }
        }

        if (!$matchedSemestre) {
            throw new Exception("No matching semestre found for module '{$module['nom']}'");
        }
        $stmtModule->execute([
            ':code'                     => $data['code'],
            ':langue'                   => $data['langue'],
            ':nom'                      => $data['nom'],
            ':type'                     => $data['type'] ?? 'standard',
            ':coefficient'              => $data['coefficient'] ?? 1,
            ':semestre_id'              => $matchedSemestre['semestre_id'], 
            ':field_id'                 => $filiereId,
            ':responsible_professor_id' => $data['chef_id'] ?? null,
            ':annee_id'                 => $anneeId,
            ':module_id'                => $moduleId
        ]);

        // 3.b) Nouveau Chef de Module -> insérer
        if (!empty($data['chef_id'])) {
            $insertRole($data['chef_id'], "Chef_de_Module");
        }

        // --- 4) Mettre à jour / insérer les éléments + insérer les nouveaux rôles
        $insertElementRoles = function(array $elementRow) use ($insertRole) {
            if (!empty($elementRow['Ref_prof_element'])) $insertRole($elementRow['Ref_prof_element'], "Chef_de_Element");
            if (!empty($elementRow['Ref_prof_tp']))      $insertRole($elementRow['Ref_prof_tp'],      "Regular");
            if (!empty($elementRow['Ref_prof_td']))      $insertRole($elementRow['Ref_prof_td'],      "Regular");
            if (!empty($elementRow['Ref_prof_cours']))   $insertRole($elementRow['Ref_prof_cours'],   "Regular");
        };

        if (!empty($data['has_elements']) && !empty($data['elements']) && is_array($data['elements'])) {
            foreach ($data['elements'] as $element) {
                if (!empty($element['element_id'])) {
                    // Update existing element
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
                        ':nom'                         => $element['nom'],
                        ':coeff_element'               => $element['coeff_element'] ?? 1,
                        ':coeff_td'                    => $element['coeff_td'] ?? 0,
                        ':coeff_ecrit'                 => $element['coeff_ecrit'] ?? 0,
                        ':coeff_tp'                    => $element['coeff_tp'] ?? 0,
                        ':coeff_cc'                    => $element['coeff_cc'] ?? 0,
                        ':coeff_projet'                => $element['coeff_projet'] ?? 0,
                        ':Ref_prof_element'            => $element['Ref_prof_element'] ?? null,
                        ':Ref_prof_tp'                 => $element['Ref_prof_tp'] ?? null,
                        ':Ref_prof_td'                 => $element['Ref_prof_td'] ?? null,
                        ':Ref_prof_cours'              => $element['Ref_prof_cours'] ?? null,
                        ':presentiel'                  => $element['presentiel'] ?? 0,
                        ':a_distance'                  => $element['a_distance'] ?? 0,
                        ':en_alternance'               => $element['en_alternance'] ?? 0,
                        ':horraire_tp'                 => $element['horraire_tp'] ?? 0,
                        ':horraire_td'                 => $element['horraire_td'] ?? 0,
                        ':horraire_cours'              => $element['horraire_cours'] ?? 0,
                        ':horraire_evaluation'         => $element['horraire_evaluation'] ?? 0,
                        ':horraire_activite_pratique'  => $element['horraire_activite_pratique'] ?? 0,
                        ':element_id'                  => $element['element_id'],
                        ':module_id'                   => $moduleId
                    ]);

                    // Insert roles for the updated assignment
                    $insertElementRoles($element);

                } else {
                    // Insert new element (uses your existing helper)
                    $this->insertElement(array_merge($element, [
                        'module_id'      => $moduleId,
                        'coeff_element'  => $element['coeff_element'] ?? 1,
                        'nom'            => $element['nom']
                    ]));

                    // Insert roles for the new assignment
                    $insertElementRoles($element);
                }
            }
        } else {
            // Module sans éléments explicites : maintenir/mettre à jour 1 élément "agrégat"
            $stmtGetElement = $this->db->prepare("SELECT element_id FROM elements WHERE module_id = :module_id LIMIT 1");
            $stmtGetElement->execute([':module_id' => $moduleId]);
            $existingElement = $stmtGetElement->fetch(PDO::FETCH_ASSOC);

            if ($existingElement) {
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
                    ':nom'                        => $data['nom'],
                    ':coeff_element'              => 1,
                    ':coeff_td'                   => $data['coeff_td'] ?? 0,
                    ':coeff_tp'                   => $data['coeff_tp'] ?? 0,
                    ':coeff_cc'                   => $data['coeff_cc'] ?? 0,
                    ':coeff_projet'               => $data['coeff_projet'] ?? 0,
                    ':coeff_ecrit'                => $data['coeff_ecrit'] ?? 0,
                    ':Ref_prof_element'           => $data['chef_element'] ?? null,
                    ':Ref_prof_tp'                => $data['prof_tp'] ?? null,
                    ':Ref_prof_td'                => $data['prof_td'] ?? null,
                    ':Ref_prof_cours'             => $data['prof_cours'] ?? null,
                    ':presentiel'                 => $data['volume_presentiel'] ?? 0,
                    ':a_distance'                 => $data['volume_distance'] ?? 0,
                    ':en_alternance'              => $data['volume_alternance'] ?? 0,
                    ':horraire_tp'                => $data['volume_tp'] ?? 0,
                    ':horraire_td'                => $data['volume_td'] ?? 0,
                    ':horraire_cours'             => $data['volume_cours'] ?? 0,
                    ':horraire_evaluation'        => $data['volume_evaluation'] ?? 0,
                    ':horraire_activite_pratique' => $data['volume_pratique'] ?? 0,
                    ':module_id'                  => $moduleId     
                ]);

                // Insert roles for the single-element path
                $insertElementRoles([
                    'Ref_prof_element' => $data['chef_element'] ?? null,
                    'Ref_prof_tp'      => $data['prof_tp'] ?? null,
                    'Ref_prof_td'      => $data['prof_td'] ?? null,
                    'Ref_prof_cours'   => $data['prof_cours'] ?? null,
                ]);

            } else {
                // Aucun élément -> en créer un et affecter les rôles
                $this->insertElement(array_merge($data, [
                    'module_id'     => $moduleId,
                    'coeff_element' => 1,
                    'nom'           => $data['nom']
                ]));

                $insertElementRoles([
                    'Ref_prof_element' => $data['chef_element'] ?? null,
                    'Ref_prof_tp'      => $data['prof_tp'] ?? null,
                    'Ref_prof_td'      => $data['prof_td'] ?? null,
                    'Ref_prof_cours'   => $data['prof_cours'] ?? null,
                ]);
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
