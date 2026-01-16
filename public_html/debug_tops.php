<?php
require_once "myphp/funciones.php";
require_once "myphp/funciones_chollos.php";
require_once "myphp/funciones_chollos_votos.php";
$collection = getCollectionChollos();
$fecha_24h = new MongoDB\BSON\UTCDateTime((time() - 24 * 3600) * 1000);
$count = $collection->countDocuments(["estado" => 1, "fecha_creacion" => ['$gte' => $fecha_24h]]);
echo "Chollos in last 24h: " . $count . "\n";

$calientes = obtenerChollosMasCalientes24h(5);
echo "Calientes count: " . count($calientes) . "\n";
foreach ($calientes as $c) {
    echo "- " . $c['titulo'] . " (" . $c['temperatura'] . "°)\n";
}

$populares = obtenerChollosMasPopulares24h(5);
echo "Populares count: " . count($populares) . "\n";
foreach ($populares as $c) {
    echo "- " . $c['titulo'] . " (" . $c['clicks'] . " clicks)\n";
}
