<?php

require __DIR__ . '/../public_html/vendor/autoload.php';

try {
    $client = new MongoDB\Client('mongodb://127.0.0.1:27017');
    $db = $client->selectDatabase('codigo_db');
    $collections = $db->listCollections();

    echo "OK: Conectado a MongoDB y a la DB 'codigo_db'\n";
    echo "Colecciones:\n";
    foreach ($collections as $c) {
        echo " - " . $c->getName() . "\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}


