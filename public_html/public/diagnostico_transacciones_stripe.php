<?php
/**
 * Script de Diagnóstico: Comparar Transacciones Stripe vs MongoDB
 * 
 * Este script compara las transacciones de códigos destacados en Stripe
 * con las registradas en MongoDB para identificar discrepancias.
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

// Claves de Stripe (via helper)
require_once __DIR__ . '/../config/stripe.php';
$stripe_test_key = get_stripe_test_secret_key();
$stripe_live_key = get_stripe_live_secret_key();

// Obtener parámetros
$dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 90;
$modo = isset($_GET['modo']) ? $_GET['modo'] : 'both'; // 'test', 'live', 'both'

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Transacciones Stripe</title>
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
        .stat-box.warning {
            border-left-color: #ffc107;
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
        .badge-warning {
            background: #ffc107;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Transacciones Stripe</h1>
        
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
                    <button type="submit">Analizar</button>
                </div>
            </form>
        </div>

        <?php
        $resultados = [];
        $errores = [];

        // Función para obtener sesiones de Stripe
        function obtenerSesionesStripe($stripe_key, $dias, $modo_nombre) {
            global $errores;
            $sesiones = [];
            
            try {
                $stripe = new \Stripe\StripeClient($stripe_key);
                
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
                            
                            $sesiones[] = [
                                'session_id' => $session->id,
                                'payment_intent' => $session->payment_intent,
                                'amount_total' => $session->amount_total / 100,
                                'currency' => $session->currency,
                                'customer_email' => $session->customer_details->email ?? null,
                                'created' => $session->created,
                                'metadata' => (array)$session->metadata,
                                'modo' => $modo_nombre
                            ];
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

        // Obtener sesiones de Stripe
        $sesiones_stripe = [];
        
        if ($modo === 'test' || $modo === 'both') {
            $sesiones_test = obtenerSesionesStripe($stripe_test_key, $dias, 'Test');
            $sesiones_stripe = array_merge($sesiones_stripe, $sesiones_test);
        }
        
        if ($modo === 'live' || $modo === 'both') {
            $sesiones_live = obtenerSesionesStripe($stripe_live_key, $dias, 'Live');
            $sesiones_stripe = array_merge($sesiones_stripe, $sesiones_live);
        }

        // Obtener transacciones de MongoDB
        $collection_transacciones = getCollectionTransacciones();
        $transacciones_mongo = $collection_transacciones->find([
            'stripe_session_id' => ['$exists' => true, '$ne' => null],
            'tipo' => 'destacado'
        ])->toArray();

        // Crear mapa de session_ids en MongoDB
        $mongo_session_ids = [];
        foreach ($transacciones_mongo as $trans) {
            if (isset($trans['stripe_session_id'])) {
                $mongo_session_ids[$trans['stripe_session_id']] = $trans;
            }
        }

        // Comparar y encontrar faltantes
        $faltantes = [];
        $existentes = [];
        
        foreach ($sesiones_stripe as $sesion) {
            $session_id = $sesion['session_id'];
            if (isset($mongo_session_ids[$session_id])) {
                $existentes[] = $sesion;
            } else {
                $faltantes[] = $sesion;
            }
        }

        // Estadísticas
        $total_stripe = count($sesiones_stripe);
        $total_mongo = count($transacciones_mongo);
        $total_faltantes = count($faltantes);
        $total_existentes = count($existentes);
        ?>

        <div class="stats">
            <div class="stat-box">
                <div class="stat-label">Sesiones en Stripe</div>
                <div class="stat-value"><?php echo $total_stripe; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Transacciones en MongoDB</div>
                <div class="stat-value"><?php echo $total_mongo; ?></div>
            </div>
            <div class="stat-box <?php echo $total_existentes > 0 ? 'success' : ''; ?>">
                <div class="stat-label">Coincidencias</div>
                <div class="stat-value"><?php echo $total_existentes; ?></div>
            </div>
            <div class="stat-box <?php echo $total_faltantes > 0 ? 'danger' : 'success'; ?>">
                <div class="stat-label">Faltantes</div>
                <div class="stat-value"><?php echo $total_faltantes; ?></div>
            </div>
        </div>

        <?php if (!empty($errores)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3>Errores:</h3>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($total_faltantes > 0): ?>
            <h2>⚠️ Transacciones Faltantes en MongoDB (<?php echo $total_faltantes; ?>)</h2>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faltantes as $faltante): ?>
                        <tr>
                            <td><small><?php echo htmlspecialchars(substr($faltante['session_id'], 0, 20)); ?>...</small></td>
                            <td><?php echo date('d/m/Y H:i', $faltante['created']); ?></td>
                            <td><?php echo number_format($faltante['amount_total'], 2); ?> <?php echo strtoupper($faltante['currency']); ?></td>
                            <td><?php echo htmlspecialchars($faltante['customer_email'] ?? 'N/A'); ?></td>
                            <td><small><?php echo htmlspecialchars($faltante['metadata']['usuario_id'] ?? 'N/A'); ?></small></td>
                            <td><small><?php echo htmlspecialchars($faltante['metadata']['codigo_id'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($faltante['metadata']['marca'] ?? 'N/A'); ?></td>
                            <td><span class="badge badge-warning"><?php echo htmlspecialchars($faltante['metadata']['tipo_destacado'] ?? 'N/A'); ?></span></td>
                            <td><span class="badge"><?php echo htmlspecialchars($faltante['modo']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px;">
                <strong>💡 Nota:</strong> Estas transacciones existen en Stripe pero no están registradas en MongoDB. 
                Puedes usar el script de sincronización para importarlas.
            </div>
        <?php else: ?>
            <div style="margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 5px; color: #155724;">
                <strong>✅ Perfecto:</strong> Todas las transacciones de Stripe están registradas en MongoDB.
            </div>
        <?php endif; ?>

        <?php if ($total_existentes > 0 && isset($_GET['mostrar_existentes'])): ?>
            <h2>✅ Transacciones Existentes (<?php echo $total_existentes; ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Session ID</th>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Email Cliente</th>
                        <th>Marca</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($existentes as $existente): ?>
                        <tr>
                            <td><small><?php echo htmlspecialchars(substr($existente['session_id'], 0, 20)); ?>...</small></td>
                            <td><?php echo date('d/m/Y H:i', $existente['created']); ?></td>
                            <td><?php echo number_format($existente['amount_total'], 2); ?> <?php echo strtoupper($existente['currency']); ?></td>
                            <td><?php echo htmlspecialchars($existente['customer_email'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($existente['metadata']['marca'] ?? 'N/A'); ?></td>
                            <td><span class="badge badge-success"><?php echo htmlspecialchars($existente['metadata']['tipo_destacado'] ?? 'N/A'); ?></span></td>
                            <td><span class="badge badge-success">Registrada</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($total_existentes > 0): ?>
            <div style="margin-top: 20px;">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['mostrar_existentes' => 1])); ?>" 
                   style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
                    Mostrar Transacciones Existentes
                </a>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>

