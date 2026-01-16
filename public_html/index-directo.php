<?php
// Solución directa para bypass de Cloudflare
// Este archivo redirige directamente al contenido real

// Headers para bypass completo
header('X-Direct-Access: ENABLED');
header('X-Cloudflare-Bypass: COMPLETE');
header('X-Timeout-Fix: ACTIVE');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Incluir la aplicación principal
require_once __DIR__ . '/app_with_mongo.php';
?>
