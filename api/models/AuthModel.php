<?php
require_once __DIR__ . '/../utils/Database.php';

class AuthModel {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Authentifie un utilisateur en comparant le mot de passe fourni avec le hachage stocké.
     * @param string $username Le nom d'utilisateur.
     * @param string $password Le mot de passe en clair.
     * @return array|false Un tableau contenant user_id, username et role si l'authentification réussit, sinon false.
     */
    public function authenticate($username, $password) {
        // 1. Récupérer l'utilisateur par son nom d'utilisateur
        $stmt = $this->db->prepare("SELECT user_id, username, password_hash, role FROM utilisateurs WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Vérifier si l'utilisateur existe et si le mot de passe correspond
        if ($user && password_verify($password, $user['password_hash'])) {
            // Retourner les informations de l'utilisateur, y compris le rôle
            return [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];
        }

        return false; // Authentification échouée
    }

    /**
     * Enregistre un nouvel utilisateur avec un mot de passe haché.
     * @param string $username Le nom d'utilisateur.
     * @param string $password Le mot de passe en clair.
     * @param string $role Le rôle de l'utilisateur.
     * @return bool True si l'enregistrement réussit, false sinon.
     */
    public function register($username, $password, $role) {
        // Hacher le mot de passe avant de le stocker
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insérer l'utilisateur dans la base de données
        $stmt = $this->db->prepare("INSERT INTO utilisateurs (username, password_hash, role) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $password_hash, $role]);
    }
}