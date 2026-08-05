<?php
/**
 * Script TARGET para forzar comentarios en un chollo específico.
 * ID: 696b64b129cdae9b480cbcc9 (LEGO)
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';
require_once __DIR__ . '/../cron/cron_fake_comments.php'; 

$chollo_id = "696b64b129cdae9b480cbcc9";

$collection_usuarios = getCollectionUsuarios();
$collection_chollos = getCollectionChollos();

$chollo = $collection_chollos->findOne(['_id' => new MongoDB\BSON\ObjectId($chollo_id)]);
if (!$chollo) die("❌ Chollo no encontrado\n");

echo "🔵 Procesando Chollo: " . $chollo['titulo'] . "\n";

// Obtener usuarios fake
$usuarios_fake = $collection_usuarios->find(['tipo' => 'fake', 'estado' => 1])->toArray();
if (empty($usuarios_fake)) die("❌ Sin usuarios fake\n");

// Detección
$marca = detectarMarca($chollo['titulo']);
$tipo = detectarTipo($chollo['titulo']);
$nombre = limpiarTituloProducto($chollo['titulo']);

echo "   🔍 Detección: Tipo=[$tipo] Marc=[$marca] Nombre=[$nombre]\n";

// Generar 2 hilos
$num_hilos = 2;
$used_texts = [];

for ($i = 0; $i < $num_hilos; $i++) {
    // Banco
    $banco_raiz = isset($COMENTARIOS_CATEGORIA[$tipo]) ? $COMENTARIOS_CATEGORIA[$tipo] : $COMENTARIOS_CATEGORIA['generic'];
    shuffle($banco_raiz);
    
    $texto_raiz = null;
    foreach($banco_raiz as $raw) {
        $candidate = procesarPlaceholder($raw, $marca, $tipo, $nombre);
        if ($candidate && !in_array($candidate, $used_texts)) {
            $texto_raiz = $candidate;
            $used_texts[] = $candidate;
            break;
        }
    }
    
    if (!$texto_raiz) continue;

    $u_raiz = $usuarios_fake[array_rand($usuarios_fake)];
    
    $res = crearComentario([
        'chollo_id' => (string)$chollo_id,
        'usuario_id' => (string)$u_raiz['_id'],
        'comentario' => $texto_raiz
    ]);
    
    if ($res['success']) {
        echo "   ✅ Hilo $i: \"$texto_raiz\"\n";
        
        // Respuesta (80%)
        if (rand(1,100) <= 80) {
            $u_resp = $usuarios_fake[array_rand($usuarios_fake)];
            while ((string)$u_resp['_id'] === (string)$u_raiz['_id']) {
                $u_resp = $usuarios_fake[array_rand($usuarios_fake)];
            }
            
            // Si es juego (o Lego), respuesta situacional de niños o regalo suele encajar bien
            // O una técnica sobre "envío" o "precio"
            $banco_resp = $COMENTARIOS_CATEGORIA['generic'];
            if (rand(1,100) <= 50) $banco_resp = $COMENTARIOS_CATEGORIA['situacional_familia'];

            $resp_raw = $banco_resp[array_rand($banco_resp)];
            $resp = procesarPlaceholder($resp_raw, $marca, $tipo, $nombre);
            
            if ($resp) {
                $res2 = crearComentario([
                    'chollo_id' => (string)$chollo_id,
                    'usuario_id' => (string)$u_resp['_id'],
                    'comentario' => $resp,
                    'padre_id' => $res['id']
                ]);
                if ($res2['success']) echo "      ↪️ Resp: \"$resp\"\n";
            }
        }
    }
}

echo "✅ Finalizado.\n";
