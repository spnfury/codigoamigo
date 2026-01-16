<?php
require_once __DIR__ . '/cron/generar_sitemap_chollos.php';

echo "Generando sitemaps en raíz...\n";
if (generarSitemapChollos()) {
    echo "¡Sitemaps generados correctamente!\n";
    echo "Ruta base: " . dirname(__DIR__) . "\n";
} else {
    echo "Error generando sitemaps.\n";
}
