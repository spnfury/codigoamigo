<?php
/**
 * Reactivación por lotes de la lista de correo, con calentamiento y freno.
 *
 * Por qué existe: la lista lleva años sin usarse. El único envío masivo
 * (2025-11-30) soltó 3.000 correos de golpe a direcciones de 2018-2021, sacó un
 * 0,2% de aperturas y se canceló. Mandar volumen de golpe desde un dominio sin
 * historial reciente es la forma más rápida de acabar en spam, y arrastra
 * también al correo transaccional (verificaciones, avisos de destacado), que hoy
 * sí funciona. Por eso aquí el volumen sube despacio y se para solo.
 *
 * Cada ejecución envía como mucho el lote del día y luego para. La rampa sube
 * únicamente si los lotes anteriores dieron señal de vida.
 *
 * Requisitos antes de la primera ejecución:
 *   1. php cron/auditar_lista_email.php --marcar   (rellena usuarios.email_auditoria)
 *   2. Una newsletter en estado 'programada' con su cola generada
 *
 * Uso:
 *   php cron/reactivacion_email.php --newsletter=<id> [--aplicar]
 *   php cron/reactivacion_email.php --estado
 *   php cron/reactivacion_email.php --reanudar
 *
 * Sin --aplicar solo dice qué haría. Cron sugerido: diario, una vez al día.
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_newsletter.php';
require_once __DIR__ . '/../myphp/funciones_baja_email.php';
require_once __DIR__ . '/../config/ai_config.php'; // TELEGRAM_BOT_TOKEN / TELEGRAM_ADMIN_CHAT_ID

$opts       = getopt('', ['newsletter:', 'aplicar', 'estado', 'reanudar', 'rampa:']);
$aplicar    = isset($opts['aplicar']);
$ver_estado = isset($opts['estado']);
$reanudar   = isset($opts['reanudar']);

$ESTADO_FILE = __DIR__ . '/../logs/reactivacion_email_estado.json';

// Rampa de calentamiento. Cada peldaño es el máximo de ese día; solo se sube si
// el lote evaluado pasó el control de aperturas.
$RAMPA = [200, 400, 800, 1500, 3000, 5000, 8000, 12000, 20000, 30000];
if (isset($opts['rampa'])) {
    $RAMPA = array_map('intval', array_filter(explode(',', $opts['rampa'])));
}

// Apertura mínima exigida a un lote ya maduro para seguir subiendo. Por debajo
// de esto el correo no está llegando a la bandeja y seguir solo empeora.
const APERTURA_MINIMA   = 0.03;  // 3%
const DIAS_MADURACION   = 2;     // días que se deja a un lote antes de juzgarlo
const LOTE_MINIMO_JUICIO = 150;  // por debajo, la muestra no dice nada

/**
 * Comprueba que el dominio autoriza a Brevo a enviar en su nombre.
 *
 * A 2026-08-07 el SPF era "v=spf1 include:_spf.mx.cloudflare.net ~all" y no
 * había ningún DKIM de Brevo publicado: todo lo que sale por Brevo con
 * remitente @codigoamigo.com falla la autenticación. Gmail y Yahoo piden SPF o
 * DKIM alineado; sin ninguno de los dos el correo va a spam por definición, y
 * ese es el motivo más probable del 0,2% de aperturas de noviembre de 2025.
 *
 * Mandar volumen en ese estado no solo se pierde: quema la reputación del
 * dominio y arrastra al correo transaccional, que hoy sí importa. Por eso esto
 * es una parada dura, no un aviso.
 *
 * Devuelve [] si todo está bien, o la lista de problemas encontrados.
 */
function comprobar_autenticacion_dominio($dominio = 'codigoamigo.com') {
    $problemas = [];

    $spf = '';
    foreach (@dns_get_record($dominio, DNS_TXT) ?: [] as $r) {
        $txt = $r['txt'] ?? '';
        if (stripos($txt, 'v=spf1') === 0) $spf = $txt;
    }
    if ($spf === '') {
        $problemas[] = 'no hay registro SPF';
    } elseif (!preg_match('/include:\s*(spf\.)?(brevo|sendinblue)\.com/i', $spf)) {
        $problemas[] = 'el SPF no autoriza a Brevo (falta include:spf.brevo.com) — actual: ' . $spf;
    }

    $dkim_ok = false;
    foreach (['brevo._domainkey', 'mail._domainkey'] as $sel) {
        $regs = @dns_get_record($sel . '.' . $dominio, DNS_TXT + DNS_CNAME) ?: [];
        foreach ($regs as $r) {
            if (!empty($r['txt']) || !empty($r['target'])) $dkim_ok = true;
        }
    }
    if (!$dkim_ok) $problemas[] = 'no hay DKIM de Brevo publicado (selector brevo._domainkey o mail._domainkey)';

    return $problemas;
}

// ─── Estado ───

function estado_cargar($file) {
    $d = json_decode((string)@file_get_contents($file), true);
    return is_array($d) ? $d : [
        'newsletter_id' => null,
        'peldano'       => 0,
        'enviados_total' => 0,
        'lotes'         => [],
        'pausado'       => false,
        'motivo_pausa'  => '',
    ];
}

function estado_guardar($file, $estado) {
    @file_put_contents($file, json_encode($estado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function avisar_telegram($texto) {
    if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_ADMIN_CHAT_ID')) return;
    @file_get_contents(
        'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage?' . http_build_query([
            'chat_id' => TELEGRAM_ADMIN_CHAT_ID,
            'text'    => $texto,
        ])
    );
}

$estado = estado_cargar($ESTADO_FILE);

if ($ver_estado) {
    echo json_encode($estado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

if ($reanudar) {
    $estado['pausado'] = false;
    $estado['motivo_pausa'] = '';
    estado_guardar($ESTADO_FILE, $estado);
    echo "Reactivación reanudada.\n";
    exit(0);
}

if ($estado['pausado']) {
    echo "PAUSADO: {$estado['motivo_pausa']}\n";
    echo "Revisa la entregabilidad antes de continuar. Para seguir: --reanudar\n";
    exit(0);
}

// ─── Autenticación del dominio (parada dura) ───

$fallos_auth = comprobar_autenticacion_dominio();
if ($fallos_auth) {
    echo "ABORTADO: el dominio no está autenticado para enviar por Brevo.\n";
    foreach ($fallos_auth as $f) echo "  - $f\n";
    echo "\nArréglalo en el DNS (Cloudflare) antes de enviar nada:\n";
    echo "  TXT  @                  v=spf1 include:spf.brevo.com include:_spf.mx.cloudflare.net ~all\n";
    echo "  (DKIM: copia el registro que da Brevo en Ajustes > Remitentes y dominios)\n";
    echo "  TXT  _dmarc             v=DMARC1; p=none; rua=mailto:rua@dmarc.brevo.com\n";
    log_error('[reactivacion_email] abortado: dominio sin autenticar', ['fallos' => $fallos_auth]);
    exit(1);
}

// ─── Newsletter objetivo ───

$db = createConnection();
$col_nl    = $db->selectCollection('newsletters');
$col_queue = $db->selectCollection('newsletter_queue');
$col_stats = $db->selectCollection('newsletter_stats');

$nl_id = $opts['newsletter'] ?? $estado['newsletter_id'];
if (!$nl_id) {
    echo "Falta --newsletter=<id>. Newsletters disponibles:\n";
    foreach ($col_nl->find([], ['projection' => ['titulo' => 1, 'estado' => 1], 'limit' => 20]) as $n) {
        printf("  %s  [%s]  %s\n", (string)$n['_id'], $n['estado'] ?? '?', $n['titulo'] ?? '');
    }
    exit(1);
}

$newsletter = $col_nl->findOne(['_id' => new MongoDB\BSON\ObjectId($nl_id)]);
if (!$newsletter) {
    echo "Newsletter $nl_id no encontrada.\n";
    exit(1);
}

// ─── Control de contenido ───
//
// La newsletter de 2025-11-30 se guardó con el HTML escapado ("&lt;h2&gt;"), o
// sea que a quien lo recibiera le llegaron las etiquetas a la vista. Antes de
// mandar nada a decenas de miles de personas conviene descartarlo.
$html = (string)($newsletter['contenido_html'] ?? '');
if ($html === '') {
    echo "ABORTADO: la newsletter no tiene contenido.\n";
    exit(1);
}
if (preg_match('/&lt;\s*(h[1-6]|p|div|ul|li|strong|a|img|br)\b/i', $html)) {
    echo "ABORTADO: el contenido tiene HTML escapado (&lt;h2&gt; y similares).\n";
    echo "          Se enviaría con las etiquetas a la vista. Corrige el contenido en el panel.\n";
    exit(1);
}
if (stripos($html, '/baja?') === false && stripos($html, 'preferencias') === false) {
    // El pie de baja lo añade el envío, pero si la plantilla ya trae uno propio
    // mejor saberlo: dos enlaces de baja distintos confunden.
    log_info('[reactivacion] la plantilla no trae enlace de baja propio; lo añade el envío');
}

// ─── Evaluar los lotes ya maduros ───

$hoy = date('Y-m-d');
$ya_enviado_hoy = false;
foreach ($estado['lotes'] as $l) {
    if (($l['fecha'] ?? '') === $hoy) $ya_enviado_hoy = true;
}
if ($ya_enviado_hoy) {
    echo "Ya se envió el lote de hoy ($hoy). Nada que hacer.\n";
    exit(0);
}

$puede_subir = true;
$evaluado    = null;

foreach (array_reverse($estado['lotes']) as $lote) {
    $edad = (int)round((strtotime($hoy) - strtotime($lote['fecha'])) / 86400);
    if ($edad < DIAS_MADURACION) continue;      // todavía joven, no se juzga
    if (($lote['enviados'] ?? 0) < LOTE_MINIMO_JUICIO) continue;

    // Aperturas registradas por el pixel propio desde que salió el lote.
    $aperturas = $col_stats->countDocuments([
        'newsletter_id' => (string)$newsletter['_id'],
        'tipo'          => 'apertura',
        'fecha'         => ['$gte' => new MongoDB\BSON\UTCDateTime(strtotime($lote['fecha']) * 1000)],
    ]);
    $tasa = $lote['enviados'] ? $aperturas / $lote['enviados'] : 0;
    $evaluado = ['fecha' => $lote['fecha'], 'enviados' => $lote['enviados'], 'aperturas' => $aperturas, 'tasa' => $tasa];

    if ($tasa < APERTURA_MINIMA) {
        $puede_subir = false;
        $estado['pausado'] = true;
        $estado['motivo_pausa'] = sprintf(
            'lote del %s: %d aperturas de %d envíos (%.2f%%), por debajo del %.0f%% exigido',
            $lote['fecha'], $aperturas, $lote['enviados'], 100 * $tasa, 100 * APERTURA_MINIMA
        );
    }
    break; // solo el lote maduro más reciente
}

if ($estado['pausado']) {
    estado_guardar($ESTADO_FILE, $estado);
    $msg = "🛑 Reactivación de correo PAUSADA\n" . $estado['motivo_pausa']
         . "\nEl correo no está llegando a la bandeja. Revisa SPF/DKIM/DMARC y la reputación en Brevo antes de reanudar.";
    echo $msg . "\n";
    if ($aplicar) avisar_telegram($msg);
    exit(0);
}

// ─── Tamaño del lote de hoy ───

$peldano = $estado['peldano'];
if ($puede_subir && $evaluado !== null) $peldano = min($peldano + 1, count($RAMPA) - 1);
$tope_hoy = $RAMPA[$peldano];

$pendientes = $col_queue->countDocuments(['newsletter_id' => (string)$newsletter['_id'], 'estado' => 'pendiente']);
$a_enviar = min($tope_hoy, $pendientes);

echo "Newsletter : " . ($newsletter['titulo'] ?? '') . "\n";
echo "Peldaño    : " . ($peldano + 1) . "/" . count($RAMPA) . " (tope $tope_hoy)\n";
echo "Pendientes : $pendientes\n";
if ($evaluado) {
    printf("Último lote juzgado: %s — %d/%d aperturas (%.2f%%)\n",
        $evaluado['fecha'], $evaluado['aperturas'], $evaluado['enviados'], 100 * $evaluado['tasa']);
}
echo "A enviar hoy: $a_enviar\n";

if ($a_enviar === 0) {
    echo "Cola vacía: reactivación terminada.\n";
    exit(0);
}

if (!$aplicar) {
    echo "\n(simulación: no se ha enviado nada; añade --aplicar)\n";
    exit(0);
}

// ─── Envío ───
//
// Se delega en procesarColaNewsletter, que ya hace reintentos, tracking y el
// corte por baja. Aquí solo se decide cuánto se manda.
$res = procesarColaNewsletter($a_enviar);

$estado['newsletter_id']  = (string)$newsletter['_id'];
$estado['peldano']        = $peldano;
$estado['enviados_total'] = ($estado['enviados_total'] ?? 0) + (int)$res['enviados'];
$estado['lotes'][]        = [
    'fecha'     => $hoy,
    'enviados'  => (int)$res['enviados'],
    'errores'   => (int)$res['errores'],
    'bajas'     => (int)($res['bajas'] ?? 0),
    'tope'      => $tope_hoy,
];
estado_guardar($ESTADO_FILE, $estado);

$resumen = sprintf(
    "📧 Reactivación día %d: %d enviados, %d errores, %d bajas. Total acumulado: %d. Quedan %d.",
    count($estado['lotes']), $res['enviados'], $res['errores'], $res['bajas'] ?? 0,
    $estado['enviados_total'], max(0, $pendientes - $res['enviados'])
);
echo $resumen . "\n";
avisar_telegram($resumen);

log_info('[reactivacion_email] lote enviado', [
    'enviados' => $res['enviados'],
    'errores'  => $res['errores'],
    'peldano'  => $peldano,
]);
