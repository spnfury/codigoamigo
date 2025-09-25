<?php
// Script para comparar rutas entre app.php y app_with_mongo.php

function extractRoutes($file) {
    $content = file_get_contents($file);
    $routes = [];
    
    // Buscar todas las rutas definidas
    preg_match_all('/\$app->(get|post|put|delete|patch)\([\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $method = $match[1];
        $path = $match[2];
        $routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'full' => $method . ' ' . $path
        ];
    }
    
    return $routes;
}

echo "Comparando rutas entre app.php y app_with_mongo.php\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$app_routes = extractRoutes('/home/admin/web/codigoamigo.com/public_html/app.php');
$mongo_routes = extractRoutes('/home/admin/web/codigoamigo.com/public_html/app_with_mongo.php');

echo "Rutas en app.php: " . count($app_routes) . "\n";
echo "Rutas en app_with_mongo.php: " . count($mongo_routes) . "\n\n";

// Crear arrays de rutas para comparación
$app_route_keys = [];
foreach ($app_routes as $route) {
    $app_route_keys[] = $route['full'];
}

$mongo_route_keys = [];
foreach ($mongo_routes as $route) {
    $mongo_route_keys[] = $route['full'];
}

// Encontrar rutas faltantes
$missing_routes = array_diff($app_route_keys, $mongo_route_keys);
$extra_routes = array_diff($mongo_route_keys, $app_route_keys);

echo "RUTAS FALTANTES EN app_with_mongo.php:\n";
echo "-" . str_repeat("-", 40) . "\n";
if (empty($missing_routes)) {
    echo "✓ No hay rutas faltantes\n";
} else {
    foreach ($missing_routes as $route) {
        echo "✗ $route\n";
    }
}

echo "\nRUTAS EXTRA EN app_with_mongo.php:\n";
echo "-" . str_repeat("-", 40) . "\n";
if (empty($extra_routes)) {
    echo "✓ No hay rutas extra\n";
} else {
    foreach ($extra_routes as $route) {
        echo "+ $route\n";
    }
}

echo "\nRUTAS CRÍTICAS FALTANTES (que deberían migrarse):\n";
echo "-" . str_repeat("-", 50) . "\n";

$critical_routes = [
    'get /registro',
    'get /login', 
    'get /logout',
    'get /usuario',
    'get /listado',
    'get /ficha/{id}',
    'get /nuevo_codigo',
    'get /modificar_codigo/{codigo_id}',
    'post /modificar_codigo/{codigo_id}',
    'post /codigo_insertado',
    'get /categoria',
    'get /listado_categorias',
    'get /listado-marcas',
    'get /listado_marcas',
    'get /politica-de-privacidad',
    'get /politica-de-cookies',
    'get /aviso-legal',
    'get /estadisticas',
    'get /destaca',
    'get /felicidades',
    'get /felicidades_splash',
    'get /bienvenido_de_nuevo',
    'post /ajax',
    'post /google_sign',
    'get /api_final',
    'post /remove_photo_user',
    'post /cambiar_foto_usuario',
    'post /list_elements_bd',
    'post /comprobar_existe_codigo',
    'post /cargar_localidades',
    'post /verify',
    'get /codes',
    'post /codes',
    'put /codes/{id}',
    'get /exchanges-que-no-informan-a-hacienda',
    'get /neobancos-que-no-informan-a-hacienda'
];

foreach ($critical_routes as $critical_route) {
    if (in_array($critical_route, $missing_routes)) {
        echo "⚠️  CRÍTICA: $critical_route\n";
    }
}

echo "\nAnálisis completado.\n";
?>
