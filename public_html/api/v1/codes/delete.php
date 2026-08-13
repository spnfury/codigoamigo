<?php
/**
 * Delete Code Endpoint
 * DELETE /api/v1/codes/delete.php
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
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Accept DELETE (or POST)
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Apply rate limiting
RateLimitMiddleware::check(null, 'default');

// Require authentication
$user = AuthMiddleware::requireAuth();

// Get request data depending on method
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Usually DELETE body is not recommended, handle query param primarily
    $codeId = $_GET['id'] ?? null;
    
    // Fallback to JSON body if no query param
    if (!$codeId) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $codeId = $data['id'] ?? null;
    }
} else {
    // For POST method disguised as DELETE
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $codeId = $data['id'] ?? null;
}

if (!$codeId) {
    ApiResponse::error('Missing code id', 'INVALID_REQUEST', 400);
}

try {
    $col_codigos = getCollectionCodigos();
    
    // Verify ownership
    $objectId = new \MongoDB\BSON\ObjectId($codeId);
    $existing = $col_codigos->findOne(['_id' => $objectId]);
    
    if (!$existing) {
        ApiResponse::error('Code not found', 'NOT_FOUND', 404);
    }
    
    if ((string)$existing['id_usuario'] !== (string)$user['user_id']) {
        ApiResponse::error('Unauthorized to delete this code', 'FORBIDDEN', 403);
    }
    
    // Delete the document
    // Optionally: just mark status = -2 (soft delete), typical for this app
    // $result = $col_codigos->updateOne(['_id' => $objectId], ['$set' => ['estado' => -2]]);
    $result = $col_codigos->deleteOne(['_id' => $objectId]);
    
    ApiResponse::success([
        'id' => $codeId,
        'deleted' => $result->getDeletedCount() > 0
    ], 'Code deleted successfully');
    
} catch (\Exception $e) {
    ApiResponse::error('Deletion failed: ' . $e->getMessage(), 'DELETE_ERROR', 500);
}
