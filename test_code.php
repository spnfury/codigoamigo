<?php
require 'public_html/inc/includes.php';
require 'public_html/myphp/funciones.php';
require 'public_html/myphp/links.php';

$slug = "taxdown-0006be8c";
$parsed = parse_ficha_codigo_slug($slug);
echo "Parsed:\n";
print_r($parsed);

$marca_clave = $parsed['marca'];
$short_id = $parsed['short_id'];

$collection_codigos = getCollectionCodigos();
$codigo = null;

$codigos_marca = $collection_codigos->find([
    'marca' => new \MongoDB\BSON\Regex('^' . preg_quote($marca_clave) . '$', 'i'),
    'estado' => ['$in' => [0, -1, 1]]
], ['limit' => 500]);

foreach ($codigos_marca as $c) {
    $id_str = (string)$c['_id'];
    if (substr($id_str, -8) === $short_id) {
        $codigo = $c;
        break;
    }
}

if (!$codigo) {
    echo "NO ENCONTRADO\n";
    exit;
}

echo "CODIGO ENCONTRADO:\n";
echo "ID: " . $codigo['_id'] . "\n";

if (is_object($codigo)) {
    try {
        $codigo = iterator_to_array($codigo);
        echo "Successfully converted using iterator_to_array\n";
    } catch (\Throwable $e) {
        echo "Error in iterator_to_array: " . $e->getMessage() . "\n";
    }
}

$marca = getObjectMarca('nombre_clave', $codigo['marca']);
if (!$marca) {
    echo "MARCA NO ENCONTRADA\n";
    exit;
}
echo "MARCA ENCONTRADA: " . $marca['nombre'] . "\n";

// ... test getting publicador
echo "SUCCESS!\n";
