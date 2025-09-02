<?php
require_once __DIR__ . '/../utils/Database.php';

class AdminStudentModel
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
}
public function getStudentDetail($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    et.user_id,
                    et.cne,
                    et.nom,
                    et.prenom,
                    et.telephone,
                    et.department_id,
                    et.field_id,
                    se.annee_id,
                    et.cycle_id,
                    e.etape_id,
                    se.semestre_id,
                    se.section_id,
                    et.group_id,
                    et.cin,
                    et.date_naissance,
                    et.nationalite,
                    et.adresse,
                    u.email,
                    u.username,
                    d.nom AS departement_nom,
                    f.nom AS filiere_nom,
                    a.annee_id AS annee_nom,
                    c.nom AS cycle_nom,
                    e.nom_etape AS etape_nom,
                    s.nom AS semestre_nom,
                    sec.nom AS section_nom,
                    g.nom AS groupe_nom
                FROM etudiants et
                JOIN utilisateurs u ON u.user_id = et.user_id
                LEFT JOIN departements d ON d.department_id = et.department_id
                LEFT JOIN student_enrollments se ON se.student_id = et.user_id
                LEFT JOIN filieres f ON f.field_id = et.field_id
                LEFT JOIN annees_academiques a ON a.annee_id = se.annee_id
                LEFT JOIN cycles c ON c.cycle_id = et.cycle_id
                LEFT JOIN etapes e ON e.etape_id = se.etape_id
                LEFT JOIN semestres s ON s.semestre_id = se.semestre_id
                LEFT JOIN sections sec ON sec.section_id = se.section_id
                LEFT JOIN groupes g ON g.group_id = et.group_id
                WHERE et.user_id = ?
                Limit 1
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return ['status' => true, 'data' => ['students' => $result]];
            } else {
                return ['status' => false, 'message' => 'Étudiant introuvable'];
            }
        } catch (PDOException $e) {
            // Log the error for debugging (in a production environment, use a proper logging system)
            error_log('Database error in getStudentDetail: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()];
        }
    }
public function createStudent(array $data) {
    $db = $this->db;
    $db->beginTransaction();

    try {
        // 1. Validate required fields
        $required = ['username', 'password', 'email', 'cin', 'cne', 'nom', 'prenom', 
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

        // 4. Validate department, field, and cycle
        $stmt = $db->prepare("SELECT 1 FROM departements WHERE department_id = ?");
        $stmt->execute([(int)$data['department_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid department specified");
        }

        $stmt = $db->prepare("SELECT 1 FROM filieres WHERE field_id = ?");
        $stmt->execute([(int)$data['field_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid field specified");
        }

        $stmt = $db->prepare("SELECT 1 FROM cycles WHERE cycle_id = ?");
        $stmt->execute([(int)$data['cycle_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid cycle specified");
        }

        // 5. Check for existing user
        $stmt = $db->prepare("SELECT user_id FROM utilisateurs WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$data['username'], $data['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Username or email already exists");
        }

        // 6. Create user account
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO utilisateurs (username, password_hash, email, role, actuel)
            VALUES (?, ?, ?, 'student', 1)
        ");
        if (!$stmt->execute([$data['username'], $passwordHash, $data['email']])) {
            throw new Exception("Failed to create user account");
        }
        
        $userId = $db->lastInsertId();
        if ($userId <= 0) {
            throw new Exception("Failed to generate valid user ID");
        }

        // 7. Create student record
        $stmt = $db->prepare("
            INSERT INTO etudiants (
                user_id, cin, cne, nom, prenom, date_naissance,
                nationalite, telephone, adresse, department_id,
                field_id, cycle_id, group_id, actuel
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
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
            (int)$data['group_id'] 
        ];
        
        if (!$stmt->execute($studentData)) {
            throw new Exception("Failed to create student record");
        }

        // 8. Create enrollment record if provided
        if (!empty($data['annee_id']) && !empty($data['semestre_id'])) {
            $stmt = $db->prepare("
                INSERT INTO student_enrollments 
                (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, group_id, section_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $enrollmentData = [
                $userId,
                $data['annee_id'],
                (int)$data['semestre_id'],
                (int)$data['cycle_id'],
                (int)$data['field_id'],
                (int)$data['etape_id'] ,
                (int)$data['group_id'] ,
                (int)$data['section_id'] 
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
  public function updateStudent($id, array $data) {
    $db = $this->db;
    $db->beginTransaction();

    try {
        // 1. Validate required fields
        $required = ['username', 'email', 'password','cin', 'cne', 'nom', 'prenom', 
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

        // 4. Validate department, field, and cycle
        $stmt = $db->prepare("SELECT 1 FROM departements WHERE department_id = ?");
        $stmt->execute([(int)$data['department_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid department specified");
        }

        $stmt = $db->prepare("SELECT 1 FROM filieres WHERE field_id = ?");
        $stmt->execute([(int)$data['field_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid field specified");
        }

        $stmt = $db->prepare("SELECT 1 FROM cycles WHERE cycle_id = ?");
        $stmt->execute([(int)$data['cycle_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid cycle specified");
        }

        // 5. Check if user exists
        $stmt = $db->prepare("SELECT 1 FROM utilisateurs WHERE user_id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception("User does not exist");
        }

        // 6. Update utilisateurs
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            UPDATE utilisateurs 
            SET username = ?, email = ?, password_hash =?,actuel = ?
            WHERE user_id = ?
        ");
        if (!$stmt->execute([$data['username'], $data['email'],$passwordHash, $data['actuel'] ?? 1, $id])) {
            throw new Exception("Failed to update user account");
        }

        // 7. Update etudiants           $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            UPDATE etudiants SET
                cin = ?, cne = ?, nom = ?, prenom = ?, date_naissance = ?,
                nationalite = ?, telephone = ?, adresse = ?, department_id = ?,
                field_id = ?, cycle_id = ?, group_id = ?, actuel = ?
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
            (int)$data['group_id'],
            $data['actuel'] ?? 1,
            $id
        ])) {
            throw new Exception("Failed to update student record");
        }

        // 8. Update or Insert enrollment if data is available
        if (!empty($data['annee_id']) && !empty($data['semestre_id'])) {
            // Check if enrollment exists
            $stmt = $db->prepare("SELECT 1 FROM student_enrollments WHERE student_id = ? AND annee_id = ? AND semestre_id = ?");
            $stmt->execute([$id, $data['annee_id'], $data['semestre_id']]);
            $enrollmentExists = $stmt->fetch();

            if ($enrollmentExists) {
                // Update existing enrollment
                $stmt = $db->prepare("
                    UPDATE student_enrollments
                    SET annee_id = ?, semestre_id = ?, cycle_id = ?, field_id = ?, etape_id = ?, 
                        group_id = ?, section_id = ?, status = ?
                    WHERE student_id = ? AND annee_id = ? AND semestre_id = ?
                ");
                if (!$stmt->execute([
                    $data['annee_id'],
                    (int)$data['semestre_id'],
                    (int)$data['cycle_id'],
                    (int)$data['field_id'],
                    (int)$data['etape_id'] ,
                    (int)$data['group_id'],
                    (int)$data['section_id'] ,
                    $data['status'] ?? 'active',
                    (int)$id,
                    (int)$data['annee_id'],
                    (int)$data['semestre_id']
                ])) {
                    throw new Exception("Failed to update enrollment record");
                }
            } else {
                // Insert new enrollment
                $stmt = $db->prepare("
                    INSERT INTO student_enrollments 
                    (student_id, annee_id, semestre_id, cycle_id, field_id, etape_id, group_id, section_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$stmt->execute([
                    $id,
                    $data['annee_id'],
                    (int)$data['semestre_id'],
                    (int)$data['cycle_id'],
                    (int)$data['field_id'],
                    (int)$data['etape_id'] ,
                    (int)$data['group_id'] ,
                    (int)$data['section_id'] ,
                    (int)$data['status'] ?? 'active'
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
public function getFilteredStudents($annee_id = null, $field_id = null, $semestre_id = null,$etape_id=null, $section_id = null, $group_id = null, $cycle_id = null, $department_id = null, $includeEnrollmentInfo = false, $search = null, $page = 1, $limit = 10) {
    try {
        $sql = "
            SELECT 
                et.user_id,
                et.cne,
                et.cin,
                et.nom,
                et.prenom,
                u.email,
                et.telephone AS telephone,
                d.nom AS departement_nom,
                f.nom AS filiere_nom,
                aa.annee_id AS date";

        if ($includeEnrollmentInfo) {
            $sql .= ",
                s.nom AS semestre_nom,
                sec.nom AS section_nom,
                g.nom AS groupe_nom,
                c.nom AS cycle_nom";
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
         if ($etape_id !== null) {
            $sql .= " AND se.etape_id = :etape_id";
            $params[':etape_id'] = $etape_id;
        }
        if ($field_id !== null) {
            $sql .= " AND et.field_id = :field_id";
            $params[':field_id'] = $field_id;
        }

        if ($cycle_id !== null) {
            $sql .= " AND se.cycle_id = :cycle_id";
            $params[':cycle_id'] = $cycle_id;
        }

        if ($semestre_id !== null) {
            $sql .= " AND se.semestre_id = :semestre_id";
            $params[':semestre_id'] = $semestre_id;
        }

        if ($section_id !== null) {
            $sql .= " AND se.section_id = :section_id";
            $params[':section_id'] = $section_id;
        }

        if ($group_id !== null) {
            $sql .= " AND se.group_id = :group_id";
            $params[':group_id'] = $group_id;
        }

        if ($search !== null && $search !== '') {
            $sql .= " AND (
                et.nom LIKE :search OR
                et.prenom LIKE :search OR
                et.cne LIKE :search OR
                et.cin LIKE :search OR
                u.email LIKE :search
            )";
            $params[':search'] = '%' . $search . '%';
        }

        // Count query
        $countSql = "SELECT COUNT(DISTINCT et.user_id) as total
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

        if (!empty($params)) {
            $countSql .= " AND " . implode(" AND ", array_map(function ($key) {
                $column = str_replace(":", "", $key);
                // Map parameter names to fully qualified column names
                $columnMap = [
                    'annee_id' => 'aa.annee_id',
                    'department_id' => 'et.department_id',
                    'field_id' => 'et.field_id',
                    'cycle_id' => 'se.cycle_id',
                    'etape_id'=>'se.etape_id',
                    'semestre_id' => 'se.semestre_id',
                    'section_id' => 'se.section_id',
                    'group_id' => 'se.group_id',
                    'search' => 'search' // Handled separately in main query
                ];
                return in_array($column, ['search']) ? '' : "{$columnMap[$column]} = :{$column}";
            }, array_keys($params)));
        }

        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $countStmt->bindValue($key, $value, $type);
        }
        $countStmt->execute();
        $totalStudents = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Pagination
        $offset = ($page - 1) * $limit;
        $sql .= " ORDER BY et.nom, et.prenom LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'students' => $students,
            'count' => (int)$totalStudents
        ];
    } catch (Exception $e) {
        error_log("Error in getFilteredStudents: " . $e->getMessage());
        throw $e;
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
   
    public function getFilierByDepartement($depId){
        if(isset($depId)){
            $stmt=$this->db->prepare( " SELECT field_id , nom FROM filieres WHERE department_id= ? ");
        }
        else {
            $stmt=$this->db->prepare( " SELECT field_id , nom FROM filieres "); 
        }
        $stmt->execute([$depId]);
        return $stmt->fetchAll();
    }
  public function getSectionsByFiliere($fieldId, $etape_id)
{
    $stmt = $this->db->prepare("
        SELECT section_id, nom
        FROM sections
        WHERE (field_id = ? OR ? IS NULL)
          AND (etape = ? OR ? IS NULL)
        ORDER BY nom
    ");
    
    // Pass each parameter twice, as explained earlier
    $stmt->execute([$fieldId, $fieldId, $etape_id, $etape_id]);
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



// 🔎 Afficher tous les détails d'un professeur

// In AdminStudentModel.php
public function getStudentInfo($user_id) {
    $stmt = $this->db->prepare("
      SELECT 
    et.*,
    u.email,
    d.nom AS departement_nom,
    f.nom AS filiere_nom,
    c.nom AS cycle_nom,
    aa.annee_id,
    s.nom AS semestre_nom,
    sec.nom AS section_nom,
    g.nom AS groupe_nom,
    se.status AS statut
FROM etudiants et
JOIN utilisateurs u ON u.user_id = et.user_id
LEFT JOIN departements d ON d.department_id = et.department_id
LEFT JOIN filieres f ON f.field_id = et.field_id
LEFT JOIN cycles c ON c.cycle_id = et.cycle_id
LEFT JOIN student_enrollments se ON se.student_id = et.user_id
LEFT JOIN annees_academiques aa ON aa.annee_id = se.annee_id
LEFT JOIN semestres s ON s.semestre_id = se.semestre_id
LEFT JOIN sections sec ON sec.section_id = se.section_id
LEFT JOIN groupes g ON g.group_id = se.group_id
WHERE et.user_id = ?
LIMIT 1
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

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

  

    public function getAllElements()
    {
        $stmt = $this->db->query("SELECT element_id, nom FROM elements");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
}