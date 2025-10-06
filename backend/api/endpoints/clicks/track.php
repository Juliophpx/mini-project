<?php
require_once __DIR__ . '/../../helpers/db.php';
require_once __DIR__ . '/../../helpers/auth_middleware.php';
require_once __DIR__ . '/../../helpers/validator.php';

function track_click() {
    $user_id = requireAuth();

    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        http_response_code(400);
        return ["error" => "Invalid JSON format"];
    }

    $allowed_fields = ['button_id'];
    $fields_validation = Validator::validateAllowedFields($input, $allowed_fields);
    if (isset($fields_validation['error'])) {
        http_response_code(400);
        return $fields_validation;
    }

    $validation_rules = [
        'button_id' => [
            'type' => 'string',
            'required' => true,
            'max_length' => 255
        ]
    ];

    $validation_result = Validator::validateInputs($input, $validation_rules);
    if (isset($validation_result['errors'])) {
        http_response_code(400);
        return ["error" => "Validation failed", "details" => $validation_result['errors']];
    }

    $button_id = $input['button_id'];

    try {
        $sql = "
            INSERT INTO user_button_clicks (user_id, button_id, click_count)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE click_count = click_count + 1
        ";
        
        query($sql, [$user_id, $button_id]);

        http_response_code(200);
        return [
            'status' => 'success',
            'message' => 'Click tracked successfully.'
        ];
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        return ["error" => "Database error"];
    }
}
?>