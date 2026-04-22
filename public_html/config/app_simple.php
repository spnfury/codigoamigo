<?php
// HABILITAR VISUALIZACIÓN DE ERRORES PHP (solo en desarrollo)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Limpiar buffer de salida
ob_clean();

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
        'addContentLengthHeader' => false,
    ]
]);

// Ruta principal
$app->get('/', function ($request, $response) {
    return $response->write('
    <!DOCTYPE html>
    <html>
    <head>
        <title>CodigoAmigo.com - Códigos de Descuento</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; }
            .logo { font-size: 2.5em; color: #2c3e50; margin-bottom: 10px; }
            .subtitle { color: #7f8c8d; font-size: 1.2em; }
            .status { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0; }
            .feature { background: #f8f9fa; padding: 20px; border-radius: 5px; text-align: center; }
            .feature h3 { color: #2c3e50; margin-top: 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">🎁 CodigoAmigo.com</div>
                <div class="subtitle">Los mejores códigos de descuento y cupones</div>
            </div>
            
            <div class="status">
                <strong>✅ Sitio funcionando correctamente</strong><br>
                Fecha: ' . date('Y-m-d H:i:s') . '<br>
                MongoDB: ' . (extension_loaded('mongodb') ? '✅ Disponible' : '❌ No disponible') . '<br>
                PHP: ' . PHP_VERSION . '
            </div>
            
            <div class="features">
                <div class="feature">
                    <h3>🛍️ Códigos de Descuento</h3>
                    <p>Encuentra los mejores códigos de descuento para tus compras favoritas</p>
                </div>
                <div class="feature">
                    <h3>🎫 Cupones Verificados</h3>
                    <p>Cupones actualizados diariamente y verificados por nuestro equipo</p>
                </div>
                <div class="feature">
                    <h3>💰 Ahorra Dinero</h3>
                    <p>Ahorra en tus compras con descuentos exclusivos y ofertas especiales</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; color: #7f8c8d;">
                <p>El sitio está siendo restaurado. Pronto tendrás acceso a todas las funcionalidades.</p>
            </div>
        </div>
    </body>
    </html>
    ');
});

// Ruta de prueba
$app->get('/test', function ($request, $response) {
    return $response->write('<h1>Test OK</h1><p>CodigoAmigo.com funcionando correctamente</p>');
});

$app->run();
?>
