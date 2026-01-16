<?php

/**
 * Script de migración para añadir campos financieros a marcas existentes
 *
 * Ejecutar: php /home/admin/web/codigoamigo.com/public_html/scripts/migracion_campos_financieros.php
 */

require_once __DIR__ . '/../inc/includes.php';

// Obtener colección de marcas
$collection_marcas = getCollectionMarcas();

echo "=== MIGRACIÓN DE CAMPOS FINANCIEROS ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Contadores
$total_marcas = 0;
$marcas_actualizadas = 0;
$marcas_sin_cambios = 0;

// Obtener todas las marcas
$marcas = $collection_marcas->find(['estado' => 1])->toArray();

foreach ($marcas as $marca) {
    $total_marcas++;

    // Verificar si la marca ya tiene datos financieros
    if (!isset($marca['datos_financieros'])) {

        echo "Actualizando marca: {$marca['nombre']} (ID: {$marca['_id']})\n";

        // Añadir campos financieros por defecto
        $datos_financieros = [
            'enlace_afiliado' => '',
            'tipo_comision' => 'fijo',
            'comision_fija' => 0,
            'comision_porcentaje' => 0,
            'notas_financieras' => '',
            'fecha_actualizacion' => date('Y-m-d H:i:s')
        ];

        try {
            $result = $collection_marcas->updateOne(
                ['_id' => $marca['_id']],
                ['$set' => ['datos_financieros' => $datos_financieros]]
            );

            if ($result->getModifiedCount() > 0) {
                $marcas_actualizadas++;
                echo "✓ Marca actualizada correctamente\n";
            } else {
                echo "✗ Error al actualizar marca\n";
            }
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    } else {
        $marcas_sin_cambios++;
        echo "• Marca {$marca['nombre']} ya tiene datos financieros\n";
    }
}

echo "\n=== RESUMEN DE MIGRACIÓN ===\n";
echo "Total de marcas procesadas: $total_marcas\n";
echo "Marcas actualizadas: $marcas_actualizadas\n";
echo "Marcas sin cambios: $marcas_sin_cambios\n";
echo "Estado: " . ($marcas_actualizadas > 0 ? "COMPLETADA" : "SIN CAMBIOS NECESARIOS") . "\n";

?>

