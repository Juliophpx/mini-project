<?php

/**
 * CORS Configuration
 *
 * Define the list of allowed origins that can access the API.
 * Use full URLs, including the protocol and port if necessary.
 */
return [
// Allowed origins are now managed via the environment variable
    // CORS_ALLOWED_ORIGINS in your file .env.
    // Example in .env: CORS_ALLOWED_ORIGINS=http://localhost:8090,https://your-domain.com
    'allowed_origins' => explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: '')
];

?>