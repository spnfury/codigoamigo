<?php
require_once "inc/includes.php";
require_once "myphp/funciones_usuario.php";
$collection_historial = getCollectionHistorial();
try {
    $result = $collection_historial->createIndex(['id_codigo' => 1, 'user_id' => 1]);
    echo "Index created: $result\n";
    print_r(iterator_to_array($collection_historial->listIndexes()));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
