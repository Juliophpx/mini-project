<?php
require_once __DIR__ . '/../../helpers/auth.php';

$user = get_user_from_token();

if (!$user) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid or expired token.']);
    exit;
}

echo json_encode(['message' => 'Token is valid.', 'user' => $user]);
