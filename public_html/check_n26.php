<?php
// Simple script to check N26 brand data
require_once __DIR__ . '/myphp/funciones.php';

$marca_slug = 'n26';
$marca_info = get_brand_info($marca_slug);

echo "Brand Info for: $marca_slug\n\n";
echo "nombre: " . ($marca_info['nombre'] ?? 'NOT SET') . "\n";
echo "seo_faq length: " . strlen($marca_info['seo_faq'] ?? '') . " chars\n";
echo "seo_faq content:\n";
echo "---BEGIN---\n";
echo ($marca_info['seo_faq'] ?? 'EMPTY');
echo "\n---END---\n";
?>
