<?php
require_once __DIR__ . '/public_html/inc/includes.php';
require_once __DIR__ . '/public_html/myphp/funciones_faq.php';

echo "Listing ALL FAQs in system:\n";

$collection = getCollectionFAQs();
$all_faqs = $collection->find([]);

$count = 0;
foreach ($all_faqs as $faq) {
    echo "ID: " . $faq['_id'] . "\n";
    echo "Marca: " . $faq['marca_clave'] . "\n";
    echo "Question: " . $faq['titulo'] . "\n";
    echo "-------------------\n";
    $count++;
}

echo "Total: $count\n";
