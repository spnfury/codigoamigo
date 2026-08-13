<?php
/**
 * Revisión diferida (~2 semanas después del 2026-08-07):
 *  1. ¿Las 15 marcas thin mejoradas con Groq subieron de posición 11-20 hacia página 1?
 *     Compara la posición GSC actual con la del baseline logs/thin_content_2026-08-07.json.
 *  2. ¿Cuántos clics salientes acumula cada marca en `clicks_salida` (/salir.php)?
 *
 * Envía el resumen a Telegram (chat admin). Programado una vez vía `at`;
 * puede relanzarse a mano: php cron/revision_thin_clicks.php
 */

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../config/ai_config.php'; // TELEGRAM_BOT_TOKEN / TELEGRAM_ADMIN_CHAT_ID

$site = 'sc-domain:codigoamigo.com';
$baseline_file = __DIR__ . '/../logs/thin_content_2026-08-07.json';

// ─── Baseline de las marcas mejoradas ───
$baseline = json_decode((string)@file_get_contents($baseline_file), true);
$mejoradas = [];
foreach (($baseline['detalle'] ?? []) as $fila) {
    if (is_array($fila) && ($fila['resultado'] ?? '') === 'mejorada' && !empty($fila['nombre_clave'])) {
        // nombre_clave = slug usado en clicks_salida y en la URL /de-{slug}
        $mejoradas[$fila['nombre_clave']] = [
            'url' => $fila['url'] ?? ('https://www.codigoamigo.com/de-' . $fila['nombre_clave']),
            'pos_antes' => round((float)($fila['posicion'] ?? 0), 1),
            'imp_antes' => (int)($fila['impresiones'] ?? 0),
        ];
    }
}

if (empty($mejoradas)) {
    log_error('[revision_thin] baseline vacío o formato inesperado en ' . $baseline_file);
}

// ─── GSC: posición actual de esas URLs (últimos 14 días) ───
$gsc_ahora = [];
try {
    $credsPath = dirname(__DIR__, 2) . '/private/google_credentials.json';
    $client = new Google\Client();
    $client->setAuthConfig($credsPath);
    $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
    $svc = new Google\Service\SearchConsole($client);

    $req = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
    $req->setStartDate(date('Y-m-d', strtotime('-17 days')));
    $req->setEndDate(date('Y-m-d', strtotime('-3 days')));
    $req->setDimensions(['page']);
    $req->setRowLimit(5000);
    $rows = $svc->searchanalytics->query($site, $req)->getRows() ?: [];
    foreach ($rows as $r) {
        $keys = $r->getKeys();
        $gsc_ahora[$keys[0]] = [
            'pos' => round($r->getPosition(), 1),
            'clicks' => (int)$r->getClicks(),
            'impr' => (int)$r->getImpressions(),
        ];
    }
} catch (\Throwable $e) {
    log_error('[revision_thin] error GSC: ' . $e->getMessage());
}

// ─── Mongo: clics salientes por marca desde el 7-ago ───
$clicks_por_marca = [];
try {
    $db = createConnection();
    $agg = $db->selectCollection('clicks_salida')->aggregate([
        ['$group' => ['_id' => '$marca', 'total' => ['$sum' => 1]]],
        ['$sort' => ['total' => -1]],
        ['$limit' => 20],
    ]);
    foreach ($agg as $doc) {
        $clicks_por_marca[(string)$doc['_id']] = (int)$doc['total'];
    }
} catch (\Throwable $e) {
    log_error('[revision_thin] error clicks_salida: ' . $e->getMessage());
}

// ─── Componer informe ───
$msg = "📊 REVISIÓN THIN CONTENT + CLICKOUT (" . date('d/m/Y') . ")\n";
$msg .= "Contenido mejorado el 07/08 — posición GSC antes → ahora:\n\n";

$subieron = 0; $total = 0;
foreach ($mejoradas as $marca => $info) {
    $ahora = $gsc_ahora[$info['url']] ?? null;
    $total++;
    if ($ahora) {
        $flecha = $ahora['pos'] < $info['pos_antes'] - 0.5 ? '⬆️' : ($ahora['pos'] > $info['pos_antes'] + 0.5 ? '⬇️' : '➡️');
        if ($ahora['pos'] < $info['pos_antes'] - 0.5) { $subieron++; }
        $pagina1 = $ahora['pos'] <= 10 ? ' 🥇pág.1' : '';
        $msg .= sprintf("%s %s: %.1f → %.1f (%d clicks, %d imp)%s\n",
            $flecha, $marca, $info['pos_antes'], $ahora['pos'], $ahora['clicks'], $ahora['impr'], $pagina1);
    } else {
        $msg .= "❔ {$marca}: sin datos GSC en la ventana\n";
    }
}
$msg .= "\nResumen: {$subieron}/{$total} marcas subieron posición.\n";

$msg .= "\n🔗 CLICS SALIENTES por marca (clicks_salida, desde 07/08):\n";
if ($clicks_por_marca) {
    foreach ($clicks_por_marca as $marca => $n) {
        $msg .= "· {$marca}: {$n}\n";
    }
    $msg .= "\nEstos datos son el argumento para pedir alta en Awin/Impact.";
} else {
    $msg .= "(sin clics registrados aún)\n";
}

// ─── Enviar a Telegram ───
$url  = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
$data = ['chat_id' => TELEGRAM_ADMIN_CHAT_ID, 'text' => $msg];
$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $data, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
$resp = curl_exec($ch);
curl_close($ch);

log_info('[revision_thin] informe enviado', ['marcas' => $total, 'subieron' => $subieron, 'clicks_marcas' => count($clicks_por_marca)]);
echo $msg;
