<?php
// Buscar específicamente la marca "ubereats"
require_once __DIR__ . '/public_html/inc/includes.php';

$collection_marcas = getCollectionMarcas();

echo "=== BÚSQUEDA DE MARCA 'UBEREATS' ===\n\n";

// Buscar exactamente "ubereats"
$marca_exacta = $collection_marcas->findOne([
    'nombre_clave' => 'ubereats'
]);

if ($marca_exacta) {
    echo "✅ Marca encontrada exactamente:\n";
    echo "- ID: " . $marca_exacta['_id'] . "\n";
    echo "- Nombre: " . $marca_exacta['nombre'] . "\n";
    echo "- Nombre clave: " . $marca_exacta['nombre_clave'] . "\n";
    echo "- Estado: " . $marca_exacta['estado'] . "\n";
    echo "- Categoría: " . $marca_exacta['categoria'] . "\n";
} else {
    echo "❌ Marca 'ubereats' no encontrada exactamente\n\n";
}

// Buscar marcas que contengan "uber" (case insensitive)
$marcas_uber = $collection_marcas->find([
    'nombre_clave' => ['$regex' => 'uber', '$options' => 'i']
])->toArray();

echo "=== MARCAS QUE CONTIENEN 'UBER' ===\n";
echo "Total encontradas: " . count($marcas_uber) . "\n\n";

foreach ($marcas_uber as $marca) {
    echo "- ID: " . $marca['_id'] . "\n";
    echo "  Nombre: '" . $marca['nombre'] . "'\n";
    echo "  Nombre clave: '" . $marca['nombre_clave'] . "'\n";
    echo "  Estado: " . $marca['estado'] . "\n";
    echo "  Categoría: " . $marca['categoria'] . "\n";
    echo "\n";
}

// Buscar marcas que contengan "eat" (case insensitive)
$marcas_eat = $collection_marcas->find([
    'nombre_clave' => ['$regex' => 'eat', '$options' => 'i']
])->toArray();

echo "=== MARCAS QUE CONTIENEN 'EAT' ===\n";
echo "Total encontradas: " . count($marcas_eat) . "\n\n";

foreach ($marcas_eat as $marca) {
    echo "- ID: " . $marca['_id'] . "\n";
    echo "  Nombre: '" . $marca['nombre'] . "'\n";
    echo "  Nombre clave: '" . $marca['nombre_clave'] . "'\n";
    echo "  Estado: " . $marca['estado'] . "\n";
    echo "  Categoría: " . $marca['categoria'] . "\n";
    echo "\n";
}

// Verificar si hay marcas con estado 0 (inactivas)
$marcas_inactivas_uber = $collection_marcas->find([
    'nombre_clave' => ['$regex' => 'uber', '$options' => 'i'],
    'estado' => 0
])->toArray();

echo "=== MARCAS INACTIVAS CON 'UBER' ===\n";
echo "Total encontradas: " . count($marcas_inactivas_uber) . "\n\n";

foreach ($marcas_inactivas_uber as $marca) {
    echo "- ID: " . $marca['_id'] . "\n";
    echo "  Nombre: '" . $marca['nombre'] . "'\n";
    echo "  Nombre clave: '" . $marca['nombre_clave'] . "'\n";
    echo "  Estado: " . $marca['estado'] . "\n";
    echo "  Categoría: " . $marca['categoria'] . "\n";
    echo "\n";
}
?>
