<?php
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';
require_once __DIR__ . '/../cron/cron_fake_comments.php'; 

// ID del Chollo Dragon Ball Silla
$chollo_id = "696b80d4e2ebb2d0d70453b2";

$collection_comentarios = getCollectionCholloComentarios();
$collection_usuarios = getCollectionUsuarios();
$collection_chollos = getCollectionChollos();

$chollo = $collection_chollos->findOne(['_id' => new MongoDB\BSON\ObjectId($chollo_id)]);
if (!$chollo) die("❌ Chollo no encontrado\n");

echo "🔵 Procesando Chollo: " . $chollo['titulo'] . "\n";

// 1. Obtener usuarios fake IDs
$usuarios_fake = $collection_usuarios->find(['tipo' => 'fake'])->toArray();
$fake_ids = array_map(function($u){ return $u['_id']; }, $usuarios_fake);

// 2. Eliminar comentarios de usuarios fake en este chollo
$deleteResult = $collection_comentarios->deleteMany([
    'chollo_id' => new MongoDB\BSON\ObjectId($chollo_id),
    'usuario_id' => ['$in' => $fake_ids]
]);
echo "🗑️ Eliminados " . $deleteResult->getDeletedCount() . " comentarios antiguos (bad data).\n";

// 3. Regenerar con la NUEVA lógica
echo "🔄 Regenerando comentarios con lógica estricta...\n";

// Forzamos la detección para ver qué sale
$marca = detectarMarca($chollo['titulo']);
$tipo = detectarTipo($chollo['titulo']);
$nombre = limpiarTituloProducto($chollo['titulo']);

echo "   🔍 Detección: Tipo=[$tipo] Marc=[$marca] Nombre=[$nombre]\n";

// Simular lógica de mass_apply para insertar 2 hilos
$num_hilos = 2;
$used_texts = [];

for ($i = 0; $i < $num_hilos; $i++) {
    // Banco Raíz
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
        echo "   ✅ Hilo $i (Raíz): \"$texto_raiz\"\n";
        
        // Respuesta
        if (rand(1,100) <= 80) {
            $u_resp = $usuarios_fake[array_rand($usuarios_fake)];
            // Lógica simple de respuesta para test
            $banco_resp = $COMENTARIOS_CATEGORIA['generic'];
            if ($tipo && isset($COMENTARIOS_CATEGORIA[$tipo])) {
                 // Si es silla, intentar respuesta técnica de silla si tuviéramos
            }
            // Por simplicidad en este script de fix, usamos generic o situacional
            $banco_resp = array_merge($COMENTARIOS_CATEGORIA['generic'], $COMENTARIOS_CATEGORIA['situacional_familia']); 
            $resp_raw = $banco_resp[array_rand($banco_resp)];
            $resp = procesarPlaceholder($resp_raw, $marca, $tipo, $nombre);
            
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
