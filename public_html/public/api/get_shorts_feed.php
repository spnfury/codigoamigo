<?php
include_once __DIR__ . '/../../inc/logger.php';
// API Endpoint para obtener el feed de shorts (estilo TikTok)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Log errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Incluir funciones
require_once __DIR__ . '/../../myphp/funciones.php';
require_once __DIR__ . '/../../myphp/funciones_chollos.php';
require_once __DIR__ . '/../../myphp/funciones_youtube.php';
require_once __DIR__ . '/../../myphp/funciones_video_cache.php';
require_once __DIR__ . '/../../myphp/funciones_chollos_helpers.php';

// Limitar tiempo de ejecución global
set_time_limit(30);
$start_time = microtime(true);

// Parámetros
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50; // Increased pool to find video matches
$skip = ($page - 1) * 12; // But keep skip aligned with "visible" page size (approx 12)

try {
    // Obtener historial de vistos de la sesión
    session_start(); // Ensure session is started
    $viewed_shorts = $_SESSION['viewed_shorts'] ?? [];
    
    // Si es página 1 y estamos dando "vueltas" (pocos resultados), limpiamos historial para no dejarlo vacío
    if ($page === 1 && count($viewed_shorts) > 100) {
        $_SESSION['viewed_shorts'] = array_slice($viewed_shorts, -20); // Quedarse solo con los últimos 20
    }
    
    // Obtener chollos recientes que tengan video corto (simulado por ahora con búsqueda)
    // En producción idealmente tendríamos un flag has_short o similar
    $collection = getCollectionChollos();
    
    // Excluir los ya vistos
    $match_stage = [
        'enlace' => ['$ne' => null], // Tener enlace
        'imagen' => ['$ne' => null]  // Tener imagen
    ];
    
    if (!empty($viewed_shorts)) {
        $converted_ids = [];
        foreach ($viewed_shorts as $id) {
            try {
                if (is_string($id) && strlen($id) === 24) {
                    $converted_ids[] = new MongoDB\BSON\ObjectId($id);
                }
            } catch (Exception $e) {}
        }
        if (!empty($converted_ids)) {
            $match_stage['_id'] = ['$nin' => $converted_ids];
        }
    }
    
    $cursor = $collection->aggregate([
        ['$match' => $match_stage],
        ['$sort' => ['fecha_inicio' => -1]],
        ['$skip' => $skip],
        ['$limit' => $limit]
    ]);
    
    $chollos = [];
    foreach ($cursor as $doc) {
        $chollos[] = (array) $doc;
    }

    // Si después de filtrar no hay chollos, buscar SIN filtrar por vistos para tener material
    if (empty($chollos) && !empty($match_stage['_id'])) {
        unset($match_stage['_id']);
        $cursor = $collection->aggregate([
            ['$match' => $match_stage],
            ['$sort' => ['fecha_inicio' => -1]],
            ['$skip' => $skip],
            ['$limit' => $limit]
        ]);
        foreach ($cursor as $doc) {
            $chollos[] = (array) $doc;
        }
    }

    $results = [];
    
    foreach ($chollos as $chollo) {
        // Si llevamos más de 20 segundos, paramos para no dar timeout al cliente
        if (microtime(true) - $start_time > 20) {
            break;
        }

        // Obtener query de búsqueda (priorizar parámetro 'q' o cualquier otro sospechoso si viene de URL)
        $q_param = isset($_GET['q']) ? trim($_GET['q']) : null;
        if (!$q_param && isset($_GET['asdasd'])) $q_param = trim($_GET['asdasd']); // Por el ejemplo del usuario

        if ($q_param) {
            $search_query = $q_param;
        } else {
            // Extraer nombre del producto
            $product_name = extractProductNameGroq($chollo['titulo'], $chollo['descripcion']);
            
            // Construir query más específica para evitar resultados genéricos
            $search_query = $product_name;
            
            // Si parece un producto, añadir keywords de intención
            if (strlen($product_name) > 3) {
                $keywords = ['review español', 'unboxing español', 'probando'];
                $suffix = $keywords[array_rand($keywords)];
                $search_query .= ' ' . $suffix;
            }
        }

        // Buscar shorts en YouTube CON CACHÉ (usando la query refinada)
        $shorts = getYouTubeVideosWithCache($search_query, 'shorts');
        
        if (!empty($shorts)) {
            // Buscar un video REALMENTE relevante (no solo el primero)
            $best_short = null;
            foreach ($shorts as $s) {
                if (isVideoRelevant($s['title'] ?? '', $product_name)) {
                    $best_short = $s;
                    break;
                }
            }
            
            // Si ningún video es relevante, saltamos este chollo
            if (!$best_short) {
                continue;
            }
            
            $chollo_id_str = (string)$chollo['_id'];

            // LOG: Guardar la cadena exacta de búsqueda usada
            try {
                $db_conn = createConnection();
                if ($db_conn) {
                    $db_conn->shorts_logs->insertOne([
                        'query' => $search_query,
                        'video_id' => $best_short['id'],
                        'video_title' => $best_short['title'] ?? 'Short',
                        'chollo_id' => $chollo_id_str,
                        'timestamp' => new MongoDB\BSON\UTCDateTime(),
                        'source' => $q_param ? 'url' : 'auto',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                    ]);
                }
            } catch (Exception $log_err) {
                log_error("Error logging shorts search: " . $log_err->getMessage());
            }

            // Generar URL correcta con categoría usando el helper del sistema
            $categoria = !empty($chollo['categoria']) ? $chollo['categoria'] : 'ofertas';
            $categoria_slug = categoriaToSlug($categoria);
            $chollo_url = '/chollos/' . $categoria_slug . '/' . $chollo_id_str . '?source=shorts';
            
            $results[] = [
                'chollo_id' => $chollo_id_str,
                'titulo' => $chollo['titulo'],
                'precio' => $chollo['precio_descuento'],
                'precio_original' => $chollo['precio_original'],
                'descuento' => $chollo['porcentaje_descuento'],
                'imagen' => $chollo['imagen'],
                'video_id' => $best_short['id'],
                'video_title' => $best_short['title'],
                'video_thumbnail' => $best_short['thumbnail_hq'] ?? $best_short['thumbnail'],
                'video_channel' => $best_short['channel'] ?? '',
                'link' => $chollo_url,
                'external_link' => 'https://www.codigoamigo.com/chollo/' . $chollo_id_str . '?source=shorts',
                'comments_count' => $best_short['comments'] ?? $chollo['total_comentarios'] ?? 0,
                'temperature' => $best_short['likes'] ?? $chollo['temperatura'] ?? 0,
                'asin' => $chollo['asin'] ?? null
            ];
        }
        
        // Si ya tenemos suficientes resultados útiles para una "pantalla", devolvemos
        // para mantener latencia controlada. El scraping de YT es el cuello de botella.
        
        if (count($results) >= 6) {
             // Actualizar historial de sesión
             foreach ($results as $res) {
                 if (!in_array($res['chollo_id'], $viewed_shorts)) {
                     $_SESSION['viewed_shorts'][] = (string)$res['chollo_id'];
                 }
             }
             break;
        }
    }
    
    // FALLBACK: Si no hay videos de YouTube, usar videos curados de MongoDB
    if (empty($results) && $page == 1) {
        // Obtener shorts curados de la base de datos
        $db = createConnection();
        if ($db) {
            $curated_cursor = $db->curated_shorts->find(
                ['active' => true],
                ['limit' => 10, 'sort' => ['added_at' => -1]]
            );
            $curated_shorts = [];
            foreach ($curated_cursor as $doc) {
                $curated_shorts[] = (array) $doc;
            }
            
            // Emparejar chollos con videos curados
            if (!empty($curated_shorts)) {
                $video_index = 0;
                foreach ($chollos as $chollo) {
                    if ($video_index >= count($curated_shorts)) break;
                    if (count($results) >= 10) break; // Limit total results
                    
                    $curated = $curated_shorts[$video_index];
                    $chollo_id_str = (string)$chollo['_id'];
                    
                    // Generar URL correcta con categoría usando el helper del sistema
                    $categoria = !empty($chollo['categoria']) ? $chollo['categoria'] : 'ofertas';
                    $categoria_slug = categoriaToSlug($categoria);
                    $chollo_url = '/chollos/' . $categoria_slug . '/' . $chollo_id_str . '?source=shorts';
                    
                    $results[] = [
                        'chollo_id' => $chollo_id_str,
                        'titulo' => $chollo['titulo'],
                        'precio' => $chollo['precio_descuento'],
                        'precio_original' => $chollo['precio_original'],
                        'descuento' => $chollo['porcentaje_descuento'],
                        'imagen' => $chollo['imagen'],
                        'video_id' => $curated['video_id'],
                        'video_title' => $curated['title'] ?? 'Short destacado',
                        'video_thumbnail' => "https://i.ytimg.com/vi/{$curated['video_id']}/hqdefault.jpg",
                        'video_channel' => $curated['channel'] ?? 'CodigoAmigo',
                        'link' => $chollo_url,
                        'external_link' => 'https://www.codigoamigo.com/chollo/' . $chollo_id_str . '?source=shorts',
                        'is_curated' => true,
                        'comments_count' => $curated['comments'] ?? $chollo['total_comentarios'] ?? 0,
                        'temperature' => $curated['likes'] ?? $chollo['temperatura'] ?? 0
                    ];
                    
                    // Add to session immediately for fallback items too
                    if (!in_array($chollo_id_str, $_SESSION['viewed_shorts'] ?? [])) {
                         $_SESSION['viewed_shorts'][] = $chollo_id_str;
                    }
                    $video_index++;
                }
            }
        }
    }
    
    // EMERGENCIA: Si después de todo sigue vacío en página 1, mandar 6 chollos aleatorios/recientes sin filtros
    if (empty($results) && $page == 1) {
        $emergency_cursor = $collection->find(['enlace' => ['$ne' => null]], ['limit' => 6, 'sort' => ['fecha_creacion' => -1]]);
        foreach ($emergency_cursor as $chollo) {
            $chollo_id_str = (string)$chollo['_id'];
            $results[] = [
                'chollo_id' => $chollo_id_str,
                'titulo' => $chollo['titulo'],
                'video_id' => '8zV0nEq7I1Y', // Video genérico de ofertas
                'video_thumbnail' => "https://i.ytimg.com/vi/8zV0nEq7I1Y/hqdefault.jpg",
                'external_link' => 'https://www.codigoamigo.com/chollo/' . $chollo_id_str . '?source=shorts',
                'is_emergency' => true,
                'comments_count' => $chollo['total_comentarios'] ?? 0,
                'temperature' => $chollo['temperatura'] ?? 0
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'page' => $page,
        'count' => count($results),
        'has_more' => count($chollos) >= $limit
    ]);

} catch (Exception $e) {
    log_error("Error in get_shorts_feed.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
