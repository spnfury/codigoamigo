<?php
/**
 * Cron nocturno: genera FAQs para top N marcas con códigos activos pero sin FAQs.
 *
 * Selección:
 *   - Marcas con >=1 código activo (estado=0)
 *   - <3 FAQs ya creadas (para no repetir las ya procesadas)
 *   - Ordenadas por nº de códigos descendente
 *
 * Llama directamente al cron bulk_faqs_priority.php con --marca=<slug> para
 * reutilizar rotación de claves Groq y modelo llama-3.3-70b-versatile.
 *
 * Uso:
 *   php cron/nightly_faqs.php             → 30 marcas
 *   php cron/nightly_faqs.php --limit=50  → 50 marcas
 *   php cron/nightly_faqs.php --min-cod=5 → umbral mínimo códigos (def. 1)
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';

$opts = getopt('', ['limit:', 'min-cod:']);
$limit = isset($opts['limit']) ? max(1, (int)$opts['limit']) : 30;
$min_cod = isset($opts['min-cod']) ? max(1, (int)$opts['min-cod']) : 1;

$bulk_script = __DIR__ . '/bulk_faqs_priority.php';
if (!file_exists($bulk_script)) {
    log_error("[nightly_faqs] bulk_faqs_priority.php no encontrado: $bulk_script");
    exit(1);
}

$db = createConnection();

// 1. Códigos activos por marca
$marcas_codigos = [];
foreach ($db->codigos->aggregate([
    ['$match' => ['estado' => 0]],
    ['$group' => ['_id' => '$marca', 'count' => ['$sum' => 1]]]
]) as $r) {
    $marcas_codigos[$r->_id] = $r->count;
}

// 2. FAQs por marca
$marcas_con_faqs = [];
foreach ($db->marcas_faqs->aggregate([
    ['$group' => ['_id' => '$marca_clave', 'count' => ['$sum' => 1]]]
]) as $r) {
    if ($r->count >= 3) $marcas_con_faqs[$r->_id] = $r->count;
}

// 3. Candidatos
$cand = [];
foreach ($marcas_codigos as $slug => $cod) {
    if (empty($slug)) continue;
    if (isset($marcas_con_faqs[$slug])) continue;
    if ($cod < $min_cod) continue;
    $cand[$slug] = $cod;
}
arsort($cand);
$cand = array_slice($cand, 0, $limit, true);

log_info("[nightly_faqs] Procesando ".count($cand)." marcas (limit=$limit, min-cod=$min_cod)");
echo "[".date('Y-m-d H:i:s')."] nightly_faqs: ".count($cand)." marcas a procesar\n";

$ok = 0; $fail = 0;
foreach ($cand as $slug => $cod_count) {
    $cmd = '/usr/bin/php ' . escapeshellarg($bulk_script) . ' --marca=' . escapeshellarg($slug) . ' 2>&1';
    $out = shell_exec($cmd);
    $tail = trim($out ? substr($out, max(0, strlen($out) - 300)) : '');
    if (strpos($tail, 'FAQs generadas: 10') !== false || strpos($tail, 'FAQs generadas: 1') !== false) {
        $ok++;
        echo "  ✓ $slug ($cod_count cod)\n";
    } else {
        $fail++;
        echo "  ✗ $slug ($cod_count cod) | tail: $tail\n";
        log_error("[nightly_faqs] Falló $slug: $tail");
    }
}

// 4. Reactivar marcas que ahora tienen FAQs si seguían inactiva_seo
$con_faqs_ahora = $db->marcas_faqs->distinct('marca_clave', []);
$res = $db->marcas->updateMany(
    ['nombre_clave' => ['$in' => $con_faqs_ahora], 'inactiva_seo' => true],
    [
        '$set' => [
            'inactiva_seo_reactivada_at' => new MongoDB\BSON\UTCDateTime(),
            'inactiva_seo_reactivada_motivo' => 'auto nightly_faqs ' . date('Y-m-d'),
        ],
        '$unset' => ['inactiva_seo' => '']
    ]
);
$reactivadas = $res->getModifiedCount();

// 5. Regenerar sitemap
$sitemap_cron = __DIR__ . '/daily_sitemap.php';
if (file_exists($sitemap_cron)) {
    shell_exec('/usr/bin/php ' . escapeshellarg($sitemap_cron) . ' 2>&1');
}

echo "\n=== nightly_faqs fin: ok=$ok | fail=$fail | reactivadas=$reactivadas ===\n";
log_info("[nightly_faqs] ok=$ok fail=$fail reactivadas=$reactivadas");
