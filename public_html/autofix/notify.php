<?php
/**
 * autofix/notify.php — envía un mensaje a Telegram reutilizando las
 * credenciales ya definidas en config/ai_config.php (chat admin).
 *
 * Uso:  php notify.php "texto del mensaje"
 *       echo "texto" | php notify.php
 */

$cfg = require __DIR__ . '/config.php';

$texto = $argv[1] ?? stream_get_contents(STDIN);
$texto = trim((string)$texto);
if ($texto === '') { fwrite(STDERR, "notify: mensaje vacío\n"); exit(1); }

// Cargar defines (TELEGRAM_BOT_TOKEN / TELEGRAM_ADMIN_CHAT_ID)
require_once $cfg['bootstrap_env'];

if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_ADMIN_CHAT_ID')
    || !TELEGRAM_BOT_TOKEN || !TELEGRAM_ADMIN_CHAT_ID) {
    fwrite(STDERR, "notify: faltan credenciales Telegram\n");
    exit(1);
}

$mensaje = "🤖 autofix · " . ($cfg['proyecto'] ?? '') . "\n\n" . $texto . "\n\n⏰ " . date('Y-m-d H:i:s');

$url  = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
$data = [
    'chat_id' => TELEGRAM_ADMIN_CHAT_ID,
    'text'    => $mensaje,                 // texto plano, sin parse_mode
    'disable_web_page_preview' => 'true',  // sin tarjeta de preview (las rutas llevan codigoamigo.com)
];

$ctx = stream_context_create(['http' => [
    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
    'method'  => 'POST',
    'content' => http_build_query($data),
    'timeout' => 8,
]]);

$res = @file_get_contents($url, false, $ctx);
if ($res === false) { fwrite(STDERR, "notify: fallo envío\n"); exit(1); }
echo "ok\n";
