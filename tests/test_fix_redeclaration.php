<?php
/**
 * Test script to verify the fix for redeclaration errors in funciones.php and _super_landing_functions.php
 */

$path_funciones = __DIR__ . '/../public_html/myphp/funciones.php';
$path_sl = __DIR__ . '/../public_html/myphp/_super_landing_functions.php';

echo "First inclusion of both files...\n";
include $path_funciones;
include $path_sl;

if (function_exists('getFechaActualCorregida') && function_exists('get_super_landing_by_slug')) {
    echo "SUCCESS: Both functions exist after first inclusion.\n";
} else {
    echo "ERROR: One or more functions MISSING after first inclusion.\n";
    exit(1);
}

echo "Second inclusion of both files (simulating the bug with include)...\n";
include $path_funciones;
include $path_sl;

if (function_exists('getFechaActualCorregida') && function_exists('get_super_landing_by_slug')) {
    echo "SUCCESS: Both functions still exist after second inclusion (No Fatal Error).\n";
} else {
    echo "ERROR: Functions lost after second inclusion?!\n";
    exit(1);
}

echo "Testing get_super_landing_by_slug...\n";
$landing = get_super_landing_by_slug('test-slug');
echo "Call successful (returned " . ($landing === null ? 'null' : 'array') . ")\n";

echo "\nVerification COMPLETE: All redeclaration risks addressed.\n";
