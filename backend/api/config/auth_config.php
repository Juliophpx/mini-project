<?php
/**
 * Authentication Configuration
 *
 * Values are loaded from environment variables (.env file).
 */
return [
    'secret_key'               => getenv('JWT_SECRET_KEY'),
    'token_expiration'         => (int) getenv('JWT_TOKEN_EXPIRATION'),
    'refresh_token_expiration' => (int) getenv('JWT_REFRESH_TOKEN_EXPIRATION'),
];
?>
