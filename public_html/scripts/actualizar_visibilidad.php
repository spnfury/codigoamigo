<?php
/**
 * Script para actualizar la visibilidad de todos los códigos existentes
 * basada en su posición real en cada marca
 */

// Incluir autoloader de Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Definir variables de entorno necesarias
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/scripts/actualizar_visibilidad.php';
}

// Incluir archivos necesarios
require_once __DIR__ . '/../inc/conexion.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_codigo.php';

echo "🔄 Iniciando actualización de visibilidad de códigos...\n\n";

try {
    $db = createConnection();
    $collection_codigos = $db->selectCollection('codigos');
    $collection_marcas = $db->selectCollection('marcas');
    
    // Obtener todas las marcas activas
    $marcas = $collection_marcas->find(['estado' => 1]);
    $total_marcas = 0;
    $total_codigos_actualizados = 0;
    
    foreach ($marcas as $marca) {
        $marca_clave = $marca['nombre_clave'];
        $total_marcas++;
        
        echo "📊 Procesando marca: {$marca['nombre']} ({$marca_clave})\n";
        
        // Actualizar visibilidad de todos los códigos de esta marca
        $codigos_actualizados = updateAllCodesVisibilityInBrand($marca_clave);
        $total_codigos_actualizados += $codigos_actualizados;
        
        echo "   ✅ {$codigos_actualizados} códigos actualizados\n\n";
    }
    
    echo "🎉 Actualización completada:\n";
    echo "   📈 Marcas procesadas: {$total_marcas}\n";
    echo "   🔄 Códigos actualizados: {$total_codigos_actualizados}\n\n";
    
    // Mostrar estadísticas de visibilidad
    echo "📊 Estadísticas de visibilidad:\n";
    
    $stats = $collection_codigos->aggregate([
        ['$match' => ['estado' => 0]],
        ['$group' => [
            '_id' => '$visibilidad',
            'count' => ['$sum' => 1]
        ]],
        ['$sort' => ['count' => -1]]
    ])->toArray();
    
    foreach ($stats as $stat) {
        $visibilidad = $stat['_id'] ?? 'sin_visibilidad';
        $count = $stat['count'];
        echo "   {$visibilidad}: {$count} códigos\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error durante la actualización: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Script completado exitosamente.\n";
?>
