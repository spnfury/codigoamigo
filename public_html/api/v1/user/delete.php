<?php
/**
 * User Delete Account Endpoint
 * POST /api/v1/user/delete.php
 *
 * Required by Apple App Store Review Guideline 5.1.1(v):
 * apps that support account creation must let users delete their account from within the app.
 *
 * Body: { "password": "<current password>" }
 *
 * Behavior:
 *  - Verifies password for identity confirmation.
 *  - Anonymizes the user record: clears PII, sets estado = -3 (deleted by user).
 *  - Detaches user's codes (id_usuario set to null) so public content remains without PII.
 *  - Blacklists the current access token.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/conexion.php';
// getCollectionUsuarios()/getCollectionCodigos() viven aquí. Sin estos
// requires el borrado de cuenta devolvía 500 — y Google Play exige que
// funcione para aprobar la app.
require_once __DIR__ . '/../../../myphp/funciones_usuario.php';
require_once __DIR__ . '/../../../myphp/funciones_codigo.php';
require_once __DIR__ . '/../middleware/ApiResponse.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

use CodigoAmigo\API\ApiResponse;
use CodigoAmigo\API\Middleware\AuthMiddleware;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

$authUser = AuthMiddleware::requireAuth();

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['password'])) {
    ApiResponse::error('Password is required to delete your account', 'MISSING_PASSWORD', 400);
}

$password = $data['password'];

try {
    $col_usuarios = getCollectionUsuarios();
    $col_codigos = getCollectionCodigos();

    $userId = new \MongoDB\BSON\ObjectId($authUser['user_id']);
    $usuario = $col_usuarios->findOne(['_id' => $userId]);

    if (!$usuario) {
        ApiResponse::notFound('User not found');
    }

    // Verify password (plain-text comparison, matching existing login.php behavior)
    if (($usuario['pass'] ?? '') !== $password) {
        ApiResponse::error('Invalid password', 'INVALID_PASSWORD', 401);
    }

    // Anonymize user: strip PII and mark as deleted
    $col_usuarios->updateOne(
        ['_id' => $userId],
        ['$set' => [
            'estado' => -3,
            'mail' => '',
            'username' => 'deleted_user_' . substr((string)$userId, -6),
            'pass' => '',
            'img' => '',
            'desc' => '',
            'telefono' => '',
            'deleted_at' => new \MongoDB\BSON\UTCDateTime(),
        ]]
    );

    // Detach user's codes (keep public content, remove ownership link)
    $col_codigos->updateMany(
        ['id_usuario' => $userId],
        ['$set' => ['id_usuario' => null, 'owner_deleted_at' => new \MongoDB\BSON\UTCDateTime()]]
    );

    // Blacklist current token
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            AuthMiddleware::blacklistToken($matches[1]);
        }
    }

    ApiResponse::success(null, 'Account deleted successfully');

} catch (\Exception $e) {
    ApiResponse::error('Failed to delete account: ' . $e->getMessage(), 'DELETE_ERROR', 500);
}
