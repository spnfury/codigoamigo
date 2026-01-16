#!/usr/bin/env php
<?php
/**
 * Script para verificar y corregir el precio de un chollo
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';

$chollo_id = $argv[1] ?? '692af44fc39637dc0d05344c';

echo "🔍 Verificando chollo ID: {$chollo_id}\n\n";

$collection = getCollectionChollos();
if (!$collection) {
    die("Error: No se pudo conectar a MongoDB\n");
}

try {
    $objectId = new MongoDB\BSON\ObjectId($chollo_id);
    $doc = $collection->findOne(['_id' => $objectId]);
    
    if (!$doc) {
        die("❌ Chollo no encontrado\n");
    }
    
    echo "📋 Información actual del chollo:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "ID: " . (string)$doc['_id'] . "\n";
    echo "Título: " . ($doc['titulo'] ?? 'N/A') . "\n";
    echo "Precio original: " . ($doc['precio_original'] ?? 'N/A') . " €\n";
    echo "Precio descuento: " . ($doc['precio_descuento'] ?? 'N/A') . " €\n";
    echo "Porcentaje descuento: " . ($doc['porcentaje_descuento'] ?? 'N/A') . "%\n";
    echo "Enlace: " . ($doc['enlace'] ?? 'N/A') . "\n";
    echo "Descripción: " . substr($doc['descripcion'] ?? '', 0, 200) . "...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Si se proporcionan nuevos precios como argumentos
    if (isset($argv[2]) && isset($argv[3])) {
        $nuevo_precio_original = floatval($argv[2]);
        $nuevo_precio_descuento = floatval($argv[3]);
        
        // Calcular porcentaje de descuento
        if ($nuevo_precio_original > 0) {
            $nuevo_porcentaje = round((($nuevo_precio_original - $nuevo_precio_descuento) / $nuevo_precio_original) * 100);
        } else {
            $nuevo_porcentaje = 0;
        }
        
        echo "🔧 Actualizando precios...\n";
        echo "   Precio original: {$nuevo_precio_original} €\n";
        echo "   Precio descuento: {$nuevo_precio_descuento} €\n";
        echo "   Porcentaje descuento: {$nuevo_porcentaje}%\n\n";
        
        $update = [
            '$set' => [
                'precio_original' => $nuevo_precio_original,
                'precio_descuento' => $nuevo_precio_descuento,
                'porcentaje_descuento' => $nuevo_porcentaje
            ]
        ];
        
        $result = $collection->updateOne(
            ['_id' => $objectId],
            $update
        );
        
        if ($result->getModifiedCount() > 0) {
            echo "✅ Precios actualizados correctamente\n";
        } else {
            echo "⚠️  No se realizaron cambios (puede que los valores sean iguales)\n";
        }
    } else {
        echo "💡 Para actualizar los precios, ejecuta:\n";
        echo "   php verificar_chollo_precio.php {$chollo_id} PRECIO_ORIGINAL PRECIO_DESCUENTO\n";
        echo "   Ejemplo: php verificar_chollo_precio.php {$chollo_id} 1158.99 856.99\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}





