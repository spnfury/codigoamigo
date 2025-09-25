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

echo "=== CORRECCIÓN DE LA MARCA DEL CÓDIGO ===\n\n";

$codigo_id = '68ca7fa797d16360a1059ae2';
$marca_correcta = 'bmw-2';

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
    echo "- Estado: " . $codigo['estado'] . "\n";
    echo "- Marca actual: '" . ($codigo['marca'] ?? 'No definida') . "'\n";
    echo "- Marca correcta: '$marca_correcta'\n";
    
    // Actualizar la marca
    echo "\nCorrigiendo marca...\n";
    $result = $collection->updateOne(
        ['_id' => $obj_id],
        ['$set' => ['marca' => $marca_correcta]]
    );
    
    if ($result->getModifiedCount() > 0) {
        echo "✓ Marca corregida a '$marca_correcta'\n";
        
        // Verificar la corrección
        $codigo_actualizado = $collection->findOne(['_id' => $obj_id]);
        echo "✓ Marca verificada: '" . $codigo_actualizado['marca'] . "'\n";
        
        // Ahora probar si el código se puede mostrar
        echo "\nProbando si el código se puede mostrar...\n";
        
        $array_filtro = array("estado" => 0, "_id" => $obj_id);
        $array_opciones = array('limit' => 1);
        
        $lista_codigos = get_all_listado_codigos_array($array_filtro, $array_opciones);
        
        if ($lista_codigos["total_number"] > 0) {
            echo "✓ Código ahora se puede mostrar correctamente\n";
            echo "✓ URL: https://www.codigoamigo.com/de-bmw-2?codigo=68ca7fa797d16360a1059ae2\n";
        } else {
            echo "✗ ERROR: El código aún no se puede mostrar\n";
        }
        
    } else {
        echo "✗ ERROR: No se pudo actualizar la marca\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA CORRECCIÓN ===\n";
?>

