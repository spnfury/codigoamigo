<?php
/**
 * Cron diario: Regenera sitemap y lo envía a Google Search Console.
 * 
 * Uso: php /home/admin/web/codigoamigo.com/public_html/cron/daily_sitemap.php
 * Cron: 0 6 * * * php /home/admin/web/codigoamigo.com/public_html/cron/daily_sitemap.php >> /tmp/cron_sitemap.log 2>&1
 */
set_time_limit(120);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_sitemaps.php';

$db = createConnection();
$today = date('Y-m-d');
$base_url = 'https://www.codigoamigo.com';
$log = function($msg) { echo "[" . date('Y-m-d H:i:s') . "] $msg\n"; };

// ─── 1. Regenerar sitemap_marcas.xml ───
// Excluir marcas marcadas como inactiva_seo (sin código creado en >12 meses)
// para evitar diluir relevancia con páginas estancadas.
$marcas = $db->marcas->find(
    ['estado' => 1, 'inactiva_seo' => ['$ne' => true]],
    ['projection' => ['nombre_clave' => 1]]
)->toArray();

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$xml .= "  <url>\n    <loc>{$base_url}/</loc>\n    <lastmod>{$today}</lastmod>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";

// Marcas con al menos un código vigente.
//
// /de-{slug} devuelve noindex cuando la marca se queda sin códigos que enseñar,
// así que anunciarla aquí es pedirle a Google que indexe una página que se
// declara no indexable. Eran 23 en la auditoría del 2026-08-07. Vuelven al
// sitemap solas en cuanto la marca recibe un código.
//
// El criterio tiene que ser el MISMO que usa la ficha, o vuelven a discrepar:
// no basta con estado 0, hay que descartar también los caducados por
// fecha_validez. Marcas como twothirds tienen códigos activos pero todos
// vencidos, y su ficha muestra "0 códigos verificados".
$hoy_validez = date('Y-m-d');
$con_codigos = [];
foreach ($db->codigos->distinct('marca', [
    'estado' => 0,
    '$nor'   => [[
        'fecha_validez' => ['$type' => 'string', '$ne' => '', '$lt' => $hoy_validez],
    ]],
]) as $mk) {
    $con_codigos[(string)$mk] = true;
}

$count = 0;
$sin_codigos = 0;
$slug_invalido = 0;

foreach ($marcas as $m) {
    $slug = $m['nombre_clave'] ?? '';
    if (empty($slug)) continue;

    // Hay slugs corruptos en BD ('https://octopusenergyes/', 'atuladoenergÍa')
    // que producían URLs con 301 o 400 dentro del sitemap.
    if (!preg_match('/^[a-z0-9][a-z0-9._-]*$/', $slug)) {
        $slug_invalido++;
        continue;
    }

    if (!isset($con_codigos[$slug])) {
        $sin_codigos++;
        continue;
    }

    $xml .= "  <url>\n    <loc>{$base_url}/de-{$slug}</loc>\n    <lastmod>{$today}</lastmod>\n    <changefreq>daily</changefreq>\n    <priority>0.9</priority>\n  </url>\n";
    $count++;
}
$xml .= '</urlset>' . "\n";

$sitemap_path = __DIR__ . '/../myphp/xml/sitemap_marcas.xml';
file_put_contents($sitemap_path, $xml);
$log("Sitemap marcas regenerado: $count URLs (excluidas: $sin_codigos sin códigos, $slug_invalido con slug inválido)");

// ─── 1b. Refrescar sitemap_categorias.xml (re-sella lastmod, mantiene XML válido) ───
// Las categorías son fijas; aquí solo se actualiza la fecha para que no quede estancado.
$cat_path = __DIR__ . '/../myphp/xml/sitemap_categorias.xml';
if (file_exists($cat_path)) {
    $cat_old = file_get_contents($cat_path);
    preg_match_all('#<loc>(https://[^<]+)</loc>#', $cat_old, $cat_m);
    if (!empty($cat_m[1])) {
        $cat_xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $cat_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($cat_m[1] as $loc) {
            $cat_xml .= "  <url>\n    <loc>{$loc}</loc>\n    <lastmod>{$today}</lastmod>\n    <changefreq>daily</changefreq>\n    <priority>0.8</priority>\n  </url>\n";
        }
        $cat_xml .= '</urlset>' . "\n";
        file_put_contents($cat_path, $cat_xml);
        $log("Sitemap categorias refrescado: " . count($cat_m[1]) . " URLs");
    }
}

// ─── 1c. Regenerar sitemap_estaticas.xml y sitemap_guias.xml ───
// Hasta hoy no los tocaba nadie: estáticas llevaba desde el 7-ago y guías desde
// el 9-jun con la misma fecha dentro, mientras el índice de abajo les ponía la
// de hoy. Google se encuentra un sitemap que prometía cambios y no los tiene.
// Se llama a las funciones de myphp/funciones_sitemaps.php, que son las que
// mandan sobre qué URLs entran (las estáticas están auditadas: 200 e indexables).
$res_est = generarSitemapEstaticas();
$log($res_est['success']
    ? "Sitemap estaticas regenerado: {$res_est['total_urls']} URLs"
    : "Error al regenerar sitemap estaticas: " . ($res_est['error'] ?? 'desconocido'));

$res_guias = generarSitemapGuias();
$log($res_guias['success']
    ? "Sitemap guias regenerado: {$res_guias['total_urls']} URLs"
    : "Error al regenerar sitemap guias: " . ($res_guias['error'] ?? 'desconocido'));

// ─── 2. Actualizar sitemap index ───
$index_xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$index_xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Nota: sitemap_chollos_*.xml excluidos — todas las rutas /chollos/* hacen 301 a malprecio.com
$sitemaps = [
    'myphp/xml/sitemap_marcas.xml',
    'myphp/xml/sitemap_categorias.xml',
    'myphp/xml/sitemap_estaticas.xml',
    'myphp/xml/sitemap_guias.xml',
    // sitemap_comparativas.xml excluido: ~5000 páginas programáticas con ~2 clics/90d.
    // Ahora noindex (ver ruta /comparar/ en app_with_mongo.php). Fuera del índice para
    // reenfocar el crawl de Google en páginas de marca.
];
foreach ($sitemaps as $sm) {
    $index_xml .= "  <sitemap>\n    <loc>{$base_url}/{$sm}</loc>\n    <lastmod>{$today}</lastmod>\n  </sitemap>\n";
}
$index_xml .= '</sitemapindex>' . "\n";

file_put_contents(__DIR__ . '/../sitemap.xml', $index_xml);
$log("Sitemap index actualizado");

// ─── 3. Enviar a Google Search Console via API ───
$authJsonPath = dirname(__DIR__, 2) . '/private/google_credentials.json';
if (file_exists($authJsonPath)) {
    try {
        $client = new Google\Client();
        $client->setAuthConfig($authJsonPath);
        $client->addScope('https://www.googleapis.com/auth/webmasters');
        $service = new Google\Service\SearchConsole($client);
        
        $siteUrl = 'sc-domain:codigoamigo.com';
        
        $service->sitemaps->submit($siteUrl, "{$base_url}/sitemap.xml");
        $log("Sitemap enviado a GSC: {$base_url}/sitemap.xml");
        
        $service->sitemaps->submit($siteUrl, "{$base_url}/myphp/xml/sitemap_marcas.xml");
        $log("Sitemap marcas enviado a GSC");
        
    } catch (Exception $e) {
        $log("Error GSC: " . $e->getMessage());
    }
} else {
    $log("Sin credenciales GSC, omitiendo envío API");
}

$log("✅ Proceso completado");
