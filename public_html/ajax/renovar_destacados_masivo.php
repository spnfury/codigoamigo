<?php
/**
 * AJAX Handler para renovar masivamente códigos destacados caducados
 * desde el área del usuario (Mis Anuncios).
 * Aplica el 50% de descuento de renovación.
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'error' => 'No has iniciado sesión']);
    exit;
}

// Recibir JSON
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['codigos']) || !is_array($input['codigos']) || empty($input['codigos'])) {
    echo json_encode(['success' => false, 'error' => 'No se enviaron códigos para renovar']);
    exit;
}

$codigos_ids = $input['codigos'];

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/includes.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones.php';

$collection_usuarios = getCollectionUsuarios();
$collection_codigos = getCollectionCodigos();
$collection_transacciones = getCollectionTransacciones();

// Obtener usuario y saldo actual
$usuario_id = $_SESSION["user_id"];
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario_id)]);

if (!$usuario) {
    echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
    exit;
}

$saldo_usuario = floatval($usuario['saldo'] ?? 0);

// Precios predefinidos y descuentos
$precio_normal = DESTACADO_PRECIO_NORMAL;
$precio_super = DESTACADO_PRECIO_SUPER;
$descuento = 0.50; // 50% de descuento en renovación
$precio_normal_desc = round($precio_normal * $descuento, 2);
$precio_super_desc = round($precio_super * $descuento, 2);

$precio_total_a_cobrar = 0;
$codigos_a_renovar = [];

// Verificar cada código enviado
foreach ($codigos_ids as $cid) {
    try {
        $codigo_obj = $collection_codigos->findOne([
            '_id' => new MongoDB\BSON\ObjectId($cid),
            'id_usuario' => $usuario_id
        ]);

        if ($codigo_obj) {
            // Verificar que fue destacado anteriormente
            $fue_destacado = (isset($codigo_obj['destacado']) && (int)$codigo_obj['destacado'] > 0);
            
            // Ver si ya expiró (precaución adicional)
            $fin = isset($codigo_obj['fecha_fin_destacado']) ? $codigo_obj['fecha_fin_destacado'] : 0;
            if ($fin instanceof MongoDB\BSON\UTCDateTime) {
                $fin_ts = $fin->toDateTime()->getTimestamp();
            } elseif (is_numeric($fin)) {
                $fin_ts = (int)$fin;
            } else {
                $fin_ts = strtotime((string)$fin);
            }

            if ($fue_destacado && $fin_ts < time()) {
                $tipo = $codigo_obj['tipo_destacado'] ?? 'normal';
                $es_super = ($tipo === 'super');
                $precio = $es_super ? $precio_super_desc : $precio_normal_desc;
                $duracion = $es_super ? DESTACADO_DURACION_SUPER : DESTACADO_DURACION_NORMAL;

                $precio_total_a_cobrar += $precio;
                $codigos_a_renovar[] = [
                    '_id' => $codigo_obj['_id'],
                    'tipo' => $tipo,
                    'precio' => $precio,
                    'duracion' => $duracion,
                    'marca' => $codigo_obj['marca'] ?? 'N/A'
                ];
            }
        }
    } catch (Exception $e) {
        // ID inválido u otro error con este código, simplemente saltarlo
        continue;
    }
}

if (empty($codigos_a_renovar)) {
    echo json_encode(['success' => false, 'error' => 'No hay códigos válidos para renovar.']);
    exit;
}

// Verificar saldo
if ($saldo_usuario < $precio_total_a_cobrar) {
    // Faltan fondos. Devolvemos un código de error especial
    // y la url a donde redirigir (el checkout o la página de saldo)
    echo json_encode([
        'success' => false, 
        'error' => 'No tienes suficiente saldo. Necesitas ' . number_format($precio_total_a_cobrar, 2, ',', '.') . '€ y tienes ' . number_format($saldo_usuario, 2, ',', '.') . '€.',
        'error_codigo' => 'saldo_insuficiente',
        'precio_total' => $precio_total_a_cobrar,
        'redirect_url' => '/public/destaca.php' // Podría ser una página especial para recargar saldo exacto
    ]);
    exit;
}

// Proceder con la renovación masiva
$nuevo_saldo = $saldo_usuario - $precio_total_a_cobrar;
$renovados = 0;

foreach ($codigos_a_renovar as $cr) {
    $nueva_fecha_fin = new MongoDB\BSON\UTCDateTime((time() + ($cr['duracion'] * 86400)) * 1000);
    $es_super = ($cr['tipo'] === 'super');

    $update_data = [
        'destacado' => time(),
        'fecha_destacado' => new MongoDB\BSON\UTCDateTime(),
        'tipo_destacado' => $cr['tipo'],
        'prioridad_pago' => time(),
        'fecha_fin_destacado' => $nueva_fecha_fin,
        'aviso_expiracion_enviado' => false,
        'aviso_expirado_enviado' => false,
        'auto_renovar_destacado' => true // Al renovar masivamente, reactivamos su auto-renovación
    ];

    if ($es_super) {
        $update_data['destacado_social'] = time();
    }

    $collection_codigos->updateOne(
        ['_id' => $cr['_id']],
        ['$set' => $update_data]
    );

    // Registrar transacción independiente por cada código
    $collection_transacciones->insertOne([
        'usuario_id' => $usuario_id,
        'tipo' => 'renovacion_destacado_masiva',
        'cantidad' => -$cr['precio'],
        'codigo_id' => (string)$cr['_id'],
        'descripcion' => 'Renovación Masiva (' . ($es_super ? 'Super' : 'Normal') . ') con 50% dto en ' . $cr['marca'],
        'fecha' => new MongoDB\BSON\UTCDateTime(),
        'estado' => 'completada',
        'marca' => $cr['marca'],
        'descuento_aplicado' => '50%',
        'precio_original' => ($es_super) ? $precio_super : $precio_normal,
        'precio_cobrado' => $cr['precio']
    ]);

    $renovados++;
}

// Cobrar saldo total al usuario
$collection_usuarios->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($usuario_id)],
        ['$set' => ['saldo' => round($nuevo_saldo, 2)]]
);

echo json_encode([
    'success' => true,
    'renovados' => $renovados,
    'nuevo_saldo' => round($nuevo_saldo, 2),
    'message' => 'Se han renovado ' . $renovados . ' códigos correctamente.'
]);
