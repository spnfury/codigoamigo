<?php
// Verificar específicamente la marca ubereats y el código del selector
require_once __DIR__ . '/public_html/inc/includes.php';

$collection_marcas = getCollectionMarcas();

echo "=== VERIFICACIÓN DETALLADA DE UBEREATS ===\n\n";

// 1. Buscar la marca exacta
$marca_ubereats = $collection_marcas->findOne(['nombre_clave' => 'ubereats']);
if ($marca_ubereats) {
    echo "✅ Marca 'ubereats' encontrada:\n";
    echo "- ID: " . $marca_ubereats['_id'] . "\n";
    echo "- Nombre: '" . $marca_ubereats['nombre'] . "'\n";
    echo "- Nombre clave: '" . $marca_ubereats['nombre_clave'] . "'\n";
    echo "- Estado: " . $marca_ubereats['estado'] . "\n";
    echo "- Categoría: " . $marca_ubereats['categoria'] . "\n";
    echo "\n";
} else {
    echo "❌ Marca 'ubereats' NO encontrada\n\n";
}

// 2. Verificar si existe alguna marca con nombre similar
$marcas_similares = $collection_marcas->find([
    'nombre_clave' => ['$regex' => 'uber', '$options' => 'i']
])->toArray();

echo "=== MARCAS CON 'UBER' EN NOMBRE CLAVE ===\n";
echo "Total: " . count($marcas_similares) . "\n\n";

foreach ($marcas_similares as $marca) {
    $nombre_limpio = trim($marca['nombre']);
    $categoria_limpia = trim($marca['categoria']);
    echo "- ID: {$marca['_id']}\n";
    echo "  Nombre: '$nombre_limpio'\n";
    echo "  Nombre clave: '{$marca['nombre_clave']}'\n";
    echo "  Estado: {$marca['estado']}\n";
    echo "  Categoría: '$categoria_limpia'\n";
    echo "  Aparecería en selector: " . ($marca['estado'] == 1 ? '✅ SÍ' : '❌ NO') . "\n";
    echo "\n";
}

// 3. Simular exactamente la consulta del selector
echo "=== SIMULACIÓN DE CONSULTA DEL SELECTOR ===\n";
$todas_marcas = $collection_marcas->find(['estado' => 1], ['sort' => ['nombre' => 1]])->toArray();
echo "Total marcas activas encontradas: " . count($todas_marcas) . "\n\n";

$encontrada = false;
foreach ($todas_marcas as $marca) {
    if (strtolower($marca['nombre_clave']) === 'ubereats') {
        $encontrada = true;
        echo "✅ 'ubereats' SÍ está en el resultado del selector:\n";
        echo "- ID: {$marca['_id']}\n";
        echo "- Nombre: '" . trim($marca['nombre']) . "'\n";
        echo "- Nombre clave: '{$marca['nombre_clave']}'\n";
        echo "- Estado: {$marca['estado']}\n";
        break;
    }
}

if (!$encontrada) {
    echo "❌ 'ubereats' NO está en el resultado del selector\n";
    echo "Esto indica un problema con la consulta o la marca no está activa\n\n";

    // Buscar posibles problemas
    $problemas = [];

    // Verificar si hay marcas con nombre_clave exactamente 'ubereats'
    $exacta = $collection_marcas->findOne(['nombre_clave' => 'ubereats']);
    if (!$exacta) {
        $problemas[] = "No existe marca con nombre_clave exactamente 'ubereats'";
    }

    // Verificar si está activa
    if ($exacta && $exacta['estado'] != 1) {
        $problemas[] = "La marca existe pero no está activa (estado: {$exacta['estado']})";
    }

    // Verificar si hay caracteres especiales o problemas de encoding
    if ($exacta) {
        $nombre_clave = $exacta['nombre_clave'];
        if (!preg_match('/^[a-z0-9]+$/', $nombre_clave)) {
            $problemas[] = "El nombre_clave contiene caracteres especiales: '$nombre_clave'";
        }
    }

    echo "Posibles problemas encontrados:\n";
    foreach ($problemas as $problema) {
        echo "- $problema\n";
    }
}
?>
