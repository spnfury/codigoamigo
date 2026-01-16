<?php
/**
 * Script de limpieza: Eliminar preguntas de envío en chollos de Amazon
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';

echo "--- INICIANDO LIMPIEZA DE COMENTARIOS AMAZON ---\n";

$collection_comentarios = getCollectionCholloComentarios();
$collection_chollos = getCollectionChollos();

// 1. Buscar comentarios que contengan "gratis" o "envío" o "tarda"
$cursor = $collection_comentarios->find([
    '$or' => [
        ['comentario' => ['$regex' => 'gratis', '$options' => 'i']],
        ['comentario' => ['$regex' => 'envío', '$options' => 'i']],
        ['comentario' => ['$regex' => 'tarda', '$options' => 'i']]
    ]
]);

$count_deleted = 0;

foreach ($cursor as $com) {
    // Verificar si el chollo es de Amazon
    $chollo = $collection_chollos->findOne(['_id' => $com['chollo_id']]);
    if (!$chollo) continue;
    
    $es_amazon = false;
    $link = mb_strtolower($chollo['enlace'] ?? '');
    $titulo = mb_strtolower($chollo['titulo'] ?? '');
    
    if (strpos($link, 'amazon') !== false || strpos($link, 'amzn') !== false || strpos($titulo, 'amazon') !== false) {
        $es_amazon = true;
    }
    
    if ($es_amazon) {
        // Analizar si es una pregunta "tonta"
        $texto = mb_strtolower($com['comentario']);
        $es_pregunta_envio = (
            (strpos($texto, 'envío') !== false && strpos($texto, 'gratis') !== false) ||
            (strpos($texto, 'cuánto tarda') !== false) ||
            (strpos($texto, 'tarda en llegar') !== false)
        );
        
        if ($es_pregunta_envio) {
            echo "🗑️ ELIMINANDO: \"{$com['comentario']}\" en Chollo Amazon: \"{$chollo['titulo']}\"\n";
            
            // Eliminar comentario
            eliminarComentario((string)$com['_id'], (string)$com['usuario_id']);
            $count_deleted++;
        }
    }
}

echo "Total eliminados: $count_deleted\n";
