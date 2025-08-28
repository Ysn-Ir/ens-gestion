<?php
// noteModel.php
// Ce fichier gère toutes les interactions avec la table 'Notes' de la base de données.

require_once __DIR__ . '/../utils/Database.php'; // Assurez-vous que le chemin est correct

class NoteModelP
{
    private $db; // Instance de la connexion PDO à la base de données

    public function __construct()
    {
        // Lors de l'instanciation du modèle, obtenir la connexion à la base de données
        $this->db = (new Database())->getConnection();
    }

    /**
     * Récupère toutes les notes de la table `Notes`.
     * @return array Toutes les notes.
     */
    public function getNotes()
    {
        $stmt = $this->db->prepare("SELECT n.* FROM notes n");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Récupère une note spécifique par un attribut donné.
     * @param string $note_att L'attribut par lequel rechercher (ex: 'note_id').
     * @param mixed $note_val La valeur de l'attribut.
     * @return array|null La note trouvée ou null si non trouvée.
     */
    public function getNote($note_att="id",$note_val)
    {
        try {
            // Utilise une requête préparée pour éviter les injections SQL
            $stmt = $this->db->prepare("SELECT * FROM Notes WHERE $note_att = ?");
            $stmt->execute([$note_val]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);
            return $note ?: null;  // retourne null si non trouvé
        } catch (PDOException $e) {
            error_log("Error fetching Note: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée une nouvelle note dans la table `Notes`.
     * Utilise une transaction pour assurer l'atomicité.
     * @param array $data Les données de la note (student_id, element_id, semester_id, annee_id, note_final).
     * @return bool True en cas de succès, False sinon.
     */
    public function createNote($data)
    {
        $this->db->beginTransaction(); // Démarre une transaction

        try {
            $stmt = $this->db->prepare("
                INSERT INTO Notes (student_id, element_id, semester_id, annee_id, note_final)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['student_id'],
                $data['element_id'],
                $data['semester_id'],
                $data['annee_id'],
                $data['note_final'] ?? null   // ajoute note_final, permet null si non défini
            ]);
            $userId = $this->db->lastInsertId(); // Récupère l'ID de la dernière insertion (si nécessaire)
            $this->db->commit(); // Valide la transaction
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack(); // Annule la transaction en cas d'erreur
            error_log("Error creating Note: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour une note existante dans la table `Notes`.
     * Utilise une transaction pour assurer l'atomicité.
     * @param array $data Les données de la note à mettre à jour (note_id, student_id, element_id, etc.).
     * @return bool True en cas de succès, False sinon.
     */
    public function updateNote($data)
    {
        $this->db->beginTransaction(); // Démarre une transaction

        try {
            $stmt = $this->db->prepare("
                UPDATE Notes
                SET student_id = ?, element_id = ?, semester_id = ?, annee_id = ?,note_final = ?
                WHERE note_id = ?
            ");
            $stmt->execute([
                $data['student_id'],
                $data['element_id'],
                $data['semester_id'],
                $data['annee_id'],
                $data['note_final'],
                $data['note_id']
            ]);
            $this->db->commit(); // Valide la transaction
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack(); // Annule la transaction en cas d'erreur
            error_log("Error updating Note: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une note de la table `Notes`.
     * Utilise une transaction pour assurer l'atomicité.
     * @param int $note_id L'ID de la note à supprimer.
     * @return bool True en cas de succès, False sinon.
     */
    public function deleteNote($note_id)
    {
        $this->db->beginTransaction(); // Démarre une transaction

        try {
            $stmt = $this->db->prepare("DELETE FROM Notes WHERE note_id = ?");
            $stmt->execute([$note_id]);
            $this->db->commit(); // Valide la transaction
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack(); // Annule la transaction en cas d'erreur
            error_log("Error deleting Note: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour la note de rattrapage et/ou la note finale d'une note.
     * Utilise une transaction pour assurer l'atomicité.
     * @param int $note_id L'ID de la note.
     * @param float|null $ratt_value La valeur de la note de rattrapage.
     * @param float|null $final_value La valeur de la note finale (optionnel).
     * @return bool True en cas de succès, False sinon.
     */
    public function setRatt($note_id, $ratt_value, $final_value = null)
    {
        $this->db->beginTransaction(); // Démarre une transaction

        try {
            // Si final_value n'est pas fourni, met à jour seulement note_rattrapage
            if ($final_value === null) {
                $stmt = $this->db->prepare("UPDATE Notes SET note_rattrapage = ? WHERE note_id = ?");
                $stmt->execute([$ratt_value, $note_id]);
            } else {
                // Sinon, met à jour les deux
                $stmt = $this->db->prepare("
                    UPDATE Notes
                    SET note_rattrapage = ?, note_final = ?
                    WHERE note_id = ?
                ");
                $stmt->execute([$ratt_value, $final_value, $note_id]);
            }
            $this->db->commit(); // Valide la transaction
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack(); // Annule la transaction en cas d'erreur
            error_log("Error setting Ratt: " . $e->getMessage());
            return false;
        }
    }
}
?>
    