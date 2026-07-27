<?php
/**
 * Cachea las "marcas oportunidad" para el bloque de enlace interno del home.
 *
 * Selecciona marcas activas cuya página /de-{slug} rankea en la franja de
 * posición 8-25 en GSC (cerca de página 1 o top de página 2) ordenadas por
 * impresiones desc. Enlazarlas de forma destacada desde el home (página de
 * alta autoridad) concentra link equity en las que tienen más opción de subir.
 *
 * Escribe myphp/data/marcas_oportunidad.json: [{slug, nombre, impr, pos}].
 *
 * Uso: php cron/generar_marcas_oportunidad.php [--min-pos=8] [--max-pos=25] [--limit=40]
 * Cron sugerido: semanal.
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_marca.php';

$opts    = getopt('', ['min-pos:', 'max-pos:', 'limit:']);
$min_pos = isset($opts['min-pos']) ? (float)$opts['min-pos'] : 8;
// max-pos 35 (antes 25): GSC 2026-07 muestra el grueso de fichas con demanda
// (iqos, skyscanner, finetwork, octopus...) en pos 25-35; sin enlace desde el
// home se quedaban fuera del empuje de link equity.
$max_pos = isset($opts['max-pos']) ? (float)$opts['max-pos'] : 35;
$limit   = isset($opts['limit'])   ? max(1, (int)$opts['limit']) : 80;

$db = createConnection();

// ─── 1. Posiciones GSC por página de marca ───
$credsPath = dirname(__DIR__, 2) . '/private/google_credentials.json';
if (!file_exists($credsPath)) {
    echo "Sin credenciales GSC, abortando\n";
    log_error('[marcas_oportunidad] sin credenciales GSC');
    exit(1);
}

$candidatos = []; // [slug => ['impr'=>, 'pos'=>]]
try {
    $client = new Google\Client();
    $client->setAuthConfig($credsPath);
    $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
    $svc = new Google\Service\SearchConsole($client);
    $req = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
    $req->setStartDate(date('Y-m-d', strtotime('-28 days')));
    $req->setEndDate(date('Y-m-d', strtotime('-1 day')));
    $req->setDimensions(['page']);
    $req->setRowLimit(5000);
    foreach ($svc->searchanalytics->query('sc-domain:codigoamigo.com', $req)->getRows() as $row) {
        $url = $row->getKeys()[0];
        if (!preg_match('~/de-([^/?\#]+)$~', $url, $m)) continue;
        $pos = $row->getPosition();
        if ($pos < $min_pos || $pos > $max_pos) continue;
        $candidatos[$m[1]] = ['impr' => (int)$row->getImpressions(), 'pos' => round($pos, 1)];
    }
} catch (Exception $e) {
    echo "Error GSC: " . $e->getMessage() . "\n";
    log_error('[marcas_oportunidad] GSC: ' . $e->getMessage());
    exit(1);
}

// Ordenar por impresiones desc
uasort($candidatos, fn($a, $b) => $b['impr'] <=> $a['impr']);

// ─── 2. Resolver nombre y validar que la marca está activa ───
$salida = [];
foreach ($candidatos as $slug => $info) {
    if (count($salida) >= $limit) break;
    $marca = $db->marcas->findOne(
        ['nombre_clave' => $slug, 'estado' => 1, 'inactiva_seo' => ['$ne' => true]],
        ['projection' => ['nombre' => 1]]
    );
    if (!$marca) continue; // marca inactiva / inexistente → no enlazar
    $salida[] = [
        'slug'   => $slug,
        'nombre' => $marca['nombre'] ?? ucwords(str_replace(['-', '_'], ' ', $slug)),
        'impr'   => $info['impr'],
        'pos'    => $info['pos'],
    ];
}

// ─── 3. Escribir cache ───
$dir = __DIR__ . '/../myphp/data';
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
$path = $dir . '/marcas_oportunidad.json';
file_put_contents($path, json_encode([
    'generado' => date('c'),
    'franja'   => "pos $min_pos-$max_pos",
    'marcas'   => $salida,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "Marcas oportunidad cacheadas: " . count($salida) . " → $path\n";
log_info('[marcas_oportunidad] ' . count($salida) . ' marcas cacheadas');
