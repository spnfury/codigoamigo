<?php
/**
 * Genera descripciones SEO (HTML) en bulk para marcas SIN descripción_larga.
 *
 * Prioridad: marcas más cercanas a la página 1 de Google primero (posición GSC
 * ascendente, p.ej. 11-20 antes que 30+), luego por impresiones. Las marcas sin
 * datos GSC se procesan al final, ordenadas por nº de códigos activos.
 *
 * Escribe el resultado en marcas.descripción_larga (mismo campo que ya renderiza
 * public/marca_detalle.php). Reutiliza la rotación de claves Groq de ai_config.
 *
 * Uso:
 *   php cron/bulk_descripciones.php                  → 15 marcas
 *   php cron/bulk_descripciones.php --limit=40
 *   php cron/bulk_descripciones.php --marca=ionos     → solo una
 *   php cron/bulk_descripciones.php --dry-run          → no escribe, muestra salida
 *   php cron/bulk_descripciones.php --min-pos=11 --max-pos=20  → acota franja GSC
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_marca.php';
require_once __DIR__ . '/../config/ai_config.php';

$opts     = getopt('', ['limit:', 'marca:', 'dry-run', 'min-pos:', 'max-pos:']);
$limit    = isset($opts['limit'])   ? max(1, (int)$opts['limit']) : 15;
$solo     = $opts['marca']          ?? null;
$dry      = isset($opts['dry-run']);
$min_pos  = isset($opts['min-pos']) ? (float)$opts['min-pos'] : 0;
$max_pos  = isset($opts['max-pos']) ? (float)$opts['max-pos'] : 9999;

define('DESC_GROQ_MODEL', 'llama-3.3-70b-versatile');

$db = createConnection();

/** Llama a Groq con rotación de claves (idéntico patrón a bulk_faqs_priority). */
function groq_desc(string $prompt): ?string {
    $keys = defined('GROQ_API_KEYS') ? GROQ_API_KEYS : [GROQ_API_KEY];
    $data = json_encode([
        'model'       => DESC_GROQ_MODEL,
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'max_tokens'  => 700,
        'temperature' => 0.7,
    ]);
    foreach ($keys as $key) {
        $ch = curl_init(GROQ_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code === 200) {
            $r = json_decode($response, true);
            return $r['choices'][0]['message']['content'] ?? null;
        }
        if ($http_code === 429) { log_info("[bulk_desc] Clave 429, rotando..."); continue; }
        log_error("[bulk_desc] Error Groq HTTP $http_code: " . substr((string)$response, 0, 300));
        return null;
    }
    log_error("[bulk_desc] Todas las claves Groq agotadas.");
    return null;
}

/** Genera la descripción HTML y la limpia (sin fences markdown ni texto fuera de <p>). */
function generar_descripcion(string $nombre): ?string {
    $prompt = "Escribe una descripción SEO en español para la marca \"$nombre\", "
        . "destinada a una página de códigos de descuento y referido en CodigoAmigo. "
        . "Requisitos: 2 párrafos, entre 120 y 180 palabras en total. Explica brevemente "
        . "qué es o qué ofrece la marca y anima a usar los códigos amigo y de descuento "
        . "verificados que comparte la comunidad. Tono natural y cercano. NO inventes "
        . "porcentajes, importes ni datos concretos que no conozcas. Devuelve EXCLUSIVAMENTE "
        . "HTML con etiquetas <p> y, si procede, <strong>. Sin markdown, sin comillas de "
        . "código, sin títulos, sin explicaciones añadidas.";
    $out = groq_desc($prompt);
    if ($out === null) return null;

    // Limpiar fences markdown y recortar a lo que va de <p> a </p>
    $out = trim($out);
    $out = preg_replace('/^```[a-z]*\s*/i', '', $out);
    $out = preg_replace('/\s*```$/', '', $out);
    if (preg_match('/<p\b.*<\/p>/is', $out, $m)) {
        $out = $m[0];
    }
    $out = trim($out);

    // Validación mínima: debe tener al menos un <p> y longitud razonable
    if (stripos($out, '<p') === false || strlen(strip_tags($out)) < 80) {
        return null;
    }
    return $out;
}

// ─── 1. Candidatos: marcas activas SIN descripción_larga ───
$candidatos = []; // slug => ['nombre'=>, 'cod'=>]
if ($solo) {
    $m = get_object_marca('nombre_clave', $solo);
    if (!$m) { echo "Marca '$solo' no encontrada\n"; exit(1); }
    $candidatos[$solo] = ['nombre' => $m['nombre'] ?? $solo, 'cod' => 0];
} else {
    $cursor = $db->marcas->find(
        ['estado' => 1, '$or' => [
            ['descripción_larga' => ['$exists' => false]],
            ['descripción_larga' => ''],
            ['descripción_larga' => null],
        ]],
        ['projection' => ['nombre_clave' => 1, 'nombre' => 1]]
    );
    foreach ($cursor as $m) {
        $slug = $m['nombre_clave'] ?? '';
        if ($slug === '') continue;
        $candidatos[$slug] = ['nombre' => $m['nombre'] ?? ucwords(str_replace(['-','_'], ' ', $slug)), 'cod' => 0];
    }
}
echo "Marcas activas sin descripción: " . count($candidatos) . "\n";

// ─── 2. Posición GSC por marca (para priorizar las cercanas a página 1) ───
$gsc = []; // slug => ['pos'=>, 'impr'=>]
$credsPath = dirname(__DIR__, 2) . '/private/google_credentials.json';
if (!$solo && file_exists($credsPath)) {
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
            if (preg_match('~/de-([^/?\#]+)$~', $url, $mm)) {
                $gsc[$mm[1]] = ['pos' => $row->getPosition(), 'impr' => $row->getImpressions()];
            }
        }
        echo "Páginas de marca con datos GSC (28d): " . count($gsc) . "\n";
    } catch (Exception $e) {
        echo "Aviso: GSC no disponible (" . $e->getMessage() . "), priorizando por códigos\n";
    }
}

// nº de códigos activos por marca (desempate para marcas sin GSC)
if (!$solo) {
    foreach ($db->codigos->aggregate([
        ['$match' => ['estado' => 0]],
        ['$group' => ['_id' => '$marca', 'count' => ['$sum' => 1]]],
    ]) as $r) {
        if (isset($candidatos[$r->_id])) $candidatos[$r->_id]['cod'] = $r->count;
    }
}

// ─── 3. Ordenar: con-GSC primero (posición asc → 11-20 antes que 30+), luego impr; resto por códigos ───
$rank = [];
foreach ($candidatos as $slug => $info) {
    $pos  = $gsc[$slug]['pos']  ?? null;
    $impr = $gsc[$slug]['impr'] ?? 0;
    if ($pos !== null && $pos >= $min_pos && $pos <= $max_pos) {
        // grupo 0: en franja GSC → priorizar por impresiones desc (más tráfico potencial),
        // luego posición asc (más cerca de página 1). Más ROI que ordenar solo por posición.
        $rank[$slug] = [0, -$impr, $pos, -$info['cod']];
    } elseif ($min_pos == 0 && $max_pos == 9999) {
        // sin franja acotada: grupo 1 (sin GSC) por códigos desc
        $rank[$slug] = [1, 9999, 0, -$info['cod']];
    }
}
uasort($rank, fn($a, $b) => $a <=> $b);
$orden = array_slice(array_keys($rank), 0, $solo ? 1 : $limit);

echo "A procesar: " . count($orden) . ($dry ? " (DRY-RUN)" : "") . "\n\n";
log_info("[bulk_desc] Inicio. " . count($orden) . " marcas (limit=$limit, dry=" . ($dry?1:0) . ")");

// ─── 4. Generar y guardar ───
$ok = 0; $err = 0;
foreach ($orden as $slug) {
    $nombre = $candidatos[$slug]['nombre'];
    $pinfo  = isset($gsc[$slug]) ? sprintf("pos=%.1f impr=%d", $gsc[$slug]['pos'], $gsc[$slug]['impr']) : "sin GSC";
    echo "[$slug] ($nombre) — $pinfo\n";

    $html = generar_descripcion($nombre);
    if ($html === null) {
        echo "  ✗ Groq falló / salida inválida\n\n";
        $err++;
        log_error("[bulk_desc] Falló $slug");
        sleep(3);
        continue;
    }

    if ($dry) {
        echo "  ⟶ " . substr(strip_tags($html), 0, 160) . "...\n\n";
    } else {
        $res = $db->marcas->updateOne(
            ['nombre_clave' => $slug],
            ['$set' => [
                'descripción_larga'      => $html,
                'descripcion_generada_at' => new MongoDB\BSON\UTCDateTime(),
                'descripcion_generada_por' => 'bulk_descripciones',
            ]]
        );
        echo "  ✓ guardada (" . strlen(strip_tags($html)) . " ch) modified=" . $res->getModifiedCount() . "\n\n";
        $ok++;
    }
    sleep(3);
}

$resumen = "Fin. Descripciones OK: $ok | Errores: $err" . ($dry ? " (dry-run)" : "");
log_info("[bulk_desc] $resumen");
echo "=== $resumen ===\n";
