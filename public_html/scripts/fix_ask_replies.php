<?php
/**
 * Script de limpieza v2: Corregir "Justo eso iba a preguntar" en no-preguntas
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';

echo "--- INICIANDO LIMPIEZA DE INCOHERENCIAS 'Iba a preguntar' ---\n";

$collection_comentarios = getCollectionCholloComentarios();

// Buscar comentarios que digan "iba a preguntar"
$cursor = $collection_comentarios->find([
    'comentario' => ['$regex' => 'iba a preguntar', '$options' => 'i'],
    'padre_id' => ['$ne' => null]
]);

$count_fixed = 0;
$count_ok = 0;

$REACCIONES_OPINION = [
    "Totalmente de acuerdo contigo.",
    "A mí me pasó algo parecido.",
    "Gracias por compartir tu experiencia.",
    "Coincido 100%.",
    "Efectivamente, así es.",
];

foreach ($cursor as $com) {
    // Obtener padre
    $padre = $collection_comentarios->findOne(['_id' => $com['padre_id']]);
    if (!$padre) continue;
    
    $padre_texto = $padre['comentario'];
    
    // Detectar si el padre ERA una pregunta
    $es_pregunta = (
        strpos($padre_texto, '?') !== false || 
        stripos($padre_texto, '¿') !== false ||
        preg_match('/^(quién|cómo|cuándo|dónde|por qué|qué|cual|cuál|sabéis|alguien sabe)/i', trim($padre_texto))
    );
    
    if (!$es_pregunta) {
        // INCOHERENCIA: Dijo "iba a preguntar" a algo que NO es pregunta
        echo "🚨 INCOHERENCIA:\n";
        echo "   Padre (No Pregunta): \"$padre_texto\"\n";
        echo "   Resp. Actual: \"{$com['comentario']}\"\n";
        
        $nuevo = $REACCIONES_OPINION[array_rand($REACCIONES_OPINION)];
        
        $collection_comentarios->updateOne(
            ['_id' => $com['_id']],
            ['$set' => ['comentario' => $nuevo]]
        );
        
        echo "   ✅ CORREGIDO A: \"$nuevo\"\n\n";
        $count_fixed++;
    } else {
        $count_ok++;
        echo "   OK (Es pregunta): \"$padre_texto\" -> \"{$com['comentario']}\"\n";
    }
}

echo "--- FINALIZADO ---\n";
echo "Corregidos: $count_fixed\n";
echo "Correctos: $count_ok\n";
