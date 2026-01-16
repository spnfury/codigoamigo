<?php
/**
 * Script para analizar y corregir comentarios incoherentes
 * Detecta si una respuesta no cuadra con el comentario padre (pregunta vs opinión)
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';

// Banco de respuestas coherentes (copiado de cron_fake_comments.php actualizado)
$RESPUESTAS_COHERENTES = [
    'duda' => [
        "Creo que sí, pero revísalo por si acaso.",
        "A mí me llegó rápido, así que imagino que funciona bien.",
        "Ni idea, lo siento.",
        "Diría que sí.",
        "Depende del vendedor, a veces cambian las condiciones.",
        "Yo lo compré y todo correcto.",
        "Si tienes Prime creo que es gratis el envío.",
        "En la descripción suele ponerlo.",
        "A mí me tardó unos 3 días.",
        "Sí, confirmado.",
    ],
    'opinion' => [
        "Totalmente de acuerdo contigo.",
        "Justo eso iba a decir yo.",
        "A mí me pasó algo parecido.",
        "Gracias por compartir tu experiencia.",
        "Coincido 100%.",
        "Pues yo discrepo un poco, a mí no me dio ese problema.",
        "Interesante punto de vista.",
        "Es verdad, es un detalle importante.",
        "Gracias por el aviso.",
        "Anotado, gracias.",
    ]
];

echo "--- INICIANDO ANÁLISIS DE COHERENCIA ---\n";

$collection_usuarios = getCollectionUsuarios();
$collection_comentarios = getCollectionCholloComentarios();

// 1. Obtener IDs de usuarios fake
$usuarios_fake = $collection_usuarios->find(['tipo' => 'fake'], ['projection' => ['_id' => 1]])->toArray();
$fake_ids = array_map(function($u) { return (string)$u['_id']; }, $usuarios_fake);

if (empty($fake_ids)) {
    die("No hay usuarios fake.\n");
}

echo "Usuarios fake encontrados: " . count($fake_ids) . "\n";

// 2. Buscar respuestas de usuarios fake
$fake_ids_oids = array_map(function($id) { return new MongoDB\BSON\ObjectId($id); }, $fake_ids);

$respuestas = $collection_comentarios->find([
    'usuario_id' => ['$in' => $fake_ids_oids],
    'padre_id' => ['$ne' => null]
]);

$count_analyzed = 0;
$count_fixed = 0;

foreach ($respuestas as $respuesta) {
    $count_analyzed++;
    
    // Obtener padre
    $padre = $collection_comentarios->findOne(['_id' => $respuesta['padre_id']]);
    if (!$padre) continue;
    
    $texto_padre = $padre['comentario'];
    $texto_respuesta = $respuesta['comentario'];
    
    // Detectar tipo de padre
    $es_pregunta = (strpos($texto_padre, '?') !== false || stripos($texto_padre, '¿') !== false);
    
    // Detectar si la respuesta actual es 'coherente' o 'incoherente'
    // Heurística simple: Si es pregunta, la respuesta no debería ser una de reacción típica ("discrepo", "acuerdo")
    // O simplemente verificamos si la respuesta actual está en el banco INCORRECTO.
    
    // Banco incorrecto para pregunta: 'opinion'
    // Banco incorrecto para opinion: 'duda' (aunque responder duda a opinion no es tan grave como opinion a duda)
    
    $deberia_ser = $es_pregunta ? 'duda' : 'opinion';
    
    // Chequear si la respuesta actual parece del otro tipo
    // Como es difícil saberlo exactamente sin comprobar contra el array, vamos a usar una lógica agresiva:
    // Si detectamos que es incoherente (ej: padre pregunta y respuesta contiene palabras clave de opinión), lo cambiamos.
    
    $es_incoherente = false;
    
    if ($es_pregunta) {
        // Si preguntan, y respondemos con "de acuerdo", "discrepo", "coincido"... es raro.
        if (stripos($texto_respuesta, 'acuerdo') !== false || 
            stripos($texto_respuesta, 'discrepo') !== false || 
            stripos($texto_respuesta, 'coincido') !== false ||
            stripos($texto_respuesta, 'gracias por compartir') !== false) {
            $es_incoherente = true;
        }
    } else {
        // Si opinan, y respondemos con "ni idea", "si confirmado"... es raro pero pasable.
        // Pero "Ni idea, lo siento" a una opinión es muy raro.
        if (stripos($texto_respuesta, 'Ni idea') !== false || 
            stripos($texto_respuesta, 'Sí, confirmado') !== false) {
            $es_incoherente = true;
        }
    }
    
    if ($es_incoherente) {
        echo "🚨 INCOHERENCIA DETECTADA:\n";
        echo "   Padre ({$deberia_ser}): \"$texto_padre\"\n";
        echo "   Resp. Actual: \"$texto_respuesta\"\n";
        
        // CORREGIR
        $nuevo_texto = $RESPUESTAS_COHERENTES[$deberia_ser][array_rand($RESPUESTAS_COHERENTES[$deberia_ser])];
        
        $collection_comentarios->updateOne(
            ['_id' => $respuesta['_id']],
            ['$set' => ['comentario' => $nuevo_texto]]
        );
        
        echo "   ✅ CORREGIDO A: \"$nuevo_texto\"\n\n";
        $count_fixed++;
    } else {
        echo "   OK: Padre=\"".substr($texto_padre,0,20)."...\" Resp=\"".substr($texto_respuesta,0,20)."\"\n";
    }
}

echo "--- FINALIZADO ---\n";
echo "Analizados: $count_analyzed\n";
echo "Corregidos: $count_fixed\n";
