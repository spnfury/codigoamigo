<?php
// Mock session
$_SESSION['user_id'] = '58bd851da54e295b8b52f702'; // The user ID we saw earlier

// Mock GET request
$_GET['codigo_id'] = '5c491a352f55c844162d7ae2'; // The IS passed in the URL

// Capture output to avoid header errors in CLI
ob_start();

// Include the script
try {
    require_once '/home/admin/web/codigoamigo.com/public_html/destacar_super.php';
} catch (Throwable $t) {
    echo "\n\nCRITICAL ERROR CAUGHT:\n";
    echo $t->getMessage() . "\n";
    echo $t->getTraceAsString() . "\n";
}

$output = ob_get_clean();
echo "Script executed without fatal errors (captured " . strlen($output) . " bytes of output).\n";
// echo $output; 
?>
