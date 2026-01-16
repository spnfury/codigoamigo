<?php
// simulate_redirect.php
$_GET['slug'] = 'prime';
$_GET['origin'] = 'landing_card';
ob_start();
include 'redirect_amazon.php';
$output = ob_get_clean();
$headers = headers_list();
echo "HEADERS:\n";
print_r($headers);
echo "\nOUTPUT:\n$output\n";
?>
