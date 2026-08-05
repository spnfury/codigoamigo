<?php
/**
 * Rate Limiting Middleware
 * Prevents API abuse by limiting requests per user/IP
 */

namespace CodigoAmigo\API\Middleware;

use CodigoAmigo\API\ApiResponse;

class RateLimitMiddleware {
    
    // Rate limit configurations
    private static $limits = [
        'default' => ['requests' => 1000, 'window' => 3600], // 1000 req/hour
        'anonymous' => ['requests' => 100, 'window' => 3600], // 100 req/hour
        'auth' => ['requests' => 10, 'window' => 3600], // 10 req/hour for auth endpoints
        'create_code' => ['requests' => 5, 'window' => 3600], // 5 códigos/hora por usuario (anti-spam)
    ];
    
    /**
     * Check rate limit for current request
     */
    public static function check($identifier = null, $limitType = 'default') {
        // If no identifier provided, use IP address
        if ($identifier === null) {
            $identifier = self::getClientIp();
            $limitType = 'anonymous';
        }
        
        $limit = self::$limits[$limitType] ?? self::$limits['default'];
        $key = self::getRateLimitKey($identifier, $limitType);
        
        $current = self::getCurrentCount($key);
        
        if ($current >= $limit['requests']) {
            $resetTime = self::getResetTime($key, $limit['window']);
            ApiResponse::rateLimitExceeded(
                "Rate limit exceeded. Try again in " . ceil(($resetTime - time()) / 60) . " minutes."
            );
        }
        
        self::incrementCount($key, $limit['window']);
    }
    
    /**
     * Get rate limit key for storage
     */
    private static function getRateLimitKey($identifier, $type) {
        return 'rate_limit:' . $type . ':' . md5($identifier);
    }
    
    /**
     * Get current request count
     */
    private static function getCurrentCount($key) {
        if (function_exists('createConnection')) {
            try {
                $db = createConnection();
                $col = $db->selectCollection('api_rate_limits');
                
                $record = $col->findOne([
                    'key' => $key,
                    'expires_at' => ['$gt' => new \MongoDB\BSON\UTCDateTime()]
                ]);
                
                return $record ? ($record['count'] ?? 0) : 0;
            } catch (\Exception $e) {
                // If rate limiting fails, allow the request
                return 0;
            }
        }
        
        return 0;
    }
    
    /**
     * Increment request count
     */
    private static function incrementCount($key, $window) {
        if (function_exists('createConnection')) {
            try {
                $db = createConnection();
                $col = $db->selectCollection('api_rate_limits');
                
                $expiresAt = new \MongoDB\BSON\UTCDateTime((time() + $window) * 1000);
                
                // Try to increment existing record
                $result = $col->updateOne(
                    [
                        'key' => $key,
                        'expires_at' => ['$gt' => new \MongoDB\BSON\UTCDateTime()]
                    ],
                    [
                        '$inc' => ['count' => 1],
                        '$set' => ['last_request' => new \MongoDB\BSON\UTCDateTime()]
                    ]
                );
                
                // If no record found, create new one
                if ($result->getModifiedCount() === 0) {
                    $col->insertOne([
                        'key' => $key,
                        'count' => 1,
                        'created_at' => new \MongoDB\BSON\UTCDateTime(),
                        'last_request' => new \MongoDB\BSON\UTCDateTime(),
                        'expires_at' => $expiresAt
                    ]);
                }
            } catch (\Exception $e) {
                // Silent fail - don't block requests if rate limiting has issues
            }
        }
    }
    
    /**
     * Get reset time for rate limit
     */
    private static function getResetTime($key, $window) {
        if (function_exists('createConnection')) {
            try {
                $db = createConnection();
                $col = $db->selectCollection('api_rate_limits');
                
                $record = $col->findOne([
                    'key' => $key,
                    'expires_at' => ['$gt' => new \MongoDB\BSON\UTCDateTime()]
                ]);
                
                if ($record && isset($record['expires_at'])) {
                    return $record['expires_at']->toDateTime()->getTimestamp();
                }
            } catch (\Exception $e) {
                // Return default
            }
        }
        
        return time() + $window;
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    /**
     * Clean expired rate limit records (should be run periodically)
     */
    public static function cleanup() {
        if (function_exists('createConnection')) {
            try {
                $db = createConnection();
                $col = $db->selectCollection('api_rate_limits');
                
                $col->deleteMany([
                    'expires_at' => ['$lt' => new \MongoDB\BSON\UTCDateTime()]
                ]);
            } catch (\Exception $e) {
                // Silent fail
            }
        }
    }
}
