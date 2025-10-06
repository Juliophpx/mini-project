<?php
require_once __DIR__ . '/../../helpers/db.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/helpers.php';



function create_user() {
    // Get input data
    $data = json_decode(file_get_contents('php://input'), true);

    $allowed_levels = ['user', 'admin'];
    $allowed_types = ['user', 'customer'];

    $level = $data['level'] ?? 'user'; // valor por defecto
    $type = $data['type'] ?? 'user';   // valor por defecto

    if (!in_array($level, $allowed_levels)) {
        http_response_code(400);
        return ['error' => 'Invalid level'];
    }
    if (!in_array($type, $allowed_types)) {
        http_response_code(400);
        return ['error' => 'Invalid type'];
    }

    // Define validation rules
    $rules = [
        'name' => ['type' => 'string', 'required' => true, 'min_length' => 2, 'max_length' => 255],
        'email' => ['type' => 'email', 'required' => true],
        'phone' => ['type' => 'phone', 'required' => false],
    ];

    // Validate input
    $validation = Validator::validateInputs($data, $rules);
    if (isset($validation['errors'])) {
        http_response_code(400);
        return ['error' => 'Validation failed', 'details' => $validation['errors']];
    }

    $validated_data = $validation['values'];

    // Prepare data for insertion
    $user_data = [
        'name' => $validated_data['name'],
        'email' => $validated_data['email'],
        'phone' => $validated_data['phone'] ?? null,
        'level' => $level,
        'type' => $type,
    ];

    try {
        $auth_user_id = requireAuth();
        $user_info = get_user_level_type($auth_user_id);

        if ($user_info['level'] !== 'admin') {
            http_response_code(403);
            return ['error' => 'Unauthorized'];
        }

        // Check for existing email or phone
        $stmt = query('SELECT id FROM users WHERE email = ? OR phone = ?', [$user_data['email'], $user_data['phone']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            return ['error' => 'User with this email or phone already exists'];
        }

        // Insert user into the database
        $sql = 'INSERT INTO users (name, email, phone, level, type, created_at) VALUES (?, ?, ?, ?, ?, NOW())';
        query($sql, [
            $user_data['name'],
            $user_data['email'],
            $user_data['phone'],
            $user_data['level'],
            $user_data['type']
        ]);

        // Get the ID of the new user
        $new_user_id = get_db_connection()->lastInsertId();

        // Return the new user's data (without password)
        http_response_code(201);
        return [
            'message' => 'User created successfully',
            'user' => [
                'id' => $new_user_id,
                'name' => $user_data['name'],
                'email' => $user_data['email'],
                'phone' => $user_data['phone'],
                'level' => $user_data['level'],
                'type' => $user_data['type'],
            ]
        ];
    } catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}
