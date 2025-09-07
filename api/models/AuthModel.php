<?php
require_once __DIR__ . '/../utils/Database.php';

class AuthModel {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Authentifie un utilisateur sans utiliser de hachage de mot de passe.
     * AVERTISSEMENT: Ceci est une VULNÉRABILITÉ DE SÉCURITÉ MAJEURE.
     * Les mots de passe sont comparés en clair.
     * @param string $username Le nom d'utilisateur.
     * @param string $password Le mot de passe en clair.
     * @return array|false Un tableau contenant user_id, username et role si l'authentification réussit, sinon false.
     */
    public function authenticate($username, $password) {
        // 1. Récupérer l'utilisateur par son nom d'utilisateur
        // Nous sélectionnons toujours 'password_hash' car c'est le nom de votre colonne,
        // mais elle devrait contenir le mot de passe en clair pour que cela fonctionne.
        $stmt = $this->db->prepare("SELECT user_id, username, password_hash, role FROM utilisateurs WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Vérifier si l'utilisateur existe et si le mot de passe correspond directement
        // Cette comparaison est INSECURE car elle ne hache pas le mot de passe.
        if ($user && $password === $user['password_hash']) {
            // Retourner les informations de l'utilisateur, y compris le rôle
            return [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];
        }

        return false; // Authentification échouée
    }
}
