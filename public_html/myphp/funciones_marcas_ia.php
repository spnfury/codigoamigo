<?php

/**
 * Funciones para generar contenido de marcas usando IA
 * Usa Groq API para generar descripciones, ventajas y buscar imágenes
 */

// Incluir configuración de IA
if (file_exists(__DIR__ . '/../config/ai_config.php')) {
    include_once __DIR__ . '/../config/ai_config.php';
}

/**
 * Genera contenido completo para la marca: descripción, ventajas, secciones SEO y FAQ.
 * Retorna campos listos para rellenar el formulario de admin.
 */
function generarDescripcionMarcaIA($nombre_marca, $categoria = '') {
    if (empty($nombre_marca)) {
        return ['success' => false, 'error' => 'Nombre de marca requerido'];
    }

    $api_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    
    if (empty($api_key) || $api_key === 'gsk-your-api-key-here') {
        return ['success' => false, 'error' => 'API key de Groq no configurada'];
    }

    // Prompt único para obtener JSON estructurado
    $prompt = "Eres un asistente que devuelve SOLO JSON válido sin comentarios. "
        . "Genera contenido para la marca \"{$nombre_marca}\" en la categoría \"{$categoria}\" con esta estructura: "
        . "{"
        . "\"descripcion\":\"<=150 palabras\","
        . "\"descripcion_larga\":\"<=400 palabras\","
        . "\"ventajas\":[\"frase corta 1\",\"frase corta 2\",\"frase corta 3\"],"
        . "\"seo_que_es\":\"2-3 párrafos sobre qué es y cómo funciona\","
        . "\"seo_como_usar\":\"pasos numerados claros\","
        . "\"seo_tips\":\"lista de tips para ahorrar\","
        . "\"seo_faq\":[{\"q\":\"¿...?\",\"a\":\"...\"},{\"q\":\"¿...?\",\"a\":\"...\"}],"
        . "\"video\":\"url opcional de YouTube/Vimeo si aplica o vacío\","
        . "\"imagen\":\"url opcional si aplica o vacío\""
        . "}. "
        . "No añadas texto fuera del JSON.";

    $data = [
        'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
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
        error_log("Error cURL Groq (contenido marca): " . $error);
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }

    if ($http_code !== 200) {
        error_log("Error HTTP Groq (contenido marca): " . $http_code . " - " . $response);
        return ['success' => false, 'error' => 'Error de API: ' . $http_code];
    }

    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        $content = trim($result['choices'][0]['message']['content']);
        // Intentar decodificar el JSON devuelto
        $json = json_decode($content, true);
        if (!$json) {
            // Intentar limpiar comillas
            $content = trim($content, "\"' \n\r\t");
            $json = json_decode($content, true);
        }

        if (is_array($json)) {
            $ventajas = $json['ventajas'] ?? [];
            // FAQ en formato líneas pregunta|respuesta
            $faq_lines = [];
            if (!empty($json['seo_faq']) && is_array($json['seo_faq'])) {
                foreach ($json['seo_faq'] as $item) {
                    if (!empty($item['q']) && !empty($item['a'])) {
                        $faq_lines[] = $item['q'] . '|' . $item['a'];
                    }
                }
            }

            return [
                'success' => true,
                'descripcion' => $json['descripcion'] ?? '',
                'descripcion_larga' => $json['descripcion_larga'] ?? '',
                'ventaja_1' => $ventajas[0] ?? '',
                'ventaja_2' => $ventajas[1] ?? '',
                'ventaja_3' => $ventajas[2] ?? '',
                'seo_que_es' => $json['seo_que_es'] ?? '',
                'seo_como_usar' => $json['seo_como_usar'] ?? '',
                'seo_tips' => $json['seo_tips'] ?? '',
                'seo_faq' => implode(\"\\n\", $faq_lines),
                'video' => $json['video'] ?? '',
                'imagen' => $json['imagen'] ?? ''
            ];
        }
    }

    return ['success' => false, 'error' => 'No se pudo obtener respuesta de Groq'];
}

/**
 * Genera una descripción larga para una marca usando IA
 */
function generarDescripcionLargaMarcaIA($nombre_marca, $categoria = '') {
    if (empty($nombre_marca)) {
        return ['success' => false, 'error' => 'Nombre de marca requerido'];
    }

    $api_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    
    if (empty($api_key) || $api_key === 'gsk-your-api-key-here') {
        return ['success' => false, 'error' => 'API key de Groq no configurada'];
    }

    $prompt_template = defined('MARCA_PROMPT_DESCRIPCION_LARGA') 
        ? MARCA_PROMPT_DESCRIPCION_LARGA 
        : 'Genera una descripción larga y detallada (mínimo 500 palabras) para la marca {NOMBRE_MARCA} en la categoría {CATEGORIA}. Incluye información sobre la empresa, servicios, beneficios y por qué usar códigos de descuento. Solo devuelve la descripción, sin explicaciones:';
    
    $prompt = str_replace(['{NOMBRE_MARCA}', '{CATEGORIA}'], [$nombre_marca, $categoria ?: 'general'], $prompt_template);

    $data = [
        'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 4000, // Más tokens para descripción larga
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
    curl_setopt($ch, CURLOPT_TIMEOUT, defined('AI_TIMEOUT') ? AI_TIMEOUT : 60); // Más tiempo para descripción larga
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Error cURL Groq (descripción larga marca): " . $error);
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }

    if ($http_code !== 200) {
        error_log("Error HTTP Groq (descripción larga marca): " . $http_code . " - " . $response);
        return ['success' => false, 'error' => 'Error de API: ' . $http_code];
    }

    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        $descripcion = trim($result['choices'][0]['message']['content']);
        $descripcion = preg_replace('/^["\']|["\']$/', '', $descripcion);
        $descripcion = trim($descripcion);

        return [
            'success' => true,
            'descripcion' => $descripcion
        ];
    }

    return ['success' => false, 'error' => 'No se pudo obtener respuesta de Groq'];
}

/**
 * Genera una ventaja principal para una marca usando IA
 */
function generarVentajaMarcaIA($nombre_marca, $categoria = '', $numero_ventaja = 1) {
    if (empty($nombre_marca)) {
        return ['success' => false, 'error' => 'Nombre de marca requerido'];
    }

    $api_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    
    if (empty($api_key) || $api_key === 'gsk-your-api-key-here') {
        return ['success' => false, 'error' => 'API key de Groq no configurada'];
    }

    $prompt_template = defined('MARCA_PROMPT_VENTAJA') 
        ? MARCA_PROMPT_VENTAJA 
        : 'Genera una ventaja principal atractiva y concisa (máximo 20 palabras) para la marca {NOMBRE_MARCA} en la categoría {CATEGORIA}. Debe ser un beneficio específico y convincente. Solo devuelve la ventaja, sin explicaciones:';
    
    $prompt = str_replace(['{NOMBRE_MARCA}', '{CATEGORIA}'], [$nombre_marca, $categoria ?: 'general'], $prompt_template);

    $data = [
        'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 200,
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
        error_log("Error cURL Groq (ventaja marca): " . $error);
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }

    if ($http_code !== 200) {
        error_log("Error HTTP Groq (ventaja marca): " . $http_code . " - " . $response);
        return ['success' => false, 'error' => 'Error de API: ' . $http_code];
    }

    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        $ventaja = trim($result['choices'][0]['message']['content']);
        $ventaja = preg_replace('/^["\']|["\']$/', '', $ventaja);
        $ventaja = trim($ventaja);

        return [
            'success' => true,
            'ventaja' => $ventaja
        ];
    }

    return ['success' => false, 'error' => 'No se pudo obtener respuesta de Groq'];
}

/**
 * Busca imágenes de una marca usando Google Custom Search API
 */
function buscarImagenesMarca($nombre_marca, $limite = 10) {
    if (empty($nombre_marca)) {
        return ['success' => false, 'error' => 'Nombre de marca requerido'];
    }

    // Configuración de Google Custom Search API
    $api_key = 'AIzaSyBO8kzIr4NtCVBxLxQSxGkq8Whw4kHgAqI';
    $cx = '011289846254342421780:ygm3rzpmf2a';
    
    if (empty($api_key) || empty($cx)) {
        return ['success' => false, 'error' => 'API de Google Custom Search no configurada'];
    }

    $busqueda = $nombre_marca . ' logo';
    $url = 'https://www.googleapis.com/customsearch/v1?' . http_build_query([
        'q' => $busqueda,
        'num' => $limite,
        'searchType' => 'image',
        'key' => $api_key,
        'cx' => $cx,
        'safe' => 'active',
        'imgSize' => 'large',
        'imgType' => 'logo'
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Error cURL Google Images: " . $error);
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }

    if ($http_code !== 200) {
        error_log("Error HTTP Google Images: " . $http_code . " - " . $response);
        return ['success' => false, 'error' => 'Error de API: ' . $http_code];
    }

    $result = json_decode($response, true);

    if (isset($result['items']) && is_array($result['items'])) {
        $imagenes = [];
        foreach ($result['items'] as $item) {
            $imagenes[] = [
                'url' => $item['link'] ?? '',
                'thumbnail' => $item['image']['thumbnailLink'] ?? $item['link'] ?? '',
                'title' => $item['title'] ?? ''
            ];
        }

        return [
            'success' => true,
            'imagenes' => $imagenes
        ];
    }

    return ['success' => false, 'error' => 'No se encontraron imágenes'];
}

?>

