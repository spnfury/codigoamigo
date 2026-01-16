<?php
/**
 * API Test Endpoint
 * GET /api/v1/test.php
 * 
 * Simple endpoint to verify API is working
 */

require_once __DIR__ . '/middleware/ApiResponse.php';

use CodigoAmigo\API\ApiResponse;

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ApiResponse::success([
    'message' => 'CodigoAmigo API v1.0 is running',
    'timestamp' => date('c'),
    'endpoints' => [
        'auth' => [
            'register' => '/api/v1/auth/register.php',
            'login' => '/api/v1/auth/login.php',
            'refresh' => '/api/v1/auth/refresh.php',
            'logout' => '/api/v1/auth/logout.php'
        ],
        'codes' => [
            'list' => '/api/v1/codes/',
            'search' => '/api/v1/codes/search.php'
        ],
        'user' => [
            'profile' => '/api/v1/user/profile.php',
            'stats' => '/api/v1/user/stats.php'
        ]
    ]
]);
