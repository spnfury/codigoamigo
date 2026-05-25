<?php
/**
 * Cron: re-engagement de publicadores inactivos.
 *
 * Identifica usuarios que han publicado al menos 1 código pero llevan
 * >90 días sin publicar. Les envía un email con su potencial de ganancias
 * y CTA para volver a publicar.
 *
 * Cooldown: no se envía a un mismo usuario más de 1 email cada 30 días
 * (campo email_reengagement_publicar_fecha).
 *
 * Ejecutar via Jenkins. Frecuencia recomendada: semanal.
 *
 * Uso:
 *   php reengagement_publicar.php             → dry-run
 *   php reengagement_publicar.php --apply     → envía emails reales
 *   php reengagement_publicar.php --apply --limit=100   → cap de envíos
 */

date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/email_helper.php';

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

$umbral_inactividad_max = strtotime('-90 days');  // al menos 90d sin publicar
$umbral_inactividad_min = strtotime('-365 days'); // pero menos de 365d (cuentas más viejas suelen ser zombi)
$umbral_email_cooldown = strtotime('-30 days');

// 1. Encontrar fecha del último código por usuario (cualquier estado activo)
$pipeline = [
    ['$match' => ['estado' => ['$in' => [0, -1, 1]]]],
    ['$project' => [
        'id_usuario' => 1,
        'ts' => ['$toDate' => '$_id'],
    ]],
    ['$group' => [
        '_id' => '$id_usuario',
        'ultimo_codigo' => ['$max' => '$ts'],
        'total_codigos' => ['$sum' => 1],
    ]],
    ['$match' => [
        'ultimo_codigo' => [
            '$lt' => new MongoDB\BSON\UTCDateTime($umbral_inactividad_max * 1000),
            '$gte' => new MongoDB\BSON\UTCDateTime($umbral_inactividad_min * 1000),
        ],
    ]],
    ['$sort' => ['ultimo_codigo' => -1]], // más recientemente inactivos primero
];

$inactivos = $collection_codigos->aggregate($pipeline)->toArray();
echo "Publicadores inactivos (>90d sin publicar): " . count($inactivos) . "\n\n";

$enviados = 0;
$saltados_cooldown = 0;
$saltados_email_off = 0;
$saltados_sin_potencial = 0;
$errores = 0;

foreach ($inactivos as $row) {
    if ($enviados >= $limit) {
        echo "Cap alcanzado ($limit). Parando.\n";
        break;
    }

    $user_id = $row['_id'];
    $usuario = $collection_usuarios->findOne(['_id' => $user_id]);
    if (!$usuario) continue;

    // Cooldown: no reenviar antes de 30 días
    if (isset($usuario['email_reengagement_publicar_fecha'])) {
        $last = $usuario['email_reengagement_publicar_fecha'];
        if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > $umbral_email_cooldown) {
            $saltados_cooldown++;
            continue;
        }
    }

    // Verificar preferencias de email del usuario
    if (function_exists('usuarioAceptaEmail') && !usuarioAceptaEmail((string)$user_id, 'reengagement_publicar')) {
        $saltados_email_off++;
        continue;
    }

    $to_email = $usuario['mail'] ?? '';
    $username = trim($usuario['username'] ?? 'Usuario');
    if (empty($to_email)) continue;

    // Calcular potencial de ganancias
    $potencial_data = obtener_potencial_completo_usuario((string)$user_id);
    $total_potential = $potencial_data['total_potential'] ?? 0;
    $total_viewers = $potencial_data['total_unique_viewers'] ?? 0;

    $dias_inactivo = (int)floor((time() - $row['ultimo_codigo']->toDateTime()->getTimestamp()) / 86400);
    $total_codigos = $row['total_codigos'];

    echo "  - $to_email | $total_codigos códigos | ${dias_inactivo}d inactivo | potencial: " . number_format($total_potential, 2) . "€ ($total_viewers viewers)\n";

    // Skip si potencial = 0: subject "0€ esperándote" mata la apertura
    if ($total_potential <= 0) {
        $saltados_sin_potencial++;
        continue;
    }

    if (!$apply) continue;

    $subject = "$username, tus $total_codigos código" . ($total_codigos > 1 ? 's siguen' : ' sigue') . " activo" . ($total_codigos > 1 ? 's' : '') . " — vuelve a publicar y suma más viewers";
    $potencial_html = '';
    if ($total_potential > 0) {
        $potencial_html = '<div style="background:#f0f9ff;border-left:4px solid #2980b9;border-radius:6px;padding:18px 20px;margin:20px 0;">'
                       . '<p style="margin:0 0 6px;font-weight:700;color:#1a5f8a;">Tu potencial de ganancias actual</p>'
                       . '<p style="margin:0;font-size:28px;font-weight:700;color:#27ae60;">' . number_format($total_potential, 2) . '€</p>'
                       . '<p style="margin:6px 0 0;font-size:13px;color:#555;">' . $total_viewers . ' personas han visto tus códigos. Cada uno puede usarlos y generarte comisiones.</p>'
                       . '</div>';
    }

    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
          . '<body style="font-family:Segoe UI,Tahoma,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
          . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.1);">'
          . '<div style="background:linear-gradient(135deg,#E30613,#C40510);color:#fff;padding:30px 20px;text-align:center;">'
          . '<h1 style="margin:0;font-size:26px;">¡Hola ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '!</h1>'
          . '<p style="margin:10px 0 0;opacity:0.95;">Llevas un tiempo sin publicar y queremos verte de vuelta</p></div>'
          . '<div style="padding:30px;">'
          . '<p>Has publicado <strong>' . $total_codigos . ' código' . ($total_codigos > 1 ? 's' : '') . '</strong> en CodigoAmigo, pero hace <strong>' . $dias_inactivo . ' días</strong> que no compartes uno nuevo.</p>'
          . $potencial_html
          . '<p>Cuanto más códigos compartas, más usuarios podrás conseguir y mayores tus ganancias potenciales.</p>'
          . '<div style="text-align:center;margin:30px 0;">'
          . '<a href="https://www.codigoamigo.com/?page=publicar_codigo" style="display:inline-block;background:linear-gradient(135deg,#27ae60,#16a085);color:#fff;padding:16px 40px;text-decoration:none;border-radius:30px;font-weight:700;font-size:16px;">'
          . 'Publicar un nuevo código</a></div>'
          . '<p style="font-size:13px;color:#999;text-align:center;">¿Necesitas ayuda? Responde a este email.</p>'
          . '<p>Un saludo,<br>El equipo de CodigoAmigo</p>'
          . '</div></div></body></html>';

    $text = "Hola $username,\n\nLlevas $dias_inactivo días sin publicar en CodigoAmigo. Tienes $total_codigos código(s) publicados y un potencial de " . number_format($total_potential, 2) . "€.\n\nPublica un nuevo código: https://www.codigoamigo.com/?page=publicar_codigo\n\nEl equipo de CodigoAmigo";

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email, $username, $subject, $html,
        'reengagement_publicar',
        (string)$user_id,
        ['total_codigos' => $total_codigos, 'dias_inactivo' => $dias_inactivo, 'potencial' => $total_potential],
        $text
    );

    if (!empty($resultado['success'])) {
        $collection_usuarios->updateOne(
            ['_id' => $user_id],
            ['$set' => ['email_reengagement_publicar_fecha' => new MongoDB\BSON\UTCDateTime()]]
        );
        $enviados++;
    } else {
        $errores++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Emails enviados: $enviados\n";
echo "Saltados (cooldown): $saltados_cooldown\n";
echo "Saltados (preferencias email): $saltados_email_off\n";
echo "Saltados (sin potencial): $saltados_sin_potencial\n";
echo "Errores: $errores\n";
