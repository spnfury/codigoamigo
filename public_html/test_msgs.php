<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/myphp/funciones_usuario.php';

$collection_mensajes = getCollectionMensajes();
if (!$collection_mensajes) {
    die("No collection\n");
}
$msgs = $collection_mensajes->find(
    ['para_usuario_id' => '67f548040cf97252c40f3982'],
    ['sort' => ['_id' => -1], 'limit' => 20]
);

$count = 0;
foreach ($msgs as $msg) {
    echo "------------------------\n";
    echo "ID Mensaje: " . $msg['_id'] . "\n";
    echo "De: " . $msg['de_usuario_id'] . "\n";
    echo "Asunto: " . ($msg['asunto'] ?? 'N/A') . "\n";
    echo "Mensaje: " . ($msg['mensaje'] ?? 'N/A') . "\n";
    echo "Contexto: " . json_encode($msg['contexto'] ?? []) . "\n";
    echo "Fecha: " . ($msg['fecha_envio'] ?? 'N/A') . "\n";
    $count++;
}
echo "Total encontrados: $count\n";
echo "Done\n";
