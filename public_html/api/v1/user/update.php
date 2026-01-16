<?php
/**
 * User Update Profile Endpoint
 * POST /api/v1/user/update.php
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require authentication
$user = AuthMiddleware::requireAuth();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    ApiResponse::error('Invalid JSON', 'INVALID_JSON', 400);
}

try {
    $col_usuarios = getCollectionUsuarios();
    
    $updateData = [];
    
    // Whitelist fields that can be updated
    if (isset($data['username']) && !empty(trim($data['username']))) {
        $updateData['username'] = trim($data['username']);
    }
    
    if (isset($data['email']) && !empty(trim($data['email']))) {
        // Basic email validation could go here
        $updateData['mail'] = trim($data['email']);
    }

    // Optional: add more fields like 'desc', 'img', etc.
    
    if (empty($updateData)) {
        ApiResponse::error('No data provided to update', 'MISSING_DATA', 400);
    }

    // Perform update
    $result = $col_usuarios->updateOne(
        ['_id' => new \MongoDB\BSON\ObjectId($user['user_id'])],
        ['$set' => $updateData]
    );
    
    if ($result->getModifiedCount() === 0 && $result->getMatchedCount() === 0) {
        ApiResponse::error('User not found or no changes made', 'UPDATE_FAILED', 404);
    }
    
    ApiResponse::success(null, 'Profile updated successfully');
    
} catch (\Exception $e) {
    ApiResponse::error('Failed to update profile: ' . $e->getMessage(), 'UPDATE_ERROR', 500);
}
