<?php
/**
 * Script específico para verificar el botón de publicar
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

// Cambiar al directorio correcto
chdir('/home/admin/web/codigoamigo.com/public_html');

// Incluir dependencias necesarias
include_once '/home/admin/web/codigoamigo.com/public_html/inc/includes.php';
include_once '/home/admin/web/codigoamigo.com/public_html/inc/conexion.php';

echo "=== VERIFICACIÓN DEL BOTÓN DE PUBLICAR ===\n\n";

// Capturar la salida
ob_start();
include 'public/publicar_codigo.php';
$output = ob_get_clean();

// Buscar específicamente el botón
if (preg_match('/<input[^>]*type="submit"[^>]*>/i', $output, $matches)) {
    echo "✓ BOTÓN ENCONTRADO:\n";
    echo $matches[0] . "\n\n";
} else {
    echo "✗ ERROR: No se encontró el botón de submit\n\n";
}

// Buscar el div del botón
if (preg_match('/<div[^>]*class="[^"]*original-save-button[^"]*"[^>]*>.*?<\/div>/s', $output, $matches)) {
    echo "✓ DIV DEL BOTÓN ENCONTRADO:\n";
    echo $matches[0] . "\n\n";
} else {
    echo "✗ ERROR: No se encontró el div del botón\n\n";
}

// Buscar estilos CSS que puedan estar ocultando el botón
if (preg_match('/\.original-save-button[^}]*{[^}]*}/s', $output, $matches)) {
    echo "✓ ESTILOS CSS DEL BOTÓN:\n";
    echo $matches[0] . "\n\n";
} else {
    echo "✗ ERROR: No se encontraron estilos CSS del botón\n\n";
}

// Verificar si hay JavaScript que oculte el botón
if (preg_match('/original-save-button.*display.*none/i', $output, $matches)) {
    echo "⚠ ADVERTENCIA: JavaScript oculta el botón original\n";
    echo "Línea encontrada: " . $matches[0] . "\n\n";
}

// Buscar el botón flotante
if (preg_match('/<button[^>]*class="[^"]*floating-save-button[^"]*"[^>]*>.*?<\/button>/s', $output, $matches)) {
    echo "✓ BOTÓN FLOTANTE ENCONTRADO:\n";
    echo $matches[0] . "\n\n";
} else {
    echo "✗ ERROR: No se encontró el botón flotante\n\n";
}

echo "=== ANÁLISIS COMPLETADO ===\n";
