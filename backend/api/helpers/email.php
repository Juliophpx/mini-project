<?php
class EmailSender {
    private static function getApiKey() {
        static $apiKey = null;
        if ($apiKey === null) {
            // It's highly recommended to use environment variables for sensitive data.
            $config = require __DIR__ . '/../config/email_config.php';
            $apiKey = $config['sendgrid_api_key'];
        }
        return $apiKey;
    }

    public static function sendVerificationCode($email, $code) {
        $url = 'https://api.sendgrid.com/v3/mail/send';
        $data = [
            'personalizations' => [
                [
                    'to' => [['email' => $email]],
                    'subject' => 'Login Verification Code'
                ]
            ],
            'from' => [
                'email' => 'noreply@hellofellow.com',
                'name' => 'ClickMindAI'
            ],
            'content' => [
                [
                    'type' => 'text/plain',
                    'value' => " Your Verification Code is: $code"
                ]
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . self::getApiKey(),
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // SendGrid devuelve 202 Accepted en caso de éxito.
        if ($http_code !== 202) {
            error_log("SendGrid API Error: Failed to send email to {$email}. HTTP Code: {$http_code}. Response: {$response_body}");
            return false;
        }
        
        return true;
    }
}

?>