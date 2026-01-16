<?php
// Funciones para gestión de contenido SEO de categorías
require_once __DIR__ . '/funciones_chollos.php';
require_once __DIR__ . '/funciones_chollos_groq.php';

/**
 * Obtiene la colección de SEO de categorías
 */
function getCollectionCategoriasSeo() {
    $db = createConnection();
    if (!$db) return null;
    return $db->selectCollection('chollos_categorias_seo');
}

/**
 * Obtiene o genera el contenido SEO para una categoría
 * @param string $ruta_categoria Ruta completa (ej: videojuegos/juegos-de-rol/ps5)
 * @param array $nombres_categoria Array de nombres legibles (ej: ['Videojuegos', 'Juegos De Rol', 'Ps5'])
 */
function obtenerContenidoSeoCategoria($ruta_categoria, $nombres_categoria) {
    if (empty($ruta_categoria)) return null;
    
    // Normalizar slug
    $slug = trim($ruta_categoria, '/');
    $nombre_final = end($nombres_categoria);
    
    // Intentar obtener de la BD
    $collection = getCollectionCategoriasSeo();
    if ($collection) {
        $doc = $collection->findOne(['slug' => $slug]);
        
        // Si existe y tiene menos de 30 días, devolver
        if ($doc) {
            $fecha_actualizacion = $doc['fecha_actualizacion']->toDateTime();
            $diferencia = (new DateTime())->diff($fecha_actualizacion)->days;
            
            if ($diferencia < 30) {
                return [
                    'h1' => $doc['h1'],
                    'html' => $doc['contenido'],
                    'meta_description' => $doc['meta_description'] ?? ''
                ];
            }
        }
    }
    
    // Si no existe o es antiguo, generar con Groq
    return generarSeoCategoriaConGroq($slug, $nombres_categoria);
}

/**
 * Genera contenido SEO usando Groq y lo guarda
 */
function generarSeoCategoriaConGroq($slug, $nombres_categoria) {
    global $groq_model; // Asegurarse de tener acceso a configuración
    
    $nombre_principal = end($nombres_categoria);
    $ruta_texto = implode(' > ', $nombres_categoria);
    
    $prompt = "Actúa como un experto en SEO y Copywriting para un sitio de chollos y ofertas.
    Necesito generar contenido original y semánticamente rico para la categoría de chollos: '{$ruta_texto}'.
    
    Objetivo: Posicionar esta categoría en buscadores usando sinónimos, LSI keywords y variaciones del término principal ('{$nombre_principal}').
    
    Instrucciones:
    1. Genera un H1 atractivo que incluya la palabra clave principal pero sea natural (max 70 caracteres).
    2. Genera un texto introductorio BREVE (max 40 palabras) de TEXTO PLANO (sin etiquetas HTML) que explique qué encontrará el usuario.
    3. Usa sinónimos. Ejemplo: Si es 'PS5', usa 'PlayStation 5', 'Consola de Sony', 'Play 5'.
    4. Genera una Meta Description concisa y persuasiva (max 155 caracteres).
    
    Formato de respuesta estrictamente JSON:
    {
        \"h1\": \"Título H1 optimizado\",
        \"h2_principal\": \"Subtítulo principal H2\",
        \"texto_intro\": \"Texto introductorio plano (sin HTML)\",
        \"h2_secundario\": \"Subtítulo secundario H2\",
        \"texto_secundario\": \"Texto secundario plano con más variaciones (max 50 palabras)\",
        \"meta_description\": \"Meta descripción para SEO\"
    }
    ";
    
    $api_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    if (empty($api_key)) return null; // No se puede generar sin API key
    
    $data = [
        'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.7,
        'response_format' => ['type' => 'json_object']
    ];
    
    // Llamada cURL (similar a la función existente pero simplificada aquí)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.groq.com/openai/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) return null;
    
    $result = json_decode($response, true);
    $content_json = $result['choices'][0]['message']['content'] ?? null;
    
    if ($content_json) {
        $seo_data = json_decode($content_json, true);
        
        if ($seo_data) {
            // Construir HTML final limpiando etiquetas
            $html_content = '';
            if (!empty($seo_data['h2_principal'])) {
                $html_content .= '<h2>' . htmlspecialchars(strip_tags($seo_data['h2_principal'])) . '</h2>';
            }
            if (!empty($seo_data['texto_intro'])) {
                $html_content .= '<p>' . htmlspecialchars(strip_tags($seo_data['texto_intro'])) . '</p>';
            }
            // Contenido secundario ocultable
            if (!empty($seo_data['h2_secundario']) || !empty($seo_data['texto_secundario'])) {
                $html_content .= '<div class="seo-more-content" style="display:none;">';
                if (!empty($seo_data['h2_secundario'])) {
                    $html_content .= '<h2>' . htmlspecialchars(strip_tags($seo_data['h2_secundario'])) . '</h2>';
                }
                if (!empty($seo_data['texto_secundario'])) {
                    $html_content .= '<p>' . htmlspecialchars(strip_tags($seo_data['texto_secundario'])) . '</p>';
                }
                $html_content .= '</div>';
                $html_content .= '<button onclick="this.previousElementSibling.style.display = this.previousElementSibling.style.display === \'none\' ? \'block\' : \'none\'; this.textContent = this.textContent === \'Leer más\' ? \'Leer menos\' : \'Leer más\';" style="background:none; border:none; color:#E30613; cursor:pointer; font-weight:bold; padding:0; margin-top:10px;">Leer más</button>';
            }
            
            $h1 = strip_tags($seo_data['h1'] ?? "Chollos de {$nombre_principal}");
            $meta_description = $seo_data['meta_description'] ?? "";
            
            // Guardar en BD
            $collection = getCollectionCategoriasSeo();
            if ($collection) {
                // Remove existing if any (upsert logic basically)
                $collection->deleteOne(['slug' => $slug]);
                
                $collection->insertOne([
                    'slug' => $slug,
                    'h1' => $h1,
                    'contenido' => $html_content,
                    'meta_description' => $meta_description,
                    'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
                ]);
            }
            
            return [
                'h1' => $h1,
                'html' => $html_content,
                'meta_description' => $meta_description
            ];
        }
    }
    
    return null;
}
