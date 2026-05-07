<?php
/**
 * Cron: digest semanal a publicadores con actividad real.
 *
 * Cada lunes envía a los usuarios con códigos activos un resumen de:
 *   - Cuántas personas vieron sus códigos esta semana
 *   - Top 3 códigos del usuario por clicks
 *   - Su potencial total acumulado
 *   - CTA para publicar más
 *
 * Filtros:
 *   - Usuario tiene >=1 código activo
 *   - Tuvo al menos 1 vista nueva en últimos 7 días (si no, no recibe email)
 *   - Cooldown 6 días (en práctica = 1 por semana)
 *
 * Uso:
 *   php digest_semanal_publicador.php           → dry-run
 *   php digest_semanal_publicador.php --apply --limit=500
 */

date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/email_helper.php';

$apply = in_array('--apply', $argv ?? [], true);
$limit = 500;
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) $limit = (int)$m[1];
}

echo "Modo: " . ($apply ? "APPLY" : "DRY-RUN") . " | Cap: $limit\n\n";

$collection_codigos  = getCollectionCodigos();
$collection_usuarios = getCollectionUsuarios();
$collection_vistas   = getCollectionVistas();

$ts_7d = (new DateTime('-7 days'))->getTimestamp();
$ts_cooldown = (new DateTime('-6 days'))->getTimestamp();

// Usar collection legacy 'vistas' que es la activa. Filtrar por _id timestamp
// (ObjectId) en lugar de fecha_vista que es string.
$min_oid = new MongoDB\BSON\ObjectId(str_pad(dechex($ts_7d), 8, '0', STR_PAD_LEFT) . str_repeat('0', 16));

$pipeline = [
    ['$match' => ['_id' => ['$gte' => $min_oid]]],
    ['$lookup' => [
        'from' => 'codigos',
        'localField' => 'id_codigo',
        'foreignField' => '_id',
        'as' => 'codigo',
    ]],
    ['$unwind' => '$codigo'],
    ['$group' => [
        '_id' => '$codigo.id_usuario',
        'vistas_7d' => ['$sum' => 1],
        'viewers_unicos' => ['$addToSet' => '$id_usuario'],
    ]],
    ['$project' => [
        'vistas_7d' => 1,
        'num_viewers_unicos' => ['$size' => '$viewers_unicos'],
    ]],
    ['$match' => ['vistas_7d' => ['$gte' => 3]]], // mínimo 3 vistas para hacer email merecer la pena
    ['$sort' => ['vistas_7d' => -1]],
];

$resultados = $collection_vistas->aggregate($pipeline, ['allowDiskUse' => true])->toArray();
echo "Publicadores con vistas en últimos 7d: " . count($resultados) . "\n\n";

$enviados = 0;
$saltados_cooldown = 0;
$saltados_email_off = 0;
$errores = 0;

foreach ($resultados as $row) {
    if ($enviados >= $limit) {
        echo "Cap alcanzado.\n";
        break;
    }

    $owner_id = $row['_id'];
    if (empty($owner_id)) continue;

    $owner_oid = is_string($owner_id) ? null : $owner_id;
    if (!$owner_oid) {
        try { $owner_oid = new MongoDB\BSON\ObjectId((string)$owner_id); }
        catch (Throwable $e) { continue; }
    }

    $usuario = $collection_usuarios->findOne(['_id' => $owner_oid]);
    if (!$usuario) continue;

    // Cooldown
    if (isset($usuario['email_digest_semanal_fecha'])) {
        $last = $usuario['email_digest_semanal_fecha'];
        if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > $ts_cooldown) {
            $saltados_cooldown++;
            continue;
        }
    }

    if (function_exists('usuarioAceptaEmail') && !usuarioAceptaEmail((string)$owner_oid, 'digest_semanal_publicador')) {
        $saltados_email_off++;
        continue;
    }

    $to_email = $usuario['mail'] ?? '';
    $username = trim($usuario['username'] ?? 'Usuario');
    if (empty($to_email)) continue;

    // Top 3 códigos del usuario por clicks (acumulado)
    $top_codigos = $collection_codigos->find(
        ['id_usuario' => $owner_oid, 'estado' => ['$in' => [0, -1, 1]]],
        [
            'projection' => ['marca' => 1, 'descripcion' => 1, 'totalclicks' => 1, 'clicks' => 1, 'destacado' => 1, 'fecha_fin_destacado' => 1],
            'sort' => ['totalclicks' => -1],
            'limit' => 3,
        ]
    )->toArray();

    // Código más visto SIN destacar activo → candidato para CTA destacar
    $ahora_destaca = new MongoDB\BSON\UTCDateTime();
    $codigo_sin_destacar = null;
    foreach ($top_codigos as $c) {
        $dest = $c['destacado'] ?? 0;
        $fin = $c['fecha_fin_destacado'] ?? null;
        $activo = $dest > 0 && $fin instanceof MongoDB\BSON\UTCDateTime && $fin > $ahora_destaca;
        if (!$activo) { $codigo_sin_destacar = $c; break; }
    }

    $vistas_7d = (int)$row['vistas_7d'];
    $viewers_unicos = (int)$row['num_viewers_unicos'];

    if ($vistas_7d === 0) continue;

    echo "  - $to_email | $vistas_7d vistas / $viewers_unicos viewers únicos\n";

    if (!$apply) { $enviados++; continue; }

    $top_html = '';
    foreach ($top_codigos as $c) {
        $marca = htmlspecialchars($c['marca'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $clicks = (int)(($c['totalclicks'] ?? 0) + ($c['clicks'] ?? 0));
        $desc = htmlspecialchars(mb_substr($c['descripcion'] ?? '', 0, 80), ENT_QUOTES, 'UTF-8');
        $top_html .= '<li style="margin:8px 0;"><strong>' . $marca . '</strong> — ' . number_format($clicks, 0, ',', '.') . ' clicks total<br><span style="color:#888;font-size:13px;">' . $desc . '...</span></li>';
    }

    $subject = "📈 $viewers_unicos personas vieron tus códigos esta semana - CodigoAmigo";

    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
          . '<body style="font-family:Segoe UI,Tahoma,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
          . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.1);">'
          . '<div style="background:linear-gradient(135deg,#27ae60,#16a085);color:#fff;padding:30px 20px;text-align:center;">'
          . '<h1 style="margin:0;font-size:24px;">¡Hola ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '!</h1>'
          . '<p style="margin:10px 0 0;opacity:0.95;">Tu resumen semanal en CodigoAmigo</p></div>'
          . '<div style="padding:30px;">'
          . '<div style="display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap;">'
          . '<div style="flex:1;min-width:130px;background:#f0f9ff;border-radius:8px;padding:18px;text-align:center;">'
          . '<div style="font-size:28px;font-weight:700;color:#2980b9;">' . $viewers_unicos . '</div>'
          . '<div style="font-size:13px;color:#555;">personas únicas vieron tus códigos</div>'
          . '</div>'
          . '<div style="flex:1;min-width:130px;background:#fff8e1;border-radius:8px;padding:18px;text-align:center;">'
          . '<div style="font-size:28px;font-weight:700;color:#d35400;">' . $vistas_7d . '</div>'
          . '<div style="font-size:13px;color:#555;">vistas totales 7 días</div>'
          . '</div>'
          . '</div>';

    if (!empty($top_html)) {
        $html .= '<h3 style="color:#1a1a2e;margin-top:25px;">Tus 3 códigos más vistos</h3>'
               . '<ul style="padding-left:20px;">' . $top_html . '</ul>';
    }

    // Bloque CTA destacar — solo si tiene código sin destacar
    if ($codigo_sin_destacar) {
        $marca_destaca = htmlspecialchars(ucfirst($codigo_sin_destacar['marca'] ?? 'tu código'), ENT_QUOTES, 'UTF-8');
        $clicks_destaca = (int)(($codigo_sin_destacar['totalclicks'] ?? 0) + ($codigo_sin_destacar['clicks'] ?? 0));
        $codigo_id_destaca = (string)$codigo_sin_destacar['_id'];
        $url_destaca = 'https://www.codigoamigo.com/destacar_codigo?codigo=' . $codigo_id_destaca;
        $html .= '<div style="background:linear-gradient(135deg,#fff8e1,#fffde7);border:2px solid #f39c12;border-radius:12px;padding:20px;margin:25px 0;">'
               . '<p style="margin:0 0 8px;font-size:15px;font-weight:700;color:#e67e22;">⭐ Multiplica el alcance de tu código ' . $marca_destaca . '</p>'
               . '<p style="margin:0 0 15px;font-size:14px;color:#555;">Tu código tiene ' . number_format($clicks_destaca, 0, ',', '.') . ' clicks acumulados. Los códigos <strong>destacados</strong> aparecen los primeros y consiguen hasta 5× más vistas.</p>'
               . '<div style="text-align:center;">'
               . '<a href="' . $url_destaca . '" style="display:inline-block;background:linear-gradient(135deg,#f39c12,#e67e22);color:#fff;padding:12px 28px;text-decoration:none;border-radius:25px;font-weight:700;font-size:15px;">⭐ Destacar por solo 0,99€</a>'
               . '</div>'
               . '</div>';
    }

    $html .= '<p>Cada persona que ve tu código es un potencial usuario que activará tu referido. Sigue añadiendo más para multiplicar tu alcance.</p>'
          . '<div style="text-align:center;margin:25px 0;">'
          . '<a href="https://www.codigoamigo.com/?page=publicar_codigo" style="display:inline-block;background:linear-gradient(135deg,#27ae60,#16a085);color:#fff;padding:14px 32px;text-decoration:none;border-radius:30px;font-weight:700;">Publicar otro código</a>'
          . '</div>'
          . '<p style="font-size:13px;color:#999;text-align:center;">Mira el ranking público de top publicadores: <a href="https://www.codigoamigo.com/lo-mas-publicado" style="color:#E30613;">lo-mas-publicado</a></p>'
          . '<p>Un saludo,<br>El equipo de CodigoAmigo</p>'
          . '</div></div></body></html>';

    $text = "Hola $username,\n\nTu resumen semanal:\n- $viewers_unicos personas únicas vieron tus códigos\n- $vistas_7d vistas totales en 7 días\n\nPublica otro código: https://www.codigoamigo.com/?page=publicar_codigo\n\nCodigoAmigo";

    $resultado = enviarEmailConBrevoYRegistrar(
        $to_email, $username, $subject, $html,
        'digest_semanal_publicador',
        (string)$owner_oid,
        ['vistas_7d' => $vistas_7d, 'viewers' => $viewers_unicos],
        $text
    );

    if (!empty($resultado['success'])) {
        $collection_usuarios->updateOne(
            ['_id' => $owner_oid],
            ['$set' => ['email_digest_semanal_fecha' => new MongoDB\BSON\UTCDateTime()]]
        );
        $enviados++;
    } else {
        $errores++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Emails enviados: $enviados\n";
echo "Saltados cooldown: $saltados_cooldown\n";
echo "Saltados preferencias email: $saltados_email_off\n";
echo "Errores: $errores\n";
echo ($apply ? "Aplicado.\n" : "Dry-run.\n");
