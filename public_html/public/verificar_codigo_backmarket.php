<?php
/**
 * Script para verificar el estado del código de backmarket de pedro perez
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

include_once __DIR__ . '/../inc/includes.php';

// ID del código de backmarket de pedro perez
$codigo_id = "65104055c051cf23770ec914";
$usuario_id = "6229e7c3"; // pedro perez

$collection_codigos = getCollectionCodigos();
$collection_transacciones = getCollectionTransacciones();
$collection_usuarios = getCollectionUsuarios();

// Buscar el código
$codigo = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);

// Buscar transacciones relacionadas
$transacciones = $collection_transacciones->find([
    'codigo_id' => $codigo_id,
    'tipo' => 'destacado'
])->toArray();

// Buscar usuario
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario_id)]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Código Backmarket</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .info-box { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .info-box.warning { border-left-color: #ffc107; }
        .info-box.error { border-left-color: #dc3545; }
        .info-box.success { border-left-color: #28a745; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .badge { padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
    </style>
</head>
<body>
    <h1>Verificación del Código Backmarket - Pedro Perez</h1>
    
    <?php if ($codigo): ?>
        <div class="info-box">
            <h2>Estado del Código</h2>
            <table>
                <tr><th>Campo</th><th>Valor</th><th>Estado</th></tr>
                <tr>
                    <td>ID Código</td>
                    <td><?php echo $codigo_id; ?></td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>Marca</td>
                    <td><?php echo htmlspecialchars($codigo['marca'] ?? 'N/A'); ?></td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>Usuario</td>
                    <td><?php echo htmlspecialchars($usuario['username'] ?? 'N/A'); ?> (<?php echo $usuario_id; ?>)</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td><strong>destacado</strong></td>
                    <td><?php 
                        $destacado = $codigo['destacado'] ?? 0;
                        echo $destacado ? ($destacado === true ? 'true' : date('d/m/Y H:i', is_numeric($destacado) ? $destacado : 0)) : '0/false';
                    ?></td>
                    <td>
                        <?php if ($destacado && $destacado !== 0): ?>
                            <span class="badge badge-success">✓ Establecido</span>
                        <?php else: ?>
                            <span class="badge badge-danger">✗ No establecido</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>destacado_social</strong></td>
                    <td><?php 
                        $destacado_social = $codigo['destacado_social'] ?? 0;
                        echo $destacado_social ? date('d/m/Y H:i', is_numeric($destacado_social) ? $destacado_social : 0) : '0';
                    ?></td>
                    <td>
                        <?php if ($destacado_social && $destacado_social > 0): ?>
                            <span class="badge badge-success">✓ Establecido</span>
                        <?php else: ?>
                            <span class="badge badge-danger">✗ No establecido (PROBLEMA)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>tipo_destacado</strong></td>
                    <td><?php echo htmlspecialchars($codigo['tipo_destacado'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if (($codigo['tipo_destacado'] ?? '') === 'super'): ?>
                            <span class="badge badge-warning">⚠ Debería tener destacado_social</span>
                        <?php else: ?>
                            <span class="badge">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>fecha_destacado</td>
                    <td><?php 
                        if (isset($codigo['fecha_destacado'])) {
                            if ($codigo['fecha_destacado'] instanceof MongoDB\BSON\UTCDateTime) {
                                echo date('d/m/Y H:i', $codigo['fecha_destacado']->toDateTime()->getTimestamp());
                            } else {
                                echo htmlspecialchars($codigo['fecha_destacado']);
                            }
                        } else {
                            echo 'N/A';
                        }
                    ?></td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>fecha_fin_destacado</td>
                    <td><?php 
                        if (isset($codigo['fecha_fin_destacado'])) {
                            if ($codigo['fecha_fin_destacado'] instanceof MongoDB\BSON\UTCDateTime) {
                                echo date('d/m/Y H:i', $codigo['fecha_fin_destacado']->toDateTime()->getTimestamp());
                            } else {
                                echo htmlspecialchars($codigo['fecha_fin_destacado']);
                            }
                        } else {
                            echo 'N/A';
                        }
                    ?></td>
                    <td>-</td>
                </tr>
            </table>
        </div>
        
        <?php 
        $problema = false;
        if (($codigo['tipo_destacado'] ?? '') === 'super' && (!isset($codigo['destacado_social']) || $codigo['destacado_social'] == 0)) {
            $problema = true;
        }
        ?>
        
        <?php if ($problema): ?>
            <div class="info-box error">
                <h3>⚠️ PROBLEMA DETECTADO</h3>
                <p>El código tiene <strong>tipo_destacado = 'super'</strong> pero <strong>destacado_social</strong> no está establecido.</p>
                <p>Esto hace que no aparezca primero en la lista porque el ordenamiento prioriza <code>destacado_social</code>.</p>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="info-box error">
            <h3>❌ Código no encontrado</h3>
            <p>No se encontró el código con ID: <?php echo htmlspecialchars($codigo_id); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="info-box">
        <h2>Transacciones Relacionadas</h2>
        <?php if (count($transacciones) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Session ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transacciones as $trans): ?>
                        <tr>
                            <td><?php 
                                if (isset($trans['fecha']) && $trans['fecha'] instanceof MongoDB\BSON\UTCDateTime) {
                                    echo date('d/m/Y H:i', $trans['fecha']->toDateTime()->getTimestamp());
                                } else {
                                    echo 'N/A';
                                }
                            ?></td>
                            <td><?php echo htmlspecialchars($trans['tipo_destacado'] ?? 'N/A'); ?></td>
                            <td>€<?php echo number_format($trans['cantidad'], 2); ?></td>
                            <td><?php echo htmlspecialchars($trans['metodo_pago'] ?? 'N/A'); ?></td>
                            <td><span class="badge badge-success"><?php echo htmlspecialchars($trans['estado'] ?? 'N/A'); ?></span></td>
                            <td><small><?php echo htmlspecialchars(substr($trans['stripe_session_id'] ?? 'N/A', 0, 30)); ?>...</small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron transacciones para este código.</p>
        <?php endif; ?>
    </div>
    
    <?php if ($problema): ?>
        <div class="info-box warning">
            <h3>🔧 Solución</h3>
            <p>Se necesita corregir el código para establecer <code>destacado_social</code> con un timestamp.</p>
            <p>Esto se hará automáticamente al corregir los archivos del sistema.</p>
        </div>
    <?php endif; ?>
</body>
</html>

