<?php
// test_audible.php
require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/funciones_amazon_services.php';

$slug = 'audible';
$service = getAmazonServiceBySlug($slug);

if ($service) {
    echo "Found service: " . $service['title'] . "\n";
    echo "Destination: " . $service['destination_url'] . "\n";
} else {
    echo "Service NOT found for slug: $slug\n";
}
?>
