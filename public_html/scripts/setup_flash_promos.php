<?php
require_once __DIR__ . '/../myphp/funciones.php';

function setupFlashPromos() {
    $db = createConnection();
    if (!$db) {
        die("Error de conexión");
    }

    $collection = $db->selectCollection('flash_promos');

    // Sample promo for Imaginbank
    $imaginPromo = [
        'marca_clave' => 'imaginbank',
        'titulo' => '¡Consigue 50€ de regalo al abrir tu cuenta!',
        'descripcion' => 'Abre tu cuenta imagin, domicilia tu nómina superior a 800€ y llévate 50€ de regalo directamente en tu cuenta. ¡Sin sorteos!',
        'beneficio' => '50€',
        'url_promo' => 'https://www.imagin.com/promociones',
        'verificado' => true,
        'fecha_expiracion' => new MongoDB\BSON\UTCDateTime(strtotime('+30 days') * 1000),
        'tipo' => 'flash_promo',
        'prioridad' => 10,
        'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
        'activo' => true
    ];

    // Check if it already exists
    $exists = $collection->findOne(['marca_clave' => 'imaginbank', 'titulo' => $imaginPromo['titulo']]);
    if (!$exists) {
        $collection->insertOne($imaginPromo);
        echo "Promo de Imaginbank añadida correctamente.\n";
    } else {
        echo "La promo de Imaginbank ya existe.\n";
    }
}

setupFlashPromos();
