<?php
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';

// El ID del chollo parece ser el último segmento de la URL: 696a2f55ced5b7a5a50661c5
// OJO: Puede que sea un slug + ID o solo slug.
// Pero la URL es /chollos/categoria/ID.
// Asumimos que es el ObjectId.

$id_str = '696a2f55ced5b7a5a50661c5';

echo "--- INSPECCIONANDO CHOLLO: $id_str ---\n";

try {
    $oid = new MongoDB\BSON\ObjectId($id_str);
} catch (Exception $e) {
    die("ID invalido: " . $e->getMessage() . "\n");
}

$collection = getCollectionCholloComentarios();
$comentarios = $collection->find(['chollo_id' => $id_str])->toArray();

if (empty($comentarios)) {
    // A veces guardan chollo_id como ObjectId, a veces como string. Probamos ambos.
    $comentarios = $collection->find(['chollo_id' => $oid])->toArray();
}

echo "Total Comentarios encontrados: " . count($comentarios) . "\n\n";

foreach ($comentarios as $c) {
    $id = (string)$c['_id'];
    $padre = isset($c['padre_id']) ? (string)$c['padre_id'] : 'NULL';
    $texto = $c['comentario'];
    $usuario = $c['usuario_id']; // Probablemente ObjectId
    
    // Verificar si el padre existe
    $status_padre = "ROOT";
    if ($padre !== 'NULL') {
        $p_doc = $collection->findOne(['_id' => $c['padre_id']]);
        if ($p_doc) {
            $status_padre = "PADRE_OK (" . substr($p_doc['comentario'], 0, 20) . "...)";
        } else {
            $status_padre = "🚨 ORPHAN (Padre no existe)";
        }
    }
    
    echo "[$id]\n";
    echo "  Usuario: $usuario\n";
    echo "  Padre: $padre -> $status_padre\n";
    echo "  Texto: \"$texto\"\n";
    echo "----------------------------------------\n";
}
