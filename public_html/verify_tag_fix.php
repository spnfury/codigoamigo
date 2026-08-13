<?php
// Since the fix is on the client-side JS of resolve_amazon.php, we can't fully test it with PHP CLI.
// However, we can re-run the PHP logic test to ensure converting functions are still correct.
require_once __DIR__ . '/myphp/funciones_chollos_amazon.php';

$bad_url = 'https://www.amazon.es/dp/B0FSDC2S6B?tag=gangastl-21&psc=1';
$fixed_url = convertirEnlaceAmazon($bad_url);

echo "PHP Logic Test:\n";
echo "Bad URL: $bad_url\n";
echo "Fixed URL: $fixed_url\n";

if (strpos($fixed_url, 'spnfuryy-21') !== false && strpos($fixed_url, 'gangastl-21') === false) {
    echo "PHP Logic: OK\n";
} else {
    echo "PHP Logic: FAIL\n";
}

echo "\nJS Logic Explanation:\n";
echo "The JS in resolve_amazon.php now employs a regex replace: finalUrl = finalUrl.replace(/tag=[^&]+/, myTag);\n";
echo "This ensures that if 'tag=gangastl-21' exists, it is replaced by 'tag=spnfuryy-21'.\n";
