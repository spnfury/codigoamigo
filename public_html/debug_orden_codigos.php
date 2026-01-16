<?php
/**
 * Script de debug para verificar el orden de códigos
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Debug Orden de Códigos</h1>";

// Parámetros como en la página principal
$limit2 = 16;
$skip = 0;
$sort_order = array('fecha_publicacion' => -1);

// Consulta exactamente igual a la de la página principal
$array_filtro = array("estado" => 0);
$array_filtro = array_merge($array_filtro, array("destacado" => 0));

$array_skip = array("limit" => $limit2);
$array_skip = array_merge($array_skip, array("skip" => $skip));
$array_skip = array_merge($array_skip, array("sort" => $sort_order));

echo "<h2>📋 Consulta realizada:</h2>";
echo "<pre>";
echo "Filtro: " . json_encode($array_filtro, JSON_PRETTY_PRINT) . "\n";
echo "Opciones: " . json_encode($array_skip, JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

// Ejecutar consulta
$lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
$lista_codigos = isset($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];

echo "<h2>📊 Códigos obtenidos (" . count($lista_codigos) . "):</h2>";

if (!empty($lista_codigos)) {
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>";
    foreach ($lista_codigos as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $titulo = $codigo['titulo'] ?? 'SIN TITULO';
        $descripcion = $codigo['descripcion'] ?? 'SIN DESCRIPCION';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;

        echo "<div style='margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #E30613;'>";
        echo "<strong>#" . ($index + 1) . "</strong> | ";
        echo "Marca: <strong>$marca</strong> | ";
        echo "Fecha: <strong>$fecha</strong> | ";
        echo "Destacado: <strong>$destacado</strong> | ";
        echo "ID: <small>" . ($codigo['_id'] ?? 'SIN ID') . "</small>";
        echo "<br><em>" . htmlspecialchars(substr($descripcion, 0, 100)) . "</em>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p style='color: red;'>❌ No se obtuvieron códigos</p>";
}

// También verificar códigos destacados para comparar
echo "<hr><h2>🔍 Códigos destacados (para comparar):</h2>";
$array_filtro_destacados = array("estado" => 0);
$array_opciones_destacados = array(
    "limit" => 5,
    "sort" => array('destacado_social' => -1, 'destacado' => -1, 'fecha_publicacion' => -1)
);

$lista_destacados_pre = get_all_listado_codigos_array($array_filtro_destacados, $array_opciones_destacados);
$lista_destacados = isset($lista_destacados_pre["results"]) ? $lista_destacados_pre["results"] : [];

if (!empty($lista_destacados)) {
    foreach ($lista_destacados as $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
        $destacado_social = isset($codigo['destacado_social']) ? $codigo['destacado_social'] : 0;

        echo "<div style='margin: 5px 0; padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107;'>";
        echo "⭐ $marca | Fecha: $fecha | Destacado: $destacado | Social: $destacado_social";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='test_orden_fecha.php' target='_blank'>🔍 Ver test completo de orden por fecha</a></p>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Volver al inicio</a></p>";
?>
