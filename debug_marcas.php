<?php
// Script de diagnóstico para ver la estructura de datos de las marcas
require_once __DIR__ . '/public_html/inc/includes.php';

$collection_marcas = getCollectionMarcas();

echo "=== ESTRUCTURA DE DATOS DE MARCAS ===\n\n";

// Obtener algunas marcas de ejemplo
$marcas_ejemplo = $collection_marcas->find(['estado' => 1], [
    'limit' => 5,
    'sort' => ['nombre' => 1]
])->toArray();

foreach ($marcas_ejemplo as $marca) {
    echo "ID: " . $marca['_id'] . "\n";
    echo "Nombre: " . ($marca['nombre'] ?? 'NO DEFINIDO') . "\n";
    echo "Nombre clave: " . ($marca['nombre_clave'] ?? 'NO DEFINIDO') . "\n";
    echo "Categoría: " . ($marca['categoria'] ?? 'NO DEFINIDO') . "\n";

    // Mostrar todas las claves disponibles
    echo "Campos disponibles: ";
    foreach (array_keys((array)$marca) as $campo) {
        echo $campo . ", ";
    }
    echo "\n\n";
}

echo "=== CONSULTA ESPECÍFICA PARA EL SELECTOR ===\n\n";

// Simular exactamente la consulta del selector
$todas_marcas = $collection_marcas->find(['estado' => 1], ['sort' => ['nombre' => 1]])->toArray();

echo "Total de marcas encontradas: " . count($todas_marcas) . "\n\n";

foreach (array_slice($todas_marcas, 0, 3) as $marca_opcion) {
    echo "Marca opción:\n";
    echo "- _id: " . $marca_opcion['_id'] . "\n";
    echo "- nombre: " . $marca_opcion['nombre'] . "\n";
    echo "- nombre_clave: " . $marca_opcion['nombre_clave'] . "\n";
    echo "- categoria: " . $marca_opcion['categoria'] . "\n";

    // Ver si hay algún problema con caracteres especiales o formato
    echo "- nombre (htmlspecialchars): " . htmlspecialchars($marca_opcion['nombre']) . "\n";
    echo "- nombre (strlen): " . strlen($marca_opcion['nombre']) . " caracteres\n";

    if (isset($marca_opcion['nombre_clave'])) {
        echo "- nombre_clave (htmlspecialchars): " . htmlspecialchars($marca_opcion['nombre_clave']) . "\n";
        echo "- nombre_clave (strlen): " . strlen($marca_opcion['nombre_clave']) . " caracteres\n";
    }

    echo "\n";
}
?>
