<?php
/**
 * Auditoría mensual de KPIs de CodigoAmigo.
 *
 * Ejecuta todos los chequeos definidos en el plan de re-auditoría tras la
 * sesión de fixes del 2026-04-26 (ver project_baseline_kpis_2026-04-26.md):
 *
 *   1. Códigos publicados últimos 30 días
 *   2. Publicadores únicos últimos 30 días
 *   3. VIPs Stripe activos
 *   4. Verificación webhook Stripe (invoices paid vs recargas BD)
 *   5. Usuarios con bonus_primer_codigo=true
 *   6. Emails reengagement enviados
 *   7. Usuarios con vip_emergency_extension (deuda técnica residual)
 *
 * Genera log en /cron/audit_kpis.log y envía resumen por email a thevega82@gmail.com.
 *
 * Programado en Jenkins: codigoamigo-audit-kpis-mensual (cron 0 10 26 * *).
 */

date_default_timezone_set('Europe/Madrid');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/email_helper.php';
require_once __DIR__ . '/../config/stripe.php';

\Stripe\Stripe::setApiKey(get_stripe_live_secret_key());

$log_file = __DIR__ . '/audit_kpis.log';
$lines = [];

function add(&$lines, $msg, $log_file) {
    $ts = date('Y-m-d H:i:s');
    $entry = "[$ts] $msg";
    file_put_contents($log_file, $entry . "\n", FILE_APPEND);
    echo $entry . "\n";
    $lines[] = $msg;
}

add($lines, "=== AUDITORÍA KPIs CodigoAmigo " . date('Y-m-d') . " ===", $log_file);

// Baseline 2026-04-26
$baseline = [
    'codigos_mes' => 52,
    'publicadores_mes' => 30,
    'vips_stripe' => 4,
    'bonus_primer_codigo' => 0,
    'reengagement_enviados' => 0,
    'vip_emergency_pendientes' => 0,
];
$baseline_date = '2026-04-26';

$collection_codigos = getCollectionCodigos();
$collection_usuarios = getCollectionUsuarios();
$collection_transacciones = getCollectionTransacciones();

$ts_30d = strtotime('-30 days');
$ts_baseline = strtotime($baseline_date);

// 1. Códigos publicados últimos 30 días (vía ObjectId timestamp)
$pipeline_codigos = [
    ['$match' => ['estado' => ['$in' => [0, -1, 1]]]],
    ['$project' => ['ts' => ['$toDate' => '$_id']]],
    ['$match' => ['ts' => ['$gte' => new MongoDB\BSON\UTCDateTime($ts_30d * 1000)]]],
    ['$count' => 'total'],
];
$res = $collection_codigos->aggregate($pipeline_codigos)->toArray();
$codigos_30d = $res[0]['total'] ?? 0;
add($lines, sprintf("1. Códigos últimos 30d: %d (baseline %s: %d) %s",
    $codigos_30d, $baseline_date, $baseline['codigos_mes'],
    $codigos_30d > $baseline['codigos_mes'] ? '✅ MEJORA' : '⚠️ SIN MEJORA'), $log_file);

// 2. Publicadores únicos últimos 30 días
$pipeline_pubs = [
    ['$match' => ['estado' => ['$in' => [0, -1, 1]]]],
    ['$project' => ['id_usuario' => 1, 'ts' => ['$toDate' => '$_id']]],
    ['$match' => ['ts' => ['$gte' => new MongoDB\BSON\UTCDateTime($ts_30d * 1000)]]],
    ['$group' => ['_id' => '$id_usuario']],
    ['$count' => 'total'],
];
$res = $collection_codigos->aggregate($pipeline_pubs)->toArray();
$publicadores_30d = $res[0]['total'] ?? 0;
add($lines, sprintf("2. Publicadores únicos 30d: %d (baseline: %d) %s",
    $publicadores_30d, $baseline['publicadores_mes'],
    $publicadores_30d > $baseline['publicadores_mes'] ? '✅ MEJORA' : '⚠️ SIN MEJORA'), $log_file);

// 3. VIPs Stripe activos
$vips_activos = $collection_usuarios->countDocuments([
    'is_vip' => true,
    'vip_subscription_id' => ['$regex' => '^sub_'],
]);
add($lines, sprintf("3. VIPs Stripe activos: %d (baseline: %d)",
    $vips_activos, $baseline['vips_stripe']), $log_file);

// 4. Verificar webhook Stripe: por cada sub, comparar invoices paid últimos 30d vs recargas en BD
$subs_a_revisar = [
    'sub_1TE5mZKZJkTJqkCwGk2a7FU0', // eduluthien
    'sub_1T5hu7KZJkTJqkCwubVkhCvY', // fran13989
    'sub_1TElVVKZJkTJqkCwl6dD0owk', // criis.lpz
    'sub_1TFFi8KZJkTJqkCwNJff33O7', // franciscojavito1
];

// Añadir suscripciones nuevas que pueden haberse creado después del baseline
$nuevas_subs = $collection_usuarios->find(
    ['is_vip' => true, 'vip_subscription_id' => ['$regex' => '^sub_'], 'vip_started_at' => ['$gte' => new MongoDB\BSON\UTCDateTime($ts_baseline * 1000)]],
    ['projection' => ['vip_subscription_id' => 1, 'mail' => 1]]
);
foreach ($nuevas_subs as $u) {
    $sid = $u['vip_subscription_id'] ?? '';
    if ($sid && !in_array($sid, $subs_a_revisar, true)) {
        $subs_a_revisar[] = $sid;
        add($lines, "  · Nueva sub detectada: $sid ({$u['mail']})", $log_file);
    }
}

$desajustes = [];
foreach ($subs_a_revisar as $sub_id) {
    try {
        $invoices = \Stripe\Invoice::all(['subscription' => $sub_id, 'limit' => 12]);
    } catch (Throwable $e) {
        add($lines, "  ✗ Stripe fallo en $sub_id: " . $e->getMessage(), $log_file);
        continue;
    }
    foreach ($invoices->data as $inv) {
        if ($inv->status !== 'paid') continue;
        $paid_at = $inv->status_transitions->paid_at ?? 0;
        if ($paid_at < $ts_baseline) continue; // sólo invoices posteriores al baseline

        $tx = $collection_transacciones->findOne([
            'tipo' => 'recarga_vip',
            'stripe_invoice' => $inv->id,
        ]);
        if (!$tx) {
            $desajustes[] = sprintf("    invoice %s | sub %s | %s | %.2f€ — SIN TRANSACCIÓN BD",
                $inv->id, $sub_id, date('Y-m-d', $paid_at), $inv->amount_paid / 100);
        }
    }
}
if (empty($desajustes)) {
    add($lines, "4. Webhook Stripe: ✅ todas las invoices pagadas tienen transacción en BD", $log_file);
} else {
    add($lines, "4. Webhook Stripe: ❌ desajustes encontrados:", $log_file);
    foreach ($desajustes as $d) add($lines, $d, $log_file);
}

// 5. Usuarios con bonus_primer_codigo=true
$bonus_count = $collection_usuarios->countDocuments(['bonus_primer_codigo' => true]);
add($lines, sprintf("5. bonus_primer_codigo otorgados: %d (baseline: %d) %s",
    $bonus_count, $baseline['bonus_primer_codigo'],
    $bonus_count > $baseline['bonus_primer_codigo'] ? '✅' : '⚠️'), $log_file);

// 6. Emails reengagement enviados (campo posterior al baseline)
$reeng_count = $collection_usuarios->countDocuments([
    'email_reengagement_publicar_fecha' => ['$gte' => new MongoDB\BSON\UTCDateTime($ts_baseline * 1000)],
]);
add($lines, sprintf("6. Emails reengagement enviados desde baseline: %d (baseline: %d) %s",
    $reeng_count, $baseline['reengagement_enviados'],
    $reeng_count > $baseline['reengagement_enviados'] ? '✅' : '⚠️'), $log_file);

// 7. Usuarios con vip_emergency_extension (deuda técnica)
$emergency_count = $collection_usuarios->countDocuments(['vip_emergency_extension' => ['$exists' => true]]);
add($lines, sprintf("7. Usuarios con vip_emergency_extension: %d (baseline tras fix: %d) %s",
    $emergency_count, $baseline['vip_emergency_pendientes'],
    $emergency_count === $baseline['vip_emergency_pendientes'] ? '✅' : '❌ NUEVO PROBLEMA — webhook puede estar fallando'), $log_file);

// Sugerencias automáticas
$sugerencias = [];
if ($publicadores_30d <= $baseline['publicadores_mes']) {
    $sugerencias[] = "Re-engagement no está moviendo la aguja. Considerar Google Ads / TikTok orientados a 'comparte tu código de referido'.";
}
if ($bonus_count === 0) {
    $sugerencias[] = "0 bonus otorgados. Verificar que createNewCode lo esté llamando en producción y que el banner de marca_moderna se vea.";
}
if ($emergency_count > 0) {
    $sugerencias[] = "vip_emergency_extension reapareciendo = webhook fallando otra vez. Revisar logs webhook_stripe.php y endpoint Stripe Dashboard.";
}
if (!empty($desajustes)) {
    $sugerencias[] = "Invoices Stripe sin transacción BD = webhook caído. Revisar STRIPE_WEBHOOK_SECRET y endpoint we_1TQ82JKZJkTJqkCwypWt5WKg.";
}

if (!empty($sugerencias)) {
    add($lines, "\n=== SIGUIENTES PASOS ===", $log_file);
    foreach ($sugerencias as $i => $s) add($lines, "  " . ($i+1) . ". $s", $log_file);
}

add($lines, "\n=== FIN AUDITORÍA ===", $log_file);

// Email resumen
$body_html = '<pre style="font-family:monospace;font-size:13px;line-height:1.5;">'
           . htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8')
           . '</pre>';
$body_text = implode("\n", $lines);

$verde = empty($desajustes) && $emergency_count === $baseline['vip_emergency_pendientes'];
$subject = ($verde ? '✅' : '⚠️') . ' Auditoría KPIs CodigoAmigo ' . date('Y-m-d');

try {
    enviarEmailConBrevoYRegistrar(
        'thevega82@gmail.com',
        'Sergio',
        $subject,
        $body_html,
        'audit_kpis_mensual',
        null,
        ['codigos_30d' => $codigos_30d, 'publicadores_30d' => $publicadores_30d, 'vips' => $vips_activos],
        $body_text
    );
    echo "\n[OK] Email enviado a thevega82@gmail.com\n";
} catch (Throwable $e) {
    echo "\n[ERROR] No se pudo enviar email: " . $e->getMessage() . "\n";
}
