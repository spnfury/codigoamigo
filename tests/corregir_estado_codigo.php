<?php
// Simular el entorno web
$_SERVER['DOCUMENT_ROOT'] = '/home/admin/web/codigoamigo.com/public_html';
$_SERVER['REQUEST_URI'] = '/de-bmw-2?codigo=68ca7fa797d16360a1059ae2';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'on';

// Cambiar al directorio correcto
chdir('/home/admin/web/codigoamigo.com/public_html');

// Incluir las dependencias necesarias
include_once '/home/admin/web/codigoamigo.com/public_html/inc/includes.php';
include_once '/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php';

echo "=== CORRECCIÓN DEL ESTADO DEL CÓDIGO ===\n\n";

$codigo_id = '68ca7fa797d16360a1059ae2';

try {
    // Conectar a la base de datos
    $db = createConnection();
    $collection = $db->selectCollection('codigos');
    
    echo "Conectado a la base de datos\n";
    
    // Buscar el código
    $obj_id = new MongoDB\BSON\ObjectId($codigo_id);
    $codigo = $collection->findOne(['_id' => $obj_id]);
    
    if (!$codigo) {
        echo "✗ ERROR: Código no encontrado\n";
        exit;
    }
    
    echo "✓ Código encontrado:\n";
    echo "- ID: " . $codigo['_id'] . "\n";
    echo "- Estado actual: " . var_export($codigo['estado'], true) . "\n";
    echo "- Marca: " . ($codigo['marca'] ?? 'No definida') . "\n";
    echo "- Código: " . ($codigo['codigo'] ?? 'No definido') . "\n";
    
    // Verificar si el estado es "activo" (string)
    if ($codigo['estado'] === 'activo') {
        echo "\n⚠ PROBLEMA: El estado es 'activo' (string) en lugar de 0 (número)\n";
        echo "Corrigiendo estado...\n";
        
        // Actualizar el estado a 0
        $result = $collection->updateOne(
            ['_id' => $obj_id],
            ['$set' => ['estado' => 0]]
        );
        
        if ($result->getModifiedCount() > 0) {
            echo "✓ Estado corregido a 0\n";
            
            // Verificar la corrección
            $codigo_actualizado = $collection->findOne(['_id' => $obj_id]);
            echo "✓ Estado verificado: " . $codigo_actualizado['estado'] . "\n";
            
        } else {
            echo "✗ ERROR: No se pudo actualizar el estado\n";
        }
        
    } else if ($codigo['estado'] === 0) {
        echo "✓ El estado ya es correcto (0)\n";
    } else {
        echo "⚠ ADVERTENCIA: Estado inesperado: " . var_export($codigo['estado'], true) . "\n";
    }
    
    // Verificar si la marca coincide con la URL
    $marca_esperada = 'bmw-2';
    $marca_actual = $codigo['marca'] ?? '';
    
    if ($marca_actual !== $marca_esperada) {
        echo "\n⚠ PROBLEMA: La marca del código ('$marca_actual') no coincide con la URL ('$marca_esperada')\n";
        echo "¿Deseas corregir la marca? (Esto cambiará la URL del código)\n";
        echo "Marca actual: '$marca_actual'\n";
        echo "Marca esperada: '$marca_esperada'\n";
    } else {
        echo "✓ La marca coincide con la URL\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA CORRECCIÓN ===\n";
?>

