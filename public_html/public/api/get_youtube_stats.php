<?php
// API Endpoint para obtener estadísticas de un video de YouTube
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../myphp/funciones.php';
require_once __DIR__ . '/../../myphp/funciones_youtube.php';
require_once __DIR__ . '/../../myphp/funciones_video_cache.php';

$video_id = $_GET['video_id'] ?? '';

if (empty($video_id)) {
    echo json_encode(['success' => false, 'error' => 'Video ID faltante']);
    exit;
}

try {
    $stats = getYoutubeVideoStatsWithCache($video_id);
    echo json_encode(['success' => true, 'stats' => $stats]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
