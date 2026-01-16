<?php
/**
 * Debug del orden actual en la página principal
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Debug Orden Actual - Página Principal</h1>";

// Recrear exactamente la consulta que hace la página principal
$limit2 = 32; // Límite duplicado que establecí
$skip = 0;
$sort_order = array('fecha_publicacion' => -1);

// Nueva consulta - TODOS los códigos recientes (sin filtro de destacado)
$array_filtro = array("estado" => 0);
$array_skip = array("limit" => $limit2);
$array_skip = array_merge($array_skip, array("skip" => $skip));
$array_skip = array_merge($array_skip, array("sort" => $sort_order));

echo "<h2>📋 Consulta actual de la página principal:</h2>";
echo "<pre>";
echo "Filtro: " . json_encode($array_filtro, JSON_PRETTY_PRINT) . "\n";
echo "Opciones: " . json_encode($array_skip, JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

$lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
$lista_codigos = isset($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];

echo "<h2>📊 Códigos que se están mostrando actualmente (" . count($lista_codigos) . "):</h2>";
echo "<p><strong>Total según count:</strong> " . (isset($lista_codigos_pre["total_number"]) ? $lista_codigos_pre["total_number"] : 'NO DISPONIBLE') . "</p>";

if (!empty($lista_codigos)) {
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>";
    foreach ($lista_codigos as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $titulo = $codigo['titulo'] ?? 'SIN TITULO';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
        $id = isset($codigo['_id']) ? (string)$codigo['_id'] : 'SIN ID';

        $bg_color = $destacado > 0 ? '#fff3cd' : '#f8f9fa';
        $border_color = $destacado > 0 ? '#ffc107' : '#E30613';

        echo "<div style='margin: 10px 0; padding: 10px; background: $bg_color; border-left: 4px solid $border_color; border-radius: 5px;'>";
        echo "<strong>#" . ($index + 1) . "</strong> | ";
        echo "Marca: <strong>$marca</strong> | ";
        echo "Fecha: <strong>$fecha</strong> | ";
        echo "Destacado: <strong>$destacado</strong> | ";
        echo "ID: <small>$id</small>";
        echo "<br><em>" . htmlspecialchars(substr($titulo, 0, 80)) . "...</em>";
        echo "</div>";
    }
    echo "</div>";
}

// Verificar si hay códigos más recientes que no se están mostrando
echo "<hr><h2>🔍 Verificar códigos más recientes disponibles:</h2>";

$collection_codigos = getCollectionCodigos();

// Consulta sin límite para ver los verdaderamente más recientes
$query_todos_recientes = [
    'estado' => 0,
    'fecha_publicacion' => ['$exists' => true, '$ne' => '']
];

$cursor_todos = $collection_codigos->find($query_todos_recientes, [
    'sort' => ['fecha_publicacion' => -1],
    'limit' => 10
]);

$todos_recientes = iterator_to_array($cursor_todos);

echo "<h3>📅 Los 10 códigos más recientes de la base de datos:</h3>";
if (!empty($todos_recientes)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
    foreach ($todos_recientes as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;

        echo "<div style='margin: 8px 0; padding: 8px; background: white; border-left: 3px solid #28a745;'>";
        echo "<strong>#" . ($index + 1) . "</strong> - $marca | $fecha | Destacado: $destacado";
        echo "</div>";
    }
    echo "</div>";
}

// Comparar con lo que se está mostrando
echo "<hr><h2>🔍 Comparación:</h2>";
echo "<h3>Códigos que se están mostrando (primeros 5):</h3>";
echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
for ($i = 0; $i < min(5, count($lista_codigos)); $i++) {
    $codigo = $lista_codigos[$i];
    $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
    $marca = $codigo['marca'] ?? 'SIN MARCA';
    echo "#$i - $marca | $fecha<br>";
}
echo "</div>";

echo "<h3>Códigos más recientes de la BD (primeros 5):</h3>";
echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
for ($i = 0; $i < min(5, count($todos_recientes)); $i++) {
    $codigo = $todos_recientes[$i];
    $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
    $marca = $codigo['marca'] ?? 'SIN MARCA';
    echo "#$i - $marca | $fecha<br>";
}
echo "</div>";

// Verificar si los primeros códigos mostrados coinciden con los más recientes
$coincide_orden = true;
for ($i = 0; $i < min(3, count($lista_codigos), count($todos_recientes)); $i++) {
    $mostrado = $lista_codigos[$i];
    $reciente = $todos_recientes[$i];

    $mostrado_fecha = isset($mostrado['fecha_publicacion']) ? $mostrado['fecha_publicacion'] : '';
    $reciente_fecha = isset($reciente['fecha_publicacion']) ? $reciente['fecha_publicacion'] : '';

    if ($mostrado_fecha !== $reciente_fecha) {
        $coincide_orden = false;
        break;
    }
}

echo "<div style='background: " . ($coincide_orden ? '#d4edda' : '#f8d7da') . "; color: " . ($coincide_orden ? '#155724' : '#721c24') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo $coincide_orden ? "✅ El orden es correcto - Los códigos mostrados coinciden con los más recientes" : "❌ El orden es incorrecto - Los códigos mostrados NO coinciden con los más recientes";
echo "</div>";

echo "<hr>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Ver página principal</a></p>";
?>
