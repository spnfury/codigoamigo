<?php
/**
 * Script para marcar marcas como destacadas en la home
 * Uso: php marcar_marca_destacada.php nombre_clave_marca
 * Ejemplo: php marcar_marca_destacada.php split
 */

require_once __DIR__ . '/../inc/conexion.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_marca.php';

if (php_sapi_name() !== 'cli' && !isset($_GET['marca'])) {
    die('Este script debe ejecutarse desde la línea de comandos o con parámetro GET marca');
}

// Obtener nombre de la marca
$nombre_clave = isset($_GET['marca']) ? $_GET['marca'] : ($argv[1] ?? '');

if (empty($nombre_clave)) {
    die("Error: Debes proporcionar el nombre_clave de la marca\nEjemplo: ?marca=split\n");
}

$collection_marcas = getCollectionMarcas();

// Buscar la marca
$marca = $collection_marcas->findOne(['nombre_clave' => $nombre_clave]);

if (!$marca) {
    die("Error: No se encontró la marca con nombre_clave: {$nombre_clave}\n");
}

// Marcar como destacada
$result = $collection_marcas->updateOne(
    ['_id' => $marca['_id']],
    [
        '$set' => [
            'destacada_home' => true,
            'fecha_destacada' => new MongoDB\BSON\UTCDateTime()
        ]
    ]
);

if ($result->getModifiedCount() > 0) {
    echo "✓ Marca '{$marca['nombre']}' marcada como destacada correctamente\n";
    
    // Si no tiene ventajas, generarlas
    if (empty($marca['ventajas_principales'])) {
        require_once __DIR__ . '/../myphp/funciones_modern.php';
        $marca_array = iterator_to_array($marca);
        $ventajas = generate_brand_advantages_with_ai($marca_array);
        
        if (!empty($ventajas)) {
            $collection_marcas->updateOne(
                ['_id' => $marca['_id']],
                ['$set' => ['ventajas_principales' => $ventajas]]
            );
            echo "✓ Ventajas principales generadas:\n";
            foreach ($ventajas as $i => $ventaja) {
                echo "  " . ($i + 1) . ". {$ventaja}\n";
            }
        }
    } else {
        echo "✓ La marca ya tiene ventajas principales definidas\n";
    }
} else {
    echo "⚠ La marca ya estaba marcada como destacada\n";
}

echo "\nPara ver todas las marcas destacadas, ejecuta:\n";
echo "db.marcas.find({destacada_home: true}).pretty()\n";
?>

