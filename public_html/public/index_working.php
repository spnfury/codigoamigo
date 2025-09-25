<?php
// Limpiar buffer de salida
ob_clean();

// Deshabilitar errores de deprecación temporalmente
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
    ]
]);

// Ruta principal
$app->get('/', function ($request, $response) {
    return $response->write('
    <!DOCTYPE html>
    <html>
    <head>
        <title>CodigoAmigo.com</title>
        <meta charset="utf-8">
    </head>
    <body>
        <h1>CodigoAmigo.com</h1>
        <p>¡Sitio funcionando correctamente!</p>
        <p>Fecha: ' . date('Y-m-d H:i:s') . '</p>
        <p>MongoDB: ' . (extension_loaded('mongodb') ? 'Disponible' : 'No disponible') . '</p>
    </body>
    </html>
    ');
});

$app->run();
?>
