<?php
/**
 * Debug detallado de códigos de 2025
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Debug Detallado Códigos de 2025</h1>";

$collection_codigos = getCollectionCodigos();

// 1. Mostrar códigos de 2025 ordenados por fecha DESC
echo "<h2>📅 Códigos de 2025 ordenados por fecha (más recientes primero):</h2>";

$query_2025 = [
    'estado' => 0,
    'fecha_publicacion' => new MongoDB\BSON\Regex('^2025-')
];

$cursor_2025 = $collection_codigos->find($query_2025, [
    'sort' => ['fecha_publicacion' => -1],
    'limit' => 10
]);

$codigos_2025 = iterator_to_array($cursor_2025);

echo "<p><strong>Total códigos de 2025:</strong> " . count($codigos_2025) . "</p>";

if (!empty($codigos_2025)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
    foreach ($codigos_2025 as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
        $id = isset($codigo['_id']) ? (string)$codigo['_id'] : 'SIN ID';
        $timestamp = strtotime($fecha);

        echo "<div style='margin: 8px 0; padding: 8px; background: white; border-left: 3px solid #28a745; border-radius: 4px;'>";
        echo "<strong>#" . ($index + 1) . "</strong> | ";
        echo "Marca: <strong>$marca</strong> | ";
        echo "Fecha: <strong>$fecha</strong> | ";
        echo "Timestamp: <strong>$timestamp</strong> | ";
        echo "Destacado: <strong>$destacado</strong> | ";
        echo "ID: <small>$id</small>";
        echo "</div>";
    }
    echo "</div>";
}

// 2. Comparar timestamps con códigos de 2024
echo "<hr><h2>🔍 Comparación de timestamps:</h2>";

$fecha_2024 = strtotime('2024-12-31 14:19');
$fecha_2025_hoy = strtotime('2025-10-03 00:06:51');

echo "<div style='background: #e9ecef; padding: 15px; border-radius: 8px;'>";
echo "<p><strong>Timestamp código 2024:</strong> $fecha_2024</p>";
echo "<p><strong>Timestamp código 2025:</strong> $fecha_2025_hoy</p>";
echo "<p><strong>Diferencia:</strong> " . ($fecha_2025_hoy - $fecha_2024) . " segundos</p>";
echo "<p><strong>¿2025 es más reciente?</strong> " . ($fecha_2025_hoy > $fecha_2024 ? '✅ SÍ' : '❌ NO') . "</p>";
echo "</div>";

// 3. Verificar si estos códigos aparecen en la consulta principal
echo "<hr><h2>🔍 Verificar si códigos de 2025 aparecen en consulta principal:</h2>";

$array_filtro_principal = array("estado" => 0);
$array_skip_principal = array(
    "limit" => 32,
    "skip" => 0,
    "sort" => array('fecha_publicacion' => -1)
);

$lista_principal_pre = get_all_listado_codigos_array($array_filtro_principal, $array_skip_principal);
$lista_principal = isset($lista_principal_pre["results"]) ? $lista_principal_pre["results"] : [];

$codigos_2025_en_principal = 0;
foreach ($lista_principal as $codigo) {
    $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : '';
    if (strpos($fecha, '2025-') === 0) {
        $codigos_2025_en_principal++;
    }
}

echo "<p><strong>Códigos de 2025 en consulta principal:</strong> $codigos_2025_en_principal</p>";

if ($codigos_2025_en_principal > 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "✅ Los códigos de 2025 SÍ aparecen en la consulta principal";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ Los códigos de 2025 NO aparecen en la consulta principal";
    echo "</div>";
}

// 4. Verificar consulta directa vs función
echo "<hr><h2>🔍 Consulta directa vs función:</h2>";

$cursor_directo = $collection_codigos->find($array_filtro_principal, $array_skip_principal);
$resultados_directos = iterator_to_array($cursor_directo);

echo "<p><strong>Consulta directa devolvió:</strong> " . count($resultados_directos) . " códigos</p>";
echo "<p><strong>Función devolvió:</strong> " . count($lista_principal) . " códigos</p>";

$codigos_2025_directos = 0;
foreach ($resultados_directos as $codigo) {
    $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : '';
    if (strpos($fecha, '2025-') === 0) {
        $codigos_2025_directos++;
    }
}

echo "<p><strong>Códigos de 2025 en consulta directa:</strong> $codigos_2025_directos</p>";

echo "<hr>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Ver página principal</a></p>";
?>


