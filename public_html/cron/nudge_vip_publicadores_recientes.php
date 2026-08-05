<?php
/**
 * Cron: empuje a VIP para publicadores recientes no-VIP.
 *
 * A diferencia de reengagement_publicar.php (que busca INACTIVOS), este busca
 * usuarios cuyo ÚLTIMO código fue publicado hace 3-4 días — el entusiasmo por
 * el código está fresco, es el momento de mayor receptividad para el pitch VIP,
 * en vez de mandarlo a los 169k usuarios de golpe.
 *
 * Cooldown: no se envía a un mismo usuario más de 1 vez cada 30 días
 * (campo email_nudge_vip_fecha).
 *
 * Uso:
 *   php nudge_vip_publicadores_recientes.php             → dry-run
 *   php nudge_vip_publicadores_recientes.php --apply     → envía emails reales
 *   php nudge_vip_publicadores_recientes.php --apply --limit=100
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

$ventana_desde = strtotime('-4 days');
$ventana_hasta = strtotime('-3 days');
$umbral_email_cooldown = strtotime('-30 days');

// Último código por usuario (cualquier estado activo/pendiente)
$pipeline = [
    ['$match' => ['estado' => ['$in' => [0, -1, 1]]]],
    ['$project' => [
        'id_usuario' => 1,
        'marca' => 1,
        'ts' => ['$toDate' => '$_id'],
    ]],
    ['$group' => [
        '_id' => '$id_usuario',
        'ultimo_codigo' => ['$max' => '$ts'],
        'ultima_marca' => ['$last' => '$marca'],
        'total_codigos' => ['$sum' => 1],
    ]],
    ['$match' => [
        'ultimo_codigo' => [
            '$gte' => new MongoDB\BSON\UTCDateTime($ventana_desde * 1000),
            '$lte' => new MongoDB\BSON\UTCDateTime($ventana_hasta * 1000),
        ],
    ]],
];

$candidatos = $collection_codigos->aggregate($pipeline)->toArray();
echo "Publicadores recientes (3-4 días): " . count($candidatos) . "\n\n";

$enviados = 0;
$saltados_vip = 0;
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

    // Ya es VIP: no tiene sentido el pitch
    if (!empty($usuario['is_vip'])) {
        $saltados_vip++;
        continue;
    }

    // Cooldown: no reenviar antes de 30 días
    if (isset($usuario['email_nudge_vip_fecha'])) {
        $last = $usuario['email_nudge_vip_fecha'];
        if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > $umbral_email_cooldown) {
            $saltados_cooldown++;
            continue;
        }
    }

    if (!usuarioAceptaEmail((string)$user_id, 'reengagement_vip_publicador')) {
        $saltados_email_off++;
        continue;
    }

    $to_email = trim($usuario['mail'] ?? '');
    $username = trim($usuario['username'] ?? 'Usuario');
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) continue;

    $total_codigos = $row['total_codigos'];
    $marca = $row['ultima_marca'] ?? '';

    echo "  - $to_email | $total_codigos código(s) | última marca: $marca\n";

    if (!$apply) continue;

    $subject = "$username, saca más partido a tu código" . ($marca ? " de " . ucfirst($marca) : "");

    $contenido = '
        <p style="margin-top:0;">Hola <strong>' . htmlspecialchars($username) . '</strong>,</p>
        <p>Hace unos días publicaste tu código' . ($marca ? ' de <strong>' . htmlspecialchars(ucfirst($marca)) . '</strong>' : '') . '. ¿Sabías que los usuarios VIP consiguen mucho más de sus códigos?</p>

        <div style="background-color:#f4f7fa;border-radius:10px;padding:20px;margin:25px 0;">
            <p style="margin:0 0 12px 0;color:#222;font-weight:700;font-size:16px;">Con VIP (9,99€/mes) consigues:</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>10€ de saldo gratis cada mes</strong> para destacar tus códigos sin pagar de tu bolsillo</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>Badge verificado</strong> — más confianza, más clics</p>
            <p style="margin:0 0 8px 0;color:#444;font-size:14px;">&#10003; <strong>Chat ilimitado</strong> — lee y responde todos los mensajes de quien te contacte, sin límite</p>
            <p style="margin:0;color:#444;font-size:14px;">&#10003; <strong>Auto-renovación</strong> de tus destacados, para no perder nunca la posición</p>
        </div>

        <div style="background-color:#fdf3f4;border:1px solid #f8d7da;border-radius:8px;padding:14px 16px;">
            <p style="margin:0;color:#c7254e;font-weight:600;font-size:14px;">Primer mes a mitad de precio: 4,99€.</p>
        </div>';

    $html = _templateBaseDestacadoEmail('Saca más partido a tus códigos', $contenido, 'Hazte VIP ahora', 'https://www.codigoamigo.com/public/suscripcion_vip.php');

    $text = "Hola $username,\n\nHace unos días publicaste tu código" . ($marca ? " de " . ucfirst($marca) : "") . ". Con VIP (9,99€/mes, primer mes 4,99€) consigues 10€ de saldo gratis cada mes, badge verificado, chat ilimitado y auto-renovación de destacados.\n\nHazte VIP: https://www.codigoamigo.com/public/suscripcion_vip.php";

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email, $username, $subject, $html,
        'reengagement_vip_publicador',
        (string)$user_id,
        ['total_codigos' => $total_codigos, 'marca' => $marca],
        $text
    );

    if (!empty($resultado['success'])) {
        $collection_usuarios->updateOne(
            ['_id' => $user_id],
            ['$set' => ['email_nudge_vip_fecha' => new MongoDB\BSON\UTCDateTime()]]
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
