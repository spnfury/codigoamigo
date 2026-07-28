<?php
/**
 * Codes Search/List Endpoint
 * GET /api/v1/codes/search.php?q=amazon&category=electronics&page=1&limit=20
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/conexion.php';
// Sin esto getCollectionCodigos() no existe y el endpoint devuelve 500:
// la búsqueda de la app móvil llevaba rota desde siempre (nadie la usaba
// porque la app nunca se publicó).
require_once __DIR__ . '/../../../myphp/funciones_codigo.php';
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
        // Se busca por marca, descripción y código. No se incluye 'titulo':
        // ese campo no existe en los documentos, así que buscar por él nunca
        // devolvía nada. La marca es justo por lo que busca la gente.
        $q_escapada = preg_quote($query, '/');
        $filter['$or'] = [
            ['marca' => new \MongoDB\BSON\Regex($q_escapada, 'i')],
            ['descripcion' => new \MongoDB\BSON\Regex($q_escapada, 'i')],
            ['codigo' => new \MongoDB\BSON\Regex($q_escapada, 'i')]
        ];
    }

    if ($brand) {
        $filter['marca'] = $brand;
    }

    if ($category) {
        $filter['clave_categoria'] = $category; // el campo real, no 'categoria'
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
                'descripcion' => 1,
                'codigo' => 1,
                'marca' => 1,
                'clave_categoria' => 1,
                'num_beneficio' => 1,
                'tipo_beneficio' => 1,
                'url_externa' => 1,
                'fecha_publicacion' => 1,
                'destacado' => 1,
                'destacado_social' => 1,
                'totalclicks' => 1,
                'clicks' => 1
            ]
        ]
    );
    
    // Mapeo alineado con detail.php: los documentos no tienen 'titulo',
    // 'categoria', 'descuento' ni 'tipo' — usarlos dejaba la lista de la app
    // con títulos y descuentos vacíos, que parece una app rota.
    $docs = $cursor->toArray();

    // Nombres de marca en una sola consulta: 'bbva' -> 'BBVA'. Con ucfirst
    // saldría "Bbva" en cada tarjeta de la app.
    $claves = array_values(array_unique(array_filter(array_map(
        fn($d) => $d['marca'] ?? '', $docs
    ))));
    $nombres_marca = [];
    if ($claves) {
        $col_marcas = createConnection()->selectCollection('marcas');
        foreach ($col_marcas->find(
            ['nombre_clave' => ['$in' => $claves]],
            ['projection' => ['nombre_clave' => 1, 'nombre' => 1]]
        ) as $m) {
            $nombres_marca[$m['nombre_clave']] = trim($m['nombre'] ?? '');
        }
    }

    $codes = [];
    foreach ($docs as $code) {
        $marca = $code['marca'] ?? '';
        $marca_nombre = $nombres_marca[$marca] ?? '';

        $num_beneficio  = $code['num_beneficio'] ?? null;
        $tipo_beneficio = $code['tipo_beneficio'] ?? 'euros';
        $discount = '';
        if ($num_beneficio !== null && (float)$num_beneficio > 0) {
            $unidad   = ($tipo_beneficio === 'porcentaje') ? '%' : '€';
            $cantidad = rtrim(rtrim(number_format((float)$num_beneficio, 2, ',', '.'), '0'), ',');
            $discount = $cantidad . $unidad;
        }

        $codeData = [
            'id' => (string)$code['_id'],
            'title' => $marca !== ''
                ? 'Código amigo de ' . ($marca_nombre !== '' ? $marca_nombre : ucfirst($marca))
                : 'Código amigo',
            'description' => $code['descripcion'] ?? '',
            'code' => $code['codigo'] ?? '',
            'brand' => $marca,
            'category' => $code['clave_categoria'] ?? '',
            'discount' => $discount,
            'type' => $tipo_beneficio,
            'url' => $code['url_externa'] ?? ($marca !== '' ? 'https://www.codigoamigo.com/de-' . $marca : ''),
            'clicks' => (int)($code['totalclicks'] ?? $code['clicks'] ?? 0),
            'is_featured' => (!empty($code['destacado']) && $code['destacado'] > 0)
                             || (!empty($code['destacado_social']) && $code['destacado_social'] > 0)
        ];

        $codes[] = $codeData;
    }
    
    ApiResponse::paginated($codes, $page, $limit, $total);
    
} catch (\Exception $e) {
    ApiResponse::error('Search failed: ' . $e->getMessage(), 'SEARCH_ERROR', 500);
}
