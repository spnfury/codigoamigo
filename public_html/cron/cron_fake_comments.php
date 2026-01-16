<?php
/**
 * Cronjob v4 - Smart Context & Brands
 * 
 * - Detección de Marcas y Productos en títulos.
 * - Inserción dinámica de placeholders ({marca}, {tipo}).
 * - Lógica de "no preguntar obviedades" (envío Amazon).
 */

// Silenciar output
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';
require_once __DIR__ . '/../myphp/funciones_chollos.php';
require_once __DIR__ . '/../myphp/funciones_chollos_comentarios.php';

$CONFIG = [
    'probabilidad_ejecucion' => 30,
    'probabilidad_comentario_nuevo' => 15,
    'min_antiguedad_respuesta' => 4,
    'max_antiguedad_respuesta' => 60,
    'max_antiguedad_chollo_nuevo' => 120,
];

// =========================================================================================
// SMART CONTEXT - DICCIONARIOS
// =========================================================================================

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

// =========================================================================================
// BANCOS DE TEXTO (Con Placeholders)
// =========================================================================================

$RESPUESTAS = [
    'respuesta_duda' => [
        "Creo que sí, pero revísalo por si acaso.",
        "A mí me llegó rápido, así que imagino que funciona bien.",
        "Ni idea, lo siento.",
        "Depende del vendedor, a veces cambian las condiciones.",
        "Yo lo compré y todo correcto.",
        "En la descripción suele ponerlo.",
        "A mí me tardó unos 3 días.",
        "Sí, confirmado.",
    ],
    'reaccion_opinion' => [
        "Totalmente de acuerdo contigo.",
        "A mí me pasó algo parecido.",
        "Gracias por compartir tu experiencia.",
        "Coincido 100%.",
        "Pues yo discrepo un poco, a mí no me dio ese problema.",
        "Interesante punto de vista.",
        "Es verdad, es un detalle importante.",
        "Gracias por el aviso.",
        "Anotado, gracias.",
        "Me pasó igual, es un puntazo.",
        "Efectivamente, así es.",
    ],
    'reaccion_pregunta_compartida' => [ // Responder a una duda sumándose a ella
        "Justo eso iba a preguntar yo. Gracias!",
        "Me interesa saber lo mismo.",
        "Yo tengo la misma duda.",
        "A ver si alguien nos ilumina.",
    ]
];

// Comentarios nuevos específicos por categoría
$COMENTARIOS_CATEGORIA = [
    'zapatillas' => [
        "¿Sabéis cómo tallan estas {marca}? Suelo usar una 43.",
        "Me encantan las {marca}, son súper cómodas.",
        "Buen precio para unas {marca}.",
        "¿Son originales? El precio es muy bueno.",
        "Las tengo en otro color y aguantan muy bien.",
    ],
    'móvil' => [
        "¿Qué tal la batería de este {marca}?",
        "Gran precio para este {tipo}, en otras tiendas está más caro.",
        "¿Viene con funda incluida?",
        "La cámara de este modelo dicen que es top.",
        "Tuve un {marca} antes y me salió muy bueno.",
    ],
    'tv' => [
        "¿Qué tal el ángulo de visión de esta {marca}?",
        "Para ver fútbol, ¿qué tal va este modelo?",
        "¿Sabéis si tiene HDMI 2.1 para la consola?",
        "Buen precio por estas pulgadas.",
    ],
    'juego' => [
        "Juegazo, le tengo muchas ganas.",
        "¿Es versión física o código?",
        "A ese precio cae seguro.",
        "Lo jugué en su día y es una joya.",
    ],
    'generic' => [
        "¡Qué chollo! Gracias por compartir 🔥",
        "¡Increíble precio! Voy a por ello",
        "Buen hallazgo, lo tenía en mi lista.",
        "Gracias por el aviso, comprado! 🛒",
        "¿Alguien lo ha probado? Tiene buena pinta.",
        "Precio mínimo histórico creo.",
    ]
];

// Preguntas genéricas (SOLO si no es Amazon o si tiene sentido)
$PREGUNTAS_LOGISTICA = [
    "¿Alguien sabe si el envío es gratis?",
    "¿Sabéis cuánto tarda en llegar?",
    "¿El precio incluye aduanas?",
];

// =========================================================================================
// HELPER FUNCTIONS
// =========================================================================================

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

function esAmazon($chollo) {
    // Detectar si el chollo es de Amazon (por enlace, fuente, o título con #Amazon)
    $t = mb_strtolower($chollo['titulo'] ?? '');
    $l = mb_strtolower($chollo['enlace'] ?? '');
    if (strpos($t, 'amazon') !== false || strpos($l, 'amazon') !== false || strpos($l, 'amzn') !== false) {
        return true;
    }
    return false;
}

function procesarPlaceholder($texto, $marca, $tipo) {
    // Si el texto requiere marca ({marca}) pero no tenemos marca, esto fallaría.
    // La validación debería hacerse ANTES de llamar a esto o dentro.
    
    if (strpos($texto, '{marca}') !== false && empty($marca)) {
        return null; // Indicar fallo para cancelar este texto
    }
    if (strpos($texto, '{tipo}') !== false && empty($tipo)) {
        return null;
    }

    $texto = str_replace('{marca}', $marca, $texto);
    $texto = str_replace('{tipo}', $tipo, $texto);
    return ucfirst($texto);
}

// =========================================================================================
// MAIN LOGIC
// =========================================================================================

function ejecutarSimulacion($config) {
    global $RESPUESTAS, $COMENTARIOS_CATEGORIA, $PREGUNTAS_LOGISTICA;
    
    $log = [];
    $log[] = "[" . date('Y-m-d H:i:s') . "] TICK START";

    if (rand(1, 100) > $config['probabilidad_ejecucion']) {
        $log[] = "💤 No toca acción.";
        return implode("\n", $log);
    }

    $collection_usuarios = getCollectionUsuarios();
    $collection_chollos = getCollectionChollos();
    $collection_comentarios = getCollectionCholloComentarios();

    // Obtener usuarios fake
    $usuarios_fake = $collection_usuarios->find(['tipo' => 'fake', 'estado' => 1])->toArray();
    if (empty($usuarios_fake)) {
        return "❌ Error: No hay usuarios fake.";
    }

    $accion = (rand(1, 100) <= $config['probabilidad_comentario_nuevo']) ? 'NUEVO' : 'RESPUESTA';

    // --- ACCIÓN: RESPUESTA ---
    if ($accion === 'RESPUESTA') {
        $now = time();
        $min_t = $now - ($config['max_antiguedad_respuesta'] * 60);
        $max_t = $now - ($config['min_antiguedad_respuesta'] * 60);
        
        $cursor = $collection_comentarios->find([
            'fecha' => ['$gte' => new MongoDB\BSON\UTCDateTime($min_t * 1000), '$lte' => new MongoDB\BSON\UTCDateTime($max_t * 1000)]
        ], ['limit' => 20]);
        $candidatos = iterator_to_array($cursor);

        if (!empty($candidatos)) {
            $com_padre = $candidatos[array_rand($candidatos)];
            $usuario = $usuarios_fake[array_rand($usuarios_fake)];
            
            // Evitar auto-respuesta
            if ((string)$com_padre['usuario_id'] === (string)$usuario['_id']) {
                 $usuario = $usuarios_fake[array_rand($usuarios_fake)];
            }
            
            // DETECTAR INTENCIÓN
            $padre_texto = $com_padre['comentario'] ?? '';
            // Detectar si es pregunta: signos interrogación o palabras clave de inicio
            $es_pregunta = (
                strpos($padre_texto, '?') !== false || 
                stripos($padre_texto, '¿') !== false ||
                preg_match('/^(quién|cómo|cuándo|dónde|por qué|qué|cual|cuál|sabéis|alguien sabe)/i', trim($padre_texto))
            );
            
            if ($es_pregunta) {
                // 80% Responder duda, 20% Tener la misma duda
                if (rand(1, 100) <= 80) {
                    $texto = $RESPUESTAS['respuesta_duda'][array_rand($RESPUESTAS['respuesta_duda'])];
                } else {
                    $texto = $RESPUESTAS['reaccion_pregunta_compartida'][array_rand($RESPUESTAS['reaccion_pregunta_compartida'])];
                }
            } else {
                $texto = $RESPUESTAS['reaccion_opinion'][array_rand($RESPUESTAS['reaccion_opinion'])];
            }

            $res = crearComentario([
                'chollo_id' => (string)$com_padre['chollo_id'],
                'usuario_id' => (string)$usuario['_id'],
                'comentario' => $texto,
                'padre_id' => (string)$com_padre['_id']
            ]);

            if ($res['success']) $log[] = "↪️ RESPUESTA ({$usuario['username']}): \"$texto\"";
            else $log[] = "❌ Error respuesta: " . $res['error'];
        } else {
            $accion = 'NUEVO'; // Fallback
        }
    }

    // --- ACCIÓN: NUEVO COMENTARIO ---
    if ($accion === 'NUEVO') {
        $now = time();
        $t = $now - ($config['max_antiguedad_chollo_nuevo'] * 60);
        $chollos = $collection_chollos->find([
            'fecha_creacion' => ['$gte' => new MongoDB\BSON\UTCDateTime($t * 1000)],
            'estado' => 1
        ], ['limit' => 10])->toArray();

        if (!empty($chollos)) {
            $chollo = $chollos[array_rand($chollos)];
            $usuario = $usuarios_fake[array_rand($usuarios_fake)];
            
            // SMART CONTEXT
            $marca = detectarMarca($chollo['titulo']);
            $tipo = detectarTipo($chollo['titulo']);
            $es_amazon = esAmazon($chollo); // Detectar si es Amazon

            // Elegir banco de textos
            $banco = isset($COMENTARIOS_CATEGORIA[$tipo]) ? $COMENTARIOS_CATEGORIA[$tipo] : $COMENTARIOS_CATEGORIA['generic'];
            
            // Si NO es Amazon, podemos añadir preguntas de logística al banco genérico
            if (!$es_amazon) {
                // Mezclar con preguntas logísticas (baja probabilidad)
                if (rand(1,100) <= 20) $banco = array_merge($banco, $PREGUNTAS_LOGISTICA);
            }
            
            $texto_raw = $banco[array_rand($banco)];
            $texto = procesarPlaceholder($texto_raw, $marca, $tipo);
            
            // Si procesarPlaceholder devuelve null (porque faltaba marca para {marca}), reintentar con GENERICO puro
            if ($texto === null) {
                $banco_back = $COMENTARIOS_CATEGORIA['generic'];
                // Filtrar solo los que NO tienen payloads o usar lógica segura
                // Simplemente elegimos uno genérico y lo procesamos (los genéricos no suelen tener {marca})
                $texto_raw = $banco_back[array_rand($banco_back)]; 
                $texto = procesarPlaceholder($texto_raw, '', ''); // Pasamos vacío, si tiene {marca} fallará de nuevo -> null
                
                if ($texto === null) {
                    $texto = "Buen chollo."; // Ultimate fallback
                }
            }

            $res = crearComentario([
                'chollo_id' => (string)$chollo['_id'],
                'usuario_id' => (string)$usuario['_id'],
                'comentario' => $texto
            ]);

            if ($res['success']) $log[] = "💬 NUEVO ({$usuario['username']} en {$chollo['titulo']}): \"$texto\"";
            else $log[] = "❌ Error nuevo: " . $res['error'];
        }
    }

    return implode("\n", $log);
}

echo ejecutarSimulacion($CONFIG);
