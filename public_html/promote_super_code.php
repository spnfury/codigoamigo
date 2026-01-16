<?php
require_once __DIR__ . '/inc/conexion.php';

$db = createConnection();
$collection = $db->selectCollection('codigos');

// Buscar un código de ING (ing-direct) para promocionar
$code = $collection->findOne(['marca' => 'ing-direct', 'estado' => 1]);

if ($code) {
    $result = $collection->updateOne(
        ['_id' => $code['_id']],
        ['$set' => [
            'tipo_destacado' => 'super',
            'destacado' => time() + 3600 // Futuro cercano para asegurar top sort
        ]]
    );
    echo "Código ID " . $code['_id'] . " actualizado a SUPER DESTACADO.\n";
} else {
    echo "No se encontró ningún código activo para ing-direct.\n";
}
?>
