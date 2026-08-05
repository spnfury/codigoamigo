<?php
/**
 * Cron: invitar a publicar a usuarios registrados que aún no tienen códigos.
 *
 * Distinto del cron de re-engagement (ese ataca a publicadores que pararon).
 * Este ataca a registrados que jamás publicaron — el embudo más grande
 * (~120k usuarios). Manda email con CTA "+1€ por tu primer código".
 *
 * Filtros:
 *   - Sin códigos activos (estado 0/-1/1)
 *   - Registrados entre hace 7d y hace 2 años (evitar nuevos y zombies)
 *   - Sin bonus_primer_codigo aún
 *   - Cooldown 60 días desde último envío (email_primer_codigo_fecha)
 *
 * Ejecutar via Jenkins. Frecuencia recomendada: semanal.
 *
 * Uso:
 *   php email_primer_codigo.php             → dry-run
 *   php email_primer_codigo.php --apply --limit=200
 */

date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/email_helper.php';

$apply = in_array('--apply', $argv ?? [], true);
$limit = 300;
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) $limit = (int)$m[1];
}

echo "Modo: " . ($apply ? "APPLY" : "DRY-RUN") . " | Cap: $limit\n\n";

$collection_usuarios = getCollectionUsuarios();
$collection_codigos  = getCollectionCodigos();

// Set de usuarios que YA publicaron al menos un código activo
$publicaron = $collection_codigos->distinct('id_usuario', ['estado' => ['$in' => [0, -1, 1]]]);
$set_publicaron = [];
foreach ($publicaron as $oid) $set_publicaron[(string)$oid] = true;

$ts_min = (new DateTime('-2 years'))->getTimestamp();
$ts_max = (new DateTime('-7 days'))->getTimestamp();
$ts_cooldown = (new DateTime('-60 days'))->getTimestamp();

// ObjectId boundaries por timestamp: 4 bytes BE timestamp + 8 bytes (00... / ff...)
$min_oid = new MongoDB\BSON\ObjectId(str_pad(dechex($ts_min), 8, '0', STR_PAD_LEFT) . str_repeat('0', 16));
$max_oid = new MongoDB\BSON\ObjectId(str_pad(dechex($ts_max), 8, '0', STR_PAD_LEFT) . str_repeat('f', 16));

$cursor = $collection_usuarios->find(
    [
        '_id' => ['$gte' => $min_oid, '$lte' => $max_oid],
        'mail' => ['$exists' => true, '$ne' => ''],
        'estado' => 1,
        'bonus_primer_codigo' => ['$ne' => true],
    ],
    [
        'projection' => ['_id' => 1, 'mail' => 1, 'username' => 1, 'email_primer_codigo_fecha' => 1],
        'sort' => ['_id' => -1], // más recientes primero (mayor probabilidad de actividad)
    ]
);

$enviados = 0;
$saltados_publicaron = 0;
$saltados_cooldown = 0;
$errores = 0;

foreach ($cursor as $u) {
    if ($enviados >= $limit) {
        echo "Cap alcanzado.\n";
        break;
    }

    $uid = (string)$u['_id'];
    if (isset($set_publicaron[$uid])) { $saltados_publicaron++; continue; }

    if (isset($u['email_primer_codigo_fecha'])) {
        $last = $u['email_primer_codigo_fecha'];
        if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > $ts_cooldown) {
            $saltados_cooldown++;
            continue;
        }
    }

    if (function_exists('usuarioAceptaEmail') && !usuarioAceptaEmail($uid, 'invitacion_primer_codigo')) {
        continue;
    }

    $to_email = $u['mail'];
    $username = trim($u['username'] ?? 'Usuario');
    if (empty($to_email)) continue;

    if (!$apply) {
        if ($enviados < 10 || $enviados % 50 === 0) {
            echo "  [$enviados] $to_email | $username\n";
        }
        $enviados++;
        continue;
    }

    $subject = "🎁 +1€ de regalo si publicas tu primer código - CodigoAmigo";
    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
          . '<body style="font-family:Segoe UI,Tahoma,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
          . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.1);">'
          . '<div style="background:linear-gradient(135deg,#27ae60,#16a085);color:#fff;padding:30px 20px;text-align:center;">'
          . '<h1 style="margin:0;font-size:26px;">Hola ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</h1>'
          . '<p style="margin:10px 0 0;opacity:0.95;">Tienes 1€ esperándote</p></div>'
          . '<div style="padding:30px;">'
          . '<p>Te registraste en CodigoAmigo pero <strong>aún no has publicado ningún código de descuento</strong>.</p>'
          . '<div style="background:linear-gradient(135deg,#fff8e1,#fffde7);border-left:4px solid #f39c12;border-radius:8px;padding:18px 20px;margin:20px 0;">'
          . '<p style="margin:0;font-weight:700;color:#7b5400;font-size:16px;">🎁 +1€ de regalo en tu saldo al publicar tu primer código.</p>'
          . '</div>'
          . '<p>¿Tienes un código de referido de alguna app que uses? <strong>N26, Coinbase, Tulotero, Repsol Waylet, Backmarket</strong>... Compártelo y empezarás a ganar:</p>'
          . '<ul style="line-height:1.8;">'
          . '<li>1€ inmediato al publicar el primero</li>'
          . '<li>Visibilidad cada vez que alguien busque esa marca</li>'
          . '<li>Comisiones potenciales por cada usuario que use tu código</li>'
          . '</ul>'
          . '<div style="text-align:center;margin:30px 0;">'
          . '<a href="https://www.codigoamigo.com/?page=publicar_codigo" style="display:inline-block;background:linear-gradient(135deg,#27ae60,#16a085);color:#fff;padding:16px 40px;text-decoration:none;border-radius:30px;font-weight:700;font-size:16px;">Publicar mi primer código</a></div>'
          . '<p style="font-size:13px;color:#999;text-align:center;">Solo te llevará 1 minuto.</p>'
          . '<p>Un saludo,<br>El equipo de CodigoAmigo</p>'
          . '</div></div></body></html>';

    $text = "Hola $username,\n\nTe registraste en CodigoAmigo pero aún no has publicado ningún código.\n\nTe regalamos 1€ en tu saldo al publicar tu primer código.\n\nPublicar: https://www.codigoamigo.com/?page=publicar_codigo\n\nEl equipo de CodigoAmigo";

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email, $username, $subject, $html,
        'invitacion_primer_codigo',
        $uid,
        ['bonus' => 1],
        $text
    );

    if (!empty($resultado['success'])) {
        $collection_usuarios->updateOne(
            ['_id' => $u['_id']],
            ['$set' => ['email_primer_codigo_fecha' => new MongoDB\BSON\UTCDateTime()]]
        );
        $enviados++;
    } else {
        $errores++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Emails enviados: $enviados\n";
echo "Saltados (ya publicaron): $saltados_publicaron\n";
echo "Saltados (cooldown 60d): $saltados_cooldown\n";
echo "Errores: $errores\n";
echo ($apply ? "Aplicado.\n" : "Dry-run.\n");
