<?php
// Test script para verificar conexión MongoDB
require_once __DIR__ . '/public_html/vendor/autoload.php';

function createConnection() {
    global $db;

    $uri = "mongodb://127.0.0.1:27017";

    if($db){
        return $db;
    }

    try {
        $mongo = new MongoDB\Client($uri);
        $db = $mongo->codigo_db;
        return $db;
    }
    catch (MongoCursorException $e) {
        echo "Error de conexión: ".$e->getMessage()."\n";
        echo "Código del error: ".$e->getCode()."\n";
        return false;
    }
}

function getCollectionMarcas() {
    $db = createConnection();
    $collection_marcas = $db->selectCollection('marcas');
    return $collection_marcas;
}

try {
    $collection_marcas = getCollectionMarcas();
    echo "Conexión exitosa a MongoDB\n";

    // Probar una consulta simple
    $count = $collection_marcas->countDocuments(['estado' => 1]);
    echo "Número de marcas activas: $count\n";

    // Probar consulta con filtros similares a los del admin_marcas.php
    $filtros = [];
    $filtros['estado'] = 1;

    $marcas = $collection_marcas->find($filtros, ['limit' => 20])->toArray();
    echo "Consulta exitosa. Encontradas " . count($marcas) . " marcas\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
