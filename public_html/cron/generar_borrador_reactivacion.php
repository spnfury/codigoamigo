<?php
/**
 * Genera el borrador del correo de reactivación (no envía nada).
 *
 * A una lista que lleva años sin recibir nada no se le manda un catálogo de
 * ofertas: se le manda un correo corto que recuerde quién eres y deje marchar
 * sin fricción a quien no quiera seguir. Cuanto más fácil sea irse, menos gente
 * pulsa "spam", y son las quejas de spam —no las bajas— las que hunden la
 * reputación del dominio y arrastran al correo transaccional.
 *
 * Por eso el correo lleva una sola llamada a la acción, la baja bien visible, y
 * como gancho los códigos reales con más movimiento, no promesas genéricas.
 *
 * Deja la newsletter en estado 'borrador' y SIN cola de destinatarios: no puede
 * salir por accidente. Para enviarla hay que revisarla en el panel, pasarla a
 * 'programada' y usar cron/reactivacion_email.php.
 *
 * Uso: php cron/generar_borrador_reactivacion.php [--marcas=6]
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';

$opts     = getopt('', ['marcas:']);
$n_marcas = isset($opts['marcas']) ? max(3, (int)$opts['marcas']) : 6;

$db = createConnection();

// ─── Gancho: marcas con actividad reciente ───
//
// No sirve ordenar por totalclicks: ese contador es acumulado de años y saca
// arriba marcas que ya no existen (ClipClaps y compañía). A quien lleva años
// sin saber de nosotros hay que enseñarle lo que está vivo HOY, así que el
// criterio es cuántos códigos se han publicado en los últimos meses.
$DIAS_ACTIVIDAD = 180;
$desde = new MongoDB\BSON\UTCDateTime((time() - $DIAS_ACTIVIDAD * 86400) * 1000);

$candidatas = [];
foreach ($db->selectCollection('codigos')->aggregate([
    ['$match'  => ['estado' => 0, '$expr' => ['$gte' => [['$toDate' => '$_id'], $desde]]]],
    ['$group'  => ['_id' => '$marca', 'recientes' => ['$sum' => 1]]],
    ['$match'  => ['recientes' => ['$gte' => 3]]],
    ['$sort'   => ['recientes' => -1]],
    ['$limit'  => 40],
]) as $r) {
    $candidatas[(string)$r['_id']] = ['recientes' => (int)$r['recientes']];
}

$col_marcas = $db->selectCollection('marcas');
$destacadas = [];
foreach ($candidatas as $slug => $d) {
    $m = $col_marcas->findOne(['nombre_clave' => $slug], ['projection' => ['nombre' => 1, 'categoria' => 1]]);
    if (!$m) continue;
    // Se enseña el total de códigos activos, no solo los recientes: es el dato
    // que ve quien entra en la ficha.
    $activos = $db->selectCollection('codigos')->countDocuments(['marca' => $slug, 'estado' => 0]);
    $destacadas[] = [
        'slug'      => $slug,
        'nombre'    => (string)($m['nombre'] ?? $slug),
        'cat'       => (string)($m['categoria'] ?? ''),
        'n'         => $activos,
        'recientes' => $d['recientes'],
    ];
    if (count($destacadas) >= $n_marcas) break;
}

if (count($destacadas) < 3) {
    echo "No hay suficientes marcas con códigos vivos para el gancho.\n";
    exit(1);
}

// ─── Plantilla ───

$filas = '';
foreach ($destacadas as $m) {
    $filas .= '<tr><td style="padding:10px 0;border-bottom:1px solid #f0f0f0;">'
        . '<a href="https://www.codigoamigo.com/de-' . htmlspecialchars($m['slug'], ENT_QUOTES, 'UTF-8') . '" '
        . 'style="color:#E30613;text-decoration:none;font-weight:700;font-size:15px;">'
        . htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') . '</a>'
        . '<span style="color:#888;font-size:13px;"> — ' . (int)$m['n'] . ' códigos activos</span>'
        . '</td></tr>';
}

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
. '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
. '<body style="margin:0;padding:0;background:#f4f7fa;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">'
. '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f7fa;padding:40px 20px;"><tr><td align="center">'
. '<table style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.05);" cellpadding="0" cellspacing="0" border="0">'

. '<tr><td align="center" style="padding:40px 30px 20px;border-bottom:1px solid #f0f0f0;">'
. '<img src="https://www.codigoamigo.com/img/logo_codigoamigo.png" alt="Código Amigo" style="max-width:180px;display:block;margin:0 auto;">'
. '<h1 style="color:#222;margin:25px 0 0;font-size:23px;font-weight:700;">¿Te seguimos escribiendo?</h1>'
. '</td></tr>'

. '<tr><td style="padding:30px 40px;color:#444;line-height:1.6;font-size:16px;">'
. '<p style="margin-top:0;">Hola,</p>'
. '<p>Te registraste en <strong>Código Amigo</strong> hace tiempo y llevamos una buena temporada sin escribirte. '
. 'Antes de volver a hacerlo, preferimos preguntártelo.</p>'
. '<p>Seguimos en lo mismo: <strong>códigos de invitación y referido</strong> que compartimos entre usuarios, '
. 'de esos en los que ganáis los dos. Ahora mismo estos son los que más se están usando:</p>'
. '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0;">' . $filas . '</table>'
. '<div style="text-align:center;margin:32px 0 10px;">'
. '<a href="https://www.codigoamigo.com" style="background:#E30613;color:#fff;padding:16px 38px;border-radius:8px;'
. 'text-decoration:none;font-weight:700;font-size:16px;display:inline-block;">Ver los códigos de hoy</a>'
. '</div>'
. '<p style="font-size:14px;color:#777;text-align:center;margin:18px 0 0;">'
. 'Si ya no te interesa, no hagas nada o date de baja abajo. No volveremos a insistir.</p>'
. '</td></tr>'

. '<tr><td style="background:#f8f9fa;padding:24px 40px;text-align:center;border-top:1px solid #f0f0f0;">'
. '<p style="color:#888;font-size:13px;margin:0;">Recibes este correo porque te registraste en '
. '<a href="https://www.codigoamigo.com" style="color:#E30613;text-decoration:none;font-weight:600;">codigoamigo.com</a>.</p>'
. '</td></tr>'

. '</table></td></tr></table></body></html>';

$texto = "Hola,\n\nTe registraste en Código Amigo hace tiempo y llevamos una buena temporada "
    . "sin escribirte. Antes de volver a hacerlo, preferimos preguntártelo.\n\n"
    . "Seguimos en lo mismo: códigos de invitación y referido que compartimos entre usuarios.\n\n"
    . "Los que más se usan ahora:\n";
foreach ($destacadas as $m) {
    $texto .= "  - {$m['nombre']}: https://www.codigoamigo.com/de-{$m['slug']} ({$m['n']} códigos)\n";
}
$texto .= "\nVer los códigos de hoy: https://www.codigoamigo.com\n\n"
    . "Si ya no te interesa, no hagas nada o date de baja con el enlace de abajo.\n";

// El pie de baja lo añade el envío con el enlace firmado de cada destinatario,
// así que aquí no se pone: si no, saldrían dos bajas distintas en el mismo correo.

$col_nl = $db->selectCollection('newsletters');
$doc = [
    'titulo'              => 'Reactivación ' . date('Y-m-d'),
    'asunto'              => '¿Te seguimos escribiendo?',
    'contenido_html'      => $html,
    'contenido_texto'     => $texto,
    'segmentacion'        => [],
    'estado'              => 'borrador',   // no lo toca el procesador de cola
    'fecha_creacion'      => new MongoDB\BSON\UTCDateTime(),
    'total_destinatarios' => 0,
    'total_enviados'      => 0,
    'total_errores'       => 0,
    'total_abiertos'      => 0,
    'total_clics'         => 0,
    'nota'                => 'Borrador automático. Revisar en el panel antes de programar. Sin cola generada.',
];

$res = $col_nl->insertOne($doc);
$id  = (string)$res->getInsertedId();

$preview = __DIR__ . '/../logs/borrador_reactivacion_' . date('Y-m-d') . '.html';
@file_put_contents($preview, $html);

echo "Borrador creado.\n";
echo "  id      : $id\n";
echo "  estado  : borrador (sin cola, no puede enviarse por accidente)\n";
echo "  asunto  : {$doc['asunto']}\n";
echo "  marcas  : " . implode(', ', array_column($destacadas, 'nombre')) . "\n";
echo "  preview : $preview\n\n";
echo "Para enviarlo hay que, por este orden:\n";
echo "  1. Arreglar SPF y DKIM en el DNS (si no, reactivacion_email.php aborta)\n";
echo "  2. Revisar y aprobar el contenido en el panel\n";
echo "  3. Pasarlo a 'programada' y generar la cola solo del segmento de publicadores\n";
echo "  4. php cron/reactivacion_email.php --newsletter=$id --aplicar\n";

log_info('[borrador_reactivacion] creado', ['id' => $id, 'marcas' => count($destacadas)]);
