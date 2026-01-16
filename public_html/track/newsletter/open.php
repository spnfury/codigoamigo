<?php
/**
 * Endpoint para tracking de aperturas de newsletters
 * 
 * Este archivo se carga como una imagen invisible de 1x1 pixel
 * para registrar cuando un usuario abre el email
 */

// Incluir funciones necesarias
require_once __DIR__ . '/../../myphp/funciones.php';
require_once __DIR__ . '/../../myphp/funciones_newsletter.php';

// Obtener token
$token = $_GET['t'] ?? '';

if (!empty($token)) {
    // Registrar apertura
    $resultado = registrarAperturaNewsletter($token);
    // Nota: No logueamos errores aquí para evitar spam en logs, pero el tracking debería funcionar
}

// Devolver imagen transparente de 1x1 pixel
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Imagen PNG transparente de 1x1
$image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
echo $image;
exit;
?>



