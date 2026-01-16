<?php
/**
 * Debug específico para verificar códigos de 2025
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Debug Códigos de 2025</h1>";

$collection_codigos = getCollectionCodigos();

// 1. Buscar códigos con fechas de 2025
echo "<h2>🔍 Códigos con fechas de 2025:</h2>";
$query_2025 = [
    'estado' => 0,
    'fecha_publicacion' => new MongoDB\BSON\Regex('^2025-')
];

$cursor_2025 = $collection_codigos->find($query_2025, [
    'sort' => ['fecha_publicacion' => -1],
    'limit' => 20
]);

$codigos_2025 = iterator_to_array($cursor_2025);

echo "<p><strong>Total códigos de 2025 encontrados:</strong> " . count($codigos_2025) . "</p>";

if (!empty($codigos_2025)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
    foreach ($codigos_2025 as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $titulo = $codigo['titulo'] ?? 'SIN TITULO';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;

        echo "<div style='margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #28a745; border-radius: 5px;'>";
        echo "<strong>#" . ($index + 1) . "</strong> | ";
        echo "Marca: <strong>$marca</strong> | ";
        echo "Fecha: <strong>$fecha</strong> | ";
        echo "Destacado: <strong>$destacado</strong>";
        echo "<br><em>" . htmlspecialchars(substr($titulo, 0, 80)) . "...</em>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ No se encontraron códigos con fechas de 2025";
    echo "</div>";
}

// 2. Buscar códigos destacados con fechas en el futuro (como vimos antes)
echo "<hr><h2>🔍 Códigos destacados con fechas sospechosas (futuro):</h2>";
$query_destacados_futuro = [
    'estado' => 0,
    'fecha_publicacion' => new MongoDB\BSON\Regex('^2025-'),
    'destacado' => ['$ne' => 0]
];

$cursor_destacados_futuro = $collection_codigos->find($query_destacados_futuro, [
    'sort' => ['fecha_publicacion' => -1],
    'limit' => 10
]);

$codigos_destacados_futuro = iterator_to_array($cursor_destacados_futuro);

echo "<p><strong>Total códigos destacados con fechas de 2025:</strong> " . count($codigos_destacados_futuro) . "</p>";

if (!empty($codigos_destacados_futuro)) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px;'>";
    foreach ($codigos_destacados_futuro as $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
        $destacado_social = isset($codigo['destacado_social']) ? $codigo['destacado_social'] : 0;

        echo "<div style='margin: 8px 0; padding: 8px; background: white; border-left: 3px solid #ffc107;'>";
        echo "⭐ $marca | Fecha: $fecha | Destacado: $destacado | Social: $destacado_social";
        echo "</div>";
    }
    echo "</div>";
}

// 3. Verificar consulta normal vs consulta corregida
echo "<hr><h2>🔍 Comparación: Consulta normal vs corregida</h2>";

echo "<h3>Consulta normal (sin filtro destacado):</h3>";
$query_normal = [
    'estado' => 0,
    'fecha_publicacion' => new MongoDB\BSON\Regex('^2025-')
];

$cursor_normal = $collection_codigos->find($query_normal, [
    'sort' => ['fecha_publicacion' => -1],
    'limit' => 10
]);

$codigos_normal_2025 = iterator_to_array($cursor_normal);

echo "<p><strong>Códigos normales de 2025:</strong> " . count($codigos_normal_2025) . "</p>";
if (!empty($codigos_normal_2025)) {
    foreach ($codigos_normal_2025 as $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
        echo "<div style='margin: 5px 0; padding: 5px; background: #e9ecef; border-left: 3px solid #6c757d;'>";
        echo "$marca | $fecha | Destacado: $destacado";
        echo "</div>";
    }
}

// 4. Verificar si el problema está en fechas mal formateadas
echo "<hr><h2>🔍 Verificar fechas mal formateadas:</h2>";
$query_fechas_invalidas = [
    'estado' => 0,
    '$or' => [
        ['fecha_publicacion' => ['$exists' => false]],
        ['fecha_publicacion' => '']
    ]
];

$cursor_invalidas = $collection_codigos->find($query_fechas_invalidas, ['limit' => 10]);
$codigos_sin_fecha = iterator_to_array($cursor_invalidas);

echo "<p><strong>Códigos sin fecha_publicacion:</strong> " . count($codigos_sin_fecha) . "</p>";

if (!empty($codigos_sin_fecha)) {
    foreach ($codigos_sin_fecha as $codigo) {
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $fecha_creacion = isset($codigo['fecha_creacion']) ? $codigo['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s') : 'SIN FECHA CREACION';
        echo "<div style='margin: 5px 0; padding: 5px; background: #f8d7da; border-left: 3px solid #dc3545;'>";
        echo "⚠️ $marca | Creado: $fecha_creacion | Sin fecha_publicacion";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Volver al inicio</a></p>";
?>


