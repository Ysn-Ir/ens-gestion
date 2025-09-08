<?php
require_once __DIR__ . '/../utils/Database.php';

class StudentModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // 1. Get basic student info with email
    public function getStudent($id)
    {
        $stmt = $this->db->prepare("
            SELECT e.*
            FROM etudiants e
            WHERE e.user_id = ?;
        ");
        $stmt->execute([$id]); // ✅ passing parameter
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. Get detailed info by etape
    public function getStudentInfo($id, $etape_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                e.*,
                ue.nom_etape,
                se.annee_id,
                se.semestre_id
            FROM etudiants e
            JOIN student_enrollments se ON e.user_id = se.student_id
            JOIN semestres s ON se.semestre_id = s.semestre_id
            JOIN etapes ue ON s.etape_id = ue.etape_id
            WHERE e.user_id = ? AND ue.etape_id = ?;
        ");
        $stmt->execute([$id, $etape_id]); // ✅ passing both params
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Get student's diplomas
    public function getDiplomes($id)
    {
        $stmt = $this->db->prepare("
            SELECT d.*
            FROM student_diplomas sd
            JOIN diplomes d ON sd.diploma_id = d.diploma_id
            WHERE sd.student_id = ?;
        ");
        $stmt->execute([$id]); // ✅ passing parameter
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Get module notes
    public function getStudentNoteModule($id)
    {
        $stmt = $this->db->prepare("
            SELECT m.nom AS module_name, nm.note_module
            FROM note_modules nm
            JOIN modules m ON nm.module_id = m.module_id
            WHERE nm.student_id = ?;
        ");
        $stmt->execute([$id]); // ✅ passing parameter
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Get all notes grouped by etape and semester
    public function getAllNotesByEtape($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                et.nom_etape,
                s.nom AS semestre_name,
                m.nom AS module_name,
                nm.note
            FROM note_modules nm
            JOIN modules m ON nm.module_id = m.module_id
            JOIN semestres s ON m.semestre_id = s.semestre_id
            JOIN etapes et ON s.etape_id = et.etape_id
            WHERE nm.student_id = ?
            ORDER BY et.etape_id, s.semestre_id, m.module_id;
        ");
        $stmt->execute([$id]); // ✅ passing parameter
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getStudentByUserId($userId) 
    {
        $sql = "SELECT e.*, u.username, u.email
                FROM etudiants e
                JOIN utilisateurs u ON e.user_id = u.user_id
                WHERE e.user_id = :userId";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllNotesByEtapeAndSemester($id, $etape_id, $semester_id)
{
    $stmt = $this->db->prepare("
        SELECT 
            et.nom_etape,
            s.nom AS semestre_name,
            m.nom AS module_name,
            nm.note_module
        FROM note_modules nm
        JOIN modules m ON nm.module_id = m.module_id
        JOIN semestres s ON m.semestre_id = s.semestre_id
        JOIN etapes et ON s.etape_id = et.etape_id
        WHERE nm.student_id = ? AND et.etape_id = ? AND s.semestre_id = ?
        ORDER BY et.etape_id, s.semestre_id, m.module_id;
    ");
    $stmt->execute([$id, $etape_id, $semester_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function getAnnualNoteAndRanking($student_id, $annee_id)
{
    // Step 1: Check if all module notes for the student in the given year are entered
    $stmt = $this->db->prepare("
        SELECT COUNT(*) as total_modules, 
               SUM(CASE WHEN nm.note_module IS NULL THEN 1 ELSE 0 END) as missing_notes
        FROM note_modules nm
        JOIN modules m ON nm.module_id = m.module_id
        JOIN semestres s ON nm.semestre_id = s.semestre_id
        WHERE nm.student_id = ? AND nm.annee_id = ? AND s.annee_id = ?
    ");
    $stmt->execute([$student_id, $annee_id, $annee_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['missing_notes'] > 0) {
        return [
            'status' => 'incomplete',
            'message' => 'Not all module notes for the year have been entered.'
        ];
    }

    // Step 2: Retrieve annual note and decision from note_annees
    $stmt = $this->db->prepare("
        SELECT na.note_annee, na.decision_annee
        FROM note_annees na
        WHERE na.student_id = ? AND na.annee_id = ?
    ");
    $stmt->execute([$student_id, $annee_id]);
    $annual_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$annual_data) {
        return [
            'status' => 'error',
            'message' => 'No annual note found for the student in this academic year.'
        ];
    }

    // Step 3: Get the student's field_id and cycle_id
    $stmt = $this->db->prepare("
        SELECT e.field_id, e.cycle_id
        FROM etudiants e
        WHERE e.user_id = ?
    ");
    $stmt->execute([$student_id]);
    $student_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student_info) {
        return [
            'status' => 'error',
            'message' => 'Student information not found.'
        ];
    }

    $field_id = $student_info['field_id'];
    $cycle_id = $student_info['cycle_id'];

    // Step 4: Calculate ranking among students in the same field, cycle, and year
    $stmt = $this->db->prepare("
        SELECT na.student_id, na.note_annee,
               RANK() OVER (ORDER BY na.note_annee DESC) as classement
        FROM note_annees na
        JOIN etudiants e ON na.student_id = e.user_id
        WHERE na.annee_id = ? AND e.field_id = ? AND e.cycle_id = ?
    ");
    $stmt->execute([$annee_id, $field_id, $cycle_id]);
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Find the student's ranking
    $classement = null;
    foreach ($rankings as $rank) {
        if ($rank['student_id'] == $student_id) {
            $classement = $rank['classement'];
            break;
        }
    }

    // Step 5: Return the result
    return [
        'status' => 'success',
        'student_id' => $student_id,
        'annee_id' => $annee_id,
        'note_annee' => $annual_data['note_annee'],
        'decision_annee' => $annual_data['decision_annee'],
        'classement' => $classement ?? 'N/A',
        'total_students' => count($rankings)
    ];
}

public function getAllEtapes() {

        $stmt = $this->db->prepare("
            SELECT etape_id, nom_etape
            FROM etapes
            ORDER BY nom_etape
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSemestresByEtapeCycleFiliere($etapeId, $cycleId, $fieldId) {
    // Prepare the SQL query to fetch semesters based on etape_id, cycle_id, field_id, and current academic year
    $stmt = $this->db->prepare("
        SELECT s.semestre_id, s.nom
        FROM semestres s
        JOIN annees_academiques aa ON s.annee_id = aa.annee_id
        WHERE s.etape_id = ?
          AND s.cycle_id = ?
          AND s.field_id = ?
          AND aa.current_flag = 1
        ORDER BY s.nom
    ");
    
    // Execute the query with the provided parameters
    $stmt->execute([$etapeId, $cycleId, $fieldId]);
    
    // Fetch and return the results as an associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    public function getAllAnnees() {
        $stmt = $this->db->prepare("
            SELECT annee_id
            FROM annees_academiques
            ORDER BY annee_id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getYearStudied($student_id) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT se.annee_id
            FROM student_enrollments se
            WHERE se.student_id = ?
            ORDER BY se.annee_id DESC
        ");
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function changePassword($userId, $newPassword) {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update the password in the database
        $stmt = $this->db->prepare("
            UPDATE utilisateurs
            SET password_hash = ?
            WHERE user_id = ?
        ");
        return $stmt->execute([$hashedPassword, $userId]);
    }

    public function getCycleOfStudent($student_id) {
        $stmt = $this->db->prepare("
            select c.cycle_id, c.nom AS cycle_name
            from cycles c
            join etudiants e ON c.cycle_id = e.cycle_id
            where e.user_id = ?
        ");
        $stmt->execute([$student_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSemestresOfStudentByCycle($student_id, $cycle_id) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT s.semestre_id, s.nom AS semestre_name
            FROM semestres s
            JOIN student_enrollments se ON s.semestre_id = se.semestre_id
            WHERE se.student_id = ? AND s.cycle_id = ?
            ORDER BY s.nom
        ");
        $stmt->execute([$student_id, $cycle_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNoteOfStudentBySemestre($student_id, $semestre_id) {
    if (!is_numeric($student_id) || !is_numeric($semestre_id)) {
        return [];
    }

    try {
        $stmt = $this->db->prepare("
            SELECT 
                m.module_id,
                m.code AS module_code,
                m.nom AS module_name,
                e.element_id,
                e.nom AS element_name,
                n.note_tp,
                n.note_td,
                n.note_cc,
                n.note_exam,
                n.note_rattrapage,
                n.note_finale AS element_finale,
                n.decision AS element_decision,
                n.decision_ratt AS element_decision_ratt,
                nm.note_module AS module_finale,
                nm.decision AS module_decision,
                nm.decision_ratt AS module_decision_ratt
            FROM notes n
            JOIN elements e ON n.element_id = e.element_id
            JOIN modules m ON e.module_id = m.module_id
            LEFT JOIN note_modules nm 
                ON nm.student_id = n.student_id
                AND nm.module_id = m.module_id
                AND nm.semestre_id = n.semestre_id
                AND nm.annee_id = n.annee_id
            JOIN annees_academiques aa ON n.annee_id = aa.annee_id
            WHERE n.student_id = ? 
              AND n.semestre_id = ? 
              AND aa.current_flag = 1
            ORDER BY m.code, e.nom
        ");
        $stmt->execute([$student_id, $semestre_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $modules = [];
        foreach ($rows as $row) {
            $moduleKey = $row['module_id'];
            if (!isset($modules[$moduleKey])) {
                $modules[$moduleKey] = [
                    'module_code' => $row['module_code'],
                    'module_name' => $row['module_name'],
                    'module_finale' => $row['module_finale'] !== null ? round($row['module_finale'], 2) : null,
                    'module_decision' => $row['module_decision'],
                    'module_decision_ratt' => $row['module_decision_ratt'],
                    'elements' => []
                ];
            }
            $modules[$moduleKey]['elements'][] = [
                'element_id' => $row['element_id'],
                'element_name' => $row['element_name'],
                'note_tp' => $row['note_tp'],
                'note_td' => $row['note_td'],
                'note_cc' => $row['note_cc'],
                'note_exam' => $row['note_exam'],
                'note_rattrapage' => $row['note_rattrapage'],
                'element_finale' => $row['element_finale'],
                'element_decision' => $row['element_decision'],
                'element_decision_ratt' => $row['element_decision_ratt']
            ];
        }

        return array_values($modules);
    } catch (PDOException $e) {
        return [];
    }
}





}