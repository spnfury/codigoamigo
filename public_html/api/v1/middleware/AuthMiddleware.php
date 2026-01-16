<?php
/**
 * JWT Authentication Middleware
 * Handles token generation, validation, and user context
 */

namespace CodigoAmigo\API\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use CodigoAmigo\API\ApiResponse;

class AuthMiddleware {
    
    private static $secretKey = null;
    private static $accessTokenExpiry = 3600; // 1 hour
    private static $refreshTokenExpiry = 2592000; // 30 days
    
    /**
     * Get JWT secret key from environment or config
     */
    private static function getSecretKey() {
        if (self::$secretKey === null) {
            // Try environment variable first
            self::$secretKey = getenv('JWT_SECRET');
            
            // Fallback to a config file or generate one
            if (!self::$secretKey) {
                // In production, this should be in environment variables
                self::$secretKey = 'codigoamigo_jwt_secret_2026_change_in_production';
            }
        }
        
        return self::$secretKey;
    }
    
    /**
     * Generate access token
     */
    public static function generateAccessToken($userId, $email, $username) {
        $issuedAt = time();
        $expire = $issuedAt + self::$accessTokenExpiry;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'iss' => 'codigoamigo.com',
            'user_id' => (string)$userId,
            'email' => $email,
            'username' => $username,
            'type' => 'access'
        ];
        
        return JWT::encode($payload, self::getSecretKey(), 'HS256');
    }
    
    /**
     * Generate refresh token
     */
    public static function generateRefreshToken($userId) {
        $issuedAt = time();
        $expire = $issuedAt + self::$refreshTokenExpiry;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'iss' => 'codigoamigo.com',
            'user_id' => (string)$userId,
            'type' => 'refresh',
            'jti' => bin2hex(random_bytes(16)) // Unique token ID
        ];
        
        return JWT::encode($payload, self::getSecretKey(), 'HS256');
    }
    
    /**
     * Validate and decode token
     */
    public static function validateToken($token, $expectedType = 'access') {
        try {
            // JWT v6 syntax
            $decoded = JWT::decode($token, new Key(self::getSecretKey(), 'HS256'));
            
            // Verify token type
            if (isset($decoded->type) && $decoded->type !== $expectedType) {
                return null;
            }
            
            // Check if token is blacklisted (for logout functionality)
            if (self::isTokenBlacklisted($token)) {
                return null;
            }
            
            return $decoded;
        } catch (\Exception $e) {
            // Log error for debugging (optional)
            // error_log("JWT validation error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Require authentication for the current request
     */
    public static function requireAuth() {
        $token = self::getBearerToken();
        
        if (!$token) {
            ApiResponse::unauthorized('Missing authentication token');
        }
        
        $decoded = self::validateToken($token);
        
        if (!$decoded) {
            ApiResponse::unauthorized('Invalid or expired token');
        }
        
        // Return user context
        return [
            'user_id' => $decoded->user_id,
            'email' => $decoded->email ?? null,
            'username' => $decoded->username ?? null
        ];
    }
    
    /**
     * Get bearer token from Authorization header
     */
    private static function getBearerToken() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Check if token is blacklisted
     */
    private static function isTokenBlacklisted($token) {
        // For now, return false. In production, check against MongoDB collection
        // $col = getCollectionTokenBlacklist();
        // return $col->findOne(['token' => $token]) !== null;
        return false;
    }
    
    /**
     * Blacklist a token (for logout)
     */
    public static function blacklistToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(self::getSecretKey(), 'HS256'));
            
            if (function_exists('createConnection')) {
                $db = createConnection();
                $col = $db->selectCollection('token_blacklist');
                
                $col->insertOne([
                    'token' => $token,
                    'user_id' => $decoded->user_id,
                    'blacklisted_at' => new \MongoDB\BSON\UTCDateTime(),
                    'expires_at' => new \MongoDB\BSON\UTCDateTime($decoded->exp * 1000)
                ]);
                
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
        
        return false;
    }
    
    /**
     * Get token expiry information
     */
    public static function getTokenExpiry() {
        return [
            'access_token_expiry' => self::$accessTokenExpiry,
            'refresh_token_expiry' => self::$refreshTokenExpiry
        ];
    }
}
