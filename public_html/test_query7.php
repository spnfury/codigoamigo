<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

$sort_order_destacados = array('prioridad_pago' => -1, 'destacado_social' => -1, 'destacado' => -1, 'fecha_publicacion' => -1);

$array_filtro = array(
    "estado" => 0,
    '$or' => array(
        array("destacado_social" => array('$ne' => 0)),
        array("destacado" => array('$ne' => 0))
    )
);

$array_skip = array("limit" => 100); 
$array_skip = array_merge($array_skip, array("sort" => $sort_order_destacados)); 

$lista_codigos_patrocinados_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
$lista_codigos_patrocinados = isset($lista_codigos_patrocinados_pre["results"]) ? $lista_codigos_patrocinados_pre["results"] : [];

foreach ($lista_codigos_patrocinados as $index => $c) {
    if ($c['marca'] == 'nuevamarcatribbu' || $c['marca'] == 'octopusenergy') {
        echo "FOUND at index $index: ID: " . $c['_id'] . " | Marca: " . $c['marca'] . " | Date: " . (isset($c['fecha_publicacion']) ? (string)$c['fecha_publicacion'] : 'No date') . " | Prioridad: " . (isset($c['prioridad_pago']) ? $c['prioridad_pago'] : 0) . " | Social: " . (isset($c['destacado_social']) ? $c['destacado_social'] : 0) . "\n";
    }
}
