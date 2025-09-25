<?php
/**
 * Script de prueba para la funcionalidad de limpieza de logs
 * Este script simula la llamada AJAX para limpiar logs
 */

// Simular sesión de administrador
session_start();
$_SESSION["user_id"] = "58bd851da54e295b8b52f702"; // ID de administrador válido

// Incluir el archivo de logs
include_once __DIR__ . '/../public_html/public/logs_simple.php';

// Simular la petición GET con acción clear_logs
$_GET['action'] = 'clear_logs';

// Capturar la salida
ob_start();

// Ejecutar el script
try {
    // El script ya está incluido, solo necesitamos simular la petición
    // Como ya está incluido, se ejecutará automáticamente
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$output = ob_get_clean();

echo "=== PRUEBA DE LIMPIEZA DE LOGS ===\n";
echo "Respuesta del servidor:\n";
echo $output . "\n";

// Decodificar JSON para mostrar información estructurada
$response = json_decode($output, true);
if ($response) {
    echo "\n=== ANÁLISIS DE RESPUESTA ===\n";
    echo "Éxito: " . ($response['success'] ? 'SÍ' : 'NO') . "\n";
    echo "Mensaje: " . ($response['message'] ?? 'N/A') . "\n";
    
    if (isset($response['cleared_files']) && is_array($response['cleared_files'])) {
        echo "Archivos limpiados: " . count($response['cleared_files']) . "\n";
        foreach ($response['cleared_files'] as $file) {
            echo "  - " . $file . "\n";
        }
    }
    
    if (isset($response['errors']) && is_array($response['errors'])) {
        echo "Errores: " . count($response['errors']) . "\n";
        foreach ($response['errors'] as $error) {
            echo "  - " . $error . "\n";
        }
    }
} else {
    echo "Error: No se pudo decodificar la respuesta JSON\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>
