<?php
// test_click_logging.php
require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/funciones_amazon_services.php';

$slug = 'prime';
$origin = 'test_manual';
$res = logAmazonServiceClick($slug, 'manual_test', $origin);
echo "Logging result: " . ($res ? "SUCCESS" : "FAILED") . "\n";
?>
