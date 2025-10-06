<?php
/**
 * Authentication Middleware
 * Validates JWT tokens for protected endpoints
 */
require_once 'auth.php';

function requireAuth() {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(\S+)/', $auth_header, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit;
    }
    
    $token = $matches[1];
    $user = Auth::verifyToken($token);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    return $user['user_id'];
}

?>