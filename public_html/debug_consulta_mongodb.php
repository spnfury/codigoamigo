<?php
/**
 * Debug directo de la consulta MongoDB para ver qué está pasando exactamente
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';

echo "<h1>🔍 Debug Consulta MongoDB Directa</h1>";

$collection_codigos = getCollectionCodigos();

// 1. Consulta básica sin filtros adicionales
echo "<h2>📋 Consulta básica (solo estado=0):</h2>";
$query_basica = ['estado' => 0];

$cursor_basica = $collection_codigos->find($query_basica, [
    'sort' => ['fecha_publicacion' => -1],
    'limit' => 10
]);

$resultados_basica = iterator_to_array($cursor_basica);

echo "<p><strong>Consulta básica devolvió:</strong> " . count($resultados_basica) . " códigos</p>";

foreach ($resultados_basica as $index => $codigo) {
    $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
    $marca = $codigo['marca'] ?? 'SIN MARCA';
    $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
    $id = isset($codigo['_id']) ? (string)$codigo['_id'] : 'SIN ID';

    echo "<div style='margin: 5px 0; padding: 5px; background: white; border-left: 3px solid #007bff;'>";
    echo "#$index - $marca | $fecha | Destacado: $destacado | ID: $id";
    echo "</div>";
}

// 2. Consulta con filtro de fecha específica
echo "<hr><h2>📋 Consulta con filtro de fecha específica:</h2>";
$query_fecha = [
    'estado' => 0,
    'fecha_publicacion' => ['$gte' => '2025-01-01']
];

$cursor_fecha = $collection_codigos->find($query_fecha, [
    'sort' => ['fecha_publicacion' => -1],
    'limit' => 10
]);

$resultados_fecha = iterator_to_array($cursor_fecha);

echo "<p><strong>Consulta con filtro fecha devolvió:</strong> " . count($resultados_fecha) . " códigos</p>";

foreach ($resultados_fecha as $index => $codigo) {
    $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
    $marca = $codigo['marca'] ?? 'SIN MARCA';
    $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;

    echo "<div style='margin: 5px 0; padding: 5px; background: white; border-left: 3px solid #28a745;'>";
    echo "#$index - $marca | $fecha | Destacado: $destacado";
    echo "</div>";
}

// 3. Verificar el tipo de dato de fecha_publicacion
echo "<hr><h2>🔍 Verificar tipo de dato fecha_publicacion:</h2>";
$query_tipo_fecha = [
    'estado' => 0,
    'fecha_publicacion' => ['$exists' => true]
];

$cursor_tipo = $collection_codigos->find($query_tipo_fecha, [
    'projection' => ['fecha_publicacion' => 1, 'marca' => 1],
    'limit' => 5
]);

foreach ($cursor_tipo as $codigo) {
    $fecha = $codigo['fecha_publicacion'];
    $marca = $codigo['marca'] ?? 'SIN MARCA';

    echo "<div style='margin: 5px 0; padding: 5px; background: white; border-left: 3px solid #ffc107;'>";
    echo "$marca | Fecha: $fecha | Tipo: " . gettype($fecha);
    if (is_object($fecha)) {
        echo " | Clase: " . get_class($fecha);
    }
    echo "</div>";
}

// 4. Verificar consulta con expresión regular
echo "<hr><h2>📋 Consulta con expresión regular para 2025:</h2>";
$query_regex = [
    'estado' => 0,
    'fecha_publicacion' => new MongoDB\BSON\Regex('^2025-')
];

$cursor_regex = $collection_codigos->find($query_regex, [
    'sort' => ['fecha_publicacion' => -1],
    'limit' => 10
]);

$resultados_regex = iterator_to_array($cursor_regex);

echo "<p><strong>Consulta con regex devolvió:</strong> " . count($resultados_regex) . " códigos</p>";

foreach ($resultados_regex as $index => $codigo) {
    $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
    $marca = $codigo['marca'] ?? 'SIN MARCA';
    $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;

    echo "<div style='margin: 5px 0; padding: 5px; background: white; border-left: 3px solid #dc3545;'>";
    echo "#$index - $marca | $fecha | Destacado: $destacado";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Ver página principal</a></p>";
?>


