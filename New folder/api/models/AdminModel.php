<?php
require_once __DIR__ . '/../utils/Database.php';

class AdminModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }









    /////////////////////////////////////////student//////////////////////////////////////////




public function getAllStudents()
{
    $stmt = $this->db->prepare("
        SELECT
            et.user_id AS user_id,
            et.cne AS cne,
            et.cin AS cin,
            et.nom AS nom,
            et.date_naissance AS date,
            et.prenom AS prenom,
            u.email AS email,
            et.telephone AS telephone,
            d.nom AS departement_nom,
            f.nom AS filiere_nom
        FROM etudiants et
        JOIN utilisateurs u ON u.user_id = et.user_id
        LEFT JOIN departements d ON d.department_id = et.department_id
        LEFT JOIN filieres f ON f.field_id = et.field_id
        where et.actuel=1
        ORDER BY et.nom, et.prenom
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}public function getStudentDetail($user_id)
{
    $stmt = $this->db->prepare("
        SELECT
            et.*,
            u.email,
            d.nom AS departement_nom,
            f.nom AS filiere_nom
        FROM etudiants et
        JOIN utilisateurs u ON u.user_id = et.user_id
        LEFT JOIN departements d ON d.department_id = et.department_id
        LEFT JOIN filieres f ON f.field_id = et.field_id
        WHERE et.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
public function createStudent(array $data) {
    $db = $this->db;
    $db->beginTransaction();

    try {
        // 1. Validate required fields
        $required = ['username', 'password', 'email', 'cin', 'cne', 'nom', 'prenom', 
                    'date_naissance', 'department_id', 'field_id', 'cycle_id'];
        // foreach ($required as $field) {
        //     if (empty($data[$field])) {
        //         throw new Exception("Missing required field: $field");
        //     }
        // }

        // 2. Validate academic year exists if provided
        if (isset($data['annee_id'])) {
            $stmt = $db->prepare("SELECT 1 FROM annees_academiques WHERE annee_id = ?");
            $stmt->execute([$data['annee_id']]);
            if (!$stmt->fetch()) {
                throw new Exception("Invalid academic year specified");
            }
        }

        // 3. Validate semester exists if provided
        if (isset($data['semestre_id'])) {
            $stmt = $db->prepare("SELECT 1 FROM semestres WHERE semestre_id = ?");
            $stmt->execute([$data['semestre_id']]);
            if (!$stmt->fetch()) {
                throw new Exception("Invalid semester specified");
            }
        }

        // 4. Check for existing user
        $stmt = $db->prepare("SELECT user_id FROM utilisateurs WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$data['username'], $data['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Username or email already exists");
        }

        // 5. Create user account
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO utilisateurs (username, password_hash, email, role) VALUES (?, ?, ?, 'student')");
        if (!$stmt->execute([$data['username'], $passwordHash, $data['email']])) {
            throw new Exception("Failed to create user account");
        }
        
        $userId = $db->lastInsertId();
        if ($userId <= 0) {
            throw new Exception("Failed to generate valid user ID");
        }

        // 6. Create student record
        $stmt = $db->prepare("
            INSERT INTO etudiants (
                user_id, cin, cne, nom, prenom, date_naissance,
                nationalite, telephone, adresse, department_id,
                field_id, cycle_id, group_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $studentData = [
            $userId,
            $data['cin'],
            $data['cne'],
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'],
            $data['nationalite'] ?? null,
            $data['telephone'] ?? null,
            $data['adresse'] ?? null,
            (int)$data['department_id'],
            (int)$data['field_id'],
            (int)$data['cycle_id'],
            $data['group_id'] ?? null
        ];
        
        if (!$stmt->execute($studentData)) {
            throw new Exception("Failed to create student record");
        }

        // 7. Create enrollment record only if all required fields exist
        if (!empty($data['annee_id']) && !empty($data['semestre_id'])) {
            $stmt = $db->prepare("
                INSERT INTO student_enrollments 
                (student_id, annee_id, semestre_id, cycle_id, group_id, section_id, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $enrollmentData = [
                $userId,
                $data['annee_id'],
                (int)($data['semestre_id'] ?? 1),
                (int)($data['cycle_id'] ?? 1),
                $data['group_id'] ?? null,
                $data['section_id'] ?? null,
            ];
            
            if (!$stmt->execute($enrollmentData)) {
                throw new Exception("Failed to create enrollment record");
            }
        }

        $db->commit();
        return ['success' => true, 'user_id' => $userId];

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Student creation error: " . $e->getMessage());
        throw new Exception("Student creation failed: " . $e->getMessage());
    }
}
  public function updateStudent($id, $data)
{
    $db = $this->db;
    $db->beginTransaction();

    try {
        // 1. Validate required fields
        $required = ['username', 'email', 'cin', 'cne', 'nom', 'prenom', 
                     'date_naissance', 'department_id', 'field_id', 'cycle_id'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // 2. Validate academic year exists if provided
        if (isset($data['annee_id'])) {
            $stmt = $db->prepare("SELECT 1 FROM annees_academiques WHERE annee_id = ?");
            $stmt->execute([$data['annee_id']]);
            if (!$stmt->fetch()) {
                throw new Exception("Invalid academic year specified");
            }
        }

        // 3. Validate semester exists if provided
        if (isset($data['semestre_id'])) {
            $stmt = $db->prepare("SELECT 1 FROM semestres WHERE semestre_id = ?");
            $stmt->execute([$data['semestre_id']]);
            if (!$stmt->fetch()) {
                throw new Exception("Invalid semester specified");
            }
        }

        // 4. Update utilisateurs
        $stmt = $db->prepare("
            UPDATE utilisateurs 
            SET username = ?, email = ?
            WHERE user_id = ?
        ");
        if (!$stmt->execute([$data['username'], $data['email'], $id])) {
            throw new Exception("Failed to update user account");
        }

        // 5. Update etudiants
        $stmt = $db->prepare("
            UPDATE etudiants SET
                cin = ?, cne = ?, nom = ?, prenom = ?, date_naissance = ?,
                nationalite = ?, telephone = ?, adresse = ?, department_id = ?,
                field_id = ?, cycle_id = ?, group_id = ?
            WHERE user_id = ?
        ");
        if (!$stmt->execute([
            $data['cin'],
            $data['cne'],
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'],
            $data['nationalite'] ?? null,
            $data['telephone'] ?? null,
            $data['adresse'] ?? null,
            (int)$data['department_id'],
            (int)$data['field_id'],
            (int)$data['cycle_id'],
            $data['group_id'] ?? null,
            $id
        ])) {
            throw new Exception("Failed to update student record");
        }

        // 6. Update or Insert enrollment if data is available
        if (!empty($data['annee_id']) && !empty($data['semestre_id'])) {
            // Check if enrollment already exists
            $stmt = $db->prepare("SELECT 1 FROM student_enrollments WHERE student_id = ?");
            $stmt->execute([$id]);
            $enrollmentExists = $stmt->fetch();

            if ($enrollmentExists) {
                // Update existing enrollment
                $stmt = $db->prepare("
                    UPDATE student_enrollments
                    SET annee_id = ?, semestre_id = ?, cycle_id = ?, group_id = ?, section_id = ?, status = 'active'
                    WHERE student_id = ?
                ");
                if (!$stmt->execute([
                    $data['annee_id'],
                    $data['semestre_id'],
                    (int)$data['cycle_id'],
                    $data['group_id'] ?? null,
                    $data['section_id'] ?? null,
                    $id
                ])) {
                    throw new Exception("Failed to update enrollment record");
                }
            } else {
                // Insert new enrollment
                $stmt = $db->prepare("
                    INSERT INTO student_enrollments 
                    (student_id, annee_id, semestre_id, cycle_id, group_id, section_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                ");
                if (!$stmt->execute([
                    $id,
                    $data['annee_id'],
                    $data['semestre_id']?? 1,
                    $data['cycle_id']?? 1,
                    $data['group_id'] ?? null,
                    $data['section_id'] ?? null
                ])) {
                    throw new Exception("Failed to insert enrollment record");
                }
            }
        }

        $db->commit();
        return ['success' => true];

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Student update error: " . $e->getMessage());
        throw new Exception("Student update failed: " . $e->getMessage());
    }
}



    public function deleteStudent($id)
    {
        $this->db->beginTransaction();

        try {
            // Supprimer d'abord l'étudiant
            $stmt = $this->db->prepare("UPDATE etudiants SET actuel = '0' WHERE user_id = ?");
            $stmt->execute([$id]);

            // Puis supprimer l'utilisateur
            $stmt = $this->db->prepare("UPDATE utilisateurs SET actuel = '0' WHERE user_id = ?");
            $stmt->execute([$id]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error deleting student: " . $e->getMessage());
            return false;
        }
    }
public function getFilteredStudents(
    ?string $annee_id = null,
    ?int $field_id = null,
    ?int $semestreId = null,
    ?int $sectionId = null,
    ?int $groupId = null,
    ?int $cycle_id = null,
    ?int $department_id = null,
    bool $includeEnrollmentInfo = false,
    ?string $search = null
) {
    $sql = "
        SELECT
            et.user_id AS user_id,
            et.cne AS cne,
            et.cin AS cin,
            et.nom AS nom,
            et.prenom AS prenom,
            et.date_naissance AS date,
            u.email AS email,
            et.telephone AS telephone,
            d.nom AS departement_nom,
            f.nom AS filiere_nom";

    if ($includeEnrollmentInfo) {
        $sql .= ",
            aa.annee_id AS annee_id";
    }

    $sql .= "
        FROM etudiants et
        JOIN utilisateurs u ON u.user_id = et.user_id
        LEFT JOIN departements d ON d.department_id = et.department_id
        LEFT JOIN filieres f ON f.field_id = et.field_id
        LEFT JOIN student_enrollments se ON se.student_id = et.user_id
        LEFT JOIN annees_academiques aa ON aa.annee_id = se.annee_id
        LEFT JOIN semestres s ON s.semestre_id = se.semestre_id
        
        LEFT JOIN sections sec ON sec.section_id = se.section_id
        LEFT JOIN groupes g ON g.group_id = se.group_id
        LEFT JOIN cycles c ON c.cycle_id = se.cycle_id
        WHERE et.actuel = 1";

    $params = [];

    if ($annee_id !== null) {
        $sql .= " AND aa.annee_id = :annee_id";
        $params[':annee_id'] = $annee_id;
    }

    if ($department_id !== null) {
        $sql .= " AND et.department_id = :department_id";
        $params[':department_id'] = $department_id;
    }

    if ($field_id !== null) {
        $sql .= " AND f.field_id = :field_id";
        $params[':field_id'] = $field_id;
    }

    if ($cycle_id !== null) {
        $sql .= " AND et.cycle_id = :cycle_id";
        $params[':cycle_id'] = $cycle_id;
    }
    if ($semestreId !== null) {
        $sql .= " AND s.semestre_id = :semestre_id";
        $params[':semestre_id'] = $semestreId;
    }

    if ($sectionId !== null) {
        $sql .= " AND sec.section_id = :section_id";
        $params[':section_id'] = $sectionId;
    }

    if ($groupId !== null) {
        $sql .= " AND g.group_id = :group_id";
        $params[':group_id'] = $groupId;
    }

    if ($search !== null && $search !== '') {
        $sql .= " AND (
            et.nom LIKE :search OR
             et.prenom LIKE :search OR
            et.cne LIKE :search OR
            et.cin LIKE :search OR
            d.nom LIKE :search OR
            f.nom LIKE :search OR
            u.email LIKE :search OR
            et.date_naissance LIKE :search 
        )";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY et.nom, et.prenom";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function assignModules(int $id, array $data) {
        if (empty($data)) {
            return [
                'status' => 400,
                'data' => [
                    'success' => false,
                    'message' => 'No module IDs provided'
                ]
            ];
        }

        // Validate user_id exists in the database
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $id]);
        if ($stmt->fetchColumn() == 0) {
            return [
                'status' => 400,
                'data' => [
                    'success' => false,
                    'message' => 'Invalid user_id'
                ]
            ];
        }

        // Delete existing module assignments
        $deleteSql = "DELETE FROM student_modules WHERE student_id = :user_id";
        $deleteStmt = $this->db->prepare($deleteSql);
        $deleteStmt->execute([':user_id' => $id]);

        // Insert new module assignments
        $insertSql = "INSERT INTO student_modules (student_id, module_id) VALUES ";
        $params = [':user_id' => $id];
        $valueParts = [];
        $i = 0;
        foreach ($data as $moduleId) {
            $paramName = ":module_id_" . $i;
            $valueParts[] = "(:user_id, $paramName)";
            $params[$paramName] = $moduleId;
            $i++;
        }

        if (empty($valueParts)) {
            return [
                'status' => 400,
                'data' => [
                    'success' => false,
                    'message' => 'No valid module IDs provided'
                ]
            ];
        }

        $insertSql .= implode(", ", $valueParts);

        try {
            $this->db->beginTransaction();
            $insertStmt = $this->db->prepare($insertSql);
            $insertStmt->execute($params);
            $this->db->commit();
            
            return [
                'status' => 200,
                'data' => [
                    'success' => true,
                    'message' => 'Modules assigned successfully',
                    'assigned_modules' => $data,
                    'student_id' => $id
                ]
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return [
                'status' => 500,
                'data' => [
                    'success' => false,
                    'message' => 'Failed to assign modules',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }


    public function getAllFilieres()
    {
        return $this->db->query("SELECT field_id, nom FROM filieres ORDER BY nom")->fetchAll();
    }
      public function getAllCycles()
    {
        return $this->db->query("SELECT * FROM cycles ")->fetchAll();
    }
    public function getAllSections()
    {
        return $this->db->query("SELECT section_id, nom FROM sections ORDER BY nom")->fetchAll();
    }
    public function getAllGroups()
    {
        return $this->db->query("SELECT group_id, nom FROM groupes ORDER BY nom")->fetchAll();
    }
    public function getAllEtapes()
    {
        $stmt = $this->db->query("SELECT *
        FROM etapes");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAllSemesteres(){
        return $this->db->query("SELECT * FROM semestres ORDER BY nom")->fetchAll();
    }
    public function getAllModule(){
        return $this->db->query("SELECT 
        m.module_id,    
        m.code,
        m.nom as module_nom,
        m.semestre_id as module_semestre_id,
        m.field_id as module_field_id,
        m.annee_id as module_annee_id ,
        m.responsible_professor_id,
        m.coefficient,
        s.semestre_id as semestre_id,
        s.nom as semestre_nom,
        c.cycle_id,
        f.field_id as semestre_field_id,
        e.etape_id
        FROM 
        modules m, 
        semestres s,
        etapes e,
        cycles c,
        filieres f
         limit 10")->fetchAll();
        
    }
    public function getFilierByDepartement($depId){
        $stmt=$this->db->prepare( " SELECT field_id , nom FROM filieres WHERE department_id= ? ");
        $stmt->execute([$depId]);
        return $stmt->fetchAll();
    }
    public function getSectionsByFiliere($fieldId)
    {
        $stmt = $this->db->prepare("SELECT section_id, nom FROM sections WHERE field_id = ? ORDER BY nom");
        $stmt->execute([$fieldId]);
        return $stmt->fetchAll();
    }

    public function getGroupesBySection($sectionId)
    {
        $stmt = $this->db->prepare("SELECT group_id, nom FROM groupes WHERE section_id = ? ORDER BY nom");
        $stmt->execute([$sectionId]);
        return $stmt->fetchAll();
    }

    public function getGroupesByFiliere($fieldId)
    {
        $stmt = $this->db->prepare("SELECT group_id, nom FROM groupes WHERE field_id = ? ORDER BY nom");
        $stmt->execute([$fieldId]);
        return $stmt->fetchAll();
    }




    public function getYear()
    {
        $stmt = $this->db->query("SELECT annee_id, current_flag 
        FROM annees academiques");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCycle()
    {
        $stmt = $this->db->query("SELECT cycle_id, nom
        FROM cycles");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function getFiliereByCycle($cycle_id)
    {
        return $this->db->query("SELECT field_id, nom FROM filieres Where cycle_id=$cycle_id ORDER BY nom")->fetchAll();
    }



   




    // 🔎 Afficher tous les détails d'un professeur



    public function getAllYears()
    {
        $stmt = $this->db->query("SELECT * FROM annees_academiques");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllDepartments()
    {
        $stmt = $this->db->query("SELECT department_id, nom FROM departements ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllModules()
    {
        $stmt = $this->db->query("SELECT module_id, nom FROM modules");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllElements()
    {
        $stmt = $this->db->query("SELECT element_id, nom FROM elements");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



     /////////////////////////////////////profs_Model/////////////////////////////////////////////////////////

     public function addProfessor($userData, $profData, $roles = [])
    {
        $this->db->beginTransaction();
        try {
            // Input validation
            if (
                empty($userData['username']) ||
                empty($userData['password']) ||
                empty($userData['email']) ||
                empty($userData['role'])
            ) {
                throw new Exception("User data is incomplete");
            }
            if (
                empty($profData['cin']) ||
                empty($profData['nom']) ||
                empty($profData['prenom']) ||
                empty($profData['department_id'])
            ) {
                throw new Exception("Professor data is incomplete");
            }
            // Check for duplicate email
            $stmt = $this->db->prepare("SELECT user_id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$userData['email']]);
            if ($stmt->fetch()) {
                throw new Exception("Email déjà utilisé");
            }
            
            // Get current academic year
            $stmt = $this->db->prepare("SELECT annee_id FROM annees_academiques WHERE current_flag = 1");
            $stmt->execute();
            $annee = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$annee) {
                throw new Exception("Aucune année académique courante trouvée");
            }
            $anneeId = $annee['annee_id'];
            // Insert user with role
            $stmt = $this->db->prepare("
                INSERT INTO utilisateurs (username, password_hash, email, role, actuel)
                VALUES (?, ?, ?, ?, ?)
            ");
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT);
            $stmt->execute([
                $userData['username'],
                $passwordHash,
                $userData['email'],
                $userData['role'], // Role is now stored in utilisateurs
                1
            ]);
            $userId = $this->db->lastInsertId();
            if (!$userId || $userId == 0) {
                throw new Exception("Échec lors de la récupération de l'identifiant utilisateur");
            }
            // Insert professor
            $stmt = $this->db->prepare("
                INSERT INTO professeurs (user_id, cin, nom, prenom, telephone, department_id, annee_id, actuel)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $profData['cin'],
                $profData['nom'],
                $profData['prenom'],
                $profData['telephone'] ?? null,
                $profData['department_id'],
                $anneeId,
                1
            ]);
            // Update department or filiere head if applicable
            if ($userData['role'] === 'chef_dep') {
                $stmt = $this->db->prepare("
                    UPDATE departements SET head_professor_id = ?
                    WHERE department_id = ?
                ");
                $stmt->execute([$userId, $profData['department_id']]);
            }
            if ($userData['role'] === 'chef_fill' && !empty($profData['field_id'])) {
                $stmt = $this->db->prepare("
                    UPDATE filieres SET head_professor_id = ?
                    WHERE field_id = ?
                ");
                $stmt->execute([$userId, $profData['field_id']]);
            }
            $this->db->commit();
            return [
                'status' => 201,
                'data' => [
                    'message' => 'Professor created successfully',
                    'user_id' => $userId
                ]
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("addProfessor: " . $e->getMessage());
            return [
                'status' => 500,
                'data' => ['message' => 'SQL Error: ' . $e->getMessage()]
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("addProfessor: " . $e->getMessage());
            return [
                'status' => 400,
                'data' => ['message' => 'Application Error: ' . $e->getMessage()]
            ];
        }
    }

   
    public function getAllProfessors($search = '')
{
    try {
        $query = "
            SELECT p.*, u.email, u.role, d.nom AS department_nom 
            FROM professeurs p
            JOIN utilisateurs u ON p.user_id = u.user_id
            JOIN departements d ON p.department_id = d.department_id
            WHERE p.actuel = 1
        ";
        if ($search) {
            $query .= " AND (p.nom LIKE :search OR p.prenom LIKE :search OR u.email LIKE :search)";
        }
        $stmt = $this->db->prepare($query);
        if ($search) {
            $stmt->bindValue(':search', '%' . $search . '%');
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getAllProfessors[search=$search]: " . $e->getMessage());
        return [];
    }
}

    public function updateProfessor($userId, $profData, $roles = [])
    {
        $this->db->beginTransaction();
        try {
            if (
                empty($profData['cin']) ||
                empty($profData['nom']) ||
                empty($profData['prenom']) ||
                empty($profData['department_id'])
            ) {
                throw new Exception("Professor data is incomplete");
            }
            if (!empty($profData['email'])) {
                $stmt = $this->db->prepare("SELECT user_id FROM utilisateurs WHERE email = ? AND user_id != ?");
                $stmt->execute([$profData['email'], $userId]);
                if ($stmt->fetch()) {
                    throw new Exception("Email déjà utilisé par un autre utilisateur");
                }
            }
            $stmt = $this->db->prepare("SELECT user_id FROM utilisateurs WHERE user_id = ?");
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                throw new Exception("Utilisateur non trouvé");
            }
            $anneeId = $profData['annee_id'] ?? null;
            if (empty($anneeId)) {
                $stmt = $this->db->prepare("SELECT annee_id FROM annees_academiques WHERE current_flag = 1");
                $stmt->execute();
                $annee = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$annee) {
                    throw new Exception("Aucune année académique courante trouvée");
                }
                $anneeId = $annee['annee_id'];
            }
            // Update utilisateurs
            if (!empty($profData['email']) && !empty($profData['role'])) {
                if (!empty($profData['password'])) {
                    $passwordHash = password_hash($profData['password'], PASSWORD_BCRYPT);
                    $stmt = $this->db->prepare("
                        UPDATE utilisateurs
                        SET email = ?, role = ?, password_hash = ?, actuel = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([
                        $profData['email'],
                        $profData['role'],
                        $passwordHash,
                        1,
                        $userId
                    ]);
                } else {
                    $stmt = $this->db->prepare("
                        UPDATE utilisateurs
                        SET email = ?, role = ?, actuel = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([
                        $profData['email'],
                        $profData['role'],
                        1,
                        $userId
                    ]);
                }
            }
            // Update professeurs
            $stmt = $this->db->prepare("
                UPDATE professeurs
                SET cin = ?, nom = ?, prenom = ?, telephone = ?, department_id = ?, annee_id = ?, actuel = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $profData['cin'],
                $profData['nom'],
                $profData['prenom'],
                $profData['telephone'] ?? null,
                $profData['department_id'],
                $anneeId,
                1,
                $userId
            ]);
            // Clear and update department or filiere head
            if ($profData['role'] === 'chef_dep') {
                $stmt = $this->db->prepare("UPDATE departements SET head_professor_id = NULL WHERE head_professor_id = ?");
                $stmt->execute([$userId]);
                $stmt = $this->db->prepare("
                    UPDATE departements SET head_professor_id = ?
                    WHERE department_id = ?
                ");
                $stmt->execute([$userId, $profData['department_id']]);
            } else {
                $stmt = $this->db->prepare("UPDATE departements SET head_professor_id = NULL WHERE head_professor_id = ?");
                $stmt->execute([$userId]);
            }
            if ($profData['role'] === 'chef_fill' && !empty($profData['field_id'])) {
                $stmt = $this->db->prepare("UPDATE filieres SET head_professor_id = NULL WHERE head_professor_id = ?");
                $stmt->execute([$userId]);
                $stmt = $this->db->prepare("
                    UPDATE filieres SET head_professor_id = ?
                    WHERE field_id = ?
                ");
                $stmt->execute([$userId, $profData['field_id']]);
            } else {
                $stmt = $this->db->prepare("UPDATE filieres SET head_professor_id = NULL WHERE head_professor_id = ?");
                $stmt->execute([$userId]);
            }
            $this->db->commit();
            return [
                'status' => 200,
                'data' => [
                    'message' => 'Professor updated successfully',
                    'user_id' => $userId
                ]
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("updateProfessor[userId=$userId]: " . $e->getMessage());
            return [
                'status' => 500,
                'data' => ['message' => 'SQL Error: ' . $e->getMessage()]
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("updateProfessor[userId=$userId]: " . $e->getMessage());
            return [
                'status' => 400,
                'data' => ['message' => 'Application Error: ' . $e->getMessage()]
            ];
        }
    }

    public function deleteProfessor($userId)
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE professeurs SET actuel = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stmt = $this->db->prepare("UPDATE utilisateurs SET actuel = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stmt = $this->db->prepare("UPDATE departements SET head_professor_id = NULL WHERE head_professor_id = ?");
            $stmt->execute([$userId]);
            $stmt = $this->db->prepare("UPDATE filieres SET head_professor_id = NULL WHERE head_professor_id = ?");
            $stmt->execute([$userId]);
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("deleteProfessor[userId=$userId]: " . $e->getMessage());
            return false;
        }
    }

    public function getProfessorDetails($userId)
{
    try {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.username, u.email, u.role AS system_role, 
                   p.cin, p.nom, p.prenom, p.telephone, p.department_id, 
                   f.field_id AS assigned_field_id
            FROM utilisateurs u
            JOIN professeurs p ON u.user_id = p.user_id
            LEFT JOIN filieres f ON f.head_professor_id = u.user_id
            WHERE u.user_id = ? AND u.actuel = 1 AND p.actuel = 1
        ");
        $stmt->execute([$userId]);
        $professor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$professor) {
            return [
                'success' => false,
                'message' => 'Professeur non trouvé'
            ];
        }
        return [
            'success' => true,
            'data' => $professor
        ];
    } catch (PDOException $e) {
        error_log("getProfessorDetails[userId=$userId]: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur serveur: ' . $e->getMessage()
        ];
    }
}

    public function getProfessorByDepartment($departmentId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.email 
                FROM professeurs p
                JOIN utilisateurs u ON p.user_id = u.user_id
                WHERE p.department_id = ? AND p.actuel = 1
            ");
            $stmt->execute([$departmentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getProfessorByDepartment[departmentId=$departmentId]: " . $e->getMessage());
            return [];
        }
    }

   public function getProfessorsByYear($anneeId)
{
    try {
        $query = "
            SELECT DISTINCT p.user_id, p.nom, p.prenom, p.telephone, p.cin, u.email, u.role, d.nom AS department_nom
            FROM professeurs p
            JOIN utilisateurs u ON p.user_id = u.user_id
            JOIN departements d ON p.department_id = d.department_id
            WHERE (p.annee_id = ? OR p.annee_id IS NULL) AND (p.actuel = 1 OR p.actuel IS NULL)
            GROUP BY p.user_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$anneeId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->prepare("
            SELECT COUNT(DISTINCT p.user_id) as total 
            FROM professeurs p 
            WHERE (p.annee_id = ? OR p.annee_id IS NULL) AND (p.actuel = 1 OR p.actuel IS NULL)
        ");
        $countStmt->execute([$anneeId]);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return ['success' => true, 'data' => $results, 'total' => $total];
    } catch (PDOException $e) {
        error_log("getProfessorsByYear[anneeId=$anneeId]: " . $e->getMessage());
        return ['success' => false, 'data' => [], 'total' => 0, 'message' => 'Erreur serveur lors du chargement des professeurs.'];
    }
}

    public function getProfessorsByDepartmentAndYear($departmentId, $anneeId, $search = '')
{
    try {
        $query = "
            SELECT DISTINCT p.user_id, p.nom, p.prenom, p.telephone, p.cin, u.email, u.role, d.nom AS department_nom
            FROM professeurs p
            JOIN utilisateurs u ON p.user_id = u.user_id
            LEFT JOIN departements d ON p.department_id = d.department_id
            WHERE p.department_id = ? AND (p.annee_id = ? OR p.annee_id IS NULL) AND p.actuel = 1
        ";
        $params = [$departmentId, $anneeId];
        if ($search) {
            $query .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR u.email LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        $query .= " GROUP BY p.user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'data' => $results];
    } catch (PDOException $e) {
        error_log("getProfessorsByDepartmentAndYear[departmentId=$departmentId,anneeId=$anneeId,search=$search]: " . $e->getMessage());
        return ['success' => false, 'data' => [], 'message' => 'Erreur serveur lors du chargement des professeurs: ' . $e->getMessage()];
    }
}

   public function getAllProfessorsCombined($search = '', $anneeId = null, $departmentId = null, $page = 1, $limit = 10)
{
    try {
        $offset = ($page - 1) * $limit;

        $query = "
            SELECT DISTINCT 
                p.user_id, p.nom, p.prenom, p.telephone, p.cin, u.email, 
                d.nom AS department_nom,
                GROUP_CONCAT(DISTINCT m.nom SEPARATOR ', ') AS modules,
                GROUP_CONCAT(DISTINCT ts.nom SEPARATOR ', ') AS tp_sessions
            FROM professeurs p
            JOIN utilisateurs u ON p.user_id = u.user_id
            LEFT JOIN departements d ON p.department_id = d.department_id
            LEFT JOIN professor_module pm ON p.user_id = pm.user_id
            LEFT JOIN modules m ON pm.module_id = m.module_id
            LEFT JOIN professor_tp_session pts ON p.user_id = pts.user_id
            LEFT JOIN tp_sessions ts ON pts.tp_id = ts.tp_id
            WHERE p.actuel = 1
        ";

        $params = [];

        if ($anneeId !== null) {
            $query .= " AND (m.annee_id = ? OR p.annee_id = ? OR p.annee_id IS NULL)";
            $params[] = $anneeId;
            $params[] = $anneeId;
        }

        if ($departmentId !== null) {
            $query .= " AND p.department_id = ?";
            $params[] = $departmentId;
        }

        if (!empty($search)) {
            $query .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR u.email LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $query .= " GROUP BY p.user_id LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcul du total pour la pagination
        $countQuery = "
            SELECT COUNT(DISTINCT p.user_id) as total
            FROM professeurs p
            JOIN utilisateurs u ON p.user_id = u.user_id
            LEFT JOIN modules m ON p.user_id = m.module_id
            WHERE p.actuel = 1
        ";
        $countParams = [];

        if ($anneeId !== null) {
            $countQuery .= " AND (m.annee_id = ? OR p.annee_id = ? OR p.annee_id IS NULL)";
            $countParams[] = $anneeId;
            $countParams[] = $anneeId;
        }

        if ($departmentId !== null) {
            $countQuery .= " AND p.department_id = ?";
            $countParams[] = $departmentId;
        }

        if (!empty($search)) {
            $countQuery .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR u.email LIKE ?)";
            $searchParam = '%' . $search . '%';
            $countParams[] = $searchParam;
            $countParams[] = $searchParam;
            $countParams[] = $searchParam;
        }

        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($countParams);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return ['data' => $results, 'total' => $total];

    } catch (PDOException $e) {
        error_log("getAllProfessorsCombined: " . $e->getMessage());
        return ['data' => [], 'total' => 0];
    }
}

     ///////////////////////////////profile admin functions////////////////////////////////////

    public function getAdminProfile($userId){

        $stmt = $this->db->prepare("
            SELECT a.*, u.username, u.email 
            FROM admin a
            JOIN utilisateurs u ON a.user_id = u.user_id
            WHERE a.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword($userId, $newPasswordHash)
    {
        $stmt = $this->db->prepare("UPDATE utilisateurs SET password_hash = ? WHERE user_id = ?");
        return $stmt->execute([$newPasswordHash, $userId]);
    }



    /////////////////////////////// super admin functions////////////////////////////////////

    public function getAllAdmins()
    {
        $stmt = $this->db->prepare("
            SELECT
                u.user_id,
                u.username,
                u.email,
                u.role,
                a.nom,
                a.prenom,
                a.telephone,
                a.adresse,
                a.date_naissance,
                a.nationalite,
                a.CIN,
                a.fonction
            FROM utilisateurs u
            JOIN admin a ON a.user_id = u.user_id
            WHERE u.role IN ('admin', 'superadmin') AND u.actuel = 1
            ORDER BY a.nom, a.prenom
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

  
     public function addAdmin($username, $password, $email, $nom, $prenom, $telephone, $adresse, $date_naissance, $nationalite, $cin, $fonction)
{
    try {
        $this->db->beginTransaction();

        // Validate inputs
        if (empty($username) || empty($password) || empty($email) || empty($nom) || empty($prenom) || empty($date_naissance) || empty($cin)) {
            throw new Exception('Tous les champs obligatoires doivent être remplis');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format d\'email invalide');
        }

        // Validate CIN format (exemple : doit être alphanumérique et <= 20 caractères)
        if (!preg_match('/^[A-Za-z0-9]{1,20}$/', $cin)) {
            throw new Exception('Format de CIN invalide');
        }

        // Validate date format
        $date = DateTime::createFromFormat('Y-m-d', $date_naissance);
        if (!$date || $date->format('Y-m-d') !== $date_naissance) {
            throw new Exception('Format de date de naissance invalide (YYYY-MM-DD requis)');
        }

        // Validate email uniqueness
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Email déjà utilisé');
        }

        // Validate CIN uniqueness
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM admin WHERE CIN = :cin");
        $stmt->execute([':cin' => $cin]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('CIN déjà utilisé');
        }

        // Insert into utilisateurs
        $stmt = $this->db->prepare("
            INSERT INTO utilisateurs (username, password_hash, email, created_at, role, actuel)
            VALUES (:username, :password_hash, :email, NOW(), :role, 1)
        ");
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $params = [
            ':username' => $username,
            ':password_hash' => $password_hash,
            ':email' => $email,
            ':role' => $fonction === 'Super Administrateur' ? 'superadmin' : 'admin'
        ];
        $stmt->execute($params);
        $user_id = $this->db->lastInsertId();

        // Insert into admin
        $stmt = $this->db->prepare("
            INSERT INTO admin (user_id, nom, prenom, telephone, adresse, date_naissance, nationalite, CIN, fonction, created_at, updated_at)
            VALUES (:user_id, :nom, :prenom, :telephone, :adresse, :date_naissance, :nationalite, :cin, :fonction, NOW(), NOW())
        ");
        $params = [
            ':user_id' => $user_id,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':telephone' => $telephone ?: null,
            ':adresse' => $adresse ?: null,
            ':date_naissance' => $date_naissance,
            ':nationalite' => $nationalite ?: null,
            ':cin' => $cin,
            ':fonction' => $fonction ?: 'Administrateur'
        ];
        $stmt->execute($params);

        $this->db->commit();
        error_log('Administrateur ajouté avec succès, user_id: ' . $user_id);
        return ['status' => 'success', 'message' => 'Administrateur ajouté avec succès', 'user_id' => $user_id];
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log('Erreur lors de l\'ajout d\'un administrateur: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        return ['status' => 'error', 'message' => 'Échec de l\'ajout de l\'administrateur: ' . $e->getMessage()];
    }
}

 
    public function deleteAdmin($user_id, $motif)
{
    try {
        $this->db->beginTransaction();

        // 1. Désactiver dans la table utilisateurs
        $stmt1 = $this->db->prepare("
            UPDATE utilisateurs
            SET actuel = 0
            WHERE user_id = :user_id AND role IN ('admin', 'superadmin')
        ");
        $stmt1->execute([':user_id' => $user_id]);

        if ($stmt1->rowCount() === 0) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => 'Aucun administrateur trouvé avec cet ID ou ce rôle'];
        }

        // 2. Mettre à jour la table admin avec date_delete et motif
        $stmt2 = $this->db->prepare("
            UPDATE admin
            SET date_delete = CURRENT_DATE, motif = :motif
            WHERE user_id = :user_id
        ");
        $stmt2->execute([
            ':user_id' => $user_id,
            ':motif' => $motif
        ]);

        $this->db->commit();

        return ['status' => 'success', 'message' => 'Administrateur supprimé avec succès'];
    } catch (PDOException $e) {
        $this->db->rollBack();
        error_log('Erreur lors de la suppression d\'un administrateur: ' . $e->getMessage());
        return ['status' => 'error', 'message' => 'Échec de la suppression de l\'administrateur: ' . $e->getMessage()];
    }
}


   
    public function updateAdmin($user_id, $username, $password, $email, $nom, $prenom, $telephone, $adresse, $date_naissance, $nationalite, $cin, $fonction)
    {
        try {
            $this->db->beginTransaction();

            // Validate email and CIN uniqueness (exclude current user)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = :email AND user_id != :user_id");
            $stmt->execute([':email' => $email, ':user_id' => $user_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Email déjà utilisé');
            }
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM admin WHERE CIN = :cin AND user_id != :user_id");
            $stmt->execute([':cin' => $cin, ':user_id' => $user_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('CIN déjà utilisé');
            }

            // Update utilisateurs
            $query = "
                UPDATE utilisateurs
                SET username = :username, email = :email, last_login = NULL
                " . ($password ? ", password_hash = :password_hash" : "") . "
                WHERE user_id = :user_id AND role IN ('admin', 'superadmin')
            ";
            $params = [
                ':user_id' => $user_id,
                ':username' => $username,
                ':email' => $email
            ];
            if ($password) {
                $params[':password_hash'] = password_hash($password, PASSWORD_BCRYPT);
            }
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Aucun administrateur trouvé avec cet ID ou ce rôle');
            }

            // Update admin
            $stmt = $this->db->prepare("
                UPDATE admin
                SET nom = :nom,
                    prenom = :prenom,
                    telephone = :telephone,
                    adresse = :adresse,
                    date_naissance = :date_naissance,
                    nationalite = :nationalite,
                    CIN = :cin,
                    fonction = :fonction,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                ':user_id' => $user_id,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':telephone' => $telephone,
                ':adresse' => $adresse,
                ':date_naissance' => $date_naissance,
                ':nationalite' => $nationalite,
                ':cin' => $cin,
                ':fonction' => $fonction
            ]);

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Administrateur mis à jour avec succès'];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erreur lors de la mise à jour d\'un administrateur: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Échec de la mise à jour de l\'administrateur: ' . $e->getMessage()];
        }
    }
public function importStudentsFromCSV($csvFilePath)
    {
        $results = ['success_count' => 0, 'errors' => []];
        
        if (!file_exists($csvFilePath) || !is_readable($csvFilePath)) {
            return ['success_count' => 0, 'errors' => ['File not found or not readable']];
        }

        try {
            $file = fopen($csvFilePath, 'r');
            if ($file === false) {
                throw new Exception('Failed to open CSV file');
            }

            // Read header row
            $headers = fgetcsv($file);
            $expectedHeaders = [
                'username', 'password', 'email', 'cin', 'cne', 'nom', 'prenom',
                'date_naissance', 'nationalite', 'telephone', 'adresse', 'department_id',
                'field_id', 'cycle_id', 'group_id', 'annee_id', 'semestre_id', 'section_id'
            ];

            // Validate headers
            if (!$headers || array_intersect($expectedHeaders, $headers) !== $expectedHeaders) {
                fclose($file);
                return ['success_count' => 0, 'errors' => ['Invalid CSV headers. Expected: ' . implode(',', $expectedHeaders)]];
            }

            $rowNumber = 1;
            while (($data = fgetcsv($file)) !== false) {
                $rowNumber++;
                if (count($data) !== count($headers)) {
                    $results['errors'][] = "Row $rowNumber: Invalid number of columns";
                    continue;
                }

                // Map CSV data to student data array
                $studentData = array_combine($headers, $data);
                
                // Validate required fields
                $requiredFields = ['username', 'password', 'email', 'cin', 'cne', 'nom', 'prenom', 'date_naissance', 'department_id', 'field_id', 'cycle_id'];
                $missingFields = array_filter($requiredFields, fn($field) => empty(trim($studentData[$field])));
                
                if (!empty($missingFields)) {
                    $results['errors'][] = "Row $rowNumber: Missing required fields: " . implode(', ', $missingFields);
                    continue;
                }

                // Validate email format
                if (!filter_var($studentData['email'], FILTER_VALIDATE_EMAIL)) {
                    $results['errors'][] = "Row $rowNumber: Invalid email format";
                    continue;
                }

                // Validate date format
                $date = DateTime::createFromFormat('Y-m-d', $studentData['date_naissance']);
                if (!$date || $date->format('Y-m-d') !== $studentData['date_naissance']) {
                    $results['errors'][] = "Row $rowNumber: Invalid date format (YYYY-MM-DD required)";
                    continue;
                }

                try {
                    $result = $this->createStudent($studentData);
                    if ($result['success']) {
                        $results['success_count']++;
                    } else {
                        $results['errors'][] = "Row $rowNumber: Failed to create student - Unknown error";
                    }
                } catch (Exception $e) {
                    $results['errors'][] = "Row $rowNumber: " . $e->getMessage();
                }
            }

            fclose($file);
            return $results;

        } catch (Exception $e) {
            if (isset($file) && is_resource($file)) {
                fclose($file);
            }
            return ['success_count' => 0, 'errors' => ['Processing error: ' . $e->getMessage()]];
        }
    }

    /**
     * Import multiple professors from a CSV file
     * Expected CSV columns: username,password,email,role,cin,nom,prenom,telephone,department_id,field_id
     * @param string $csvFilePath Path to the CSV file
     * @return array Import results including success count and errors
     */
    public function importProfessorsFromCSV($csvFilePath)
    {
        $results = ['success_count' => 0, 'errors' => []];
        
        if (!file_exists($csvFilePath) || !is_readable($csvFilePath)) {
            return ['success_count' => 0, 'errors' => ['File not found or not readable']];
        }

        try {
            $file = fopen($csvFilePath, 'r');
            if ($file === false) {
                throw new Exception('Failed to open CSV file');
            }

            // Read header row
            $headers = fgetcsv($file);
            $expectedHeaders = [
                'username', 'password', 'email', 'role', 'cin',
                'nom', 'prenom', 'telephone', 'department_id', 'field_id'
            ];

            // Validate headers
            if (!$headers || array_intersect($expectedHeaders, $headers) !== $expectedHeaders) {
                fclose($file);
                return ['success_count' => 0, 'errors' => ['Invalid CSV headers. Expected: ' . implode(',', $expectedHeaders)]];
            }

            $rowNumber = 1;
            while (($data = fgetcsv($file)) !== false) {
                $rowNumber++;
                if (count($data) !== count($headers)) {
                    $results['errors'][] = "Row $rowNumber: Invalid number of columns";
                    continue;
                }

                // Map CSV data to user and professor data arrays
                $rowData = array_combine($headers, $data);
                
                $userData = [
                    'username' => $rowData['username'],
                    'password' => $rowData['password'],
                    'email' => $rowData['email'],
                    'role' => $rowData['role']
                ];

                $profData = [
                    'cin' => $rowData['cin'],
                    'nom' => $rowData['nom'],
                    'prenom' => $rowData['prenom'],
                    'telephone' => $rowData['telephone'],
                    'department_id' => $rowData['department_id'],
                    'field_id' => $rowData['field_id'] ?? null
                ];

                // Validate required fields
                $requiredUserFields = ['username', 'password', 'email', 'role'];
                $requiredProfFields = ['cin', 'nom', 'prenom', 'department_id'];
                
                $missingUserFields = array_filter($requiredUserFields, fn($field) => empty(trim($userData[$field])));
                $missingProfFields = array_filter($requiredProfFields, fn($field) => empty(trim($profData[$field])));
                
                if (!empty($missingUserFields) || !empty($missingProfFields)) {
                    $missingFields = array_merge($missingUserFields, $missingProfFields);
                    $results['errors'][] = "Row $rowNumber: Missing required fields: " . implode(', ', $missingFields);
                    continue;
                }

                // Validate email format
                if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                    $results['errors'][] = "Row $rowNumber: Invalid email format";
                    continue;
                }

                // Validate role
                if (!in_array($userData['role'], ['professor', 'chef_dep', 'chef_fill'])) {
                    $results['errors'][] = "Row $rowNumber: Invalid role. Must be professor, chef_dep, or chef_fill";
                    continue;
                }

                try {
                    $result = $this->addProfessor($userData, $profData);
                    if ($result['status'] === 201) {
                        $results['success_count']++;
                    } else {
                        $results['errors'][] = "Row $rowNumber: " . $result['data']['message'];
                    }
                } catch (Exception $e) {
                    $results['errors'][] = "Row $rowNumber: " . $e->getMessage();
                }
            }

            fclose($file);
            return $results;

        } catch (Exception $e) {
            if (isset($file) && is_resource($file)) {
                fclose($file);
            }
            return ['success_count' => 0, 'errors' => ['Processing error: ' . $e->getMessage()]];
        }
    }
}
