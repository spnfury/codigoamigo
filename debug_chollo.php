<?php
// Debug script to check chollo status
require_once __DIR__ . '/public_html/myphp/funciones_chollos.php';

$id = '696664fff65aba5c490f6b43';
$chollo = obtenerCholloPorId($id);

if ($chollo) {
    echo "Chollo found:\n";
    echo "ID: " . $chollo['id'] . "\n";
    echo "Title: " . $chollo['titulo'] . "\n";
    echo "Estado: " . ($chollo['estado'] ?? 'not set') . "\n";
    echo "Categoría: " . json_encode($chollo['categoria']) . "\n";
    if (isset($chollo['fecha_creacion'])) {
        if ($chollo['fecha_creacion'] instanceof MongoDB\BSON\UTCDateTime) {
            echo "Fecha Creación: " . $chollo['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s') . "\n";
        } else {
            echo "Fecha Creación: " . $chollo['fecha_creacion'] . "\n";
        }
    }
} else {
    echo "Chollo NOT found for ID: $id\n";
}
