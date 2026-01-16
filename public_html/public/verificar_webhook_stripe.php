<?php
/**
 * Script de Verificación: Verificar Configuración del Webhook de Stripe
 * 
 * Este script verifica la configuración del webhook y muestra información
 * sobre su estado y los últimos eventos procesados.
 */

session_start();

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';

// Obtener configuración del webhook
$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? 'whsec_TU_WEBHOOK_SECRET_OBTENIDO_DEL_DASHBOARD';
$webhook_url = 'https://www.codigoamigo.com/webhook_stripe.php';

// Función para leer los últimos logs del webhook
function obtenerUltimosLogsWebhook($limite = 50) {
    $logs = [];
    
    // Intentar leer desde diferentes ubicaciones comunes de logs
    $posibles_rutas = [
        '/var/log/apache2/error.log',
        '/var/log/php_errors.log',
        '/var/log/error_log',
        __DIR__ . '/../logs/webhook.log'
    ];
    
    foreach ($posibles_rutas as $ruta) {
        if (file_exists($ruta) && is_readable($ruta)) {
            $comando = "tail -n $limite '$ruta' | grep -i 'WEBHOOK'";
            $output = shell_exec($comando);
            
            if ($output) {
                $lineas = explode("\n", trim($output));
                foreach ($lineas as $linea) {
                    if (!empty(trim($linea))) {
                        $logs[] = [
                            'ruta' => $ruta,
                            'linea' => trim($linea)
                        ];
                    }
                }
            }
        }
    }
    
    // Si no encontramos logs en archivos, intentar desde error_log de PHP
    // Nota: Esto requiere que error_log esté configurado correctamente
    
    return array_slice($logs, -$limite); // Devolver solo los últimos
}

// Función para verificar si el archivo del webhook existe y es accesible
function verificarArchivoWebhook() {
    $webhook_path = __DIR__ . '/webhook_stripe.php';
    $resultado = [
        'existe' => file_exists($webhook_path),
        'legible' => is_readable($webhook_path),
        'ejecutable' => is_executable($webhook_path),
        'tamaño' => file_exists($webhook_path) ? filesize($webhook_path) : 0,
        'ultima_modificacion' => file_exists($webhook_path) ? date('Y-m-d H:i:s', filemtime($webhook_path)) : null
    ];
    
    return $resultado;
}

// Función para verificar la configuración del endpoint secret
function verificarEndpointSecret() {
    global $endpoint_secret;
    
    $resultado = [
        'configurado' => !empty($endpoint_secret) && $endpoint_secret !== 'whsec_TU_WEBHOOK_SECRET_OBTENIDO_DEL_DASHBOARD',
        'tiene_valor_default' => $endpoint_secret === 'whsec_TU_WEBHOOK_SECRET_OBTENIDO_DEL_DASHBOARD',
        'longitud' => strlen($endpoint_secret),
        'prefijo_correcto' => strpos($endpoint_secret, 'whsec_') === 0,
        'tiene_variable_env' => isset($_ENV['STRIPE_WEBHOOK_SECRET'])
    ];
    
    return $resultado;
}

// Obtener estadísticas de transacciones recientes
function obtenerEstadisticasTransacciones() {
    $collection_transacciones = getCollectionTransacciones();
    
    // Transacciones de los últimos 7 días
    $fecha_7_dias = new MongoDB\BSON\UTCDateTime((time() - 7*24*60*60) * 1000);
    
    $total_recientes = $collection_transacciones->countDocuments([
        'fecha' => ['$gte' => $fecha_7_dias],
        'tipo' => 'destacado',
        'stripe_session_id' => ['$exists' => true, '$ne' => null]
    ]);
    
    // Transacciones registradas por webhook (sin sincronización manual)
    $por_webhook = $collection_transacciones->countDocuments([
        'fecha' => ['$gte' => $fecha_7_dias],
        'tipo' => 'destacado',
        'stripe_session_id' => ['$exists' => true, '$ne' => null],
        'sincronizado_manual' => ['$ne' => true]
    ]);
    
    // Transacciones sincronizadas manualmente
    $sincronizadas = $collection_transacciones->countDocuments([
        'fecha' => ['$gte' => $fecha_7_dias],
        'tipo' => 'destacado',
        'stripe_session_id' => ['$exists' => true, '$ne' => null],
        'sincronizado_manual' => true
    ]);
    
    return [
        'total_recientes' => $total_recientes,
        'por_webhook' => $por_webhook,
        'sincronizadas' => $sincronizadas
    ];
}

$info_archivo = verificarArchivoWebhook();
$info_secret = verificarEndpointSecret();
$estadisticas = obtenerEstadisticasTransacciones();
$logs = obtenerUltimosLogsWebhook(20);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Webhook Stripe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .section h2 {
            margin-top: 0;
            color: #007bff;
        }
        .status-box {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            margin: 5px;
        }
        .status-ok {
            background: #d4edda;
            color: #155724;
        }
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .info-table th,
        .info-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .info-table th {
            background: #007bff;
            color: white;
        }
        .log-entry {
            padding: 8px;
            margin: 5px 0;
            background: #fff;
            border-left: 4px solid #007bff;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
        }
        .log-entry.error {
            border-left-color: #dc3545;
        }
        .log-entry.warning {
            border-left-color: #ffc107;
        }
        .log-entry.success {
            border-left-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificación del Webhook de Stripe</h1>
        
        <div class="section">
            <h2>📁 Archivo del Webhook</h2>
            <table class="info-table">
                <tr>
                    <th>Propiedad</th>
                    <th>Valor</th>
                    <th>Estado</th>
                </tr>
                <tr>
                    <td>Ruta del archivo</td>
                    <td><code>public/webhook_stripe.php</code></td>
                    <td>
                        <?php if ($info_archivo['existe']): ?>
                            <span class="status-box status-ok">✓ Existe</span>
                        <?php else: ?>
                            <span class="status-box status-error">✗ No existe</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Legible</td>
                    <td><?php echo $info_archivo['legible'] ? 'Sí' : 'No'; ?></td>
                    <td>
                        <?php if ($info_archivo['legible']): ?>
                            <span class="status-box status-ok">✓ OK</span>
                        <?php else: ?>
                            <span class="status-box status-error">✗ Error</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Tamaño</td>
                    <td><?php echo number_format($info_archivo['tamaño']); ?> bytes</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>Última modificación</td>
                    <td><?php echo $info_archivo['ultima_modificacion'] ?? 'N/A'; ?></td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>URL del webhook</td>
                    <td><code><?php echo htmlspecialchars($webhook_url); ?></code></td>
                    <td>
                        <span class="status-box status-ok">Configurado</span>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2>🔐 Configuración del Endpoint Secret</h2>
            <table class="info-table">
                <tr>
                    <th>Propiedad</th>
                    <th>Valor</th>
                    <th>Estado</th>
                </tr>
                <tr>
                    <td>Configurado</td>
                    <td><?php echo $info_secret['configurado'] ? 'Sí' : 'No'; ?></td>
                    <td>
                        <?php if ($info_secret['configurado']): ?>
                            <span class="status-box status-ok">✓ OK</span>
                        <?php else: ?>
                            <span class="status-box status-error">✗ No configurado</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Tiene valor por defecto</td>
                    <td><?php echo $info_secret['tiene_valor_default'] ? 'Sí' : 'No'; ?></td>
                    <td>
                        <?php if ($info_secret['tiene_valor_default']): ?>
                            <span class="status-box status-warning">⚠ Usar valor real</span>
                        <?php else: ?>
                            <span class="status-box status-ok">✓ OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Prefijo correcto (whsec_)</td>
                    <td><?php echo $info_secret['prefijo_correcto'] ? 'Sí' : 'No'; ?></td>
                    <td>
                        <?php if ($info_secret['prefijo_correcto']): ?>
                            <span class="status-box status-ok">✓ OK</span>
                        <?php else: ?>
                            <span class="status-box status-error">✗ Formato incorrecto</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Longitud</td>
                    <td><?php echo $info_secret['longitud']; ?> caracteres</td>
                    <td>
                        <?php if ($info_secret['longitud'] > 20): ?>
                            <span class="status-box status-ok">✓ OK</span>
                        <?php else: ?>
                            <span class="status-box status-warning">⚠ Corto</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Usa variable de entorno</td>
                    <td><?php echo $info_secret['tiene_variable_env'] ? 'Sí' : 'No'; ?></td>
                    <td>
                        <?php if ($info_secret['tiene_variable_env']): ?>
                            <span class="status-box status-ok">✓ Recomendado</span>
                        <?php else: ?>
                            <span class="status-box status-warning">⚠ Configurar en código</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <?php if (!$info_secret['configurado'] || $info_secret['tiene_valor_default']): ?>
                <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 5px; color: #856404;">
                    <strong>⚠️ Acción requerida:</strong> Debes configurar el endpoint secret del webhook en Stripe Dashboard.
                    <ol style="margin-top: 10px;">
                        <li>Ve a <a href="https://dashboard.stripe.com/webhooks" target="_blank">Stripe Dashboard > Webhooks</a></li>
                        <li>Selecciona tu webhook o créalo si no existe</li>
                        <li>Copia el "Signing secret" (comienza con <code>whsec_</code>)</li>
                        <li>Configúralo como variable de entorno <code>STRIPE_WEBHOOK_SECRET</code> o edita el archivo <code>webhook_stripe.php</code></li>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>📊 Estadísticas de Transacciones (Últimos 7 días)</h2>
            <table class="info-table">
                <tr>
                    <th>Métrica</th>
                    <th>Valor</th>
                </tr>
                <tr>
                    <td>Total transacciones destacadas</td>
                    <td><strong><?php echo $estadisticas['total_recientes']; ?></strong></td>
                </tr>
                <tr>
                    <td>Registradas por webhook</td>
                    <td><strong><?php echo $estadisticas['por_webhook']; ?></strong></td>
                </tr>
                <tr>
                    <td>Sincronizadas manualmente</td>
                    <td><strong><?php echo $estadisticas['sincronizadas']; ?></strong></td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2>📝 Últimos Logs del Webhook</h2>
            <?php if (empty($logs)): ?>
                <p>No se encontraron logs recientes del webhook. Esto puede significar:</p>
                <ul>
                    <li>El webhook no ha recibido eventos recientemente</li>
                    <li>Los logs están en una ubicación diferente</li>
                    <li>El logging no está configurado correctamente</li>
                </ul>
            <?php else: ?>
                <p>Mostrando los últimos <?php echo count($logs); ?> logs relacionados con el webhook:</p>
                <?php foreach (array_reverse($logs) as $log): ?>
                    <?php
                    $clase = 'log-entry';
                    if (stripos($log['linea'], 'ERROR') !== false) {
                        $clase .= ' error';
                    } elseif (stripos($log['linea'], 'WARNING') !== false) {
                        $clase .= ' warning';
                    } elseif (stripos($log['linea'], 'success') !== false || stripos($log['linea'], 'registrada') !== false) {
                        $clase .= ' success';
                    }
                    ?>
                    <div class="<?php echo $clase; ?>">
                        <strong>[<?php echo htmlspecialchars($log['ruta']); ?>]</strong><br>
                        <?php echo htmlspecialchars($log['linea']); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>🔗 Enlaces Útiles</h2>
            <ul>
                <li><a href="diagnostico_transacciones_stripe.php" target="_blank">Diagnóstico de Transacciones</a></li>
                <li><a href="sync_stripe_transactions.php" target="_blank">Sincronizar Transacciones</a></li>
                <li><a href="https://dashboard.stripe.com/webhooks" target="_blank">Stripe Dashboard - Webhooks</a></li>
                <li><a href="https://dashboard.stripe.com/test/webhooks" target="_blank">Stripe Dashboard - Webhooks (Test)</a></li>
            </ul>
        </div>
    </div>
</body>
</html>

