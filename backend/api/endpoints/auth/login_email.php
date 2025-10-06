<?php
require_once 'helpers/db.php';
require_once 'helpers/validator.php';
require_once 'helpers/auth.php';
require_once 'helpers/email.php';

//sleep(3);

function login_email() {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        http_response_code(400);
        return ["error" => "Invalid JSON format"];
    }
    
    // Validate that only allowed fields are sent
    $allowed_fields = ['email'];
    $fields_validation = Validator::validateAllowedFields($input, $allowed_fields);
    if (isset($fields_validation['error'])) {
        http_response_code(400);
        return $fields_validation;
    }
    
    // Validate input
    $validation_rules = [
        'email' => [
            'type' => 'email',
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
        $user = query("SELECT id FROM users WHERE email = ?", [$input['email']])->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // If user doesn't exist, create a new one
            $default_name = explode('@', $input['email'])[0];
            query(
                "INSERT INTO users (name, email, level, type, created_at) VALUES (?, ?, 'user', 'user', NOW())",
                [$default_name, $input['email']]
            );
            $user_id = get_db_connection()->lastInsertId();
            $user = ['id' => $user_id];
        }

        // Rate Limiting para prevenir abuso
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];

        // Verificar intentos desde la misma IP en los últimos 15 minutos
        $ip_attempts = query(
            "SELECT COUNT(*) as count FROM login_attempts 
            WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$ip_address]
        )->fetch(PDO::FETCH_ASSOC);

        if ($ip_attempts['count'] >= 10) {
            http_response_code(429);
            return ["error" => "Too many attempts from this IP. Please try again later."];
        }

        // Verificar intentos para este email en los últimos 15 minutos
        // NOTA: Esto asume que tu tabla `login_attempts` tiene una columna `email`.
        // Si solo tiene `phone`, necesitarás alterarla.
        $email_attempts = query(
            "SELECT COUNT(*) as count FROM login_attempts 
            WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$input['email']]
        )->fetch(PDO::FETCH_ASSOC);

        if ($email_attempts['count'] >= 3) {
            http_response_code(429);
            return ["error" => "Too many verification attempts for this email. Please try again later."];
        }

        // Registrar el intento
        query(
            "INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)",
            [$input['email'], $ip_address]
        );

        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Save code in database
        query("INSERT INTO verification_codes (email, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))", [$input['email'], $code]);
        
        // Send code via email
        $email_sent = EmailSender::sendVerificationCode($input['email'], $code);
        if (!$email_sent) {
            error_log("Failed to send verification email to {$input['email']}");
            // We don't expose this error to the user to prevent revealing if an email is registered.
        }
        
        http_response_code(200);
        return [
            'status' => 'success',
            'message' => 'A verification code has been sent to your email.'
        ];
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        return ["error" => "Database error"];
    }
}
?>