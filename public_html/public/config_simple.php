<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
    ]
]);

// Ruta de prueba
$app->get('/', function ($request, $response) {
    return $response->write('<h1>CodigoAmigo.com</h1><p>¡Sitio funcionando correctamente!</p>');
});

$app->run();
?>
