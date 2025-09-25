<?php
// Test simple para verificar que la página de cambiar contraseña funciona

echo "Probando página de cambiar contraseña...\n";

// Verificar que el archivo existe
$file_path = __DIR__ . '/../public_html/public/cambiar_password.php';
if (file_exists($file_path)) {
    echo "✓ Archivo cambiar_password.php encontrado\n";
    
    // Leer el contenido del archivo
    $content = file_get_contents($file_path);
    
    if (strpos($content, 'Recuperar contraseña') !== false) {
        echo "✓ Contenido de la página es correcto\n";
    } else {
        echo "✗ Contenido de la página no es el esperado\n";
    }
    
    if (strpos($content, 'cambio_password') !== false) {
        echo "✓ Formulario de envío encontrado\n";
    } else {
        echo "✗ Formulario de envío no encontrado\n";
    }
    
} else {
    echo "✗ ERROR: Archivo cambiar_password.php no encontrado en $file_path\n";
}

// Verificar que las rutas están definidas en app_with_mongo.php
$app_file = __DIR__ . '/../public_html/app_with_mongo.php';
if (file_exists($app_file)) {
    $app_content = file_get_contents($app_file);
    
    if (strpos($app_content, "get('/cambiar_password'") !== false) {
        echo "✓ Ruta GET /cambiar_password definida en app_with_mongo.php\n";
    } else {
        echo "✗ Ruta GET /cambiar_password NO definida en app_with_mongo.php\n";
    }
    
    if (strpos($app_content, "post('/cambio_password'") !== false) {
        echo "✓ Ruta POST /cambio_password definida en app_with_mongo.php\n";
    } else {
        echo "✗ Ruta POST /cambio_password NO definida en app_with_mongo.php\n";
    }
    
    if (strpos($app_content, "get('/nuevo_password'") !== false) {
        echo "✓ Ruta GET /nuevo_password definida en app_with_mongo.php\n";
    } else {
        echo "✗ Ruta GET /nuevo_password NO definida en app_with_mongo.php\n";
    }
    
} else {
    echo "✗ ERROR: Archivo app_with_mongo.php no encontrado\n";
}

echo "\nTest completado.\n";
?>
