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

    public function getSemestresByEtape($etapeId) {
        $stmt = $this->db->prepare("
            SELECT semestre_id, nom
            FROM semestres
            WHERE etape_id = ?
            ORDER BY nom
        ");
        $stmt->execute([$etapeId]);
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

    
}