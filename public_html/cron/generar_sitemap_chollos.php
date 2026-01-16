<?php
// Script para generar sitemaps de chollos y marcas
error_reporting(E_ERROR | E_PARSE);
global $website;
$website = "https://www.codigoamigo.com/";
$GLOBALS["website"] = $website;

require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_helpers.php';
require_once __DIR__ . '/../myphp/funciones_sitemaps.php'; // Necesario para generarSitemapMarcas

function generarSitemapChollosCron() {
    $base_url = 'https://www.codigoamigo.com';
    $path_sitemaps = dirname(__DIR__); // public_html raíz
    
    // 0. Regenerar Sitemap de Marcas
    generarSitemapMarcas();
    
    // 1. Generar Sitemap de Categorías
    $urls_categorias = [];
    
    // Obtener todas las categorías y subcategorías usadas
    $collection = getCollectionChollos();
    $chollos = $collection->find(['estado' => 1], ['projection' => ['categoria' => 1]]);
    
    $rutas_vistas = [];
    
    foreach ($chollos as $chollo) {
        $categorias = $chollo['categoria'];
        
        // Normalizar BSONArray a array nativo
        if (is_object($categorias) && method_exists($categorias, 'getArrayCopy')) {
            $categorias = $categorias->getArrayCopy();
        } elseif (!is_array($categorias)) {
            $categorias = [$categorias];
        }
        
        if (!empty($categorias)) {
            $ruta_actual = '';
            foreach ($categorias as $cat) {
                if (is_string($cat)) { // Asegurar que es string
                    $cat_slug = categoriaToSlug($cat);
                    $ruta_actual .= ($ruta_actual ? '/' : '') . $cat_slug;
                    
                    if (!in_array($ruta_actual, $rutas_vistas)) {
                        $rutas_vistas[] = $ruta_actual;
                        $urls_categorias[] = [
                            'loc' => $base_url . '/chollos/' . $ruta_actual,
                            'changefreq' => 'daily',
                            'priority' => '0.8'
                        ];
                    }
                }
            }
        }
    }
    
    // Añadir raíz de chollos
    $urls_categorias[] = [
        'loc' => $base_url . '/chollos',
        'changefreq' => 'hourly',
        'priority' => '0.9'
    ];
    
    crearArchivoSitemapCron($path_sitemaps . '/sitemap_chollos_categorias.xml', $urls_categorias);
    
    // 2. Generar Sitemap de Detalles (últimos 10,000 chollos para no hacer un archivo gigante)
    $urls_detalles = [];
    $chollos_detalle = $collection->find(
        ['estado' => 1], 
        [
            'sort' => ['fecha_creacion' => -1],
            'limit' => 10000,
            'projection' => ['_id' => 1, 'categoria' => 1, 'fecha_creacion' => 1] // fecha para lastmod
        ]
    );
    
    foreach ($chollos_detalle as $chollo) {
        $ruta_cat = '';
        $categorias = $chollo['categoria'];
        
        // Normalizar BSONArray
        if (is_object($categorias) && method_exists($categorias, 'getArrayCopy')) {
            $categorias = $categorias->getArrayCopy();
        } elseif (!is_array($categorias)) {
            $categorias = [$categorias];
        }
        
        if (!empty($categorias)) {
            // Filtrar solo strings
            $categorias = array_filter($categorias, 'is_string');
            $slugs = array_map('categoriaToSlug', $categorias);
            $ruta_cat = implode('/', $slugs);
        } else {
            $ruta_cat = 'general';
        }
        
        $lastmod = date('Y-m-d');
        if (isset($chollo['fecha_creacion']) && $chollo['fecha_creacion'] instanceof MongoDB\BSON\UTCDateTime) {
            $lastmod = $chollo['fecha_creacion']->toDateTime()->format('Y-m-d');
        }
        
        $urls_detalles[] = [
            'loc' => $base_url . '/chollos/' . $ruta_cat . '/' . (string)$chollo['_id'],
            'changefreq' => 'weekly',
            'priority' => '0.6',
            'lastmod' => $lastmod
        ];
    }
    
    crearArchivoSitemapCron($path_sitemaps . '/sitemap_chollos_detalle.xml', $urls_detalles);
    
    // 3. Regenerar Índice Principal (sitemap.xml)
    $sitemaps = [
        'https://www.codigoamigo.com/myphp/xml/sitemap_marcas.xml',
        'https://www.codigoamigo.com/myphp/xml/sitemap_categorias.xml',
        $base_url . '/sitemap_chollos_categorias.xml',
        $base_url . '/sitemap_chollos_detalle.xml'
    ];
    
    $content_index = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $content_index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    
    foreach ($sitemaps as $loc) {
        $content_index .= '  <sitemap>' . PHP_EOL;
        $content_index .= '    <loc>' . $loc . '</loc>' . PHP_EOL;
        $content_index .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
        $content_index .= '  </sitemap>' . PHP_EOL;
    }
    
    $content_index .= '</sitemapindex>';
    
    file_put_contents($path_sitemaps . '/sitemap.xml', $content_index);
    
    // 4. Notificar a motores de búsqueda (Pings)
    pingSearchEnginesCron($base_url . '/sitemap.xml');
    
    return true;
}

function pingSearchEnginesCron($sitemap_url) {
    // 1. Ping Google
    $google_ping_url = "https://www.google.com/ping?sitemap=" . urlencode($sitemap_url);
    @file_get_contents($google_ping_url);
    
    // 2. IndexNow (Bing, Yandex, etc.)
    // Definir la clave de IndexNow (32 caracteres hexadecimales)
    $key = "14e44366b64e6e889f71d29b267cfd05"; // Clave generada basada en el ID de conversación
    $key_file = dirname(__DIR__) . "/indexnow_key.txt";
    
    if (!file_exists($key_file)) {
        file_put_contents($key_file, $key);
    }
    
    $indexnow_url = "https://www.bing.com/indexnow";
    $data = [
        "host" => "www.codigoamigo.com",
        "key" => $key,
        "keyLocation" => "https://www.codigoamigo.com/indexnow_key.txt",
        "urlList" => [
            "https://www.codigoamigo.com/sitemap.xml",
            "https://www.codigoamigo.com/sitemap_chollos_detalle.xml",
            "https://www.codigoamigo.com/chollos"
        ]
    ];
    
    $ch = curl_init($indexnow_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

function crearArchivoSitemapCron($filepath, $urls) {
    $content = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    
    foreach ($urls as $url) {
        $content .= '  <url>' . PHP_EOL;
        $content .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . PHP_EOL;
        if (isset($url['lastmod'])) {
            $content .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . PHP_EOL;
        }
        $content .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
        $content .= '    <priority>' . $url['priority'] . '</priority>' . PHP_EOL;
        $content .= '  </url>' . PHP_EOL;
    }
    
    $content .= '</urlset>';
    
    file_put_contents($filepath, $content);
}

// Ejecutar si se llama directamente
if (php_sapi_name() === 'cli') {
    generarSitemapChollosCron();
    echo "Sitemaps de chollos y marcas regenerados.\n";
}
