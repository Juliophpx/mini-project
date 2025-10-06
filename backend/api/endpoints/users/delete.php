<?php
require_once __DIR__ . '/../../helpers/db.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/helpers.php';

function delete_user($id) {
    $auth_user_id = requireAuth();
    $user_info = get_user_level_type($auth_user_id);

    if ($user_info['level'] !== 'admin') {
        http_response_code(403);
        return ['error' => 'Unauthorized'];
    }

    if (!$id) {
        http_response_code(400);
        return ['error' => 'User ID is required'];
    }

    try {
        // Check if user exists
        $stmt = query('SELECT id FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            return ['error' => 'User not found'];
        }

        // Perform a soft delete
        $sql = 'UPDATE users SET deleted_at = NOW() WHERE id = ?';
        query($sql, [$id]);

        http_response_code(204); // No Content
        return null;
    } catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}
