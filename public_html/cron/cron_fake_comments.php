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
    'dyson', 'roborock', 'colgate', 'ariel', 'fairy', 'finish', 'dodot', 'lego', 'funko', 'barbie'
];

$TIPOS_PRODUCTO = [
    'silla' => ['silla', 'trono', 'asiento', 'butaca'], // Prioridad sobre juego
    'periferico' => ['teclado', 'ratón', 'mouse', 'auriculares', 'cascos', 'mando', 'gamepad', 'volante'],
    'zapatillas' => ['zapatillas', 'zapato', 'bota', 'sneaker'],
    'móvil' => ['smartphone', 'móvil', 'movil', 'telefono', 'teléfono', 'iphone', 'galaxy', 'redmi', 'pixel'],
    'tv' => ['tv', 'televis', 'smart tv', 'oled', 'qled'],
    'portátil' => ['portatil', 'portátil', 'laptop', 'ordenador'],
    'aspiradora' => ['aspirador', 'roborock', 'dyson', 'conga'],
    'juego' => ['juego', 'game', 'ps5', 'ps4', 'playstation', 'switch', 'xbox', 'videojuego'],
    'reloj' => ['watch', 'reloj', 'smartwatch', 'band'],
    'cocina' => ['chopper', 'picadora', 'airfryer', 'batidora', 'freidora', 'cafetera', 'horno', 'alimento', 'verdura', 'carne', 'exprimidor', 'tostadora'],
    'joyeria' => ['anillo', 'collar', 'pulsera', 'pendientes', 'joya', 'plata', 'oro', 'brillante', 'zirconio'],
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
        "Opino igual, es muy buena oferta.",
        "Interesante, no lo había visto así.",
        "Es verdad, es un detalle importante.",
        "Gracias por el aviso, me lo apunto.",
        "Anotado, ¡gracias por el aporte!",
        "Me pasó igual, es un puntazo.",
        "Exacto, eso mismo pensaba yo.",
        "A ver si llego a tiempo, ¡qué pintaza!",
    ],
    'reaccion_pregunta_compartida' => [ // Responder a una duda sumándose a ella
        "Justo eso iba a preguntar yo. Gracias!",
        "Me interesa saber lo mismo, a ver si alguien lo ha probado.",
        "Yo tengo la misma duda, me quedo por aquí a ver qué dicen.",
        "A ver si alguien nos ilumina, que tiene buena pinta.",
    ]
];

// =========================================================================================
// PERSONALIDADES - FLAVORS & FILLERS
// =========================================================================================

$PERSONALIDADES = [
    'entusiasta' => [
        'prefijos' => ["¡Bua!", "¡Ofertón!", "¡Madre mía!", "¡Qué locura!", "¡Brutal!"],
        'sufijos' => ["🔥", "🚀", "🙌", "¡Pillado!", "¡Gracias por compartir!"],
        'chance' => 25
    ],
    'casual' => [
        'prefijos' => ["Oye,", "Buenas!", "Hola!", "Pues...", "La verdad es que"],
        'sufijos' => ["Ni tan mal.", "A ver qué tal sale.", "Me viene de lujo.", "👍"],
        'chance' => 40
    ],
    'critico' => [
        'prefijos' => ["Uff,", "Mmm,", "Sinceramente,", "Habrá que verlo,", "Ojo con esto,"],
        'sufijos' => ["¿No es un poco sospechoso?", "A ver si no lo cancelan.", "Espero que sea original.", "🤔"],
        'chance' => 20
    ],
    'experto' => [
        'prefijos' => ["Sinceramente,", "Por mi experiencia,", "He tenido varios y", "Ojo, que en estos modelos"],
        'sufijos' => ["Por este precio es top.", "No vas a encontrar nada mejor ahora mismo.", "Calidad-precio imbatible."],
        'chance' => 15
    ]
];

// Comentarios nuevos específicos por categoría
$COMENTARIOS_CATEGORIA = [
    'silla' => [
        "¿Es cómoda para estar sentado muchas horas? Mido 1.80m.",
        "¿El material da mucho calor en verano? Busco algo transpirable.",
        "¿Las ruedas hacen mucho ruido en parquet?",
        "¿El cojín lumbar es ajustable o viene fijo?",
        "Tengo problemas de espalda, ¿recomendáis esta silla?",
        "¿Hasta qué peso aguanta el pistón?",
        "¿Es difícil de montar? No soy muy manitas...",
    ],
    'periferico' => [
        "¿Sirve para PS5 o solo para PC?",
        "¿Qué tal es la calidad de construcción? ¿Se siente plástico malo?",
        "¿Tiene software propio para configurar luces y botones?",
        "¿Es inalámbrico o lleva cable?",
        "¿El tacto de los botones es clicky o silencioso?",
        "¿Tiene buena precisión para shooters?",
    ],
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
        "¿Sabéis si tiene NFC para pagos?",
        "¿Qué tal se ve la pantalla del {nombre} a plena luz del día?",
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
        "¿Qué tal la refrigeración del {nombre}? ¿Se calienta mucho?",
        "¿El teclado es español QWERTY con Ñ?",
        "Busco algo ligero para la uni, ¿este {tipo} pesa mucho?",
    ],
    'aspiradora' => [
        "¿Qué tal la potencia de succión de esta {marca}?",
        "¿Sirve para pelos de mascota?",
        "Tengo una {marca} antigua y quiero renovar, ¿esta merece la pena?",
        "Buen precio para este robot {tipo}.",
    ],
    'cuidado_personal' => [
        "¿Sabéis si este {tipo} es preciso? Lo necesito para los niños.",
        "¿Qué tipo de pilas usa este {marca}?",
        "¿Alguien tiene este {tipo} de {marca}? ¿Es duradero?",
        "¿Duele al usarlo? Es que tengo la piel sensible.",
        "Buen precio para ser de {marca}, suelen ser más caros.",
        "¿Qué tal la precisión del {nombre}?",
        "¿Cuánto le dura la batería al {nombre}?",
        "¿Recomendáis el {nombre} para uso diario?",
    ],
    'cocina' => [
        "¿Qué tal se limpia este {tipo}? Me da miedo que sea un lío.",
        "¿Pica bien el hielo o se encasquilla?",
        "Tengo una {tipo} parecida y va de lujo, esta marca es buena.",
        "¿Consume mucho este {marca}?",
        "Ideal para la cocina, me viene de perlas el {nombre}.",
        "¿Qué capacidad tiene el vaso del {nombre}?",
        "¿Alguien lo ha probado para hacer purés? ¿Quedan finos?",
    ],
    'joyeria' => [
        "¿Es plata de ley auténtica 925?",
        "¿Viene con estuche o caja de la marca para regalo?",
        "Me encanta el diseño de este {tipo}, ¿sabéis si se pone feo con el agua?",
        "¿Es {marca} original o imitación? El precio parece demasiado bueno.",
        "¿Tiene grabado el sello de 925 en el interior?",
        "¿Viene con certificado de autenticidad?",
    ],
    'generic_tech' => [
        "¿Sabéis si se conecta fácil por Bluetooth?",
        "¿Qué tal la calidad de los materiales? Parece plástico del malo.",
        "¿Viene con cable de carga incluido?",
        "¿Es compatible con iPhone y Android?",
        "Para el precio que tiene parece que cumple.",
    ],
    'generic_brand' => [
        "Me gusta mucho esta marca, {marca} suele salir buena.",
        "He tenido otras cosas de {marca} y cero problemas.",
        "¿Qué tal la garantía de {marca}? ¿Responden bien?",
        "Prefiero pagar un poco más y que sea {marca}.",
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
        "¿Qué tal sale el {nombre}? ¿Alguien lo tiene?",
    ],
    // BANCOS SITUACIONALES (Vida Real)
    'situacional_familia' => [
        "Pillado para casa, a ver si así ahorramos un poco o nos matan 😂",
        "¿Sabéis si aguanta bien los golpes? Mis hijos no perdonan ni una...",
        "¡Bua! me viene de lujo para el cumple del peque, que le encanta {marca}.",
        "Ideal para la familia, me lo apunto sin falta.",
    ],
    'situacional_estudios' => [
        "Sinceramente, para el presupuesto que tengo de estudiante, esto es gloria.",
        "¿Pesa mucho para llevarlo en la mochila todo el día a la uni?",
        "Ofertón, por fin algo decente para mi cuarto de estudio. 🚀",
        "¿Lo recomendáis para llevar a clase o es muy trasto?",
    ],
    'situacional_trabajo' => [
        "Lo necesito para el curro, ¿alguien sabe si rinde bien con mucha tralla?",
        "La verdad es que por mi experiencia, para trabajar va sobrado.",
        "Justo buscaba algo así para la oficina, ¡gracias por el aporte!",
        "¿Se calienta mucho si lo tengo encendido 8 horas seguidas en el despacho?",
    ]
];

// --- RESPUESTAS TÉCNICAS (Keyword Based) ---
$RESPUESTAS_TECNICAS = [
    'bateria' => [
        "Dura bastante, a mí me aguanta días.",
        "Lleva pilas normales, así que sin problema.",
        "La batería es lo mejor que tiene, muy buena autonomía.",
        "Se carga rápido, en una hora lo tienes listo.",
    ],
    'ram' => [
        "Creo que lleva un slot libre para ampliar, pero confírmalo.",
        "Para un uso normal con 16GB vas sobrado.",
        "Suele venir soldada en esta gama, ojo.",
    ],
    'pantalla' => [
        "Se ve de lujo incluso con luz directa.",
        "Tiene buen brillo y los colores son vivos.",
        "La resolución es top para este precio.",
        "Ojo que el panel es IPS de los buenos.",
    ],
    'teclado' => [
        "Sí, viene con Ñ, es el layout español.",
        "El tacto es muy cómodo para escribir mucho.",
    ],
    'peso' => [
        "Es súper ligero, no llega a los 2kg.",
        "Para ser gaming pesa lo suyo, pero compensa por potencia.",
        "Se nota robusto pero no es excesivamente pesado.",
    ],
    'pesa' => [
        "Es súper ligero, no llega a los 2kg.",
        "Para ser gaming pesa lo suyo, pero compensa por potencia.",
        "La verdad es que es bastante liviano para estas specs.",
    ],
    'refrigeracion' => [
        "Con un uso normal ni se oyen los ventiladores.",
        "Jugando sube un poco la temperatura pero nada exagerado.",
        "El sistema de ventilación de esta gama es bastante decente.",
    ],
    'calienta' => [
        "Con un uso normal ni se oyen los ventiladores.",
        "Jugando sube un poco la temperatura pero nada exagerado.",
        "Es verdad que bajo carga se nota algo de calor, pero es normal.",
    ],
    'sobrado' => [
        "Para un uso normal (internet, vídeos, office) va sobradísimo.",
        "Sí, con esas specs no vas a tener problemas de rendimiento.",
        "Va muy fluido, la verdad que no se queda corto.",
    ],
    'gaming' => [
        "Para jugar en 1080p va genial con esa gráfica.",
        "Mueve la mayoría de juegos actuales en calidad media-alta.",
        "Si lo quieres para jugar, es una de las mejores opciones por este precio.",
    ],
    'ofimatica' => [
        "Para trabajar y estudiar es ideal, muy rápido abriendo programas.",
        "Sí, de hecho es hasta demasiado potente solo para ofimática, te durará años.",
        "Para el paquete Office y navegar va como un tiro.",
    ],
    'potencia' => [
        "Tiene potencia de sobra para el 90% de los usuarios.",
        "El procesador es bastante potente para este rango de precios.",
        "No le falta fuerza, aguanta bien la multitarea.",
    ],
    'specs' => [
        "Por estas especificaciones es difícil encontrar algo mejor ahora mismo.",
        "Las características están muy compensadas, buen equilibrio.",
        "Specs muy sólidas para el precio que tiene.",
    ],
    'talla' => [
        "Tallan normal, pide tu número habitual.",
        "Ojo que vienen un poco ajustadas, igual pide una más.",
        "Son fieles a la talla, a mí me quedan clavadas.",
    ],
    'preciso' => [ // Termómetros, básculas
        "Sí, lo he comparado con el de farmacia y da igual.",
        "Es bastante exacto, no falla.",
        "Para uso doméstico sobra, muy fiable.",
    ],
    'limpia' => [
        "Se desmonta fácil y al lavavajillas, súper cómodo.",
        "La limpieza es lo peor, pero como pica tan bien compensa.",
        "Se limpia en un momento bajo el grifo.",
    ],
    'pica' => [
        "Pica de todo, hasta frutos secos y hielo sin despeinarse.",
        "Las cuchillas son muy potentes, pican la carne súper fino.",
        "Para verduras va genial, lo deja todo perfecto.",
    ],
    'potencia' => [
        "Tiene fuerza de sobra, no se queda corto.",
        "Para el tamaño que tiene, me ha sorprendido la potencia.",
        "Va sobrado para un uso normal en casa.",
    ],
    'ruido' => [ // Aspiradoras, ventiladores
        "No hace mucho ruido, se puede usar de noche.",
        "Es bastante silencioso comparado con otros.",
        "En modo turbo suena un poco, pero lo normal.",
    ],
    'original' => [
        "Sí sí, es original 100%, vendido por Amazon.",
        "Viene precintado y con garantía oficial, quédate tranquilo.",
        "Totalmente original, comprobado con el de tienda oficial.",
    ],
    'garantia' => [
        "Tienes los 3 años de garantía legal, sin problema.",
        "Si es Amazon no tienes problema, su servicio postventa es el mejor.",
    ],
    'envío' => [ // Inlcuyendo "llegue", "tiempo"
        "Amazon suele ser muy rápido, mañana o pasado lo tienes en casa.",
        "A mí me llegó en un par de días, no te preocupes por el envío.",
        "Depende de dónde vivas, pero suelen cumplir plazos a rajatabla.",
    ],
    'llegue' => [
        "Tranqui, que a estas alturas los envíos vuelan, te llega fijo.",
        "Seguro que te llega a tiempo, Amazon es súper serio con eso.",
        "A mí me pasó el año pasado y me llegó incluso antes de lo esperado.",
    ],
    'gracias' => [
        "¡De nada! Espero que te sirva el chollo, yo ya lo he pillado.",
        "¡A mandar! Entre todos nos ayudamos a ahorrar un poco, que falta hace.",
        "¡Un placer compartirlo! A disfrutarlo cuando te llegue, ya me contarás.",
    ],
    // RESPUESTAS SITUACIONALES (Storytelling)
    'uni' => [
        "Yo lo llevo a la facultad a diario y ni se nota en el hombro, muy ligero.",
        "Para estudiar en la biblioteca va genial porque no hace nada de ruido.",
        "Como estudiante te digo que es la mejor inversión que puedes hacer ahora.",
    ],
    'niños' => [
        "A mis hijos les encanta y por ahora resiste toda la guerra que le dan.",
        "Para los críos es perfecto, muy intuitivo y fácil de usar.",
        "Lo pillé para un sobrino y sus padres están encantados con el regalo.",
    ],
    'oficina' => [
        "En mi despacho tenemos varios de estos y aguantan las 8 horas sin despeinarse.",
        "Para el trabajo del día a día cumple de sobra, muy fluido todo.",
        "Lo uso para el teletrabajo y me ha cambiado la vida la verdad.",
    ],
    'plata' => [
        "Sí, viene grabado 925 en el interior, es de ley auténtica.",
        "Confirmo que es plata de ley, no es el típico baño que se va.",
        "El mío vino perfecto con su certificado y todo en su caja.",
    ],
    '925' => [
        "Sí, tiene el sello de 925 bien visible, lo he comprobado.",
        "Es plata auténtica, se nota en el peso y sobre todo en el brillo.",
    ],
    'auténtico' => [
        "Viene con el packaging original de la marca, todo en regla.",
        "Es 100% original, yo lo compré y es idéntico al de El Corte Inglés.",
    ]
];

$RESPUESTAS_DUDA_GENERICAS = [
    "Creo que sí, pero revísalo por si acaso.",
    "En las especificaciones suele ponerlo.",
    "A mí me funciona perfecto para eso.",
    "Sí, confirmado.",
    "Diría que sí.",
];

// --- HELPER FUNCTIONS ---

function limpiarTituloProducto($titulo) {
    // Eliminar palabras "oferta", "chollo", porcentajes, precios...
    $t = preg_replace('/(oferta|chollo|descuento|rebaja|precio|barato|amazon|\d+%|\d+€)/i', '', $titulo);
    // Eliminar caracteres raros al inicio/fin
    $t = trim($t, " -.|,");
    // Cortar si es muy largo (títulos de Amazon kilométricos)
    if (strlen($t) > 40) {
        $parts = explode(' ', $t);
        $t = implode(' ', array_slice($parts, 0, 5)); // Coger primeras 5 palabras
    }
    return trim($t);
}

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
    
    // Keywords dinámicas si no están en el array global
    if (!isset($TIPOS_PRODUCTO['cuidado_personal'])) {
        $TIPOS_PRODUCTO['cuidado_personal'] = ['termometro', 'termómetro', 'afeitadora', 'depiladora', 'cepillo', 'masajeador', 'tensiometro'];
    }
    if (!isset($TIPOS_PRODUCTO['joyeria'])) {
         $TIPOS_PRODUCTO['joyeria'] = ['anillo', 'collar', 'pulsera', 'pendientes', 'joya', 'plata', 'oro', 'brillante', 'zirconio', 'charms'];
    }
    // Detección genérica de tecnología si no cae en otra categoría
    if (!isset($TIPOS_PRODUCTO['generic_tech'])) {
        $TIPOS_PRODUCTO['generic_tech'] = ['bluetooth', 'wifi', 'usb', 'inalámbrico', 'inalambrico', 'batería', 'bateria', 'carga', 'cable', 'hdmi', '4k', 'smart'];
    }
    
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

function procesarPlaceholder($texto, $marca, $tipo, $nombre_producto = '') {
    global $PERSONALIDADES;
    
    if (strpos($texto, '{marca}') !== false && empty($marca)) {
        return null;
    }
    if (strpos($texto, '{tipo}') !== false && empty($tipo)) {
        return null;
    }
    if (strpos($texto, '{nombre}') !== false && empty($nombre_producto)) {
        return null;
    }

    $texto = str_replace('{marca}', $marca, $texto);
    $texto = str_replace('{tipo}', $tipo, $texto);
    $texto = str_replace('{nombre}', $nombre_producto, $texto);
    
    // HUMANIZAR - Añadir personalidad (60% de probabilidad)
    if (rand(1, 100) <= 60) {
        // Elegir personalidad por pesos
        $rand = rand(1, 100);
        $total = 0;
        $perfil_key = 'casual';
        foreach ($PERSONALIDADES as $key => $p) {
            $total += $p['chance'];
            if ($rand <= $total) {
                $perfil_key = $key;
                break;
            }
        }
        
        $perfil = $PERSONALIDADES[$perfil_key];
        
        // 40% añadir prefijo
        if (rand(1, 100) <= 40) {
            $pref = $perfil['prefijos'][array_rand($perfil['prefijos'])];
            // Si el texto empieza por ¿ o ¡, no hacemos lcfirst
            if (mb_substr($texto, 0, 1) === '¿' || mb_substr($texto, 0, 1) === '¡') {
                $texto = $pref . " " . $texto;
            } else {
                $texto = $pref . " " . lcfirst($texto);
            }
        }
        
        // 40% añadir sufijo
        if (rand(1, 100) <= 40) {
            $suf = $perfil['sufijos'][array_rand($perfil['sufijos'])];
            // Asegurar que el texto base termina en puntuación si el sufijo es descriptivo
            $last_char = mb_substr($texto, -1);
            if (!in_array($last_char, ['.', '!', '?', '🔥', '🚀', '🙌'])) {
                $texto .= ".";
            }
            $texto = rtrim($texto, ". ") . " " . $suf;
        }
    }

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
        
        // Solo buscar comentarios PADRE (Nivel 0) para responder
        // Esto evita anidamiento infinito (>1 nivel)
        $cursor = $collection_comentarios->find([
            'fecha' => ['$gte' => new MongoDB\BSON\UTCDateTime($min_t * 1000), '$lte' => new MongoDB\BSON\UTCDateTime($max_t * 1000)],
            'padre_id' => null // CRÍTICO: Solo respondemos a hilos principales
        ], ['limit' => 20]);
        $candidatos = iterator_to_array($cursor);

        if (!empty($candidatos)) {
            $com_padre = $candidatos[array_rand($candidatos)];
            $usuario = $usuarios_fake[array_rand($usuarios_fake)];
            
            // Evitar auto-respuesta
            if ((string)$com_padre['usuario_id'] === (string)$usuario['_id']) {
                 $usuario = $usuarios_fake[array_rand($usuarios_fake)];
            }
            
            // DETECTAR INTENCIÓN Y KEYWORDS
            $padre_texto = $com_padre['comentario'] ?? '';
            $es_pregunta = (
                strpos($padre_texto, '?') !== false || 
                stripos($padre_texto, '¿') !== false ||
                preg_match('/^(quién|cómo|cuándo|dónde|por qué|qué|cual|cuál|sabéis|alguien sabe)/i', trim($padre_texto))
            );
            
            if ($es_pregunta) {
                // INTENTO DE RESPUESTA TÉCNICA
                $keyword_found = false;
                foreach ($RESPUESTAS_TECNICAS as $key => $respuestas) {
                    if (stripos($padre_texto, $key) !== false) {
                        $texto = $respuestas[array_rand($respuestas)];
                        $keyword_found = true;
                        break;
                    }
                }
                
                if (!$keyword_found) {
                    // 80% Responder duda genérica, 20% Tener la misma duda
                    if (rand(1, 100) <= 80) {
                        $texto = $RESPUESTAS_DUDA_GENERICAS[array_rand($RESPUESTAS_DUDA_GENERICAS)];
                    } else {
                        $texto = $RESPUESTAS['reaccion_pregunta_compartida'][array_rand($RESPUESTAS['reaccion_pregunta_compartida'])];
                    }
                }
            } else {
                // REACCIÓN A OPINIÓN (También con keywords inteligentes)
                $keyword_found = false;
                foreach (['gracias', 'llegue', 'talla', 'original'] as $key) {
                    if (stripos($padre_texto, $key) !== false && isset($RESPUESTAS_TECNICAS[$key])) {
                        $texto = $RESPUESTAS_TECNICAS[$key][array_rand($RESPUESTAS_TECNICAS[$key])];
                        $keyword_found = true;
                        break;
                    }
                }
                
                if (!$keyword_found) {
                    $texto = $RESPUESTAS['reaccion_opinion'][array_rand($RESPUESTAS['reaccion_opinion'])];
                }
            }

            // --- EVITAR DUPLICADOS EN EL MISMO CHOLLO ---
            $existe = $collection_comentarios->findOne([
                'chollo_id' => $com_padre['chollo_id'],
                'comentario' => $texto
            ]);
            if ($existe) {
                // Si ya existe, intentamos elegir otro aleatorio del banco genérico una vez
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
            $nombre = limpiarTituloProducto($chollo['titulo']);
            $es_amazon = esAmazon($chollo); // Detectar si es Amazon

            // Elegir banco de textos
            $banco = isset($COMENTARIOS_CATEGORIA[$tipo]) ? $COMENTARIOS_CATEGORIA[$tipo] : $COMENTARIOS_CATEGORIA['generic'];
            
            // FALLBACK SMART: Si es tipo genérico pero conocemos la MARCA, usar `generic_brand`
            if ($tipo === '' && !empty($marca) && isset($COMENTARIOS_CATEGORIA['generic_brand'])) {
                $banco = $COMENTARIOS_CATEGORIA['generic_brand'];
            }
            
            // Si NO es Amazon, podemos añadir preguntas de logística al banco (solo si no es marca fallback, para no diluir)
            if (!$es_amazon) {
                // Mezclar con preguntas logísticas (baja probabilidad)
                if (rand(1,100) <= 20) $banco = array_merge($banco, $PREGUNTAS_LOGISTICA);
            }
            
            $texto_raw = $banco[array_rand($banco)];
            $texto = procesarPlaceholder($texto_raw, $marca, $tipo, $nombre);
            
            // Si procesarPlaceholder devuelve null (porque faltaba marca para {marca}), reintentar con GENERICO puro
            if ($texto === null) {
                $banco_back = $COMENTARIOS_CATEGORIA['generic'];
                // Filtrar solo los que NO tienen payloads o usar lógica segura
                // Simplemente elegimos uno genérico y lo procesamos (los genéricos no suelen tener {marca})
                $texto_raw = $banco_back[array_rand($banco_back)]; 
                $texto = procesarPlaceholder($texto_raw, '', '', ''); // Pasamos vacío
                
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

// Solo ejecutar si se llama directamente (no si se incluye desde otro script)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    echo ejecutarSimulacion($CONFIG);
}
