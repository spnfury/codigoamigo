<?php
/**
 * User Profile Endpoint
 * GET /api/v1/user/profile.php
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/conexion.php';
require_once __DIR__ . '/../../../myphp/funciones_usuario.php';
require_once __DIR__ . '/../middleware/ApiResponse.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

use CodigoAmigo\API\ApiResponse;
use CodigoAmigo\API\Middleware\AuthMiddleware;

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require authentication
$user = AuthMiddleware::requireAuth();

// Only accept GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ApiResponse::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

try {
    $col_usuarios = getCollectionUsuarios();
    $col_codigos = getCollectionCodigos();
    
    // Get user data
    $usuario = $col_usuarios->findOne(['_id' => new \MongoDB\BSON\ObjectId($user['user_id'])]);
    
    if (!$usuario) {
        ApiResponse::notFound('User not found');
    }
    
    // Count user's codes
    $totalCodes = $col_codigos->countDocuments([
        'id_usuario' => $usuario['_id'],
        'estado' => 0
    ]);
    
    // Get total clicks
    $pipeline = [
        ['$match' => [
            'id_usuario' => $usuario['_id'],
            'estado' => 0
        ]],
        ['$group' => [
            '_id' => null,
            'totalClicks' => ['$sum' => '$totalclicks']
        ]]
    ];
    
    $clicksResult = $col_codigos->aggregate($pipeline)->toArray();
    $totalClicks = $clicksResult[0]['totalClicks'] ?? 0;
    
    $profile = [
        'user_id' => (string)$usuario['_id'],
        'username' => $usuario['username'] ?? '',
        'email' => $usuario['mail'] ?? '',
        'img' => $usuario['img'] ?? '',
        'zumbido_saldo' => $usuario['zumbido_saldo'] ?? 0,
        'estado' => $usuario['estado'] ?? 0,
        'fecha_registro' => $usuario['fecha_registro'] ?? '',
        'stats' => [
            'total_codes' => $totalCodes,
            'total_clicks' => $totalClicks
        ]
    ];
    
    ApiResponse::success($profile);
    
} catch (\Exception $e) {
    ApiResponse::error('Failed to get profile: ' . $e->getMessage(), 'PROFILE_ERROR', 500);
}
