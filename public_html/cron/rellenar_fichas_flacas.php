<?php
/**
 * Rellena el cuerpo de las fichas de marca que reciben impresiones de Google
 * pero no tienen nada que leer.
 *
 * De las 149 fichas que están en posición 21 o peor con al menos 150
 * impresiones en 90 días, la mayoría NO son thin content: ya tienen intro,
 * descripción y FAQs, y su problema es de autoridad, no de texto (mismo
 * veredicto que la revisión de julio para la franja 11-20). Pero un puñado sí
 * está vacío de verdad: descripción de dos líneas, sin FAQs y sin bloque SEO.
 * Esas son las que arregla este script.
 *
 * Rellena tres campos, que son los que renderiza public/marca_detalle.php:
 *   descripción_larga  → intro de la ficha
 *   descripción_seo    → bloque de texto SEO
 *   seo_faq            → FAQs, una por línea, en formato "Pregunta|Respuesta"
 *
 * No toca las marcas que tienen contenido escrito a mano en
 * myphp/ai_content_data.php: ese override gana en el render y lo que se
 * guardara aquí no se llegaría a ver.
 *
 * Uso: php cron/rellenar_fichas_flacas.php [--apply] [--limit=10] [--min-impresiones=150]
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../config/ai_config.php';
require_once __DIR__ . '/../myphp/ai_content_data.php';

$opts  = getopt('', ['apply', 'limit:', 'min-impresiones:']);
$apply = isset($opts['apply']);
$limit = isset($opts['limit']) ? max(1, (int)$opts['limit']) : 15;
$min_impresiones = isset($opts['min-impresiones']) ? (int)$opts['min-impresiones'] : 150;

$db = createConnection();
$marcas = $db->selectCollection('marcas');

// ─── 1. Fichas con impresiones y mala posición, según GSC ───

$creds = dirname(__DIR__, 2) . '/private/google_credentials.json';
if (!file_exists($creds)) {
    log_error('[fichas_flacas] sin credenciales GSC');
    exit(1);
}

$cli = new Google\Client();
$cli->setAuthConfig($creds);
$cli->addScope('https://www.googleapis.com/auth/webmasters.readonly');
$svc = new Google\Service\SearchConsole($cli);
$req = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
$req->setStartDate(date('Y-m-d', strtotime('-93 days')));
$req->setEndDate(date('Y-m-d', strtotime('-3 days')));
$req->setDimensions(['page']);
$req->setRowLimit(25000);

try {
    $filas = $svc->searchanalytics->query('sc-domain:codigoamigo.com', $req)->getRows() ?: [];
} catch (\Throwable $e) {
    log_error('[fichas_flacas] GSC: ' . $e->getMessage());
    exit(1);
}

$candidatas = [];
foreach ($filas as $f) {
    $ruta = (string)parse_url($f->getKeys()[0], PHP_URL_PATH);
    if (!preg_match('~^/de-(.+)$~', $ruta, $m)) continue;
    if ($f->getPosition() < 21 || $f->getImpressions() < $min_impresiones) continue;
    $candidatas[$m[1]] = ['impresiones' => (int)$f->getImpressions(), 'posicion' => round($f->getPosition(), 1)];
}

// ─── 2. Quedarse con las que de verdad están vacías ───
//
// Los campos llevan tilde en la base de datos ('descripción_larga'). Es fácil
// consultarlos sin ella y que todo salga a cero, que es justo lo que hace
// parecer thin a media web.

$flacas = [];
foreach ($candidatas as $slug => $datos) {
    if (get_ai_brand_content($slug)) continue;   // lo escrito a mano manda

    $doc = $marcas->findOne(
        ['nombre_clave' => $slug],
        ['projection' => ['nombre' => 1, 'categoria' => 1, 'descripción' => 1,
                          'descripción_larga' => 1, 'descripción_seo' => 1,
                          'seo_faq' => 1, 'seo_que_es' => 1]]
    );
    if (!$doc) continue;

    $len = fn($c) => mb_strlen(trim(strip_tags((string)($doc[$c] ?? ''))));
    if ($len('descripción_larga') >= 500 || $len('seo_faq') >= 300) continue;

    // El nombre está guardado en mayúsculas ("BANCO SABADELL"). Si se le pasa
    // así al modelo, lo copia literal en las preguntas y la ficha acaba con
    // "¿Cómo aplico un código de REVOLUT?".
    $nombre_bruto = (string)($doc['nombre'] ?? $slug);
    $nombre = $nombre_bruto === mb_strtoupper($nombre_bruto, 'UTF-8')
        ? mb_convert_case(mb_strtolower($nombre_bruto, 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
        : $nombre_bruto;

    $flacas[] = [
        'slug'   => $slug,
        'nombre' => $nombre,
        'categoria' => (string)($doc['categoria'] ?? ''),
        'pista'  => trim(strip_tags((string)($doc['seo_que_es'] ?? $doc['descripción'] ?? ''))),
        'largo_actual' => $len('descripción_larga'),
        'faq_actual'   => $len('seo_faq'),
    ] + $datos;
}

usort($flacas, fn($a, $b) => $b['impresiones'] <=> $a['impresiones']);
$flacas = array_slice($flacas, 0, $limit);

printf("Fichas en posición 21+ con %d+ impresiones: %d | vacías de contenido: %d\n\n",
    $min_impresiones, count($candidatas), count($flacas));
foreach ($flacas as $f) {
    printf("  %-22s %6d imp  pos %5.1f  (intro %d car., faq %d car.)\n",
        $f['slug'], $f['impresiones'], $f['posicion'], $f['largo_actual'], $f['faq_actual']);
}

if (!$flacas) exit(0);
if (!$apply) { echo "\nSimulación. Añade --apply para escribir en la base de datos.\n"; exit(0); }

// ─── 3. Redactar y guardar ───

$registro = [];
$escritas = 0;

foreach ($flacas as $f) {
    $prompt = <<<TXT
Escribe el contenido de la página de códigos de descuento de la marca "{$f['nombre']}" (categoría: {$f['categoria']}) para un sitio español de cupones.

Contexto de la marca: {$f['pista']}

Devuelve SOLO un JSON válido, sin texto alrededor, con esta forma:
{
  "intro": "<p>...</p>",
  "seo": "<p>...</p><h3>...</h3><p>...</p>",
  "faqs": [{"pregunta": "...", "respuesta": "..."}, ...]
}

Reglas:
- Español de España, tono directo y natural, sin superlativos de marketing.
- "intro": 2 o 3 frases (400-700 caracteres) explicando qué es la marca y qué se puede ahorrar.
- "seo": 700-1200 caracteres en HTML simple (p, h3, ul, li). Explica cómo se usan los códigos y qué tipo de descuentos suele haber.
- "faqs": exactamente 4 preguntas con respuestas de 2 o 3 frases, del estilo "¿Cómo aplico un código de {$f['nombre']}?".
- No inventes porcentajes concretos, plazos ni promociones vigentes; habla en general.
- No menciones a la competencia ni prometas nada que no dependa de la marca.
TXT;

    $r = groq_request([
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.6,
        'max_tokens' => 1800,
    ], 60);

    if (!$r['ok'] || empty($r['content'])) {
        printf("  %-22s FALLO (http %d)\n", $f['slug'], $r['http']);
        log_error('[fichas_flacas] Groq falló para ' . $f['slug'], ['http' => $r['http']]);
        continue;
    }

    $texto = trim((string)$r['content']);
    if (preg_match('/\{.*\}/s', $texto, $m)) $texto = $m[0];
    $j = json_decode($texto, true);

    if (!is_array($j) || empty($j['intro']) || empty($j['seo']) || empty($j['faqs'])) {
        printf("  %-22s respuesta no utilizable\n", $f['slug']);
        log_error('[fichas_flacas] respuesta no parseable para ' . $f['slug']);
        continue;
    }

    // Las FAQs se guardan como "Pregunta|Respuesta" por línea: es el formato que
    // lee marca_detalle.php para pintarlas y para el schema FAQPage.
    $faq_lineas = [];
    foreach ($j['faqs'] as $faq) {
        $p = trim(strip_tags((string)($faq['pregunta'] ?? '')));
        $rp = trim(strip_tags((string)($faq['respuesta'] ?? '')));
        if ($p === '' || $rp === '') continue;
        $faq_lineas[] = str_replace('|', '-', $p) . '|' . str_replace('|', '-', $rp);
    }
    if (count($faq_lineas) < 2) {
        printf("  %-22s FAQs insuficientes\n", $f['slug']);
        continue;
    }

    $nuevo = [
        'descripción_larga' => trim((string)$j['intro']),
        'descripción_seo'   => trim((string)$j['seo']),
        'seo_faq'           => implode("\n", $faq_lineas),
        'contenido_generado_at' => date('Y-m-d H:i:s'),
    ];

    $doc_previo = $marcas->findOne(['nombre_clave' => $f['slug']]);
    $registro[] = [
        'slug' => $f['slug'],
        'impresiones' => $f['impresiones'],
        'posicion_antes' => $f['posicion'],
        'previo' => json_decode(json_encode($doc_previo), true),
        'nuevo'  => $nuevo,
    ];

    $marcas->updateOne(['nombre_clave' => $f['slug']], ['$set' => $nuevo]);
    $escritas++;
    printf("  %-22s escrita (intro %d car., seo %d car., %d FAQs)\n",
        $f['slug'], mb_strlen($nuevo['descripción_larga']), mb_strlen($nuevo['descripción_seo']), count($faq_lineas));
}

$destino = __DIR__ . '/../logs/fichas_flacas_' . date('Y-m-d') . '.json';
file_put_contents($destino, json_encode([
    'fecha' => date('c'), 'escritas' => $escritas, 'detalle' => $registro,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

printf("\nFichas rellenadas: %d. Estado previo guardado en %s\n", $escritas, $destino);
log_info('[fichas_flacas] contenido generado', ['escritas' => $escritas]);
