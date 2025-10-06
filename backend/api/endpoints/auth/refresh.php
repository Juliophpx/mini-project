<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../config/auth_config.php';

function refresh() {
    // Only accept POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        return ['error' => 'Method not allowed'];
    }

    // Get refresh token from body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['refresh_token'])) {
        http_response_code(400);
        return ['error' => 'Refresh token not provided'];
    }

    // Try to refresh the token
    $result = Auth::refreshAccessToken($data['refresh_token']);

    if ($result === false) {
        http_response_code(401);
        return ['error' => 'Invalid or expired refresh token'];
    }

    return [
        'access_token' => $result['access_token'],
        'refresh_token' => $result['refresh_token']
    ];
}
?>