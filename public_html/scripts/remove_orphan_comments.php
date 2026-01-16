<?php
/**
 * Script limpieza: Eliminar comentarios huérfanos (padre_id apunta a nada)
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';

echo "--- BUSCANDO COMENTARIOS HUÉRFANOS ---\n";

$collection_comentarios = getCollectionCholloComentarios();
$collection_chollos = getCollectionChollos();

// Buscar todos los comentarios que tienen padre_id (son respuestas)
$cursor = $collection_comentarios->find(['padre_id' => ['$ne' => null]]);

$orphans = 0;

foreach ($cursor as $com) {
    try {
        $padre_id = $com['padre_id'];
        
        // Verificar si existe el padre
        $padre = $collection_comentarios->findOne(['_id' => $padre_id]);
        
        if (!$padre) {
            $orphans++;
            $txt = substr($com['comentario'], 0, 50);
            echo "👻 HUÉRFANO DETECTADO: [$txt] (Padre: $padre_id)\n";
            
            // Eliminar huérfano
            $collection_comentarios->deleteOne(['_id' => $com['_id']]);
            
            // Decrementar contador chollo
            $collection_chollos->updateOne(
                ['_id' => $com['chollo_id']],
                ['$inc' => ['total_comentarios' => -1]]
            );
        }
    } catch (Exception $e) {
        // Ignorar errores de ID invalido
    }
}

echo "Total Huérfanos eliminados: $orphans\n";
