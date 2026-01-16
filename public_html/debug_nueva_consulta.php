<?php
/**
 * Debug de la nueva consulta que obtiene todos los códigos recientes
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Debug Nueva Consulta - Todos los Códigos Recientes</h1>";

// Recrear la nueva consulta que obtiene TODOS los códigos recientes
$limit2 = 16;
$skip = 0;
$sort_order = array('fecha_publicacion' => -1);

// Nueva consulta - TODOS los códigos recientes (sin filtro de destacado)
$array_filtro = array("estado" => 0);
$array_skip = array("limit" => $limit2 * 2); // Límite duplicado
$array_skip = array_merge($array_skip, array("skip" => $skip));
$array_skip = array_merge($array_skip, array("sort" => $sort_order));

echo "<h2>📋 Nueva consulta - parámetros:</h2>";
echo "<pre>";
echo "Filtro: " . json_encode($array_filtro, JSON_PRETTY_PRINT) . "\n";
echo "Opciones: " . json_encode($array_skip, JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

$lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
$lista_codigos = isset($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];

echo "<h2>📊 Códigos obtenidos (" . count($lista_codigos) . "):</h2>";
echo "<p><strong>Total según count:</strong> " . (isset($lista_codigos_pre["total_number"]) ? $lista_codigos_pre["total_number"] : 'NO DISPONIBLE') . "</p>";

if (!empty($lista_codigos)) {
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>";
    foreach ($lista_codigos as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $titulo = $codigo['titulo'] ?? 'SIN TITULO';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;

        $bg_color = $destacado > 0 ? '#fff3cd' : '#f8f9fa';
        $border_color = $destacado > 0 ? '#ffc107' : '#E30613';

        echo "<div style='margin: 10px 0; padding: 10px; background: $bg_color; border-left: 4px solid $border_color; border-radius: 5px;'>";
        echo "<strong>#" . ($index + 1) . "</strong> | ";
        echo "Marca: <strong>$marca</strong> | ";
        echo "Fecha: <strong>$fecha</strong> | ";
        echo "Destacado: <strong>$destacado</strong>";
        echo "<br><em>" . htmlspecialchars(substr($titulo, 0, 80)) . "...</em>";
        echo "</div>";
    }
    echo "</div>";
}

// Comparar con consulta anterior (solo normales)
echo "<hr><h2>🔍 Comparación con consulta anterior (solo normales):</h2>";
$array_filtro_antiguo = array("estado" => 0, "destacado" => 0);
$array_skip_antiguo = array("limit" => $limit2);
$array_skip_antiguo = array_merge($array_skip_antiguo, array("skip" => $skip));
$array_skip_antiguo = array_merge($array_skip_antiguo, array("sort" => $sort_order));

$lista_antigua_pre = get_all_listado_codigos_array($array_filtro_antiguo, $array_skip_antiguo);
$lista_antigua = isset($lista_antigua_pre["results"]) ? $lista_antigua_pre["results"] : [];

echo "<p><strong>Consulta antigua devolvió:</strong> " . count($lista_antigua) . " códigos normales</p>";

if (!empty($lista_antigua)) {
    echo "<div style='background: #e9ecef; padding: 15px; border-radius: 8px;'>";
    foreach ($lista_antigua as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';

        echo "<div style='margin: 8px 0; padding: 8px; background: white; border-left: 3px solid #007bff;'>";
        echo "#$index - $marca | $fecha";
        echo "</div>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<h2>✅ Verificación final:</h2>";
echo "<p>La nueva consulta obtiene <strong>" . count($lista_codigos) . "</strong> códigos recientes (incluyendo destacados)</p>";
echo "<p>La consulta antigua obtiene <strong>" . count($lista_antigua) . "</strong> códigos normales</p>";

$codigos_2025_nuevos = array_filter($lista_codigos, function($codigo) {
    return strpos($codigo['fecha_publicacion'] ?? '', '2025-') === 0;
});

echo "<p><strong>Códigos de 2025 en nueva consulta:</strong> " . count($codigos_2025_nuevos) . "</p>";

if (!empty($codigos_2025_nuevos)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
    foreach (array_slice($codigos_2025_nuevos, 0, 5) as $codigo) {
        $fecha = $codigo['fecha_publicacion'] ?? 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        echo "<div style='margin: 5px 0; padding: 5px; background: white; border-left: 3px solid #28a745;'>";
        echo "✅ $marca | $fecha";
        echo "</div>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Ver página principal con nueva consulta</a></p>";
?>


