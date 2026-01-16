<?php
// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';
include_once __DIR__ . '/../myphp/funciones_codigo.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

// Obtener datos del POST
$codigo_id = $_POST['codigo_id'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$precio = $_POST['precio'] ?? 0;

if (empty($codigo_id) || empty($tipo) || empty($precio)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

try {
    // Obtener el usuario y verificar saldo
    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);

    if (!$usuario) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }

    $saldo_actual = $usuario['saldo'] ?? 0;
    $precio_euros = $precio / 100;

    if ($saldo_actual < $precio_euros) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Saldo insuficiente']);
        exit;
    }

    // Obtener información del código
    $collection_codigos = getCollectionCodigos();
    $codigo = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);

    if (!$codigo || $codigo["id_usuario"] != $_SESSION["user_id"]) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Código no encontrado o no autorizado']);
        exit;
    }

    // Destacar el código
    $duracion_dias = $tipo === 'normal' ? 30 : 60;
    $fecha_fin = new DateTime();
    $fecha_fin->add(new DateInterval('P' . $duracion_dias . 'D'));

    $collection_codigos->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
        [
            '$set' => [
                'destacado' => true,
                'tipo_destacado' => $tipo,
                'fecha_destacado' => new MongoDB\BSON\UTCDateTime(),
                'fecha_fin_destacado' => new MongoDB\BSON\UTCDateTime($fecha_fin->getTimestamp() * 1000)
            ]
        ]
    );

    // Obtener saldo anterior
    $saldo_anterior = $saldo_actual;
    
    // Actualizar saldo del usuario
    $nuevo_saldo = $saldo_actual - $precio_euros;
    $collection_usuarios->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])],
        ['$set' => ['saldo' => $nuevo_saldo]]
    );

    // Registrar transacción
    $collection_transacciones = getCollectionTransacciones();
    $marca_nombre = $codigo['marca'] ?? '';
    $transaccion = [
        'usuario_id' => $_SESSION["user_id"],
        'tipo' => 'destacado',
        'subtipo' => $tipo,
        'cantidad' => -$precio_euros,
        'descripcion' => "Destacado de código - Marca: {$marca_nombre} - Tipo: {$tipo}",
        'fecha' => new MongoDB\BSON\UTCDateTime(),
        'estado' => 'completada',
        'codigo_id' => $codigo_id,
        'marca' => $marca_nombre,
        'tipo_destacado' => $tipo,
        'metodo_pago' => 'saldo',
        'saldo_anterior' => $saldo_anterior,
        'saldo_nuevo' => $nuevo_saldo
    ];
    $collection_transacciones->insertOne($transaccion);

    // Log de la transacción
    error_log("Destacado con saldo exitoso - Usuario: " . $_SESSION["user_id"] . ", Código: " . $codigo_id);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Código destacado exitosamente',
        'saldo_restante' => $nuevo_saldo
    ]);

} catch (Exception $e) {
    error_log("Error procesando destacado con saldo: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>
