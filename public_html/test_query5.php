<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

$collection_usuarios = getCollectionUsuarios();
$users = $collection_usuarios->find(['username' => new MongoDB\BSON\Regex('juanlu', 'i')]);

echo "Users with juanlu:\n";
foreach ($users as $u) {
    echo "ID: " . $u['_id'] . " | Username: " . $u['username'] . " | Email: " . (isset($u['email']) ? $u['email'] : 'N/A') . "\n";
}

$collection_codigos = getCollectionCodigos();

// Find ANY recent code sponsored, maybe we can spot Juanlu's code by its name or marca.
$codes = $collection_codigos->find(
    ['destacado' => ['$ne' => 0]],
    ['sort' => ['destacado' => -1], 'limit' => 20]
);

echo "\nMax Destacado Codes:\n";
foreach ($codes as $c) {
    if (isset($c['id_usuario'])) {
        $u = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($c['id_usuario'])]);
        if (!$u) $u = $collection_usuarios->findOne(['_id' => $c['id_usuario']]);
        $uname = $u ? $u['username'] : 'Unknown';
    } else {
        $uname = 'No user';
    }
    
    echo "Marca: " . $c['marca'] . " | Destacado: " . $c['destacado'] . " | User: " . $uname . " (" . (isset($c['id_usuario']) ? $c['id_usuario'] : '') . ")\n";
}
