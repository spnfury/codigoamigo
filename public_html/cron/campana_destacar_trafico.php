<?php
/**
 * Campaña one-shot: destacar en fichas con tráfico real.
 *
 * Público (medido 2026-07-28): la unión de
 *   A) pagadores históricos con tarjeta (36 únicos) — intención de pago probada
 *   B) publicadores con código activo SIN destacar en una ficha que recibe
 *      >=3 clicks orgánicos/mes según GSC (959 únicos)
 *
 * El email lleva datos reales del usuario (su marca, cuántos compiten, visitas
 * de Google de la ficha) — nada de humo. Los destacados salen primero en la
 * ficha, eso es verdad y es el producto.
 *
 * Guardarraíles:
 *   - dry-run por defecto; enviar exige --apply
 *   - cap por defecto 300 (no quemar el dominio: el sitio envía ~55/día)
 *   - cooldown: no repetir a quien ya la recibió (campo email_campana_destacar_fecha)
 *   - respeta usuarioAceptaEmail() y el footer de baja lo añade el helper
 *   - prioriza por valor: pagadores primero, luego mejores fichas (clicks/competidor)
 *
 * Uso:
 *   php cron/campana_destacar_trafico.php                  → dry-run
 *   php cron/campana_destacar_trafico.php --apply          → envía (cap 300)
 *   php cron/campana_destacar_trafico.php --apply --limit=100
 *
 * NO programar en cron: es one-shot manual. Si se quiere repetir, esperar
 * >=30 días (el cooldown lo garantiza).
 */

date_default_timezone_set('Europe/Madrid');

// OJO: sin define('CRON_MODE') a propósito — con él, getCollectionUsuarios()
// (que usa usuarioAceptaEmail del email_helper) recibe un string en vez de la
// conexión y peta. Mismo patrón que reengagement_publicar.php.
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/email_helper.php';

$apply = in_array('--apply', $argv ?? [], true);
$limit = 300;
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) $limit = (int)$m[1];
}
echo "Modo: " . ($apply ? "APPLY" : "DRY-RUN") . " | Cap: $limit\n\n";

$cod = getCollectionCodigos();
// OJO: no llamar $db a esta variable — createConnection() usa `global $db`
// para cachear la conexión Mongo, y pisarla con el nombre (string) rompe
// getCollectionUsuarios() y todo lo que venga después.
$db_name = $cod->getDatabaseName();
$mgr = $cod->getManager();
$tx  = new MongoDB\Collection($mgr, $db_name, 'transacciones');
$us  = new MongoDB\Collection($mgr, $db_name, 'usuarios');

// Fichas con clicks orgánicos/mes (GSC 28d, medido 2026-07-28) y nº de
// competidores. Se usa para el texto del email y para priorizar.
$fichas = [
    'endado' => 193, 'oferplan' => 20, 'pagostore' => 16, 'cleverea' => 15,
    'honestgreenrestaurant' => 13, 'repsol-waylet' => 11, 'promosapp' => 10,
    'elevenfit' => 10, 'ilernaonline' => 9, 'petroprix' => 9, 'wesmartpark' => 9,
    'xceed' => 9, 'relojdurcal' => 8, 'wuolah' => 8, 'yego' => 8, 'dribo' => 7,
    'wonduu' => 6, 'monese' => 5, 'amovens' => 4, 'chippio' => 4,
    'coinmaster' => 4, 'deoss' => 4, 'widilo' => 4, 'divain' => 3,
];

// ─── Segmento A: pagadores históricos ───
$pagadores = [];
foreach ($tx->find(['$or' => [['stripe_payment_status' => 'paid'], ['metodo_pago' => 'tarjeta']]]) as $d) {
    $a = iterator_to_array($d);
    $uid = (string)($a['usuario_id'] ?? '');
    if ($uid !== '') $pagadores[$uid] = true;
}

// ─── Segmento B: publicadores sin destacar en fichas con tráfico ───
$competidores = [];
$candidatos = []; // uid => ['marca','clk','codigo_id','competidores']
foreach ($fichas as $marca => $clk) {
    $docs = $cod->find(['marca' => $marca, 'estado' => 0],
        ['projection' => ['id_usuario' => 1, 'destacado' => 1]])->toArray();
    $competidores[$marca] = count($docs);
    foreach ($docs as $c) {
        $a = iterator_to_array($c);
        if (!empty($a['destacado'])) continue; // ya destacado
        $uid = (string)$a['id_usuario'];
        if (!isset($candidatos[$uid]) || $fichas[$candidatos[$uid]['marca']] < $clk) {
            $candidatos[$uid] = [
                'marca' => $marca, 'clk' => $clk,
                'codigo_id' => (string)$a['_id'],
            ];
        }
    }
}

// Unión, con score: pagador pesa mucho; luego clicks por competidor
$destinatarios = [];
foreach (array_unique(array_merge(array_keys($pagadores), array_keys($candidatos))) as $uid) {
    $c = $candidatos[$uid] ?? null;
    $score = (isset($pagadores[$uid]) ? 1000 : 0)
           + ($c ? round(100 * $c['clk'] / max($competidores[$c['marca']], 1)) : 0);
    $destinatarios[$uid] = ['score' => $score, 'ficha' => $c];
}
uasort($destinatarios, fn($a, $b) => $b['score'] <=> $a['score']);

echo "Pagadores históricos: " . count($pagadores)
   . " | Publicadores en fichas con tráfico: " . count($candidatos)
   . " | Unión: " . count($destinatarios) . "\n\n";

$enviados = 0; $saltados_pref = 0; $saltados_cooldown = 0; $sin_mail = 0; $errores = 0;
$cooldown_ts = strtotime('-30 days');

foreach ($destinatarios as $uid => $info) {
    if ($enviados >= $limit) { echo "Cap alcanzado ($limit).\n"; break; }

    try { $oid = new MongoDB\BSON\ObjectId($uid); } catch (Throwable $e) { continue; }
    $u = $us->findOne(['_id' => $oid]);
    if (!$u) continue;
    $ua = iterator_to_array($u);
    $mail = strtolower(trim($ua['mail'] ?? ''));
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) { $sin_mail++; continue; }

    // Cooldown de esta campaña
    $last = $ua['email_campana_destacar_fecha'] ?? null;
    if ($last instanceof MongoDB\BSON\UTCDateTime && $last->toDateTime()->getTimestamp() > $cooldown_ts) {
        $saltados_cooldown++; continue;
    }

    // Preferencias del usuario
    if (function_exists('usuarioAceptaEmail') && !usuarioAceptaEmail($uid, 'campana_destacar')) {
        $saltados_pref++; continue;
    }

    $nombre = trim($ua['username'] ?? 'Publicador');
    $ficha  = $info['ficha'];

    if ($ficha) {
        $marca  = $ficha['marca'];
        $ncomp  = $competidores[$marca] ?? 0;
        $clk    = $ficha['clk'];
        $url    = 'https://www.codigoamigo.com/destacar_codigo?codigo=' . $ficha['codigo_id'];
        $subject = "Tu código de " . strtoupper($marca) . " compite con " . max($ncomp - 1, 1) . " más — destácalo desde 0,99€";
        $cuerpo_dato = '<p>Tu ficha de <strong>' . htmlspecialchars(strtoupper($marca), ENT_QUOTES, 'UTF-8') . '</strong> recibió '
            . '<strong>' . (int)$clk . ' visitas desde Google el último mes</strong>, y ahí compiten '
            . '<strong>' . (int)$ncomp . ' códigos</strong>. Los destacados aparecen los primeros: '
            . 'quien entra suele usar uno de los primeros que ve.</p>';
    } else {
        // Pagador histórico sin ficha en la lista: mensaje general
        $url = 'https://www.codigoamigo.com/mis-anuncios';
        $subject = "$nombre, vuelve a poner tu código arriba — destacar desde 0,99€";
        $cuerpo_dato = '<p>Ya destacaste algún código antes y sabes cómo funciona: los destacados salen '
            . 'los primeros de su marca y se llevan la mayoría de los usos.</p>';
    }

    printf("  %-36s score=%-5d %s\n", substr($mail, 0, 35), $info['score'],
        $ficha ? "{$ficha['marca']} ({$ficha['clk']} clk, {$competidores[$ficha['marca']]} comp)" : '[pagador]');

    if (!$apply) { $enviados++; continue; }

    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
        . '<body style="font-family:Segoe UI,Tahoma,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
        . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;">'
        . '<div style="background:linear-gradient(135deg,#E30613,#C40510);color:#fff;padding:26px 20px;text-align:center;">'
        . '<h1 style="margin:0;font-size:24px;">Hola, ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</h1></div>'
        . '<div style="padding:28px;">'
        . $cuerpo_dato
        . '<p>Destacar cuesta <strong>desde 0,99&euro; la semana</strong> (súper destacado con salida en portada, 3,99&euro;). '
        . 'Sin renovación automática salvo que tú la actives.</p>'
        . '<div style="text-align:center;margin:26px 0;">'
        . '<a href="' . $url . '" style="display:inline-block;background:linear-gradient(135deg,#27ae60,#16a085);color:#fff;'
        . 'padding:15px 38px;text-decoration:none;border-radius:30px;font-weight:700;font-size:16px;">Destacar mi código</a></div>'
        . '<p style="font-size:13px;color:#999;text-align:center;">Los datos de visitas provienen de Google Search Console del último mes.</p>'
        . '</div></div></body></html>';

    $text = "Hola $nombre,\n\n"
        . ($ficha
            ? "Tu ficha de " . strtoupper($ficha['marca']) . " recibió {$ficha['clk']} visitas desde Google el último mes y ahí compiten {$competidores[$ficha['marca']]} códigos. Los destacados salen primero.\n"
            : "Ya destacaste antes: los destacados salen primero en su marca.\n")
        . "\nDestacar cuesta desde 0,99€/semana (súper 3,99€). Sin renovación automática salvo que la actives.\n\n$url\n\nEl equipo de CodigoAmigo";

    $res = enviarEmailConBrevoYRegistrar($mail, $nombre, $subject, $html,
        'campana_destacar_trafico', $uid,
        ['marca' => $ficha['marca'] ?? null, 'clk' => $ficha['clk'] ?? null, 'score' => $info['score']],
        $text);

    if (!empty($res['success'])) {
        $enviados++;
        $us->updateOne(['_id' => $oid],
            ['$set' => ['email_campana_destacar_fecha' => new MongoDB\BSON\UTCDateTime()]]);
    } else {
        $errores++;
    }

    usleep(300000); // 0,3s entre envíos: no saturar SMTP de Brevo
}

echo "\n=== RESUMEN ===\n";
echo ($apply ? "Enviados" : "Se enviarían") . ": $enviados\n";
echo "Saltados cooldown: $saltados_cooldown | preferencias: $saltados_pref | sin mail: $sin_mail | errores: $errores\n";
