<?php
// Script de verificación de expansión y tag de Amazon (v2)

require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_chollos.php';
require_once __DIR__ . '/myphp/funciones_chollos_amazon.php';

$test_urls = [
    'https://ganga.ad/UQe6',      // Problemático
    'https://amz.tf/dFruvKY',    // Debería expandir bien
    'https://www.amazon.es/dp/B08DV8FJJK', // Directo sin tag
    'https://www.amazon.es/dp/B08DV8FJJK?tag=test-21' // Con tag diferente
];

echo "VERIFICACIÓN DE tagging garantizado DE AMAZON\n";
echo "============================================\n\n";

foreach ($test_urls as $url) {
    echo "Original: $url\n";
    
    // Probar tagging garantizado
    $final = convertirEnlaceAmazonGarantizado($url);
    echo "Final: $final\n";
    
    $tiene_tag = (strpos($final, 'tag=spnfuryy-21') !== false);
    echo "Tiene tag correcto: " . ($tiene_tag ? "SÍ ✅" : "NO ❌") . "\n";
    
    echo "--------------------------------------------\n";
}
