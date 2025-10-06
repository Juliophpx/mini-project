<?php
/**
 * CSRF Protection Helper
 * Simple and robust CSRF protection implementation
 */
class CSRF {
    /**
     * Generate or return existing CSRF token
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token from request header
     */
    public static function verify() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }

        $headers = getallheaders();
        $token = $headers['X-Csrf-Token'] ?? null;

        if (!isset($_SESSION['csrf_token']) || !$token || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            echo json_encode(["error" => "Invalid CSRF token"]);
            exit;
        }

        return true;
    }
}

?>