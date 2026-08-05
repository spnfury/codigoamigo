#!/usr/bin/php
<?php
/**
 * Cron script para pre-cargar videos de YouTube para chollos populares
 * 
 * Uso: php preload_shorts_videos.php
 * Recomendado: Ejecutar cada 6-12 horas via cron
 */

// Configurar para ejecución CLI
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); // 10 minutos máximo

echo "=== Pre-carga de Videos Shorts DESHABILITADA ===\n";
echo "Este script ha sido movido a Malprecio.com\n";
exit;

echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Incluir funciones necesarias
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_youtube.php';
require_once __DIR__ . '/../myphp/funciones_video_cache.php';

// Ejecutar la pre-carga
$result = preloadVideosForPopularChollos(30); // Procesar 30 chollos populares

echo "Resultados:\n";
echo "- Chollos analizados: " . $result['total_chollos'] . "\n";
echo "- Nuevos procesados: " . $result['processed'] . "\n";
echo "- Con videos encontrados: " . $result['found_videos'] . "\n";

// Estadísticas de caché
try {
    $collection = getCollectionVideoCache();
    if ($collection) {
        $total_cached = $collection->countDocuments([]);
        $with_videos = $collection->countDocuments(['videos' => ['$ne' => []]]);
        
        echo "\nEstadísticas de caché:\n";
        echo "- Total en caché: $total_cached\n";
        echo "- Con videos: $with_videos\n";
        echo "- Sin videos: " . ($total_cached - $with_videos) . "\n";
    }
} catch (Exception $e) {
    echo "Error obteniendo estadísticas: " . $e->getMessage() . "\n";
}

echo "\n=== Fin de la pre-carga ===\n";
