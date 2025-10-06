<?php
require_once __DIR__ . '/../../helpers/db.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/helpers.php';

function update_user($id) {
    if (!$id) {
        http_response_code(400);
        return ['error' => 'User ID is required'];
    }

    $auth_user_id = requireAuth();
    $user_info = get_user_level_type($auth_user_id);

    // Si es user, solo puede actualizar su propio usuario
    if ($user_info['level'] === 'user' && $auth_user_id != $id) {
        http_response_code(403);
        return ['error' => 'Unauthorized'];
    }

    // Get input data
    $data = json_decode(file_get_contents('php://input'), true);

    $allowed_levels = ['user', 'admin'];
    $allowed_types = ['user', 'customer'];

    $level = $data['level'] ?? null;
    $type = $data['type'] ?? null;

    // Solo admin puede cambiar level o type
    if ($user_info['level'] !== 'admin' && ($level !== null || $type !== null)) {
        http_response_code(403);
        return ['error' => 'Only admin can change level or type'];
    }

    if ($level !== null && !in_array($level, $allowed_levels)) {
        http_response_code(400);
        return ['error' => 'Invalid level'];
    }
    if ($type !== null && !in_array($type, $allowed_types)) {
        http_response_code(400);
        return ['error' => 'Invalid type'];
    }

    // Define validation rules
    $rules = [
        'name' => ['type' => 'string', 'min_length' => 2, 'max_length' => 255],
        'email' => ['type' => 'email'],
        'phone' => ['type' => 'phone'],
    ];

    // Validate input
    $validation = Validator::validateInputs($data, $rules);
    if (isset($validation['errors'])) {
        http_response_code(400);
        return ['error' => 'Validation failed', 'details' => $validation['errors']];
    }

    $validated_data = $validation['values'];

    // Actualizar en la base de datos
    $fields = [];
    $params = [];

    if (isset($data['name'])) {
        $fields[] = 'name = ?';
        $params[] = $data['name'];
    }
    if (isset($data['email'])) {
        $fields[] = 'email = ?';
        $params[] = $data['email'];
    }
    if (isset($data['phone'])) {
        $fields[] = 'phone = ?';
        $params[] = $data['phone'];
    }
    // Solo admin puede cambiar level y type
    if ($user_info['level'] === 'admin') {
        if ($level !== null) {
            $fields[] = 'level = ?';
            $params[] = $level;
        }
        if ($type !== null) {
            $fields[] = 'type = ?';
            $params[] = $type;
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        return ['error' => 'No fields to update'];
    }

    $params[] = $id;
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL';
    query($sql, $params);

    // Fetch the updated user data
    $stmt = query('SELECT id, name, email, phone, level, type, created_at, updated_at FROM users WHERE id = ?', [$id]);
    $updated_user = $stmt->fetch(PDO::FETCH_ASSOC);

    return ['message' => 'User updated successfully', 'user' => $updated_user];
}
