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

echo "=== PRUEBA DEL SELECT DE CATEGORÍA ===\n\n";

// Capturar la salida del formulario
ob_start();
include '/home/admin/web/codigoamigo.com/public_html/public/publicar_codigo.php';
$output = ob_get_clean();

// Buscar el select de categoría
if (preg_match('/(<select id="categoria"[^>]*>.*?<\/select>)/s', $output, $matches)) {
    echo "✓ Select de categoría encontrado:\n";
    echo $matches[1] . "\n\n";
    
    // Verificar que tenga la opción por defecto
    if (strpos($matches[1], 'Selecciona una categoría para tu marca') !== false) {
        echo "✓ Opción por defecto presente\n";
    } else {
        echo "✗ ERROR: Opción por defecto no encontrada\n";
    }
    
    // Verificar que tenga las categorías
    if (preg_match_all('/<option value="[^"]*">([^<]*)<\/option>/', $matches[1], $options)) {
        echo "✓ Opciones encontradas: " . count($options[1]) . "\n";
        foreach ($options[1] as $option) {
            echo "  - " . $option . "\n";
        }
    } else {
        echo "✗ ERROR: No se encontraron opciones\n";
    }
    
} else {
    echo "✗ ERROR: Select de categoría no encontrado\n";
}

// Verificar estilos CSS
if (strpos($output, '.nueva-marca-content select') !== false) {
    echo "✓ Estilos CSS para el select presentes\n";
} else {
    echo "✗ ERROR: Estilos CSS para el select no encontrados\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>

