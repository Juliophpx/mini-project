<?php
require_once __DIR__ . '/../../helpers/db.php';
require_once __DIR__ . '/../../helpers/helpers.php';

function get_users($id = null) {
    try {
        $auth_user_id = requireAuth(); // Asegúrate de tener el ID del usuario autenticado
        $user_info = get_user_level_type($auth_user_id);

        if ($user_info['level'] === 'user') {
            // Solo puede ver su propio usuario
            $stmt = query('SELECT id, name, email, phone, level, type, created_at, updated_at FROM users WHERE id = ? AND deleted_at IS NULL', [$auth_user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(404);
                return ['error' => 'User not found'];
            }

            return ['user' => $user];
        } elseif ($user_info['level'] === 'admin') {
            if ($id) {
                // Puede ver cualquier usuario por ID
                $stmt = query('SELECT id, name, email, phone, level, type, created_at, updated_at FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    http_response_code(404);
                    return ['error' => 'User not found'];
                }

                return ['user' => $user];
            } else {
                // Puede ver todos los usuarios
                $stmt = query('SELECT id, name, email, phone, level, type, created_at, updated_at FROM users WHERE deleted_at IS NULL', []);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return ['users' => $users];
            }
        } else {
            http_response_code(403);
            return ['error' => 'Unauthorized'];
        }
    } catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}
?> 