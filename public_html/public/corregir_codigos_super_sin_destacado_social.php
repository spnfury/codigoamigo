<?php
/**
 * Script para corregir todos los códigos con tipo_destacado='super' que no tienen destacado_social
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

$collection_codigos = getCollectionCodigos();
$collection_transacciones = getCollectionTransacciones();

// Buscar códigos con tipo_destacado='super' que no tienen destacado_social o lo tienen en 0
$codigos_problema = $collection_codigos->find([
    'tipo_destacado' => 'super',
    '$or' => [
        ['destacado_social' => ['$exists' => false]],
        ['destacado_social' => 0],
        ['destacado_social' => null]
    ]
])->toArray();

echo "=== CORRECCIÓN DE CÓDIGOS SUPER SIN DESTACADO_SOCIAL ===\n\n";
echo "Códigos encontrados con problema: " . count($codigos_problema) . "\n\n";

$corregidos = 0;
$errores = [];

foreach ($codigos_problema as $codigo) {
    $codigo_id = (string)$codigo['_id'];
    
    // Buscar la transacción más reciente de tipo 'super' para este código
    $transaccion = $collection_transacciones->findOne(
        [
            'codigo_id' => $codigo_id,
            'tipo' => 'destacado',
            'tipo_destacado' => 'super'
        ],
        ['sort' => ['fecha' => -1]]
    );
    
    if ($transaccion && isset($transaccion['fecha'])) {
        $fecha_transaccion = $transaccion['fecha']->toDateTime()->getTimestamp();
        
        // Actualizar el código
        $update_data = [
            'destacado_social' => $fecha_transaccion
        ];
        
        // Si destacado no está establecido o es 0, también establecerlo
        if (!isset($codigo['destacado']) || $codigo['destacado'] == 0) {
            $update_data['destacado'] = $fecha_transaccion;
        }
        
        $resultado = $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
            ['$set' => $update_data]
        );
        
        if ($resultado->getModifiedCount() > 0) {
            $corregidos++;
            echo "✅ Código $codigo_id ({$codigo['marca']}) - Corregido\n";
            echo "   destacado_social: " . date('Y-m-d H:i:s', $fecha_transaccion) . "\n";
        } else {
            $errores[] = "No se pudo actualizar código $codigo_id";
        }
    } else {
        // Si no hay transacción, usar la fecha de destacado o fecha actual
        $fecha_destacado = isset($codigo['destacado']) && is_numeric($codigo['destacado']) 
            ? $codigo['destacado'] 
            : time();
        
        $resultado = $collection_codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
            ['$set' => ['destacado_social' => $fecha_destacado]]
        );
        
        if ($resultado->getModifiedCount() > 0) {
            $corregidos++;
            echo "✅ Código $codigo_id ({$codigo['marca']}) - Corregido (sin transacción)\n";
            echo "   destacado_social: " . date('Y-m-d H:i:s', $fecha_destacado) . "\n";
        } else {
            $errores[] = "No se pudo actualizar código $codigo_id";
        }
    }
}

echo "\n=== RESUMEN ===\n";
echo "Códigos corregidos: $corregidos\n";
if (count($errores) > 0) {
    echo "Errores: " . count($errores) . "\n";
    foreach ($errores as $error) {
        echo "  - $error\n";
    }
}

