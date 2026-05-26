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

$count = 0;
foreach ($marcas as $m) {
    $slug = $m['nombre_clave'] ?? '';
    if (empty($slug)) continue;
    $xml .= "  <url>\n    <loc>{$base_url}/de-{$slug}</loc>\n    <lastmod>{$today}</lastmod>\n    <changefreq>daily</changefreq>\n    <priority>0.9</priority>\n  </url>\n";
    $count++;
}
$xml .= '</urlset>' . "\n";

$sitemap_path = __DIR__ . '/../myphp/xml/sitemap_marcas.xml';
file_put_contents($sitemap_path, $xml);
$log("Sitemap marcas regenerado: $count URLs");

// ─── 2. Actualizar sitemap index ───
$index_xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$index_xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Nota: sitemap_chollos_*.xml excluidos — todas las rutas /chollos/* hacen 301 a malprecio.com
$sitemaps = [
    'myphp/xml/sitemap_marcas.xml',
    'myphp/xml/sitemap_categorias.xml',
    'myphp/xml/sitemap_estaticas.xml',
    'myphp/xml/sitemap_guias.xml',
    'myphp/xml/sitemap_comparativas.xml',
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
