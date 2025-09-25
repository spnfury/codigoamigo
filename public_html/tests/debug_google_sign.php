<?php
/**
 * Script de debug para el endpoint /google_sign
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG GOOGLE SIGN ===\n\n";

// 1. Verificar que el archivo existe
$file_path = __DIR__ . '/../public_html/public/google-sign-in.php';
echo "1. Verificando archivo: $file_path\n";
if (file_exists($file_path)) {
    echo "   ✓ Archivo existe\n";
} else {
    echo "   ✗ Archivo NO existe\n";
    exit(1);
}

// 2. Verificar que se puede incluir
echo "\n2. Verificando inclusión del archivo...\n";
try {
    // Simular entorno
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['id_token'] = 'test_token';
    
    // Capturar salida
    ob_start();
    include $file_path;
    $output = ob_get_clean();
    
    echo "   ✓ Archivo incluido correctamente\n";
    echo "   Salida: " . (empty($output) ? '(vacía)' : $output) . "\n";
    
} catch (Exception $e) {
    echo "   ✗ Error al incluir archivo: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "   ✗ Error fatal al incluir archivo: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
