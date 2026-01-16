<?php
/**
 * Endpoint para tracking de clics en newsletters
 * 
 * Este archivo redirige al usuario a la URL original
 * después de registrar el clic
 */

// Incluir funciones necesarias
require_once __DIR__ . '/../../../myphp/funciones.php';
require_once __DIR__ . '/../../../myphp/funciones_newsletter.php';

// Obtener parámetros
$token = $_GET['t'] ?? '';
$url_original = $_GET['url'] ?? '';

if (empty($url_original)) {
    // Si no hay URL, redirigir a la página principal
    header('Location: https://www.codigoamigo.com');
    exit;
}

// Registrar clic
if (!empty($token) && !empty($url_original)) {
    registrarClicNewsletter($token, $url_original);
    // Nota: El tracking se registra antes de redirigir para asegurar que se guarde
}

// Redirigir a la URL original
header('Location: ' . $url_original);
exit;
?>



