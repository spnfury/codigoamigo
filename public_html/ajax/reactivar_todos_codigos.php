<?php
/**
 * AJAX: Reactivar TODOS los códigos caducados del usuario
 * Cambia el estado a 0 (activo) y actualiza la fecha de publicación
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

try {
    // Incluir funciones necesarias
    require_once __DIR__ . '/../myphp/funciones.php';
    
    // Conectar a MongoDB
    $db = createConnection();
    $collection = $db->selectCollection('codigos');
    
    $user_id = $_SESSION["user_id"];
    
    // Condición: códigos del usuario con estado negativo (inactivos/caducados)
    // -3: Caducado, -2: Desactivado por usuario, -1: Inactivo
    $filtro = [
        'id_usuario' => ['$in' => [
            $user_id, 
            (string)$user_id, 
            (int)$user_id, 
            new MongoDB\BSON\ObjectId($user_id)
        ]],
        'estado' => ['$lt' => 0]
    ];
    
    // UTCDateTime para ordenar correctamente en Mongo
    $ahora = new MongoDB\BSON\UTCDateTime(time() * 1000);
    
    // Realizar actualización masiva
    $resultado = $collection->updateMany(
        $filtro,
        ['$set' => [
            'estado' => 0,
            'fecha_publicacion' => $ahora,
            'fecha_reactivacion' => $ahora,
            'reactivado_por' => 'usuario_masivo'
        ]]
    );
    
    $reactivados = $resultado->getModifiedCount();
    
    if ($reactivados > 0) {
        echo json_encode([
            'success' => true, 
            'reactivados' => $reactivados,
            'message' => "Se reactivaron $reactivados códigos correctamente."
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'No se encontraron códigos caducados para reactivar.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error masivo reactivar códigos: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de conexión interno.']);
}
