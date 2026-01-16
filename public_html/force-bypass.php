<?php
// Bypass completo temporal para Cloudflare
// Este archivo fuerza el bypass de TODAS las protecciones

// Headers ultra agresivos para bypass
header('X-CF-Bypass: FORCED');
header('X-Development-Mode: ULTRA_ACTIVE');
header('X-Direct-Access: ENABLED');
header('X-Bypass-Status: COMPLETE');
header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
header('Last-Modified: Thu, 01 Jan 1970 00:00:00 GMT');

// Eliminar headers de Cloudflare
header_remove('CF-Ray');
header_remove('CF-Cache-Status');
header_remove('CF-Connecting-IP');
header_remove('CF-Visitor');
header_remove('CF-Country');
header_remove('CF-IPCountry');

// Log del bypass
error_log("ULTRA BYPASS ACTIVATED - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " - UA: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));

// Redirigir al sitio principal con bypass forzado
$redirect_url = '/index.php?cf_bypass=1&force=1&ultra=1&direct=1&timestamp=' . time();
header('Location: ' . $redirect_url);
exit;
?>

