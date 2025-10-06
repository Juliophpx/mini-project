<?php
require_once 'helpers/db.php';
require_once 'helpers/validator.php';
require_once 'helpers/auth.php';

function verify_sms() {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        http_response_code(400);
        return ["error" => "Invalid JSON format"];
    }
    
    // Validate allowed fields
    $allowed_fields = ['phone', 'code'];
    $fields_validation = Validator::validateAllowedFields($input, $allowed_fields);
    if (isset($fields_validation['error'])) {
        http_response_code(400);
        return $fields_validation;
    }
    
    // Use the existing 'phone' validator type for consistency
    $validation_rules = [
        'phone' => ['type' => 'phone', 'required' => true],
        'code' => ['type' => 'string', 'required' => true]
    ];
    
    $validation_result = Validator::validateInputs($input, $validation_rules);
    if (isset($validation_result['errors'])) {
        http_response_code(400);
        return ["error" => "Validation failed", "details" => $validation_result['errors']];
    }
    
    try {
        // Step 1: Verify the code with Twilio first.
        $twilio_config = require __DIR__ . '/../../config/twilio_config.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://verify.twilio.com/v2/Services/{$twilio_config['service_sid']}/VerificationCheck");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERPWD, "{$twilio_config['account_sid']}:{$twilio_config['auth_token']}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "To" => $input['phone'],
            "Code" => $input['code']
        ]));
        
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response = json_decode($result, true) ?? [];
        curl_close($ch);
        
        if ($status !== 200 || !isset($response['valid']) || !$response['valid']) {
            error_log("Twilio Verification Failed for phone: {$input['phone']}. Status: $status, Response: " . $result);
            http_response_code(401);
            return ["error" => "Invalid or expired code"];
        }
        
        // Step 2: Code is valid. Find the user in the `users` table.
        $user = query("SELECT id, name, email, phone FROM users WHERE phone = ? AND deleted_at IS NULL", [$input['phone']])->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // This case should ideally not be reached if login_with_sms is called first,
            // as it checks for user existence. This is a safeguard.
            // To prevent user enumeration, return a generic error message.
            error_log("SMS verification successful for a non-existent or deleted user: " . $input['phone']);
            http_response_code(401); // Unauthorized
            return ["error" => "Invalid or expired code"];
        }

        // Step 3: Generate tokens for the found user.
        $access_token = Auth::generateToken($user['id']);
        $refresh_token = Auth::generateRefreshToken($user['id']);
        
        return [
            'status' => 'success',
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("SMS Verification Error: " . $e->getMessage());
        http_response_code(500);
        return ["error" => "An internal server error occurred during verification."];
    }
}

?>
