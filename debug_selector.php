<?php
// Debug específico del selector de marca destino
require_once __DIR__ . '/public_html/inc/includes.php';

$collection_marcas = getCollectionMarcas();
$collection_codigos = getCollectionCodigos();

echo "=== DEBUG DEL SELECTOR DE MARCA DESTINO ===\n\n";

// Simular la consulta exacta del selector
$todas_marcas = $collection_marcas->find(['estado' => 1], ['sort' => ['nombre' => 1]])->toArray();

echo "Total marcas activas: " . count($todas_marcas) . "\n\n";

// Buscar específicamente ubereats
$ubereats_encontrada = false;
foreach ($todas_marcas as $marca) {
    if ($marca['nombre_clave'] === 'ubereats') {
        $ubereats_encontrada = true;
        echo "✅ UBEREATS ENCONTRADA EN EL SELECTOR:\n";
        echo "- Posición en la lista: " . array_search($marca, $todas_marcas) . "\n";
        echo "- ID: {$marca['_id']}\n";
        echo "- Nombre: '" . trim($marca['nombre']) . "'\n";
        echo "- Nombre clave: '{$marca['nombre_clave']}'\n";
        echo "- Categoría: '{$marca['categoria']}'\n";
        break;
    }
}

if (!$ubereats_encontrada) {
    echo "❌ UBEREATS NO ENCONTRADA EN EL SELECTOR\n";
    echo "Esto indica que hay un problema con la consulta\n\n";
}

// Buscar marcas con nombre similar para ver el orden
echo "=== MARCAS CON NOMBRES SIMILARES (orden alfabético) ===\n";
$marcas_similares = [];
foreach ($todas_marcas as $marca) {
    $nombre_lower = strtolower(trim($marca['nombre']));
    if (strpos($nombre_lower, 'uber') !== false || strpos($nombre_lower, 'eat') !== false) {
        $marcas_similares[] = $marca;
    }
}

usort($marcas_similares, function($a, $b) {
    return strcmp(trim($a['nombre']), trim($b['nombre']));
});

foreach ($marcas_similares as $marca) {
    $nombre_limpio = trim($marca['nombre']);
    $categoria_limpia = trim($marca['categoria']);
    $es_ubereats = $marca['nombre_clave'] === 'ubereats' ? '⭐ UBEREATS' : '';
    echo "- $nombre_limpio ($categoria_limpia) - {$marca['nombre_clave']} $es_ubereats\n";
}

echo "\n";

// Verificar si hay algún problema con el límite de resultados
$total_marcas = count($todas_marcas);
$limite_por_defecto = 20; // Este es el límite típico de MongoDB si no se especifica

echo "=== VERIFICACIÓN DE LÍMITES ===\n";
echo "Total marcas en BD: $total_marcas\n";
echo "Límite típico de consulta: $limite_por_defecto\n";

if ($total_marcas > $limite_por_defecto) {
    echo "⚠️  POSIBLE PROBLEMA: Hay más marcas ($total_marcas) que el límite típico ($limite_por_defecto)\n";
    echo "La consulta podría estar limitada y no devolver todas las marcas\n\n";

    // Verificar si ubereats está dentro de los primeros resultados
    $primeros_20 = array_slice($todas_marcas, 0, 20);
    $ubereats_en_primeros = false;

    foreach ($primeros_20 as $marca) {
        if ($marca['nombre_clave'] === 'ubereats') {
            $ubereats_en_primeros = true;
            break;
        }
    }

    echo "Ubereats en primeros 20 resultados: " . ($ubereats_en_primeros ? '✅ SÍ' : '❌ NO') . "\n";
} else {
    echo "✅ Todas las marcas deberían estar disponibles\n";
}
?>
