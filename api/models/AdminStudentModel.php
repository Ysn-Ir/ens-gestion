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