<?php
require_once 'helpers/db.php';
require_once 'helpers/validator.php';
require_once 'helpers/auth.php';

function login_sms() {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        http_response_code(400);
        return ["error" => "Invalid JSON format"];
    }
    
    // Validate allowed fields
    $allowed_fields = ['phone'];
    $fields_validation = Validator::validateAllowedFields($input, $allowed_fields);
    if (isset($fields_validation['error'])) {
        http_response_code(400);
        return $fields_validation;
    }
    
    // Validate input
    $validation_rules = [
        'phone' => [
            'type' => 'phone',
            'required' => true
        ]
    ];
    
    $validation_result = Validator::validateInputs($input, $validation_rules);
    if (isset($validation_result['errors'])) {
        http_response_code(400);
        return ["error" => "Validation failed", "details" => $validation_result['errors']];
    }
    
    try {
        // Verify if user exists
        $user = query("SELECT id FROM users WHERE phone = ? AND deleted_at IS NULL", [$input['phone']])->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // If user doesn't exist, create a new one
            $phone = $input['phone'];
            $last_four = substr($phone, -4);
            $name = "Phone: " . $last_four;
            query(
                "INSERT INTO users (name, phone, level, type, created_at) VALUES (?, ?, 'user', 'user', NOW())",
                [$name, $phone]
            );
            $user_id = get_db_connection()->lastInsertId();
            $user = ['id' => $user_id];
        }

        // Obtener IP del cliente
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        
        // Verificar intentos en los últimos 15 minutos desde la misma IP
        $ip_attempts = query(
            "SELECT COUNT(*) as count FROM login_attempts 
            WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$ip_address]
        )->fetch(PDO::FETCH_ASSOC);
        
        if ($ip_attempts['count'] >= 10) {
            http_response_code(429);
            return ["error" => "Too many attempts from this IP. Please try again later."];
        }
        
        // Verificar intentos para este número de teléfono en los últimos 15 minutos
        $phone_attempts = query(
            "SELECT COUNT(*) as count FROM login_attempts 
            WHERE phone = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$input['phone']]
        )->fetch(PDO::FETCH_ASSOC);
        
        if ($phone_attempts['count'] >= 3) {
            http_response_code(429);
            return ["error" => "Too many verification attempts for this phone number. Please try again later."];
        }
        
        // Registrar el intento
        query(
            "INSERT INTO login_attempts (phone, ip_address) VALUES (?, ?)",
            [$input['phone'], $ip_address]
        );
        

        
        /*
        // It IS necessary to check if the user exists to decide whether to send an SMS.
        // However, to prevent user enumeration, we will always return a generic success message.
        $user = query(
            "SELECT id, identity FROM identities WHERE identity = ?", 
            [$input['phone']]
        )->fetch(PDO::FETCH_ASSOC);
        
        // If the user is not found, we stop here but return a success message.
        // This prevents an attacker from guessing registered phone numbers.
        if (!$user) {
            // We don't send an SMS, but we tell the client that we might have.
            return [
                'status' => 'success',
                'message' => 'If your phone number is registered, you will receive a verification code.'
            ];
        }
        // --- From this point on, we know the user exists and we can proceed to send the SMS. ---


*/

        // Load Twilio configuration
        $twilio_config = require __DIR__ . '/../../config/twilio_config.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://verify.twilio.com/v2/Services/{$twilio_config['service_sid']}/Verifications");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERPWD, "{$twilio_config['account_sid']}:{$twilio_config['auth_token']}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            // BUG FIX: Use the validated phone from the input. The $user variable doesn't have 'phone'.
            "To" => $input['phone'],
            "Channel" => "sms"
        ]));
        
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response_body = json_decode($result, true);
        curl_close($ch);
        
        if ($status !== 201) {
            // Log the actual error for debugging, but don't expose it to the client.
            error_log("Twilio API Error: Status $status, Body: " . print_r($response_body, true));
            throw new Exception("An error occurred while trying to send the verification code.");
        }
        
        return [
            'status' => 'success',
            'message' => 'You will receive a verification code.'
        ];
        
    } catch (Exception $e) {
        error_log("login_with_sms error: " . $e->getMessage());
        http_response_code(500);
        return ["error" => "An internal server error occurred. Please try again later."];
    }
}

?>