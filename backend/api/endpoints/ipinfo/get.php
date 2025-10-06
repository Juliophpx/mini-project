<?php
// Set the content type to application/json
header('Content-Type: application/json');

// Get the user's IP address, checking for proxies
$ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}

// The token for the ipinfo.io API
$token = 'fb4e243249b675';
// The URL for the ipinfo.io API, now including the user's IP
$url = "https://ipinfo.io/" . urlencode(trim($ip)) . "/json?token={$token}";

// Use file_get_contents to fetch the data from the API
$response = @file_get_contents($url);

// Check for errors
if ($response === FALSE) {
    // If there was an error, create a JSON response with an error message
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch IP info for ' . $ip]);
    exit;
}

// If the request was successful, output the response from the API
// The response is already in JSON format, so we can just echo it
echo $response;