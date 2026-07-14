<?php
/**
 * Cron: empuje a VIP para "power publishers" no-VIP.
 *
 * Distinto de nudge_vip_publicadores_recientes.php (que mira el ÚLTIMO
 * código, ventana 3-4 días): este busca volumen — 5+ códigos publicados
 * en los últimos 30 días. Es el usuario que ya demuestra ser un publicador
 * activo, pitch de "ya generas valor, VIP te lo devuelve".
 *
 * Cortesía anti-solapamiento: si el usuario recibió el nudge de
 * publicador-reciente en los últimos 7 días, se salta (evita dos pitches
 * VIP la misma semana).
 *
 * Cooldown propio: no se envía a un mismo usuario más de 1 vez cada 30 días
 * (campo email_power_publisher_vip_fecha).
 *
 * Uso:
 *   php power_publisher_vip.php             → dry-run
 *   php power_publisher_vip.php --apply     → envía emails reales
 *   php power_publisher_vip.php --apply --limit=100
 */

date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_destacados_email.php';

$apply = in_array('--apply', $argv ?? [], true);
$limit = 200;
$umbral_codigos = 5;
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int)$m[1];
    }
    if (preg_match('/^--umbral=(\d+)$/', $arg, $m)) {
        $umbral_codigos = (int)$m[1];
    }
}

echo "Modo: " . ($apply ? "APPLY" : "DRY-RUN") . " | Cap: $limit envíos | Umbral: $umbral_codigos códigos/30d\n\n";

$collection_codigos = getCollectionCodigos();
$collection_usuarios = getCollectionUsuarios();

$ventana_desde = strtotime('-30 days');
$umbral_email_cooldown = strtotime('-30 days');
$umbral_cortesia_nudge = strtotime('-7 days');

$pipeline = [
    ['$match' => ['estado' => ['$in' => [0, -1, 1]]]],
    ['$project' => [
        'id_usuario' => 1,
        'ts' => ['$toDate' => '$_id'],
    ]],
    ['$match' => [
        'ts' => ['$gte' => new MongoDB\BSON\UTCDateTime($ventana_desde * 1000)],
    ]],
    ['$group' => [
        '_id' => '$id_usuario',
        'total_codigos' => ['$sum' => 1],
    ]],
    ['$match' => [
        'total_codigos' => ['$gte' => $umbral_codigos],
    ]],
];

$candidatos = $collection_codigos->aggregate($pipeline)->toArray();
echo "Power publishers (>= $umbral_codigos códigos / 30d): " . count($candidatos) . "\n\n";

$enviados = 0;
$saltados_vip = 0;
$saltados_cooldown = 0;
$saltados_cortesia = 0;
$saltados_email_off = 0;
$errores = 0;

foreach ($candidatos as $row) {
    if ($enviados >= $limit) {
        echo "Cap alcanzado ($limit). Parando.\n";
        break;
    }

    $user_id = $row['_id'];
    $usuario = $collection_usuarios->findOne(['_id' => $user_id]);
    if (!$usuario) continue;

    if (!empty($usuario['is_vip'])) {
        $saltados_vip++;
        continue;
    }

    if (isset($usuario['email_nudge_vip_fecha'])) {
        $last_nudge = $usuario['email_nudge_vip_fecha'];
        if ($last_nudge instanceof MongoDB\BSON\UTCDateTime && $last_nudge->toDateTime()->getTimestamp() > $umbral_cortesia_nudge) {
            $saltados_cortesia++;
            continue;
        }
    }

    if (isset($usuario['email_power_publisher_vip_fecha'])) {
        $last = $usuario['email_power_publisher_vip_fecha'];
        if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > $umbral_email_cooldown) {
            $saltados_cooldown++;
            continue;
        }
    }

    if (!usuarioAceptaEmail((string)$user_id, 'power_publisher_vip')) {
        $saltados_email_off++;
        continue;
    }

    $to_email = trim($usuario['mail'] ?? '');
    $username = trim($usuario['username'] ?? 'Usuario');
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) continue;

    $total_codigos = $row['total_codigos'];

    echo "  - $to_email | $total_codigos código(s) en 30d\n";

    if (!$apply) continue;

    $subject = "$username, con $total_codigos códigos publicados ya mereces ser VIP";

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($username) . '</strong>,</p>
        <p>Este mes has publicado <strong>' . (int)$total_codigos . ' códigos</strong>. Eres de los que más aporta a la comunidad — es el momento de que la comunidad te lo devuelva.</p>

        <div style="background-color:#f4f7fa;border-radius:10px;padding:20px;margin:25px 0;">
            <p style="margin:0 0 12px 0;color:#222;font-weight:700;font-size:16px;">Con VIP (9,99€/mes) consigues:</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>10€ de saldo gratis cada mes</strong> para destacar tus códigos sin pagar de tu bolsillo</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>Badge verificado</strong> — más confianza, más clics en TODOS tus códigos</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>Chat ilimitado</strong> — no pierdas ni un contacto interesado</p>
            <p style="margin:0;color:#444;font-size:14px;">&#10003; <strong>Auto-renovación</strong> de tus destacados</p>
        </div>

        <div style="background-color:#fdf3f4;border:1px solid #f8d7da;border-radius:8px;padding:14px 16px;">
            <p style="margin:0;color:#c7254e;font-weight:600;font-size:14px;">Primer mes a mitad de precio: 4,99€.</p>
        </div>';

    $html = _templateBaseDestacadoEmail('Con ' . (int)$total_codigos . ' códigos, ya mereces ser VIP', $contenido, 'Hazte VIP ahora', 'https://www.codigoamigo.com/public/suscripcion_vip.php');
    $text = "Hola $username,\n\nEste mes has publicado $total_codigos códigos. Con VIP (9,99€/mes, primer mes 4,99€) consigues 10€ de saldo gratis cada mes, badge verificado, chat ilimitado y auto-renovación de destacados.\n\nHazte VIP: https://www.codigoamigo.com/public/suscripcion_vip.php";

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email, $username, $subject, $html,
        'power_publisher_vip',
        (string)$user_id,
        ['total_codigos' => $total_codigos],
        $text
    );

    if (!empty($resultado['success'])) {
        $collection_usuarios->updateOne(
            ['_id' => $user_id],
            ['$set' => ['email_power_publisher_vip_fecha' => new MongoDB\BSON\UTCDateTime()]]
        );
        $enviados++;
    } else {
        $errores++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Emails enviados: $enviados\n";
echo "Saltados (ya VIP): $saltados_vip\n";
echo "Saltados (cortesía nudge reciente): $saltados_cortesia\n";
echo "Saltados (cooldown): $saltados_cooldown\n";
echo "Saltados (preferencias email): $saltados_email_off\n";
echo "Errores: $errores\n";
