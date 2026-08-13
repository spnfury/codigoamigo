<?php
/**
 * Create Code Endpoint
 * POST /api/v1/codes/create.php
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/conexion.php';
require_once __DIR__ . '/../../../myphp/funciones_codigo.php';
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

// Require authentication
$user = AuthMiddleware::requireAuth();

// Rate limit por usuario: máx 5 códigos/hora (anti-spam)
RateLimitMiddleware::check($user['user_id'], 'create_code');

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    ApiResponse::error('Invalid JSON', 'INVALID_JSON', 400);
}

// Validate required fields
ApiResponse::validateRequired($data, ['title', 'code', 'brand']);

$title = trim($data['title']);
$code = trim($data['code']);
$brand = trim($data['brand']);
$description = trim($data['description'] ?? '');
$category = trim($data['category'] ?? '');

try {
    $col_codigos = getCollectionCodigos();
    
    // 1 código activo por marca por usuario. Filtra por estado activo para
    // permitir re-publicar tras eliminar el anterior (estado -2).
    $existing = $col_codigos->findOne([
        'id_usuario' => new \MongoDB\BSON\ObjectId($user['user_id']),
        'marca' => $brand,
        'estado' => ['$in' => [0, -1, 1]]
    ]);
    if ($existing) {
        ApiResponse::error('You already have an active code for this brand', 'CODE_EXISTS', 409);
    }
    
    $insertData = [
        'titulo' => $title,
        'codigo' => $code,
        'marca' => $brand,
        'descripcion' => $description,
        'categoria' => $category,
        'id_usuario' => new \MongoDB\BSON\ObjectId($user['user_id']),
        'estado' => 0, // 0 = active
        'fecha_publicacion' => date('Y-m-d H:i:s'),
        'created_at' => new \MongoDB\BSON\UTCDateTime(),
        'updated_at' => new \MongoDB\BSON\UTCDateTime(),
        'clicks' => 0,
        'votos_positivos' => 0,
        'votos_negativos' => 0,
        'tipo' => 'codigo',
        'destacado' => 0
    ];
    
    $result = $col_codigos->insertOne($insertData);
    
    if (!$result->getInsertedId()) {
        ApiResponse::error('Failed to create code', 'CREATION_FAILED', 500);
    }
    
    ApiResponse::success([
        'id' => (string)$result->getInsertedId(),
        'title' => $title,
        'code' => $code,
        'brand' => $brand,
        'status' => 'active'
    ], 'Code created successfully', 201);
    
} catch (\Exception $e) {
    ApiResponse::error('Code creation failed: ' . $e->getMessage(), 'CREATION_ERROR', 500);
}
