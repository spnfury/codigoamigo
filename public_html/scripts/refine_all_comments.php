<?php
/**
 * Script MAESTRO de Refinamiento de Comentarios
 * 
 * 1. Recorre comentarios fake recientes.
 * 2. Aplica Smart Context (inyecta Marca/Producto si es genérico).
 * 3. Verifica Coherencia Semántica (Pregunta vs Opinión).
 * 4. Elimina "obviedades" (Envío Amazon).
 */

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';

// --- CONFIGURACIÓN Y DICCIONARIOS ---

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
];

$COMENTARIOS_CATEGORIA = [
    'zapatillas' => [
        "¿Sabéis cómo tallan estas {marca}? Suelo usar una 43.",
        "Me encantan las {marca}, son súper cómodas.",
        "Buen precio para unas {marca}.",
        "¿Son originales? El precio es muy bueno.",
    ],
    'móvil' => [
        "¿Qué tal la batería de este {marca}?",
        "Gran precio para este {tipo}, en otras tiendas está más caro.",
        "¿Viene con funda incluida?",
        "Tuve un {marca} antes y me salió muy bueno.",
    ],
    'tv' => [
        "¿Qué tal el ángulo de visión de esta {marca}?",
        "Para ver fútbol, ¿qué tal va este modelo?",
        "¿Sabéis si tiene HDMI 2.1 para la consola?",
    ],
    'juego' => [
        "Juegazo, le tengo muchas ganas.",
        "¿Es versión física o código?",
        "A ese precio cae seguro.",
    ],
    'generic' => [
        "¡Qué chollo! Gracias por compartir 🔥",
        "Buen hallazgo, lo tenía en mi lista.",
        "Gracias por el aviso, comprado! 🛒",
        "¿Alguien lo ha probado? Tiene buena pinta.",
        "Precio mínimo histórico creo.",
        "Buen chollo." // Added as safe fallback
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
    // Si el texto requiere marca ({marca}) pero no tenemos marca, fallamos (return null)
    if (strpos($texto, '{marca}') !== false && empty($marca)) return null;
    if (strpos($texto, '{tipo}') !== false && empty($tipo)) return null;
    
    $texto = str_replace('{marca}', $marca, $texto);
    $texto = str_replace('{tipo}', $tipo, $texto);
    return ucfirst($texto);
}

// --- MAIN ---

echo "=== INICIANDO REFINAMIENTO DE COMENTARIOS ===\n";

$collection_usuarios = getCollectionUsuarios();
$collection_chollos = getCollectionChollos();
$collection_comentarios = getCollectionCholloComentarios();

// 1. Obtener IDs Fake
$fake_users = $collection_usuarios->find(['tipo' => 'fake'], ['projection' => ['_id' => 1]])->toArray();
$fake_ids = array_map(function($u){ return new MongoDB\BSON\ObjectId((string)$u['_id']); }, $fake_users);

// 2. Buscar comentarios recientes de estos usuarios
$cursor = $collection_comentarios->find([
    'usuario_id' => ['$in' => $fake_ids]
], ['sort' => ['fecha' => -1], 'limit' => 100]); // Analizar últimos 100

$count_updated = 0;

foreach ($cursor as $com) {
    $updated = false;
    $texto_original = $com['comentario'];
    $nuevo_texto = $texto_original;

    // Obtener Chollo
    $chollo = $collection_chollos->findOne(['_id' => $com['chollo_id']]);
    if (!$chollo) continue;

    // --- A. LIMPIEZA AMAZON ---
    $es_amazon = (strpos(mb_strtolower($chollo['titulo'].$chollo['enlace']), 'amazon') !== false);
    if ($es_amazon && (strpos($texto_original, 'envío') !== false || strpos($texto_original, 'gratis') !== false)) {
        // Reemplazar comentario de envío por uno genérico bueno
        $nuevo_texto = $COMENTARIOS_CATEGORIA['generic'][array_rand($COMENTARIOS_CATEGORIA['generic'])];
        $updated = true;
        echo "♻️ [AMAZON CLEANUP] '$texto_original' -> '$nuevo_texto'\n";
    }

    // --- B. COHERENCIA DE RESPUESTAS ---
    if (!$updated && !empty($com['padre_id'])) {
        $padre = $collection_comentarios->findOne(['_id' => $com['padre_id']]);
        if ($padre) {
            $padre_texto = $padre['comentario'];
            $es_pregunta = (strpos($padre_texto, '?') !== false || stripos($padre_texto, '¿') !== false || preg_match('/^(quién|cómo|cuándo|dónde)/i', trim($padre_texto)));
            
            // Si es respuesta a PREGUNTA, verificar que NO sea opinión
            if ($es_pregunta && (stripos($texto_original, 'acuerdo') !== false || stripos($texto_original, 'discrepo') !== false)) {
                $nuevo_texto = $RESPUESTAS_DUDA[array_rand($RESPUESTAS_DUDA)];
                $updated = true;
                echo "🧠 [LOGIC FIX (Q->A)] '$texto_original' -> '$nuevo_texto'\n";
            }
            // Si es respuesta a OPINIÓN, verificar que NO sea duda o "Justo iba a preguntar"
            if (!$es_pregunta && (stripos($texto_original, 'iba a preguntar') !== false || stripos($texto_original, 'ni idea') !== false)) {
                $nuevo_texto = $RESPUESTAS_OPINION[array_rand($RESPUESTAS_OPINION)];
                $updated = true;
                echo "🧠 [LOGIC FIX (A->A)] '$texto_original' -> '$nuevo_texto'\n";
            }
        }
    }

    // --- C. SMART CONTEXT (Mejorar comentarios muy cortos/genéricos) ---
    // Si el comentario es muy corto o genérico y detectamos marca, lo mejoramos
    $es_generico = (strlen($texto_original) < 20 || stripos($texto_original, 'buen precio') !== false || stripos($texto_original, 'interesante') !== false);
    
    // Si detectamos que ya se rompió antes ("para unas marca"), forzamos arreglo
    if (stripos($texto_original, 'para unas marca') !== false) {
        $es_generico = true; // Forzar regeneración
    }
    
    if (!$updated && empty($com['padre_id']) && $es_generico) {
        $marca = detectarMarca($chollo['titulo']);
        $tipo = detectarTipo($chollo['titulo']);
        
        $banco = isset($COMENTARIOS_CATEGORIA[$tipo]) ? $COMENTARIOS_CATEGORIA[$tipo] : $COMENTARIOS_CATEGORIA['generic'];
        $candidatos = array_filter($banco, function($t) { return strpos($t, '{') !== false; });
        
        if (!empty($candidatos)) {
            $t_raw = $candidatos[array_rand($candidatos)];
            $nuevo_texto_candidato = procesarPlaceholder($t_raw, $marca, $tipo);
            
            if ($nuevo_texto_candidato !== null && $nuevo_texto_candidato !== $texto_original) {
                $nuevo_texto = $nuevo_texto_candidato;
                $updated = true; 
                echo "💎 [CONTEXT ENRICH] '$texto_original' -> '$nuevo_texto'\n";
            } else {
                // Si falló el enriquecimiento (null) y el original estaba roto ("para unas marca"), revertir básico
                if (stripos($texto_original, 'para unas marca') !== false) {
                     $nuevo_texto = "Buen chollo.";
                     $updated = true;
                     echo "🩹 [FIX BROKEN] '$texto_original' -> '$nuevo_texto'\n";
                }
            }
        }
    }

    if ($updated) {
        $collection_comentarios->updateOne(
            ['_id' => $com['_id']],
            ['$set' => ['comentario' => $nuevo_texto]]
        );
        $count_updated++;
    }
}

echo "Total actualizados: $count_updated\n";
