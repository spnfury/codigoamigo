<?php
/**
 * Cron: win-back para VIP caducados recientes.
 *
 * Usuarios que YA pagaron VIP una vez (vip_started_at existe) y cuyo
 * vip_expires_at venció hace 3-7 días. Ya conocen el valor del producto —
 * mayor probabilidad de reconversión que un frío. Ventana de 3-7 días para
 * no competir con el email de cancelación/expiración transaccional inmediato.
 *
 * Cooldown: no se envía a un mismo usuario más de 1 vez cada 30 días
 * (campo email_winback_vip_fecha).
 *
 * Uso:
 *   php winback_vip_caducado.php             → dry-run
 *   php winback_vip_caducado.php --apply     → envía emails reales
 *   php winback_vip_caducado.php --apply --limit=100
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

$collection_usuarios = getCollectionUsuarios();

$ventana_desde = strtotime('-7 days');
$ventana_hasta = strtotime('-3 days');
$umbral_email_cooldown = strtotime('-30 days');

$candidatos = $collection_usuarios->find([
    'is_vip' => false,
    'vip_started_at' => ['$exists' => true],
    'vip_expires_at' => [
        '$gte' => new MongoDB\BSON\UTCDateTime($ventana_desde * 1000),
        '$lte' => new MongoDB\BSON\UTCDateTime($ventana_hasta * 1000),
    ],
])->toArray();

echo "VIP caducados (3-7 días): " . count($candidatos) . "\n\n";

$enviados = 0;
$saltados_vip = 0;
$saltados_cooldown = 0;
$saltados_email_off = 0;
$errores = 0;

foreach ($candidatos as $usuario) {
    if ($enviados >= $limit) {
        echo "Cap alcanzado ($limit). Parando.\n";
        break;
    }

    $user_id = (string)$usuario['_id'];

    // Doble check: pudo haberse re-suscrito entre la query y este punto
    if (!empty($usuario['is_vip'])) {
        $saltados_vip++;
        continue;
    }

    if (isset($usuario['email_winback_vip_fecha'])) {
        $last = $usuario['email_winback_vip_fecha'];
        if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > $umbral_email_cooldown) {
            $saltados_cooldown++;
            continue;
        }
    }

    if (!usuarioAceptaEmail($user_id, 'winback_vip_caducado')) {
        $saltados_email_off++;
        continue;
    }

    $to_email = trim($usuario['mail'] ?? '');
    $username = trim($usuario['username'] ?? 'Usuario');
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) continue;

    echo "  - $to_email\n";

    if (!$apply) continue;

    $subject = "$username, tu saldo VIP te está esperando";

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($username) . '</strong>,</p>
        <p>Tu suscripción VIP caducó hace unos días. ¿La retomamos?</p>

        <div style="background-color:#f4f7fa;border-radius:10px;padding:20px;margin:25px 0;">
            <p style="margin:0 0 12px 0;color:#222;font-weight:700;font-size:16px;">Con VIP (9,99€/mes) recuperas:</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>10€ de saldo gratis cada mes</strong> para destacar tus códigos</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>Badge verificado</strong> — más confianza, más clics</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>Chat ilimitado</strong> — lee y responde todos los mensajes sin límite</p>
            <p style="margin:0;color:#444;font-size:14px;">&#10003; <strong>Auto-renovación</strong> de tus destacados</p>
        </div>

        <div style="background-color:#fdf3f4;border:1px solid #f8d7da;border-radius:8px;padding:14px 16px;">
            <p style="margin:0;color:#c7254e;font-weight:600;font-size:14px;">Vuelve con el primer mes a mitad de precio: 4,99€.</p>
        </div>';

    $html = _templateBaseDestacadoEmail('Tu saldo VIP te está esperando', $contenido, 'Reactivar VIP', 'https://www.codigoamigo.com/public/suscripcion_vip.php');
    $text = "Hola $username,\n\nTu suscripción VIP caducó hace unos días. Con VIP (9,99€/mes, primer mes 4,99€) recuperas 10€ de saldo gratis cada mes, badge verificado, chat ilimitado y auto-renovación de destacados.\n\nReactivar VIP: https://www.codigoamigo.com/public/suscripcion_vip.php";

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email, $username, $subject, $html,
        'winback_vip_caducado',
        $user_id,
        [],
        $text
    );

    if (!empty($resultado['success'])) {
        $collection_usuarios->updateOne(
            ['_id' => $usuario['_id']],
            ['$set' => ['email_winback_vip_fecha' => new MongoDB\BSON\UTCDateTime()]]
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
