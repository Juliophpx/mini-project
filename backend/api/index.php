<?php
// --- START: Loading environment variables from .env ---
// This allows for secure configurations outside of version control (Git).
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignore comments and empty lines
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        putenv(sprintf('%s=%s', trim($name), trim($value)));
    }
}
// --- END: LOAD ENVIROMENT ---

// --- START: Session Cookie Configuration for Cross-Site CSRF ---
// This is crucial for allowing the frontend on a different domain to send
// the session cookie, which is necessary for CSRF validation to work.
session_set_cookie_params([
    'lifetime' => 0, // Session cookie
    'path' => '/',
    'domain' => '.juliophp.com', // Crucial for cross-domain/subdomain sessions
    'secure' => true,   // Must be true if SameSite is None
    'httponly' => true, // Prevent client-side script access
    'samesite' => 'None' // Allow cross-domain cookie sharing
]);
// --- END: Session Cookie Configuration ---

// Start session after setting cookie parameters
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle CORS (Cross-Origin Resource Sharing)
// This is necessary to allow your frontend (e.g., running on 192.168.1.188:8090)
// to make requests to your API.

// --- START: Robust CORS Handling ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN");
}

// Handle preflight OPTIONS request from the browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204); // No Content
    exit();
}
// --- END: Robust CORS Handling ---

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'");
header("Referrer-Policy: strict-origin-when-cross-origin");





require_once 'routes.php';

?>