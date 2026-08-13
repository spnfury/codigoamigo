<?php
/**
 * Vigilancia de la salud de los envíos de email.
 *
 * No sustituye al panel de Brevo (los rebotes duros solo los conoce Brevo:
 * el envío es por SMTP y BREVO_API_KEY es un placeholder), pero sí detecta
 * lo que se ve desde aquí: fallos de entrega en el momento del envío,
 * caídas de volumen y crons que dejan de enviar.
 *
 * Pensado como red de seguridad tras ampliar la ventana de
 * reengagement_publicar.php a 90-730d (auditoría 2026-07-28): si la tasa de
 * error sube, avisa por Telegram en vez de seguir enviando a ciegas.
 *
 * Uso: php cron/salud_envios_email.php [--dias=7] [--umbral=5]
 * Cron sugerido: lunes 10:30 (tras el envío semanal de reengagement).
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../config/ai_config.php'; // TELEGRAM_BOT_TOKEN / TELEGRAM_ADMIN_CHAT_ID

$dias = 7;
$umbral_error_pct = 5.0; // % de fallos a partir del cual se avisa
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--dias=(\d+)$/', $arg, $m))   $dias = (int)$m[1];
    if (preg_match('/^--umbral=([\d.]+)$/', $arg, $m)) $umbral_error_pct = (float)$m[1];
}

$cod = getCollectionCodigos();
$db  = $cod->getDatabaseName();
$mgr = $cod->getManager();
$logs = new MongoDB\Collection($mgr, $db, 'email_logs');

$desde = new MongoDB\BSON\UTCDateTime(strtotime("-{$dias} days") * 1000);

// Agrupar por tipo: enviados OK vs fallidos
$pipe = [
    ['$match' => ['fecha' => ['$gte' => $desde]]],
    ['$group' => [
        '_id'   => ['tipo' => '$tipo', 'enviado' => '$enviado'],
        'n'     => ['$sum' => 1],
    ]],
];

$por_tipo = [];
foreach ($logs->aggregate($pipe) as $r) {
    $a = iterator_to_array($r);
    $id = iterator_to_array($a['_id']);
    $tipo = $id['tipo'] ?? '(sin tipo)';
    $ok = !empty($id['enviado']);
    if (!isset($por_tipo[$tipo])) $por_tipo[$tipo] = ['ok' => 0, 'fail' => 0];
    $por_tipo[$tipo][$ok ? 'ok' : 'fail'] += $a['n'];
}

if (!$por_tipo) {
    echo "Sin envíos registrados en los últimos $dias días.\n";
    exit(0);
}

uasort($por_tipo, fn($a, $b) => ($b['ok'] + $b['fail']) <=> ($a['ok'] + $a['fail']));

$tot_ok = 0; $tot_fail = 0;
echo "=== SALUD DE ENVÍOS (últimos $dias días) ===\n";
printf("%-38s %-7s %-7s %s\n", "tipo", "ok", "fallo", "% fallo");
$alertas = [];
foreach ($por_tipo as $tipo => $c) {
    $total = $c['ok'] + $c['fail'];
    $pct = $total ? 100 * $c['fail'] / $total : 0;
    printf("%-38s %-7d %-7d %.1f%%\n", substr($tipo, 0, 37), $c['ok'], $c['fail'], $pct);
    $tot_ok += $c['ok']; $tot_fail += $c['fail'];
    if ($pct >= $umbral_error_pct && $total >= 10) {
        $alertas[] = "{$tipo}: {$c['fail']}/{$total} fallidos (" . number_format($pct, 1) . "%)";
    }
}

$total_global = $tot_ok + $tot_fail;
$pct_global = $total_global ? 100 * $tot_fail / $total_global : 0;
echo "\nTotal: $tot_ok enviados, $tot_fail fallidos (" . number_format($pct_global, 1) . "% error)\n";

// Aviso a Telegram solo si algo se sale de madre
if ($alertas || $pct_global >= $umbral_error_pct) {
    $msg = "⚠️ *Salud de envíos email* (últimos {$dias}d)\n\n"
         . "Total: {$tot_ok} OK / {$tot_fail} fallidos (" . number_format($pct_global, 1) . "%)\n\n"
         . "Tipos por encima del umbral ({$umbral_error_pct}%):\n· "
         . implode("\n· ", $alertas ?: ['(ninguno concreto, pero el global supera el umbral)'])
         . "\n\nRevisar rebotes reales en el panel de Brevo.";

    if (defined('TELEGRAM_BOT_TOKEN') && defined('TELEGRAM_ADMIN_CHAT_ID')) {
        $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'chat_id'    => TELEGRAM_ADMIN_CHAT_ID,
                'text'       => $msg,
                'parse_mode' => 'Markdown',
            ]),
            'timeout' => 10,
        ]]);
        @file_get_contents($url, false, $ctx);
        echo "\nAviso enviado a Telegram.\n";
    }
} else {
    echo "Todo dentro de umbral ({$umbral_error_pct}%). Sin aviso.\n";
}
