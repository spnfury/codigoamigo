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

echo "=== ANÁLISIS DEL CÓDIGO ESPECÍFICO ===\n\n";

$codigo_id = '68ca7fa797d16360a1059ae2';
$marca = 'bmw-2';

echo "Código ID: " . $codigo_id . "\n";
echo "Marca: " . $marca . "\n\n";

try {
    // Verificar si el ObjectId es válido (formato básico)
    if (!preg_match('/^[a-f\d]{24}$/i', $codigo_id)) {
        echo "✗ ERROR: El ID del código no tiene el formato correcto de ObjectId\n";
        exit;
    }
    
    echo "✓ ObjectId válido\n";
    
    // Crear ObjectId
    $obj_id = new MongoDB\BSON\ObjectId($codigo_id);
    echo "✓ ObjectId creado: " . $obj_id . "\n\n";
    
    // Buscar el código en la base de datos
    $array_filtro = array("estado" => 0, "_id" => $obj_id);
    $array_opciones = array('limit' => 1);
    
    echo "Buscando código con filtro:\n";
    echo "- estado: 0\n";
    echo "- _id: " . $obj_id . "\n\n";
    
    $lista_codigos = get_all_listado_codigos_array($array_filtro, $array_opciones);
    
    echo "Resultados encontrados: " . $lista_codigos["total_number"] . "\n";
    
    if ($lista_codigos["total_number"] > 0) {
        $codigo = $lista_codigos["results"][0];
        echo "✓ Código encontrado:\n";
        echo "- ID: " . $codigo["_id"] . "\n";
        echo "- Marca: " . ($codigo["marca"] ?? 'No definida') . "\n";
        echo "- Estado: " . ($codigo["estado"] ?? 'No definido') . "\n";
        echo "- Código: " . ($codigo["codigo"] ?? 'No definido') . "\n";
        echo "- Usuario: " . ($codigo["id_usuario"] ?? 'No definido') . "\n";
        echo "- Fecha publicación: " . ($codigo["fecha_publicacion"] ?? 'No definida') . "\n";
        echo "- Descripción: " . substr($codigo["descripcion"] ?? 'No definida', 0, 100) . "...\n";
        
        // Verificar si la marca coincide
        if (isset($codigo["marca"]) && $codigo["marca"] !== $marca) {
            echo "⚠ ADVERTENCIA: La marca del código (" . $codigo["marca"] . ") no coincide con la URL (" . $marca . ")\n";
        }
        
    } else {
        echo "✗ ERROR: Código no encontrado\n";
        
        // Buscar sin filtro de estado
        echo "\nBuscando sin filtro de estado...\n";
        $array_filtro_sin_estado = array("_id" => $obj_id);
        $lista_codigos_sin_estado = get_all_listado_codigos_array($array_filtro_sin_estado, $array_opciones);
        
        echo "Resultados sin filtro de estado: " . $lista_codigos_sin_estado["total_number"] . "\n";
        
        if ($lista_codigos_sin_estado["total_number"] > 0) {
            $codigo_sin_estado = $lista_codigos_sin_estado["results"][0];
            echo "✓ Código encontrado (sin filtro de estado):\n";
            echo "- ID: " . $codigo_sin_estado["_id"] . "\n";
            echo "- Estado: " . ($codigo_sin_estado["estado"] ?? 'No definido') . "\n";
            echo "- Marca: " . ($codigo_sin_estado["marca"] ?? 'No definida') . "\n";
            
            if (isset($codigo_sin_estado["estado"]) && $codigo_sin_estado["estado"] !== 0) {
                echo "⚠ PROBLEMA: El código está inactivo (estado: " . $codigo_sin_estado["estado"] . ")\n";
            }
        } else {
            echo "✗ ERROR: Código no existe en la base de datos\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL ANÁLISIS ===\n";
?>
