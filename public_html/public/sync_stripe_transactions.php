<?php
/**
 * Script de Sincronización: Importar Transacciones Faltantes desde Stripe
 * 
 * Este script sincroniza las transacciones de códigos destacados desde Stripe
 * hacia MongoDB, insertando solo las que no existen.
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

// Claves de Stripe
$stripe_test_key = "sk_test_ML0vGPIQHfl4iQYVHeflQTZt";
$stripe_live_key = "sk_live_dfMwJTC7REoMy76Bp2PzVoZV00U5KaNCcv";

// Obtener parámetros
$dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 90;
$modo = isset($_GET['modo']) ? $_GET['modo'] : 'both'; // 'test', 'live', 'both'
$confirmar = isset($_GET['confirmar']) && $_GET['confirmar'] === '1';

// Función para obtener sesiones faltantes de Stripe
function obtenerSesionesFaltantes($stripe_key, $dias, $modo_nombre) {
    global $errores;
    $sesiones = [];
    
    try {
        $stripe = new \Stripe\StripeClient($stripe_key);
        $collection_transacciones = getCollectionTransacciones();
        
        // Obtener todas las session_ids existentes en MongoDB
        $transacciones_existentes = $collection_transacciones->find([
            'stripe_session_id' => ['$exists' => true, '$ne' => null]
        ], [
            'projection' => ['stripe_session_id' => 1]
        ])->toArray();
        
        $session_ids_existentes = [];
        foreach ($transacciones_existentes as $trans) {
            if (isset($trans['stripe_session_id'])) {
                $session_ids_existentes[$trans['stripe_session_id']] = true;
            }
        }
        
        // Calcular fecha límite
        $fecha_limite = time() - ($dias * 24 * 60 * 60);
        
        // Obtener todas las sesiones de checkout
        $has_more = true;
        $starting_after = null;
        $total_obtenidas = 0;
        
        while ($has_more && $total_obtenidas < 1000) { // Límite de seguridad
            $params = [
                'limit' => 100,
            ];
            
            if ($starting_after) {
                $params['starting_after'] = $starting_after;
            }
            
            $sessions = $stripe->checkout->sessions->all($params);
            
            foreach ($sessions->data as $session) {
                // Filtrar por fecha
                if ($session->created < $fecha_limite) {
                    $has_more = false;
                    break;
                }
                
                // Solo sesiones completadas con metadata tipo destacar_codigo
                if ($session->payment_status === 'paid' && 
                    isset($session->metadata) && 
                    isset($session->metadata->tipo) && 
                    $session->metadata->tipo === 'destacar_codigo') {
                    
                    // Solo agregar si no existe en MongoDB
                    if (!isset($session_ids_existentes[$session->id])) {
                        $sesiones[] = [
                            'session_id' => $session->id,
                            'payment_intent' => $session->payment_intent,
                            'amount_total' => $session->amount_total / 100,
                            'currency' => $session->currency,
                            'customer_email' => $session->customer_details->email ?? null,
                            'created' => $session->created,
                            'metadata' => (array)$session->metadata,
                            'payment_status' => $session->payment_status,
                            'modo' => $modo_nombre
                        ];
                    }
                }
            }
            
            $has_more = $sessions->has_more;
            if ($has_more && count($sessions->data) > 0) {
                $starting_after = end($sessions->data)->id;
            }
            
            $total_obtenidas += count($sessions->data);
        }
        
    } catch (Exception $e) {
        $errores[] = "Error obteniendo sesiones de Stripe ($modo_nombre): " . $e->getMessage();
    }
    
    return $sesiones;
}

// Función para insertar transacción en MongoDB
function insertarTransaccion($sesion) {
    global $errores, $insertadas, $errores_insertar;
    
    try {
        $collection_transacciones = getCollectionTransacciones();
        
        // Verificar nuevamente que no existe (doble verificación)
        $existe = $collection_transacciones->findOne(['stripe_session_id' => $sesion['session_id']]);
        if ($existe) {
            return ['success' => false, 'message' => 'Ya existe en MongoDB'];
        }
        
        // Validar que tenemos los metadata necesarios
        if (!isset($sesion['metadata']['usuario_id']) || 
            !isset($sesion['metadata']['codigo_id']) || 
            !isset($sesion['metadata']['tipo_destacado'])) {
            return ['success' => false, 'message' => 'Metadata incompleto'];
        }
        
        // Crear la transacción con el mismo formato que el webhook
        $transaccion = [
            'usuario_id' => $sesion['metadata']['usuario_id'],
            'tipo' => 'destacado',
            'subtipo' => $sesion['metadata']['tipo_destacado'], // normal o super
            'cantidad' => $sesion['amount_total'], // Ya está en euros
            'descripcion' => "Destacado de código - Marca: " . ($sesion['metadata']['marca'] ?? 'N/A') . " - Tipo: " . $sesion['metadata']['tipo_destacado'],
            'fecha' => new MongoDB\BSON\UTCDateTime($sesion['created'] * 1000),
            'estado' => 'completada',
            'codigo_id' => $sesion['metadata']['codigo_id'],
            'marca' => $sesion['metadata']['marca'] ?? '',
            'tipo_destacado' => $sesion['metadata']['tipo_destacado'],
            // Datos de Stripe
            'stripe_session_id' => $sesion['session_id'],
            'stripe_payment_intent' => $sesion['payment_intent'],
            'stripe_customer_email' => $sesion['customer_email'],
            'stripe_payment_status' => $sesion['payment_status'],
            'metodo_pago' => 'tarjeta',
            'sincronizado_manual' => true, // Marca que fue sincronizado manualmente
            'fecha_sincronizacion' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $resultado = $collection_transacciones->insertOne($transaccion);
        
        if ($resultado->getInsertedCount() > 0) {
            $insertadas++;
            return ['success' => true, 'message' => 'Insertada correctamente', 'id' => (string)$resultado->getInsertedId()];
        } else {
            return ['success' => false, 'message' => 'Error al insertar'];
        }
        
    } catch (Exception $e) {
        $errores_insertar++;
        $errores[] = "Error insertando transacción {$sesion['session_id']}: " . $e->getMessage();
        return ['success' => false, 'message' => 'Excepción: ' . $e->getMessage()];
    }
}

$errores = [];
$insertadas = 0;
$errores_insertar = 0;
$sesiones_faltantes = [];

// Obtener sesiones faltantes
if ($modo === 'test' || $modo === 'both') {
    $sesiones_test = obtenerSesionesFaltantes($stripe_test_key, $dias, 'Test');
    $sesiones_faltantes = array_merge($sesiones_faltantes, $sesiones_test);
}

if ($modo === 'live' || $modo === 'both') {
    $sesiones_live = obtenerSesionesFaltantes($stripe_live_key, $dias, 'Live');
    $sesiones_faltantes = array_merge($sesiones_faltantes, $sesiones_live);
}

$resultados_insertar = [];

// Si se confirmó, proceder con la inserción
if ($confirmar && count($sesiones_faltantes) > 0) {
    foreach ($sesiones_faltantes as $sesion) {
        $resultado = insertarTransaccion($sesion);
        $resultados_insertar[] = [
            'sesion' => $sesion,
            'resultado' => $resultado
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronizar Transacciones Stripe</title>
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
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .stat-box.success {
            border-left-color: #28a745;
        }
        .stat-box.danger {
            border-left-color: #dc3545;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filters form {
            display: flex;
            gap: 15px;
            align-items: end;
        }
        .filters label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .filters input, .filters select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filters button {
            padding: 8px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filters button:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #007bff;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Sincronizar Transacciones Stripe</h1>
        
        <div class="filters">
            <form method="GET">
                <div>
                    <label>Días a revisar:</label>
                    <input type="number" name="dias" value="<?php echo $dias; ?>" min="1" max="365">
                </div>
                <div>
                    <label>Modo Stripe:</label>
                    <select name="modo">
                        <option value="both" <?php echo $modo === 'both' ? 'selected' : ''; ?>>Test + Live</option>
                        <option value="test" <?php echo $modo === 'test' ? 'selected' : ''; ?>>Solo Test</option>
                        <option value="live" <?php echo $modo === 'live' ? 'selected' : ''; ?>>Solo Live</option>
                    </select>
                </div>
                <div>
                    <button type="submit">Buscar Faltantes</button>
                </div>
            </form>
        </div>

        <?php if (count($sesiones_faltantes) > 0): ?>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-label">Transacciones Faltantes</div>
                    <div class="stat-value"><?php echo count($sesiones_faltantes); ?></div>
                </div>
                <?php if ($confirmar): ?>
                    <div class="stat-box <?php echo $insertadas > 0 ? 'success' : ''; ?>">
                        <div class="stat-label">Insertadas</div>
                        <div class="stat-value"><?php echo $insertadas; ?></div>
                    </div>
                    <div class="stat-box <?php echo $errores_insertar > 0 ? 'danger' : ''; ?>">
                        <div class="stat-label">Errores</div>
                        <div class="stat-value"><?php echo $errores_insertar; ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$confirmar): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Atención:</strong> Se encontraron <?php echo count($sesiones_faltantes); ?> transacciones faltantes.
                    Haz clic en el botón de abajo para sincronizarlas.
                </div>
                
                <div style="margin: 20px 0;">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['confirmar' => '1'])); ?>" 
                       class="btn-danger" 
                       style="padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; display: inline-block;"
                       onclick="return confirm('¿Estás seguro de que quieres sincronizar <?php echo count($sesiones_faltantes); ?> transacciones?');">
                        🔄 Sincronizar <?php echo count($sesiones_faltantes); ?> Transacciones
                    </a>
                </div>
            <?php else: ?>
                <?php if ($insertadas > 0): ?>
                    <div class="alert alert-success">
                        <strong>✅ Éxito:</strong> Se insertaron <?php echo $insertadas; ?> transacciones correctamente.
                    </div>
                <?php endif; ?>
                
                <?php if ($errores_insertar > 0): ?>
                    <div class="alert alert-danger">
                        <strong>❌ Error:</strong> Hubo <?php echo $errores_insertar; ?> errores al insertar transacciones.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <h2>Transacciones a Sincronizar</h2>
            <table>
                <thead>
                    <tr>
                        <th>Session ID</th>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Email Cliente</th>
                        <th>Usuario ID</th>
                        <th>Código ID</th>
                        <th>Marca</th>
                        <th>Tipo</th>
                        <th>Modo</th>
                        <?php if ($confirmar): ?>
                            <th>Resultado</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sesiones_mostrar = $confirmar ? $resultados_insertar : array_map(function($s) { return ['sesion' => $s, 'resultado' => null]; }, $sesiones_faltantes);
                    foreach ($sesiones_mostrar as $item): 
                        $sesion = $item['sesion'];
                        $resultado = $item['resultado'] ?? null;
                    ?>
                        <tr>
                            <td><small><?php echo htmlspecialchars(substr($sesion['session_id'], 0, 20)); ?>...</small></td>
                            <td><?php echo date('d/m/Y H:i', $sesion['created']); ?></td>
                            <td><?php echo number_format($sesion['amount_total'], 2); ?> <?php echo strtoupper($sesion['currency']); ?></td>
                            <td><?php echo htmlspecialchars($sesion['customer_email'] ?? 'N/A'); ?></td>
                            <td><small><?php echo htmlspecialchars($sesion['metadata']['usuario_id'] ?? 'N/A'); ?></small></td>
                            <td><small><?php echo htmlspecialchars($sesion['metadata']['codigo_id'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($sesion['metadata']['marca'] ?? 'N/A'); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($sesion['metadata']['tipo_destacado'] ?? 'N/A'); ?></span></td>
                            <td><span class="badge"><?php echo htmlspecialchars($sesion['modo']); ?></span></td>
                            <?php if ($confirmar && $resultado): ?>
                                <td>
                                    <?php if ($resultado['success']): ?>
                                        <span class="badge badge-success">✅ <?php echo htmlspecialchars($resultado['message']); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">❌ <?php echo htmlspecialchars($resultado['message']); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>✅ Perfecto:</strong> No se encontraron transacciones faltantes. Todo está sincronizado.
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <h3>Errores:</h3>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="diagnostico_transacciones_stripe.php" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;">
                ← Volver al Diagnóstico
            </a>
        </div>
    </div>
</body>
</html>

