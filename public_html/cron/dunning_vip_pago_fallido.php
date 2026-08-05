<?php
/**
 * Cron: dunning para VIP con renovación fallida.
 *
 * Usuarios con vip_pago_fallido=true (lo marca public/webhook_stripe.php al
 * recibir invoice.payment_failed) que SIGUEN siendo VIP: Stripe reintentará el
 * cobro, pero si todos los intentos fallan perderán el VIP. Les pedimos que
 * actualicen su método de pago antes de que eso pase.
 *
 * No existe Customer Portal de Stripe operativo en el proyecto
 * (public/create-portal-session.php es código de debug roto), así que el CTA
 * apunta a /public/mis_viewers.php — página canónica VIP (landing + gestión),
 * destino del redirect 301 de suscripcion_vip.php.
 *
 * Cooldown: no se envía a un mismo usuario más de 1 vez cada 3 días
 * (campo vip_dunning_last_email), aunque el cron corra a diario.
 *
 * Uso:
 *   php dunning_vip_pago_fallido.php             → dry-run (no envía ni marca)
 *   php dunning_vip_pago_fallido.php --apply     → envía emails reales
 *   php dunning_vip_pago_fallido.php --apply --limit=100
 *
 * Sugerido: 1 vez al día.
 * php /home/admin/web/codigoamigo.com/public_html/cron/dunning_vip_pago_fallido.php --apply >> /home/admin/web/codigoamigo.com/public_html/cron/cron_dunning_vip.log 2>&1
 */

date_default_timezone_set('Europe/Madrid');
set_time_limit(300);

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
log_info('dunning_vip_pago_fallido: inicio', ['apply' => $apply, 'limit' => $limit]);

$collection_usuarios = getCollectionUsuarios();

$umbral_email_cooldown = strtotime('-3 days');

$candidatos = $collection_usuarios->find([
    'vip_pago_fallido' => true,
    'is_vip' => true,
])->toArray();

echo "VIP con pago fallido (aún activos): " . count($candidatos) . "\n\n";

$enviados = 0;
$saltados_no_vip = 0;
$saltados_cooldown = 0;
$saltados_email_off = 0;
$errores = 0;

foreach ($candidatos as $usuario) {
    if ($enviados >= $limit) {
        echo "Cap alcanzado ($limit). Parando.\n";
        break;
    }

    $user_id = (string)$usuario['_id'];

    // Doble check: pudo haberse resuelto el pago entre la query y este punto
    if (empty($usuario['is_vip']) || empty($usuario['vip_pago_fallido'])) {
        $saltados_no_vip++;
        continue;
    }

    // Cooldown: no reenviar antes de 3 días
    if (isset($usuario['vip_dunning_last_email'])) {
        $last = $usuario['vip_dunning_last_email'];
        if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > $umbral_email_cooldown) {
            $saltados_cooldown++;
            continue;
        }
    }

    if (!usuarioAceptaEmail($user_id, 'vip_dunning_pago_fallido')) {
        $saltados_email_off++;
        continue;
    }

    $to_email = trim($usuario['mail'] ?? '');
    $username = trim($usuario['username'] ?? 'Usuario');
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) continue;

    $proximo_intento_html = '';
    $proximo_intento_text = '';
    if (!empty($usuario['vip_pago_fallido_proximo_intento']) && $usuario['vip_pago_fallido_proximo_intento'] instanceof MongoDB\BSON\UTCDateTime) {
        $proximo_ts = $usuario['vip_pago_fallido_proximo_intento']->toDateTime()->getTimestamp();
        if ($proximo_ts > time()) {
            $proximo_fecha = date('d/m/Y', $proximo_ts);
            $proximo_intento_html = '<p style="margin:0;color:#444;font-size:14px;">Próximo intento de cobro automático: <strong>' . $proximo_fecha . '</strong></p>';
            $proximo_intento_text = "\nPróximo intento de cobro automático: $proximo_fecha";
        }
    }

    echo "  - $to_email\n";

    if (!$apply) continue;

    $subject = "$username, no hemos podido renovar tu VIP";

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($username) . '</strong>,</p>
        <p>No hemos podido cobrar la renovación mensual de tu suscripción VIP (9,99€): tu banco ha rechazado el cargo.</p>

        <div style="background-color:#fff8e1;border-left:4px solid #f39c12;border-radius:0 8px 8px 0;padding:14px 16px;margin:25px 0;">
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">Tu VIP <strong>sigue activo de momento</strong> y reintentaremos el cobro automáticamente, pero si todos los intentos fallan perderás tus beneficios.</p>
            ' . $proximo_intento_html . '
        </div>

        <p>Para no perder tu VIP, actualiza tu método de pago desde tu panel (puedes usar otra tarjeta):</p>

        <div style="background-color:#f4f7fa;border-radius:10px;padding:20px;margin:25px 0;">
            <p style="margin:0 0 12px 0;color:#222;font-weight:700;font-size:16px;">Lo que conservas si actualizas el pago:</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>10€ de saldo gratis cada mes</strong> para destacar tus códigos</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>Badge verificado</strong> — más confianza, más clics</p>
            <p style="margin:0;color:#444;font-size:14px;">&#10003; <strong>Chat ilimitado</strong> y auto-renovación de destacados</p>
        </div>

        <p style="color:#888;font-size:13px;">Si ya actualizaste tu tarjeta o crees que es un error, ignora este email o responde y te ayudamos.</p>';

    $url_panel = 'https://www.codigoamigo.com/public/mis_viewers.php';
    $html = _templateBaseDestacadoEmail('Problema con la renovación de tu VIP', $contenido, 'Actualizar método de pago', $url_panel);
    $text = "Hola $username,\n\nNo hemos podido cobrar la renovación mensual de tu suscripción VIP (9,99€): tu banco ha rechazado el cargo.\n\nTu VIP sigue activo de momento y reintentaremos el cobro automáticamente, pero si todos los intentos fallan perderás tus beneficios."
        . $proximo_intento_text
        . "\n\nActualiza tu método de pago desde tu panel: $url_panel\n\nSi ya actualizaste tu tarjeta o crees que es un error, ignora este email.";

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email, $username, $subject, $html,
        'vip_dunning_pago_fallido',
        $user_id,
        [],
        $text
    );

    if (!empty($resultado['success'])) {
        $collection_usuarios->updateOne(
            ['_id' => $usuario['_id']],
            ['$set' => ['vip_dunning_last_email' => new MongoDB\BSON\UTCDateTime()]]
        );
        $enviados++;
    } else {
        $errores++;
        log_error('dunning_vip_pago_fallido: error enviando email', [
            'usuario_id' => $user_id,
            'error' => $resultado['error'] ?? 'desconocido',
        ]);
    }
}

echo "\n=== RESUMEN ===\n";
echo "Emails enviados: $enviados\n";
echo "Saltados (ya no VIP / pago resuelto): $saltados_no_vip\n";
echo "Saltados (cooldown 3 días): $saltados_cooldown\n";
echo "Saltados (preferencias email): $saltados_email_off\n";
echo "Errores: $errores\n";

log_info('dunning_vip_pago_fallido: fin', [
    'enviados' => $enviados,
    'saltados_no_vip' => $saltados_no_vip,
    'saltados_cooldown' => $saltados_cooldown,
    'saltados_email_off' => $saltados_email_off,
    'errores' => $errores,
]);
