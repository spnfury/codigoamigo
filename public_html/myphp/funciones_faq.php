<?php

// Incluir funciones de conexión a MongoDB
if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}

/******************************************************
 *  FUNCIONES PARA PREGUNTAS FRECUENTES DE MARCAS
 * ***************************************************/

/**
 * Obtiene la colección de FAQs de marcas
 */
function getCollectionFAQs() {
    $db = createConnection();
    $collection_faqs = $db->selectCollection('marcas_faqs');
    return $collection_faqs;
}

/**
 * Obtiene todas las FAQs de una marca específica
 */
function getFAQsByMarca($marca_clave, $activas_solo = true) {
    $collection_faqs = getCollectionFAQs();
    
    $filtro = ['marca_clave' => $marca_clave];
    if ($activas_solo) {
        $filtro['activa'] = true;
    }
    
    $faqs = $collection_faqs->find(
        $filtro,
        [
            'sort' => ['orden' => 1, 'fecha_creacion' => -1]
        ]
    );
    
    return iterator_to_array($faqs);
}

/**
 * Obtiene una FAQ específica por ID
 */
function getFAQByID($faq_id) {
    $collection_faqs = getCollectionFAQs();
    return $collection_faqs->findOne(['_id' => new MongoDB\BSON\ObjectId($faq_id)]);
}

/**
 * Crea una nueva FAQ para una marca
 */
function crearFAQ($marca_clave, $titulo, $respuesta, $orden = 0) {
    $collection_faqs = getCollectionFAQs();
    
    $faq_data = [
        'marca_clave' => $marca_clave,
        'titulo' => $titulo,
        'respuesta' => $respuesta,
        'orden' => $orden,
        'activa' => true,
        'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
    ];
    
    $result = $collection_faqs->insertOne($faq_data);
    return $result->getInsertedId();
}

/**
 * Actualiza una FAQ existente
 */
function actualizarFAQ($faq_id, $titulo, $respuesta, $orden = null, $activa = true) {
    $collection_faqs = getCollectionFAQs();
    
    $update_data = [
        'titulo' => $titulo,
        'respuesta' => $respuesta,
        'activa' => $activa,
        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
    ];
    
    if ($orden !== null) {
        $update_data['orden'] = $orden;
    }
    
    $result = $collection_faqs->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($faq_id)],
        ['$set' => $update_data]
    );
    
    return $result->getModifiedCount() > 0;
}

/**
 * Elimina una FAQ
 */
function eliminarFAQ($faq_id) {
    $collection_faqs = getCollectionFAQs();
    
    $result = $collection_faqs->deleteOne(['_id' => new MongoDB\BSON\ObjectId($faq_id)]);
    return $result->getDeletedCount() > 0;
}

/**
 * Cambia el estado activo/inactivo de una FAQ
 */
function toggleFAQActiva($faq_id) {
    $collection_faqs = getCollectionFAQs();
    
    $faq = getFAQByID($faq_id);
    if (!$faq) return false;
    
    $nuevo_estado = !$faq['activa'];
    
    $result = $collection_faqs->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($faq_id)],
        [
            '$set' => [
                'activa' => $nuevo_estado,
                'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
            ]
        ]
    );
    
    return $result->getModifiedCount() > 0;
}

/**
 * Reordena las FAQs de una marca
 */
function reordenarFAQs($marca_clave, $faq_ordenes) {
    $collection_faqs = getCollectionFAQs();
    
    foreach ($faq_ordenes as $faq_id => $nuevo_orden) {
        $collection_faqs->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($faq_id)],
            [
                '$set' => [
                    'orden' => $nuevo_orden,
                    'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                ]
            ]
        );
    }
    
    return true;
}

/**
 * Importa múltiples FAQs desde texto (formato copiar-pegar)
 */
function importarFAQsBulk($marca_clave, $texto_faqs) {
    $collection_faqs = getCollectionFAQs();
    $faqs_creadas = [];
    
    // Dividir por líneas y procesar
    $lineas = explode("\n", $texto_faqs);
    $faq_actual = null;
    $orden = 0;
    
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        
        if (empty($linea)) continue;
        
        // Detectar si es una pregunta (termina en ? o empieza con ¿)
        if (preg_match('/^[¿]?.*[?]$/', $linea) || preg_match('/^[Qq]ué|^[Cc]ómo|^[Dd]ónde|^[Cc]uándo|^[Pp]or qué|^[Ee]s |^[Hh]ay /', $linea)) {
            // Si había una FAQ anterior sin respuesta, guardarla
            if ($faq_actual && !empty($faq_actual['titulo'])) {
                $faq_actual['orden'] = $orden++;
                $faq_actual['marca_clave'] = $marca_clave;
                $faq_actual['activa'] = true;
                $faq_actual['fecha_creacion'] = new MongoDB\BSON\UTCDateTime();
                $faq_actual['fecha_actualizacion'] = new MongoDB\BSON\UTCDateTime();
                
                $result = $collection_faqs->insertOne($faq_actual);
                $faqs_creadas[] = $result->getInsertedId();
            }
            
            // Iniciar nueva FAQ
            $faq_actual = [
                'titulo' => $linea,
                'respuesta' => ''
            ];
        } else {
            // Es parte de la respuesta
            if ($faq_actual) {
                if (!empty($faq_actual['respuesta'])) {
                    $faq_actual['respuesta'] .= "\n";
                }
                $faq_actual['respuesta'] .= $linea;
            }
        }
    }
    
    // Guardar la última FAQ si existe
    if ($faq_actual && !empty($faq_actual['titulo'])) {
        $faq_actual['orden'] = $orden++;
        $faq_actual['marca_clave'] = $marca_clave;
        $faq_actual['activa'] = true;
        $faq_actual['fecha_creacion'] = new MongoDB\BSON\UTCDateTime();
        $faq_actual['fecha_actualizacion'] = new MongoDB\BSON\UTCDateTime();
        
        $result = $collection_faqs->insertOne($faq_actual);
        $faqs_creadas[] = $result->getInsertedId();
    }
    
    return $faqs_creadas;
}

/**
 * Genera FAQs usando IA (Perplexity o Groq)
 */
function generarFAQsConIA($marca_clave, $marca_nombre, $api_provider = 'perplexity') {
    $faqs_generadas = [];
    
    try {
        // Fallback automático si no hay API key de Perplexity
        if ($api_provider === 'perplexity' && (!defined('PERPLEXITY_API_KEY') || strpos(PERPLEXITY_API_KEY, 'your-api-key') !== false)) {
            error_log("Aviso: PERPLEXITY_API_KEY no configurada. Usando Groq como fallback.");
            $api_provider = 'groq';
        }

        if ($api_provider === 'perplexity') {
            $faqs_generadas = generarFAQsPerplexity($marca_clave, $marca_nombre);
        } elseif ($api_provider === 'groq') {
            $faqs_generadas = generarFAQsGroq($marca_clave, $marca_nombre);
        }
        
        if (empty($faqs_generadas)) {
             error_log("Error: No se generaron FAQs para la marca $marca_nombre con el proveedor $api_provider");
             return false;
        }

        // Guardar las FAQs generadas en la base de datos
        $collection_faqs = getCollectionFAQs();
        $faqs_guardadas = [];
        
        foreach ($faqs_generadas as $index => $faq) {
            $faq_data = [
                'marca_clave' => $marca_clave,
                'titulo' => $faq['pregunta'],
                'respuesta' => $faq['respuesta'],
                'orden' => $index,
                'activa' => true,
                'generada_ia' => true,
                'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
                'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $result = $collection_faqs->insertOne($faq_data);
            $faqs_guardadas[] = $result->getInsertedId();
        }
        
        return $faqs_guardadas;
        
    } catch (Exception $e) {
        error_log("Error crítico generando FAQs con IA: " . $e->getMessage());
        return false;
    }
}

/**
 * Genera FAQs usando Perplexity API
 */
function generarFAQsPerplexity($marca_clave, $marca_nombre) {
    // Incluir configuración de IA
    if (file_exists(__DIR__ . '/../config/ai_config.php')) {
        include_once __DIR__ . '/../config/ai_config.php';
    }
    
    $api_key = defined('PERPLEXITY_API_KEY') ? PERPLEXITY_API_KEY : '';
    
    if (empty($api_key) || strpos($api_key, 'your-api-key') !== false) {
        error_log("Error: PERPLEXITY_API_KEY no configurada correctamente.");
        return [];
    }
    
    $prompt_template = defined('FAQ_PROMPT_TEMPLATE') ? FAQ_PROMPT_TEMPLATE : 'Genera 8-10 preguntas frecuentes sobre {MARCA_NOMBRE}, especialmente relacionadas con códigos de descuento, promociones, registro y uso de la plataforma. Responde en español y formato JSON con estructura: [{"pregunta": "...", "respuesta": "..."}]';
    $prompt = str_replace('{MARCA_NOMBRE}', $marca_nombre, $prompt_template);
    
    $data = [
        'model' => defined('PERPLEXITY_MODEL') ? PERPLEXITY_MODEL : 'llama-3.1-sonar-small-128k-online',
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
    curl_setopt($ch, CURLOPT_URL, defined('PERPLEXITY_API_URL') ? PERPLEXITY_API_URL : 'https://api.perplexity.ai/chat/completions');
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
        error_log("Error cURL Perplexity: " . $error);
        return [];
    }
    
    if ($http_code !== 200) {
        error_log("Error HTTP Perplexity: " . $http_code . " - " . $response);
        return [];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        $content = $result['choices'][0]['message']['content'];
        $faqs = json_decode($content, true);
        
        if (is_array($faqs)) {
            return $faqs;
        }
    }
    
    return [];
}

/**
 * Genera FAQs usando Groq API
 */
function generarFAQsGroq($marca_clave, $marca_nombre) {
    // Incluir configuración de IA
    if (file_exists(__DIR__ . '/../config/ai_config.php')) {
        include_once __DIR__ . '/../config/ai_config.php';
    }
    
    $api_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    
    if (empty($api_key) || strpos($api_key, 'your-api-key') !== false) {
        error_log("Error: GROQ_API_KEY no configurada correctamente.");
        return [];
    }
    
    $prompt_template = defined('FAQ_PROMPT_TEMPLATE') ? FAQ_PROMPT_TEMPLATE : 'Genera 8-10 preguntas frecuentes sobre {MARCA_NOMBRE}, especialmente relacionadas con códigos de descuento, promociones, registro y uso de la plataforma. Responde en español y formato JSON con estructura: [{"pregunta": "...", "respuesta": "..."}]';
    $prompt = str_replace('{MARCA_NOMBRE}', $marca_nombre, $prompt_template);
    
    $data = [
        'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama3-8b-8192',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 2000,
        'temperature' => defined('AI_TEMPERATURE') ? AI_TEMPERATURE : 0.7
    ];
    
    // Groq con rotación de claves + fallback de modelo (ver groq_request en ai_config)
    $__g = groq_request($data);
    $response  = $__g['body'];
    $http_code = $__g['http'];
    $error = '';
    
    if ($error) {
        error_log("Error cURL Groq: " . $error);
        return [];
    }
    
    if ($http_code !== 200) {
        error_log("Error HTTP Groq: " . $http_code . " - " . $response);
        return [];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        $content = $result['choices'][0]['message']['content'];
        $faqs = json_decode($content, true);
        
        if (is_array($faqs)) {
            return $faqs;
        }
    }
    
    return [];
}

/**
 * Obtiene estadísticas de FAQs por marca
 */
function getEstadisticasFAQs($marca_clave) {
    $collection_faqs = getCollectionFAQs();
    
    $total = $collection_faqs->countDocuments(['marca_clave' => $marca_clave]);
    $activas = $collection_faqs->countDocuments(['marca_clave' => $marca_clave, 'activa' => true]);
    $generadas_ia = $collection_faqs->countDocuments(['marca_clave' => $marca_clave, 'generada_ia' => true]);
    
    return [
        'total' => $total,
        'activas' => $activas,
        'inactivas' => $total - $activas,
        'generadas_ia' => $generadas_ia,
        'manuales' => $total - $generadas_ia
    ];
}

?>
