<?php
/**
 * Script para crear índices en MongoDB para las colecciones de votos y favoritos
 * Ejecutar una vez para optimizar las consultas
 */

require_once __DIR__ . '/../inc/conexion.php';
require_once __DIR__ . '/../myphp/funciones_codigo.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

echo "=== Creando índices para votos y favoritos ===\n\n";

try {
    $db = createConnection();
    
    // Índices para la colección de votos
    echo "1. Creando índices para colección 'votos'...\n";
    $collection_votos = $db->selectCollection('votos');
    
    // Índice único compuesto para evitar votos duplicados
    $collection_votos->createIndex(
        ['usuario_id' => 1, 'codigo_id' => 1],
        ['unique' => true, 'name' => 'usuario_codigo_unique']
    );
    echo "   ✓ Índice único (usuario_id, codigo_id) creado\n";
    
    // Índice para buscar votos por usuario
    $collection_votos->createIndex(
        ['usuario_id' => 1],
        ['name' => 'usuario_id_idx']
    );
    echo "   ✓ Índice en usuario_id creado\n";
    
    // Índice para buscar votos por código
    $collection_votos->createIndex(
        ['codigo_id' => 1],
        ['name' => 'codigo_id_idx']
    );
    echo "   ✓ Índice en codigo_id creado\n";
    
    // Índice para ordenar por fecha
    $collection_votos->createIndex(
        ['created_at' => -1],
        ['name' => 'created_at_idx']
    );
    echo "   ✓ Índice en created_at creado\n";
    
    echo "\n";
    
    // Índices para la colección de favoritos
    echo "2. Creando índices para colección 'favoritos'...\n";
    $collection_favoritos = $db->selectCollection('favoritos');
    
    // Índice único compuesto para evitar favoritos duplicados
    $collection_favoritos->createIndex(
        ['usuario_id' => 1, 'codigo_id' => 1],
        ['unique' => true, 'name' => 'usuario_codigo_unique']
    );
    echo "   ✓ Índice único (usuario_id, codigo_id) creado\n";
    
    // Índice para buscar favoritos por usuario
    $collection_favoritos->createIndex(
        ['usuario_id' => 1, 'created_at' => -1],
        ['name' => 'usuario_created_idx']
    );
    echo "   ✓ Índice compuesto (usuario_id, created_at) creado\n";
    
    // Índice para buscar favoritos por código
    $collection_favoritos->createIndex(
        ['codigo_id' => 1],
        ['name' => 'codigo_id_idx']
    );
    echo "   ✓ Índice en codigo_id creado\n";
    
    echo "\n";
    echo "=== Índices creados exitosamente ===\n";
    echo "\n";
    echo "Índices creados:\n";
    echo "- votos: usuario_codigo_unique (único), usuario_id_idx, codigo_id_idx, created_at_idx\n";
    echo "- favoritos: usuario_codigo_unique (único), usuario_created_idx, codigo_id_idx\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Algunos índices pueden ya existir, esto es normal.\n";
}

?>

