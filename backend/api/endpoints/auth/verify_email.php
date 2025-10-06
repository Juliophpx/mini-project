<?php
require_once 'helpers/db.php';
require_once 'helpers/validator.php';
require_once 'helpers/auth.php';

function verify_code() {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        http_response_code(400);
        return ["error" => "Invalid JSON format"];
    }
    
    // Validate that only allowed fields are sent
    $allowed_fields = ['email', 'code'];
    $fields_validation = Validator::validateAllowedFields($input, $allowed_fields);
    if (isset($fields_validation['error'])) {
        http_response_code(400);
        return $fields_validation;
    }
    
    $validation_rules = [
        'email' => ['type' => 'email', 'required' => true],
        'code' => ['type' => 'string', 'required' => true]
    ];
    
    $validation_result = Validator::validateInputs($input, $validation_rules);
    if (isset($validation_result['errors'])) {
        http_response_code(400);
        return ["error" => "Validation failed"];
    }
    
    try {
        // Step 1: Find the  email.
        // We also check for soft-deleted email to prevent login.
        $user = query(
            "SELECT id, email FROM users WHERE email = ? AND deleted_at IS NULL",
            [$input['email']]
        )->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            return ["error" => "Invalid credentials"]; // Generic message for security
        }

        // Step 2: Find a valid verification code for this user.
        $verification = query(
            "SELECT * FROM verification_codes 
            WHERE email = ? AND code = ? AND used = 0
            AND expires_at > NOW() 
            ORDER BY created_at DESC LIMIT 1",
            [$input['email'], $input['code']]
        )->fetch(PDO::FETCH_ASSOC);
        
        if (!$verification) {
            http_response_code(401);
            return ["error" => "Invalid or expired code"];
        }
        
        // Step 3: Mark code as used to prevent reuse.
        query(
            "UPDATE verification_codes SET used = 1 WHERE id = ?",
            [$verification['id']]
        );
        
        // Step 4: Generate tokens using the user ID, consistent with other login methods.
        $access_token = Auth::generateToken($user['id']);
        $refresh_token = Auth::generateRefreshToken($user['id']);
        
        return [
            'status' => 'success',
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email']
            ]
        ];
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        return ["error" => "Database error"];
    }
}

?>