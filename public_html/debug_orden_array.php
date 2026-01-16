<?php
/**
 * Debug específico para verificar el orden del array después de iterator_to_array
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Debug Orden Array - Verificación Paso a Paso</h1>";

// Recrear exactamente la consulta de la página principal
$array_filtro = array("estado" => 0, "destacado" => 0);
$array_skip = array(
    "limit" => 16,
    "skip" => 0,
    "sort" => array('fecha_publicacion' => -1)
);

echo "<h2>📋 Parámetros de consulta:</h2>";
echo "<pre>";
echo "Filtro: " . json_encode($array_filtro, JSON_PRETTY_PRINT) . "\n";
echo "Opciones: " . json_encode($array_skip, JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

$collection_codigos = getCollectionCodigos();

// 1. Obtener el cursor directamente
echo "<h3>🔍 Paso 1: Cursor de MongoDB</h3>";
$cursor = $collection_codigos->find($array_filtro, $array_skip);
echo "<p><strong>Cursor obtenido:</strong> " . get_class($cursor) . "</p>";

// 2. Convertir a array
echo "<h3>🔍 Paso 2: Convertir a array con iterator_to_array()</h3>";
$lista_codigos_array = iterator_to_array($cursor);
echo "<p><strong>Total elementos después de iterator_to_array:</strong> " . count($lista_codigos_array) . "</p>";

// 3. Verificar orden del array
echo "<h3>🔍 Paso 3: Verificar orden del array</h3>";
if (!empty($lista_codigos_array)) {
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>";
    foreach ($lista_codigos_array as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $id = isset($codigo['_id']) ? (string)$codigo['_id'] : 'SIN ID';

        echo "<div style='margin: 8px 0; padding: 8px; background: white; border-left: 4px solid #E30613; border-radius: 4px;'>";
        echo "<strong>Índice $index:</strong> $marca | $fecha | ID: $id";
        echo "</div>";
    }
    echo "</div>";
}

// 4. Comparar con función get_all_listado_codigos_array
echo "<hr><h3>🔍 Paso 4: Comparación con función get_all_listado_codigos_array()</h3>";
$lista_funcion_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
$lista_funcion = isset($lista_funcion_pre["results"]) ? $lista_funcion_pre["results"] : [];

echo "<p><strong>Función devolvió:</strong> " . count($lista_funcion) . " códigos</p>";

if (!empty($lista_funcion)) {
    echo "<div style='background: #e9ecef; padding: 15px; border-radius: 8px;'>";
    foreach ($lista_funcion as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $id = isset($codigo['_id']) ? (string)$codigo['_id'] : 'SIN ID';

        echo "<div style='margin: 8px 0; padding: 8px; background: white; border-left: 4px solid #007bff; border-radius: 4px;'>";
        echo "<strong>Índice $index:</strong> $marca | $fecha | ID: $id";
        echo "</div>";
    }
    echo "</div>";
}

// 5. Verificar si hay diferencias en el procesamiento
echo "<hr><h3>🔍 Paso 5: Comparación detallada</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th>Índice</th>";
echo "<th>Array Directo - Marca</th>";
echo "<th>Array Directo - Fecha</th>";
echo "<th>Función - Marca</th>";
echo "<th>Función - Fecha</th>";
echo "<th>¿Iguales?</th>";
echo "</tr>";

for ($i = 0; $i < min(count($lista_codigos_array), count($lista_funcion)); $i++) {
    $directo = $lista_codigos_array[$i];
    $funcion = $lista_funcion[$i];

    $directo_marca = $directo['marca'] ?? 'SIN MARCA';
    $directo_fecha = $directo['fecha_publicacion'] ?? 'SIN FECHA';
    $funcion_marca = $funcion['marca'] ?? 'SIN MARCA';
    $funcion_fecha = $funcion['fecha_publicacion'] ?? 'SIN FECHA';

    $son_iguales = ($directo_marca === $funcion_marca && $directo_fecha === $funcion_fecha) ? '✅' : '❌';

    echo "<tr>";
    echo "<td>$i</td>";
    echo "<td>$directo_marca</td>";
    echo "<td>$directo_fecha</td>";
    echo "<td>$funcion_marca</td>";
    echo "<td>$funcion_fecha</td>";
    echo "<td>$son_iguales</td>";
    echo "</tr>";
}

echo "</table>";

// 6. Verificar si el problema está en el procesamiento posterior
echo "<hr><h3>🔍 Paso 6: Procesamiento posterior en página principal</h3>";
echo "<p>La página principal hace esto después de obtener los códigos:</p>";
echo "<pre>";
echo "\$lista_codigos = isset(\$lista_codigos_pre[\"results\"]) && is_array(\$lista_codigos_pre[\"results\"]) ? \$lista_codigos_pre[\"results\"] : [];\n";
echo "\n";
echo "\$codigos_normales = array_values(array_filter(\$lista_codigos, function(\$codigo) {\n";
echo "    return !isset(\$codigo['destacado']) || \$codigo['destacado'] == 0;\n";
echo "}));\n";
echo "</pre>";

$codigos_procesados = array_values(array_filter($lista_funcion, function($codigo) {
    return !isset($codigo['destacado']) || $codigo['destacado'] == 0;
}));

echo "<p><strong>Después del procesamiento:</strong> " . count($codigos_procesados) . " códigos</p>";

if (!empty($codigos_procesados)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
    foreach ($codigos_procesados as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';

        echo "<div style='margin: 5px 0; padding: 5px; background: white; border-left: 3px solid #28a745;'>";
        echo "#$index - $marca | $fecha";
        echo "</div>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Volver al inicio</a></p>";
?>


