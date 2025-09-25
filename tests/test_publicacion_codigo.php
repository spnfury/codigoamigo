<?php
// Simular el entorno web
$_SERVER['DOCUMENT_ROOT'] = '/home/admin/web/codigoamigo.com/public_html';
$_SERVER['REQUEST_URI'] = '/php/codigo_insertado.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'on';

// Cambiar al directorio correcto
chdir('/home/admin/web/codigoamigo.com/public_html');

// Incluir las dependencias necesarias
include_once '/home/admin/web/codigoamigo.com/public_html/inc/includes.php';
include_once '/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php';

echo "=== PRUEBA DE PUBLICACIÓN DE CÓDIGO ===\n\n";

// Simular datos de un código de prueba
$datos_codigo = [
    'marca' => 'test-marca',
    'num_beneficio' => 10,
    'tipo_beneficio' => 'euros',
    'codigo' => 'TEST123',
    'descuento' => 'TEST123',
    'descripcion' => 'Código de prueba para verificar el estado',
    'provincia' => 'Madrid',
    'localidad' => 'Madrid',
    'fecha_caducidad' => '2025-12-31'
];

// Simular sesión de usuario
session_start();
$_SESSION['user_id'] = '58bd851da54e295b8b52f702'; // Usuario de prueba

echo "Datos del código de prueba:\n";
foreach ($datos_codigo as $key => $value) {
    echo "- $key: $value\n";
}
echo "- Usuario: " . $_SESSION['user_id'] . "\n\n";

try {
    // Conectar a la base de datos
    $db = createConnection();
    $collection = $db->selectCollection('codigos');
    
    echo "Conectado a la base de datos\n";
    
    // Verificar si ya existe un código de esta marca para este usuario
    $codigo_existente = $collection->findOne([
        'marca' => $datos_codigo['marca'],
        'id_usuario' => $_SESSION["user_id"]
    ]);
    
    if ($codigo_existente) {
        echo "⚠ ADVERTENCIA: Ya existe un código de esta marca para este usuario\n";
        echo "Eliminando código existente para la prueba...\n";
        $collection->deleteOne(['_id' => $codigo_existente['_id']]);
        echo "✓ Código existente eliminado\n";
    }
    
    // Preparar datos para insertar (simulando el código corregido)
    $nuevo_codigo = [
        'marca' => $datos_codigo['marca'],
        'num_beneficio' => (int)$datos_codigo['num_beneficio'],
        'tipo_beneficio' => $datos_codigo['tipo_beneficio'],
        'codigo' => $datos_codigo['codigo'],
        'codigo_descuento' => $datos_codigo['descuento'],
        'descripcion' => $datos_codigo['descripcion'],
        'provincia' => $datos_codigo['provincia'],
        'localidad' => $datos_codigo['localidad'],
        'fecha_validez' => $datos_codigo['fecha_caducidad'],
        'id_usuario' => $_SESSION["user_id"],
        'fecha_publicacion' => date('Y-m-d H:i:s'),
        'fecha_modificacion' => date('Y-m-d H:i:s'),
        'visibilidad' => 'media',
        'estado' => 0, // CORREGIDO: Ahora es 0 en lugar de 'activo'
        'clicks' => 0
    ];
    
    echo "\nInsertando código con estado: " . $nuevo_codigo['estado'] . " (tipo: " . gettype($nuevo_codigo['estado']) . ")\n";
    
    // Insertar el código
    $result = $collection->insertOne($nuevo_codigo);
    
    if ($result->getInsertedId()) {
        $codigo_id = (string)$result->getInsertedId();
        echo "✓ Código insertado con ID: $codigo_id\n";
        
        // Verificar que el código se puede encontrar con los filtros del sistema
        $array_filtro = array("estado" => 0, "_id" => new MongoDB\BSON\ObjectId($codigo_id));
        $array_opciones = array('limit' => 1);
        
        $lista_codigos = get_all_listado_codigos_array($array_filtro, $array_opciones);
        
        if ($lista_codigos["total_number"] > 0) {
            echo "✅ CÓDIGO FUNCIONA CORRECTAMENTE\n";
            echo "- Estado: " . $lista_codigos["results"][0]["estado"] . "\n";
            echo "- Tipo de estado: " . gettype($lista_codigos["results"][0]["estado"]) . "\n";
            echo "- URL: /de-" . $datos_codigo['marca'] . "?codigo=" . $codigo_id . "\n";
        } else {
            echo "❌ ERROR: El código no se puede encontrar con los filtros del sistema\n";
        }
        
        // Limpiar el código de prueba
        echo "\nLimpiando código de prueba...\n";
        $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($codigo_id)]);
        echo "✓ Código de prueba eliminado\n";
        
    } else {
        echo "❌ ERROR: No se pudo insertar el código\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>

