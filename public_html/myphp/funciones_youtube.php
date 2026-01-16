<?php

/**
 * Funciones para YouTube y extracción de productos
 */

// Incluir configuración de IA si no está
if (file_exists(__DIR__ . '/../config/ai_config.php')) {
    include_once __DIR__ . '/../config/ai_config.php';
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

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, defined('GROQ_API_URL') ? GROQ_API_URL : 'https://api.groq.com/openai/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

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
                        "Accept-Language: es-ES,es;q=0.9\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    
    // Intentar obtener el HTML
    $html = @file_get_contents($url, false, $context);
    
    if (!$html) return [];

    // Extraer datos iniciales JSON (ytInitialData)
    if (preg_match('/var ytInitialData = (\{.*?\});<\/script>/', $html, $matches)) {
        $json_data = json_decode($matches[1], true);
        
        if (!$json_data) return [];
        
        // Navegar el JSON para encontrar los items
        // Esta estructura cambia a veces, hay que ser flexible o usar recursividad limitada
        // Generalmente: contents -> twoColumnSearchResultsRenderer -> primaryContents -> sectionListRenderer -> contents -> itemSectionRenderer -> contents
        
        $contents = $json_data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'][0]['itemSectionRenderer']['contents'] ?? [];
        
        foreach ($contents as $item) {
            $video_data = [];
            
            if ($type === 'shorts' && isset($item['reelItemRenderer'])) {
                // Es un Short (en vista mixta) o reelItem
                $renderer = $item['reelItemRenderer'];
                $video_data = [
                    'id' => $renderer['videoId'],
                    'title' => $renderer['headline']['simpleText'],
                    'thumbnail' => $renderer['thumbnail']['thumbnails'][0]['url'],
                    'type' => 'short'
                ];
            } elseif ($type === 'video' && isset($item['videoRenderer'])) {
                // Es un Video normal
                $renderer = $item['videoRenderer'];
                $video_data = [
                    'id' => $renderer['videoId'],
                    'title' => $renderer['title']['runs'][0]['text'] ?? '',
                    'thumbnail' => $renderer['thumbnail']['thumbnails'][0]['url'],
                    'length' => $renderer['lengthText']['simpleText'] ?? '',
                    'views' => $renderer['viewCountText']['simpleText'] ?? '',
                    'author' => $renderer['ownerText']['runs'][0]['text'] ?? '',
                    'type' => 'video'
                ];
            }
            
            if (!empty($video_data['id'])) {
                // Mejorar calidad de thumbnail si es posible
                // hqdefault.jpg es mejor que las urls raras de yt
                $video_data['thumbnail_hq'] = "https://i.ytimg.com/vi/{$video_data['id']}/hqdefault.jpg";
                
                $videos[] = $video_data;
                if (count($videos) >= 6) break; // Limite
            }
        }
    }
    
    // Si no encontramos nada con el método JSON, fallback a regex simple sobre el HTML (menos fiable)
    if (empty($videos)) {
        if ($type === 'shorts') {
             preg_match_all('/"reelItemRenderer":{"videoId":"([^"]+)","headline":{"simpleText":"([^"]+)"}.*?"thumbnails":\[{"url":"([^"]+)"/', $html, $matches, PREG_SET_ORDER);
             
             foreach ($matches as $m) {
                 $videos[] = [
                     'id' => $m[1],
                     'title' => $m[2],
                     'thumbnail' => $m[3],
                     'thumbnail_hq' => "https://i.ytimg.com/vi/{$m[1]}/hqdefault.jpg",
                     'type' => 'short'
                 ];
                 if (count($videos) >= 6) break;
             }
        } else {
            preg_match_all('/"videoRenderer":{"videoId":"([^"]+)","thumbnail":\{"thumbnails":\[\{"url":"([^"]+)".*?"title":\{"runs":\[\{"text":"([^"]+)"/', $html, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $m) {
                 $videos[] = [
                     'id' => $m[1],
                     'thumbnail' => $m[2],
                     'title' => $m[3],
                     'thumbnail_hq' => "https://i.ytimg.com/vi/{$m[1]}/hqdefault.jpg",
                     'type' => 'video'
                 ];
                 if (count($videos) >= 6) break;
            }
        }
    }

    return $videos;
}
