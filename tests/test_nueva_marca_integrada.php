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

echo "=== PRUEBA DE NUEVA MARCA INTEGRADA ===\n\n";

// Capturar la salida del formulario
ob_start();
include '/home/admin/web/codigoamigo.com/public_html/public/publicar_codigo.php';
$output = ob_get_clean();

// Buscar la sección de nueva marca integrada
if (preg_match('/(<div class="hide" id="div_nueva_marca">.*?<\/div>)/s', $output, $matches)) {
    echo "✓ Sección de nueva marca integrada encontrada\n";
    echo "Contenido:\n";
    echo $matches[1] . "\n\n";
    
    // Verificar elementos específicos
    if (strpos($matches[1], 'nueva-marca-container') !== false) {
        echo "✓ Contenedor de nueva marca presente\n";
    } else {
        echo "✗ ERROR: Contenedor de nueva marca no encontrado\n";
    }
    
    if (strpos($matches[1], 'nueva-marca-header') !== false) {
        echo "✓ Header de nueva marca presente\n";
    } else {
        echo "✗ ERROR: Header de nueva marca no encontrado\n";
    }
    
    if (strpos($matches[1], 'btn-cancelar') !== false) {
        echo "✓ Botón de cancelar presente\n";
    } else {
        echo "✗ ERROR: Botón de cancelar no encontrado\n";
    }
    
    if (strpos($matches[1], 'imagenes-container') !== false) {
        echo "✓ Contenedor de imágenes presente\n";
    } else {
        echo "✗ ERROR: Contenedor de imágenes no encontrado\n";
    }
    
} else {
    echo "✗ ERROR: Sección de nueva marca integrada no encontrada\n";
}

// Verificar que esté dentro del campo de marca
if (preg_match('/(<div class="form-group">.*?<label for="marca">Marca o Servicio<\/label>.*?<div class="hide" id="div_nueva_marca">.*?<\/div>.*?<\/div>)/s', $output)) {
    echo "✓ Nueva marca está correctamente integrada dentro del campo de marca\n";
} else {
    echo "✗ ERROR: Nueva marca no está integrada correctamente\n";
}

// Verificar estilos CSS
if (strpos($output, '.nueva-marca-container') !== false) {
    echo "✓ Estilos CSS para nueva marca presentes\n";
} else {
    echo "✗ ERROR: Estilos CSS para nueva marca no encontrados\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>
