<?php
include_once __DIR__ . '/inc/logger.php';
// Iniciar buffer de salida para evitar output accidental
ob_start();

session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener datos del POST
$codigo_id = $_POST['codigo_id'] ?? '';
$tipo = $_POST['tipo'] ?? 'normal';
$precio = $_POST['precio'] ?? '';

if (empty($codigo_id) || empty($tipo) || empty($precio)) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// Obtener información del código
$collection_codigos = getCollectionCodigos();
$codigo = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);

if (!$codigo) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Código no encontrado']);
    exit;
}

// Verificar que el código pertenece al usuario
if ($codigo["id_usuario"] != $_SESSION["user_id"]) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado para destacar este código']);
    exit;
}

// Validar precio
$precio_valido = ($tipo === 'normal') ? 99 : 399; // en céntimos
if ($precio != $precio_valido) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Precio inválido']);
    exit;
}

// Registrar intento de pago
log_info("Creando orden PayPal para destacar código: $codigo_id, tipo: $tipo, precio: $precio");

try {
    // Para PayPal no necesitamos crear una sesión previa como con Stripe
    // Solo validamos los datos y devolvemos éxito para que el frontend maneje el pago

    // Preparar metadata para la transacción futura
    $metadata = [
        'tipo' => 'destacar_codigo_paypal',
        'codigo_id' => $codigo_id,
        'marca' => $codigo['marca'] ?? '',
        'usuario_id' => $_SESSION["user_id"],
        'username' => $_SESSION["username"] ?? 'Usuario',
        'tipo_destacado' => $tipo,
        'precio' => $precio / 100,
        'descripcion' => substr($codigo['descripcion'] ?? '', 0, 200),
        'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
        'estado' => 'pendiente'
    ];

    // Podríamos guardar una transacción pendiente aquí si quisiéramos
    // pero por simplicidad, la guardaremos cuando se complete el pago

    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'tipo' => $tipo,
        'precio' => $precio,
        'codigo_id' => $codigo_id,
        'metadata' => $metadata
    ]);
    exit;

} catch (Exception $e) {
    log_error("Error creando orden PayPal: " . $e->getMessage());
    log_error("Stack trace: " . $e->getTraceAsString());

    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al crear la orden de pago: ' . $e->getMessage()]);
    exit;
}
?>
