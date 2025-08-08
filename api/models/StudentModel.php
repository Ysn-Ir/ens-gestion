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
    public function getStudentByUserId($userId) {
    $sql = "SELECT e.*, u.username, u.email
            FROM etudiants e
            JOIN utilisateurs u ON e.user_id = u.user_id
            WHERE e.user_id = :userId";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([':userId' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

}
