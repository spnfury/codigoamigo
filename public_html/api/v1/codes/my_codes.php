<?php
/**
 * My Codes Endpoint
 * GET /api/v1/codes/my_codes.php
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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ApiResponse::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Apply rate limiting
RateLimitMiddleware::check(null, 'default');

// Require authentication
$user = AuthMiddleware::requireAuth();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$status = $_GET['status'] ?? 'all';

try {
    $col_codigos = getCollectionCodigos();
    
    // Build filter for logged-in user
    $filter = [
        'id_usuario' => new \MongoDB\BSON\ObjectId($user['user_id'])
    ];
    
    if ($status === 'active') {
        $filter['estado'] = 0;
    } elseif ($status === 'inactive') {
        $filter['estado'] = ['$ne' => 0];
    }
    
    // Get total count
    $total = $col_codigos->countDocuments($filter);
    
    // Calculate skip
    $skip = ($page - 1) * $limit;
    
    // Get codes
    $cursor = $col_codigos->find(
        $filter,
        [
            'sort' => ['_id' => -1],
            'limit' => $limit,
            'skip' => $skip
        ]
    );
    
    $codes = [];
    foreach ($cursor as $code) {
        $codes[] = [
            'id' => (string)$code['_id'],
            'title' => $code['titulo'] ?? '',
            'description' => $code['descripcion'] ?? '',
            'code' => $code['codigo'] ?? '',
            'brand' => $code['marca'] ?? '',
            'category' => $code['categoria'] ?? '',
            'status' => $code['estado'] ?? 0,
            'type' => $code['tipo'] ?? 'codigo',
            'clicks' => $code['totalclicks'] ?? 0,
            'is_featured' => isset($code['destacado']) && $code['destacado'] > 0,
            'created_at' => $code['fecha_publicacion'] ?? ''
        ];
    }
    
    ApiResponse::paginated($codes, $page, $limit, $total);
    
} catch (\Exception $e) {
    ApiResponse::error('Failed to fetch codes: ' . $e->getMessage(), 'FETCH_ERROR', 500);
}
