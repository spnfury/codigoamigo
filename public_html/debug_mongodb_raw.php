<?php
/**
 * Debug directo con MongoDB para verificar fechas
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
require_once __DIR__ . '/vendor/autoload.php';

try {
    $mongo = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $collection = $mongo->codigo_db->codigos;

    echo "<h1>🔍 Debug MongoDB Raw - Fechas</h1>";

    // 1. Verificar códigos de 2025 con expresión regular
    echo "<h2>📅 Códigos de 2025 (expresión regular):</h2>";
    $cursor_2025_regex = $collection->find([
        'estado' => 0,
        'fecha_publicacion' => new MongoDB\BSON\Regex('^2025-')
    ], [
        'sort' => ['fecha_publicacion' => -1],
        'limit' => 5
    ]);

    $codigos_2025_regex = iterator_to_array($cursor_2025_regex);

    foreach ($codigos_2025_regex as $codigo) {
        $fecha = $codigo['fecha_publicacion'] ?? 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        echo "<div style='margin: 5px 0; padding: 5px; background: #d4edda; border-left: 3px solid #28a745;'>";
        echo "✅ $marca | $fecha";
        echo "</div>";
    }

    // 2. Verificar códigos con fecha >= 2025-01-01
    echo "<hr><h2>📅 Códigos con fecha >= 2025-01-01:</h2>";
    $cursor_2025_gte = $collection->find([
        'estado' => 0,
        'fecha_publicacion' => ['$gte' => '2025-01-01']
    ], [
        'sort' => ['fecha_publicacion' => -1],
        'limit' => 5
    ]);

    $codigos_2025_gte = iterator_to_array($cursor_2025_gte);

    echo "<p><strong>Encontrados con $gte:</strong> " . count($codigos_2025_gte) . "</p>";

    foreach ($codigos_2025_gte as $codigo) {
        $fecha = $codigo['fecha_publicacion'] ?? 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        echo "<div style='margin: 5px 0; padding: 5px; background: #f8d7da; border-left: 3px solid #dc3545;'>";
        echo "❌ $marca | $fecha";
        echo "</div>";
    }

    // 3. Comparar fechas directamente
    echo "<hr><h2>🔍 Comparación directa de fechas:</h2>";

    $fecha_2024 = '2024-12-31 23:59:59';
    $fecha_2025 = '2025-01-01 00:00:00';

    echo "<div style='background: #e9ecef; padding: 15px; border-radius: 8px;'>";
    echo "<p><strong>Fecha 2024:</strong> $fecha_2024</p>";
    echo "<p><strong>Fecha 2025:</strong> $fecha_2025</p>";
    echo "<p><strong>Comparación string:</strong> " . ($fecha_2025 > $fecha_2024 ? '2025 > 2024' : '2024 > 2025') . "</p>";
    echo "<p><strong>Timestamp 2024:</strong> " . strtotime($fecha_2024) . "</p>";
    echo "<p><strong>Timestamp 2025:</strong> " . strtotime($fecha_2025) . "</p>";
    echo "</div>";

    // 4. Verificar consulta sin límite
    echo "<hr><h2>📅 Todos los códigos ordenados por fecha (primeros 20):</h2>";
    $cursor_todos = $collection->find([
        'estado' => 0,
        'fecha_publicacion' => ['$exists' => true, '$ne' => '']
    ], [
        'sort' => ['fecha_publicacion' => -1],
        'limit' => 20
    ]);

    $todos_ordenados = iterator_to_array($cursor_todos);

    foreach ($todos_ordenados as $index => $codigo) {
        $fecha = $codigo['fecha_publicacion'] ?? 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $destacado = $codigo['destacado'] ?? 0;

        $bg_color = strpos($fecha, '2025-') === 0 ? '#d4edda' : '#f8f9fa';
        $border_color = strpos($fecha, '2025-') === 0 ? '#28a745' : '#6c757d';

        echo "<div style='margin: 5px 0; padding: 5px; background: $bg_color; border-left: 3px solid $border_color;'>";
        echo "#$index - $marca | $fecha | Destacado: $destacado";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ Error de conexión: " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Ver página principal</a></p>";
?>
