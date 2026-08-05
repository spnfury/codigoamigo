<?php
/**
 * Script para resetear y regenerar comentarios de un chollo específico
 * v3 - Zero Duplication & Smart Keywords
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';
require_once __DIR__ . '/../cron/cron_fake_comments.php';

$chollo_id = '696c7dec5351f93687052aa4'; // El Chopper/Picadora

echo "--- RESEEDING CHOLLO: $chollo_id ---\n";

$collection_comentarios = getCollectionCholloComentarios();
$collection_chollos = getCollectionChollos();
$collection_usuarios = getCollectionUsuarios();

// 1. Limpieza
$usuarios_fake = $collection_usuarios->find(['tipo' => 'fake'], ['projection' => ['_id' => 1, 'username' => 1]])->toArray();
$fake_ids = array_map(function($u){ return $u['_id']; }, $usuarios_fake);

$deleteResult = $collection_comentarios->deleteMany([
    'chollo_id' => new MongoDB\BSON\ObjectId($chollo_id),
    'usuario_id' => ['$in' => $fake_ids]
]);
echo "Eliminados " . $deleteResult->getDeletedCount() . " comentarios fakes anteriores.\n";

$chollo = $collection_chollos->findOne(['_id' => new MongoDB\BSON\ObjectId($chollo_id)]);
if (!$chollo) die("Chollo no encontrado.\n");

$marca = detectarMarca($chollo['titulo']);
$tipo = detectarTipo($chollo['titulo']);
$nombre = limpiarTituloProducto($chollo['titulo']);

echo "Detected Category: $tipo\n";

$num_hilos = 3;
$used_texts = [];

for ($i = 0; $i < $num_hilos; $i++) {
    // A. Raíz
    $cat_exists = isset($COMENTARIOS_CATEGORIA[$tipo]) && $tipo !== 'generic';
    
    // Si existe categoría, el PRIMER comentario DEBE ser de esa categoría y tipo PREGUNTA si es posible
    if ($i === 0 && $cat_exists) {
        $banco_raiz = $COMENTARIOS_CATEGORIA[$tipo];
    } else {
        // Para el resto, mezclamos pero priorizamos la categoría si existe
        $banco_raiz = $cat_exists ? array_merge($COMENTARIOS_CATEGORIA[$tipo], $COMENTARIOS_CATEGORIA['generic']) : $COMENTARIOS_CATEGORIA['generic'];
    }
    
    // Mezclar para tener variedad
    shuffle($banco_raiz);
    
    $texto_raiz = null;
    foreach($banco_raiz as $raw) {
        $candidate = procesarPlaceholder($raw, $marca, $tipo, $nombre);
        if ($candidate && !in_array($candidate, $used_texts)) {
            // Si es el primer comentario y tenemos categoría, buscar uno que sea PREGUNTA
            if ($i === 0 && $cat_exists) {
                $es_pregunta_candidate = (strpos($candidate, '?') !== false || strpos($candidate, '¿') !== false);
                if ($es_pregunta_candidate) {
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
    
    // Fallback si no encontramos pregunta en el loop anterior para i=0
    if (!$texto_raiz && $i === 0 && $cat_exists) {
        foreach($COMENTARIOS_CATEGORIA[$tipo] as $raw) {
            $candidate = procesarPlaceholder($raw, $marca, $tipo, $nombre);
            if ($candidate && !in_array($candidate, $used_texts)) {
                $texto_raiz = $candidate;
                $used_texts[] = $candidate;
                break;
            }
        }
    }

    if (!$texto_raiz) $texto_raiz = "Buen chollo para este " . ($nombre ?: "producto") . " ($i).";

    $u_raiz = $usuarios_fake[array_rand($usuarios_fake)];
    $res_raiz = crearComentario([
        'chollo_id' => $chollo_id,
        'usuario_id' => (string)$u_raiz['_id'],
        'comentario' => $texto_raiz
    ]);

    if ($res_raiz['success']) {
        $raiz_id = $res_raiz['id'];
        echo "✅ Raíz [$i] (" . ($cat_exists ? $tipo : 'generic') . "): \"$texto_raiz\"\n";
        
        // B. Respuesta
        $u_resp = $usuarios_fake[array_rand($usuarios_fake)];
        while ((string)$u_resp['_id'] === (string)$u_raiz['_id']) {
            $u_resp = $usuarios_fake[array_rand($usuarios_fake)];
        }
        
        $es_pregunta = (strpos($texto_raiz, '?') !== false || stripos($texto_raiz, '¿') !== false);
        $respuesta = null;

        // Keyword checking
        $keys_to_check = ['gracias', 'llegue', 'talla', 'original', 'bateria', 'preciso', 'limpia', 'pica', 'potencia', 'ram', 'teclado', 'peso', 'pesa', 'calienta', 'refrigeracion', 'sobrado', 'ofimatica', 'gaming', 'potencia', 'specs'];
        foreach ($keys_to_check as $key) {
            if (stripos($texto_raiz, $key) !== false && isset($RESPUESTAS_TECNICAS[$key])) {
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

        if (!$respuesta) {
            $banco_resp = $es_pregunta ? $RESPUESTAS_DUDA_GENERICAS : $RESPUESTAS['reaccion_opinion'];
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
        
        if (!$respuesta) $respuesta = "Totalmente de acuerdo ($i).";

        $res_resp = crearComentario([
            'chollo_id' => $chollo_id,
            'usuario_id' => (string)$u_resp['_id'],
            'comentario' => $respuesta,
            'padre_id' => $raiz_id
        ]);
        if ($res_resp['success']) echo "   ↪️ Respuesta 1: \"$respuesta\"\n";

        // C. Segunda Respuesta (Opcional, 40% de probabilidad en el primer hilo)
        if ($i === 0 && rand(1,100) <= 40) {
            $u_resp2 = $usuarios_fake[array_rand($usuarios_fake)];
            while ((string)$u_resp2['_id'] === (string)$u_raiz['_id'] || (string)$u_resp2['_id'] === (string)$u_resp['_id']) {
                $u_resp2 = $usuarios_fake[array_rand($usuarios_fake)];
            }
            
            $banco_extra = [
                "A mí también me interesa mucho este tema.", 
                "Justo eso mismo iba a decir yo, qué casualidad.", 
                "¡Muchas gracias por la info detallada! Se agradece.", 
                "Me lo apunto sin falta para un regalo que tengo pendiente.", 
                "Qué buen aporte habéis hecho entre los dos, gracias."
            ];
            $resp2_raw = $banco_extra[array_rand($banco_extra)];
            $resp2 = procesarPlaceholder($resp2_raw, $marca, $tipo, $nombre);
            
            $res_resp2 = crearComentario([
                'chollo_id' => $chollo_id,
                'usuario_id' => (string)$u_resp2['_id'],
                'comentario' => $resp2,
                'padre_id' => $raiz_id
            ]);
            if ($res_resp2['success']) echo "   ↪️ Respuesta 2: \"$resp2\"\n";
        }
    }
    usleep(50000); 
}
echo "--- FIN ---\n";
