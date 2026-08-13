<?php
require_once __DIR__ . '/vendor/autoload.php';

$mongo = new MongoDB\Client("mongodb://127.0.0.1:27017");
$db = $mongo->codigo_db;

$user_id = new MongoDB\BSON\ObjectId('67f548040cf97252c40f3982');
$msgs_obj = $db->mensajes->find(['para_usuario_id' => $user_id], ['sort' => ['_id' => -1], 'limit' => 10]);

echo "Mensajes íntegros:\n";
foreach ($msgs_obj as $msg) {
    
    // Buscar nombre del remitente
    $sender = $db->usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($msg['de_usuario_id'])]);
    $sender_name = $sender ? $sender['username'] : $msg['de_usuario_id'];
    
    echo "------------------------\n";
    echo "De: " . $sender_name . "\n";
    echo "Mensaje: \n" . $msg['mensaje'] . "\n";
}
echo "------------------------\n";
