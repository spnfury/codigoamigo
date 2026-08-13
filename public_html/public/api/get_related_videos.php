<?php
include_once __DIR__ . '/../../inc/logger.php';
// API Endpoint para obtener videos relacionados
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Incluir funciones
require_once __DIR__ . '/../../myphp/funciones.php';
require_once __DIR__ . '/../../myphp/funciones_chollos.php';
require_once __DIR__ . '/../../myphp/funciones_youtube.php';

// Obtener ID del chollo
$chollo_id = $_GET['id'] ?? '';
$debug = isset($_GET['debug']);

if (empty($chollo_id)) {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

try {
    // Obtener chollo
    $chollo = obtenerCholloPorId($chollo_id);
    
    if (!$chollo) {
        echo json_encode(['success' => false, 'error' => 'Chollo no encontrado']);
        exit;
    }

    // 1. Extraer nombre del producto (Product Name extraction)
    // Intentamos usar caché simple via el campo 'producto_detectado' en la DB si existiera (optimización futura)
    // Por ahora extraemos en tiempo real (o deberíamos guardar esto en DB)
    
    $product_name = extractProductNameGroq($chollo['titulo'], $chollo['descripcion'] ?? '');
    
    if ($debug) {
        log_info("Producto detectado: " . $product_name);
    }

    // 2. Buscar videos (Shorts y Normales)
    // Buscamos "Review + Nombre" para videos largos
    // Buscamos "Nombre" para shorts
    
    $shorts = scrapeYoutubeVideos($product_name, 'shorts');
    $videos = scrapeYoutubeVideos($product_name . ' review analisis', 'video');

    echo json_encode([
        'success' => true,
        'product_name' => $product_name,
        'shorts' => $shorts,
        'videos' => $videos
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
