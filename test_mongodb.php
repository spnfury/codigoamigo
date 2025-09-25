<?php
require_once 'vendor/autoload.php';

echo "Probando conexión a MongoDB...\n";

try {
    $mongo = new MongoDB\Client('mongodb://localhost:27017');
    echo "✅ SUCCESS - MongoDB está accesible\n";
    
    // Probar una operación básica
    $database = $mongo->selectDatabase('codigoamigo');
    $collection = $database->selectCollection('usuarios');
    $count = $collection->countDocuments();
    echo "✅ SUCCESS - Base de datos accesible, usuarios encontrados: $count\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}



