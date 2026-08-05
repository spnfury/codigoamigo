<?php
/**
 * Script de prueba para verificar los fixes de funciones indefinidas
 */

// Incluir LayoutManager que a su vez incluye funciones.php e inc/funciones.php
require_once __DIR__ . '/myphp/LayoutManager.php';

echo "[TEST] Verificando debuglog()...\n";
if (function_exists('debuglog')) {
    debuglog("Test de debuglog exitoso");
    echo "✓ debuglog() existe y fue llamada (revisar debug.log para confirmación).\n";
} else {
    echo "✗ ERROR: debuglog() sigue sin estar definida.\n";
}

echo "\n[TEST] Verificando getListMarcaSpecial()...\n";
if (function_exists('getListMarcaSpecial')) {
    try {
        $marcas = getListMarcaSpecial();
        echo "✓ getListMarcaSpecial() existe y devolvió " . count($marcas) . " marcas.\n";
    } catch (Throwable $e) {
        echo "✗ ERROR al ejecutar getListMarcaSpecial(): " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ ERROR: getListMarcaSpecial() sigue sin estar definida.\n";
}

echo "\n[TEST] Verificando mandaBot()...\n";
if (function_exists('mandaBot')) {
    echo "✓ mandaBot() existe.\n";
    // No la llamamos para no inundar Telegram si no estamos seguros del ID
} else {
    echo "✗ ERROR: mandaBot() no está definida.\n";
}
?>
