<?php
// Note: The main auth helpers are included in routes.php

function logout($user_id) {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($input['refresh_token'])) {
        http_response_code(400);
        return ["error" => "Refresh token is required to log out"];
    }
    
    try {
        // Always invalidate the specific refresh token provided
        Auth::invalidateRefreshToken($input['refresh_token']);
        
        // If the user wants to log out from all devices, invalidate all their tokens
        if (!empty($input['invalidate_all'])) {
            Auth::invalidateUserTokens($user_id);
        }
        
        http_response_code(200);
        return [
            "status" => "success",
            "message" => "Logged out successfully"
        ];
    } catch (Exception $e) {
        http_response_code(400);
        // Provide a more specific error message for debugging
        return ["error" => "Logout failed: " . $e->getMessage()];
    }
}
?>