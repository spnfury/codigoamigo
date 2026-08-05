<?php
/**
 * Cron mensual: oferta a los TOP 10 publicadores.
 *
 * Selecciona los 10 publicadores con más clicks acumulados (códigos activos).
 * Les envía email VIP-feel: estás en el top, te regalamos un mes VIP gratis
 * (campo regalo_vip_top_otorgado para evitar duplicados).
 *
 * Frecuencia: mensual (último día del mes).
 *
 * Uso:
 *   php email_top_publicadores.php             → dry-run
 *   php email_top_publicadores.php --apply
 */

date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/email_helper.php';

$apply = in_array('--apply', $argv ?? [], true);
echo "Modo: " . ($apply ? "APPLY" : "DRY-RUN") . "\n\n";

$collection_codigos  = getCollectionCodigos();
$collection_usuarios = getCollectionUsuarios();

// Top 10 por clicks acumulados
$top = $collection_codigos->aggregate([
    ['$match' => ['estado' => ['$in' => [0, -1, 1]]]],
    ['$group' => [
        '_id' => '$id_usuario',
        'total_codigos' => ['$sum' => 1],
        'total_clicks'  => ['$sum' => ['$add' => [
            ['$ifNull' => ['$totalclicks', 0]],
            ['$ifNull' => ['$clicks', 0]],
        ]]],
    ]],
    ['$match' => ['total_codigos' => ['$gte' => 3]]],
    ['$sort' => ['total_clicks' => -1]],
    ['$limit' => 10],
])->toArray();

echo "Top 10 publicadores por clicks:\n";
foreach ($top as $i => $row) {
    $u = $collection_usuarios->findOne(['_id' => $row['_id']]);
    if (!$u) continue;
    $username = trim($u['username'] ?? 'Usuario');
    $clicks = number_format($row['total_clicks'], 0, ',', '.');
    $codigos = $row['total_codigos'];
    $email = $u['mail'] ?? '';
    $is_vip = !empty($u['is_vip']);
    $ya_regalo = !empty($u['regalo_vip_top_otorgado']);

    echo "  " . ($i+1) . ". $email | $codigos códigos | $clicks clicks | "
        . ($is_vip ? "ya VIP" : "no VIP") . " | "
        . ($ya_regalo ? "ya recibió regalo" : "elegible") . "\n";

    if (!$apply) continue;
    if (empty($email)) continue;
    if ($ya_regalo) continue;

    if (function_exists('usuarioAceptaEmail') && !usuarioAceptaEmail((string)$row['_id'], 'top_publicador')) continue;

    // Si ya es VIP, no le damos VIP de regalo — le damos +5€ saldo extra como reconocimiento
    $premio_html = '';
    $premio_texto = '';
    if ($is_vip) {
        $nuevo_saldo = ($u['saldo'] ?? 0) + 5;
        $collection_usuarios->updateOne(
            ['_id' => $row['_id']],
            [
                '$set' => ['saldo' => $nuevo_saldo, 'regalo_vip_top_otorgado' => true, 'regalo_vip_top_fecha' => new MongoDB\BSON\UTCDateTime()],
            ]
        );
        $col_tx = getCollectionTransacciones();
        $col_tx->insertOne([
            'usuario_id' => (string)$row['_id'],
            'tipo' => 'reconocimiento_top_publicador',
            'cantidad' => 5,
            'descripcion' => 'Reconocimiento mensual top 10 publicadores',
            'fecha' => new MongoDB\BSON\UTCDateTime(),
            'estado' => 'completado',
        ]);
        $premio_html = '<div style="background:#fff8e1;border-left:4px solid #f39c12;border-radius:8px;padding:18px 20px;margin:20px 0;text-align:center;">'
                     . '<p style="margin:0;font-weight:700;color:#7b5400;">+5€ extra en tu saldo como reconocimiento</p></div>';
        $premio_texto = '+5€ saldo extra';
    } else {
        // Extender VIP 1 mes gratis
        $expires_at = new DateTime('+1 month');
        $collection_usuarios->updateOne(
            ['_id' => $row['_id']],
            [
                '$set' => [
                    'is_vip' => true,
                    'vip_subscription_id' => 'regalo_top_publicador_' . time(),
                    'vip_expires_at' => new MongoDB\BSON\UTCDateTime($expires_at->getTimestamp() * 1000),
                    'vip_started_at' => new MongoDB\BSON\UTCDateTime(),
                    'regalo_vip_top_otorgado' => true,
                    'regalo_vip_top_fecha' => new MongoDB\BSON\UTCDateTime(),
                ],
            ]
        );
        // +10€ saldo VIP
        try { renovar_saldo_vip((string)$row['_id']); } catch (Throwable $e) {}
        $premio_html = '<div style="background:linear-gradient(135deg,#ffd700,#f39c12);border-radius:8px;padding:20px;margin:20px 0;text-align:center;color:#1a1a2e;">'
                     . '<p style="margin:0;font-weight:700;font-size:18px;">👑 1 MES VIP GRATIS + 10€ saldo</p>'
                     . '<p style="margin:6px 0 0;font-size:13px;">Tu cuenta queda como VIP hasta el ' . $expires_at->format('d/m/Y') . '</p></div>';
        $premio_texto = '1 mes VIP gratis + 10€ saldo';
    }

    $subject = "🏆 Estás en el TOP 10 - regalo dentro - CodigoAmigo";
    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
          . '<body style="font-family:Segoe UI,Tahoma,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
          . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.1);">'
          . '<div style="background:linear-gradient(135deg,#E30613,#C40510);color:#fff;padding:30px 20px;text-align:center;">'
          . '<h1 style="margin:0;font-size:26px;">🏆 ¡Estás en el TOP 10!</h1>'
          . '<p style="margin:10px 0 0;opacity:0.95;">Hola ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</p></div>'
          . '<div style="padding:30px;">'
          . '<p>Eres uno de los <strong>10 publicadores con más clicks</strong> de CodigoAmigo este mes:</p>'
          . '<ul style="line-height:1.8;">'
          . '<li><strong>' . $codigos . '</strong> códigos publicados</li>'
          . '<li><strong>' . $clicks . '</strong> clicks acumulados en tus códigos</li>'
          . '<li>Posición <strong>#' . ($i + 1) . '</strong> del ranking</li>'
          . '</ul>'
          . '<p>Como reconocimiento, te regalamos:</p>'
          . $premio_html
          . '<p>Sigue publicando para mantenerte en el top y aparecer en <a href="https://www.codigoamigo.com/lo-mas-publicado">/lo-mas-publicado</a>.</p>'
          . '<p>Un saludo,<br>El equipo de CodigoAmigo</p>'
          . '</div></div></body></html>';

    $text = "🏆 ¡Estás en el TOP 10!\n\nHola $username,\n\nEres uno de los 10 publicadores con más clicks: $codigos códigos, $clicks clicks acumulados, posición #" . ($i+1) . ".\n\nRegalo: $premio_texto\n\nCodigoAmigo";

    enviarEmailConBrevoYRegistrar($email, $username, $subject, $html, 'top_publicador', (string)$row['_id'], ['posicion' => $i+1, 'clicks' => (int)$row['total_clicks']], $text);
    echo "    ✓ enviado + premio aplicado\n";
}

echo "\n=== FIN ===\n";
