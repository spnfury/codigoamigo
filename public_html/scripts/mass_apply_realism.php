<?php
/**
 * Script MASIVO para aplicar el nuevo realismo a todos los chollos activos.
 * - Elimina comentarios fake antiguos (genéricos).
 * - Genera 2-3 hilos de conversación con personalidades y storytelling.
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';
require_once __DIR__ . '/../cron/cron_fake_comments.php'; 

// Aumentar límites para script masivo
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "--- INICIANDO ACTUALIZACIÓN MASIVA DE REALISMO ---\n";

$collection_chollos = getCollectionChollos();
$collection_comentarios = getCollectionCholloComentarios();
$collection_usuarios = getCollectionUsuarios();

// Obtener usuarios fake una sola vez
$usuarios_fake = $collection_usuarios->find(['tipo' => 'fake', 'estado' => 1])->toArray();
if (empty($usuarios_fake)) die("❌ Error: No hay usuarios fake disponibles.\n");
$fake_ids = array_map(function($u){ return $u['_id']; }, $usuarios_fake);

// Obtener Chollos Activos (Últimos 100 para no saturar de golpe, o filtrar por fecha)
// Vamos a procesar los activos ordenados por fecha recientes
$cursor = $collection_chollos->find(
    ['estado' => 1], 
    ['sort' => ['fecha_creacion' => -1], 'limit' => 50] // Empezamos con 50 para probar
);
$chollos = iterator_to_array($cursor);

echo "🎯 Se han encontrado " . count($chollos) . " chollos activos para procesar.\n";

foreach ($chollos as $index => $chollo) {
    $chollo_id = (string)$chollo['_id'];
    echo "\n[" . ($index + 1) . "] Procesando: " . $chollo['titulo'] . "\n";

    // 1. Limpieza de comentarios fake antiguos (para que no se mezclen los robots tontos con los listos)
    $deleteResult = $collection_comentarios->deleteMany([
        'chollo_id' => new MongoDB\BSON\ObjectId($chollo_id),
        'usuario_id' => ['$in' => $fake_ids]
    ]);
    if ($deleteResult->getDeletedCount() > 0) {
        echo "   🧹 Limpiados " . $deleteResult->getDeletedCount() . " comentarios antiguos.\n";
    }

    // 2. Generación de Hilos Realistas
    // Detectar contexto
    $marca = detectarMarca($chollo['titulo']);
    $tipo = detectarTipo($chollo['titulo']);
    $nombre = limpiarTituloProducto($chollo['titulo']);
    
    // Decidir cuántos hilos crear (entre 2 y 4 para que parezca vivo)
    $num_hilos = rand(2, 4);
    $used_texts = []; // Para no repetir frases en el mismo chollo

    for ($i = 0; $i < $num_hilos; $i++) {
        // A. Raíz
        $cat_exists = isset($COMENTARIOS_CATEGORIA[$tipo]) && $tipo !== 'generic';
        
        // Priorizar categoría específica en el primer hilo
        if ($i === 0 && $cat_exists) {
            $banco_raiz = $COMENTARIOS_CATEGORIA[$tipo];
        } else {
            // Mezclar bancos (específico + genérico + situacionales)
            $banco_raiz = $COMENTARIOS_CATEGORIA['generic'];
            if ($cat_exists) {
                $banco_raiz = array_merge($banco_raiz, $COMENTARIOS_CATEGORIA[$tipo]);
            }
            // Añadir situacionales con baja probabilidad
            if (rand(1, 100) <= 30) {
                $banco_raiz = array_merge($banco_raiz, $COMENTARIOS_CATEGORIA['situacional_familia'], $COMENTARIOS_CATEGORIA['situacional_trabajo'], $COMENTARIOS_CATEGORIA['situacional_estudios']);
            }
        }
        
        shuffle($banco_raiz);
        
        $texto_raiz = null;
        foreach($banco_raiz as $raw) {
            // Procesar placeholder YA APLICA PERSONALIDAD
            $candidate = procesarPlaceholder($raw, $marca, $tipo, $nombre);
            if ($candidate && !in_array($candidate, $used_texts)) {
                // Si es el hilo principal (0) y tenemos categoría, intentamos forzar pregunta
                if ($i === 0 && $cat_exists) {
                    $es_pregunta = (strpos($candidate, '?') !== false || strpos($candidate, '¿') !== false);
                    if ($es_pregunta) {
                        $texto_raiz = $candidate;
                        $used_texts[] = $candidate;
                        break;
                    }
                } else {
                    $texto_raiz = $candidate;
                    $used_texts[] = $candidate;
                    break;
                }
            }
        }
        
        if (!$texto_raiz) continue; // Si no hay texto, saltamos hilo

        // Crear Comentario Raíz
        $u_raiz = $usuarios_fake[array_rand($usuarios_fake)];
        // Distribuir timestamps para que no sean todos "ahora mismo"
        // Hacemos que parezca que pasaron en las últimas 24h
        $fake_time = time() - rand(60, 86400); 
        
        // Insertamos manualmente para poder trucar la fecha si quisiéramos (aunque usaré crearComentario y luego update si es necesario, 
        // pero crearComentario usa NOW(). Para simplificar, dejaremos NOW() o podríamos modificar la función.
        // Por ahora usamos crearComentario normal, parecerán recientes, lo cual está bien para "actividad actual".
        
        $res_raiz = crearComentario([
            'chollo_id' => $chollo_id,
            'usuario_id' => (string)$u_raiz['_id'],
            'comentario' => $texto_raiz
        ]);

        if ($res_raiz['success']) {
            $raiz_id = $res_raiz['id'];
            echo "   ✅ Hilo $i: \"$texto_raiz\"\n";
            
            // B. Respuesta (Alta probabilidad: 80%)
            if (rand(1, 100) <= 80) {
                $u_resp = $usuarios_fake[array_rand($usuarios_fake)];
                while ((string)$u_resp['_id'] === (string)$u_raiz['_id']) {
                    $u_resp = $usuarios_fake[array_rand($usuarios_fake)];
                }
                
                $respuesta = null;
                // Lógica de respuesta inteligente (Keywords)
                $keys_to_check = array_keys($RESPUESTAS_TECNICAS);
                foreach ($keys_to_check as $key) {
                    if (stripos($texto_raiz, $key) !== false) {
                        $banco_key = $RESPUESTAS_TECNICAS[$key];
                        shuffle($banco_key);
                        foreach($banco_key as $rk) {
                            $rk_proc = procesarPlaceholder($rk, $marca, $tipo, $nombre);
                            if ($rk_proc && !in_array($rk_proc, $used_texts)) {
                                $respuesta = $rk_proc;
                                $used_texts[] = $rk_proc;
                                break;
                            }
                        }
                        if ($respuesta) break;
                    }
                }
                
                // Si no hay respuesta técnica, una situacional o genérica
                if (!$respuesta) {
                    $banco_resp = $COMENTARIOS_CATEGORIA['generic']; // Fallback
                    if (rand(1,100) <= 40) $banco_resp = $COMENTARIOS_CATEGORIA['situacional_familia']; // Respuesta tipo "pues a mi hijo..."
                    
                    shuffle($banco_resp);
                    foreach($banco_resp as $rb) {
                         $rb_proc = procesarPlaceholder($rb, $marca, $tipo, $nombre);
                         if ($rb_proc && !in_array($rb_proc, $used_texts)) {
                             $respuesta = $rb_proc;
                             $used_texts[] = $rb_proc;
                             break;
                         }
                    }
                }
                
                if ($respuesta) {
                    $res_resp = crearComentario([
                        'chollo_id' => $chollo_id,
                        'usuario_id' => (string)$u_resp['_id'],
                        'comentario' => $respuesta,
                        'padre_id' => $raiz_id
                    ]);
                    if ($res_resp['success']) echo "      ↪️ Resp 1: \"$respuesta\"\n";
                    
                    // C. Segunda respuesta (Conversación de 3) - 30% prob
                    if (rand(1, 100) <= 30) {
                         $u_resp2 = $usuarios_fake[array_rand($usuarios_fake)];
                         // Evitar repetir usuarios en el hilo
                         while ((string)$u_resp2['_id'] === (string)$u_raiz['_id'] || (string)$u_resp2['_id'] === (string)$u_resp['_id']) {
                             $u_resp2 = $usuarios_fake[array_rand($usuarios_fake)];
                         }
                         
                         $banco_cierre = [
                             "Totalmente, buena compra.", 
                             "Gracias por aclarar la duda!", 
                             "Me habéis convencido, pillado.", 
                             "Justo eso venía buscando."
                         ];
                         $resp2_raw = $banco_cierre[array_rand($banco_cierre)];
                         // Aplicar personalidad también al cierre
                         $resp2 = procesarPlaceholder($resp2_raw, $marca, $tipo, $nombre);
                         
                         $res_resp2 = crearComentario([
                            'chollo_id' => $chollo_id,
                            'usuario_id' => (string)$u_resp2['_id'],
                            'comentario' => $resp2,
                            'padre_id' => $raiz_id
                        ]);
                        if ($res_resp2['success']) echo "      ↪️ Resp 2: \"$resp2\"\n";
                    }
                }
            }
        }
    }
}

echo "\n--- FINALIZADO CORRECTAMENTE ---\n";
