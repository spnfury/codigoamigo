<?php
require_once __DIR__ . '/inc/conexion.php';

$db = createConnection();
$codigos = $db->selectCollection('codigos');
$marcas = $db->selectCollection('marcas');

$ing = $marcas->findOne(['nombre_clave' => 'ing-direct']);
if (!$ing) $ing = $marcas->findOne(['nombre_clave' => 'ing']);

echo "Marca search result: " . ($ing ? $ing['nombre_clave'] : 'NOT FOUND') . "\n";

if ($ing) {
    // Buscar por ID de marca
    $countId = $codigos->countDocuments(['marca_id' => (string)$ing['_id']]);
    echo "Codes by ID: $countId\n";
    
    // Buscar por slug de marca
    $countSlug = $codigos->countDocuments(['marca' => $ing['nombre_clave']]);
    echo "Codes by slug ({$ing['nombre_clave']}): $countSlug\n";
    
    // Buscar cualquier codigo
    $one = $codigos->findOne(['marca' => $ing['nombre_clave']]);
    if ($one) {
        echo "Found one ID: " . $one['_id'] . "\n";
        
        // Promote it
        $codigos->updateOne(
            ['_id' => $one['_id']],
            ['$set' => ['tipo_destacado' => 'super', 'destacado' => time() + 3600, 'estado' => 1]]
        );
        echo "Promoted code " . $one['_id'] . "\n";
    }
}
?>
