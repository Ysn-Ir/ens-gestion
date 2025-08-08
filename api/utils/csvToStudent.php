<?php

class StudentImporter {
    private $db;

    // Assume this class has the same db connection as createStudent
    public function __construct($db) {
        $this->db = $db;
    }

    public function createStudent(array $data) {
        // The original createStudent function as provided
        $db = $this->db;
        $db->beginTransaction();

        try {
            $required = ['username', 'password', 'email', 'cin', 'cne', 'nom', 'prenom', 
                        'date_naissance', 'department_id', 'field_id', 'cycle_id'];

            if (isset($data['annee_id'])) {
                $stmt = $db->prepare("SELECT 1 FROM annees_academiques WHERE annee_id = ?");
                $stmt->execute([$data['annee_id']]);
                if (!$stmt->fetch()) {
                    throw new Exception("Invalid academic year specified");
                }
            }

            if (isset($data['semestre_id'])) {
                $stmt = $db->prepare("SELECT 1 FROM semestres WHERE semestre_id = ?");
                $stmt->execute([$data['semestre_id']]);
                if (!$stmt->fetch()) {
                    throw new Exception("Invalid semester specified");
                }
            }

            $stmt = $db->prepare("SELECT user_id FROM utilisateurs WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$data['username'], $data['email']]);
            if ($stmt->fetch()) {
                throw new Exception("Username or email already exists");
            }

            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO utilisateurs (username, password_hash, email, role) VALUES (?, ?, ?, 'student')");
            if (!$stmt->execute([$data['username'], $passwordHash, $data['email']])) {
                throw new Exception("Failed to create user account");
            }
            
            $userId = $db->lastInsertId();
            if ($userId <= 0) {
                throw new Exception("Failed to generate valid user ID");
            }

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

    /**
     * Imports student data from a CSV file and inserts it into the database.
     * @param string $filePath Path to the CSV file
     * @return array Summary of the import process including success count and errors
     * @throws Exception If the file cannot be opened or headers cannot be read
     */
    public function importStudentsFromCSV($filePath) {
        if (($handle = fopen($filePath, "r")) === FALSE) {
            throw new Exception("Failed to open CSV file");
        }

        // Read headers from the first row
        $headers = fgetcsv($handle, 1000, ",");
        if ($headers === FALSE) {
            fclose($handle);
            throw new Exception("Failed to read headers from CSV file");
        }
        $headers = array_map('trim', $headers);

        $rowNumber = 1;
        $successCount = 0;
        $errors = [];

        // Process each data row
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rowNumber++;
            $data = array_map('trim', $data);

            // Check for column count mismatch
            if (count($data) !== count($headers)) {
                $errors[] = "Row $rowNumber: Invalid number of columns";
                continue;
            }

            // Map CSV row to associative array
            $rowData = array_combine($headers, $data);

            try {
                $result = $this->createStudent($rowData);
                if ($result['success']) {
                    $successCount++;
                }
            } catch (Exception $e) {
                $errors[] = "Row $rowNumber: " . $e->getMessage();
            }
        }

        fclose($handle);

        return [
            'success_count' => $successCount,
            'errors' => $errors
        ];
    }
}

?>