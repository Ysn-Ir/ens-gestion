<?php
require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/ProfessorModel.php';

class ProfessorMiddleware extends AuthMiddleware {
    private $response;
    private $model;

    public function __construct() {
        parent::__construct();
        $this->response = new Response();
        $this->model = new ProfessorModel();
    }

    /**
     * Vérifie si l'utilisateur connecté a les privilèges de professeur,
     * chef de département ou chef de filière.
     * Si ce n'est pas le cas, envoie une réponse 403 Forbidden et termine l'exécution.
     */
    public function verifyProfessor() {
        parent::verifySession(); // Vérifie l'authentification de la session

        // Vérifie si le rôle de l'utilisateur n'est ni 'prof', ni 'chef_dep', ni 'chef_fill'
        if ($_SESSION['user']['role'] !== 'prof' && 
            $_SESSION['user']['role'] !== 'chef_dep' && 
            $_SESSION['user']['role'] !== 'chef_fill') {
            $this->response->send(403, ['error' => 'Privilèges professeur requis']);
            exit; // Termine l'exécution si les privilèges sont insuffisants
        }
    }
}
?>
