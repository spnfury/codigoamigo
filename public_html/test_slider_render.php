<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones_modern.php';

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

echo "TOTAL CODES RETRIEVED: " . count($lista_codigos_patrocinados) . "\n";

foreach($lista_codigos_patrocinados as $index => $c) {
    if ($index < 5) {
        echo "[$index] " . $c['_id'] . " | " . $c['marca'] . " | user_id: " . $c['id_usuario'] . "\n";
    }
}

$html = generate_modern_featured_cards($lista_codigos_patrocinados);

if (strpos($html, 'Juanlu') !== false) {
    echo "JUANLU is in HTML!\n";
} else {
    echo "JUANLU is NOT in HTML!\n";
}
if (strpos($html, 'tribbu') !== false) {
    echo "tribbu is in HTML!\n";
} else {
    echo "tribbu is NOT in HTML!\n";
}
