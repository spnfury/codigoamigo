<?php
/**
 * AJAX: Eliminar un código del usuario
 * Verifica sesión y propiedad, elimina de MongoDB, devuelve JSON.
 * Patrón idéntico a reactivar_codigo.php (que funciona correctamente).
 */

// Capturar cualquier output para evitar contaminar el JSON
ob_start();

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar cualquier output previo
ob_end_clean();
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
    // Incluir las funciones necesarias (mismo patrón que reactivar_codigo.php)
    require_once __DIR__ . '/../myphp/funciones.php';
    
    // Obtener conexión (mismo método que reactivar_codigo.php)
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
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para eliminar este código.']);
        exit;
    }

    // Eliminar el código
    $resultado = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);

    if ($resultado->getDeletedCount() > 0) {
        error_log("Código eliminado via AJAX: codigo_id=$codigo_id user_id=" . $_SESSION["user_id"]);
        echo json_encode([
            'success' => true, 
            'message' => 'Código eliminado correctamente.',
            'codigo_id' => $codigo_id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el código.']);
    }

} catch (Exception $e) {
    error_log("Error al eliminar código via AJAX: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno. Inténtalo de nuevo.']);
}
