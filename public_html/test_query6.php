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

echo "Patrocinados en query de homepage:\n";
foreach ($lista_codigos_patrocinados as $c) {
    if ($c['marca'] == 'nuevamarcatribbu' || $c['marca'] == 'octopusenergy') {
        echo "FOUND! ID: " . $c['_id'] . " | Marca: " . $c['marca'] . "\n";
    }
}
echo "Total encontrados: " . count($lista_codigos_patrocinados) . "\n";

