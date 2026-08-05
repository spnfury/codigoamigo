<?php
/**
 * Funciones de caché para videos de YouTube
 * Guarda y recupera videos de MongoDB para evitar scraping repetitivo
 */

/**
 * Obtiene la colección de caché de videos
 */
function getCollectionVideoCache() {
    $db = createConnection();
    if (!$db) return null;
    return $db->video_cache;
}

/**
 * Busca videos en caché por término de búsqueda
 * @param string $search_term Término de búsqueda (nombre del producto)
 * @param string $type Tipo de video ('shorts' o 'video')
 * @return array|null Videos encontrados o null si no hay caché
 */
function getCachedVideos($search_term, $type = 'shorts') {
    try {
        $collection = getCollectionVideoCache();
        if (!$collection) return null;
        
        // Normalizar el término de búsqueda
        $cache_key = strtolower(trim($search_term));
        
        $cached = $collection->findOne([
            'search_term' => $cache_key,
            'type' => $type,
            'expires_at' => ['$gt' => new MongoDB\BSON\UTCDateTime()]
        ]);
        
        if ($cached && isset($cached['videos'])) {
            // Convertir BSONArray a PHP array
            $videos = [];
            foreach ($cached['videos'] as $video) {
                $videos[] = (array) $video;
            }
            return $videos;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error getting cached videos: " . $e->getMessage());
        return null;
    }
}

/**
 * Guarda videos en caché
 * @param string $search_term Término de búsqueda
 * @param array $videos Videos a guardar
 * @param string $type Tipo de video
 * @param int $ttl_hours Horas de vida del caché (default 48h)
 */
function setCachedVideos($search_term, $videos, $type = 'shorts', $ttl_hours = 48) {
    try {
        $collection = getCollectionVideoCache();
        if (!$collection) return false;
        
        $cache_key = strtolower(trim($search_term));
        $expires_at = new MongoDB\BSON\UTCDateTime((time() + ($ttl_hours * 3600)) * 1000);
        
        $collection->updateOne(
            ['search_term' => $cache_key, 'type' => $type],
            [
                '$set' => [
                    'search_term' => $cache_key,
                    'type' => $type,
                    'videos' => $videos,
                    'updated_at' => new MongoDB\BSON\UTCDateTime(),
                    'expires_at' => $expires_at
                ]
            ],
            ['upsert' => true]
        );
        
        return true;
    } catch (Exception $e) {
        error_log("Error saving cached videos: " . $e->getMessage());
        return false;
    }
}

/**
 * Busca videos de YouTube con caché inteligente
 * Primero busca en caché, si no hay, scrapea y guarda
 * @param string $product_name Nombre del producto
 * @param string $type Tipo de video
 * @return array Videos encontrados
 */
function getYouTubeVideosWithCache($product_name, $type = 'shorts') {
    // 1. Intentar obtener de caché
    $cached = getCachedVideos($product_name, $type);
    if ($cached !== null) {
        return $cached;
    }
    
    // 2. No hay caché - intentar primero con API, luego scraping
    if (!function_exists('searchYouTubeAPI')) {
        include_once __DIR__ . '/funciones_youtube.php';
    }
    
    // Preferir API si hay key configurada
    $api_key = defined('YOUTUBE_API_KEY') ? YOUTUBE_API_KEY : '';
    if (!empty($api_key)) {
        $videos = searchYouTubeAPI($product_name, $type);
    } else {
        $videos = scrapeYoutubeVideos($product_name, $type);
    }
    
    // 3. Guardar en caché (incluso si está vacío, para no reintentar constantemente)
    // Si está vacío, TTL más corto (6 horas)
    $ttl = empty($videos) ? 6 : 48;
    setCachedVideos($product_name, $videos, $type, $ttl);
    
    return $videos;
}

/**
 * Pre-carga videos para chollos populares
 * Diseñado para ejecutarse via cron
 * @param int $limit Cantidad de chollos a procesar
 */
function preloadVideosForPopularChollos($limit = 50) {
    if (!function_exists('obtenerChollos')) {
        include_once __DIR__ . '/funciones_chollos.php';
    }
    if (!function_exists('extractProductNameGroq')) {
        include_once __DIR__ . '/funciones_youtube.php';
    }
    
    $chollos = obtenerChollos([
        'estado' => 1,
        'limite' => $limit,
        'sort' => 'populares'
    ]);
    
    $processed = 0;
    $found = 0;
    
    foreach ($chollos as $chollo) {
        $product_name = extractProductNameGroq($chollo['titulo'], $chollo['descripcion'] ?? '');
        
        // Verificar si ya está en caché
        $cached = getCachedVideos($product_name, 'shorts');
        if ($cached !== null) {
            continue; // Ya está en caché, saltar
        }
        
        // Scrapear y guardar
        $videos = scrapeYoutubeVideos($product_name, 'shorts');
        $ttl = empty($videos) ? 6 : 48;
        setCachedVideos($product_name, $videos, 'shorts', $ttl);
        
        $processed++;
        if (!empty($videos)) $found++;
        
        // Pequeña pausa para no saturar YouTube
        usleep(500000); // 0.5 segundos
    }
    
    return [
        'processed' => $processed,
        'found_videos' => $found,
        'total_chollos' => count($chollos)
    ];
}

/**
 * Obtiene la colección de caché de estadísticas de videos
 */
function getCollectionVideoStatsCache() {
    $db = createConnection();
    if (!$db) return null;
    return $db->video_stats_cache;
}

/**
 * Obtiene estadísticas de un video de YouTube con caché y fallback
 * @param string $videoId ID del video
 * @return array Estadísticas (likes, comments, views)
 */
function getYoutubeVideoStatsWithCache($videoId) {
    try {
        $collection = getCollectionVideoStatsCache();
        $cache_key = (string)$videoId;
        
        // 1. Intentar obtener de caché (válido por 12 horas)
        if ($collection) {
            $cached = $collection->findOne([
                'video_id' => $cache_key,
                'expires_at' => ['$gt' => new MongoDB\BSON\UTCDateTime()]
            ]);
            
            if ($cached) {
                return [
                    'likes' => (int)($cached['likes'] ?? 0),
                    'comments' => (int)($cached['comments'] ?? 0),
                    'views' => (int)($cached['views'] ?? 0),
                    'from_cache' => true
                ];
            }
        }
        
        // 2. No hay caché o expiró - Intentar API Oficial
        $stats = getYoutubeVideoStats($videoId);
        
        // Si la API devolvió algo útil (likes > 0 o views > 0), lo guardamos
        // Nota: likes son 0 a veces en videos nuevos, pero views suelen estar
        if ($stats['likes'] > 0 || $stats['views'] > 0) {
            saveVideoStatsToCache($videoId, $stats);
            return $stats;
        }
        
        // 3. Si la API falló (quota o 0 stats), intentar Scraping como fallback
        $scrapedStats = scrapeYoutubeVideoStats($videoId);
        if ($scrapedStats['likes'] > 0 || $scrapedStats['views'] > 0) {
            saveVideoStatsToCache($videoId, $scrapedStats);
            return $scrapedStats;
        }
        
        // Si todo falla, devolver lo que tengamos (probablemente ceros)
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error in getYoutubeVideoStatsWithCache: " . $e->getMessage());
        return ['likes' => 0, 'comments' => 0, 'views' => 0];
    }
}

/**
 * Guarda las estadísticas en MongoDB
 */
function saveVideoStatsToCache($videoId, $stats) {
    try {
        $collection = getCollectionVideoStatsCache();
        if (!$collection) return false;
        
        $expires_at = new MongoDB\BSON\UTCDateTime((time() + (12 * 3600)) * 1000); // 12 horas
        
        $collection->updateOne(
            ['video_id' => $videoId],
            [
                '$set' => [
                    'video_id' => $videoId,
                    'likes' => (int)$stats['likes'],
                    'comments' => (int)$stats['comments'],
                    'views' => (int)$stats['views'],
                    'updated_at' => new MongoDB\BSON\UTCDateTime(),
                    'expires_at' => $expires_at
                ]
            ],
            ['upsert' => true]
        );
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Scrapea estadísticas básicas de una página de video de YouTube
 */
function scrapeYoutubeVideoStats($videoId) {
    $stats = ['likes' => 0, 'comments' => 0, 'views' => 0];
    $url = "https://www.youtube.com/watch?v={$videoId}";
    
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\r\n" .
                        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8\r\n" .
                        "Accept-Language: es-ES,es;q=0.9,en;q=0.8\r\n" .
                        "Cookie: CONSENT=PENDING+999; \r\n", // Intento de saltar consentimientos
            "timeout" => 7
        ]
    ];
    $context = stream_context_create($opts);
    $html = @file_get_contents($url, false, $context);
    
    if (!$html) return $stats;
    
    // Buscar vistas (viewCount) - Patrón muy común en JSON
    if (preg_match('/"viewCount":"(\d+)"/', $html, $matches)) {
        $stats['views'] = (int)$matches[1];
    }
    
    // Buscar likes - YouTube usa varios formatos
    // Formato 1: accessibility label (viene con texto "X likes")
    if (preg_match('/"label":"([\d\.,\s]+)(?:likes?|me gusta)"/i', $html, $matches)) {
        $stats['likes'] = (int)str_replace(['.', ',', ' '], '', $matches[1]);
    }
    // Formato 2: simpleText en likeCount
    if ($stats['likes'] === 0 && preg_match('/"likeCountText":\{"accessibility":\{"accessibilityData":\{"label":"([\d\.,\s]+)/i', $html, $matches)) {
        $stats['likes'] = (int)str_replace(['.', ',', ' '], '', $matches[1]);
    }
    // Formato 3: fullLikeCount
    if ($stats['likes'] === 0 && preg_match('/"fullLikeCount":"([\d\.,]+)"/', $html, $matches)) {
        $stats['likes'] = (int)str_replace(['.', ','], '', $matches[1]);
    }
    
    // Buscar comentarios
    if (preg_match('/"commentCount":\{"simpleText":"([\d\.,]+)"\}/', $html, $matches)) {
        $stats['comments'] = (int)str_replace(['.', ','], '', $matches[1]);
    } elseif (preg_match('/"label":"([\d\.,\s]+)comentarios"/i', $html, $matches)) {
        $stats['comments'] = (int)str_replace(['.', ',', ' '], '', $matches[1]);
    }
    
    return $stats;
}
