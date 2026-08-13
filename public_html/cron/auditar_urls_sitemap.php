<?php
/**
 * Audita todas las URLs del sitemap contra el sitio real.
 *
 * Nació de encontrar que 12 de las 16 páginas de categoría se publicaban con el
 * título "Códigos de descuento Categoría": una variable sin resolver que llevaba
 * meses en producción sin que nada la detectara, en páginas que Google sí estaba
 * mostrando. Esto busca el resto de fallos de esa familia recorriendo lo que el
 * sitemap declara indexable y mirando lo que de verdad devuelve el servidor.
 *
 * Detecta: respuestas que no son 200, marcadores sin sustituir en el título,
 * títulos y descripciones repetidos, noindex en páginas que el sitemap declara
 * indexables, canonical que apunta a otro sitio y páginas casi sin texto.
 *
 * No arregla nada: solo informa.
 *
 * Uso: php cron/auditar_urls_sitemap.php [--limit=0] [--concurrencia=12] [--json]
 * Cron sugerido: semanal.
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';

$opts   = getopt('', ['limit:', 'concurrencia:', 'json', 'sitemap:']);
$limit  = isset($opts['limit'])        ? (int)$opts['limit']              : 0;   // 0 = todas
$conc   = isset($opts['concurrencia']) ? max(1, (int)$opts['concurrencia']) : 12;
$as_json = isset($opts['json']);

$UA = 'Mozilla/5.0 (compatible; CodigoAmigoAudit/1.0; +https://www.codigoamigo.com)';

// Marcadores que delatan una variable que no se ha sustituido.
$PLACEHOLDERS = [
    'Categoría -', 'descuento Categoría', 'undefined', 'Array', ' null',
    '{{', '%s', 'Sin nombre', 'NOMBRE_MARCA', 'Nueva marca',
];

// ─── 1. Reunir URLs ───

$sitemaps = [];
$indice = __DIR__ . '/../sitemap.xml';
if (file_exists($indice)) {
    if (preg_match_all('~<loc>\s*([^<]+)\s*</loc>~', (string)file_get_contents($indice), $m)) {
        foreach ($m[1] as $loc) {
            // El índice apunta a las URLs públicas; se leen del disco para no
            // depender de la caché de Cloudflare al recoger la lista.
            $rel = parse_url(trim($loc), PHP_URL_PATH);
            $local = __DIR__ . '/..' . $rel;
            if (file_exists($local)) $sitemaps[] = $local;
        }
    }
}
if (isset($opts['sitemap'])) $sitemaps = [$opts['sitemap']];

if (!$sitemaps) {
    echo "No se han encontrado sitemaps en sitemap.xml\n";
    exit(1);
}

$urls = [];
foreach ($sitemaps as $sm) {
    if (preg_match_all('~<loc>\s*([^<]+)\s*</loc>~', (string)file_get_contents($sm), $m)) {
        foreach ($m[1] as $u) $urls[trim($u)] = basename($sm);
    }
}
$urls = $limit > 0 ? array_slice($urls, 0, $limit, true) : $urls;

echo "Sitemaps: " . count($sitemaps) . " | URLs a comprobar: " . count($urls) . "\n\n";

// ─── 2. Descargar en paralelo ───

/** Extrae de una respuesta HTML lo que interesa para la auditoría. */
function extraer($html) {
    $d = ['title' => '', 'desc' => '', 'robots' => '', 'canonical' => '', 'texto' => 0, 'h1' => ''];

    if (preg_match('~<title>(.*?)</title>~si', $html, $m))                       $d['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
    if (preg_match('~name="description"\s+content="(.*?)"~si', $html, $m))       $d['desc']  = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
    if (preg_match('~name="robots"\s+content="(.*?)"~si', $html, $m))            $d['robots'] = strtolower(trim($m[1]));
    if (preg_match('~rel="canonical"\s+href="(.*?)"~si', $html, $m))             $d['canonical'] = trim($m[1]);
    if (preg_match('~<h1[^>]*>(.*?)</h1>~si', $html, $m))                        $d['h1'] = trim(strip_tags($m[1]));

    $txt = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~si', ' ', $html);
    $txt = trim(preg_replace('~\s+~u', ' ', strip_tags($txt)));
    $d['texto'] = mb_strlen($txt, 'UTF-8');

    return $d;
}

$resultados = [];
$pendientes = array_keys($urls);
$total = count($pendientes);
$hechas = 0;

$multi = curl_multi_init();
$activos = [];

$lanzar = function () use (&$pendientes, &$activos, $multi, $UA) {
    if (!$pendientes) return false;
    $url = array_shift($pendientes);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,   // interesa ver el 301, no seguirlo
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_USERAGENT      => $UA,
        CURLOPT_ENCODING       => '',
    ]);
    curl_multi_add_handle($multi, $ch);
    $activos[(int)$ch] = ['ch' => $ch, 'url' => $url];
    return true;
};

for ($i = 0; $i < $conc; $i++) if (!$lanzar()) break;

do {
    curl_multi_exec($multi, $corriendo);
    curl_multi_select($multi, 0.5);

    while ($info = curl_multi_info_read($multi)) {
        $ch  = $info['handle'];
        $id  = (int)$ch;
        $url = $activos[$id]['url'] ?? '';
        $cuerpo = (string)curl_multi_getcontent($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $fila = ['url' => $url, 'http' => $http, 'sitemap' => $urls[$url] ?? ''] + extraer($cuerpo);
        if ($http >= 300 && $http < 400) {
            $fila['destino'] = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        }
        $resultados[] = $fila;

        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
        unset($activos[$id]);

        $hechas++;
        if ($hechas % 100 === 0) echo "  $hechas/$total...\n";

        $lanzar();
    }
} while ($corriendo || $activos || $pendientes);

curl_multi_close($multi);

// ─── 3. Analizar ───

$problemas = [
    'http_no_200'      => [],
    'placeholder'      => [],
    'noindex'          => [],
    'sin_titulo'       => [],
    'sin_descripcion'  => [],
    'canonical_ajeno'  => [],
    'texto_escaso'     => [],
];

$por_titulo = [];
$por_desc   = [];

foreach ($resultados as $r) {
    if ($r['http'] !== 200) {
        $problemas['http_no_200'][] = $r;
        continue;                       // lo demás no aplica si no hay página
    }

    foreach ($PLACEHOLDERS as $p) {
        if ($p !== '' && (stripos($r['title'], $p) !== false || stripos($r['h1'], $p) !== false)) {
            $problemas['placeholder'][] = $r;
            break;
        }
    }

    if (strpos($r['robots'], 'noindex') !== false) $problemas['noindex'][] = $r;
    if ($r['title'] === '')                       $problemas['sin_titulo'][] = $r;
    if ($r['desc'] === '')                        $problemas['sin_descripcion'][] = $r;
    if ($r['texto'] < 800)                        $problemas['texto_escaso'][] = $r;

    if ($r['canonical'] !== '' && rtrim($r['canonical'], '/') !== rtrim($r['url'], '/')) {
        $problemas['canonical_ajeno'][] = $r;
    }

    if ($r['title'] !== '') $por_titulo[$r['title']][] = $r['url'];
    if ($r['desc']  !== '') $por_desc[$r['desc']][]    = $r['url'];
}

$titulos_dup = array_filter($por_titulo, fn($v) => count($v) > 1);
$descs_dup   = array_filter($por_desc,   fn($v) => count($v) > 1);

// ─── 4. Salida ───

$resumen = [
    'fecha'            => date('c'),
    'urls'             => count($resultados),
    'http_no_200'      => count($problemas['http_no_200']),
    'placeholder'      => count($problemas['placeholder']),
    'noindex'          => count($problemas['noindex']),
    'sin_titulo'       => count($problemas['sin_titulo']),
    'sin_descripcion'  => count($problemas['sin_descripcion']),
    'canonical_ajeno'  => count($problemas['canonical_ajeno']),
    'texto_escaso'     => count($problemas['texto_escaso']),
    'titulos_repetidos' => count($titulos_dup),
    'descripciones_repetidas' => count($descs_dup),
];

$destino = __DIR__ . '/../logs/auditoria_urls_' . date('Y-m-d') . '.json';
@file_put_contents($destino, json_encode([
    'resumen'   => $resumen,
    'problemas' => $problemas,
    'titulos_repetidos' => array_slice($titulos_dup, 0, 60, true),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

if ($as_json) {
    echo json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

echo "\n=== RESUMEN ===\n";
foreach ($resumen as $k => $v) if ($k !== 'fecha') printf("  %-24s %s\n", $k, $v);

$muestra = function ($titulo, array $lista, $n = 15, $extra = null) {
    if (!$lista) return;
    echo "\n--- $titulo (" . count($lista) . ") ---\n";
    foreach (array_slice($lista, 0, $n) as $r) {
        $ruta = str_replace('https://www.codigoamigo.com', '', $r['url']);
        echo '  ' . str_pad(substr($ruta, 0, 52), 54) . ($extra ? $extra($r) : '') . "\n";
    }
    if (count($lista) > $n) echo '  ... y ' . (count($lista) - $n) . " más\n";
};

$muestra('RESPUESTAS QUE NO SON 200', $problemas['http_no_200'], 20,
    fn($r) => $r['http'] . (isset($r['destino']) ? ' -> ' . str_replace('https://www.codigoamigo.com', '', $r['destino']) : ''));
$muestra('MARCADOR SIN SUSTITUIR EN TÍTULO/H1', $problemas['placeholder'], 20, fn($r) => substr($r['title'], 0, 60));
$muestra('NOINDEX PERO ESTÁ EN EL SITEMAP', $problemas['noindex'], 20);
$muestra('CANONICAL A OTRA URL', $problemas['canonical_ajeno'], 15,
    fn($r) => '-> ' . str_replace('https://www.codigoamigo.com', '', $r['canonical']));
$muestra('SIN META DESCRIPTION', $problemas['sin_descripcion'], 10);
$muestra('MUY POCO TEXTO (<800 car.)', $problemas['texto_escaso'], 15, fn($r) => $r['texto'] . ' car.');

if ($titulos_dup) {
    echo "\n--- TÍTULOS REPETIDOS (" . count($titulos_dup) . ") ---\n";
    $i = 0;
    foreach ($titulos_dup as $t => $us) {
        printf("  \"%s\" en %d URLs\n", substr($t, 0, 60), count($us));
        if (++$i >= 12) { echo '  ... y ' . (count($titulos_dup) - 12) . " más\n"; break; }
    }
}

echo "\nDetalle completo en $destino\n";

log_info('[auditar_urls] completada', $resumen);
