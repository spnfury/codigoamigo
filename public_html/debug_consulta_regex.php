<?php
/**
 * Debug de la nueva consulta con expresión regular
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Debug Nueva Consulta con Expresión Regular</h1>";

// Recrear exactamente la consulta que hace la página principal
$limit2 = 32;
$skip = 0;
$sort_order = array('fecha_publicacion' => -1);

// Nueva consulta con expresión regular para 2024 y 2025
$array_filtro = array(
    "estado" => 0,
    "fecha_publicacion" => array('$regex' => '^202[4-5]-')
);

$array_skip = array("limit" => $limit2);
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
        $id = isset($codigo['_id']) ? (string)$codigo['_id'] : 'SIN ID';

        $bg_color = strpos($fecha, '2025-') === 0 ? '#d4edda' : '#f8f9fa';
        $border_color = strpos($fecha, '2025-') === 0 ? '#28a745' : ($destacado > 0 ? '#ffc107' : '#E30613');

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

// Verificar códigos de 2025 específicamente
$codigos_2025 = array_filter($lista_codigos, function($codigo) {
    $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : '';
    return strpos($fecha, '2025-') === 0;
});

echo "<hr><h2>✅ Códigos de 2025 encontrados:</h2>";
echo "<p><strong>Total códigos de 2025:</strong> " . count($codigos_2025) . "</p>";

if (!empty($codigos_2025)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
    foreach ($codigos_2025 as $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;

        echo "<div style='margin: 8px 0; padding: 8px; background: white; border-left: 3px solid #28a745;'>";
        echo "✅ $marca | $fecha | Destacado: $destacado";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ No se encontraron códigos de 2025";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Ver página principal con nueva consulta</a></p>";
?>


