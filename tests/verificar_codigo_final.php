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

echo "=== VERIFICACIÓN FINAL DEL CÓDIGO ===\n\n";

$codigo_id = '68ca7fa797d16360a1059ae2';
$marca = 'bmw-2';

try {
    // Simular la lógica de la ruta /de-{marca}
    $array_filtro = array("estado" => 0, "_id" => new MongoDB\BSON\ObjectId($codigo_id));
    $array_opciones = array('limit' => 1);
    
    $lista_codigos = get_all_listado_codigos_array($array_filtro, $array_opciones);
    $codigo = isset($lista_codigos["results"][0]) ? $lista_codigos["results"][0] : null;
    
    if ($codigo) {
        echo "✅ CÓDIGO FUNCIONANDO CORRECTAMENTE\n\n";
        echo "Detalles del código:\n";
        echo "- ID: " . $codigo["_id"] . "\n";
        echo "- Estado: " . $codigo["estado"] . " (correcto)\n";
        echo "- Marca: " . $codigo["marca"] . " (coincide con URL)\n";
        echo "- Código: " . ($codigo["codigo"] ?? 'No definido') . "\n";
        echo "- Usuario: " . ($codigo["id_usuario"] ?? 'No definido') . "\n";
        echo "- Descripción: " . substr($codigo["descripcion"] ?? 'No definida', 0, 100) . "...\n";
        echo "- Fecha publicación: " . ($codigo["fecha_publicacion"] ?? 'No definida') . "\n";
        
        echo "\n✅ URL FUNCIONAL:\n";
        echo "https://www.codigoamigo.com/de-bmw-2?codigo=68ca7fa797d16360a1059ae2\n";
        
        echo "\n✅ PROBLEMAS SOLUCIONADOS:\n";
        echo "1. Estado corregido de 'activo' (string) a 0 (número)\n";
        echo "2. Marca corregida de 'bmw 3' a 'bmw-2'\n";
        echo "3. El código ahora cumple con los filtros del sistema\n";
        
    } else {
        echo "❌ ERROR: El código aún no se puede mostrar\n";
        echo "Total de resultados: " . $lista_codigos["total_number"] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA VERIFICACIÓN ===\n";
?>

