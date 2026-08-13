<?php
include_once __DIR__ . '/../inc/logger.php';
/**
 * AJAX endpoint: Obtener un código aleatorio ponderado para una marca.
 * 
 * POST /ajax/obtener_codigo.php
 * Params: marca (nombre_clave), exclude_id (opcional, para "prueba otro")
 * Returns: JSON con el código seleccionado y datos del usuario
 */

header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión para acceso a user_id (necesario para registrar leads)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir dependencias
require_once __DIR__ . '/../inc/conexion.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_codigo.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_trust_score.php';

try {
    $marca = $_POST['marca'] ?? $_GET['marca'] ?? '';
    $exclude_id = $_POST['exclude_id'] ?? $_GET['exclude_id'] ?? null;
    
    if (empty($marca)) {
        echo json_encode([
            'success' => false,
            'message' => 'Parámetro "marca" requerido'
        ]);
        exit;
    }
    
    // Sanitizar
    $marca = preg_replace('/[^a-zA-Z0-9\-_]/', '', $marca);
    
    $resultado = selectWeightedRandomCode($marca, $exclude_id);
    
    if (!$resultado) {
        echo json_encode([
            'success' => false,
            'message' => 'No hay códigos disponibles para esta marca',
            'total_codigos' => 0
        ]);
        exit;
    }
    
    // Registrar vista/lead (email + notificación in-app al dueño del código)
    // Usa la misma lógica que code-viewer-modal.js → registrar_vista_codigo.php
    if (!empty($resultado['codigo_id'])) {
        try {
            $viewer_user_id = $_SESSION['user_id'] ?? null;
            $session_id = session_id();
            registrar_vista_codigo($resultado['codigo_id'], $viewer_user_id, $session_id);
        } catch (Throwable $e_vista) {
            log_error("obtener_codigo.php: Error registrando vista: " . $e_vista->getMessage());
        }
    }
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    log_error("Error en obtener_codigo.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
