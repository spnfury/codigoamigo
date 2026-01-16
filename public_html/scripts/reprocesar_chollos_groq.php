#!/usr/bin/env php
<?php
/**
 * Script para reprocesar todos los chollos existentes con Groq
 * Reescribe descripciones y recategoriza automáticamente
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../config/ai_config.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_groq.php';
require_once __DIR__ . '/../myphp/telegram_chollos_bot.php';

echo "=== REPROCESAMIENTO DE CHOLLOS CON GROQ ===\n\n";

// Obtener todos los chollos
$collection = getCollectionChollos();
if (!$collection) {
    echo "Error: No se pudo conectar a la base de datos\n";
    exit(1);
}

$chollos = $collection->find([]);
$total = $collection->countDocuments([]);

echo "Total de chollos a procesar: {$total}\n";
echo "Iniciando procesamiento...\n\n";

$procesados = 0;
$actualizados = 0;
$errores = 0;
$sin_cambios = 0;

foreach ($chollos as $chollo) {
    $procesados++;
    $chollo_id = (string)$chollo['_id'];
    $titulo = $chollo['titulo'] ?? '';
    $descripcion_actual = $chollo['descripcion'] ?? '';
    $categoria_actual = $chollo['categoria'] ?? 'general';
    
    // Construir texto completo para análisis
    $texto_completo = trim($titulo . "\n\n" . $descripcion_actual);
    
    if (empty($titulo)) {
        echo "[{$procesados}/{$total}] ⚠️  Chollo {$chollo_id}: Sin título, saltando...\n";
        $errores++;
        continue;
    }
    
    echo "[{$procesados}/{$total}] Procesando: " . substr($titulo, 0, 50) . "...\n";
    
    // Procesar con Groq (con reintentos para rate limit)
    $resultado = null;
    $intentos = 0;
    $max_intentos = 3;
    
    while ($intentos < $max_intentos) {
        $resultado = procesarCholloConGroq($titulo, $descripcion_actual, $texto_completo);
        
        if ($resultado['success']) {
            break; // Éxito, salir del bucle
        }
        
        // Si es rate limit, esperar y reintentar
        if (isset($resultado['error']) && strpos($resultado['error'], '429') !== false) {
            $intentos++;
            if ($intentos < $max_intentos) {
                $tiempo_espera = 5 * $intentos; // Esperar 5, 10, 15 segundos
                echo "  ⏳ Rate limit alcanzado, esperando {$tiempo_espera}s...\n";
                sleep($tiempo_espera);
                continue;
            }
        } else {
            // Otro tipo de error, no reintentar
            break;
        }
    }
    
    if (!$resultado || !$resultado['success']) {
        echo "  ❌ Error: " . ($resultado['error'] ?? 'Error desconocido') . "\n";
        $errores++;
        // Esperar un poco antes de continuar
        sleep(2);
        continue;
    }
    
    // Verificar si hay cambios
    $hay_cambios = false;
    $actualizaciones = [];
    
    // Actualizar descripción si hay cambios
    if (!empty($resultado['descripcion_reescrita']) && 
        $resultado['descripcion_reescrita'] !== $descripcion_actual) {
        $actualizaciones['descripcion'] = $resultado['descripcion_reescrita'];
        $actualizaciones['texto_reescrito'] = true;
        $hay_cambios = true;
        echo "  ✓ Descripción reescrita\n";
    }
    
    // Actualizar categoría si hay cambios
    if ($resultado['categoria'] !== 'general' && 
        $resultado['categoria'] !== $categoria_actual) {
        $actualizaciones['categoria'] = $resultado['categoria'];
        $hay_cambios = true;
        echo "  ✓ Categoría actualizada: {$categoria_actual} → {$resultado['categoria']}\n";
    }
    
    // Actualizar título si hay cambios
    if (!empty($resultado['titulo_reescrito']) && 
        $resultado['titulo_reescrito'] !== $titulo) {
        $actualizaciones['titulo'] = $resultado['titulo_reescrito'];
        $hay_cambios = true;
        echo "  ✓ Título mejorado\n";
    }
    
    if ($hay_cambios) {
        try {
            $objectId = new MongoDB\BSON\ObjectId($chollo_id);
            $collection->updateOne(
                ['_id' => $objectId],
                ['$set' => $actualizaciones]
            );
            $actualizados++;
            echo "  ✅ Chollo actualizado\n";
        } catch (Exception $e) {
            echo "  ❌ Error al actualizar: " . $e->getMessage() . "\n";
            $errores++;
        }
    } else {
        $sin_cambios++;
        echo "  ⏭️  Sin cambios necesarios\n";
    }
    
    echo "\n";
    
    // Pausa más larga para evitar rate limits
    sleep(2); // 2 segundos entre peticiones
}

echo "\n=== RESUMEN ===\n";
echo "Total procesados: {$procesados}\n";
echo "Actualizados: {$actualizados}\n";
echo "Sin cambios: {$sin_cambios}\n";
echo "Errores: {$errores}\n";
echo "\n✅ Procesamiento completado\n";

