<?php
/**
 * CSRF Configuration
 * Simple configuration for CSRF protection
 */
return [
    // URLs that should be excluded from CSRF protection
    'excluded_urls' => [
        '/api/auth/login',  // Login endpoint doesn't need CSRF as it's the initial point
        '/api/csrf/token'   // Token generation endpoint
    ]
];
?>