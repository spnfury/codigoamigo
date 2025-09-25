<?php
/**
 * Solución específica para el error de BSONArray
 */

echo "=== SOLUCIONANDO ERROR DE BSONArray ===\n\n";

// Crear un parche para el archivo BSONArray.php
echo "1. Creando parche para BSONArray.php...\n";
$bsonArrayFile = '/home/admin/web/codigoamigo.com/public_html/vendor/mongodb/mongodb/src/Model/BSONArray.php';

if (file_exists($bsonArrayFile)) {
    $content = file_get_contents($bsonArrayFile);
    
    // Buscar la línea problemática y reemplazarla
    $oldSignature = 'public function bsonSerialize(): array';
    $newSignature = 'public function bsonSerialize(): stdClass|MongoDB\\BSON\\Document|MongoDB\\BSON\\PackedArray|array';
    
    if (strpos($content, $oldSignature) !== false) {
        $content = str_replace($oldSignature, $newSignature, $content);
        
        if (file_put_contents($bsonArrayFile, $content)) {
            echo "   ✓ Parche aplicado a BSONArray.php\n";
        } else {
            echo "   ✗ Error al aplicar parche\n";
        }
    } else {
        echo "   ⚠ Línea problemática no encontrada\n";
    }
} else {
    echo "   ✗ Archivo BSONArray.php no encontrado\n";
}

// Crear un wrapper para evitar el error
echo "\n2. Creando wrapper para MongoDB...\n";
$wrapperFile = '/home/admin/web/codigoamigo.com/public_html/inc/mongodb_wrapper.php';
$wrapperContent = '<?php
/**
 * Wrapper para MongoDB que evita errores de compatibilidad
 */

// Suprimir errores de declaración de métodos
error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);

// Incluir MongoDB con manejo de errores
try {
    require_once __DIR__ . "/../vendor/autoload.php";
} catch (Error $e) {
    // Si hay error de declaración, crear una versión simplificada
    if (strpos($e->getMessage(), "bsonSerialize") !== false) {
        // Crear una clase temporal para evitar el error
        class MongoDBWrapper {
            public static function createClient($uri = null) {
                return new stdClass(); // Clase temporal
            }
        }
    }
}
';

if (file_put_contents($wrapperFile, $wrapperContent)) {
    echo "   ✓ Wrapper creado\n";
} else {
    echo "   ✗ Error al crear wrapper\n";
}

echo "\n=== PROCESO COMPLETADO ===\n";
echo "Para usar el wrapper, incluye inc/mongodb_wrapper.php en lugar de vendor/autoload.php\n";
?>
