<?php
/**
 * Registrar vista de código
 * Registra que un usuario (logueado o anónimo) ha visto un código
 */

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener datos de la solicitud
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$codigo_id = $data['codigo_id'] ?? null;

if (empty($codigo_id)) {
    echo json_encode([
        'success' => false,
        'error' => 'ID de código requerido'
    ]);
    exit;
}

// Obtener el usuario actual (si está logueado)
$viewer_user_id = $_SESSION['user_id'] ?? null;

// Obtener session_id
$session_id = session_id();

try {
    // Registrar la vista
    $resultado = registrar_vista_codigo($codigo_id, $viewer_user_id, $session_id);
    
    if ($resultado) {
        // Obtener datos del código para retornarlos
        $collection_codigos = getCollectionCodigos();
        $codigo = $collection_codigos->findOne([
            '_id' => new MongoDB\BSON\ObjectId($codigo_id)
        ]);
        
        if ($codigo) {
            // Si el usuario está logueado, retornar el código
            echo json_encode([
                'success' => true,
                'is_logged_in' => !empty($viewer_user_id),
                'codigo' => [
                    'codigo' => $codigo['codigo'] ?? '',
                    'descripcion' => $codigo['descripcion'] ?? '',
                    'marca' => $codigo['marca'] ?? ''
                ],
                'message' => $viewer_user_id 
                    ? 'Vista registrada correctamente' 
                    : 'Regístrate para que el autor pueda ayudarte'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Código no encontrado'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error al registrar vista'
        ]);
    }
} catch (Throwable $e) {
    log_error("Error en registrar_vista_codigo.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>
