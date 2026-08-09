<?php
/**
 * Detecta códigos cuyo enlace no lleva a la marca bajo la que están publicados.
 *
 * Caso que lo motivó: la ficha /de-endado (recambios de coche) es la que más
 * tráfico orgánico recibe de todo el sitio —485 clics en 90 días, un 25% del
 * total— y el código que el algoritmo elegía como "mejor" era un enlace a
 * playfulbet.com, una casa de apuestas, con la descripción "PUEDEN
 * BENEFICIARSE DE ALGUNA MANERA".
 *
 * Quien llega buscando un cupón de una marca y se encuentra un enlace de otra
 * cosa se va. Es tráfico ya conseguido y pagado en esfuerzo de SEO que se tira,
 * y encima ensucia el dato de clics salientes con el que hay que negociar las
 * altas de afiliación.
 *
 * La comprobación es la obvia: el dominio al que apunta el enlace debería
 * parecerse al nombre de la marca o a su web oficial. Se listan los que no.
 *
 * Uso: php cron/detectar_codigos_impostores.php [--limit=60] [--solo-con-trafico] [--json]
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';

$opts  = getopt('', ['limit:', 'solo-con-trafico', 'json']);
$limit = isset($opts['limit']) ? max(1, (int)$opts['limit']) : 60;
$solo_trafico = isset($opts['solo-con-trafico']);
$as_json = isset($opts['json']);

$db = createConnection();

/** minúsculas, sin tildes, solo alfanumérico */
function ci_norm($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u','ç'=>'c']);
    return preg_replace('/[^a-z0-9]/', '', $s);
}

/** dominio registrable aproximado: quita www y el TLD */
function ci_dominio_base($url) {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return '';
    $host = preg_replace('/^www\./i', '', strtolower($host));
    $partes = explode('.', $host);
    // co.uk, com.es... nos quedamos con la etiqueta significativa
    if (count($partes) >= 3 && in_array($partes[count($partes) - 2], ['co', 'com', 'org', 'net'], true)) {
        return $partes[count($partes) - 3];
    }
    return $partes[0] ?? '';
}

// Acortadores y redes de afiliación: el dominio no dice nada de la marca, así
// que no se pueden juzgar por este método.
$OPACOS = [
    'bit', 'tinyurl', 'goo', 'ow', 'cutt', 'rb', 'shorturl', 't', 'lnk', 'linktr',
    'awin1', 'tradedoubler', 'zanox', 'webgains', 'impact', 'go2cloud', 'prf',
    'click', 'clkde', 'tc', 'shrsl', 'sjv', 'kqzyfj', 'dpbolvw', 'anrdoezrs',
    'refer', 'invite', 'share', 'app', 'link', 'onelink', 'page', 'my',
];

// ─── Marcas ───

$marcas = [];
foreach ($db->selectCollection('marcas')->find([], ['projection' => ['nombre_clave' => 1, 'nombre' => 1, 'url' => 1]]) as $m) {
    $slug = (string)($m['nombre_clave'] ?? '');
    if ($slug === '') continue;
    $marcas[$slug] = [
        'nombre'  => (string)($m['nombre'] ?? $slug),
        'dominio' => ci_dominio_base((string)($m['url'] ?? '')),
    ];
}

// ─── Recorrer códigos que son enlace ───

$cursor = $db->selectCollection('codigos')->find(
    ['estado' => 0, 'codigo' => ['$regex' => '^https?://']],
    ['projection' => ['marca' => 1, 'codigo' => 1, 'descripcion' => 1, 'totalclicks' => 1, 'destacado' => 1]]
);

$sospechosos = [];
$revisados = 0;
$opacos = 0;

foreach ($cursor as $c) {
    $revisados++;
    $slug = (string)($c['marca'] ?? '');
    if ($slug === '' || !isset($marcas[$slug])) continue;

    $dom = ci_dominio_base((string)$c['codigo']);
    if ($dom === '') continue;
    if (in_array($dom, $OPACOS, true) || strlen($dom) <= 2) { $opacos++; continue; }

    $slug_n   = ci_norm($slug);
    $nombre_n = ci_norm($marcas[$slug]['nombre']);
    $ofic_n   = ci_norm($marcas[$slug]['dominio']);
    $dom_n    = ci_norm($dom);

    // Coincide si el dominio contiene el nombre de la marca o al revés, o si
    // es el dominio oficial. Cubre 'revolut' en 'revolut.com' y también
    // 'bancosabadell' en 'bancsabadell.com'.
    $encaja = false;
    foreach ([$slug_n, $nombre_n, $ofic_n] as $ref) {
        if ($ref === '') continue;
        // Igualdad exacta primero y sin exigir longitud: marcas como 'n26' o
        // 'voi' tienen menos de 4 caracteres y el mínimo de abajo las descartaba,
        // marcando n26.com como impostor de la marca N26.
        if ($ref === $dom_n) { $encaja = true; break; }
        if (strlen($ref) < 4) continue;
        if (str_contains($dom_n, $ref) || str_contains($ref, $dom_n)) { $encaja = true; break; }
        similar_text($ref, $dom_n, $pct);
        if ($pct >= 75) { $encaja = true; break; }
    }
    if ($encaja) continue;

    // Segunda señal, imprescindible para que la lista sirva de algo.
    //
    // Los programas de referidos usan dominios de terceros por diseño: Repsol
    // reparte por aklam.io, Honest Greens por hnst.app, muchas apps por bnc.lt
    // (Branch). Juzgando solo por el dominio salían 825 "sospechosos" de los que
    // casi todos eran legítimos.
    //
    // Lo que de verdad delata a un impostor es que NADA en el texto hable de la
    // marca: "Contrata la luz y el gas de Repsol" es legítimo aunque salga por
    // aklam.io, mientras que "PUEDEN BENEFICIARSE DE ALGUNA MANERA" bajo la
    // marca endado, apuntando a una casa de apuestas, no lo es.
    $texto_n = ci_norm(($c['descripcion'] ?? '') . ' ' . (string)$c['codigo']);

    // Las referencias incluyen cada palabra suelta del nombre, no solo el
    // nombre entero: la marca 'repsol-waylet' se normaliza a "repsolwaylet" y
    // una descripción que dice "Repsol" no contiene esa cadena. Sin partir por
    // palabras, medio catálogo legítimo salía marcado.
    $refs = [$slug_n, $nombre_n, $ofic_n];
    foreach (preg_split('/[^\p{L}\p{N}]+/u', (string)$marcas[$slug]['nombre'], -1, PREG_SPLIT_NO_EMPTY) as $palabra) {
        $refs[] = ci_norm($palabra);
    }
    foreach (preg_split('/[-_.]+/', $slug, -1, PREG_SPLIT_NO_EMPTY) as $trozo) {
        $refs[] = ci_norm($trozo);
    }

    $mencionada = false;
    foreach (array_unique($refs) as $ref) {
        if ($ref !== '' && strlen($ref) >= 4 && str_contains($texto_n, $ref)) { $mencionada = true; break; }
    }
    if ($mencionada) continue;

    $sospechosos[] = [
        'id'          => (string)$c['_id'],
        'marca'       => $slug,
        'marca_nombre' => $marcas[$slug]['nombre'],
        'dominio'     => $dom,
        'url'         => mb_substr((string)$c['codigo'], 0, 90),
        'descripcion' => mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($c['descripcion'] ?? ''))), 0, 70),
        'clicks'      => (int)($c['totalclicks'] ?? 0),
        'destacado'   => (int)($c['destacado'] ?? 0),
    ];
}

// ─── Cruce con tráfico real: primero lo que está quemando visitas ───

$trafico = [];
$creds = dirname(__DIR__, 2) . '/private/google_credentials.json';
if (file_exists($creds)) {
    try {
        $cli = new Google\Client();
        $cli->setAuthConfig($creds);
        $cli->addScope('https://www.googleapis.com/auth/webmasters.readonly');
        $svc = new Google\Service\SearchConsole($cli);
        $req = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $req->setStartDate(date('Y-m-d', strtotime('-93 days')));
        $req->setEndDate(date('Y-m-d', strtotime('-3 days')));
        $req->setDimensions(['page']);
        $req->setRowLimit(25000);
        foreach ($svc->searchanalytics->query('sc-domain:codigoamigo.com', $req)->getRows() ?: [] as $r) {
            if (preg_match('~/de-([^/?\#]+)~', $r->getKeys()[0], $m)) {
                $trafico[$m[1]] = (int)$r->getClicks();
            }
        }
    } catch (\Throwable $e) {
        log_error('[impostores] GSC no disponible: ' . $e->getMessage());
    }
}

foreach ($sospechosos as &$s) $s['clicks_seo'] = $trafico[$s['marca']] ?? 0;
unset($s);

if ($solo_trafico) $sospechosos = array_values(array_filter($sospechosos, fn($s) => $s['clicks_seo'] > 0));

usort($sospechosos, fn($a, $b) => [$b['clicks_seo'], $b['clicks']] <=> [$a['clicks_seo'], $a['clicks']]);

// ─── Salida ───

$destino = __DIR__ . '/../logs/codigos_impostores_' . date('Y-m-d') . '.json';
@file_put_contents($destino, json_encode([
    'fecha' => date('c'), 'revisados' => $revisados, 'sospechosos' => count($sospechosos),
    'detalle' => $sospechosos,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

if ($as_json) {
    echo json_encode(['revisados' => $revisados, 'sospechosos' => count($sospechosos)], JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

printf("Códigos-enlace revisados: %d | dominio opaco (acortador/red): %d\n", $revisados, $opacos);
printf("Sospechosos (el enlace no lleva a la marca): %d\n\n", count($sospechosos));

printf("%-22s %-22s %6s  %s\n", 'MARCA', 'LLEVA A', 'CLK/90d', 'DESCRIPCIÓN');
echo str_repeat('-', 100) . "\n";
foreach (array_slice($sospechosos, 0, $limit) as $s) {
    printf("%-22s %-22s %6d  %s%s\n",
        mb_substr($s['marca'], 0, 21),
        mb_substr($s['dominio'], 0, 21),
        $s['clicks_seo'],
        mb_substr($s['descripcion'], 0, 44),
        $s['destacado'] > 0 ? '  [DESTACADO]' : ''
    );
}

$con_trafico = array_filter($sospechosos, fn($s) => $s['clicks_seo'] > 0);
printf("\nEn fichas que reciben visitas de Google: %d códigos, sobre %d clics/90d\n",
    count($con_trafico), array_sum(array_column($con_trafico, 'clicks_seo')));
echo "Detalle en $destino\n";

log_info('[impostores] análisis completado', ['revisados' => $revisados, 'sospechosos' => count($sospechosos)]);
