<?php
class Response {
    /**
     * Envoie une réponse JSON standardisée
     * 
     * @param int $code Code HTTP
     * @param array $data Données à envoyer
     */
    public static function send(int $code, array $data = []) {
        http_response_code($code );
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $code,
            'data' => $data
        ]);
        exit;
    }

    public static function sende(int $code, array $data = []) {
        http_response_code($code );
        header('Content-Type: application/json');
        $response = $data;
        $response['http_code'] = $code;
        echo json_encode($response );
        exit;
    }

    /**
     * Envoie une réponse d\'erreur
     * 
     * @param int $code Code HTTP
     * @param string $message Message d\'erreur
     */
    public static function error(int $code, string $message) {
        self::send($code, [
            'error' => true,
            'message' => $message
        ]);
    }

    /**
     * Envoie une réponse de succès
     * 
     * @param array $data Données à envoyer
     * @param int $code Code HTTP (200 par défaut)
     */
    public static function success(array $data = [], int $code = 200) {
        self::send($code, [
            'success' => true,
            'data' => $data
        ]);
    }

    public static function sendSuccess($message, $data = [], $statusCode = 200) {
        header('Content-Type: application/json', true, $statusCode);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Send an error JSON response
     * @param string $message Error message
     * @param int $statusCode HTTP status code (default: 400)
     */
    public static function sendError($message, $statusCode = 400) {
        header('Content-Type: application/json', true, $statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}
