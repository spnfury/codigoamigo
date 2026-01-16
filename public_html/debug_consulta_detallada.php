<?php
/**
 * Debug detallado de la consulta de códigos
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Debug Detallado de Consulta de Códigos</h1>";

// Parámetros EXACTAMENTE como en la página principal
$limit2 = 16;
$skip = 0;
$sort_order = array('fecha_publicacion' => -1);

// Recrear exactamente la misma consulta que hace la página principal
echo "<h2>📋 Recreando consulta de la página principal:</h2>";
echo "<pre>";
echo "\$limit2 = $limit2;\n";
echo "\$skip = $skip;\n";
echo "\$sort_order = " . var_export($sort_order, true) . ";\n";
echo "</pre>";

// Consulta PATROCINADOS/DESTACADOS
echo "<h3>🔍 Consulta DESTACADOS:</h3>";
$array_filtro_destacados = array("estado" => 0);
$array_skip_destacados = array(
    "limit" => 1000,
    "skip" => 0,
    "sort" => array('destacado_social' => -1, 'destacado' => -1, 'fecha_publicacion' => -1)
);

echo "<p><strong>Filtro:</strong> " . json_encode($array_filtro_destacados) . "</p>";
echo "<p><strong>Opciones:</strong> " . json_encode($array_skip_destacados) . "</p>";

$lista_codigos_patrocinados_pre = get_all_listado_codigos_array($array_filtro_destacados, $array_skip_destacados);
$lista_codigos_patrocinados = isset($lista_codigos_patrocinados_pre["results"]) ? $lista_codigos_patrocinados_pre["results"] : [];

echo "<p><strong>Total destacados encontrados:</strong> " . count($lista_codigos_patrocinados) . "</p>";
if (!empty($lista_codigos_patrocinados)) {
    echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    foreach ($lista_codigos_patrocinados as $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
        $destacado_social = isset($codigo['destacado_social']) ? $codigo['destacado_social'] : 0;
        echo "⭐ $marca | $fecha | Dest: $destacado | Social: $destacado_social<br>";
    }
    echo "</div>";
}

// Consulta NORMALES
echo "<hr><h3>🔍 Consulta NORMALES:</h3>";
$array_filtro = array("estado" => 0);
$array_filtro = array_merge($array_filtro, array("destacado" => 0));

$array_skip = array("limit" => $limit2);
$array_skip = array_merge($array_skip, array("skip" => $skip));
$array_skip = array_merge($array_skip, array("sort" => $sort_order));

echo "<p><strong>Filtro:</strong> " . json_encode($array_filtro) . "</p>";
echo "<p><strong>Opciones:</strong> " . json_encode($array_skip) . "</p>";

$lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
$lista_codigos = isset($lista_codigos_pre["results"]) && is_array($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];

echo "<p><strong>Total códigos encontrados:</strong> " . count($lista_codigos) . "</p>";
echo "<p><strong>Total según count:</strong> " . (isset($lista_codigos_pre["total_number"]) ? $lista_codigos_pre["total_number"] : 'NO DISPONIBLE') . "</p>";

// Mostrar códigos encontrados
if (!empty($lista_codigos)) {
    echo "<h4>📋 Códigos obtenidos (orden devuelto por MongoDB):</h4>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>";
    foreach ($lista_codigos as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $titulo = $codigo['titulo'] ?? 'SIN TITULO';
        $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
        $id = isset($codigo['_id']) ? (string)$codigo['_id'] : 'SIN ID';

        echo "<div style='margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #E30613; border-radius: 5px;'>";
        echo "<strong>#" . ($index + 1) . "</strong> | ";
        echo "Marca: <strong>$marca</strong> | ";
        echo "Fecha: <strong>$fecha</strong> | ";
        echo "ID: <small>$id</small> | ";
        echo "Destacado: <strong>$destacado</strong>";
        echo "<br><em>" . htmlspecialchars(substr($titulo, 0, 80)) . "...</em>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ No se obtuvieron códigos normales";
    echo "</div>";
}

// Verificar si el problema está en la consulta misma
echo "<hr><h2>🔬 Análisis de la consulta directa:</h2>";
echo "<p>Vamos a hacer la consulta directamente sin pasar por la función para ver si hay algún problema:</p>";

try {
    $collection_codigos = getCollectionCodigos();

    // Consulta directa con los mismos parámetros
    $cursor = $collection_codigos->find($array_filtro, $array_skip);
    $resultados_directos = iterator_to_array($cursor);

    echo "<p><strong>Consulta directa devolvió:</strong> " . count($resultados_directos) . " códigos</p>";

    if (!empty($resultados_directos)) {
        echo "<h4>📋 Resultados directos:</h4>";
        echo "<div style='background: #e9ecef; padding: 15px; border-radius: 8px;'>";
        foreach ($resultados_directos as $index => $codigo) {
            $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
            $marca = $codigo['marca'] ?? 'SIN MARCA';
            echo "<div style='margin: 5px 0; padding: 8px; background: white; border-left: 3px solid #007bff;'>";
            echo "#$index - $marca | $fecha";
            echo "</div>";
        }
        echo "</div>";
    }

    // También verificar consulta sin límite para ver el orden natural
    echo "<h4>🔍 Consulta sin límite (primeros 20):</h4>";
    $cursor_sin_limite = $collection_codigos->find($array_filtro, [
        'sort' => $sort_order,
        'limit' => 20
    ]);
    $resultados_sin_limite = iterator_to_array($cursor_sin_limite);

    if (!empty($resultados_sin_limite)) {
        foreach ($resultados_sin_limite as $index => $codigo) {
            $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
            $marca = $codigo['marca'] ?? 'SIN MARCA';
            echo "<div style='margin: 3px 0; padding: 5px; background: #d1ecf1; border-left: 2px solid #17a2b8;'>";
            echo "#$index - $marca | $fecha";
            echo "</div>";
        }
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ Error en consulta directa: " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='debug_pagina_principal.php' target='_blank'>🔍 Ver debug página principal</a></p>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Volver al inicio</a></p>";
?>
