
<?php
require_once __DIR__ . '/../utils/Database.php';

class settingModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAllSettings()
    {
        $stmt = $this->db->prepare("SELECT * FROM system_settings");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addSetting($name, $value)
    {
        $stmt = $this->db->prepare("INSERT INTO system_settings (name, value) VALUES (:name, :value)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':value', $value);
        return $stmt->execute();
    }

    public function updateSetting($name, $value)
    {
        $stmt = $this->db->prepare("UPDATE system_settings SET value = :value WHERE name = :name");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':value', $value);
        return $stmt->execute();
    }

    public function deleteSetting($name)
    {
        $stmt = $this->db->prepare("DELETE FROM system_settings WHERE name = :name");
        $stmt->bindParam(':name', $name);
        return $stmt->execute();
    }

    public function getAllConstraints($scope = null)
    {
        $query = "SELECT * FROM grading_rules";
        if ($scope) {
            $query .= " WHERE scope = :scope";
        }
        $stmt = $this->db->prepare($query);
        if ($scope) {
            $stmt->bindParam(':scope', $scope);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addConstraint($data)
    {
        // Verify table schema
        $columns = $this->db->query("SHOW COLUMNS FROM grading_rules")->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['scope', 'rule_name', 'rule_value'];
        foreach ($required_columns as $col) {
            if (!in_array($col, $columns)) {
                throw new Exception("Column '$col' not found in grading_rules table", 500);
            }
        }

        $stmt = $this->db->prepare("INSERT INTO grading_rules (scope, rule_name, rule_value) VALUES (:scope, :rule_name, :rule_value)");
        $stmt->bindParam(':scope', $data['scope']);
        $stmt->bindParam(':rule_name', $data['rule_name']);
        $stmt->bindParam(':rule_value', $data['rule_value']);
        return $stmt->execute();
    }

    public function updateConstraint($rule_id, $data)
    {
        // Verify table schema
        $columns = $this->db->query("SHOW COLUMNS FROM grading_rules")->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['rule_id', 'scope', 'rule_name', 'rule_value'];
        foreach ($required_columns as $col) {
            if (!in_array($col, $columns)) {
                throw new Exception("Column '$col' not found in grading_rules table", 500);
            }
        }

        $stmt = $this->db->prepare("UPDATE grading_rules SET scope = :scope, rule_name = :rule_name, rule_value = :rule_value WHERE rule_id = :rule_id");
        $stmt->bindParam(':rule_id', $rule_id, PDO::PARAM_INT);
        $stmt->bindParam(':scope', $data['scope']);
        $stmt->bindParam(':rule_name', $data['rule_name']);
        $stmt->bindParam(':rule_value', $data['rule_value']);
        return $stmt->execute();
    }

    public function deleteConstraint($rule_id)
    {
        $stmt = $this->db->prepare("DELETE FROM grading_rules WHERE rule_id = :rule_id");
        $stmt->bindParam(':rule_id', $rule_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getAllAcademicYears()
    {
        $stmt = $this->db->prepare("SELECT * FROM annees_academiques");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAcademicYear($annee_id, $current_flag)
    {
        $this->db->beginTransaction();
        try {
            if ($current_flag == 1) {
                $stmt = $this->db->prepare("UPDATE annees_academiques SET current_flag = 0 WHERE current_flag = 1");
                $stmt->execute();
            }
            $stmt = $this->db->prepare("INSERT INTO annees_academiques (annee_id, current_flag) VALUES (:annee_id, :current_flag)");
            $stmt->bindParam(':annee_id', $annee_id);
            $stmt->bindParam(':current_flag', $current_flag, PDO::PARAM_INT);
            $success = $stmt->execute();
            $this->db->commit();
            return $success;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateAcademicYear($annee_id, $current_flag)
    {
        $this->db->beginTransaction();
        try {
            if ($current_flag == 1) {
                $stmt = $this->db->prepare("UPDATE annees_academiques SET current_flag = 0 WHERE current_flag = 1");
                $stmt->execute();
            }
            $stmt = $this->db->prepare("UPDATE annees_academiques SET current_flag = :current_flag WHERE annee_id = :annee_id");
            $stmt->bindParam(':annee_id', $annee_id);
            $stmt->bindParam(':current_flag', $current_flag, PDO::PARAM_INT);
            $success = $stmt->execute();
            $this->db->commit();
            return $success;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteAcademicYear($annee_id)
    {
        $stmt = $this->db->prepare("DELETE FROM annees_academiques WHERE annee_id = :annee_id");
        $stmt->bindParam(':annee_id', $annee_id);
        return $stmt->execute();
    }
}
?>