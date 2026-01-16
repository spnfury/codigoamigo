<?php

/**
 * Script para reasignar chollos de Black Friday a categorías apropiadas
 * 
 * Este script:
 * 1. Busca todos los chollos con categoría "black-friday"
 * 2. Usa Groq AI para determinar la categoría correcta basándose en el contenido
 * 3. Actualiza el chollo eliminando "black-friday" y asignando la nueva categoría
 */

// Incluir funciones necesarias
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_groq.php';

echo "=== REASIGNACIÓN DE CHOLLOS BLACK FRIDAY ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Obtener colección de chollos
$collection = getCollectionChollos();
if (!$collection) {
    die("Error: No se pudo conectar a la base de datos\n");
}

// Buscar todos los chollos que tienen "black-friday" en su categoría
echo "Buscando chollos con categoría 'black-friday'...\n";

$query = [
    'categoria' => ['$in' => ['black-friday']]
];

$total_chollos = $collection->countDocuments($query);
echo "Total de chollos encontrados: {$total_chollos}\n\n";

if ($total_chollos == 0) {
    echo "No hay chollos de Black Friday para reasignar.\n";
    exit(0);
}

// Procesar chollos en lotes
$limite = 2000;
$procesados = 0;
$actualizados = 0;
$errores = 0;

$cursor = $collection->find($query, [
    'limit' => $limite,
    'sort' => ['fecha_creacion' => -1]
]);

echo "Procesando chollos...\n";
echo str_repeat('-', 80) . "\n";

foreach ($cursor as $doc) {
    $procesados++;
    $chollo_id = (string)$doc['_id'];
    
    // Obtener datos del chollo
    $titulo = $doc['titulo'] ?? '';
    $descripcion = $doc['descripcion'] ?? '';
    $categoria_actual = $doc['categoria'] ?? [];
    
    // Convertir categoría a array si es necesario
    if (is_object($categoria_actual)) {
        if (method_exists($categoria_actual, 'toArray')) {
            $categoria_actual = $categoria_actual->toArray();
        } else {
            $categoria_actual = (array)$categoria_actual;
        }
    }
    if (!is_array($categoria_actual)) {
        $categoria_actual = [$categoria_actual];
    }
    
    // Convertir elementos a string
    $categoria_actual = array_map(function($cat) {
        if (is_object($cat)) {
            return (string)$cat;
        }
        return $cat;
    }, $categoria_actual);
    
    echo "\n[{$procesados}/{$total_chollos}] ID: {$chollo_id}\n";
    echo "Título: " . substr($titulo, 0, 60) . (strlen($titulo) > 60 ? '...' : '') . "\n";
    echo "Categorías actuales: " . implode(', ', $categoria_actual) . "\n";
    
    // Usar Groq para determinar la categoría correcta
    // Nota: La función procesarCholloConGroq ya no incluirá "black-friday" después de actualizar
    $resultado_groq = procesarCholloConGroq($titulo, $descripcion);
    
    if ($resultado_groq['success'] && !empty($resultado_groq['categoria'])) {
        $nuevas_categorias = $resultado_groq['categoria'];
        
        // Eliminar "black-friday" de las nuevas categorías si está presente
        $nuevas_categorias = array_filter($nuevas_categorias, function($cat) {
            return $cat !== 'black-friday';
        });
        
        // Si no quedó ninguna categoría, usar 'general'
        if (empty($nuevas_categorias)) {
            $nuevas_categorias = ['general'];
        }
        
        // Reindexar array
        $nuevas_categorias = array_values($nuevas_categorias);
        
        echo "Nuevas categorías: " . implode(', ', $nuevas_categorias) . "\n";
        
        // Actualizar el chollo
        try {
            $objectId = new MongoDB\BSON\ObjectId($chollo_id);
            $resultado_update = $collection->updateOne(
                ['_id' => $objectId],
                [
                    '$set' => [
                        'categoria' => $nuevas_categorias,
                        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($resultado_update->getModifiedCount() > 0) {
                echo "✓ Actualizado correctamente\n";
                $actualizados++;
            } else {
                echo "⚠ No se modificó (posiblemente ya tenía esas categorías)\n";
            }
        } catch (Exception $e) {
            echo "✗ Error al actualizar: " . $e->getMessage() . "\n";
            $errores++;
        }
    } else {
        // Si Groq falla, asignar categoría basándose en palabras clave
        echo "⚠ Groq no pudo categorizar, usando detección por palabras clave...\n";
        
        $texto_completo = strtolower($titulo . ' ' . $descripcion);
        $nueva_categoria = 'general';
        
        // Detectar categoría por palabras clave
        if (preg_match('/\b(iphone|samsung|android|smartphone|móvil|tablet|portátil|laptop|auriculares|tv|televisor|monitor|smartwatch)\b/i', $texto_completo)) {
            $nueva_categoria = 'electronica';
        } elseif (preg_match('/\b(playstation|ps5|ps4|xbox|nintendo|switch|videojuego|gaming|gamer|consola)\b/i', $texto_completo)) {
            $nueva_categoria = 'videojuegos';
        } elseif (preg_match('/\b(zapatos|ropa|camiseta|pantalón|vestido|chaqueta|bolso|mochila|perfume|moda)\b/i', $texto_completo)) {
            $nueva_categoria = 'moda';
        } elseif (preg_match('/\b(mueble|sofá|mesa|silla|cama|colchón|cocina|nevera|lavadora|hogar|casa)\b/i', $texto_completo)) {
            $nueva_categoria = 'hogar';
        } elseif (preg_match('/\b(deporte|gimnasio|running|fútbol|baloncesto|tenis|bicicleta|zapatillas deportivas)\b/i', $texto_completo)) {
            $nueva_categoria = 'deportes';
        } elseif (preg_match('/\b(libro|ebook|kindle|lectura|novela)\b/i', $texto_completo)) {
            $nueva_categoria = 'libros';
        } elseif (preg_match('/\b(amazon)\b/i', $texto_completo) || preg_match('/amazon/i', $doc['enlace'] ?? '')) {
            $nueva_categoria = 'amazon';
        }
        
        $nuevas_categorias = [$nueva_categoria];
        echo "Categoría detectada: {$nueva_categoria}\n";
        
        // Actualizar el chollo
        try {
            $objectId = new MongoDB\BSON\ObjectId($chollo_id);
            $resultado_update = $collection->updateOne(
                ['_id' => $objectId],
                [
                    '$set' => [
                        'categoria' => $nuevas_categorias,
                        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($resultado_update->getModifiedCount() > 0) {
                echo "✓ Actualizado correctamente\n";
                $actualizados++;
            } else {
                echo "⚠ No se modificó\n";
            }
        } catch (Exception $e) {
            echo "✗ Error al actualizar: " . $e->getMessage() . "\n";
            $errores++;
        }
    }
    
    // Pequeña pausa para no saturar la API de Groq
    if ($resultado_groq['success']) {
        usleep(500000); // 0.5 segundos
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "RESUMEN:\n";
echo "- Total procesados: {$procesados}\n";
echo "- Actualizados correctamente: {$actualizados}\n";
echo "- Errores: {$errores}\n";
echo "- Sin cambios: " . ($procesados - $actualizados - $errores) . "\n";
echo "\nFinalizado: " . date('Y-m-d H:i:s') . "\n";
