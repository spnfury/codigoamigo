<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones_amazon_services.php';

$collection = getCollectionAffiliateLinks();
$services = $collection->find(['active' => true])->toArray();

echo "Active slugs:\n";
foreach ($services as $srv) {
    echo "- " . $srv['slug'] . "\n";
}
