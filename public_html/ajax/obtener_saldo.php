<?php
session_start();
header('Content-Type: application/json');

// Log para debug
log_info("AJAX obtener_saldo - Session ID: " . session_id());
log_info("AJAX obtener_saldo - User ID: " . ($_SESSION["user_id"] ?? 'NO SET'));

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    log_warning("AJAX obtener_saldo - Usuario no autenticado");
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';

try {
    // Obtener el saldo del usuario
    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
    
    if ($usuario) {
        $saldo = $usuario['saldo'] ?? 0;
        log_info("AJAX obtener_saldo - Saldo encontrado: " . $saldo);
        echo json_encode(['saldo' => $saldo]);
    } else {
        log_error("AJAX obtener_saldo - Usuario no encontrado en BD");
        echo json_encode(['error' => 'Usuario no encontrado']);
    }
} catch (Exception $e) {
    log_error("Error obteniendo saldo: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
