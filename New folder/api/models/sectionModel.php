<?php
require_once __DIR__ . '/../utils/Database.php';

class SectionModel {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Récupère les sections, filtrées par filière si filiereId est fourni.
     * @param int|null $filiereId ID de la filière pour filtrer (optionnel)
     * @return array Liste des sections
     */
    public function getSections($filiereId = null) {
        $query = "
            SELECT 
                s.section_id,
                s.nom,
                COALESCE(f.nom, 'Aucun') AS field_name,
                e.nom_etape AS etape_name,
                s.field_id,
                s.etape AS etape_id
            FROM sections s
            LEFT JOIN filieres f ON s.field_id = f.field_id
            JOIN etapes e ON s.etape = e.etape_id
        ";
        
        if ($filiereId !== null) {
            $query .= " WHERE s.field_id = :filiereId";
        }
        
        $query .= " ORDER BY s.nom";
        
        $stmt = $this->db->prepare($query);
        
        if ($filiereId !== null) {
            $stmt->bindParam(':filiereId', $filiereId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute une section.
     * @param string $nom Nom de la section
     * @param int|null $field_id ID de la filière (optionnel)
     * @param int $etape_id ID de l'étape
     * @return array Résultat de l'opération
     */
    public function addSection($nom, $field_id, $etape_id) {
        try {
            $this->db->beginTransaction();

            if (empty($nom) || empty($etape_id)) {
                throw new Exception('Le nom et l\'étape sont requis', 400);
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM etapes WHERE etape_id = ?");
            $stmt->execute([$etape_id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Étape invalide', 400);
            }

            if ($field_id) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM filieres WHERE field_id = ?");
                $stmt->execute([$field_id]);
                if ($stmt->fetchColumn() == 0) {
                    throw new Exception('Filière invalide', 400);
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO sections (nom, field_id, etape)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$nom, $field_id ?: null, $etape_id]);
            $section_id = $this->db->lastInsertId();

            $this->db->commit();
            return [
                'status' => 'success',
                'message' => 'Section ajoutée avec succès',
                'section_id' => $section_id
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Échec de l\'ajout de la section: ' . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Met à jour une section.
     * @param int $section_id ID de la section
     * @param string $nom Nom de la section
     * @param int|null $field_id ID de la filière (optionnel)
     * @param int $etape_id ID de l'étape
     * @return array Résultat de l'opération
     */
    public function updateSection($section_id, $nom, $field_id, $etape_id) {
        try {
            $this->db->beginTransaction();

            if (empty($nom) || empty($etape_id)) {
                throw new Exception('Le nom et l\'étape sont requis', 400);
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM sections WHERE section_id = ?");
            $stmt->execute([$section_id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Section invalide', 400);
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM etapes WHERE etape_id = ?");
            $stmt->execute([$etape_id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Étape invalide', 400);
            }

            if ($field_id) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM filieres WHERE field_id = ?");
                $stmt->execute([$field_id]);
                if ($stmt->fetchColumn() == 0) {
                    throw new Exception('Filière invalide', 400);
                }
            }

            $stmt = $this->db->prepare("
                UPDATE sections
                SET nom = ?, field_id = ?, etape = ?
                WHERE section_id = ?
            ");
            $stmt->execute([$nom, $field_id ?: null, $etape_id, $section_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Aucune section trouvée avec cet ID', 404);
            }

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Section mise à jour avec succès'];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Échec de la mise à jour de la section: ' . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Récupère les groupes, filtrés par filière si filiereId est fourni.
     * @param int|null $filiereId ID de la filière pour filtrer (optionnel)
     * @return array Liste des groupes
     */
    public function getGroups($filiereId = null) {
        $query = "
            SELECT 
                g.group_id,
                g.nom,
                COALESCE(f.nom, 'Aucun') AS field_name,
                s.nom AS section_name,
                g.field_id,
                g.section_id
            FROM groupes g
            LEFT JOIN filieres f ON g.field_id = f.field_id
            JOIN sections s ON g.section_id = s.section_id
        ";
        
        if ($filiereId !== null) {
            $query .= " WHERE g.field_id = :filiereId";
        }
        
        $query .= " ORDER BY g.nom";
        
        $stmt = $this->db->prepare($query);
        
        if ($filiereId !== null) {
            $stmt->bindParam(':filiereId', $filiereId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute un groupe.
     * @param string $nom Nom du groupe
     * @param int|null $field_id ID de la filière (optionnel)
     * @param int $section_id ID de la section
     * @return array Résultat de l'opération
     */
    public function addGroup($nom, $field_id, $section_id) {
        try {
            $this->db->beginTransaction();

            if (empty($nom) || empty($section_id)) {
                throw new Exception('Le nom et la section sont requis', 400);
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM sections WHERE section_id = ?");
            $stmt->execute([$section_id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Section invalide', 400);
            }

            if ($field_id) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM filieres WHERE field_id = ?");
                $stmt->execute([$field_id]);
                if ($stmt->fetchColumn() == 0) {
                    throw new Exception('Filière invalide', 400);
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO groupes (nom, field_id, section_id)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$nom, $field_id ?: null, $section_id]);
            $group_id = $this->db->lastInsertId();

            $this->db->commit();
            return [
                'status' => 'success',
                'message' => 'Groupe ajouté avec succès',
                'group_id' => $group_id
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Échec de l\'ajout du groupe: ' . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Met à jour un groupe.
     * @param int $group_id ID du groupe
     * @param string $nom Nom du groupe
     * @param int|null $field_id ID de la filière (optionnel)
     * @param int $section_id ID de la section
     * @return array Résultat de l'opération
     */
    public function updateGroup($group_id, $nom, $field_id, $section_id) {
        try {
            $this->db->beginTransaction();

            if (empty($nom) || empty($section_id)) {
                throw new Exception('Le nom et la section sont requis', 400);
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM groupes WHERE group_id = ?");
            $stmt->execute([$group_id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Groupe invalide', 400);
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM sections WHERE section_id = ?");
            $stmt->execute([$section_id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Section invalide', 400);
            }

            if ($field_id) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM filieres WHERE field_id = ?");
                $stmt->execute([$field_id]);
                if ($stmt->fetchColumn() == 0) {
                    throw new Exception('Filière invalide', 400);
                }
            }

            $stmt = $this->db->prepare("
                UPDATE groupes
                SET nom = ?, field_id = ?, section_id = ?
                WHERE group_id = ?
            ");
            $stmt->execute([$nom, $field_id ?: null, $section_id, $group_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Aucun groupe trouvé avec cet ID', 404);
            }

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Groupe mis à jour avec succès'];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Échec de la mise à jour du groupe: ' . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Supprime une section et ses groupes associés, si aucun étudiant n'est lié aux groupes.
     * @param int $section_id ID de la section
     * @return array Résultat de l'opération
     */
    public function deleteSection($section_id) {
        try {
            $this->db->beginTransaction();

            // Vérifier si la section existe
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM sections WHERE section_id = ?");
            $stmt->execute([$section_id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Section invalide', 400);
            }

            // Récupérer tous les groupes associés à la section
            $stmt = $this->db->prepare("SELECT group_id FROM groupes WHERE section_id = ?");
            $stmt->execute([$section_id]);
            $groups = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Vérifier si des étudiants sont associés à chaque groupe
            foreach ($groups as $group_id) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM etudiants WHERE group_id = ?");
                $stmt->execute([$group_id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Impossible de supprimer la section : des étudiants sont associés à un ou plusieurs groupes', 400);
                }
            }

            // Supprimer les groupes associés
            if (!empty($groups)) {
                $stmt = $this->db->prepare("DELETE FROM groupes WHERE section_id = ?");
                $stmt->execute([$section_id]);
            }

            // Supprimer la section
            $stmt = $this->db->prepare("DELETE FROM sections WHERE section_id = ?");
            $stmt->execute([$section_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Aucune section trouvée avec cet ID', 404);
            }

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Section et ses groupes supprimés avec succès'];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Échec de la suppression de la section: ' . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Supprime un groupe.
     * @param int $group_id ID du groupe
     * @return array Résultat de l'opération
     */
    public function deleteGroup($group_id) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM groupes WHERE group_id = ?");
            $stmt->execute([$group_id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Groupe invalide', 400);
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM etudiants WHERE group_id = ?");
            $stmt->execute([$group_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Impossible de supprimer un groupe contenant des étudiants', 400);
            }

            $stmt = $this->db->prepare("DELETE FROM groupes WHERE group_id = ?");
            $stmt->execute([$group_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Aucun groupe trouvé avec cet ID', 404);
            }

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Groupe supprimé avec succès'];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Échec de la suppression du groupe: ' . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Récupère les options pour les formulaires (filières, étapes, sections).
     * @return array Liste des options
     */
    public function getOptions() {
        $filieres = $this->db->prepare("SELECT field_id, nom FROM filieres ORDER BY nom");
        $filieres->execute();
        $etapes = $this->db->prepare("SELECT etape_id, nom_etape FROM etapes ORDER BY nom_etape");
        $etapes->execute();
        $sections = $this->db->prepare("SELECT section_id, nom FROM sections ORDER BY nom");
        $sections->execute();

        return [
            'filieres' => $filieres->fetchAll(PDO::FETCH_ASSOC),
            'etapes' => $etapes->fetchAll(PDO::FETCH_ASSOC),
            'sections' => $sections->fetchAll(PDO::FETCH_ASSOC)
        ];
    }
}
?>