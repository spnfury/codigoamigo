<?php
/**
 * Script de ayuda para configurar el webhook de Telegram para chollos
 * 
 * Acceso: https://www.codigoamigo.com/public/configurar_webhook_telegram.php
 */

session_start();

// Verificar permisos de administrador
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702", //thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", //edna
    "639899bc6321ee0d0e4010d2", //aron
    "5c8a10ce2f55c86d6e707d82"  //jose
];

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    die("Acceso denegado");
}

$bot_token = '1208948207:AAF0O45V1zcsp7wjRgbwOrLQ7tNRUHlfnME';
$webhook_url = 'https://www.codigoamigo.com/webhook/telegram-chollos';
$resultado = '';

if ($_POST && isset($_POST['configurar'])) {
    $url = "https://api.telegram.org/bot" . $bot_token . "/setWebhook";
    $data = ['url' => $webhook_url];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $resultado = json_decode($response, true);
}

// Obtener información del webhook actual
$url_info = "https://api.telegram.org/bot" . $bot_token . "/getWebhookInfo";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_info);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$info_response = curl_exec($ch);
curl_close($ch);
$webhook_info = json_decode($info_response, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Webhook Telegram - Chollos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4>Configurar Webhook de Telegram para Chollos</h4>
                    </div>
                    <div class="card-body">
                        <h5>Información del Webhook Actual</h5>
                        <?php if ($webhook_info && $webhook_info['ok']): ?>
                            <div class="alert alert-info">
                                <strong>URL actual:</strong> <?php echo htmlspecialchars($webhook_info['result']['url'] ?? 'No configurado'); ?><br>
                                <strong>Última actualización:</strong> <?php echo isset($webhook_info['result']['last_error_date']) ? date('Y-m-d H:i:s', $webhook_info['result']['last_error_date']) : 'N/A'; ?><br>
                                <?php if (isset($webhook_info['result']['last_error_message'])): ?>
                                    <strong>Último error:</strong> <span class="text-danger"><?php echo htmlspecialchars($webhook_info['result']['last_error_message']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <h5 class="mt-4">Configurar Nuevo Webhook</h5>
                        <p>URL del webhook: <code><?php echo htmlspecialchars($webhook_url); ?></code></p>
                        
                        <form method="POST">
                            <button type="submit" name="configurar" class="btn btn-primary">
                                Configurar Webhook
                            </button>
                        </form>
                        
                        <?php if ($resultado): ?>
                            <div class="alert alert-<?php echo $resultado['ok'] ? 'success' : 'danger'; ?> mt-3">
                                <?php if ($resultado['ok']): ?>
                                    <strong>✓ Webhook configurado correctamente</strong>
                                <?php else: ?>
                                    <strong>✗ Error:</strong> <?php echo htmlspecialchars($resultado['description'] ?? 'Error desconocido'); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <h5>Instrucciones:</h5>
                        <ol>
                            <li>El bot debe estar añadido como administrador al canal de entrada (donde recibirás los chollos)</li>
                            <li>El bot debe estar añadido como administrador al canal de salida (donde se publicarán los chollos)</li>
                            <li>Configura los Chat IDs en <code>config/ai_config.php</code>:
                                <ul>
                                    <li><code>TELEGRAM_CHOLLOS_CHAT_ID_ENTRADA</code> - Canal de donde se importan chollos</li>
                                    <li><code>TELEGRAM_CHOLLOS_CHAT_ID_SALIDA</code> - Canal donde se publican chollos</li>
                                </ul>
                            </li>
                            <li>Para obtener los Chat IDs:
                                <ul>
                                    <li>Envía un mensaje al canal</li>
                                    <li>Visita: <code>https://api.telegram.org/bot<?php echo $bot_token; ?>/getUpdates</code></li>
                                    <li>Busca el <code>chat.id</code> en la respuesta (será un número negativo)</li>
                                </ul>
                            </li>
                            <li>Haz clic en "Configurar Webhook" arriba</li>
                        </ol>
                        
                        <div class="mt-4">
                            <a href="admin_chollos.php" class="btn btn-secondary">Volver a Gestión de Chollos</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


