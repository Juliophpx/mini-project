<?php
/**
 * Main router file
 * Simple and minimal router for API endpoints
 */

require_once 'helpers/csrf.php';
require_once 'helpers/auth_middleware.php';

// Get request info
$uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', trim($uri_path, '/'));
$method = $_SERVER['REQUEST_METHOD'];

// Find the 'api' segment to make the routing independent of the base path.
// This will work for /www/api/, /hellofellowdev/api/, or just /api/.
$api_key = array_search('mini-project', array_map('strtolower', $uri_segments));
$api_parts = [];
if ($api_key !== false) {
    // Get everything after the 'api' segment
    $api_parts = array_slice($uri_segments, $api_key + 1);
}

// The resource is the first part after /api/, the action is the second.
$resource = $api_parts[2] ?? '';
$action = $api_parts[3] ?? '';

// Set JSON content type
header('Content-Type: application/json');

// Define routes that are exempt from CSRF protection.
// These are typically routes that initiate a session or don't handle sensitive data.
$csrf_exempt_routes = [
    'auth/login_email',
    'auth/login_sms',
    'auth/verify_email',
    'auth/verify_sms',
    'auth/refresh',
    'auth/validate_token',
    'clicks/track',
    'ai/generate_text',
    'ai/analyze_clicks'
];

// By convention, all state-changing requests (POST, PUT, DELETE, PATCH) are CSRF protected...
$is_state_changing_method = in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH']);
// ...unless they are explicitly exempted.
$current_route_path = "$resource/$action";
$is_exempt = in_array(trim($current_route_path, '/'), $csrf_exempt_routes);

if ($is_state_changing_method && !$is_exempt) {
    CSRF::verify();
}

// Define resources that can have an ID parameter (e.g., /users/{id})
$resources_with_id = ['users'];

$route_key = "$method $resource";
// For resources that handle both collection (e.g., /users) and specific items (e.g., /users/123),
// we normalize the route key to always include a trailing slash.
if (in_array($resource, $resources_with_id) && in_array($method, ['GET', 'PUT', 'DELETE'])) {
    $route_key = "$method $resource/";
}

// Simple routing
try {
    switch ($route_key) {
        // CSRF token route
        case 'GET csrf':
            if ($action === 'token') {
                echo json_encode(['csrf_token' => CSRF::getToken()]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            }
            break;

        // ipinfo route
        case 'GET ipinfo':
            require_once 'endpoints/ipinfo/get.php';
            break;

        // Auth routes
        case 'POST auth':
            switch ($action) {
                case 'login_email':
                    require_once 'endpoints/auth/login_email.php';
                    echo json_encode(login_email());
                    break;
                case 'login_sms':
                    require_once 'endpoints/auth/login_sms.php';
                    echo json_encode(login_sms());
                    break;
                case 'verify_email':
                    require_once 'endpoints/auth/verify_email.php';
                    echo json_encode(verify_code());
                    break;
                case 'verify_sms':
                    require_once 'endpoints/auth/verify_sms.php';
                    echo json_encode(verify_sms());
                    break;
                case 'logout':
                    $userId = requireAuth();
                    require_once 'endpoints/auth/logout.php';
                    echo json_encode(logout($userId));
                    break;
                case 'refresh':
                    require_once 'endpoints/auth/refresh.php';
                    echo json_encode(refresh());
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Not found']);
                    break;
            }
            break;

        case 'GET auth':
            switch ($action) {
                case 'validate_token':
                    require_once 'endpoints/auth/validate_token.php';
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Not found']);
                    break;
            }
            break;

        // User routes
        case 'GET users/':
            $user = requireAuth();
            require_once 'endpoints/users/get.php';
            // If $action is an ID (e.g., /users/123), pass it. Otherwise, get all users.
            echo json_encode(get_users($action ?: null));
            break;
            
        case 'POST users':
            $user = requireAuth();
            require_once 'endpoints/users/create.php';
            echo json_encode(create_user());
            break;
            
        case 'PUT users/':
            $user = requireAuth();
            require_once 'endpoints/users/update.php';
            echo json_encode(update_user($action));
            break;
            
        case 'DELETE users/':
            $user = requireAuth();
            require_once 'endpoints/users/delete.php';
            echo json_encode(delete_user($action));
            break;



        // Clicks tracking
        case 'POST clicks':
            if ($action === 'track') {
                require_once 'endpoints/clicks/track.php';
                echo json_encode(track_click());
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            }
            break;

        case 'GET clicks':
            if ($action === 'stats') {
                require_once 'endpoints/clicks/stats.php';
                stream_click_stats(); // This function handles its own output
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            }
            break;

        // AI routes
        case 'POST ai':
            if ($action === 'generate_text') {
                require_once 'endpoints/ai/generate_text.php';
                echo json_encode(generate_text_from_ai());
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            }
            break;
        
        case 'GET ai':
            if ($action === 'analyze_clicks') {
                require_once 'helpers/db.php'; // Ensure db connection is available
                require_once 'endpoints/ai/analyze_clicks.php';
                
                // Fetch data here and pass it to the function
                $stmt = query("SELECT button_id, SUM(click_count) as click_count FROM user_button_clicks GROUP BY button_id");
                $click_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(analyze_clicks_with_ai($click_data));
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>