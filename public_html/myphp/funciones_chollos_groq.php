<?php

/**
 * Funciones para reescribir textos de chollos usando Groq API
 */

// Incluir configuración de IA
if (file_exists(__DIR__ . '/../config/ai_config.php')) {
    include_once __DIR__ . '/../config/ai_config.php';
}

// Incluir funciones de Amazon para detectar enlaces
if (!function_exists('esEnlaceAmazon')) {
    include_once __DIR__ . '/funciones_chollos_amazon.php';
}

/**
 * Reescribe un texto usando Groq API
 */
function reescribirTextoGroq($texto_original, $tipo = 'general') {
    if (empty($texto_original)) {
        return ['success' => false, 'error' => 'Texto vacío'];
    }

    $api_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    
    if (empty($api_key) || $api_key === 'gsk-your-api-key-here') {
        return ['success' => false, 'error' => 'API key de Groq no configurada'];
    }

    // Prompts según el tipo
    $prompts = [
        'titulo' => 'Reescribe este título de chollo de forma atractiva y SEO-friendly, manteniendo la información clave del producto y el descuento. Máximo 80 caracteres. Solo devuelve el título reescrito, sin explicaciones:',
        'descripcion' => 'Reescribe esta descripción de chollo de forma atractiva, destacando las ventajas y beneficios del producto. Usa un tono persuasivo pero honesto. Máximo 200 palabras. Solo devuelve la descripción reescrita, sin explicaciones:',
        'general' => 'Reescribe este texto de chollo de forma atractiva y profesional, manteniendo toda la información importante sobre el producto y la oferta. Solo devuelve el texto reescrito, sin explicaciones:',
        'seo_optimizer' => 'Actúa como un experto SEO. Genera un Título H1 optimizado para Google para la siguiente Keyword. El título debe ser atractivo, incluir la keyword principal al inicio si es posible y tener gancho comercial (CTR alto). Máximo 60 caracteres. Solo devuelve el título, nada más:',
        'seo_full_optimizer' => 'Eres un experto en SEO y Copywriting. Tu tarea es optimizar una página de códigos de descuento. 
            Keyword principal: {keyword}. 
            Marca: {brand}.
            Actual H1: {h1}.
            Actual H2: {h2}.
            
            Debes devolver un JSON con la siguiente estructura:
            {
                "h1": "Nuevo H1 atractivo con la keyword",
                "h2": "Nuevo H2 persuasivo",
                "descripcion": "Meta descripción optimizada (máx 155 caracteres)",
                "seo_que_es": "Un párrafo largo (mínimo 100 palabras) explicando qué es la marca y sus beneficios para el SEO",
                "seo_tips": "3 consejos rápidos para ahorrar en esta marca"
            }
            El tono debe ser profesional y enfocado al ahorro. Devuelve EXCLUSIVAMENTE el JSON, sin texto adicional.'
    ];

    $prompt = $prompts[$tipo] ?? $prompts['general'];
    $prompt_completo = $prompt . "\n\n" . $texto_original;

    $data = [
        'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt_completo
            ]
        ],
        'max_tokens' => defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 2000,
        'temperature' => defined('AI_TEMPERATURE') ? AI_TEMPERATURE : 0.7
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, defined('GROQ_API_URL') ? GROQ_API_URL : 'https://api.groq.com/openai/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, defined('AI_TIMEOUT') ? AI_TIMEOUT : 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Error cURL Groq: " . $error);
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }

    if ($http_code !== 200) {
        error_log("Error HTTP Groq: " . $http_code . " - " . $response);
        return ['success' => false, 'error' => 'Error de API: ' . $http_code];
    }

    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        $texto_reescrito = trim($result['choices'][0]['message']['content']);
        
        // Limpiar el texto si tiene comillas o formato extra
        $texto_reescrito = preg_replace('/^["\']|["\']$/', '', $texto_reescrito);
        $texto_reescrito = trim($texto_reescrito);

        return [
            'success' => true,
            'texto_reescrito' => $texto_reescrito,
            'texto_original' => $texto_original
        ];
    }

    return ['success' => false, 'error' => 'No se pudo obtener respuesta de Groq'];
}

/**
 * Reescribe título y descripción de un chollo
 */
function reescribirCholloCompleto($titulo_original, $descripcion_original) {
    $resultado = [
        'titulo' => ['success' => false],
        'descripcion' => ['success' => false]
    ];

    // Reescribir título
    if (!empty($titulo_original)) {
        $resultado_titulo = reescribirTextoGroq($titulo_original, 'titulo');
        $resultado['titulo'] = $resultado_titulo;
    }

    // Reescribir descripción
    if (!empty($descripcion_original)) {
        $resultado_descripcion = reescribirTextoGroq($descripcion_original, 'descripcion');
        $resultado['descripcion'] = $resultado_descripcion;
    }

    return $resultado;
}

/**
 * Procesa un chollo completo con Groq: reescribe descripción y categoriza
 * 
 * @param string $titulo Título del chollo
 * @param string $descripcion Descripción del chollo
 * @param string $texto_original Texto original completo (opcional, para mejor categorización)
 * @return array Resultado con descripción reescrita y categoría detectada
 */
function procesarCholloConGroq($titulo, $descripcion = '', $texto_original = '') {
    $resultado = [
        'success' => false,
        'descripcion_reescrita' => $descripcion,
        'categoria' => ['general'], // Siempre array
        'titulo_reescrito' => $titulo,
        'error' => null
    ];

    $api_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    
    if (empty($api_key) || $api_key === 'gsk-your-api-key-here') {
        $resultado['error'] = 'API key de Groq no configurada';
        return $resultado;
    }

    // Construir el texto completo para análisis
    $texto_completo = trim($titulo . "\n\n" . $descripcion . "\n\n" . $texto_original);
    $texto_completo = trim($texto_completo);

    if (empty($texto_completo)) {
        $resultado['error'] = 'Texto vacío';
        return $resultado;
    }

    // Prompt combinado para reescribir y categorizar
    $prompt = "Analiza este chollo y realiza las siguientes tareas:

1. Reescribe la descripción de forma atractiva, destacando las ventajas y beneficios del producto. Usa un tono persuasivo pero honesto. Máximo 200 palabras.

2. Genera una estructura de categorías HIERÁRQUICA y ESPECÍFICA para este producto.
   - Crea las categorías que consideres necesarias para clasificar perfectamente el producto. NO te limites a una lista fija.
   - La estructura debe ser: [\"Categoría Principal\", \"Subcategoría\", \"Sub-subcategoría\" (si aplica)].
   - Ejemplo: Si es un juego de mesa Monopoly: [\"Juguetes\", \"Juegos de Mesa\", \"Juegos de Estrategia\"]
   - Ejemplo: Si es un iPhone: [\"Electrónica\", \"Telefonía\", \"Smartphones\", \"Apple\"]
   - Sé lo más específico posible.

Responde SOLO en formato JSON válido con esta estructura exacta:
{
  \"descripcion\": \"descripción reescrita aquí\",
  \"categoria\": [\"Categoría Principal\", \"Subcategoría\"],
  \"titulo\": \"título mejorado (opcional, solo si puede mejorarse)\"
}

Texto del chollo:
" . $texto_completo;


    $data = [
        'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 2000,
        'temperature' => defined('AI_TEMPERATURE') ? AI_TEMPERATURE : 0.7,
        'response_format' => ['type' => 'json_object'] // Forzar respuesta JSON
    ];

    // Reintentar hasta 3 veces en caso de error
    $max_intentos = 3;
    $intento = 0;
    $response = null;
    $http_code = 0;
    $error = null;
    
    while ($intento < $max_intentos) {
        $intento++;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, defined('GROQ_API_URL') ? GROQ_API_URL : 'https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, defined('AI_TIMEOUT') ? AI_TIMEOUT : 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Si fue exitoso, salir del bucle
        if (!$error && $http_code === 200) {
            break;
        }
        
        // Si es rate limiting (429), esperar más tiempo con un poco de aleatoriedad (jitter)
        if ($http_code === 429 && $intento < $max_intentos) {
            $espera = (2 * $intento) + rand(1, 3); // Esperar 3-5, 5-7, 7-9 segundos
            error_log("Rate limit alcanzado en Groq, esperando {$espera}s antes de reintentar (intento {$intento}/{$max_intentos})");
            sleep($espera); 
            continue;
        }
        
        // Si es otro error y quedan intentos, esperar un poco
        if ($intento < $max_intentos) {
            error_log("Error en Groq, reintentando (intento {$intento}/{$max_intentos}): " . ($error ?: "HTTP {$http_code}"));
            sleep(1);
        }
    }

    if ($error) {
        error_log("Error cURL Groq después de {$intento} intentos: " . $error);
        $resultado['error'] = 'Error de conexión: ' . $error;
        return $resultado;
    }

    if ($http_code !== 200) {
        error_log("Error HTTP Groq después de {$intento} intentos: " . $http_code . " - " . $response);
        $resultado['error'] = 'Error de API: ' . $http_code;
        return $resultado;
    }

    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        $content = trim($result['choices'][0]['message']['content']);
        
        // Intentar parsear JSON
        $json_data = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
            // Datos válidos en JSON
            if (isset($json_data['descripcion']) && !empty($json_data['descripcion'])) {
                $resultado['descripcion_reescrita'] = trim($json_data['descripcion']);
            }
            
            if (isset($json_data['categoria']) && !empty($json_data['categoria'])) {
                // Aceptar tanto array como string
                $categorias = [];
                if (is_array($json_data['categoria'])) {
                    $categorias = $json_data['categoria'];
                } else {
                    $categorias = [trim($json_data['categoria'])];
                }
                
                // Normalizar categorías (primera letra mayúscula, resto minúscula para consistencia, o tal cual vienen)
                // Vamos a capitalizar cada palabra para que sea "Juegos De Mesa"
                $categorias = array_map(function($cat) {
                    return mb_convert_case(trim($cat), MB_CASE_TITLE, "UTF-8");
                }, $categorias);
                
                // NO filtramos contra lista predefinida para permitir creación al vuelo
                if (!empty($categorias)) {
                    $resultado['categoria'] = array_values($categorias); // Siempre array
                }
            }
            
            if (isset($json_data['titulo']) && !empty($json_data['titulo'])) {
                $resultado['titulo_reescrito'] = trim($json_data['titulo']);
            }
            
            $resultado['success'] = true;
        } else {
            // Si no es JSON válido, intentar extraer información del texto
            // Buscar descripción entre comillas o después de "descripcion:"
            if (preg_match('/"descripcion"\s*:\s*"([^"]+)"/i', $content, $matches)) {
                $resultado['descripcion_reescrita'] = trim($matches[1]);
            } elseif (preg_match('/descripcion[:\s]+(.+?)(?:\n|"categoria|$)/i', $content, $matches)) {
                $resultado['descripcion_reescrita'] = trim($matches[1]);
            }
            
            // Buscar categoría (puede ser array o string)
            if (preg_match('/"categoria"\s*:\s*\[([^\]]+)\]/i', $content, $matches)) {
                // Es un array
                preg_match_all('/"([^"]+)"/', $matches[1], $cat_matches);
                $categorias = array_map(function($cat) {
                    return mb_convert_case(trim($cat), MB_CASE_TITLE, "UTF-8");
                }, $cat_matches[1]);
            } elseif (preg_match('/"categoria"\s*:\s*"([^"]+)"/i', $content, $matches)) {
                // Es un string
                $categorias = [mb_convert_case(trim($matches[1]), MB_CASE_TITLE, "UTF-8")];
            }
            
            if (isset($categorias) && !empty($categorias)) {
                $resultado['categoria'] = array_values($categorias); // Siempre array
            }
            
            if (!empty($resultado['descripcion_reescrita']) || (isset($resultado['categoria']) && !empty($resultado['categoria']))) {
                $resultado['success'] = true;
            } else {
                $resultado['error'] = 'No se pudo parsear la respuesta de Groq';
                error_log("Respuesta Groq no parseable: " . $content);
            }
        }
    } else {
        $resultado['error'] = 'No se pudo obtener respuesta de Groq';
    }

    return $resultado;
}

/**
 * Procesa un PAQUETE de chollos con Groq en una sola llamada
 * Optimiza el uso de tokens y evita rate limits
 * 
 * @param array $paquete Array de chollos, cada uno con ['id', 'titulo', 'descripcion', 'texto_original']
 * @return array Array de resultados indexados por el ID original
 */
function procesarPaqueteChollosConGroq($paquete) {
    if (empty($paquete)) {
        return [];
    }

    $api_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    if (empty($api_key)) return [];

    // Construir el prompt para múltiples chollos
    $input_text = "";
    foreach ($paquete as $item) {
        $id = $item['id_interno'];
        $titulo = $item['titulo'] ?? '';
        $desc = $item['descripcion'] ?? '';
        $original = $item['texto_original'] ?? '';
        
        $input_text .= "--- CHOLLO_ID: {$id} ---\n";
        $input_text .= "TITULO: {$titulo}\n";
        $input_text .= "DESC: {$desc}\n";
        $input_text .= "TEXTO_ORIGINAL: {$original}\n\n";
    }

    $prompt = "Analiza estos " . count($paquete) . " chollos y para CADA UNO realiza estas tareas:
1. Reescribe la descripción de forma atractiva, destacando ventajas. Máximo 200 palabras.
2. Genera una estructura de categorías HIERÁRQUICA y ESPECÍFICA (ej: [\"Electrónica\", \"Telefonía\", \"Smartphones\"]).
3. Mejora el título si se puede hacer más atractivo.

Responde ÚNICAMENTE un objeto JSON donde las llaves sean los CHOLLO_ID y los valores sean objetos con {descripcion, categoria, titulo}.

Chollos a procesar:
" . $input_text;

    $data = [
        'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 4000, 
        'temperature' => 0.6,
        'response_format' => ['type' => 'json_object']
    ];

    // Reintentar hasta 3 veces en caso de error
    $max_intentos = 3;
    $intento = 0;
    $response = null;
    $http_code = 0;
    $error = null;
    
    while ($intento < $max_intentos) {
        $intento++;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, defined('GROQ_API_URL') ? GROQ_API_URL : 'https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Si fue exitoso, salir del bucle
        if (!$error && $http_code === 200) {
            break;
        }
        
        // Si es rate limiting (429), esperar más tiempo con un poco de aleatoriedad (jitter)
        if ($http_code === 429 && $intento < $max_intentos) {
            $espera = (3 * $intento) + rand(2, 5); // Esperar 5-8, 8-11, 11-14 segundos
            error_log("Rate limit alcanzado en procesarPaqueteChollosConGroq, esperando {$espera}s antes de reintentar (intento {$intento}/{$max_intentos})");
            sleep($espera); 
            continue;
        }
        
        // Si es otro error y quedan intentos, esperar un poco
        if ($intento < $max_intentos) {
            error_log("Error en procesarPaqueteChollosConGroq, reintentando (intento {$intento}/{$max_intentos}): " . ($error ?: "HTTP {$http_code}"));
            sleep(2);
        }
    }

    if ($http_code === 200) {
        $result = json_decode($response, true);
        $content = $result['choices'][0]['message']['content'] ?? '{}';
        $json_res = json_decode($content, true);
        
        if (is_array($json_res)) {
            foreach ($json_res as $id => &$item) {
                if (isset($item['categoria']) && is_array($item['categoria'])) {
                    $item['categoria'] = array_map(function($cat) {
                        return mb_convert_case(trim($cat), MB_CASE_TITLE, "UTF-8");
                    }, $item['categoria']);
                }
            }
            return $json_res;
        }
    }

    error_log("Error en procesarPaqueteChollosConGroq: HTTP {$http_code} - " . $response);
    return [];
}

/**
 * Extrae información de un texto de chollo (título, precio, descuento, etc.)
 */
/**
 * Limpia el texto de Telegram eliminando markdown y caracteres sobrantes
 */
function limpiarTextoTelegram($texto) {
    if (empty($texto)) {
        return $texto;
    }
    
    // PRIMERO: Eliminar sección de "Síguenos también para ofertas en..." y todo lo que sigue
    // Esto debe hacerse ANTES de proteger URLs para no incluir URLs de otros canales
    $texto = preg_replace('/síguenos también para ofertas en.*$/i', '', $texto);
    $texto = preg_replace('/síguenos también.*$/i', '', $texto);
    $texto = preg_replace('/⭐\s*\[.*?\]\s*\([^\)]+\)/i', '', $texto); // Eliminar enlaces con ⭐
    $texto = preg_replace('/\[ALIEXPRESS\].*$/i', '', $texto);
    $texto = preg_replace('/\[MIRAVIA\].*$/i', '', $texto);
    $texto = preg_replace('/\[Whatsapp\].*$/i', '', $texto);
    $texto = preg_replace('/\[WhatsApp\].*$/i', '', $texto);
    
    // Eliminar hashtags de publicidad y referencias a otros canales
    $texto = preg_replace('/#Publicidad\b/i', '', $texto);
    $texto = preg_replace('/#\s*Publicidad\b/i', '', $texto);
    $texto = preg_replace('/\b#Publicidad\b/i', '', $texto);
    $texto = preg_replace('/\s*#\s*[Pp]ublicidad\b/i', '', $texto);
    // Eliminar cualquier hashtag que contenga "publicidad" o "publicidad"
    $texto = preg_replace('/#\w*[Pp]ublicidad\w*/i', '', $texto);
    // Eliminar referencias a otros canales (formato @canal o #canal)
    $texto = preg_replace('/@\w+\s*/i', '', $texto); // Eliminar menciones @canal
    $texto = preg_replace('/#\w*canal\w*/i', '', $texto); // Eliminar hashtags de canales
    
    // Ahora proteger las URLs para no perderlas (usar un placeholder más seguro)
    $urls = [];
    $patron_url = '/(https?:\/\/[^\s<>"\'\)]+)/i';
    $texto = preg_replace_callback($patron_url, function($matches) use (&$urls) {
        $placeholder = 'URLPLACEHOLDER' . count($urls) . 'URLPLACEHOLDER';
        $urls[] = $matches[1];
        return $placeholder;
    }, $texto);
    
    // Eliminar enlaces markdown [texto](url) -> texto (antes de limpiar otros markdown)
    $texto = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $texto);
    
    // Eliminar markdown de Telegram (múltiples pasadas para casos anidados)
    // **texto** o __texto__ -> texto
    $texto = preg_replace('/\*\*([^*]+)\*\*/', '$1', $texto);
    $texto = preg_replace('/__([^_]+)__/', '$1', $texto);
    $texto = preg_replace('/\*([^*]+)\*/', '$1', $texto);
    $texto = preg_replace('/_([^_]+)_/', '$1', $texto);
    
    // Eliminar código inline `texto` -> texto
    $texto = preg_replace('/`([^`]+)`/', '$1', $texto);
    
    // Eliminar emojis de formato especiales de Telegram
    $texto = preg_replace('/[✴️❌✅🌏✔️🔥]/u', '', $texto);
    
    // Eliminar caracteres especiales de formato [‍] y similares
    $texto = preg_replace('/\[‍\]/', '', $texto);
    
    // Eliminar URLs truncadas (que terminan en ...)
    $texto = preg_replace('/\(https?:\/\/[^\)]*\.\.\.[^\)]*\)/i', '', $texto);
    $texto = preg_replace('/https?:\/\/[^\s]*\.\.\.[^\s]*/i', '', $texto);
    
    // Eliminar caracteres de control y espacios múltiples
    $texto = preg_replace('/\s+/', ' ', $texto);
    
    // Limpiar espacios al inicio y final
    $texto = trim($texto);
    
    // Restaurar URLs (en orden inverso para evitar conflictos)
    for ($i = count($urls) - 1; $i >= 0; $i--) {
        $texto = str_replace('URLPLACEHOLDER' . $i . 'URLPLACEHOLDER', $urls[$i], $texto);
    }
    
    return $texto;
}

function extraerInfoChollo($texto) {
    // Limpiar el texto primero
    $texto = limpiarTextoTelegram($texto);
    
    $info = [
        'titulo' => '',
        'descripcion' => '',
        'precio_original' => null,
        'precio_descuento' => null,
        'porcentaje_descuento' => null,
        'enlace' => '',
        'imagen' => ''
    ];

        // Extraer URLs - priorizar enlaces de Amazon o los que vienen después de "Enlace:", "Link:", etc.
        // Primero buscar enlaces en formato markdown [texto](url)
        $patron_markdown = '/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/i';
        if (preg_match_all($patron_markdown, $texto, $matches_markdown)) {
            foreach ($matches_markdown[2] as $url_markdown) {
                // Limpiar la URL de markdown
                $url_markdown = trim($url_markdown);
                if (esEnlaceAmazon($url_markdown)) {
                    $info['enlace'] = $url_markdown;
                    break;
                }
            }
        }
        
        // Si no se encontró en markdown, buscar URLs directas
        if (empty($info['enlace'])) {
    $patron_url = '/(https?:\/\/[^\s<>"\'\)]+)/i';
    if (preg_match_all($patron_url, $texto, $matches)) {
                $urls_encontradas = $matches[1];
                
                // Buscar primero enlaces de Amazon
                foreach ($urls_encontradas as $url) {
                    // Limpiar la URL (puede tener caracteres extra al final)
                    $url = trim($url);
                    // Eliminar caracteres no válidos al final de la URL
                    $url = preg_replace('/[^\w\/\?\=\&\.\-\:]+$/', '', $url);
                    
                    if (esEnlaceAmazon($url)) {
                        $info['enlace'] = $url;
                        break;
                    }
                }
                
                // Si no hay enlace de Amazon, buscar después de palabras clave
                if (empty($info['enlace'])) {
                    $patron_enlace = '/(?:enlace|link|url|comprar|oferta)[:：]\s*(https?:\/\/[^\s<>"\'\)]+)/i';
                    if (preg_match($patron_enlace, $texto, $match_enlace)) {
                        $info['enlace'] = trim($match_enlace[1]);
                    } else {
                        // Si no hay palabra clave, usar la última URL (generalmente es el enlace del producto)
                        $info['enlace'] = trim(end($urls_encontradas));
                    }
                }
            }
    }

    // Extraer precios (formato: €XX.XX o XX,XX€ o XX.XX€)
    // Buscar patrones como "79.99 €", "79,99 €", "€79.99", "~~79.99 €~~", etc.
    $patron_precio = '/(?:precio\s*(?:original|oferta)[:：]?\s*)?(?:~~)?(\d+[.,]\d+)\s*€|€\s*(\d+[.,]\d+)/i';
    if (preg_match_all($patron_precio, $texto, $matches_precio)) {
        $precios = [];
        foreach ($matches_precio[0] as $precio_str) {
            // Limpiar el precio: eliminar todo excepto números, comas y puntos
            $precio_limpio = preg_replace('/[^\d,.]/', '', $precio_str);
            // Reemplazar coma por punto si es necesario
            $precio_limpio = str_replace(',', '.', $precio_limpio);
            $precio_float = floatval($precio_limpio);
            if ($precio_float > 0 && $precio_float < 100000) { // Validar rango razonable
                $precios[] = $precio_float;
            }
        }
        // Eliminar duplicados y ordenar
        $precios = array_unique($precios);
        rsort($precios);
        
        if (count($precios) >= 2) {
            $info['precio_original'] = max($precios);
            $info['precio_descuento'] = min($precios);
            if ($info['precio_original'] > 0) {
                $info['porcentaje_descuento'] = round((($info['precio_original'] - $info['precio_descuento']) / $info['precio_original']) * 100);
            }
        } elseif (count($precios) == 1) {
            $info['precio_descuento'] = $precios[0];
        }
    }

    // Extraer porcentaje de descuento
    $patron_descuento = '/(\d+)%\s*(?:off|descuento|dto|rebaja)/i';
    if (preg_match($patron_descuento, $texto, $matches_descuento)) {
        $info['porcentaje_descuento'] = intval($matches_descuento[1]);
    }

    // Extraer título (primera línea, limpiando URLs truncadas y caracteres sobrantes)
    $lineas = explode("\n", $texto);
    $titulo_raw = trim($lineas[0] ?? '');
    
    // Limpiar título: eliminar URLs truncadas y caracteres sobrantes
    $titulo_raw = preg_replace('/\(https?:\/\/[^\)]*\.\.\.[^\)]*\)/i', '', $titulo_raw);
    $titulo_raw = preg_replace('/https?:\/\/[^\s]*\.\.\.[^\s]*/i', '', $titulo_raw);
    $titulo_raw = preg_replace('/\s*\([^\)]*\.\.\.[^\)]*\)/', '', $titulo_raw);
    
    // Cortar el título en palabras clave como "Precio", "Enlace", "Link", etc.
    // Buscar patrones como "Precio:", "Enlace:", "(Precio", etc.
    $patron_corte = '/(?:^|\s|\(|\*)(precio|enlace|link|url|comprar|oferta|descuento)[:：\s]/i';
    if (preg_match($patron_corte, $titulo_raw, $matches, PREG_OFFSET_CAPTURE)) {
        $posicion = $matches[0][1];
        // Si hay un paréntesis antes, incluirlo en el corte
        if ($posicion > 0 && $titulo_raw[$posicion - 1] === '(') {
            $posicion--;
        }
        $titulo_raw = substr($titulo_raw, 0, $posicion);
    }
    
    // También cortar si hay un punto seguido de espacio y luego una palabra clave
    $patron_corte_punto = '/\.\s+(precio|enlace|link|url|comprar|oferta|descuento)/i';
    if (preg_match($patron_corte_punto, $titulo_raw, $matches, PREG_OFFSET_CAPTURE)) {
        $titulo_raw = substr($titulo_raw, 0, $matches[0][1]);
    }
    
    $titulo_raw = trim($titulo_raw);
    
    // Si el título está vacío o es muy corto, intentar con más líneas
    if (strlen($titulo_raw) < 10 && count($lineas) > 1) {
        $titulo_raw = trim($lineas[0] . ' ' . $lineas[1]);
        $titulo_raw = preg_replace('/\(https?:\/\/[^\)]*\.\.\.[^\)]*\)/i', '', $titulo_raw);
        $titulo_raw = preg_replace('/https?:\/\/[^\s]*\.\.\.[^\s]*/i', '', $titulo_raw);
        // Aplicar mismo corte
        if (preg_match($patron_corte, $titulo_raw, $matches, PREG_OFFSET_CAPTURE)) {
            $titulo_raw = substr($titulo_raw, 0, $matches[0][1]);
        }
        $titulo_raw = trim($titulo_raw);
    }
    
    // Limpiar espacios múltiples finales y paréntesis sobrantes
    $titulo_raw = preg_replace('/\s+/', ' ', $titulo_raw);
    $titulo_raw = preg_replace('/\s*\($/', '', $titulo_raw); // Eliminar paréntesis al final
    $titulo_raw = trim($titulo_raw);
    
    // Limpiar el título de hashtags y referencias no deseadas
    $titulo_raw = preg_replace('/#Publicidad\b/i', '', $titulo_raw);
    $titulo_raw = preg_replace('/#\s*Publicidad\b/i', '', $titulo_raw);
    $titulo_raw = preg_replace('/\b#Publicidad\b/i', '', $titulo_raw);
    $titulo_raw = preg_replace('/\s*#\s*[Pp]ublicidad\b/i', '', $titulo_raw);
    $titulo_raw = preg_replace('/#\w*[Pp]ublicidad\w*/i', '', $titulo_raw);
    $titulo_raw = preg_replace('/@\w+\s*/i', '', $titulo_raw); // Eliminar menciones @canal
    $titulo_raw = preg_replace('/#\w*canal\w*/i', '', $titulo_raw); // Eliminar hashtags de canales
    $titulo_raw = preg_replace('/\s+/', ' ', $titulo_raw); // Limpiar espacios múltiples
    $titulo_raw = trim($titulo_raw);
    
    $info['titulo'] = $titulo_raw;
    if (strlen($info['titulo']) > 100) {
        $info['titulo'] = substr($info['titulo'], 0, 97) . '...';
    }

    // Descripción (resto del texto)
    if (count($lineas) > 1) {
        $info['descripcion'] = trim(implode("\n", array_slice($lineas, 1)));
    } else {
        $info['descripcion'] = $texto;
    }
    
    // Limpiar la descripción de hashtags y referencias no deseadas
    $info['descripcion'] = preg_replace('/#Publicidad\b/i', '', $info['descripcion']);
    $info['descripcion'] = preg_replace('/#\s*Publicidad\b/i', '', $info['descripcion']);
    $info['descripcion'] = preg_replace('/\b#Publicidad\b/i', '', $info['descripcion']);
    $info['descripcion'] = preg_replace('/\s*#\s*[Pp]ublicidad\b/i', '', $info['descripcion']);
    $info['descripcion'] = preg_replace('/#\w*[Pp]ublicidad\w*/i', '', $info['descripcion']);
    $info['descripcion'] = preg_replace('/@\w+\s*/i', '', $info['descripcion']); // Eliminar menciones @canal
    $info['descripcion'] = preg_replace('/#\w*canal\w*/i', '', $info['descripcion']); // Eliminar hashtags de canales
    
    // Eliminar líneas que empiezan con "Enlace:", "Link:", "URL:", etc.
    $lineas_desc = explode("\n", $info['descripcion']);
    $lineas_limpias = [];
    foreach ($lineas_desc as $linea) {
        $linea_trim = trim($linea);
        // Saltar líneas que empiezan con palabras clave de enlaces
        if (preg_match('/^(enlace|link|url|comprar|oferta)[:：]\s*/i', $linea_trim)) {
            continue;
        }
        // Eliminar enlaces markdown truncados como "[https://amzn.to/......](url)"
        $linea_trim = preg_replace('/\[https?:\/\/[^\]]*\.\.\.[^\]]*\]\([^\)]+\)/i', '', $linea_trim);
        $linea_trim = preg_replace('/\[https?:\/\/[^\]]*\]\([^\)]+\)/i', '', $linea_trim); // Eliminar cualquier enlace markdown
        // Eliminar referencias a enlaces después de "Enlace:" o "Link:"
        $linea_trim = preg_replace('/.*(?:enlace|link|url)[:：].*/i', '', $linea_trim);
        if (!empty(trim($linea_trim))) {
            $lineas_limpias[] = $linea_trim;
        }
    }
    $info['descripcion'] = implode("\n", $lineas_limpias);
    
    // Eliminar enlaces markdown que puedan quedar en el texto
    $info['descripcion'] = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $info['descripcion']); // Convertir [texto](url) a texto
    $info['descripcion'] = preg_replace('/https?:\/\/[^\s<>"\'\)]+/i', '', $info['descripcion']); // Eliminar URLs directas
    $info['descripcion'] = preg_replace('/\s+/', ' ', $info['descripcion']); // Limpiar espacios múltiples
    $info['descripcion'] = trim($info['descripcion']);

    return $info;
}

