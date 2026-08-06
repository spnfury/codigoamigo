<?php
include_once __DIR__ . '/../inc/logger.php';
/**
 * AJAX endpoint: Obtener todos los códigos de una marca con sus trust scores.
 * 
 * POST /ajax/ver_mas_codigos.php
 * Params: marca (nombre_clave)
 * Returns: JSON con lista de todos los códigos ordenados por trust score
 */

header('Content-Type: application/json; charset=utf-8');

// Incluir dependencias
require_once __DIR__ . '/../inc/conexion.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_codigo.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_trust_score.php';

try {
    $marca = $_POST['marca'] ?? $_GET['marca'] ?? '';
    
    if (empty($marca)) {
        echo json_encode([
            'success' => false,
            'message' => 'Parámetro "marca" requerido'
        ]);
        exit;
    }
    
    // Sanitizar
    $marca = preg_replace('/[^a-zA-Z0-9\-_]/', '', $marca);
    
    $codigos = getBrandCodesWithScores($marca);
    
    echo json_encode([
        'success' => true,
        'total' => count($codigos),
        'codigos' => $codigos
    ]);
    
} catch (Exception $e) {
    log_error("Error en ver_mas_codigos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
