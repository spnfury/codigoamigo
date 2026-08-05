<?php
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_chollos.php';
require_once __DIR__ . '/myphp/funciones_chollos_amazon.php';

// Mock values
$chollo_id = '697218789bb5a33865065483';
$url_with_bad_tag = 'https://www.amazon.es/dp/B0FSDC2S6B?tag=gangastl-21&psc=1';

echo "Testing tag replacement logic...\n";
echo "Original URL: $url_with_bad_tag\n";

$converted = convertirEnlaceAmazon($url_with_bad_tag);
echo "Converted URL: $converted\n";

if (strpos($converted, 'spnfuryy-21') !== false && strpos($converted, 'gangastl-21') === false) {
    echo "SUCCESS: Tag replaced correctly.\n";
} else {
    echo "FAILURE: Tag NOT replaced correctly.\n";
}

echo "\nTesting convertirEnlaceAmazonGarantizado with unexpanded URL...\n";
$unexpanded = 'https://ganga.ad/AE9b';
// We can't really test this fully without mocking the HTTP request results or DB,
// but we can check if it tries to use the cache or similar.

echo "Testing resolve_amazon.php logic simulation...\n";
// This logic is in JS, so we can't test it in PHP easily, but we can verify the API response it would get.
// If api_amazon_maintenance.php is called with updates...

echo "\nChecking API logic for update...\n";
// Creating a mock payload
$payload = [
    'id' => $chollo_id,
    'enlace_expandido' => $url_with_bad_tag
];
// Logic from api_amazon_maintenance.php (simplified)
$expanded_url = $payload['enlace_expandido'];
if (!empty($expanded_url) && strpos($expanded_url, 'amazon.') !== false) {
    if (strpos($expanded_url, 'tag=') === false) {
        $separator = (strpos($expanded_url, '?') === false) ? '?' : '&';
        $expanded_url .= $separator . 'tag=spnfuryy-21';
    } else {
        // Reemplazar cualquier tag por el nuestro
        $expanded_url = preg_replace('/tag=[^&]+/', 'tag=spnfuryy-21', $expanded_url);
    }
}
echo "API Processed URL: $expanded_url\n";
