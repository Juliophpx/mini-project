<?php

/**
 * Twilio Configuration
 *
 * Values are loaded from environment variables (.env file).
 */
return [
    'account_sid' => getenv('TWILIO_ACCOUNT_SID'),
    'auth_token'  => getenv('TWILIO_AUTH_TOKEN'),
    'service_sid' => getenv('TWILIO_VERIFY_SERVICE_SID'),
];

?>

