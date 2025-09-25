<?php
/**
 * Script para verificar específicamente el botón del formulario
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

// Capturar la salida
ob_start();
include 'public/publicar_codigo.php';
$output = ob_get_clean();

// Buscar la sección del botón
if (preg_match('/<div class="text-center original-save-button".*?<\/div>/s', $output, $matches)) {
    echo "=== BOTÓN ENCONTRADO ===\n";
    echo $matches[0] . "\n";
} else {
    echo "=== BOTÓN NO ENCONTRADO ===\n";
}

// Buscar cualquier input de tipo submit
if (preg_match('/<input[^>]*type="submit"[^>]*>/', $output, $matches)) {
    echo "\n=== INPUT SUBMIT ENCONTRADO ===\n";
    echo $matches[0] . "\n";
} else {
    echo "\n=== INPUT SUBMIT NO ENCONTRADO ===\n";
}

// Buscar la clase btn-custom
if (preg_match('/class="[^"]*btn-custom[^"]*"/', $output, $matches)) {
    echo "\n=== CLASE BTN-CUSTOM ENCONTRADA ===\n";
    echo $matches[0] . "\n";
} else {
    echo "\n=== CLASE BTN-CUSTOM NO ENCONTRADA ===\n";
}

// Mostrar el final del formulario
if (preg_match('/<form[^>]*>.*?<\/form>/s', $output, $matches)) {
    echo "\n=== FORMULARIO COMPLETO ===\n";
    $form = $matches[0];
    // Mostrar las últimas 500 caracteres del formulario
    echo substr($form, -500) . "\n";
}
