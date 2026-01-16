<?php
/**
 * Codes Search/List Endpoint
 * GET /api/v1/codes/search.php?q=amazon&category=electronics&page=1&limit=20
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/conexion.php';
require_once __DIR__ . '/../middleware/ApiResponse.php';
require_once __DIR__ . '/../middleware/RateLimitMiddleware.php';

use CodigoAmigo\API\ApiResponse;
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

// Get query parameters
$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));

try {
    $col_codigos = getCollectionCodigos();
    
    // Build filter
    $filter = ['estado' => 0]; // Only active codes
    
    if ($query) {
        // Search in title and description
        $filter['$or'] = [
            ['titulo' => new \MongoDB\BSON\Regex($query, 'i')],
            ['descripcion' => new \MongoDB\BSON\Regex($query, 'i')],
            ['codigo' => new \MongoDB\BSON\Regex($query, 'i')]
        ];
    }
    
    if ($brand) {
        $filter['marca'] = $brand;
    }
    
    if ($category) {
        $filter['categoria'] = $category;
    }
    
    // Get total count
    $total = $col_codigos->countDocuments($filter);
    
    // Calculate skip
    $skip = ($page - 1) * $limit;
    
    // Get codes
    $cursor = $col_codigos->find(
        $filter,
        [
            'sort' => ['destacado' => -1, '_id' => -1],
            'limit' => $limit,
            'skip' => $skip,
            'projection' => [
                'titulo' => 1,
                'descripcion' => 1,
                'codigo' => 1,
                'marca' => 1,
                'categoria' => 1,
                'descuento' => 1,
                'tipo' => 1,
                'url_externa' => 1,
                'fecha_publicacion' => 1,
                'destacado' => 1,
                'totalclicks' => 1
            ]
        ]
    );
    
    $codes = [];
    foreach ($cursor as $code) {
        $codeData = [
            'id' => (string)$code['_id'],
            'title' => $code['titulo'] ?? '',
            'description' => $code['descripcion'] ?? '',
            'code' => $code['codigo'] ?? '',
            'brand' => $code['marca'] ?? '',
            'category' => $code['categoria'] ?? '',
            'discount' => $code['descuento'] ?? '',
            'type' => $code['tipo'] ?? 'codigo',
            'url' => $code['url_externa'] ?? '',
            'clicks' => $code['totalclicks'] ?? 0,
            'is_featured' => isset($code['destacado']) && $code['destacado'] > 0
        ];
        
        $codes[] = $codeData;
    }
    
    ApiResponse::paginated($codes, $page, $limit, $total);
    
} catch (\Exception $e) {
    ApiResponse::error('Search failed: ' . $e->getMessage(), 'SEARCH_ERROR', 500);
}
