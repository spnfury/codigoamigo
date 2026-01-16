<?php
// Verificar específicamente coinbase y todas las marcas disponibles
require_once __DIR__ . '/public_html/inc/includes.php';

$collection_marcas = getCollectionMarcas();

echo "=== VERIFICACIÓN DE COINBASE Y TODAS LAS MARCAS ===\n\n";

// 1. Buscar coinbase específicamente
$marca_coinbase = $collection_marcas->findOne(['nombre_clave' => 'coinbase']);
if ($marca_coinbase) {
    echo "✅ Marca 'coinbase' encontrada:\n";
    echo "- ID: " . $marca_coinbase['_id'] . "\n";
    echo "- Nombre: '" . $marca_coinbase['nombre'] . "'\n";
    echo "- Nombre clave: '" . $marca_coinbase['nombre_clave'] . "'\n";
    echo "- Estado: " . $marca_coinbase['estado'] . "\n";
    echo "- Categoría: " . $marca_coinbase['categoria'] . "\n";
    echo "\n";
} else {
    echo "❌ Marca 'coinbase' NO encontrada\n\n";
}

// 2. Buscar marcas que contengan "coin"
$marcas_coin = $collection_marcas->find([
    'nombre_clave' => ['$regex' => 'coin', '$options' => 'i']
])->toArray();

echo "=== MARCAS CON 'COIN' EN NOMBRE CLAVE ===\n";
echo "Total: " . count($marcas_coin) . "\n\n";

foreach ($marcas_coin as $marca) {
    $nombre_limpio = trim($marca['nombre']);
    $categoria_limpia = trim($marca['categoria']);
    echo "- ID: {$marca['_id']}\n";
    echo "  Nombre: '$nombre_limpio'\n";
    echo "  Nombre clave: '{$marca['nombre_clave']}'\n";
    echo "  Estado: {$marca['estado']}\n";
    echo "  Categoría: '$categoria_limpia'\n";
    echo "\n";
}

// 3. Ver cuántas marcas activas hay realmente
$total_marcas_activas = $collection_marcas->countDocuments(['estado' => 1]);
echo "=== TOTAL DE MARCAS ACTIVAS ===\n";
echo "Total: $total_marcas_activas\n\n";

// 4. Simular la consulta actual con límite 2000
$marcas_limit_2000 = $collection_marcas->find(['estado' => 1], ['sort' => ['nombre' => 1], 'limit' => 2000])->toArray();
$total_con_limite = count($marcas_limit_2000);
echo "=== CON LÍMITE 2000 ===\n";
echo "Total devuelto: $total_con_limite\n";

$coinbase_en_limit_2000 = false;
foreach ($marcas_limit_2000 as $marca) {
    if ($marca['nombre_clave'] === 'coinbase') {
        $coinbase_en_limit_2000 = true;
        echo "✅ coinbase SÍ está en límite 2000\n";
        break;
    }
}

if (!$coinbase_en_limit_2000) {
    echo "❌ coinbase NO está en límite 2000\n";
}

// 5. Probar sin límite
$marcas_sin_limite = $collection_marcas->find(['estado' => 1], ['sort' => ['nombre' => 1]])->toArray();
$total_sin_limite = count($marcas_sin_limite);
echo "\n=== SIN LÍMITE ===\n";
echo "Total devuelto: $total_sin_limite\n";

$coinbase_sin_limite = false;
foreach ($marcas_sin_limite as $marca) {
    if ($marca['nombre_clave'] === 'coinbase') {
        $coinbase_sin_limite = true;
        echo "✅ coinbase SÍ está sin límite\n";
        break;
    }
}

if (!$coinbase_sin_limite) {
    echo "❌ coinbase NO está sin límite\n";
}
?>
