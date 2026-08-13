<?php
/**
 * Cron: promoción del programa de referidos a publicadores activos que nunca
 * lo han usado.
 *
 * Contexto: el programa de referidos (5€ para el invitado + 5€ para quien
 * invita cuando el amigo publica su primer código) lleva construido tiempo,
 * mecánica end-to-end correcta (landing de invitado, registro, recompensa),
 * pero 0 de 169.914 usuarios había generado nunca un código de referido — el
 * enlace solo vivía enterrado en un dropdown de perfil. No es un bug, es
 * pura invisibilidad.
 *
 * Selección: usuarios con >=1 código activo/pendiente publicado en los
 * últimos 60 días (ya demostraron intención de ganar dinero en la
 * plataforma) que NUNCA generaron codigo_referido (nunca vieron/usaron la
 * función). Se les genera el código YA en este cron (generarCodigoReferido)
 * para que el email traiga el enlace listo para compartir, sin pedirles
 * un paso extra.
 *
 * Cooldown: no se envía a un mismo usuario más de 1 vez cada 30 días
 * (campo email_referidos_promo_fecha).
 *
 * Uso:
 *   php promo_referidos_publicadores.php             → dry-run
 *   php promo_referidos_publicadores.php --apply     → envía emails reales
 *   php promo_referidos_publicadores.php --apply --limit=100
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

$collection_codigos = getCollectionCodigos();
$collection_usuarios = getCollectionUsuarios();

$ventana_desde = strtotime('-60 days');
$umbral_email_cooldown = strtotime('-30 days');

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
];

$candidatos = $collection_codigos->aggregate($pipeline)->toArray();
echo "Publicadores activos (60 días): " . count($candidatos) . "\n\n";

$enviados = 0;
$saltados_ya_tiene_codigo = 0;
$saltados_cooldown = 0;
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

    // Ya conoce/usa el programa: no repetir el pitch de descubrimiento
    if (!empty($usuario['codigo_referido'])) {
        $saltados_ya_tiene_codigo++;
        continue;
    }

    if (isset($usuario['email_referidos_promo_fecha'])) {
        $last = $usuario['email_referidos_promo_fecha'];
        if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > $umbral_email_cooldown) {
            $saltados_cooldown++;
            continue;
        }
    }

    if (!usuarioAceptaEmail((string)$user_id, 'promo_referidos_publicador')) {
        $saltados_email_off++;
        continue;
    }

    $to_email = trim($usuario['mail'] ?? '');
    $username = trim($usuario['username'] ?? 'Usuario');
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) continue;

    echo "  - $to_email | $row[total_codigos] código(s)\n";

    if (!$apply) continue;

    // Generar el código de referido YA para que el email lleve el enlace listo
    $codigo_referido = generarCodigoReferido((string)$user_id, (array)$usuario);
    $link_referido = 'https://www.codigoamigo.com/registro?ref=' . $codigo_referido;

    $subject = "$username, invita a un amigo y gana 5€";

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($username) . '</strong>,</p>
        <p>¿Sabías que puedes ganar dinero solo por invitar a tus amigos a Código Amigo?</p>

        <div style="background-color:#f0fdf4;border-radius:10px;padding:20px;margin:25px 0;border-left:4px solid #22c55e;">
            <p style="margin:0 0 8px 0;color:#222;font-weight:700;font-size:16px;">Así de simple:</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; Tu amigo recibe <strong>5€ gratis</strong> al registrarse con tu enlace</p>
            <p style="margin:0;color:#444;font-size:14px;">&#10003; Tú ganas <strong>otros 5€</strong> cuando publique su primer código</p>
        </div>

        <div style="background-color:#f4f7fa;border-radius:10px;padding:16px 20px;margin:25px 0;text-align:center;">
            <p style="margin:0 0 8px 0;color:#666;font-size:13px;">Tu enlace personal, ya listo para compartir:</p>
            <p style="margin:0;font-family:monospace;font-size:14px;color:#E30613;font-weight:700;word-break:break-all;">' . htmlspecialchars($link_referido) . '</p>
        </div>';

    $html = _templateBaseDestacadoEmail('Invita a un amigo, gana 5€', $contenido, 'Ver mi panel de invitaciones', 'https://www.codigoamigo.com/invitar-amigos');
    $text = "Hola $username,\n\nInvita a tus amigos a Código Amigo. Tu amigo recibe 5€ al registrarse y tú ganas otros 5€ cuando publique su primer código.\n\nTu enlace: $link_referido\n\nPanel de invitaciones: https://www.codigoamigo.com/invitar-amigos";

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email, $username, $subject, $html,
        'promo_referidos_publicador',
        (string)$user_id,
        ['total_codigos' => $row['total_codigos'], 'codigo_referido' => $codigo_referido],
        $text
    );

    if (!empty($resultado['success'])) {
        $collection_usuarios->updateOne(
            ['_id' => $user_id],
            ['$set' => ['email_referidos_promo_fecha' => new MongoDB\BSON\UTCDateTime()]]
        );
        $enviados++;
    } else {
        $errores++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Emails enviados: $enviados\n";
echo "Saltados (ya conocen/usan referidos): $saltados_ya_tiene_codigo\n";
echo "Saltados (cooldown): $saltados_cooldown\n";
echo "Saltados (preferencias email): $saltados_email_off\n";
echo "Errores: $errores\n";
