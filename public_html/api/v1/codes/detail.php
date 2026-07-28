<?php
/**
 * Code Detail Endpoint
 * GET /api/v1/codes/detail.php?id=507f1f77bcf86cd799439011
 *
 * La app móvil llamaba a /codes/{id}.php, que nunca ha existido: la pantalla
 * de detalle devolvía 404 en producción. Este endpoint la sustituye y
 * devuelve la misma forma que search.php (interface Code de la app).
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/conexion.php';
// getCollectionCodigos() vive aquí, no en conexion.php. Los demás endpoints
// de codes/ omiten este require y por eso devuelven 500 en producción.
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

$id = trim($_GET['id'] ?? '');

if ($id === '') {
    ApiResponse::error('Missing required parameter: id', 'MISSING_ID', 400);
}

// Validar antes de construir el ObjectId: con un id malformado el
// constructor lanza y acabaríamos devolviendo un 500 por una entrada inválida.
if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
    ApiResponse::error('Invalid code id', 'INVALID_ID', 400);
}

try {
    $col_codigos = getCollectionCodigos();

    $code = $col_codigos->findOne([
        '_id'    => new \MongoDB\BSON\ObjectId($id),
        'estado' => ['$in' => [0, 1]], // solo códigos activos
    ]);

    if (!$code) {
        ApiResponse::notFound('Code not found');
    }

    // Beneficio legible: los documentos guardan la cantidad y el tipo por
    // separado (num_beneficio + tipo_beneficio), no un campo "descuento".
    $num_beneficio  = $code['num_beneficio'] ?? null;
    $tipo_beneficio = $code['tipo_beneficio'] ?? 'euros';
    $discount = '';
    if ($num_beneficio !== null && (float)$num_beneficio > 0) {
        $unidad   = ($tipo_beneficio === 'porcentaje') ? '%' : '€';
        $cantidad = rtrim(rtrim(number_format((float)$num_beneficio, 2, ',', '.'), '0'), ',');
        $discount = $cantidad . $unidad;
    }

    $marca = $code['marca'] ?? '';

    // Nombre real de la marca ('bbva' -> 'BBVA'); con ucfirst saldría "Bbva".
    $marca_nombre = '';
    if ($marca !== '') {
        $m = createConnection()->selectCollection('marcas')->findOne(
            ['nombre_clave' => $marca],
            ['projection' => ['nombre' => 1]]
        );
        $marca_nombre = trim($m['nombre'] ?? '');
    }

    $codeData = [
        'id'          => (string)$code['_id'],
        // No existe campo "titulo" en los documentos: se compone a partir de
        // la marca para que la pantalla de detalle no quede con el título vacío.
        'title'       => $marca !== ''
            ? 'Código amigo de ' . ($marca_nombre !== '' ? $marca_nombre : ucfirst($marca))
            : 'Código amigo',
        'description' => $code['descripcion'] ?? '',
        'code'        => $code['codigo'] ?? '',
        'brand'       => $marca,
        'category'    => $code['clave_categoria'] ?? '',
        'discount'    => $discount,
        'type'        => $tipo_beneficio,
        'url'         => $code['url_externa'] ?? ($marca !== '' ? 'https://www.codigoamigo.com/de-' . $marca : ''),
        'clicks'      => (int)($code['totalclicks'] ?? $code['clicks'] ?? 0),
        'is_featured' => (!empty($code['destacado']) && $code['destacado'] > 0)
                         || (!empty($code['destacado_social']) && $code['destacado_social'] > 0),
    ];

    ApiResponse::success($codeData);

} catch (\Exception $e) {
    ApiResponse::error('Failed to fetch code: ' . $e->getMessage(), 'DETAIL_ERROR', 500);
}
