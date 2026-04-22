<?php
/**
 * Token Refresh Endpoint
 * POST /api/v1/auth/refresh
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/conexion.php';
require_once __DIR__ . '/../../../myphp/funciones_usuario.php';
require_once __DIR__ . '/../middleware/ApiResponse.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RateLimitMiddleware.php';

use CodigoAmigo\API\ApiResponse;
use CodigoAmigo\API\Middleware\AuthMiddleware;
use CodigoAmigo\API\Middleware\RateLimitMiddleware;

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Apply rate limiting
RateLimitMiddleware::check(null, 'default');

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    ApiResponse::error('Invalid JSON', 'INVALID_JSON', 400);
}

// Validate required fields
ApiResponse::validateRequired($data, ['refresh_token']);

$refresh_token = $data['refresh_token'];

try {
    // Validate refresh token
    $decoded = AuthMiddleware::validateToken($refresh_token, 'refresh');
    
    if (!$decoded) {
        ApiResponse::error('Invalid or expired refresh token', 'INVALID_REFRESH_TOKEN', 401);
    }
    
    $user_id = $decoded->user_id;
    
    // Get user data to generate new access token
    $col_usuarios = getCollectionUsuarios();
    $usuario = $col_usuarios->findOne(['_id' => new \MongoDB\BSON\ObjectId($user_id)]);
    
    if (!$usuario) {
        ApiResponse::error('User not found', 'USER_NOT_FOUND', 404);
    }
    
    // Check user status
    if (isset($usuario['estado'])) {
        if ($usuario['estado'] == -3) {
            ApiResponse::error('This account has been deleted', 'ACCOUNT_DELETED', 403);
        } elseif ($usuario['estado'] == -2) {
            ApiResponse::error('Account permanently banned', 'ACCOUNT_BANNED', 403);
        } elseif ($usuario['estado'] == -1) {
            ApiResponse::error('Account temporarily suspended', 'ACCOUNT_SUSPENDED', 403);
        }
    }
    
    $username = $usuario['username'] ?? 'User';
    $email = $usuario['mail'] ?? '';
    
    // Generate new access token
    $access_token = AuthMiddleware::generateAccessToken($user_id, $email, $username);
    
    $expiry = AuthMiddleware::getTokenExpiry();
    
    ApiResponse::success([
        'access_token' => $access_token,
        'expires_in' => $expiry['access_token_expiry']
    ], 'Token refreshed successfully');
    
} catch (\Exception $e) {
    ApiResponse::error('Token refresh failed: ' . $e->getMessage(), 'REFRESH_ERROR', 500);
}
