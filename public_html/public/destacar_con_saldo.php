<?php
session_start();
require_once '../app_with_mongo.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: https://www.codigoamigo.com/login");
    exit;
}

$codigo_id = $_GET['codigo'] ?? '';
$tipo = $_GET['tipo'] ?? 'individual'; // individual o splash

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
        $collection_codigos->updateMany(
            ['usuario_id' => $user_id, 'estado' => 0],
            ['$set' => ['destacado' => 1, 'fecha_destacado' => new MongoDB\BSON\UTCDateTime()]]
        );
        
        // Descontar el saldo
        $nuevo_saldo = $saldo_actual - $costo_destacado;
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
            'estado' => 'completada'
        ];
        
        $collection_transacciones = $mongo->selectCollection('transacciones');
        $collection_transacciones->insertOne($transaccion);
        
        $_SESSION['msg_success'] = "¡Todos tus códigos han sido destacados exitosamente!";
        
    } else {
        // Destacar código individual
        $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id), 'usuario_id' => $user_id],
            ['$set' => ['destacado' => 1, 'fecha_destacado' => new MongoDB\BSON\UTCDateTime()]]
        );
        
        // Descontar el saldo
        $nuevo_saldo = $saldo_actual - $costo_destacado;
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
            'estado' => 'completada'
        ];
        
        $collection_transacciones = $mongo->selectCollection('transacciones');
        $collection_transacciones->insertOne($transaccion);
        
        $_SESSION['msg_success'] = "¡Código destacado exitosamente!";
    }
    
} catch (Exception $e) {
    error_log("Error destacando código: " . $e->getMessage());
    $_SESSION['msg_error'] = "Error destacando el código. Contacta con soporte.";
}

header("Location: https://www.codigoamigo.com/mis-anuncios");
exit;
?>
