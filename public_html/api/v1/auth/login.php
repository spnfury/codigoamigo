<?php
/**
 * User Login Endpoint
 * POST /api/v1/auth/login
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
RateLimitMiddleware::check(null, 'auth');

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    ApiResponse::error('Invalid JSON', 'INVALID_JSON', 400);
}

// Validate required fields
ApiResponse::validateRequired($data, ['email', 'password']);

$email = trim($data['email']);
$password = $data['password'];

try {
    $col_usuarios = getCollectionUsuarios();
    
    // Find user by email
    $usuario = $col_usuarios->findOne(['mail' => $email]);
    
    if (!$usuario) {
        ApiResponse::error('Invalid credentials', 'INVALID_CREDENTIALS', 401);
    }
    
    // Verify password
    // Note: In production, passwords should be hashed with password_hash()
    // and verified with password_verify()
    if ($usuario['pass'] !== $password) {
        ApiResponse::error('Invalid credentials', 'INVALID_CREDENTIALS', 401);
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
    
    $user_id = (string)$usuario['_id'];
    $username = $usuario['username'] ?? 'User';
    
    // Generate tokens
    $access_token = AuthMiddleware::generateAccessToken($user_id, $email, $username);
    $refresh_token = AuthMiddleware::generateRefreshToken($user_id);
    
    $expiry = AuthMiddleware::getTokenExpiry();
    
    // Update last login (optional)
    $col_usuarios->updateOne(
        ['_id' => $usuario['_id']],
        ['$set' => ['last_login' => new \MongoDB\BSON\UTCDateTime()]]
    );
    
    ApiResponse::success([
        'user_id' => $user_id,
        'username' => $username,
        'email' => $email,
        'img' => $usuario['img'] ?? '',
        'zumbido_saldo' => $usuario['zumbido_saldo'] ?? 0,
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'expires_in' => $expiry['access_token_expiry']
    ], 'Login successful');
    
} catch (\Exception $e) {
    ApiResponse::error('Login failed: ' . $e->getMessage(), 'LOGIN_ERROR', 500);
}
