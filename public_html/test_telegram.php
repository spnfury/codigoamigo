<?php
/**
 * Script de prueba para Telegram
 * Ayuda a verificar la configuración y obtener el Chat ID
 */

// Incluir configuración
require_once __DIR__ . '/config/ai_config.php';

echo "<h1>Test de Telegram</h1>";
echo "<h2>Configuración actual:</h2>";
echo "<pre>";
echo "TELEGRAM_BOT_TOKEN: " . (defined('TELEGRAM_BOT_TOKEN') ? substr(TELEGRAM_BOT_TOKEN, 0, 20) . "..." : "NO DEFINIDO") . "\n";
echo "TELEGRAM_ADMIN_CHAT_ID: " . (defined('TELEGRAM_ADMIN_CHAT_ID') ? TELEGRAM_ADMIN_CHAT_ID : "NO DEFINIDO") . "\n";
echo "</pre>";

if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_ADMIN_CHAT_ID')) {
    echo "<p style='color: red;'><strong>ERROR:</strong> Configuración incompleta</p>";
    exit;
}

// Intentar obtener información del bot
echo "<h2>Información del Bot:</h2>";
$url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getMe";
$response = file_get_contents($url);
$result = json_decode($response, true);
echo "<pre>" . print_r($result, true) . "</pre>";

// Intentar obtener actualizaciones recientes para ver los chats disponibles
echo "<h2>Últimas actualizaciones (para encontrar Chat ID):</h2>";
echo "<p><em>Si has enviado un mensaje al bot o lo has añadido a un canal recientemente, aparecerá aquí:</em></p>";
$url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getUpdates";
$response = file_get_contents($url);
$result = json_decode($response, true);
echo "<pre>" . print_r($result, true) . "</pre>";

// Intentar enviar un mensaje de prueba
echo "<h2>Enviando mensaje de prueba...</h2>";
$botToken = TELEGRAM_BOT_TOKEN;
$chatId = TELEGRAM_ADMIN_CHAT_ID;
$message = "🧪 *Mensaje de Prueba*\n\nSi ves este mensaje, la configuración de Telegram está funcionando correctamente.\n\n⏰ " . date('Y-m-d H:i:s');

$url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
$data = [
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'Markdown'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
        'timeout' => 10
    ]
];

$context  = stream_context_create($options);
$response = @file_get_contents($url, false, $context);
$result = json_decode($response, true);

echo "<pre>";
print_r($result);
echo "</pre>";

if (isset($result['ok']) && $result['ok'] === true) {
    echo "<p style='color: green; font-weight: bold;'>✅ ¡Mensaje enviado correctamente!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Error al enviar mensaje:</p>";
    echo "<pre>" . print_r($result, true) . "</pre>";
}

// Instrucciones
echo "<h2>Cómo obtener el Chat ID de tu canal:</h2>";
echo "<ol>";
echo "<li>Añade el bot <code>@RawDataBot</code> a tu canal de Telegram</li>";
echo "<li>El bot te enviará un mensaje con toda la información, incluyendo el <strong>chat_id</strong></li>";
echo "<li>Copia el <strong>chat_id</strong> (será un número negativo como <code>-1003443178963</code>)</li>";
echo "<li>Actualiza el archivo <code>config/ai_config.php</code> con ese Chat ID</li>";
echo "</ol>";
echo "<p><strong>Alternativamente:</strong> Reenvía cualquier mensaje del canal al bot <code>@userinfobot</code> y te dirá el Chat ID</p>";
?>
