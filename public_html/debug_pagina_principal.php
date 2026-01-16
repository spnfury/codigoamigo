<?php
/**
 * Script para debuggear exactamente lo que se muestra en la página principal
 */

// Simular las mismas condiciones que la página principal
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';
include_once __DIR__ . '/myphp/funciones_modern.php';

echo "<h1>🏠 Debug Página Principal - Últimos Códigos</h1>";

// Simular las mismas variables que usa la página principal
$limit2 = 16;
$skip = 0;
$sort_order = array('fecha_publicacion' => -1);

// Consulta PATROCINADOS/DESTACADOS (igual que página principal)
$array_filtro_destacados = array("estado" => 0);
$array_skip_destacados = array(
    "limit" => 1000,
    "skip" => 0,
    "sort" => array('destacado_social' => -1, 'destacado' => -1, 'fecha_publicacion' => -1)
);

$lista_codigos_patrocinados_pre = get_all_listado_codigos_array($array_filtro_destacados, $array_skip_destacados);
$lista_codigos_patrocinados = isset($lista_codigos_patrocinados_pre["results"]) ? $lista_codigos_patrocinados_pre["results"] : [];

// Consulta NORMALES (igual que página principal)
$array_filtro = array("estado" => 0);
$array_filtro = array_merge($array_filtro, array("destacado" => 0));

$array_skip = array("limit" => $limit2);
$array_skip = array_merge($array_skip, array("skip" => $skip));
$array_skip = array_merge($array_skip, array("sort" => $sort_order));

$lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
$lista_codigos = isset($lista_codigos_pre["results"]) && is_array($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];

echo "<h2>📊 Códigos Patrocinados/Destacados (" . count($lista_codigos_patrocinados) . "):</h2>";
if (!empty($lista_codigos_patrocinados)) {
    foreach ($lista_codigos_patrocinados as $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $titulo = $codigo['titulo'] ?? 'SIN TITULO';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
        $destacado_social = isset($codigo['destacado_social']) ? $codigo['destacado_social'] : 0;

        echo "<div style='margin: 5px 0; padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107;'>";
        echo "⭐ $marca | Fecha: $fecha | Destacado: $destacado | Social: $destacado_social";
        echo "<br><small>" . htmlspecialchars(substr($titulo, 0, 100)) . "</small>";
        echo "</div>";
    }
}

echo "<hr><h2>📋 Códigos Normales que se muestran en 'Últimos Códigos' (" . count($lista_codigos) . "):</h2>";

// Filtrar solo códigos normales (como hace la página principal)
$codigos_normales = array_values(array_filter($lista_codigos, function($codigo) {
    return !isset($codigo['destacado']) || $codigo['destacado'] == 0;
}));

echo "<p><strong>Total códigos normales después del filtro:</strong> " . count($codigos_normales) . "</p>";

if (!empty($codigos_normales)) {
    foreach ($codigos_normales as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $titulo = $codigo['titulo'] ?? 'SIN TITULO';
        $descripcion = $codigo['descripcion'] ?? 'SIN DESCRIPCION';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;

        echo "<div style='margin: 10px 0; padding: 15px; background: white; border-left: 4px solid #E30613; border-radius: 8px;'>";
        echo "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;'>";
        echo "<strong>#" . ($index + 1) . " - $marca</strong>";
        echo "<span style='background: #e9ecef; padding: 2px 8px; border-radius: 12px; font-size: 0.8em;'>Fecha: $fecha</span>";
        echo "</div>";
        echo "<h4>" . htmlspecialchars($titulo) . "</h4>";
        echo "<p>" . htmlspecialchars(substr($descripcion, 0, 150)) . "...</p>";
        echo "<small>ID: " . ($codigo['_id'] ?? 'SIN ID') . " | Destacado: $destacado</small>";
        echo "</div>";
    }
}

// Verificar si hay algún problema con el orden visual
echo "<hr><h2>🔍 Verificación de orden:</h2>";
$fechas_ordenadas = array_column($codigos_normales, 'fecha_publicacion');
echo "<p>Fechas en orden: " . implode(" → ", $fechas_ordenadas) . "</p>";

// Verificar si las fechas están correctamente ordenadas
$fechas_timestamp = [];
foreach ($codigos_normales as $codigo) {
    $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : '';
    if ($fecha) {
        $timestamp = strtotime($fecha);
        $fechas_timestamp[] = $timestamp;
    }
}

$ordenado_correctamente = $fechas_timestamp === array_reverse($fechas_timestamp) || empty($fechas_timestamp);
echo "<p style='color: " . ($ordenado_correctamente ? 'green' : 'red') . "'>";
echo "✅ Las fechas están ordenadas correctamente (más reciente primero)";
echo "</p>";

if (!$ordenado_correctamente) {
    echo "<p style='color: red;'>❌ Las fechas NO están ordenadas correctamente</p>";
    echo "<p>Debug - Timestamps: " . implode(", ", $fechas_timestamp) . "</p>";
}

echo "<hr>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Ver página principal real</a></p>";
?>
