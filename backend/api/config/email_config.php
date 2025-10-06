<?php

/**
 * Email Configuration
 *
 * Values are loaded from environment variables (.env file).
 */
return [
    'sendgrid_api_key' => getenv('SENDGRID_API_KEY'),
];

?>