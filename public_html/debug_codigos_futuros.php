<?php
/**
 * Debug para verificar si hay códigos con fechas en el futuro que deberían aparecer primero
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Debug Códigos con Fechas en el Futuro</h1>";

$collection_codigos = getCollectionCodigos();

// 1. Buscar códigos con fechas muy en el futuro (después de hoy)
$hoy = date('Y-m-d');
$query_futuros = [
    'estado' => 0,
    'fecha_publicacion' => ['$gt' => $hoy]
];

$cursor_futuros = $collection_codigos->find($query_futuros, [
    'sort' => ['fecha_publicacion' => 1], // Orden ascendente para ver los más cercanos primero
    'limit' => 20
]);

$codigos_futuros = iterator_to_array($cursor_futuros);

echo "<h2>📅 Códigos con fechas en el futuro (después de $hoy):</h2>";
echo "<p><strong>Total encontrados:</strong> " . count($codigos_futuros) . "</p>";

if (!empty($codigos_futuros)) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px;'>";
    foreach ($codigos_futuros as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
        $id = isset($codigo['_id']) ? (string)$codigo['_id'] : 'SIN ID';

        echo "<div style='margin: 8px 0; padding: 8px; background: white; border-left: 3px solid #ffc107; border-radius: 4px;'>";
        echo "<strong>#" . ($index + 1) . "</strong> | ";
        echo "Marca: <strong>$marca</strong> | ";
        echo "Fecha: <strong>$fecha</strong> | ";
        echo "Destacado: <strong>$destacado</strong> | ";
        echo "ID: <small>$id</small>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "✅ No hay códigos con fechas en el futuro";
    echo "</div>";
}

// 2. Verificar códigos muy antiguos que podrían estar mal configurados
echo "<hr><h2>🔍 Códigos muy antiguos (antes de 2020) que podrían estar mal configurados:</h2>";

$query_antiguos = [
    'estado' => 0,
    'fecha_publicacion' => ['$lt' => '2020-01-01']
];

$cursor_antiguos = $collection_codigos->find($query_antiguos, [
    'sort' => ['fecha_publicacion' => -1],
    'limit' => 10
]);

$codigos_antiguos = iterator_to_array($cursor_antiguos);

echo "<p><strong>Total códigos muy antiguos:</strong> " . count($codigos_antiguos) . "</p>";

if (!empty($codigos_antiguos)) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px;'>";
    foreach ($codigos_antiguos as $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;

        echo "<div style='margin: 8px 0; padding: 8px; background: white; border-left: 3px solid #dc3545;'>";
        echo "⚠️ $marca | $fecha | Destacado: $destacado";
        echo "</div>";
    }
    echo "</div>";
}

// 3. Verificar distribución de fechas por año
echo "<hr><h2>📊 Distribución de códigos por año:</h2>";

$years = [];
for ($year = 2018; $year <= 2025; $year++) {
    $query_year = [
        'estado' => 0,
        'fecha_publicacion' => new MongoDB\BSON\Regex('^' . $year . '-')
    ];

    $count = $collection_codigos->count($query_year);
    if ($count > 0) {
        $years[$year] = $count;
    }
}

echo "<div style='background: #e9ecef; padding: 15px; border-radius: 8px;'>";
foreach ($years as $year => $count) {
    $color = $year >= 2024 ? '#28a745' : ($year >= 2022 ? '#ffc107' : '#dc3545');
    echo "<div style='margin: 5px 0; padding: 5px; background: white; border-left: 3px solid $color;'>";
    echo "📅 $year: <strong>$count</strong> códigos";
    echo "</div>";
}
echo "</div>";

// 4. Verificar si hay códigos sin fecha_publicacion
echo "<hr><h2>🔍 Códigos sin fecha_publicacion:</h2>";

$query_sin_fecha = [
    'estado' => 0,
    '$or' => [
        ['fecha_publicacion' => ['$exists' => false]],
        ['fecha_publicacion' => '']
    ]
];

$count_sin_fecha = $collection_codigos->count($query_sin_fecha);
echo "<p><strong>Total códigos sin fecha_publicacion:</strong> $count_sin_fecha</p>";

if ($count_sin_fecha > 0) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "⚠️ Hay $count_sin_fecha códigos sin fecha_publicacion que podrían estar mal configurados";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "✅ Todos los códigos tienen fecha_publicacion";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Ver página principal</a></p>";
?>


