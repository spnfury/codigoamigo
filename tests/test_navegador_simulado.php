<?php
// Simular el entorno web completo
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

echo "=== SIMULACIÓN DEL NAVEGADOR ===\n\n";

// Capturar la salida del formulario
ob_start();
include '/home/admin/web/codigoamigo.com/public_html/public/publicar_codigo.php';
$output = ob_get_clean();

// Buscar específicamente el botón y su contexto
if (preg_match('/(<div class="text-center original-save-button"[^>]*>.*?<\/div>)/s', $output, $matches)) {
    echo "✓ Div del botón encontrado:\n";
    echo $matches[1] . "\n\n";
    
    // Verificar si el botón está dentro del div
    if (strpos($matches[1], 'type="submit"') !== false) {
        echo "✓ Botón de submit está dentro del div\n";
    } else {
        echo "✗ ERROR: Botón de submit NO está dentro del div\n";
    }
    
} else {
    echo "✗ ERROR: No se encontró el div del botón\n";
}

// Verificar el CSS aplicado
echo "\n=== VERIFICACIÓN DE CSS ===\n";
if (preg_match('/\.original-save-button\s*\{[^}]*\}/s', $output, $css_matches)) {
    echo "✓ CSS encontrado:\n";
    echo $css_matches[0] . "\n\n";
    
    if (strpos($css_matches[0], 'display: block') !== false) {
        echo "✓ CSS configurado para mostrar el botón\n";
    } else {
        echo "✗ ERROR: CSS no configurado para mostrar el botón\n";
    }
} else {
    echo "✗ ERROR: CSS para .original-save-button no encontrado\n";
}

// Verificar si hay JavaScript que pueda estar ocultando el botón
echo "\n=== VERIFICACIÓN DE JAVASCRIPT ===\n";
if (preg_match('/\$\([^)]*original-save-button[^)]*\)\.hide\(\)/', $output)) {
    echo "✗ ERROR: JavaScript está ocultando el botón\n";
} else {
    echo "✓ No hay JavaScript ocultando el botón\n";
}

echo "\n=== RECOMENDACIONES ===\n";
echo "1. Haz un hard refresh en el navegador (Ctrl+F5 o Cmd+Shift+R)\n";
echo "2. Limpia el cache del navegador\n";
echo "3. Verifica que estés accediendo a la URL correcta\n";
echo "4. Revisa la consola del navegador para errores JavaScript\n";

echo "\n=== FIN DE LA SIMULACIÓN ===\n";
?>
