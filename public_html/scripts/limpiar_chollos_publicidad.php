<?php
/**
 * Script para limpiar chollos antiguos que contienen #Publicidad
 * en el título o descripción
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_groq.php';

// Conectar a MongoDB
$collection = getCollectionChollos();
if (!$collection) {
    die("Error: No se pudo conectar a MongoDB\n");
}

echo "🔍 Buscando chollos con #Publicidad o enlaces markdown...\n\n";

// Buscar chollos que contengan #Publicidad o enlaces markdown truncados en título o descripción
$query = [
    '$or' => [
        ['titulo' => ['$regex' => '#[Pp]ublicidad', '$options' => 'i']],
        ['descripcion' => ['$regex' => '#[Pp]ublicidad', '$options' => 'i']],
        ['descripcion' => ['$regex' => 'Enlace:.*\[https?://', '$options' => 'i']],
        ['descripcion' => ['$regex' => 'Link:.*\[https?://', '$options' => 'i']],
        ['descripcion' => ['$regex' => '\[https?://[^\]]*\.\.\.[^\]]*\]', '$options' => 'i']]
    ]
];

$cursor = $collection->find($query);
$chollos_encontrados = [];
foreach ($cursor as $doc) {
    $chollos_encontrados[] = $doc;
}

$total = count($chollos_encontrados);
echo "📊 Encontrados {$total} chollos con #Publicidad\n\n";

if ($total == 0) {
    echo "✅ No hay chollos para limpiar\n";
    exit(0);
}

$actualizados = 0;
$errores = 0;

foreach ($chollos_encontrados as $doc) {
    $id = (string)$doc['_id'];
    $titulo_original = $doc['titulo'] ?? '';
    $descripcion_original = $doc['descripcion'] ?? '';
    
    $titulo_limpio = $titulo_original;
    $descripcion_limpia = $descripcion_original;
    
    // Limpiar título
    $titulo_limpio = preg_replace('/#Publicidad\b/i', '', $titulo_limpio);
    $titulo_limpio = preg_replace('/#\s*Publicidad\b/i', '', $titulo_limpio);
    $titulo_limpio = preg_replace('/\b#Publicidad\b/i', '', $titulo_limpio);
    $titulo_limpio = preg_replace('/\s*#\s*[Pp]ublicidad\b/i', '', $titulo_limpio);
    $titulo_limpio = preg_replace('/#\w*[Pp]ublicidad\w*/i', '', $titulo_limpio);
    $titulo_limpio = preg_replace('/@\w+\s*/i', '', $titulo_limpio);
    $titulo_limpio = preg_replace('/#\w*canal\w*/i', '', $titulo_limpio);
    $titulo_limpio = preg_replace('/\s+/', ' ', $titulo_limpio);
    $titulo_limpio = trim($titulo_limpio);
    
    // Limpiar descripción
    $descripcion_limpia = preg_replace('/#Publicidad\b/i', '', $descripcion_limpia);
    $descripcion_limpia = preg_replace('/#\s*Publicidad\b/i', '', $descripcion_limpia);
    $descripcion_limpia = preg_replace('/\b#Publicidad\b/i', '', $descripcion_limpia);
    $descripcion_limpia = preg_replace('/\s*#\s*[Pp]ublicidad\b/i', '', $descripcion_limpia);
    $descripcion_limpia = preg_replace('/#\w*[Pp]ublicidad\w*/i', '', $descripcion_limpia);
    $descripcion_limpia = preg_replace('/@\w+\s*/i', '', $descripcion_limpia);
    $descripcion_limpia = preg_replace('/#\w*canal\w*/i', '', $descripcion_limpia);
    
    // Eliminar líneas que empiezan con "Enlace:", "Link:", "URL:", etc.
    $lineas_desc = explode("\n", $descripcion_limpia);
    $lineas_limpias = [];
    foreach ($lineas_desc as $linea) {
        $linea_trim = trim($linea);
        // Saltar líneas que empiezan con palabras clave de enlaces
        if (preg_match('/^(enlace|link|url|comprar|oferta)[:：]\s*/i', $linea_trim)) {
            continue;
        }
        // Eliminar enlaces markdown truncados como "[https://amzn.to/......](url)"
        $linea_trim = preg_replace('/\[https?:\/\/[^\]]*\.\.\.[^\]]*\]\([^\)]+\)/i', '', $linea_trim);
        $linea_trim = preg_replace('/\[https?:\/\/[^\]]*\]\([^\)]+\)/i', '', $linea_trim); // Eliminar cualquier enlace markdown
        // Eliminar referencias a enlaces después de "Enlace:" o "Link:"
        $linea_trim = preg_replace('/.*(?:enlace|link|url)[:：].*/i', '', $linea_trim);
        if (!empty(trim($linea_trim))) {
            $lineas_limpias[] = $linea_trim;
        }
    }
    $descripcion_limpia = implode("\n", $lineas_limpias);
    
    // Eliminar enlaces markdown que puedan quedar en el texto
    $descripcion_limpia = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $descripcion_limpia); // Convertir [texto](url) a texto
    $descripcion_limpia = preg_replace('/https?:\/\/[^\s<>"\'\)]+/i', '', $descripcion_limpia); // Eliminar URLs directas
    $descripcion_limpia = preg_replace('/\s+/', ' ', $descripcion_limpia);
    $descripcion_limpia = trim($descripcion_limpia);
    
    // Solo actualizar si hubo cambios
    if ($titulo_limpio !== $titulo_original || $descripcion_limpia !== $descripcion_original) {
        try {
            $objectId = new MongoDB\BSON\ObjectId($id);
            $update = [
                '$set' => [
                    'titulo' => $titulo_limpio,
                    'descripcion' => $descripcion_limpia
                ]
            ];
            
            $result = $collection->updateOne(
                ['_id' => $objectId],
                $update
            );
            
            if ($result->getModifiedCount() > 0) {
                $actualizados++;
                echo "✅ Actualizado: {$id}\n";
                if ($titulo_limpio !== $titulo_original) {
                    echo "   Título: '{$titulo_original}' → '{$titulo_limpio}'\n";
                }
                if ($descripcion_limpia !== $descripcion_original) {
                    echo "   Descripción limpiada\n";
                }
            } else {
                $errores++;
                echo "⚠️  No se pudo actualizar: {$id}\n";
            }
        } catch (Exception $e) {
            $errores++;
            echo "❌ Error actualizando {$id}: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⏭️  Sin cambios: {$id}\n";
    }
}

echo "\n";
echo "📊 Resumen:\n";
echo "   Total encontrados: {$total}\n";
echo "   Actualizados: {$actualizados}\n";
echo "   Errores: {$errores}\n";
echo "   Sin cambios: " . ($total - $actualizados - $errores) . "\n";
echo "\n✅ Proceso completado\n";

