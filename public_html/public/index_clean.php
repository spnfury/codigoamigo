<?php
// Limpiar buffer de salida para evitar errores de Slim (si existe)
if (ob_get_level()) {
    ob_clean();
}

// Deshabilitar errores de deprecación temporalmente para compatibilidad con PHP 8.3
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

session_start();

require_once dirname(__DIR__) . '/vendor/autoload.php';
include_once '/home/admin/web/codigoamigo.com/public_html/inc/includes.php';
include_once '/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php';
include_once '/home/admin/web/codigoamigo.com/public_html/myphp/_header.php';

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => false, // Cambiado a false para ocultar warnings
        'addContentLengthHeader' => false,
        'debug' => false,
        'determineRouteBeforeAppMiddleware' => true
    ]
]);

// Ruta principal que incluye el contenido original
$app->get('/', function ($request, $response) {
    // Inicializar Mobile_Detect
    if (!class_exists('Mobile_Detect')) {
        require_once __DIR__ . '/../myphp/librerias/Mobile_Detect.php';
    }
    $detect = new Mobile_Detect();
    $GLOBALS['detect'] = $detect;
    
    // Variables necesarias para main.php
    $title = "CodigoAmigo.com - Códigos de Descuento";
    $description = "Los mejores códigos de descuento y cupones";
    $title_social = $title;
    $description_social = $description;
    $imagen_social = "https://www.codigoamigo.com/logo.png";
    $links_meta = "";
    $numero_codigos = 1000;
    
    // Incluir main.php
    ob_start();
    include_once '/home/admin/web/codigoamigo.com/public_html/public/main.php';
    $content = ob_get_clean();
    
    return $response->write($content);
});

$app->run();
?>
