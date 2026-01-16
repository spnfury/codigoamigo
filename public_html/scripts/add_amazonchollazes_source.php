<?php
/**
 * Script para agregar la fuente AmazonChollazosES a la base de datos
 * Ejecutar una sola vez: php add_amazonchollazes_source.php
 */

// Incluir funciones necesarias
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos_fuentes.php';

echo "=== Agregando fuente AmazonChollazosES ===\n\n";

// Datos de la nueva fuente
$datos_fuente = [
    'nombre' => 'AmazonChollazosES',
    'tipo' => 'telegram',
    'url' => 'AmazonChollazosES',  // Username del canal (sin @)
    'configuracion' => [
        'categoria_defecto' => 'general',
        'estado_defecto' => 1,  // 1 = activo (visible inmediatamente)
        'reescribir_automatico' => true  // Usar Groq AI para reescribir
    ],
    'activo' => true
];

echo "Creando fuente con los siguientes datos:\n";
echo "- Nombre: {$datos_fuente['nombre']}\n";
echo "- Tipo: {$datos_fuente['tipo']}\n";
echo "- URL/Canal: {$datos_fuente['url']}\n";
echo "- Estado por defecto: " . ($datos_fuente['configuracion']['estado_defecto'] == 1 ? 'Activo' : 'Inactivo') . "\n";
echo "- Reescritura automática: " . ($datos_fuente['configuracion']['reescribir_automatico'] ? 'Sí' : 'No') . "\n";
echo "- Activa: " . ($datos_fuente['activo'] ? 'Sí' : 'No') . "\n\n";

// Verificar si ya existe
$collection = getCollectionChollosFuentes();
if ($collection) {
    $fuente_existente = $collection->findOne(['url' => $datos_fuente['url']]);
    if ($fuente_existente) {
        echo "⚠️  La fuente ya existe en la base de datos.\n";
        echo "ID: " . $fuente_existente['_id'] . "\n";
        echo "Nombre: " . ($fuente_existente['nombre'] ?? 'N/A') . "\n";
        echo "Activa: " . (($fuente_existente['activo'] ?? false) ? 'Sí' : 'No') . "\n";
        echo "\n¿Deseas actualizarla? (s/n): ";
        
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) !== 's') {
            echo "\nOperación cancelada.\n";
            exit(0);
        }
        
        // Actualizar fuente existente
        $resultado = actualizarFuente((string)$fuente_existente['_id'], $datos_fuente);
        if ($resultado['success']) {
            echo "\n✓ Fuente actualizada exitosamente!\n";
            echo "ID: " . $fuente_existente['_id'] . "\n";
        } else {
            echo "\n✗ Error al actualizar la fuente: " . ($resultado['error'] ?? 'Error desconocido') . "\n";
            exit(1);
        }
    } else {
        // Crear nueva fuente
        $resultado = crearFuente($datos_fuente);
        
        if ($resultado['success']) {
            echo "✓ Fuente creada exitosamente!\n";
            echo "ID: {$resultado['id']}\n\n";
            echo "Puedes ejecutar el script de sincronización con:\n";
            echo "cd /home/admin/web/codigoamigo.com/public_html/scripts\n";
            echo "python3 telegram_monitor.py\n";
        } else {
            echo "✗ Error al crear la fuente: " . ($resultado['error'] ?? 'Error desconocido') . "\n";
            exit(1);
        }
    }
} else {
    echo "✗ Error: No se pudo conectar a la base de datos.\n";
    exit(1);
}

echo "\nListo! La fuente ha sido configurada.\n";
?>
