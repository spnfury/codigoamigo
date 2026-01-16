<?php
// redirect_amazon.php
// Manejador de redirecciones para afiliados Amazon

require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/funciones.php'; // Necesario para createConnection()
require_once __DIR__ . '/myphp/funciones_amazon_services.php';

// Obtener slug de la query string
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$slug = preg_replace('/[^a-zA-Z0-9-_]/', '', $slug); // Sanitize

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "Servicio no especificado.";
    exit;
}

// Buscar el servicio en la BD
$service = getAmazonServiceBySlug($slug);

if ($service) {
    // URL destino
    $destination = $service['destination_url'];
    
    // Registrar el click
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    // Podríamos pasar un param 'origin' en la URL si el frontend lo añade: /go/prime?origin=footer
    $origin = $_GET['origin'] ?? 'unknown';
    
    logAmazonServiceClick($slug, $referrer, $origin);
    
    // Redirección 302 (Temporal)
    header("Location: " . $destination, true, 302);
    exit;
    
} else {
    // Si no existe, 404
    header("HTTP/1.0 404 Not Found");
    echo "Servicio no encontrado.";
    exit;
}
?>
