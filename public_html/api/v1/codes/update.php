<?php
/**
 * Update Code Endpoint
 * PUT /api/v1/codes/update.php
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
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Accept PUT (or POST if client doesn't support PUT well)
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Apply rate limiting
RateLimitMiddleware::check(null, 'default');

// Require authentication
$user = AuthMiddleware::requireAuth();

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    ApiResponse::error('Invalid JSON', 'INVALID_JSON', 400);
}

// Validate required fields
ApiResponse::validateRequired($data, ['id']);
$codeId = trim($data['id']);

try {
    $col_codigos = getCollectionCodigos();
    
    // Verify ownership
    $objectId = new \MongoDB\BSON\ObjectId($codeId);
    $existing = $col_codigos->findOne(['_id' => $objectId]);
    
    if (!$existing) {
        ApiResponse::error('Code not found', 'NOT_FOUND', 404);
    }
    
    if ((string)$existing['id_usuario'] !== (string)$user['user_id']) {
        ApiResponse::error('Unauthorized to edit this code', 'FORBIDDEN', 403);
    }
    
    // Prepare update fields
    $updateFields = [];
    if (isset($data['title'])) $updateFields['titulo'] = trim($data['title']);
    if (isset($data['code'])) $updateFields['codigo'] = trim($data['code']);
    if (isset($data['description'])) $updateFields['descripcion'] = trim($data['description']);
    
    $updateFields['updated_at'] = new \MongoDB\BSON\UTCDateTime();
    
    if (empty($updateFields)) {
        ApiResponse::error('No fields to update', 'BAD_REQUEST', 400);
    }
    
    $result = $col_codigos->updateOne(
        ['_id' => $objectId],
        ['$set' => $updateFields]
    );
    
    ApiResponse::success([
        'id' => $codeId,
        'updated' => $result->getModifiedCount() > 0
    ], 'Code updated successfully');
    
} catch (\Exception $e) {
    ApiResponse::error('Update failed: ' . $e->getMessage(), 'UPDATE_ERROR', 500);
}
