<?php
/**
 * Informe SEO semanal: compara métricas GSC de esta semana vs la anterior y
 * envía un resumen a Telegram (TELEGRAM_ADMIN_CHAT_ID). Vigila la recuperación
 * post-fix Cloudflare sin entrar a GSC a mano.
 *
 * GSC tiene ~3 días de lag, así que "semana actual" = días -10..-4 y
 * "semana previa" = días -17..-11.
 *
 * Uso: php cron/informe_gsc_semanal.php
 * Cron sugerido: lunes 8:00 (tras refresco de marcas_oportunidad a las 7).
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../config/ai_config.php'; // define TELEGRAM_BOT_TOKEN / TELEGRAM_ADMIN_CHAT_ID (no cargado por includes)

$site = 'sc-domain:codigoamigo.com';

// ─── Credenciales GSC ───
$credsPath = dirname(__DIR__, 2) . '/private/google_credentials.json';
if (!file_exists($credsPath)) {
    log_error('[informe_gsc] sin credenciales GSC');
    exit(1);
}

$cur_ini = date('Y-m-d', strtotime('-10 days'));
$cur_fin = date('Y-m-d', strtotime('-4 days'));
$pre_ini = date('Y-m-d', strtotime('-17 days'));
$pre_fin = date('Y-m-d', strtotime('-11 days'));

try {
    $client = new Google\Client();
    $client->setAuthConfig($credsPath);
    $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
    $svc = new Google\Service\SearchConsole($client);
} catch (\Throwable $e) {
    log_error('[informe_gsc] error cliente GSC: ' . $e->getMessage());
    exit(1);
}

/** Suma clicks/impresiones/CTR/posición media ponderada de un rango. */
function gsc_totales($svc, $site, $ini, $fin) {
    $req = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
    $req->setStartDate($ini);
    $req->setEndDate($fin);
    $req->setDimensions(['date']);
    $req->setRowLimit(1000);
    $rows = $svc->searchanalytics->query($site, $req)->getRows() ?: [];
    $clk = 0; $imp = 0; $pos_sum = 0;
    foreach ($rows as $r) {
        $clk += $r->getClicks();
        $imp += $r->getImpressions();
        $pos_sum += $r->getPosition() * $r->getImpressions();
    }
    return [
        'clicks' => $clk,
        'impr'   => $imp,
        'ctr'    => $imp ? 100 * $clk / $imp : 0,
        'pos'    => $imp ? $pos_sum / $imp : 0,
    ];
}

/** Top páginas por clicks en el rango. */
function gsc_top_paginas($svc, $site, $ini, $fin, $n = 5) {
    $req = new Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
    $req->setStartDate($ini);
    $req->setEndDate($fin);
    $req->setDimensions(['page']);
    $req->setRowLimit(25000);
    $rows = $svc->searchanalytics->query($site, $req)->getRows() ?: [];
    usort($rows, fn($a, $b) => $b->getClicks() <=> $a->getClicks());
    $out = [];
    foreach (array_slice($rows, 0, $n) as $r) {
        $out[] = [
            'url' => str_replace('https://www.codigoamigo.com', '', $r->getKeys()[0]),
            'clk' => $r->getClicks(),
            'pos' => $r->getPosition(),
        ];
    }
    return $out;
}

try {
    $cur = gsc_totales($svc, $site, $cur_ini, $cur_fin);
    $pre = gsc_totales($svc, $site, $pre_ini, $pre_fin);
    $top = gsc_top_paginas($svc, $site, $cur_ini, $cur_fin, 5);
} catch (\Throwable $e) {
    log_error('[informe_gsc] error consulta GSC: ' . $e->getMessage());
    exit(1);
}

/** Flecha de variación porcentual. */
function delta($cur, $pre) {
    if ($pre == 0) return $cur > 0 ? '▲ nuevo' : '—';
    $pct = 100 * ($cur - $pre) / $pre;
    $arrow = $pct > 1 ? '▲' : ($pct < -1 ? '▼' : '▬');
    return sprintf('%s %+.0f%%', $arrow, $pct);
}

$msg  = "📊 Informe SEO semanal — CodigoAmigo\n";
$msg .= "Semana {$cur_ini} a {$cur_fin}\n";
$msg .= "(vs {$pre_ini} a {$pre_fin})\n\n";
$msg .= sprintf("👆 Clicks: %d  %s\n", $cur['clicks'], delta($cur['clicks'], $pre['clicks']));
$msg .= sprintf("👁 Impresiones: %d  %s\n", $cur['impr'], delta($cur['impr'], $pre['impr']));
$msg .= sprintf("🎯 CTR: %.2f%%  (antes %.2f%%)\n", $cur['ctr'], $pre['ctr']);
$msg .= sprintf("📍 Posición media: %.1f  (antes %.1f)\n", $cur['pos'], $pre['pos']);
$msg .= "\n🏆 Top páginas (clicks):\n";
foreach ($top as $t) {
    $msg .= sprintf("• %s — %d clk (pos %.0f)\n", $t['url'], $t['clk'], $t['pos']);
}

// ─── Enviar a Telegram ───
if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_ADMIN_CHAT_ID')) {
    echo $msg . "\n[sin credenciales Telegram, no enviado]\n";
    exit(0);
}

$url  = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
$data = ['chat_id' => TELEGRAM_ADMIN_CHAT_ID, 'text' => $msg]; // texto plano (igual que log_critical)
$ctx  = stream_context_create(['http' => [
    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
    'method'  => 'POST',
    'content' => http_build_query($data),
    'timeout' => 8,
]]);
$resp = @file_get_contents($url, false, $ctx);
if ($resp === false) {
    log_error('[informe_gsc] fallo envío Telegram');
    echo $msg . "\n[fallo envío]\n";
    exit(1);
}

log_info('[informe_gsc] informe semanal enviado a Telegram');
echo "Informe enviado.\n\n" . $msg;
