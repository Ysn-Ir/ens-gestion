<?php
require_once __DIR__ . '/../utils/Database.php';

class settingModel
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

    public function addSetting($name, $value){
        $stmt = $this->db->prepare("INSERT INTO system_settings (name, value) VALUES (:name, :value)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':value', $value);
        return $stmt->execute();
    }

    public function updateSetting($name, $value){
        $stmt = $this->db->prepare("UPDATE system_settings SET value = :value WHERE name = :name");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':value', $value);
        return $stmt->execute();
    }

    public function deleteSetting($name){
        $stmt = $this->db->prepare("DELETE FROM system_settings WHERE name = :name");
        $stmt->bindParam(':name', $name);
        return $stmt->execute();
    }

    public function getAllConstraints(){
        $stmt = $this->db->prepare("SELECT * FROM grading_rules");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addConstraint($data){
        $stmt = $this->db->prepare("INSERT INTO grading_rules (scope, rule_name, rule_value, semestre_id, annee_id, field_id) VALUES (:scope, :rule_name, :rule_value, :semestre_id, :annee_id, :field_id)");
        $stmt->bindParam(':scope', $data['scope']);
        $stmt->bindParam(':rule_name', $data['rule_name']);
        $stmt->bindParam(':rule_value', $data['rule_value']);
        $stmt->bindParam(':semestre_id', $data['semestre_id'], PDO::PARAM_INT);
        $stmt->bindParam(':annee_id', $data['annee_id'], PDO::PARAM_INT);
        $stmt->bindParam(':field_id', $data['field_id'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateConstraint($rule_id, $data){
        $stmt = $this->db->prepare("UPDATE grading_rules SET scope = :scope, rule_name = :rule_name, rule_value = :rule_value, semestre_id = :semestre_id, annee_id = :annee_id, field_id = :field_id WHERE rule_id = :rule_id");
        $stmt->bindParam(':rule_id', $rule_id, PDO::PARAM_INT);
        $stmt->bindParam(':scope', $data['scope']);
        $stmt->bindParam(':rule_name', $data['rule_name']);
        $stmt->bindParam(':rule_value', $data['rule_value']);
        $stmt->bindParam(':semestre_id', $data['semestre_id'], PDO::PARAM_INT);
        $stmt->bindParam(':annee_id', $data['annee_id'], PDO::PARAM_INT);
        $stmt->bindParam(':field_id', $data['field_id'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deleteConstraint($rule_id){
        $stmt = $this->db->prepare("DELETE FROM grading_rules WHERE rule_id = :rule_id");
        $stmt->bindParam(':rule_id', $rule_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}