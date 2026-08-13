<?php
require_once __DIR__ . '/public_html/inc/includes.php';
require_once __DIR__ . '/public_html/myphp/funciones_faq_frontend.php';

$brand = 'holaluz';
$name = 'Holaluz';

echo "Testing HTML generation for $brand...\n";
$html = incluirFAQsEnMarca($brand, $name);

if (empty($html)) {
    echo "HTML IS EMPTY!\n";
} else {
    echo "HTML Generated (Length: " . strlen($html) . "):\n";
    echo substr($html, 0, 500) . "...\n";
}
