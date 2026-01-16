<?php
/**
 * Script para corregir el código de backmarket de pedro perez
 * Establece destacado_social y tipo_destacado correctamente
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

// Obtener la transacción más reciente para este código
$collection_transacciones = getCollectionTransacciones();
$transaccion = $collection_transacciones->findOne(
    [
        'codigo_id' => $codigo_id,
        'tipo' => 'destacado',
        'tipo_destacado' => 'super'
    ],
    ['sort' => ['fecha' => -1]]
);

if (!$transaccion) {
    die("No se encontró transacción de tipo 'super' para este código.");
}

$tipo_destacado = $transaccion['tipo_destacado'] ?? 'super';
$fecha_transaccion = $transaccion['fecha']->toDateTime()->getTimestamp();

echo "Corrigiendo código backmarket...\n";
echo "Código ID: $codigo_id\n";
echo "Tipo destacado: $tipo_destacado\n";
echo "Fecha transacción: " . date('Y-m-d H:i:s', $fecha_transaccion) . "\n\n";

// Actualizar el código
$collection_codigos = getCollectionCodigos();
$update_data = [
    'destacado' => $fecha_transaccion, // Usar timestamp de la transacción
    'destacado_social' => $fecha_transaccion, // Establecer destacado_social para tipo super
    'tipo_destacado' => $tipo_destacado,
    'fecha_destacado' => new MongoDB\BSON\UTCDateTime($fecha_transaccion * 1000)
];

$resultado = $collection_codigos->updateOne(
    ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
    ['$set' => $update_data]
);

if ($resultado->getModifiedCount() > 0) {
    echo "✅ Código corregido exitosamente!\n";
    echo "   - destacado: " . date('Y-m-d H:i:s', $fecha_transaccion) . "\n";
    echo "   - destacado_social: " . date('Y-m-d H:i:s', $fecha_transaccion) . "\n";
    echo "   - tipo_destacado: $tipo_destacado\n";
} else {
    echo "⚠️ No se pudo actualizar el código (puede que ya esté correcto)\n";
}

// Verificar el resultado
$codigo_actualizado = $collection_codigos->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
echo "\nEstado actual del código:\n";
echo "   - destacado: " . (isset($codigo_actualizado['destacado']) ? (is_numeric($codigo_actualizado['destacado']) ? date('Y-m-d H:i:s', $codigo_actualizado['destacado']) : 'true') : 'no existe') . "\n";
echo "   - destacado_social: " . (isset($codigo_actualizado['destacado_social']) ? date('Y-m-d H:i:s', $codigo_actualizado['destacado_social']) : 'no existe') . "\n";
echo "   - tipo_destacado: " . ($codigo_actualizado['tipo_destacado'] ?? 'N/A') . "\n";

