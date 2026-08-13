<?php
/**
 * Dossier de marcas monetizables, para dar de alta en redes de afiliación.
 *
 * Hoy el portal manda tráfico a los comercios y no cobra por ello: Awin está
 * inactive, Impact solo tiene un programa de prueba (brand_id 'default_test'
 * apuntando a impact.com/test_link) y lo único vivo es el tag de Amazon. Los
 * 8.203 códigos activos que son un enlace de referido salen gratis.
 *
 * Para dar de alta un programa hace falta enseñar tráfico. Esto lo calcula:
 * cruza los clics que GSC atribuye a cada ficha /de-{slug} con el inventario de
 * códigos de esa marca y con los clics salientes que ya registra /salir.php.
 *
 * Salida: logs/dossier_afiliacion_YYYY-MM-DD.{json,txt}
 *
 * Uso: php cron/informe_afiliacion.php [--dias=90] [--limit=40]
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';

$opts  = getopt('', ['dias:', 'limit:']);
$dias  = isset($opts['dias'])  ? max(7, (int)$opts['dias'])   : 90;
$limit = isset($opts['limit']) ? max(1, (int)$opts['limit'])  : 40;

$db = createConnection();

// ─── 1. Tráfico por ficha de marca según GSC ───

$credsPath = dirname(__DIR__, 2) . '/private/google_credentials.json';
if (!file_exists($credsPath)) {
    log_error('[informe_afiliacion] sin credenciales GSC');
    exit(1);
}

try {
    $client = new Google\Client();
    $client->setAuthConfig($credsPath);
    $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
    $svc = new Google\Service\SearchConsole($client);

    $req = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
    $req->setStartDate(date('Y-m-d', strtotime("-" . ($dias + 3) . " days")));
    $req->setEndDate(date('Y-m-d', strtotime('-3 days')));
    $req->setDimensions(['page']);
    $req->setRowLimit(25000);
    $filas = $svc->searchanalytics->query('sc-domain:codigoamigo.com', $req)->getRows() ?: [];
} catch (\Throwable $e) {
    log_error('[informe_afiliacion] error GSC: ' . $e->getMessage());
    exit(1);
}

$trafico = [];
foreach ($filas as $f) {
    $url = $f->getKeys()[0];
    if (!preg_match('~/de-([^/?\#]+)~', $url, $m)) continue;
    $slug = $m[1];
    $trafico[$slug] = [
        'clicks' => (int)$f->getClicks(),
        'impr'   => (int)$f->getImpressions(),
        'pos'    => round($f->getPosition(), 1),
    ];
}

// ─── 2. Inventario de enlaces salientes por marca ───

$col_cod = $db->selectCollection('codigos');

$con_enlace = [];
foreach ($col_cod->aggregate([
    ['$match' => ['estado' => 0, 'codigo' => ['$regex' => '^https?://']]],
    ['$group' => ['_id' => '$marca', 'n' => ['$sum' => 1]]],
]) as $r) {
    $con_enlace[(string)$r['_id']] = (int)$r['n'];
}

$activos = [];
foreach ($col_cod->aggregate([
    ['$match' => ['estado' => 0]],
    ['$group' => ['_id' => '$marca', 'n' => ['$sum' => 1]]],
]) as $r) {
    $activos[(string)$r['_id']] = (int)$r['n'];
}

// ─── 3. Clics salientes ya medidos ───

$salidas = [];
foreach ($db->selectCollection('clicks_salida')->aggregate([
    ['$group' => ['_id' => '$marca', 'n' => ['$sum' => 1]]],
]) as $r) {
    $salidas[(string)$r['_id']] = (int)$r['n'];
}

// ─── 4. Datos de la marca ───

$marcas = [];
foreach ($db->selectCollection('marcas')->find([], ['projection' => [
    'nombre_clave' => 1, 'nombre' => 1, 'categoria' => 1, 'url' => 1, 'datos_financieros' => 1,
]]) as $m) {
    $marcas[(string)($m['nombre_clave'] ?? '')] = [
        'nombre'    => (string)($m['nombre'] ?? ''),
        'categoria' => (string)($m['categoria'] ?? ''),
        'url'       => (string)($m['url'] ?? ''),
        'afiliado'  => trim((string)($m['datos_financieros']['enlace_afiliado'] ?? '')),
    ];
}

// ─── 5. Ranking ───

$dossier = [];
foreach ($trafico as $slug => $t) {
    if ($t['clicks'] < 1) continue;                 // sin clics no hay nada que vender
    $enlaces = $con_enlace[$slug] ?? 0;
    $dossier[] = [
        'slug'          => $slug,
        'nombre'        => $marcas[$slug]['nombre'] ?? $slug,
        'categoria'     => $marcas[$slug]['categoria'] ?? '',
        'web'           => $marcas[$slug]['url'] ?? '',
        'clicks_seo'    => $t['clicks'],
        'impresiones'   => $t['impr'],
        'posicion'      => $t['pos'],
        'codigos'       => $activos[$slug] ?? 0,
        'con_enlace'    => $enlaces,
        'clicks_salida' => $salidas[$slug] ?? 0,
        'ya_afiliado'   => ($marcas[$slug]['afiliado'] ?? '') !== '',
    ];
}

usort($dossier, fn($a, $b) => $b['clicks_seo'] <=> $a['clicks_seo']);

$sin_afiliar = array_values(array_filter($dossier, fn($d) => !$d['ya_afiliado'] && $d['con_enlace'] > 0));
$total_clicks_sin_afiliar = array_sum(array_column($sin_afiliar, 'clicks_seo'));

// ─── 6. Salida ───

$fecha = date('Y-m-d');
$json = [
    'generado'      => date('c'),
    'ventana_dias'  => $dias,
    'marcas_con_trafico' => count($dossier),
    'marcas_sin_afiliar' => count($sin_afiliar),
    'clicks_sin_monetizar' => $total_clicks_sin_afiliar,
    'ranking'       => array_slice($dossier, 0, 200),
];
@file_put_contents(__DIR__ . '/../logs/dossier_afiliacion_' . $fecha . '.json',
    json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

$txt  = "DOSSIER DE AFILIACIÓN — codigoamigo.com — $fecha\n";
$txt .= "Ventana: últimos $dias días (Google Search Console)\n\n";
$txt .= sprintf("Marcas con tráfico orgánico  : %d\n", count($dossier));
$txt .= sprintf("Sin programa de afiliación   : %d\n", count($sin_afiliar));
$txt .= sprintf("Clics/%dd que no se cobran   : %d\n\n", $dias, $total_clicks_sin_afiliar);
$txt .= str_pad('MARCA', 26) . str_pad('CLICS', 7) . str_pad('IMPR', 8) . str_pad('POS', 7)
      . str_pad('CÓDS', 6) . str_pad('ENLACES', 9) . "CATEGORÍA\n";
$txt .= str_repeat('-', 95) . "\n";
foreach (array_slice($sin_afiliar, 0, $limit) as $d) {
    $txt .= str_pad(substr($d['nombre'] ?: $d['slug'], 0, 25), 26)
          . str_pad($d['clicks_seo'], 7)
          . str_pad($d['impresiones'], 8)
          . str_pad($d['posicion'], 7)
          . str_pad($d['codigos'], 6)
          . str_pad($d['con_enlace'], 9)
          . substr($d['categoria'], 0, 30) . "\n";
}
$txt .= "\nCÓDS = códigos activos | ENLACES = de esos, cuántos son un enlace de referido saliente\n";

$destino_txt = __DIR__ . '/../logs/dossier_afiliacion_' . $fecha . '.txt';
@file_put_contents($destino_txt, $txt);

echo $txt;
echo "\nGuardado en $destino_txt\n";

log_info('[informe_afiliacion] dossier generado', [
    'marcas' => count($dossier),
    'sin_afiliar' => count($sin_afiliar),
    'clicks' => $total_clicks_sin_afiliar,
]);
