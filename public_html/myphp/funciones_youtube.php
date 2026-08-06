<?php
include_once __DIR__ . '/../inc/logger.php';

/**
 * Funciones para YouTube y extracción de productos
 */

// Incluir configuración de IA si no está
if (file_exists(__DIR__ . '/../config/ai_config.php')) {
    include_once __DIR__ . '/../config/ai_config.php';
}

/**
 * Realiza una petición a la API de YouTube rotando llaves si es necesario
 */
function callYoutubeAPIWithRotation($endpoint, $params) {
    $keys = defined('YOUTUBE_API_KEYS') ? YOUTUBE_API_KEYS : (defined('YOUTUBE_API_KEY') ? [YOUTUBE_API_KEY] : []);
    
    if (empty($keys)) return null;
    
    foreach ($keys as $key) {
        $params['key'] = $key;
        $url = "https://www.googleapis.com/youtube/v3/{$endpoint}?" . http_build_query($params);
        
        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => 7,
                'ignore_errors' => true, // Para capturar el 403
                'header' => 'Accept: application/json'
            ]
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            // Si hay error de cuota, probar con la siguiente llave
            if (isset($data['error']['errors'][0]['reason']) && $data['error']['errors'][0]['reason'] === 'quotaExceeded') {
                log_warning("YouTube Quota exceeded for key: " . substr($key, 0, 8) . "...");
                continue;
            }
            
            // Si es otro error (ej. 403 pero no cuota), o éxito, devolvemos
            return $response;
        }
    }
    
    return null;
}

/**
 * Busca videos en YouTube usando la API oficial v3
 * Más confiable que el scraping
 * @param string $query Término de búsqueda
 * @param string $type 'shorts' o 'video'
 * @param int $maxResults Máximo de resultados
 * @return array Videos encontrados
 */
function searchYouTubeAPI($query, $type = 'shorts', $maxResults = 6) {
    $api_key = defined('YOUTUBE_API_KEY') ? YOUTUBE_API_KEY : '';
    
    if (empty($api_key)) {
        // Sin API key, intentar scraping como fallback
        return scrapeYoutubeVideos($query, $type);
    }
    
    $videos = [];
    
    // Construir URL de la API
    $params = [
        'part' => 'snippet',
        'q' => $query,
        'type' => 'video',
        'maxResults' => $maxResults,
        'key' => $api_key,
        'relevanceLanguage' => 'es',
        'regionCode' => 'ES'
    ];
    
    // Para shorts, añadir filtro de duración corta
    if ($type === 'shorts') {
        $params['videoDuration'] = 'short'; // Videos menores a 4 minutos
        $params['q'] = $query . ' #shorts'; // Añadir hashtag para encontrar shorts
    }
    
    $response = callYoutubeAPIWithRotation('search', $params);
    
    if (!$response) {
        return scrapeYoutubeVideos($query, $type);
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['items']) || empty($data['items'])) {
        return [];
    }
    
    foreach ($data['items'] as $item) {
        $videoId = $item['id']['videoId'] ?? '';
        if (empty($videoId)) continue;
        
        $videos[] = [
            'id' => $videoId,
            'title' => $item['snippet']['title'] ?? '',
            'thumbnail' => $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['default']['url'] ?? '',
            'thumbnail_hq' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
            'channel' => $item['snippet']['channelTitle'] ?? '',
            'type' => $type === 'shorts' ? 'short' : 'video'
        ];
    }
    
    // OPTIMIZACIÓN: Obtener estadísticas (likes, comentarios) para todos los videos encontrados en una sola llamada (1 unidad extra)
    if (!empty($videos)) {
        $ids = array_column($videos, 'id');
        $stats_params = [
            'part' => 'statistics',
            'id' => implode(',', $ids)
        ];
        
        $stats_response = callYoutubeAPIWithRotation('videos', $stats_params);
        if ($stats_response) {
            $stats_data = json_decode($stats_response, true);
            if (isset($stats_data['items'])) {
                // Crear mapa de stats por ID
                $stats_map = [];
                foreach ($stats_data['items'] as $stat_item) {
                    $stats_map[$stat_item['id']] = [
                        'likes' => (int)($stat_item['statistics']['likeCount'] ?? 0),
                        'comments' => (int)($stat_item['statistics']['commentCount'] ?? 0),
                        'views' => (int)($stat_item['statistics']['viewCount'] ?? 0)
                    ];
                }
                
                // Asignar stats a cada video
                foreach ($videos as &$v) {
                    if (isset($stats_map[$v['id']])) {
                        $v['likes'] = $stats_map[$v['id']]['likes'];
                        $v['comments'] = $stats_map[$v['id']]['comments'];
                        $v['views'] = $stats_map[$v['id']]['views'];
                    }
                }
            }
        }
    }
    
    return $videos;
}

/**
 * Extrae el nombre real del producto usando Groq
 * Ejemplo: "Xiaomi Redmi Watch 5" de un título largo con marketing
 */
function extractProductNameGroq($title, $description = '') {
    $api_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    
    if (empty($api_key) || $api_key === 'gsk-your-api-key-here') {
        return cleanTitleSimple($title); // Fallback manual simple
    }

    $text_to_analyze = "TITULO: " . $title . "\nDESCRIPCION: " . substr(strip_tags($description), 0, 300);

    $prompt = "Identifica el NOMBRE EXACTO del producto en este chollo. Eliminando adjetivos de venta como 'barato', 'oferta', 'profesional', 'original', 'nuevo', etc.
    Si es un Xiaomi Redmi Watch 5 Active, devuelve solo \"Xiaomi Redmi Watch 5 Active\".
    Si es un Pack de 4 AirTags, devuelve \"Apple AirTag\".
    Si es ropa, devuelve la marca y modelo/tipo.
    Devuelve SOLO el nombre, sin comillas ni explicaciones.";

    $data = [
        'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt . "\n\n" . $text_to_analyze
            ]
        ],
        'max_tokens' => 50,
        'temperature' => 0.1 // Muy baja temperatura para ser preciso
    ];

    // Groq con rotación de claves + fallback de modelo (ver groq_request en ai_config)
    $__g = groq_request($data, 10);
    $response = $__g['body'];
    $error = '';

    if (!$error) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            $name = trim($result['choices'][0]['message']['content']);
            // Limpieza extra por si acaso
            $name = preg_replace('/^"|"$|^\.$/', '', $name);
            return $name;
        }
    }

    return cleanTitleSimple($title);
}

/**
 * Limpieza simple por si falla la IA
 */
function cleanTitleSimple($title) {
    // Eliminar paréntesis y corchetes y su contenido
    $title = preg_replace('/\[.*?\]|\(.*?\)/', '', $title);
    
    // Palabras a eliminar
    $stopwords = ['oferta', 'chollo', 'barato', 'descuento', 'rebaja', 'nuevo', 'original', 'envío gratis', 'prime', 'amazon'];
    foreach ($stopwords as $word) {
        $title = str_ireplace($word, '', $title);
    }
    
    // Quedarse con los primeros 40 caracteres aprox o las primeras 4-5 palabras
    $words = explode(' ', trim($title));
    return implode(' ', array_slice($words, 0, 5));
}

/**
 * Busca videos en YouTube haciendo scraping de la página de resultados
 * @param string $query Término de búsqueda (nombre del producto)
 * @param string $type 'shorts' o 'video'
 * @return array Lista de videos
 */
function scrapeYoutubeVideos($query, $type = 'video') {
    $videos = [];
    
    // Para shorts, añadir keywords específicos para encontrar contenido corto
    if ($type === 'shorts') {
        $query = $query . ' shorts español';
    }
    $query = urlencode($query);
    
    // sp=EgIQAQ%253D%253D -> Videos
    // sp=EgZzaG9ydHM%253D -> Shorts (filtro específico de Shorts)
    
    $sp = ($type === 'shorts') ? 'EgZzaG9ydHM%3D' : 'EgIQAQ%3D%3D'; 
    $url = "https://www.youtube.com/results?search_query={$query}&sp={$sp}";

    // Contexto para simular navegador real
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                        "Accept-Language: es-ES,es;q=0.9\r\n",
            "timeout" => 5 // Tiempo de espera de 5 segundos
        ]
    ];
    $context = stream_context_create($opts);
    
    // Intentar obtener el HTML
    $html = @file_get_contents($url, false, $context);
    
    if (!$html) return [];

    // MÉTODO 1: Extracción directa de /shorts/ ID del HTML (Garantiza formato Short)
    // Este patrón es mucho más específico que solo videoId
    $video_ids = [];
    
    if ($type === 'shorts') {
        // Buscar el patrón /shorts/VIDEO_D en el HTML
        if (preg_match_all('/\/shorts\/([a-zA-Z0-9_-]{11})/', $html, $id_matches)) {
            // Eliminar duplicados y tomar los primeros únicos
            $video_ids = array_unique($id_matches[1]);
            $video_ids = array_slice($video_ids, 0, 10);
        }
        
        // Si no encontramos con /shorts/, intentar con el JSON pero filtrando por el comando de shorts
        if (empty($video_ids)) {
             if (preg_match_all('/"commandMetadata":\{"webCommandMetadata":\{"url":"\/shorts\/([a-zA-Z0-9_-]{11})"/', $html, $json_matches)) {
                $video_ids = array_unique($json_matches[1]);
                $video_ids = array_slice($video_ids, 0, 10);
             }
        }
        
        // Extraer títulos para los IDs encontrados
        foreach ($video_ids as $vid) {
            $title = 'Short de ofertas';
            
            // Intentar extraer título del contexto cercano al videoId
            // Buscamos un patrón que incluya el videoId y un título cercano
            $title_pattern = '/"videoId":"' . preg_quote($vid) . '".*?"title":\s*\{[^}]*"text":\s*"([^"]+)"/s';
            if (preg_match($title_pattern, $html, $title_match)) {
                $title = $title_match[1];
            } else {
                $alt_pattern = '/"videoId":"' . preg_quote($vid) . '".*?"headline":\s*\{[^}]*"simpleText":\s*"([^"]+)"/s';
                if (preg_match($alt_pattern, $html, $alt_match)) {
                    $title = $alt_match[1];
                }
            }
            
            $videos[] = [
                'id' => $vid,
                'title' => html_entity_decode($title, ENT_QUOTES, 'UTF-8'),
                'thumbnail' => "https://i.ytimg.com/vi/{$vid}/hqdefault.jpg",
                'thumbnail_hq' => "https://i.ytimg.com/vi/{$vid}/hqdefault.jpg",
                'type' => 'short'
            ];
            
            if (count($videos) >= 6) break;
        }
        
        if (!empty($videos)) {
            return $videos;
        }
    }

    // MÉTODO 2: Parseo de ytInitialData JSON (fallback)
    $patterns = [
        '/var ytInitialData = (\{.*?\});/', 
        '/ytInitialData\s*=\s*(\{.*?\});/',
        '/window\["ytInitialData"\]\s*=\s*(\{.*?\});/'
    ];
    
    $json_data = null;
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            $json_data = json_decode($matches[1], true);
            if ($json_data) break;
        }
    }
    
    if (!$json_data) {
        return $videos; // Retornar lo que tengamos del método 1
    }
    
    // Buscar shorts en múltiples posibles ubicaciones del JSON
    $contents = [];
    
    // Ubicación 1: Estructura estándar de búsqueda
    if (isset($json_data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'])) {
        $sections = $json_data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'];
        foreach ($sections as $section) {
            if (isset($section['itemSectionRenderer']['contents'])) {
                $contents = array_merge($contents, $section['itemSectionRenderer']['contents']);
            }
            // Shorts específicos en reelShelfRenderer
            if (isset($section['reelShelfRenderer']['items'])) {
                $contents = array_merge($contents, $section['reelShelfRenderer']['items']);
            }
        }
    }
    
    // Ubicación 2: Resultados directos
    if (empty($contents) && isset($json_data['contents']['sectionListRenderer']['contents'])) {
        foreach ($json_data['contents']['sectionListRenderer']['contents'] as $section) {
            if (isset($section['itemSectionRenderer']['contents'])) {
                $contents = array_merge($contents, $section['itemSectionRenderer']['contents']);
            }
        }
    }
    
    foreach ($contents as $item) {
        $video_data = [];
        
        // Shorts en formato reelItemRenderer
        if (isset($item['reelItemRenderer'])) {
            $renderer = $item['reelItemRenderer'];
            $video_data = [
                'id' => $renderer['videoId'] ?? '',
                'title' => $renderer['headline']['simpleText'] ?? ($renderer['headline']['runs'][0]['text'] ?? 'Short'),
                'thumbnail' => $renderer['thumbnail']['thumbnails'][0]['url'] ?? '',
                'type' => 'short'
            ];
        }
        // Shorts en formato shortsLockupViewModel (nuevo formato 2024+)
        elseif (isset($item['shortsLockupViewModel'])) {
            $renderer = $item['shortsLockupViewModel'];
            $videoId = '';
            if (isset($renderer['onTap']['innertubeCommand']['reelWatchEndpoint']['videoId'])) {
                $videoId = $renderer['onTap']['innertubeCommand']['reelWatchEndpoint']['videoId'];
            } elseif (isset($renderer['entityId'])) {
                // El entityId a veces contiene el videoId
                $videoId = str_replace('shorts-shelf-item-', '', $renderer['entityId']);
            }
            if ($videoId) {
                $video_data = [
                    'id' => $videoId,
                    'title' => $renderer['overlayMetadata']['primaryText']['content'] ?? 'Short',
                    'thumbnail' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                    'type' => 'short'
                ];
            }
        }
        // Videos normales
        elseif ($type === 'video' && isset($item['videoRenderer'])) {
            $renderer = $item['videoRenderer'];
            $video_data = [
                'id' => $renderer['videoId'],
                'title' => $renderer['title']['runs'][0]['text'] ?? '',
                'thumbnail' => $renderer['thumbnail']['thumbnails'][0]['url'] ?? '',
                'length' => $renderer['lengthText']['simpleText'] ?? '',
                'views' => $renderer['viewCountText']['simpleText'] ?? '',
                'author' => $renderer['ownerText']['runs'][0]['text'] ?? '',
                'type' => 'video'
            ];
        }
        
        if (!empty($video_data['id'])) {
            $video_data['thumbnail_hq'] = "https://i.ytimg.com/vi/{$video_data['id']}/hqdefault.jpg";
            $videos[] = $video_data;
            if (count($videos) >= 6) break;
        }
    }
    
    // Si no encontramos nada, usar fallback
    if (empty($videos)) {
        return getFallbackShorts($query);
    }

    return $videos;
}

/**
 * Devuelve shorts de fallback cuando el scraping falla
 * Son videos populares de tech/ofertas que siempre funcionan
 */
function getFallbackShorts($query = '') {
    // Videos de tech/unboxing populares que siempre están disponibles
    $fallback_videos = [
        ['id' => 'dQw4w9WgXcQ', 'title' => 'Ofertas increíbles', 'type' => 'short'],
        ['id' => 'J---aiyznGQ', 'title' => 'Lo mejor del día', 'type' => 'short'],
        ['id' => 'ZZ5LpwO-An4', 'title' => 'Descuentos top', 'type' => 'short'],
    ];
    
    // Añadir thumbnails
    foreach ($fallback_videos as &$video) {
        $video['thumbnail'] = "https://i.ytimg.com/vi/{$video['id']}/hqdefault.jpg";
        $video['thumbnail_hq'] = $video['thumbnail'];
    }
    
    // Para evitar mostrar siempre lo mismo, devolver vacío
    // El usuario verá el mensaje de "no hay shorts" que es mejor que videos random
    return [];
}

/**
 * Obtiene estadísticas de un video de YouTube (likes, comentarios, vistas)
 * @param string $videoId ID del video
 * @return array Estadísticas encontradas
 */
function getYoutubeVideoStats($videoId) {
    if (empty($videoId)) return ['likes' => 0, 'comments' => 0, 'views' => 0];

    $params = [
        'part' => 'statistics',
        'id' => $videoId
    ];

    $response = callYoutubeAPIWithRotation('videos', $params);

    if (!$response) return ['likes' => 0, 'comments' => 0, 'views' => 0];

    $data = json_decode($response, true);
    if (!isset($data['items'][0]['statistics'])) return ['likes' => 0, 'comments' => 0, 'views' => 0];

    $stats = $data['items'][0]['statistics'];
    return [
        'likes' => (int)($stats['likeCount'] ?? 0),
        'comments' => (int)($stats['commentCount'] ?? 0),
        'views' => (int)($stats['viewCount'] ?? 0)
    ];
}

/**
 * Extrae palabras clave significativas de un texto
 * @param string $text Texto a procesar
 * @return array Palabras clave únicas
 */
function extractKeywords($text) {
    // Normalizar: minúsculas, quitar caracteres especiales
    $text = strtolower($text);
    $text = preg_replace('/[^a-záéíóúñü0-9\s]/u', ' ', $text);
    $words = preg_split('/\s+/', trim($text));
    
    // Stopwords comunes en español e inglés (palabras que no aportan relevancia)
    $stopwords = [
        'review', 'unboxing', 'español', 'shorts', 'video', 'nuevo', 'mejor', 'top',
        'oferta', 'chollo', 'amazon', 'prime', 'barato', 'descuento', 'oferton',
        'probando', 'test', 'prueba', 'opinion', 'vale', 'pena', 'merece', 'comprar',
        'para', 'con', 'sin', 'como', 'que', 'del', 'los', 'las', 'una', 'uno',
        'the', 'and', 'for', 'with', 'you', 'this', 'best', 'color', 'negro', 'blanco',
        'rojo', 'azul', 'rosa', 'talla', 'size', 'pack', 'unisex', 'club'
    ];
    
    // Filtrar: solo palabras >2 caracteres y no stopwords
    $keywords = [];
    foreach ($words as $word) {
        if (strlen($word) > 2 && !in_array($word, $stopwords)) {
            $keywords[] = $word;
        }
    }
    
    return array_unique($keywords);
}

/**
 * Valida si un video de YouTube es relevante para un producto dado
 * Compara palabras clave del producto con el título del video
 * @param string $videoTitle Título del video
 * @param string $productName Nombre del producto
 * @return bool True si el video parece relevante
 */
function isVideoRelevant($videoTitle, $productName) {
    $product_keywords = extractKeywords($productName);
    $video_keywords = extractKeywords($videoTitle);
    
    // Si no hay keywords del producto, aceptar cualquier video
    if (empty($product_keywords)) {
        return true;
    }
    
    // Buscar coincidencias
    $matches = array_intersect($product_keywords, $video_keywords);
    
    // Requiere al menos 1 palabra clave coincidente para ser relevante
    // Si el producto tiene una marca conocida, debería aparecer en el video
    return count($matches) >= 1;
}
