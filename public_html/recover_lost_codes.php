<?php
// Script para recuperar códigos perdidos por cambios en nombre_clave de marcas
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>Recuperación de Códigos Perdidos</h1>";
echo "<p>Este script busca códigos que pueden haberse perdido por cambios en nombre_clave de marcas.</p>";

try {
    $collection_codigos = getCollectionCodigos();
    $collection_marcas = getCollectionMarcas();
    
    // Obtener todas las marcas
    $marcas = $collection_marcas->find([])->toArray();
    
    echo "<h2>Marcas encontradas: " . count($marcas) . "</h2>";
    
    $codigos_recuperados = 0;
    $problemas_encontrados = 0;
    
    foreach ($marcas as $marca) {
        $nombre_clave = $marca['nombre_clave'];
        $nombre = $marca['nombre'];
        
        // Buscar códigos que usen el nombre_clave actual
        $codigos_marca = $collection_codigos->countDocuments(['marca' => $nombre_clave]);
        
        // Buscar códigos que usen el nombre (posible problema)
        $codigos_nombre = $collection_codigos->countDocuments(['marca' => strtolower($nombre)]);
        
        // Buscar códigos que usen variaciones del nombre
        $codigos_variaciones = $collection_codigos->find([
            'marca' => [
                '$regex' => preg_quote(strtolower($nombre), '/'),
                '$options' => 'i'
            ]
        ])->toArray();
        
        if ($codigos_marca == 0 && ($codigos_nombre > 0 || count($codigos_variaciones) > 0)) {
            echo "<div style='border: 1px solid #ff6b35; padding: 10px; margin: 10px 0; background: #fff5f2;'>";
            echo "<h3>🔍 Problema detectado en marca: " . htmlspecialchars($nombre) . "</h3>";
            echo "<p><strong>Nombre clave actual:</strong> " . htmlspecialchars($nombre_clave) . "</p>";
            echo "<p><strong>Códigos con nombre_clave actual:</strong> " . $codigos_marca . "</p>";
            echo "<p><strong>Códigos con nombre:</strong> " . $codigos_nombre . "</p>";
            echo "<p><strong>Códigos con variaciones:</strong> " . count($codigos_variaciones) . "</p>";
            
            // Mostrar códigos problemáticos
            if (count($codigos_variaciones) > 0) {
                echo "<h4>Códigos encontrados con variaciones:</h4>";
                echo "<ul>";
                foreach ($codigos_variaciones as $codigo) {
                    echo "<li>ID: " . $codigo['_id'] . " - Marca: " . htmlspecialchars($codigo['marca']) . "</li>";
                }
                echo "</ul>";
                
                // Preguntar si corregir
                echo "<form method='POST' style='margin: 10px 0;'>";
                echo "<input type='hidden' name='action' value='fix_marca'>";
                echo "<input type='hidden' name='marca_id' value='" . $marca['_id'] . "'>";
                echo "<input type='hidden' name='nombre_clave_correcto' value='" . htmlspecialchars($nombre_clave) . "'>";
                echo "<button type='submit' style='background: #28a745; color: white; padding: 5px 10px; border: none; border-radius: 3px;'>";
                echo "Corregir códigos asociados";
                echo "</button>";
                echo "</form>";
            }
            
            echo "</div>";
            $problemas_encontrados++;
        }
    }
    
    // Procesar corrección si se envió el formulario
    if ($_POST['action'] === 'fix_marca') {
        $marca_id = $_POST['marca_id'];
        $nombre_clave_correcto = $_POST['nombre_clave_correcto'];
        
        // Obtener la marca
        $marca = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_id)]);
        
        if ($marca) {
            // Buscar códigos con variaciones del nombre
            $codigos_problematicos = $collection_codigos->find([
                'marca' => [
                    '$regex' => preg_quote(strtolower($marca['nombre']), '/'),
                    '$options' => 'i'
                ]
            ])->toArray();
            
            $corregidos = 0;
            foreach ($codigos_problematicos as $codigo) {
                $result = $collection_codigos->updateOne(
                    ['_id' => $codigo['_id']],
                    ['$set' => ['marca' => $nombre_clave_correcto]]
                );
                if ($result->getModifiedCount() > 0) {
                    $corregidos++;
                }
            }
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0;'>";
            echo "<h3>✅ Corrección completada</h3>";
            echo "<p><strong>Marca:</strong> " . htmlspecialchars($marca['nombre']) . "</p>";
            echo "<p><strong>Códigos corregidos:</strong> " . $corregidos . "</p>";
            echo "<p><strong>Nuevo nombre_clave:</strong> " . htmlspecialchars($nombre_clave_correcto) . "</p>";
            echo "</div>";
            
            $codigos_recuperados += $corregidos;
        }
    }
    
    echo "<h2>📊 Resumen</h2>";
    echo "<ul>";
    echo "<li><strong>Marcas analizadas:</strong> " . count($marcas) . "</li>";
    echo "<li><strong>Problemas encontrados:</strong> " . $problemas_encontrados . "</li>";
    echo "<li><strong>Códigos recuperados:</strong> " . $codigos_recuperados . "</li>";
    echo "</ul>";
    
    if ($problemas_encontrados == 0) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0;'>";
        echo "<h3>🎉 ¡Excelente!</h3>";
        echo "<p>No se encontraron problemas con códigos perdidos. Todas las marcas tienen sus códigos correctamente asociados.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Nota:</strong> Este script es seguro de ejecutar. Solo lee datos y permite corregir problemas manualmente.</p>";
echo "<p><strong>Fecha de ejecución:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>


