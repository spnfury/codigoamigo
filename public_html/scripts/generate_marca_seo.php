<?php
/**
 * Genera descripción_larga (contenido editorial SEO) para marcas de codigoamigo con
 * el campo vacío, priorizadas por GSC (fichero /tmp/codigoamigo_priority.txt).
 * Groq, grounded: SOLO usa datos reales (nombre, categoría, nº códigos activos).
 * Anti-alucinación: prohibido inventar descuentos/importes concretos.
 *
 * Uso: GROQ_API_KEY=... php scripts/generate_marca_seo.php [--limit=15] [--dry-run] [--force]
 */
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';

$opts   = getopt('', ['limit:', 'dry-run', 'force']);
$limit  = (int)($opts['limit'] ?? 15);
$dryRun = isset($opts['dry-run']);
$force  = isset($opts['force']);

$groqKey = getenv('GROQ_API_KEY') ?: '';
if ($groqKey === '' || str_contains($groqKey, 'your-groq')) {
    fwrite(STDERR, "ERROR: GROQ_API_KEY no válida.\n");
    exit(1);
}

$marcas  = getCollectionMarcas();
$codigos = getCollectionCodigos();

$prioFile = '/tmp/codigoamigo_priority.txt';
$slugs = is_file($prioFile) ? array_filter(array_map('trim', file($prioFile))) : [];
if (empty($slugs)) {
    // Fallback: todas las marcas con descripción_larga vacía
    foreach ($marcas->find(['$or' => [['descripción_larga' => ['$exists' => false]], ['descripción_larga' => ''], ['descripción_larga' => null]]], ['projection' => ['nombre_clave' => 1]]) as $m) {
        if (!empty($m['nombre_clave'])) $slugs[] = $m['nombre_clave'];
    }
}
echo "[marca-seo] candidatas: " . count($slugs) . " | modo=" . ($dryRun ? 'DRY' : 'APPLY') . "\n";

function numbersIn(string $s): array {
    preg_match_all('/\d[\d.,]*/', $s, $m);
    $o = [];
    foreach ($m[0] as $n) { $x = preg_replace('/[.,]/', '', $n); if (strlen($x) >= 2) $o[$x] = true; }
    return $o;
}
function sanitizeHtml(string $h): string {
    $h = preg_replace('/```html?|```/i', '', $h);
    return trim(strip_tags($h, '<p><h3><ul><li><strong><em>'));
}
function groqGen(string $key, array $facts): array {
    $model = getenv('GROQ_MODEL') ?: 'llama-3.1-8b-instant';
    $sys = "Eres redactor SEO experto en cupones y códigos de referido en España. Escribes SOLO con los "
         . "datos dados. PROHIBIDO inventar porcentajes/importes de descuento, fechas o condiciones no listadas. "
         . "Español de España, tono útil y natural, sin marketing vacío ni CTAs tipo 'no esperes'.";
    $user = "Escribe contenido SEO para la página de códigos de referido de esta marca usando SOLO estos datos:\n\n"
         . json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
         . "\n\nSalida HTML (sin markdown): un <h3> + 2-3 <p> (110-180 palabras). Explica qué es {$facts['marca']}, "
         . "cómo funcionan los códigos de referido/amigo (ambos ganan al compartir), y cómo usarlos en CodigoAmigo. "
         . "Si hay nº de códigos disponibles, menciónalo. NO inventes el % de descuento concreto (varía por código).";
    $payload = json_encode(['model' => $model, 'temperature' => 0.5, 'max_tokens' => 600,
        'messages' => [['role' => 'system', 'content' => $sys], ['role' => 'user', 'content' => $user]]], JSON_UNESCAPED_UNICODE);
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $key],
        CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 60]);
    $resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code === 429 && stripos((string)$resp, 'per day') === false) { sleep(35); return groqGen($key, $facts); }
    if ($code !== 200) { fwrite(STDERR, "  Groq $code\n"); return ['_http' => $code]; }
    $j = json_decode((string)$resp, true);
    return ['content' => $j['choices'][0]['message']['content'] ?? ''];
}

$done = 0; $skip = 0;
foreach ($slugs as $slug) {
    if ($done >= $limit) break;
    $m = $marcas->findOne(['nombre_clave' => $slug]);
    if (!$m) { $skip++; continue; }
    $dl = trim((string)($m['descripción_larga'] ?? ''));
    if (!$force && $dl !== '') { $skip++; continue; }

    $nCodigos = $codigos->countDocuments(['marca' => $slug, 'estado' => ['$in' => [0, 1]]]);
    $facts = array_filter([
        'marca'             => (string)($m['nombre'] ?? $slug),
        'categoria'         => (string)($m['categoria'] ?? ''),
        'codigos_disponibles' => $nCodigos ?: null,
        'descripcion_corta' => trim((string)($m['descripción'] ?? '')) ?: null,
        'portal'            => 'CodigoAmigo',
    ], fn($v) => $v !== null && $v !== '');

    echo sprintf("[%d/%d] %s (%s) codigos=%d\n", $done + 1, $limit, $slug, $facts['marca'], $nCodigos);

    $g = groqGen($groqKey, $facts);
    if (empty($g['content'])) { if (($g['_http'] ?? 0) === 429) { echo "  rate-limited, paro.\n"; break; } $skip++; continue; }
    $html = sanitizeHtml($g['content']);
    if (mb_strlen(strip_tags($html)) < 120) { echo "  corto, descarto.\n"; $skip++; continue; }

    // Gate anti-alucinación: números >=2 dígitos del texto deben estar en los datos.
    $allowed = [];
    foreach ($facts as $v) foreach (numbersIn((string)$v) as $k => $_) $allowed[$k] = true;
    $bad = array_diff(array_keys(numbersIn(strip_tags($html))), array_keys($allowed));
    if (!empty($bad)) { echo "  RECHAZADO (núm inventados: " . implode(',', $bad) . ")\n"; $skip++; sleep(20); continue; }

    if ($dryRun) { echo "  --- DRY:\n  " . str_replace("\n", "\n  ", $html) . "\n\n"; $done++; usleep(300000); continue; }

    $r = $marcas->updateOne(['_id' => $m['_id']], ['$set' => ['descripción_larga' => $html, 'descripcion_larga_generada' => 'groq_' . date('Y-m-d')]]);
    echo "  " . ($r->getModifiedCount() ? 'GUARDADO' : 'sin cambio') . "\n";
    $done += $r->getModifiedCount() ? 1 : 0;
    sleep(22);
}
echo "[marca-seo] hechos=$done saltados=$skip\n";
