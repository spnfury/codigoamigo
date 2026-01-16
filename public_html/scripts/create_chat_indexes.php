<?php
/**
 * Script para crear índices MongoDB para optimizar búsquedas de chat
 * Ejecutar una vez: php create_chat_indexes.php
 */

require_once __DIR__ . '/../inc/includes.php';

try {
    $collection_mensajes = getCollectionMensajes();
    
    if (!$collection_mensajes) {
        echo "Error: No se pudo obtener la colección de mensajes\n";
        exit(1);
    }
    
    echo "Creando índices para la colección 'mensajes'...\n\n";
    
    // Índice para conversacion_id (búsqueda de mensajes en conversación)
    echo "1. Creando índice en 'conversacion_id'...\n";
    $collection_mensajes->createIndex(['conversacion_id' => 1]);
    echo "   ✓ Índice creado\n\n";
    
    // Índice compuesto para búsqueda de mensajes por conversación y fecha
    echo "2. Creando índice compuesto en 'conversacion_id' y 'fecha'...\n";
    $collection_mensajes->createIndex([
        'conversacion_id' => 1,
        'fecha' => -1
    ]);
    echo "   ✓ Índice creado\n\n";
    
    // Índice para búsqueda de texto en mensajes
    echo "3. Creando índice de texto en 'mensaje'...\n";
    $collection_mensajes->createIndex(['mensaje' => 'text']);
    echo "   ✓ Índice creado\n\n";
    
    // Índice para usuarios (de_usuario_id y para_usuario_id)
    echo "4. Creando índice en 'de_usuario_id'...\n";
    $collection_mensajes->createIndex(['de_usuario_id' => 1]);
    echo "   ✓ Índice creado\n\n";
    
    echo "5. Creando índice en 'para_usuario_id'...\n";
    $collection_mensajes->createIndex(['para_usuario_id' => 1]);
    echo "   ✓ Índice creado\n\n";
    
    // Índice compuesto para obtener conversaciones de un usuario
    echo "6. Creando índice compuesto para consultas de conversaciones...\n";
    $collection_mensajes->createIndex([
        'de_usuario_id' => 1,
        'fecha' => -1
    ]);
    $collection_mensajes->createIndex([
        'para_usuario_id' => 1,
        'fecha' => -1
    ]);
    echo "   ✓ Índices creados\n\n";
    
    // Índice para mensajes no leídos
    echo "7. Creando índice en 'leido' y 'para_usuario_id'...\n";
    $collection_mensajes->createIndex([
        'para_usuario_id' => 1,
        'leido' => 1,
        'fecha' => -1
    ]);
    echo "   ✓ Índice creado\n\n";
    
    echo "✓ Todos los índices creados correctamente\n";
    echo "\nPara verificar los índices, ejecuta en MongoDB:\n";
    echo "db.mensajes.getIndexes()\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

