<?php
require_once __DIR__ . '/../utils/Database.php';

class DashboardModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAllSettings(){
        $stmt = $this->db->prepare("SELECT * FROM system_settings");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate the total number of active students.
     *
     * @return int The number of active students.
     */
    public function getStudentCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM etudiants WHERE actuel = 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    /**
     * Calculate the total number of active professors.
     *
     * @return int The number of active professors.
     */
    public function getProfessorCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM professeurs WHERE actuel = 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    /**
     * Calculate the total number of modules.
     *
     * @return int The number of modules.
     */
    public function getModuleCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM modules");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    /**
     * Calculate the total number of notes.
     *
     * @return int The number of notes.
     */
    public function getNoteCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM notes");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    /**
     * Calculate the number of students per filière (field).
     *
     * @return array An associative array with filière names as keys and student counts as values.
     */
    public function getStudentsPerFiliere()
    {
        $stmt = $this->db->prepare("
            SELECT f.nom AS filiere_nom, COUNT(e.user_id) AS student_count
            FROM etudiants e
            JOIN filieres f ON e.field_id = f.field_id
            WHERE e.actuel = 1
            GROUP BY e.field_id
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $studentsPerFiliere = [];
        foreach ($results as $row) {
            $studentsPerFiliere[$row['filiere_nom']] = (int) $row['student_count'];
        }
        
        return $studentsPerFiliere;
    }
}