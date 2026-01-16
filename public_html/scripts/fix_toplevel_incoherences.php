<?php
/**
 * Script de limpieza: Detectar comentarios de Nivel 0 (sin padre) que parecen respuestas
 * Ej: "Totalmente de acuerdo", "Sí, confirmado", "Justo iba a preguntar eso".
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';

echo "--- BUSCANDO INCOHERENCIAS EN NIVEL 0 ---\n";

$collection_comentarios = getCollectionCholloComentarios();

// Frases que NO deberían estar en un comentario raíz
$FRASES_PROHIBIDAS_ROOT = [
    "Justo eso iba a preguntar yo",
    "Totalmente de acuerdo contigo",
    "Coincido 100%",
    "Sí, confirmado",
    "A mí me pasó algo parecido",
    "Gracias por compartir tu experiencia",
    "Efectivamente, así es",
    "Ni idea, lo siento",
    "Pues yo discrepo un poco",
    "Me interesa saber lo mismo",
    "Depende del vendedor" // Muy context-dependant
];

// Construir regex OR
$regex_parts = array_map(function($f) { return preg_quote($f, '/'); }, $FRASES_PROHIBIDAS_ROOT);
$regex = '/(' . implode('|', $regex_parts) . ')/i';

$cursor = $collection_comentarios->find([
    'padre_id' => null, // Solo comentarios raíz
    'comentario' => ['$regex' => $regex]
]);

$count = 0;
foreach ($cursor as $com) {
    echo "🚨 INCOHERENCIA ROOT DETECTADA:\n";
    echo "   ID: {$com['_id']}\n";
    echo "   Texto: \"{$com['comentario']}\"\n";
    
    // Solución: Convertirlos en comentarios genéricos de "Buen chollo" o eliminarlos?
    // Mejor cambiarlos por algo genérico para no perder la actividad.
    
    $GENERICOS = [
        "Tiene buena pinta.",
        "Buen precio.",
        "A ver si dura la oferta.",
        "Lo estaba buscando, gracias.",
        "Interesante oferta.",
    ];
    
    $nuevo = $GENERICOS[array_rand($GENERICOS)];
    
    $collection_comentarios->updateOne(
        ['_id' => $com['_id']],
        ['$set' => ['comentario' => $nuevo]]
    );
    
    echo "   ✅ CORREGIDO A: \"$nuevo\"\n\n";
    $count++;
}

echo "Total corregidos: $count\n";
