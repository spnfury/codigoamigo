<?php
// Simular el entorno web
$_SERVER['DOCUMENT_ROOT'] = '/home/admin/web/codigoamigo.com/public_html';
$_SERVER['REQUEST_URI'] = '/public/publicar_codigo.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'on';

// Cambiar al directorio correcto
chdir('/home/admin/web/codigoamigo.com/public_html');

// Incluir las dependencias necesarias
include_once '/home/admin/web/codigoamigo.com/public_html/inc/includes.php';
include_once '/home/admin/web/codigoamigo.com/public_html/inc/conexion.php';

// Simular que no estamos en modo modificación
$codigo_data = null;

echo "=== PRUEBA DE VISIBILIDAD DEL BOTÓN ===\n\n";

// Capturar la salida del formulario
ob_start();
include '/home/admin/web/codigoamigo.com/public_html/public/publicar_codigo.php';
$output = ob_get_clean();

// Buscar el botón de submit
if (preg_match('/<input[^>]*type="submit"[^>]*>/', $output, $matches)) {
    echo "✓ Botón de submit encontrado:\n";
    echo $matches[0] . "\n\n";
    
    // Verificar si tiene la clase correcta
    if (strpos($matches[0], 'btn-custom') !== false) {
        echo "✓ Botón tiene la clase 'btn-custom'\n";
    } else {
        echo "✗ ERROR: Botón NO tiene la clase 'btn-custom'\n";
    }
    
    // Verificar el valor del botón
    if (preg_match('/value="([^"]*)"/', $matches[0], $value_matches)) {
        echo "✓ Valor del botón: " . $value_matches[1] . "\n";
    }
    
} else {
    echo "✗ ERROR: No se encontró el botón de submit\n";
}

// Verificar el CSS del botón original
if (strpos($output, '.original-save-button') !== false) {
    echo "✓ CSS para .original-save-button encontrado\n";
    
    if (strpos($output, 'display: block') !== false) {
        echo "✓ CSS configurado para mostrar el botón (display: block)\n";
    } else {
        echo "✗ ERROR: CSS no configurado correctamente para mostrar el botón\n";
    }
} else {
    echo "✗ ERROR: CSS para .original-save-button no encontrado\n";
}

// Verificar si el contenedor tiene la clase correcta
if (preg_match('/<div class="container[^"]*">/', $output, $container_matches)) {
    echo "✓ Contenedor encontrado: " . $container_matches[0] . "\n";
    
    if (strpos($container_matches[0], 'modo-modificacion') === false) {
        echo "✓ Contenedor NO tiene clase 'modo-modificacion' (correcto para nuevo código)\n";
    } else {
        echo "✗ ERROR: Contenedor tiene clase 'modo-modificacion' cuando no debería\n";
    }
} else {
    echo "✗ ERROR: No se encontró el contenedor\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>
