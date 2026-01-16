<?php
/**
 * Funciones para generar y gestionar sitemaps de CodigoAmigo
 */

if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}

if (!function_exists('link_marca')) {
    include_once __DIR__ . '/links.php';
}

function getCollectionSitemapLogs() {
    $db = createConnection();
    if (!$db) return null;
    try {
        return $db->selectCollection('sitemap_logs');
    } catch (Throwable $e) {
        error_log("Error al obtener colección de logs de sitemaps: " . $e->getMessage());
        return null;
    }
}

function registrarGeneracionSitemap($tipo, $resultado) {
    $collection = getCollectionSitemapLogs();
    if (!$collection) return false;
    try {
        $log = [
            'tipo' => $tipo,
            'fecha' => new MongoDB\BSON\UTCDateTime(time() * 1000),
            'usuario_id' => $_SESSION['user_id'] ?? null,
            'resultado' => $resultado,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        $collection->insertOne($log);
        return true;
    } catch (Throwable $e) {
        error_log("Error al registrar log de sitemap: " . $e->getMessage());
        return false;
    }
}

function obtenerHistorialSitemaps($limite = 50) {
    $collection = getCollectionSitemapLogs();
    if (!$collection) return [];
    try {
        $cursor = $collection->find([], ['sort' => ['fecha' => -1], 'limit' => $limite]);
        $historial = [];
        foreach ($cursor as $doc) {
            $historial[] = [
                'id' => (string)$doc['_id'],
                'tipo' => $doc['tipo'] ?? 'desconocido',
                'fecha' => isset($doc['fecha']) ? $doc['fecha']->toDateTime()->format('Y-m-d H:i:s') : '',
                'usuario_id' => $doc['usuario_id'] ?? null,
                'resultado' => $doc['resultado'] ?? [],
                'ip' => $doc['ip'] ?? null
            ];
        }
        return $historial;
    } catch (Throwable $e) {
        error_log("Error al obtener historial de sitemaps: " . $e->getMessage());
        return [];
    }
}

function generarSitemapPrincipal($incluir_codigos = false, $incluir_chollos = true) {
    $hoy = date("Y-m-d");
    $base_url = "https://www.codigoamigo.com";
    $xml_dir = __DIR__ . '/xml';
    if (!is_dir($xml_dir)) mkdir($xml_dir, 0755, true);
    
    $xml = new DOMDocument("1.0", "UTF-8");
    $xml->formatOutput = true;
    $sitemapindex = $xml->createElement("sitemapindex");
    $sitemapindex = $xml->appendChild($sitemapindex);
    $sitemapindex->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
    
    $sitemap = $xml->createElement("sitemap");
    $sitemap = $sitemapindex->appendChild($sitemap);
    $loc = $xml->createElement("loc", $base_url . "/myphp/xml/sitemap_marcas.xml");
    $sitemap->appendChild($loc);
    $lastmod = $xml->createElement("lastmod", $hoy);
    $sitemap->appendChild($lastmod);
    
    $sitemap = $xml->createElement("sitemap");
    $sitemap = $sitemapindex->appendChild($sitemap);
    $loc = $xml->createElement("loc", $base_url . "/myphp/xml/sitemap_categorias.xml");
    $sitemap->appendChild($loc);
    $lastmod = $xml->createElement("lastmod", $hoy);
    $sitemap->appendChild($lastmod);
    
    if ($incluir_codigos) {
        $sitemap = $xml->createElement("sitemap");
        $sitemap = $sitemapindex->appendChild($sitemap);
        $loc = $xml->createElement("loc", $base_url . "/myphp/xml/sitemap_codigos.xml");
        $sitemap->appendChild($loc);
        $lastmod = $xml->createElement("lastmod", $hoy);
        $sitemap->appendChild($lastmod);
    }
    
    if ($incluir_chollos) {
        $sitemap = $xml->createElement("sitemap");
        $sitemap = $sitemapindex->appendChild($sitemap);
        $loc = $xml->createElement("loc", $base_url . "/myphp/xml/sitemap_chollos.xml");
        $sitemap->appendChild($loc);
        $lastmod = $xml->createElement("lastmod", $hoy);
        $sitemap->appendChild($lastmod);
    }
    
    $sitemap = $xml->createElement("sitemap");
    $sitemap = $sitemapindex->appendChild($sitemap);
    $loc = $xml->createElement("loc", $base_url . "/myphp/xml/sitemap_estaticas.xml");
    $sitemap->appendChild($loc);
    $lastmod = $xml->createElement("lastmod", $hoy);
    $sitemap->appendChild($lastmod);
    
    $sitemap_path = __DIR__ . '/../sitemap.xml';
    $xml->save($sitemap_path);
    
    return ['success' => true, 'archivo' => $sitemap_path, 'url' => $base_url . '/sitemap.xml', 'fecha' => $hoy];
}

function generarSitemapMarcas() {
    $hoy = date("Y-m-d");
    $base_url = "https://www.codigoamigo.com";
    $xml_dir = __DIR__ . '/xml';
    if (!is_dir($xml_dir)) mkdir($xml_dir, 0755, true);
    
    try {
        // Usar la función getMarcas que ya existe
        if (!function_exists('getMarcas')) {
            include_once __DIR__ . '/funciones_marca.php';
        }
        
        $lista_marcas = getMarcas(9999);
        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;
        $urlset = $xml->createElement("urlset");
        $urlset = $xml->appendChild($urlset);
        $urlset->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
        
        $total = 0;
        foreach ($lista_marcas as $marca) {
            $nombre_clave = is_array($marca) ? ($marca['nombre_clave'] ?? '') : ($marca->nombre_clave ?? '');
            if (empty($nombre_clave)) continue;
            
            $url_marca = "https://www.codigoamigo.com/" . link_marca($nombre_clave);
            $url = $xml->createElement("url");
            $url = $urlset->appendChild($url);
            $loc = $xml->createElement("loc", $url_marca);
            $url->appendChild($loc);
            $lastmod = $xml->createElement("lastmod", $hoy);
            $url->appendChild($lastmod);
            $changefreq = $xml->createElement("changefreq", "daily");
            $url->appendChild($changefreq);
            $priority = $xml->createElement("priority", "0.9");
            $url->appendChild($priority);
            $total++;
        }
        
        $sitemap_path = $xml_dir . '/sitemap_marcas.xml';
        $xml->save($sitemap_path);
        return ['success' => true, 'archivo' => $sitemap_path, 'url' => $base_url . '/myphp/xml/sitemap_marcas.xml', 'total_urls' => $total, 'fecha' => $hoy];
    } catch (Throwable $e) {
        error_log("Error al generar sitemap de marcas: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function generarSitemapCategorias() {
    $hoy = date("Y-m-d");
    $base_url = "https://www.codigoamigo.com";
    $xml_dir = __DIR__ . '/xml';
    if (!is_dir($xml_dir)) mkdir($xml_dir, 0755, true);
    
    try {
        $listacategorias = getCategorias();
        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;
        $urlset = $xml->createElement("urlset");
        $urlset = $xml->appendChild($urlset);
        $urlset->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
        
        $total = 0;
        foreach ($listacategorias as $categoria) {
            $nombre_clave = is_array($categoria) ? ($categoria['nombre_clave'] ?? '') : ($categoria->nombre_clave ?? '');
            if (empty($nombre_clave)) continue;
            
            $url_categoria = "https://www.codigoamigo.com/" . link_categoria($nombre_clave);
            $url = $xml->createElement("url");
            $url = $urlset->appendChild($url);
            $loc = $xml->createElement("loc", $url_categoria);
            $url->appendChild($loc);
            $lastmod = $xml->createElement("lastmod", $hoy);
            $url->appendChild($lastmod);
            $changefreq = $xml->createElement("changefreq", "weekly");
            $url->appendChild($changefreq);
            $priority = $xml->createElement("priority", "0.8");
            $url->appendChild($priority);
            $total++;
        }
        
        $sitemap_path = $xml_dir . '/sitemap_categorias.xml';
        $xml->save($sitemap_path);
        return ['success' => true, 'archivo' => $sitemap_path, 'url' => $base_url . '/myphp/xml/sitemap_categorias.xml', 'total_urls' => $total, 'fecha' => $hoy];
    } catch (Throwable $e) {
        error_log("Error al generar sitemap de categorías: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function generarSitemapCodigos($limite = 10000) {
    $hoy = date("Y-m-d");
    $base_url = "https://www.codigoamigo.com";
    $xml_dir = __DIR__ . '/xml';
    if (!is_dir($xml_dir)) mkdir($xml_dir, 0755, true);
    
    $collection_codigos = getCollectionCodigos();
    if (!$collection_codigos) return ['success' => false, 'error' => 'Error de conexión'];
    
    try {
        $codigos = $collection_codigos->find(['estado' => 0], ['sort' => ['fecha_publicacion' => -1, '_id' => -1], 'limit' => $limite]);
        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;
        $urlset = $xml->createElement("urlset");
        $urlset = $xml->appendChild($urlset);
        $urlset->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
        
        $total = 0;
        foreach ($codigos as $codigo) {
            $marca = $codigo['marca'] ?? '';
            $id_codigo = (string)($codigo['_id'] ?? '');
            if (empty($marca) || empty($id_codigo)) continue;
            
            $url_codigo = link_codigo_sitemap($id_codigo, $marca);
            $url = $xml->createElement("url");
            $url = $urlset->appendChild($url);
            $loc = $xml->createElement("loc", $url_codigo);
            $url->appendChild($loc);
            $lastmod = $xml->createElement("lastmod", $hoy);
            $url->appendChild($lastmod);
            $changefreq = $xml->createElement("changefreq", "daily");
            $url->appendChild($changefreq);
            $priority = $xml->createElement("priority", "0.7");
            $url->appendChild($priority);
            $total++;
        }
        
        $sitemap_path = $xml_dir . '/sitemap_codigos.xml';
        $xml->save($sitemap_path);
        return ['success' => true, 'archivo' => $sitemap_path, 'url' => $base_url . '/myphp/xml/sitemap_codigos.xml', 'total_urls' => $total, 'fecha' => $hoy];
    } catch (Throwable $e) {
        error_log("Error al generar sitemap de códigos: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function generarSitemapChollos($limite = 5000) {
    $hoy = date("Y-m-d");
    $base_url = "https://www.codigoamigo.com";
    $xml_dir = __DIR__ . '/xml';
    if (!is_dir($xml_dir)) mkdir($xml_dir, 0755, true);
    
    $collection_chollos = getCollectionChollos();
    if (!$collection_chollos) return ['success' => false, 'error' => 'Error de conexión'];
    
    try {
        $fecha_actual = date('Y-m-d H:i:s');
        $chollos = $collection_chollos->find([
            'estado' => 1,
            '$or' => [['fecha_fin' => null], ['fecha_fin' => ['$gte' => $fecha_actual]]]
        ], ['sort' => ['fecha_creacion' => -1], 'limit' => $limite]);
        
        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;
        $urlset = $xml->createElement("urlset");
        $urlset = $xml->appendChild($urlset);
        $urlset->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
        
        $total = 0;
        foreach ($chollos as $chollo) {
            $chollo_id = (string)($chollo['_id'] ?? '');
            if (empty($chollo_id)) continue;
            
            $url_chollo = $base_url . "/chollos_detalle.php?id=" . $chollo_id;
            $url = $xml->createElement("url");
            $url = $urlset->appendChild($url);
            $loc = $xml->createElement("loc", $url_chollo);
            $url->appendChild($loc);
            $lastmod = $xml->createElement("lastmod", $hoy);
            $url->appendChild($lastmod);
            $changefreq = $xml->createElement("changefreq", "daily");
            $url->appendChild($changefreq);
            $priority = $xml->createElement("priority", "0.8");
            $url->appendChild($priority);
            $total++;
        }
        
        $sitemap_path = $xml_dir . '/sitemap_chollos.xml';
        $xml->save($sitemap_path);
        return ['success' => true, 'archivo' => $sitemap_path, 'url' => $base_url . '/myphp/xml/sitemap_chollos.xml', 'total_urls' => $total, 'fecha' => $hoy];
    } catch (Throwable $e) {
        error_log("Error al generar sitemap de chollos: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function generarSitemapEstaticas() {
    $hoy = date("Y-m-d");
    $base_url = "https://www.codigoamigo.com";
    $xml_dir = __DIR__ . '/xml';
    if (!is_dir($xml_dir)) mkdir($xml_dir, 0755, true);
    
    $paginas_estaticas = [
        ['url' => $base_url . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
        ['url' => $base_url . '/listado-marcas', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['url' => $base_url . '/listado-categorias', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['url' => $base_url . '/chollos_listado.php', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['url' => $base_url . '/contacto', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['url' => $base_url . '/aviso_legal', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ['url' => $base_url . '/politica_de_privacidad', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ['url' => $base_url . '/sobre_nosotros', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['url' => $base_url . '/registro', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ];
    
    try {
        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;
        $urlset = $xml->createElement("urlset");
        $urlset = $xml->appendChild($urlset);
        $urlset->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
        
        foreach ($paginas_estaticas as $pagina) {
            $url = $xml->createElement("url");
            $url = $urlset->appendChild($url);
            $loc = $xml->createElement("loc", $pagina['url']);
            $url->appendChild($loc);
            $lastmod = $xml->createElement("lastmod", $hoy);
            $url->appendChild($lastmod);
            $changefreq = $xml->createElement("changefreq", $pagina['changefreq']);
            $url->appendChild($changefreq);
            $priority = $xml->createElement("priority", $pagina['priority']);
            $url->appendChild($priority);
        }
        
        $sitemap_path = $xml_dir . '/sitemap_estaticas.xml';
        $xml->save($sitemap_path);
        return ['success' => true, 'archivo' => $sitemap_path, 'url' => $base_url . '/myphp/xml/sitemap_estaticas.xml', 'total_urls' => count($paginas_estaticas), 'fecha' => $hoy];
    } catch (Throwable $e) {
        error_log("Error al generar sitemap de páginas estáticas: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function generarTodosLosSitemaps($opciones = []) {
    $incluir_codigos = $opciones['incluir_codigos'] ?? false;
    $incluir_chollos = $opciones['incluir_chollos'] ?? true;
    $limite_codigos = $opciones['limite_codigos'] ?? 10000;
    $limite_chollos = $opciones['limite_chollos'] ?? 5000;
    
    $resultados = ['fecha_inicio' => date('Y-m-d H:i:s'), 'sitemaps' => []];
    
    $resultado_principal = generarSitemapPrincipal($incluir_codigos, $incluir_chollos);
    $resultados['sitemaps']['principal'] = $resultado_principal;
    
    $resultado_marcas = generarSitemapMarcas();
    $resultados['sitemaps']['marcas'] = $resultado_marcas;
    
    $resultado_categorias = generarSitemapCategorias();
    $resultados['sitemaps']['categorias'] = $resultado_categorias;
    
    if ($incluir_codigos) {
        $resultado_codigos = generarSitemapCodigos($limite_codigos);
        $resultados['sitemaps']['codigos'] = $resultado_codigos;
    }
    
    if ($incluir_chollos) {
        $resultado_chollos = generarSitemapChollos($limite_chollos);
        $resultados['sitemaps']['chollos'] = $resultado_chollos;
    }
    
    $resultado_estaticas = generarSitemapEstaticas();
    $resultados['sitemaps']['estaticas'] = $resultado_estaticas;
    
    $resultados['fecha_fin'] = date('Y-m-d H:i:s');
    
    $total_urls = 0;
    $exitosos = 0;
    $fallidos = 0;
    
    foreach ($resultados['sitemaps'] as $tipo => $resultado) {
        if (isset($resultado['success']) && $resultado['success']) {
            $exitosos++;
            if (isset($resultado['total_urls'])) {
                $total_urls += $resultado['total_urls'];
            }
        } else {
            $fallidos++;
        }
    }
    
    $resultados['resumen'] = [
        'total_sitemaps' => count($resultados['sitemaps']),
        'exitosos' => $exitosos,
        'fallidos' => $fallidos,
        'total_urls' => $total_urls
    ];
    
    registrarGeneracionSitemap('completo', $resultados);
    return $resultados;
}

function obtenerEstadisticasSitemaps() {
    $xml_dir = __DIR__ . '/xml';
    $sitemap_principal = __DIR__ . '/../sitemap.xml';
    $base_url = "https://www.codigoamigo.com";
    
    $estadisticas = ['sitemaps' => [], 'total_urls' => 0, 'ultima_generacion' => null];
    
    if (file_exists($sitemap_principal)) {
        $estadisticas['sitemaps']['principal'] = [
            'existe' => true,
            'tamaño' => filesize($sitemap_principal),
            'fecha_modificacion' => date('Y-m-d H:i:s', filemtime($sitemap_principal)),
            'url' => $base_url . '/sitemap.xml'
        ];
    } else {
        $estadisticas['sitemaps']['principal'] = ['existe' => false];
    }
    
    $tipos_urls = [
        'marcas' => $base_url . '/myphp/xml/sitemap_marcas.xml',
        'categorias' => $base_url . '/myphp/xml/sitemap_categorias.xml',
        'codigos' => $base_url . '/myphp/xml/sitemap_codigos.xml',
        'chollos' => $base_url . '/myphp/xml/sitemap_chollos.xml',
        'estaticas' => $base_url . '/myphp/xml/sitemap_estaticas.xml'
    ];
    
    foreach ($tipos_urls as $tipo => $url) {
        $archivo = $xml_dir . '/sitemap_' . $tipo . '.xml';
        if (file_exists($archivo)) {
            $xml_content = file_get_contents($archivo);
            $url_count = substr_count($xml_content, '<url>');
            $estadisticas['sitemaps'][$tipo] = [
                'existe' => true,
                'tamaño' => filesize($archivo),
                'total_urls' => $url_count,
                'fecha_modificacion' => date('Y-m-d H:i:s', filemtime($archivo)),
                'url' => $url
            ];
            $estadisticas['total_urls'] += $url_count;
            if (!$estadisticas['ultima_generacion'] || filemtime($archivo) > strtotime($estadisticas['ultima_generacion'])) {
                $estadisticas['ultima_generacion'] = date('Y-m-d H:i:s', filemtime($archivo));
            }
        } else {
            $estadisticas['sitemaps'][$tipo] = ['existe' => false, 'url' => $url];
        }
    }
    
    $historial = obtenerHistorialSitemaps(1);
    if (!empty($historial)) {
        $estadisticas['ultima_generacion_log'] = $historial[0]['fecha'] ?? null;
    }
    
    return $estadisticas;
}
