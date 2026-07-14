<?php
/**
 * Cron: follow-up para usuarios que chocaron con el paywall de "Completar con IA"
 * (evento 'modal_ia_bloqueada' en eventos_vip_bloqueo) y siguen sin convertir.
 *
 * Corre 1 vez al día por la tarde/noche, mirando eventos de las últimas 24h —
 * intención especulativa (vio el modal, no necesariamente quiere pagar YA),
 * por eso ventana corta y solo 1 intento por evento.
 *
 * Cooldown: no se envía a un mismo usuario más de 1 vez cada 30 días
 * (campo email_followup_ia_modal_fecha).
 *
 * Uso:
 *   php followup_ia_bloqueada_vip.php             → dry-run
 *   php followup_ia_bloqueada_vip.php --apply     → envía emails reales
 *   php followup_ia_bloqueada_vip.php --apply --limit=100
 */

date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_destacados_email.php';

$apply = in_array('--apply', $argv ?? [], true);
$limit = 200;
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int)$m[1];
    }
}

echo "Modo: " . ($apply ? "APPLY" : "DRY-RUN") . " | Cap: $limit envíos\n\n";

$collection_eventos = getCollectionEventosVipBloqueo();
$collection_usuarios = getCollectionUsuarios();

$ventana_desde = strtotime('-1 day');
$umbral_email_cooldown = strtotime('-30 days');

$eventos = $collection_eventos->find([
    'tipo' => 'modal_ia_bloqueada',
    'fecha' => ['$gte' => new MongoDB\BSON\UTCDateTime($ventana_desde * 1000)],
])->toArray();

echo "Eventos modal IA bloqueada (últimas 24h): " . count($eventos) . "\n\n";

$enviados = 0;
$saltados_vip = 0;
$saltados_cooldown = 0;
$saltados_email_off = 0;
$errores = 0;
$vistos = [];

foreach ($eventos as $evento) {
    if ($enviados >= $limit) {
        echo "Cap alcanzado ($limit). Parando.\n";
        break;
    }

    $user_id = $evento['user_id'];
    if (isset($vistos[$user_id])) continue; // un usuario pudo generar el evento varios días seguidos
    $vistos[$user_id] = true;

    $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
    if (!$usuario) continue;

    if (!empty($usuario['is_vip'])) {
        $saltados_vip++;
        continue;
    }

    if (isset($usuario['email_followup_ia_modal_fecha'])) {
        $last = $usuario['email_followup_ia_modal_fecha'];
        if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > $umbral_email_cooldown) {
            $saltados_cooldown++;
            continue;
        }
    }

    if (!usuarioAceptaEmail($user_id, 'followup_ia_modal_vip')) {
        $saltados_email_off++;
        continue;
    }

    $to_email = trim($usuario['mail'] ?? '');
    $username = trim($usuario['username'] ?? 'Usuario');
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) continue;

    echo "  - $to_email\n";

    if (!$apply) continue;

    $subject = "$username, completa tus descripciones con IA gratis";

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($username) . '</strong>,</p>
        <p>Vimos que intentaste usar <strong>Completar con IA</strong> al publicar tu código. Es una función exclusiva VIP, pero merece la pena:</p>

        <div style="background-color:#f4f7fa;border-radius:10px;padding:20px;margin:25px 0;">
            <p style="margin:0 0 12px 0;color:#222;font-weight:700;font-size:16px;">Con VIP (9,99€/mes) consigues:</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>IA ilimitada</strong> para completar descripciones de tus códigos</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>10€ de saldo gratis cada mes</strong> para destacar tus códigos</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>Badge verificado</strong> — más confianza, más clics</p>
            <p style="margin:0;color:#444;font-size:14px;">&#10003; <strong>Chat ilimitado</strong> y auto-renovación de destacados</p>
        </div>

        <div style="background-color:#fdf3f4;border:1px solid #f8d7da;border-radius:8px;padding:14px 16px;">
            <p style="margin:0;color:#c7254e;font-weight:600;font-size:14px;">Primer mes a mitad de precio: 4,99€.</p>
        </div>';

    $html = _templateBaseDestacadoEmail('Completa tus descripciones con IA', $contenido, 'Hazte VIP ahora', 'https://www.codigoamigo.com/public/suscripcion_vip.php');
    $text = "Hola $username,\n\nVimos que intentaste usar Completar con IA al publicar tu código. Con VIP (9,99€/mes, primer mes 4,99€) consigues IA ilimitada, 10€ de saldo gratis cada mes, badge verificado y chat ilimitado.\n\nHazte VIP: https://www.codigoamigo.com/public/suscripcion_vip.php";

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email, $username, $subject, $html,
        'followup_ia_modal_vip',
        $user_id,
        [],
        $text
    );

    if (!empty($resultado['success'])) {
        $collection_usuarios->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($user_id)],
            ['$set' => ['email_followup_ia_modal_fecha' => new MongoDB\BSON\UTCDateTime()]]
        );
        $enviados++;
    } else {
        $errores++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Emails enviados: $enviados\n";
echo "Saltados (ya VIP): $saltados_vip\n";
echo "Saltados (cooldown): $saltados_cooldown\n";
echo "Saltados (preferencias email): $saltados_email_off\n";
echo "Errores: $errores\n";
