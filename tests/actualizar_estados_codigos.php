<?php
// Simular el entorno web
$_SERVER['DOCUMENT_ROOT'] = '/home/admin/web/codigoamigo.com/public_html';
$_SERVER['REQUEST_URI'] = '/tests/actualizar_estados_codigos.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'on';

// Cambiar al directorio correcto
chdir('/home/admin/web/codigoamigo.com/public_html');

// Incluir las dependencias necesarias
include_once '/home/admin/web/codigoamigo.com/public_html/inc/includes.php';
include_once '/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php';

echo "=== ACTUALIZACIÓN MASIVA DE ESTADOS DE CÓDIGOS ===\n\n";

try {
    // Conectar a la base de datos
    $db = createConnection();
    $collection = $db->selectCollection('codigos');
    
    echo "Conectado a la base de datos\n";
    
    // Buscar códigos con estado "activo" (string)
    $codigos_activos = $collection->find(['estado' => 'activo']);
    $total_activos = $collection->countDocuments(['estado' => 'activo']);
    
    echo "Códigos encontrados con estado 'activo': $total_activos\n\n";
    
    if ($total_activos > 0) {
        echo "Actualizando códigos...\n";
        
        // Actualizar todos los códigos con estado "activo" a estado 0
        $result = $collection->updateMany(
            ['estado' => 'activo'],
            ['$set' => ['estado' => 0]]
        );
        
        echo "✓ Códigos actualizados: " . $result->getModifiedCount() . "\n";
        
        // Verificar la actualización
        $codigos_verificacion = $collection->countDocuments(['estado' => 0]);
        $codigos_activos_restantes = $collection->countDocuments(['estado' => 'activo']);
        
        echo "✓ Códigos con estado 0: $codigos_verificacion\n";
        echo "✓ Códigos con estado 'activo' restantes: $codigos_activos_restantes\n";
        
        if ($codigos_activos_restantes == 0) {
            echo "✅ ACTUALIZACIÓN COMPLETADA EXITOSAMENTE\n";
        } else {
            echo "⚠ ADVERTENCIA: Quedan códigos con estado 'activo'\n";
        }
        
    } else {
        echo "✓ No hay códigos con estado 'activo' para actualizar\n";
    }
    
    // Mostrar estadísticas finales
    echo "\n=== ESTADÍSTICAS FINALES ===\n";
    $estados = [
        '0' => $collection->countDocuments(['estado' => 0]),
        '1' => $collection->countDocuments(['estado' => 1]),
        '-1' => $collection->countDocuments(['estado' => -1]),
        '-2' => $collection->countDocuments(['estado' => -2]),
        'activo' => $collection->countDocuments(['estado' => 'activo']),
        'otros' => $collection->countDocuments(['estado' => ['$nin' => [0, 1, -1, -2, 'activo']]])
    ];
    
    foreach ($estados as $estado => $cantidad) {
        echo "- Estado '$estado': $cantidad códigos\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA ACTUALIZACIÓN ===\n";
?>

