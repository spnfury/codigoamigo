<?php
/**
 * Script MAESTRO de Refinamiento de Comentarios v2
 * 
 * 1. Elimina respuestas de nivel > 1 (respuestas a respuestas).
 * 2. Enriquece comentarios genéricos con nuevos templates específicos.
 * 3. Mantiene limpieza de Amazon y coherencia de preguntas.
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';

// --- CONFIGURACIÓN Y DICCIONARIOS (Actualizados) ---

$MARCAS = [
    'nike', 'adidas', 'puma', 'reebok', 'new balance', 'asics', 'vans', 'xiaomi', 'samsung', 
    'apple', 'iphone', 'pixel', 'google', 'sony', 'lg', 'philips', 'hisense', 'tcl', 
    'lenovo', 'hp', 'dell', 'asus', 'acer', 'msi', 'logitech', 'razer', 'corsair', 
    'nintendo', 'playstation', 'ps5', 'xbox', 'steam', 'bosch', 'balay', 'cecotec', 
    'dyson', 'roborock', 'colgate', 'ariel', 'fairy', 'finish', 'dodot'
];

$TIPOS_PRODUCTO = [
    'zapatillas' => ['zapatillas', 'zapato', 'bota', 'sneaker'],
    'móvil' => ['smartphone', 'móvil', 'telefono', 'iphone', 'galaxy', 'redmi', 'pixel'],
    'tv' => ['tv', 'televis', 'smart tv', 'oled', 'qled'],
    'portátil' => ['portatil', 'laptop', 'ordenador'],
    'aspiradora' => ['aspirador', 'roborock', 'dyson', 'conga'],
    'juego' => ['juego', 'game', 'ps5', 'switch', 'xbox'],
    'auriculares' => ['auricul', 'headset', 'cascos', 'airpods', 'buds'],
    'reloj' => ['watch', 'reloj', 'smartwatch', 'band'],
    'monitor' => ['monitor', 'pantalla'],
];

$COMENTARIOS_CATEGORIA = [
    'zapatillas' => [
        "¿Sabéis cómo tallan estas {marca}? Suelo usar una 43.",
        "Me encantan las {marca}, son súper cómodas.",
        "Buen precio para unas {marca}.",
        "¿Son originales? El precio es muy bueno para ser {marca}.",
        "Las tengo en otro color y aguantan muy bien el trote.",
        "He tenido otras {marca} y la calida siempre es top.",
        "Perfectas para renovar mis viejas {tipo}.",
    ],
    'móvil' => [
        "¿Qué tal la batería de este {marca}?",
        "Gran precio para este {tipo}, en otras tiendas está más caro.",
        "¿Viene con funda incluida en la caja?",
        "La cámara de este {marca} dicen que es top en gama media.",
        "Tuve un {marca} antes y me salió muy bueno.",
        "Estoy buscando un {tipo} nuevo, ¿recomendáis este?",
        "¿Tiene carga rápida este {marca}?",
    ],
    'tv' => [
        "¿Qué tal el ángulo de visión de esta {marca}?",
        "Para ver fútbol, ¿qué tal va este modelo de {marca}?",
        "¿Sabéis si tiene HDMI 2.1 para la consola?",
        "Buen precio por estas pulgadas en una {marca}.",
        "¿El sistema operativo de {marca} va fluido?",
        "Buscaba una {tipo} para el salón, me la apunto.",
    ],
    'juego' => [
        "Juegazo, le tengo muchas ganas.",
        "¿Es versión física o código de descarga?",
        "A ese precio cae seguro para la colección.",
        "Lo jugué en su día y es una joya.",
        "¿Incluye los DLCs esta edición?",
        "Uno de los mejores {tipo} del año.",
    ],
    'portátil' => [
        "¿Sabéis si se puede ampliar la RAM de este {marca}?",
        "Para ofimática y navegar, ¿va sobrado este {tipo}?",
        "Buen precio para un {marca} con estas specs.",
        "La pantalla de los {marca} suele ser muy buena.",
    ],
    'aspiradora' => [
        "¿Qué tal la potencia de succión de esta {marca}?",
        "¿Sirve para pelos de mascota?",
        "Tengo una {marca} antigua y quiero renovar, ¿esta merece la pena?",
        "Buen precio para este robot {tipo}.",
    ],
    'generic' => [
        "¡Qué chollo! Gracias por compartir 🔥",
        "¡Increíble precio! Voy a por ello antes de que se agote.",
        "Buen hallazgo, lo tenía en mi lista de deseos.",
        "Gracias por el aviso, comprado! 🛒",
        "¿Alguien lo ha probado? Tiene muy buena pinta.",
        "Precio mínimo histórico creo, a la saca.",
        "Ojalá llegue a tiempo, tiene pintaza.",
        "Vaya ofertaza has encontrado.",
    ]
];

$RESPUESTAS_OPINION = [
    "Totalmente de acuerdo contigo.",
    "A mí me pasó algo parecido.",
    "Gracias por compartir tu experiencia.",
    "Coincido 100%.",
    "Efectivamente, así es.",
];

$RESPUESTAS_DUDA = [
    "Creo que sí, pero revísalo por si acaso.",
    "Ni idea, lo siento.",
    "En la descripción suele ponerlo.",
    "Sí, confirmado.",
    "Depende del vendedor.",
];

// --- HELPER FUNCTIONS ---

function detectarMarca($titulo) {
    global $MARCAS;
    $t = mb_strtolower($titulo);
    foreach ($MARCAS as $m) {
        if (strpos($t, $m) !== false) return ucfirst($m);
    }
    return '';
}

function detectarTipo($titulo) {
    global $TIPOS_PRODUCTO;
    $t = mb_strtolower($titulo);
    foreach ($TIPOS_PRODUCTO as $tipo => $keywords) {
        foreach ($keywords as $k) {
             if (strpos($t, $k) !== false) return $tipo;
        }
    }
    return '';
}

function procesarPlaceholder($texto, $marca, $tipo) {
    if (strpos($texto, '{marca}') !== false && empty($marca)) return null;
    if (strpos($texto, '{tipo}') !== false && empty($tipo)) return null;
    $texto = str_replace('{marca}', $marca, $texto);
    $texto = str_replace('{tipo}', $tipo, $texto);
    return ucfirst($texto);
}

// --- MAIN ---

echo "=== INICIANDO REFINAMIENTO DE COMENTARIOS v2 ===\n";

$collection_usuarios = getCollectionUsuarios();
$collection_chollos = getCollectionChollos();
$collection_comentarios = getCollectionCholloComentarios();

// 1. Obtener IDs Fake
$fake_users = $collection_usuarios->find(['tipo' => 'fake'], ['projection' => ['_id' => 1]])->toArray();
$fake_ids = array_map(function($u){ return new MongoDB\BSON\ObjectId((string)$u['_id']); }, $fake_users);

// 2. Buscar comentarios recientes de estos usuarios (Últimos 200)
$cursor = $collection_comentarios->find([
    'usuario_id' => ['$in' => $fake_ids]
], ['sort' => ['fecha' => -1], 'limit' => 200]);

$count_updated = 0;
$count_deleted_deep = 0;

foreach ($cursor as $com) {
    $updated = false;
    $texto_original = $com['comentario'];
    $nuevo_texto = $texto_original;

    // --- CHECK 1: PROFUNDIDAD (Nivel > 1) ---
    if (!empty($com['padre_id'])) {
        $padre = $collection_comentarios->findOne(['_id' => $com['padre_id']]);
        if ($padre && !empty($padre['padre_id'])) {
            // El padre TIENE padre -> Esto es una respuesta a respuesta (Nivel 2)
            // BORRAR INMEDIATAMENTE
            $collection_comentarios->deleteOne(['_id' => $com['_id']]);
            // Decrementar contador chollo
            $collection_chollos->updateOne(['_id' => $com['chollo_id']], ['$inc' => ['total_comentarios' => -1]]);
            echo "🔥 [DELETE DEEP] Borrada respuesta a respuesta: '$texto_original'\n";
            $count_deleted_deep++;
            continue; // Stop processing this comment
        }
    }

    // Obtener Chollo
    $chollo = $collection_chollos->findOne(['_id' => $com['chollo_id']]);
    if (!$chollo) continue;

    // --- CHECK 2: ENRIQUECIMIENTO ---
    // Si el comentario es genérico ("Interesante", "Buen precio") -> Reemplazar con específico
    // Filtros de genérico: < 30 chars O palabras clave genéricas
    $es_generico = (strlen($texto_original) < 30 || 
                    stripos($texto_original, 'buen precio') !== false || 
                    stripos($texto_original, 'interesante') !== false ||
                    stripos($texto_original, 'buen chollo') !== false);
    
    // Solo si es comentario raíz (Nivel 0) queremos enriquecer mucho con Marca/Producto
    if (empty($com['padre_id']) && $es_generico) {
        $marca = detectarMarca($chollo['titulo']);
        $tipo = detectarTipo($chollo['titulo']);
        
        if ($marca || $tipo) {
            $banco = isset($COMENTARIOS_CATEGORIA[$tipo]) ? $COMENTARIOS_CATEGORIA[$tipo] : $COMENTARIOS_CATEGORIA['generic'];
            
            // Intentar buscar uno con placeholders para darle calidad
            $candidatos = array_filter($banco, function($t) { return strpos($t, '{') !== false; });
            
            if (!empty($candidatos)) {
                $t_raw = $candidatos[array_rand($candidatos)];
                $nuevo_texto_candidato = procesarPlaceholder($t_raw, $marca, $tipo);
                
                if ($nuevo_texto_candidato !== null && $nuevo_texto_candidato !== $texto_original) {
                    $nuevo_texto = $nuevo_texto_candidato;
                    $updated = true;
                    echo "💎 [ENRICH] '$texto_original' -> '$nuevo_texto'\n";
                }
            }
        }
    }

    // Actualizar si hubo cambio
    if ($updated) {
        $collection_comentarios->updateOne(
            ['_id' => $com['_id']],
            ['$set' => ['comentario' => $nuevo_texto]]
        );
        $count_updated++;
    }
}

echo "\n--- RESUMEN ---\n";
echo "Deep Replies Deleted (>1): $count_deleted_deep\n";
echo "Comments Enriched: $count_updated\n";
