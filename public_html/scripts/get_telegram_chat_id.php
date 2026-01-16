#!/usr/bin/env php
<?php
/**
 * Script para obtener el Chat ID de un canal de Telegram
 * 
 * Uso:
 * 1. Asegúrate de que el bot sea administrador del canal
 * 2. Envía un mensaje al canal
 * 3. Ejecuta este script: php get_telegram_chat_id.php
 */

require_once __DIR__ . '/../config/ai_config.php';

$bot_token = TELEGRAM_BOT_TOKEN;

if (empty($bot_token) || $bot_token === '1208948207:AAF0O45V1zcsp7wjRgbwOrLQ7tNRUHlfnME') {
    echo "⚠️  Usando el token por defecto. Asegúrate de que sea el correcto.\n\n";
}

echo "🔍 Obteniendo actualizaciones del bot...\n\n";

$url = "https://api.telegram.org/bot{$bot_token}/getUpdates";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Error de conexión: {$error}\n";
    exit(1);
}

if ($http_code !== 200) {
    echo "❌ Error HTTP {$http_code}\n";
    echo "Respuesta: {$response}\n";
    exit(1);
}

$result = json_decode($response, true);

if (!$result['ok']) {
    echo "❌ Error de API: " . ($result['description'] ?? 'Desconocido') . "\n";
    exit(1);
}

if (empty($result['result'])) {
    echo "⚠️  No hay mensajes recientes.\n\n";
    echo "📝 Para obtener el Chat ID:\n";
    echo "   1. Asegúrate de que el bot sea administrador del canal @codigoamigocom\n";
    echo "   2. Envía un mensaje al canal (cualquier mensaje)\n";
    echo "   3. Ejecuta este script de nuevo\n\n";
    exit(0);
}

echo "✅ Encontrados " . count($result['result']) . " mensajes\n\n";
echo "📋 Canales/Chats encontrados:\n";
echo str_repeat("=", 80) . "\n\n";

$canales_encontrados = [];

foreach ($result['result'] as $update) {
    $chat = null;
    
    // Buscar en diferentes tipos de actualizaciones
    if (isset($update['message']['chat'])) {
        $chat = $update['message']['chat'];
    } elseif (isset($update['channel_post']['chat'])) {
        $chat = $update['channel_post']['chat'];
    } elseif (isset($update['my_chat_member']['chat'])) {
        $chat = $update['my_chat_member']['chat'];
    }
    
    if ($chat) {
        $chat_id = $chat['id'];
        $chat_type = $chat['type'] ?? 'unknown';
        $chat_title = $chat['title'] ?? $chat['username'] ?? 'Sin título';
        $chat_username = isset($chat['username']) ? '@' . $chat['username'] : 'Sin username';
        
        // Evitar duplicados
        if (!isset($canales_encontrados[$chat_id])) {
            $canales_encontrados[$chat_id] = [
                'id' => $chat_id,
                'type' => $chat_type,
                'title' => $chat_title,
                'username' => $chat_username
            ];
        }
    }
}

if (empty($canales_encontrados)) {
    echo "⚠️  No se encontraron canales en los mensajes recientes.\n";
    exit(0);
}

foreach ($canales_encontrados as $canal) {
    echo "Tipo: {$canal['type']}\n";
    echo "Título: {$canal['title']}\n";
    echo "Username: {$canal['username']}\n";
    echo "Chat ID: {$canal['id']}\n";
    
    if ($canal['type'] === 'channel' && strpos($canal['username'], 'codigoamigo') !== false) {
        echo "\n✨ ¡Este parece ser el canal correcto!\n";
        echo "\n📝 Para configurarlo, edita /home/admin/web/codigoamigo.com/public_html/config/ai_config.php:\n";
        echo "   define('TELEGRAM_CHOLLOS_CHAT_ID_SALIDA', '{$canal['id']}');\n";
    }
    
    echo "\n" . str_repeat("-", 80) . "\n\n";
}

echo "\n✅ Proceso completado\n";
?>




