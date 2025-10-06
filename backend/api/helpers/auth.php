<?php
/**
 * Authentication helper
 * Handles JWT generation and validation
 */
class Auth {
    private static function getConfig() {
        static $config = null;
        if ($config === null) {
            $config = require __DIR__ . '/../config/auth_config.php';
        }
        return $config;
    }
    
    /**
     * Convert base64 to base64url encoding
     */
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Convert base64url to base64 encoding
     */
    private static function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Generate JWT token for user
     */
    public static function generateToken($user_id) {
        $config = self::getConfig();
        $header = self::base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $time = time();
        $payload = self::base64url_encode(json_encode([
            'user_id' => $user_id,
            'iat' => $time,
            'exp' => $time + $config['token_expiration']
        ]));
        
        $signature = self::base64url_encode(
            hash_hmac('sha256', "$header.$payload", $config['secret_key'], true)
        );
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Generate refresh token for user
     */
    public static function generateRefreshToken($user_id) {
        require_once __DIR__ . '/db.php';
        
        $config = self::getConfig();
        $header = self::base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $time = time();
        $expiration = $time + $config['refresh_token_expiration'];
        
        $payload = self::base64url_encode(json_encode([
            'user_id' => $user_id,
            'is_refresh_token' => true,
            'iat' => $time,
            'exp' => $expiration
        ]));
        
        $signature = self::base64url_encode(
            hash_hmac('sha256', "$header.$payload", $config['secret_key'], true)
        );
        
        $token = "$header.$payload.$signature";

        try {
            query(
                "INSERT INTO refresh_tokens (user_id, token, expiration) VALUES (?, ?, ?)",
                [$user_id, $token, $expiration]
            );
            
            return $token;
        } catch (PDOException $e) {
            error_log("Error saving refresh token: " . $e->getMessage());
            throw new Exception('Error generating refresh token');
        }
    }
    
    /**
     * Verify JWT token
     */
public static function verifyToken($token) {
    require_once __DIR__ . '/db.php';
    $config = self::getConfig();
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    $valid_signature = self::base64url_encode(
        hash_hmac('sha256', "$header.$payload", $config['secret_key'], true)
    );
    
    if ($signature !== $valid_signature) {
        return false;
    }
    
    $payload_data = json_decode(self::base64url_decode($payload), true);
    if (!$payload_data || $payload_data['exp'] < time()) {
        return false;
    }
    
    // Check if the user has invalidated tokens
    $invalidation = query(
        "SELECT invalidated_at FROM user_token_invalidations WHERE user_id = ?",
        [$payload_data['user_id']]
    )->fetch(PDO::FETCH_ASSOC);
    
    if ($invalidation && strtotime($invalidation['invalidated_at']) > $payload_data['iat']) {
        return false; // Token was issued before the invalidation timestamp
    }
    
    return $payload_data;
}


public static function invalidateUserTokens($user_id) {
    // Use PHP's time() to ensure consistency with JWT's 'iat' timestamp, formatted for SQL
    $utc_timestamp = gmdate("Y-m-d H:i:s");

    $stmt = query(
        "INSERT INTO user_token_invalidations (user_id, invalidated_at) VALUES (?, ?) 
         ON DUPLICATE KEY UPDATE invalidated_at = ?",
        [$user_id, $utc_timestamp, $utc_timestamp]
    );
    
    return $stmt->rowCount() > 0;
}


    /**
     * Refresh access token using refresh token
     */
    public static function refreshAccessToken($refresh_token) {
        require_once __DIR__ . '/db.php';
        $config = self::getConfig();
        
        // Verify the JWT token
        $payload = self::verifyToken($refresh_token);
        if (!$payload) {
            return false;
        }
        
        // Verify that the token is a refresh token
        if (!isset($payload['is_refresh_token']) || !$payload['is_refresh_token']) {
            return false;
        }
    
        try {
            // Verify that the token is revoked in the database
            $result = query(
                "SELECT is_revoked FROM refresh_tokens WHERE token = ?",
                [$refresh_token]
            )->fetch();
    
            if (!$result || $result['is_revoked']) {
                return false;
            }
            
            // Revoke the current token
            query(
                "UPDATE refresh_tokens SET is_revoked = 1 WHERE token = ?",
                [$refresh_token]
            );
            
            // Generate new access token and refresh token
            $new_access_token = self::generateToken($payload['user_id']);
            $new_refresh_token = self::generateRefreshToken($payload['user_id']);
            
            return [
                'access_token' => $new_access_token,
                'refresh_token' => $new_refresh_token
            ];
        } catch (PDOException $e) {
            error_log("Error verifying refresh token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Middleware to protect routes
     */
    public static function requireAuth() {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
            http_response_code(401);
            echo json_encode(["error" => "No authorization token provided"]);
            exit;
        }
        
        $token = $matches[1];
        $payload = self::verifyToken($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(["error" => "Invalid or expired token"]);
            exit;
        }
        
        return $payload['user_id'];
    }

    /**
     * Invalidate refresh token
     */
    public static function invalidateRefreshToken($refresh_token) {
        require_once __DIR__ . '/db.php';
        
        try {
            // First, we check if the token exists in the database
            $checkToken = query(
                "SELECT COUNT(*) as count, is_revoked FROM refresh_tokens WHERE token = ? GROUP BY is_revoked",
                [$refresh_token]
            )->fetch();

            if ($checkToken === false) {
                throw new Exception('Refresh token not found');
            }

            if ($checkToken['is_revoked']) {
                throw new Exception('Refresh token already invalidated');
            }

            // Update the token as revoked regardless of expiration
            $result = query(
                "UPDATE refresh_tokens SET is_revoked = 1 WHERE token = ? AND is_revoked = 0",
                [$refresh_token]
            );

            if ($result->rowCount() === 0) {
                throw new Exception('Refresh token not found or already invalidated');
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error invalidating refresh token: " . $e->getMessage());
            throw new Exception('Error invalidating refresh token');
        }
    }
}

/**
 * Get user data from JWT token in Authorization header
 */
function get_user_from_token() {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
        return null;
    }
    
    $token = $matches[1];
    $payload = Auth::verifyToken($token);
    
    if (!$payload) {
        return null;
    }
    
    return $payload;
}

?>