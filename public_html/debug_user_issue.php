<?php
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';

// Conexión a Mongo
$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");

// 1. Buscar usuario
$filter = ['mail' => 'thevega82@gmail.com'];
$query = new MongoDB\Driver\Query($filter);
$cursor = $manager->executeQuery('codigo_db.usuarios', $query);
$usuarios = $cursor->toArray();

if (empty($usuarios)) {
    echo "Usuario no encontrado.\n";
    exit;
}

$user = $usuarios[0];
$user_id = (string)$user->_id;
echo "Usuario encontrado: " . $user->username . " (ID: " . $user_id . ")\n";

// 3. Buscar ESPECÍFICAMENTE 'elevenlabs' ahora que sabemos cómo buscar el usuario
$filter_codes = [
    'id_usuario' => ['$in' => [$user_id, $user_id_obj]],
    'marca' => 'elevenlabs'
];

$query_codes = new MongoDB\Driver\Query($filter_codes);
$cursor_codes = $manager->executeQuery('codigo_db.codigos', $query_codes);
$codes = $cursor_codes->toArray();

echo "Códigos elevenlabs encontrados: " . count($codes) . "\n";

foreach ($codes as $code) {
    echo "CODIGO_ID: " . $code->_id . "\n";
    echo "MARCA: " . $code->marca . "\n";
}
?>
