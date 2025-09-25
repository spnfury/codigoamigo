<?php
/**
 * Script de prueba para verificar la funcionalidad de nueva marca
 */

// Simular la sesión
session_start();
$_SESSION["user_id"] = "639899bc6321ee0d0e4010d2";

// Simular variables de servidor
$_SERVER['DOCUMENT_ROOT'] = '/home/admin/web/codigoamigo.com/public_html';
$_SERVER['SERVER_NAME'] = 'www.codigoamigo.com';
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'www.codigoamigo.com';
$_SERVER['REQUEST_URI'] = '/nuevo_codigo';

// Simular parámetros de la URL
$_GET['marca'] = 'FINANZEN ZERO';

echo "=== PRUEBA DE FUNCIONALIDAD NUEVA MARCA ===\n\n";

// Cambiar al directorio correcto
chdir('/home/admin/web/codigoamigo.com/public_html');

// Incluir dependencias necesarias
include_once '/home/admin/web/codigoamigo.com/public_html/inc/includes.php';
include_once '/home/admin/web/codigoamigo.com/public_html/inc/conexion.php';

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "1. Simulando ejecución del formulario...\n";
    
    // Capturar la salida
    ob_start();
    include 'public/publicar_codigo.php';
    $output = ob_get_clean();
    
    echo "   ✓ Formulario cargado correctamente\n";
    
    // Verificar que el JavaScript de nueva marca esté presente
    if (strpos($output, 'nueva_marca') !== false) {
        echo "   ✓ JavaScript de nueva marca encontrado\n";
    } else {
        echo "   ✗ ERROR: JavaScript de nueva marca no encontrado\n";
    }
    
    // Verificar que el div de nueva marca esté presente
    if (strpos($output, 'div_nueva_marca') !== false) {
        echo "   ✓ Div de nueva marca encontrado\n";
    } else {
        echo "   ✗ ERROR: Div de nueva marca no encontrado\n";
    }
    
    // Verificar que el botón de cancelar esté presente
    if (strpos($output, 'id="cancelar"') !== false) {
        echo "   ✓ Botón de cancelar encontrado\n";
    } else {
        echo "   ✗ ERROR: Botón de cancelar no encontrado\n";
    }
    
    echo "\n2. Verificando funcionalidad de búsqueda de marcas...\n";
    
    // Probar la búsqueda de marcas
    $test_query = "marca_inexistente_test";
    $url = "http://localhost/ajax/buscar_marcas.php?q=" . urlencode($test_query);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if (is_array($data) && !empty($data)) {
            echo "   ✓ API de búsqueda de marcas funciona\n";
            echo "   ✓ Respuesta: " . json_encode($data) . "\n";
        } else {
            echo "   ✗ ERROR: API de búsqueda no devuelve datos válidos\n";
        }
    } else {
        echo "   ✗ ERROR: No se pudo conectar con la API de búsqueda\n";
    }
    
    echo "\n=== PRUEBA COMPLETADA ===\n";
    
} catch (Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
}
