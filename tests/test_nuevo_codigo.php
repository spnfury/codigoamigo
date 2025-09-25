<?php
/**
 * Script de prueba para verificar el formulario de nuevo código
 */

// Simular la sesión
session_start();
$_SESSION["user_id"] = "639899bc6321ee0d0e4010d2"; // Usuario admin

// Simular variables de servidor
$_SERVER['DOCUMENT_ROOT'] = '/home/admin/web/codigoamigo.com/public_html';
$_SERVER['SERVER_NAME'] = 'www.codigoamigo.com';
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'www.codigoamigo.com';
$_SERVER['REQUEST_URI'] = '/nuevo_codigo';

// Simular parámetros de la URL
$_GET['marca'] = 'FINANZEN ZERO';

echo "=== PRUEBA DEL FORMULARIO NUEVO CÓDIGO ===\n\n";

// Cambiar al directorio correcto
chdir('/home/admin/web/codigoamigo.com/public_html');

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "1. Simulando ejecución del formulario...\n";
    
    // Capturar la salida
    ob_start();
    
    // Incluir el archivo
    include 'public/publicar_codigo.php';
    
    $output = ob_get_clean();
    
    echo "   ✓ Archivo ejecutado correctamente\n";
    echo "   ✓ Longitud de la salida: " . strlen($output) . " caracteres\n";
    
    // Verificar que no hay errores fatales
    if (strpos($output, 'Fatal error') === false && strpos($output, 'Parse error') === false) {
        echo "   ✓ No se encontraron errores fatales\n";
        
        // Verificar que se muestra el título
        if (strpos($output, 'NUEVO CÓDIGO AMIGO') !== false || strpos($output, 'Nuevo Código') !== false) {
            echo "   ✓ Título del formulario se muestra correctamente\n";
        } else {
            echo "   ⚠ El título del formulario no se encontró\n";
        }
        
        // Verificar que se muestra el botón de publicar
        if (strpos($output, 'btn-custom') !== false) {
            echo "   ✓ Clase del botón encontrada\n";
        } else {
            echo "   ✗ Clase del botón no encontrada\n";
        }
        
        if (strpos($output, 'Añadir código amigo') !== false || strpos($output, 'Publicar código') !== false) {
            echo "   ✓ Texto del botón encontrado\n";
        } else {
            echo "   ✗ Texto del botón no encontrado\n";
        }
        
        if (strpos($output, 'type="submit"') !== false) {
            echo "   ✓ Botón de submit encontrado\n";
        } else {
            echo "   ✗ Botón de submit no encontrado\n";
        }
        
        // Verificar que se muestran los campos del formulario
        if (strpos($output, 'Marca o Servicio') !== false) {
            echo "   ✓ Campo de marca encontrado\n";
        } else {
            echo "   ✗ Campo de marca no encontrado\n";
        }
        
        if (strpos($output, 'Beneficio económico') !== false) {
            echo "   ✓ Campo de beneficio encontrado\n";
        } else {
            echo "   ✗ Campo de beneficio no encontrado\n";
        }
        
        if (strpos($output, 'Código promocional') !== false) {
            echo "   ✓ Campo de código encontrado\n";
        } else {
            echo "   ✗ Campo de código no encontrado\n";
        }
        
        // Mostrar una muestra del HTML generado
        echo "\n2. Muestra del HTML generado (primeros 1000 caracteres):\n";
        echo substr($output, 0, 1000) . "\n";
        
    } else {
        echo "   ✗ Se encontraron errores fatales en la salida\n";
        echo "   Salida (primeros 500 caracteres):\n";
        echo substr($output, 0, 500) . "\n";
    }
    
    echo "\n=== PRUEBA COMPLETADA ===\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR GENERAL: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
