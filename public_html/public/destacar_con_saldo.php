<?php
session_start();
require_once '../app_with_mongo.php';
include_once __DIR__ . '/../myphp/funciones.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: https://www.codigoamigo.com/login");
    exit;
}

$codigo_id = $_GET['codigo'] ?? '';
$tipo = $_GET['tipo'] ?? 'individual'; // valores antiguos: individual|splash. También puede venir 'normal'|'super'

// Permitir que 'tipo' reciba directamente el nivel de destacado
$tipo_destacado = 'normal';
if ($tipo === 'super' || $tipo === 'normal') {
    $tipo_destacado = $tipo;
    $tipo = 'individual';
} else {
    // Alternativamente aceptar 'tipo_destacado' explícito
    if (isset($_GET['tipo_destacado']) && in_array($_GET['tipo_destacado'], ['normal','super'])) {
        $tipo_destacado = $_GET['tipo_destacado'];
    }
}

if (empty($codigo_id)) {
    header("Location: https://www.codigoamigo.com/mis-anuncios");
    exit;
}

$user_id = $_SESSION["user_id"];
$costo_destacado = 9.99;

// Verificar saldo del usuario
$saldo_actual = isset($data_usuario['saldo']) ? $data_usuario['saldo'] : 0;

if ($saldo_actual < $costo_destacado) {
    $_SESSION['msg_error'] = "No tienes suficiente saldo. Necesitas {$costo_destacado}€ para destacar un código.";
    header("Location: https://www.codigoamigo.com/mis-anuncios");
    exit;
}

try {
    if ($tipo === 'splash') {
        // Destacar todos los códigos del usuario
        $collection_codigos = getCollectionCodigos();
        
        // Preparar datos de actualización
        $update_data = [
            'destacado' => time(),
            'fecha_destacado' => date('Y-m-d H:i:s'),
            'tipo_destacado' => $tipo_destacado
        ];
        
        // Para destacado "super", establecer también destacado_social (aparece en home y tiene prioridad)
        if ($tipo_destacado === 'super') {
            $update_data['destacado_social'] = time();
        }
        
        $collection_codigos->updateMany(
            ['id_usuario' => new MongoDB\BSON\ObjectId($user_id), 'estado' => 0],
            ['$set' => $update_data]
        );
        
        // Obtener saldo anterior
        $saldo_anterior = $saldo_actual;
        
        // Descontar el saldo
        $nuevo_saldo = $saldo_actual - $costo_destacado;
        $collection_usuarios = getCollectionUsuarios();
        $collection_usuarios->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($user_id)],
            ['$set' => ['saldo' => $nuevo_saldo]]
        );
        
        // Registrar la transacción
        $transaccion = [
            'usuario_id' => $user_id,
            'tipo' => 'destacado_splash',
            'cantidad' => -$costo_destacado,
            'descripcion' => 'Destacado splash de todos los códigos',
            'fecha' => new MongoDB\BSON\UTCDateTime(),
            'estado' => 'completada',
            'saldo_anterior' => $saldo_anterior,
            'saldo_nuevo' => $nuevo_saldo
        ];
        
        $collection_transacciones = getCollectionTransacciones();
        $collection_transacciones->insertOne($transaccion);
        
        $_SESSION['msg_success'] = "¡Todos tus códigos han sido destacados exitosamente!";

        // Enviar notificaciones por cada código destacado del usuario
        $codigos_usuario = $collection_codigos->find(['id_usuario' => new MongoDB\BSON\ObjectId($user_id), 'estado' => 0]);
        foreach ($codigos_usuario as $cod) {
            destacar_codigo_moderno((string)$cod['_id'], $tipo_destacado);
        }
        
    } else {
        // Destacar código individual
        $collection_codigos = getCollectionCodigos();
        
        // Preparar datos de actualización
        $update_data = [
            'destacado' => time(),
            'fecha_destacado' => date('Y-m-d H:i:s'),
            'tipo_destacado' => $tipo_destacado
        ];
        
        // Para destacado "super", establecer también destacado_social (aparece en home y tiene prioridad)
        if ($tipo_destacado === 'super') {
            $update_data['destacado_social'] = time();
        }
        
        $resultado_update = $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id), 'id_usuario' => new MongoDB\BSON\ObjectId($user_id)],
            ['$set' => $update_data]
        );
        
        // Si es destacado super y se actualizó correctamente, notificar a usuarios del home
        if ($resultado_update->getModifiedCount() > 0 && $tipo_destacado === 'super') {
            if (function_exists('notificar_competencia_home_destacado_super')) {
                $codigo_actualizado = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
                $emails_enviados = notificar_competencia_home_destacado_super(
                    $codigo_id,
                    $user_id,
                    $codigo_actualizado
                );
                error_log("Notificaciones de competencia home enviadas: $emails_enviados");
            }
        }
        
        // Obtener saldo anterior
        $saldo_anterior = $saldo_actual;
        
        // Descontar el saldo
        $nuevo_saldo = $saldo_actual - $costo_destacado;
        $collection_usuarios = getCollectionUsuarios();
        $collection_usuarios->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($user_id)],
            ['$set' => ['saldo' => $nuevo_saldo]]
        );
        
        // Registrar la transacción
        $transaccion = [
            'usuario_id' => $user_id,
            'tipo' => 'destacado_individual',
            'cantidad' => -$costo_destacado,
            'codigo_id' => $codigo_id,
            'descripcion' => 'Destacado de código individual',
            'fecha' => new MongoDB\BSON\UTCDateTime(),
            'estado' => 'completada',
            'saldo_anterior' => $saldo_anterior,
            'saldo_nuevo' => $nuevo_saldo
        ];
        
        $collection_transacciones = getCollectionTransacciones();
        $collection_transacciones->insertOne($transaccion);
        
        $_SESSION['msg_success'] = "¡Código destacado exitosamente!";

        // Notificaciones (propietario + competidores) y registro en logs
        destacar_codigo_moderno($codigo_id, $tipo_destacado);
        
        // Redirigir con parámetros para mostrar el modal de éxito
        header("Location: https://www.codigoamigo.com/mis-anuncios?success=destacado&codigo=" . urlencode($codigo_id) . "&tipo=" . urlencode($tipo_destacado));
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error destacando código: " . $e->getMessage());
    $_SESSION['msg_error'] = "Error destacando el código. Contacta con soporte.";
    header("Location: https://www.codigoamigo.com/mis-anuncios");
    exit;
}

// Si llegamos aquí sin haber redirigido, redirigir por defecto
header("Location: https://www.codigoamigo.com/mis-anuncios");
exit;
?>
