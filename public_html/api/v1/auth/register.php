<?php
/**
 * User Registration Endpoint
 * POST /api/v1/auth/register
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
ApiResponse::validateRequired($data, ['username', 'email', 'password']);

$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];
$referral_code = $data['referral_code'] ?? null;

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ApiResponse::error('Invalid email format', 'INVALID_EMAIL', 400);
}

// Validate password strength
if (strlen($password) < 6) {
    ApiResponse::error('Password must be at least 6 characters', 'WEAK_PASSWORD', 400);
}

// Check if user already exists
if (comprobar_usuario_existe($email)) {
    ApiResponse::error('Email already registered', 'EMAIL_EXISTS', 409);
}

// Create user
$user_data = [
    'nombre' => $username,
    'correo' => $email,
    'password' => $password,
    'codigo_referido' => $referral_code
];

try {
    // Use existing registration function
    $col_usuarios = getCollectionUsuarios();
    
    $insert_data = [
        'estado' => 0, // Pending verification
        'type' => 'api',
        'username' => $username,
        'mail' => $email,
        'pass' => $password, // Should be hashed in production
        'fecha_registro' => date('d-m-Y H:i'),
        'img' => '',
        'saldo' => 0,
        'zumbido_saldo' => 0,
        'created_via' => 'api'
    ];
    
    // Process referral code if provided
    if ($referral_code) {
        if (function_exists('procesarCodigoReferido')) {
            $referido_por = procesarCodigoReferido($referral_code);
            if ($referido_por) {
                $insert_data['referido_por'] = $referido_por;
                $insert_data['codigo_referido_usado'] = $referral_code;
            }
        }
    }
    
    $result = $col_usuarios->insertOne($insert_data);
    
    if (!$result->getInsertedId()) {
        ApiResponse::error('Failed to create user', 'CREATION_FAILED', 500);
    }
    
    $user_id = (string)$result->getInsertedId();
    
    // Generate tokens
    $access_token = AuthMiddleware::generateAccessToken($user_id, $email, $username);
    $refresh_token = AuthMiddleware::generateRefreshToken($user_id);
    
    $expiry = AuthMiddleware::getTokenExpiry();
    
    // Send verification email (optional, can be done asynchronously)
    // enviar_mail_activacion($user_data);
    
    ApiResponse::success([
        'user_id' => $user_id,
        'username' => $username,
        'email' => $email,
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'expires_in' => $expiry['access_token_expiry'],
        'verification_required' => true
    ], 'User registered successfully', 201);
    
} catch (\Exception $e) {
    ApiResponse::error('Registration failed: ' . $e->getMessage(), 'REGISTRATION_ERROR', 500);
}
