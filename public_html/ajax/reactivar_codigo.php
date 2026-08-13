<?php
include_once __DIR__ . '/../inc/logger.php';
/**
 * AJAX: Reactivar un código caducado/desactivado
 * Cambia el estado del código a 0 (activo) y actualiza la fecha de publicación
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'error' => 'No has iniciado sesión.']);
    exit;
}

// Verificar que se envió el ID del código
if (!isset($_POST['codigo_id']) || empty($_POST['codigo_id'])) {
    echo json_encode(['success' => false, 'error' => 'Falta el ID del código.']);
    exit;
}

$codigo_id = $_POST['codigo_id'];

try {
    // Incluir las funciones necesarias
    require_once __DIR__ . '/../myphp/funciones.php';
    
    // Obtener conexión
    $db = createConnection();
    $collection = $db->selectCollection('codigos');

    // Obtener el código
    $codigo = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
    
    if (!$codigo) {
        echo json_encode(['success' => false, 'error' => 'Código no encontrado.']);
        exit;
    }
    
    // Verificar que el código pertenece al usuario logueado
    $codigo_user_id = (string)($codigo['id_usuario'] ?? '');
    if ($codigo_user_id !== $_SESSION["user_id"]) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para modificar este código.']);
        exit;
    }
    
    // Verificar que el código no está ya activo
    $estado_actual = isset($codigo['estado']) ? (int)$codigo['estado'] : 0;
    if ($estado_actual === 0) {
        echo json_encode(['success' => false, 'error' => 'Este código ya está activo.']);
        exit;
    }
    
    // Reactivar: cambiar estado a 0 y actualizar fecha de publicación usando UTCDateTime
    $ahora = new MongoDB\BSON\UTCDateTime(time() * 1000);
    $resultado = $collection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
        ['$set' => [
            'estado' => 0,
            'fecha_publicacion' => $ahora,
            'fecha_reactivacion' => $ahora,
            'reactivado_por' => 'usuario'
        ]]
    );
    
    if ($resultado->getModifiedCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Código reactivado correctamente.',
            'codigo_id' => $codigo_id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo actualizar el código.']);
    }
    
} catch (Exception $e) {
    log_error("Error al reactivar código: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno. Inténtalo de nuevo.']);
}
