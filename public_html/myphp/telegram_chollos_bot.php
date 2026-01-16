<?php

/**
 * Bot de Telegram para procesar y publicar chollos
 */

// Incluir funciones necesarias
if (!function_exists('createConnection')) {
    include_once __DIR__ . '/funciones.php';
}
include_once __DIR__ . '/funciones_chollos.php';
include_once __DIR__ . '/funciones_chollos_amazon.php';
include_once __DIR__ . '/funciones_chollos_groq.php';

// Incluir configuración
if (file_exists(__DIR__ . '/../config/ai_config.php')) {
    include_once __DIR__ . '/../config/ai_config.php';
}

/**
 * Procesa un mensaje recibido de Telegram
 */
function procesarMensajeTelegram($body) {
    $data = json_decode($body, true);
    
    // LOG TEMPORAL PARA CAPTURAR CHAT ID
    file_put_contents(__DIR__ . '/../telegram_debug.log', "REQUEST RECEIVED: " . $body . PHP_EOL, FILE_APPEND);
    
    if (!$data || !isset($data['message'])) {
        return ['success' => false, 'error' => 'Formato de mensaje inválido'];
    }
    
    $message = $data['message'];
    $chat_id = $message['chat']['id'] ?? null;
    $text = $message['text'] ?? '';
    $caption = $message['caption'] ?? '';
    $photo = $message['photo'] ?? [];
    
    // Verificar que viene del canal de entrada configurado
    $chat_id_entrada = defined('TELEGRAM_CHOLLOS_CHAT_ID_ENTRADA') ? TELEGRAM_CHOLLOS_CHAT_ID_ENTRADA : '';
    
    if (!empty($chat_id_entrada) && $chat_id != $chat_id_entrada) {
        return ['success' => false, 'error' => 'Canal no autorizado'];
    }
    
    // Combinar texto y caption
    $texto_completo = trim($text . "\n" . $caption);
    
    if (empty($texto_completo)) {
        return ['success' => false, 'error' => 'Mensaje vacío'];
    }
    
    // Extraer información del chollo
    $info = extraerInfoChollo($texto_completo);
    
    if (empty($info['titulo']) && empty($info['enlace'])) {
        return ['success' => false, 'error' => 'No se pudo extraer información del chollo'];
    }
    
    // Obtener imagen si existe
    if (!empty($photo)) {
        $photo_file_id = end($photo)['file_id']; // Obtener la de mayor resolución
        $info['imagen'] = obtenerUrlFotoTelegram($photo_file_id);
    }

    // VALIDACIÓN ESTRICTA: Solo permitir chollos de Amazon
    if (empty($info['enlace']) || !esEnlaceAmazon($info['enlace'])) {
        return ['success' => false, 'error' => 'Solo se permiten chollos de Amazon'];
    }
    
    // Convertir enlace de Amazon si es necesario
    if (!empty($info['enlace']) && esEnlaceAmazon($info['enlace'])) {
        $info['enlace_original'] = $info['enlace'];
        $info['enlace'] = convertirEnlaceAmazon($info['enlace']);
    }
    
    // Reescribir texto con Groq
    if (!empty($info['titulo'])) {
        $resultado_titulo = reescribirTextoGroq($info['titulo'], 'titulo');
        if ($resultado_titulo['success']) {
            $info['titulo'] = $resultado_titulo['texto_reescrito'];
            $info['texto_reescrito'] = true;
        }
    }
    
    if (!empty($info['descripcion'])) {
        $resultado_desc = reescribirTextoGroq($info['descripcion'], 'descripcion');
        if ($resultado_desc['success']) {
            $info['descripcion'] = $resultado_desc['texto_reescrito'];
        }
    }
    
    // Añadir campos adicionales
    $info['categoria'] = detectarCategoria($texto_completo);
    $info['fuente'] = 'telegram';
    $info['estado'] = 0; // Por defecto inactivo para revisar
    
    // Crear chollo
    $resultado = crearChollo($info);
    
    if ($resultado['success']) {
        // Publicar en canal de salida si está configurado
        $chat_id_salida = defined('TELEGRAM_CHOLLOS_CHAT_ID_SALIDA') ? TELEGRAM_CHOLLOS_CHAT_ID_SALIDA : '';
        if (!empty($chat_id_salida)) {
            publicarCholloEnTelegram($resultado['id'], $chat_id_salida);
        }
        
        return ['success' => true, 'id' => $resultado['id']];
    }
    
    return $resultado;
}

/**
 * Publica un chollo en un canal de Telegram
 */
function publicarCholloEnTelegram($chollo_id, $chat_id = null) {
    if (empty($chat_id)) {
        $chat_id = defined('TELEGRAM_CHOLLOS_CHAT_ID_SALIDA') ? TELEGRAM_CHOLLOS_CHAT_ID_SALIDA : '';
    }
    
    if (empty($chat_id)) {
        return ['success' => false, 'error' => 'Chat ID no configurado'];
    }
    
    $bot_token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '1208948207:AAF0O45V1zcsp7wjRgbwOrLQ7tNRUHlfnME';
    
    // Obtener chollo
    $chollo = obtenerCholloPorId($chollo_id);
    if (!$chollo) {
        return ['success' => false, 'error' => 'Chollo no encontrado'];
    }
    
    // Verificar si el chollo ya fue publicado en Telegram
    if (!empty($chollo['publicado_telegram']) && $chollo['publicado_telegram'] === true) {
        return ['success' => false, 'error' => 'Este chollo ya fue publicado en Telegram anteriormente', 'ya_publicado' => true];
    }
    
    // Construir mensaje con formato limpio y profesional
    $mensaje = "🔥 *" . $chollo['titulo'] . "*\n\n";
    
    // Precios en formato compacto
    if ($chollo['precio_original'] || $chollo['precio_descuento']) {
        if ($chollo['precio_original'] && $chollo['precio_original'] > $chollo['precio_descuento']) {
            $mensaje .= "Precio original: ~~" . number_format($chollo['precio_original'], 2, '.', '') . " €~~\n";
        }
        if ($chollo['precio_descuento']) {
            $mensaje .= "Precio oferta: *" . number_format($chollo['precio_descuento'], 2, '.', '') . " €* 🔥";
            if ($chollo['porcentaje_descuento']) {
                $mensaje .= " *(-" . $chollo['porcentaje_descuento'] . "%)*";
            }
            $mensaje .= "\n\n";
        }
    }
    
    // Enlaces: directo a Amazon y a la ficha de CodigoAmigo
    $url_amazon = 'https://www.codigoamigo.com/chollo/' . $chollo['id']; // Redirige a Amazon
    
    // Obtener la primera categoría si es array
    $categoria = 'general';
    if (!empty($chollo['categoria'])) {
        if (is_array($chollo['categoria'])) {
            $categoria = $chollo['categoria'][0];
        } else {
            $categoria = $chollo['categoria'];
        }
    }
    // Generar slug SEO-friendly para la categoría
    if (!function_exists('categoriaToSlug')) {
        include_once __DIR__ . '/funciones_chollos_helpers.php';
    }
    $categoria_slug = categoriaToSlug($categoria);
    
    $url_ficha = 'https://www.codigoamigo.com/chollos/' . $categoria_slug . '/' . $chollo['id']; // Ficha del chollo
    
    $mensaje .= "🛒 [Ver oferta en Amazon](" . $url_amazon . ")\n";
    $mensaje .= "💬 [Ver ficha y comentar](" . $url_ficha . ")\n\n";
    $mensaje .= "📢 *Únete a nuestro canal:* [t.me/cholloscodigoamigo](https://t.me/cholloscodigoamigo)\n";
    $mensaje .= "_Síguenos para más chollos y ofertas exclusivas_ 🎁";
    
    // Preparar datos para enviar
    $data = [
        'chat_id' => $chat_id,
        'text' => $mensaje,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => false
    ];
    
    // Si hay imagen, enviar con foto
    if (!empty($chollo['imagen']) && filter_var($chollo['imagen'], FILTER_VALIDATE_URL)) {
        // Usar sendPhoto con URL
        $url = "https://api.telegram.org/bot" . $bot_token . "/sendPhoto";
        $data = [
            'chat_id' => $chat_id,
            'photo' => $chollo['imagen'],
            'caption' => $mensaje,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => false
        ];
    } else {
        $url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $mensaje,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => false
        ];
    }
    
    // Enviar mensaje
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Error al publicar chollo en Telegram: " . $error);
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }
    
    if ($http_code !== 200) {
        error_log("Error HTTP al publicar chollo en Telegram: " . $http_code . " - " . $response);
        return ['success' => false, 'error' => 'Error de API: ' . $http_code];
    }
    
    $result = json_decode($response, true);
    
    if ($result['ok']) {
        // Actualizar chollo como publicado
        actualizarChollo($chollo_id, [
            'publicado_telegram' => true,
            'fecha_publicacion_telegram' => date('Y-m-d H:i:s')
        ]);
        
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Error al publicar: ' . ($result['description'] ?? 'Desconocido')];
}

/**
 * Obtiene la URL de una foto de Telegram
 */
function obtenerUrlFotoTelegram($file_id) {
    $bot_token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '1208948207:AAF0O45V1zcsp7wjRgbwOrLQ7tNRUHlfnME';
    
    $url = "https://api.telegram.org/bot" . $bot_token . "/getFile?file_id=" . urlencode($file_id);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($result['ok'] && isset($result['result']['file_path'])) {
        return "https://api.telegram.org/file/bot" . $bot_token . "/" . $result['result']['file_path'];
    }
    
    return '';
}

/**
 * Detecta categorías de un chollo. Puede devolver múltiples categorías.
 * 
 * @param string $texto Texto del chollo
 * @return array Array de categorías detectadas
 */
function detectarCategoria($texto) {
    $texto_lower = strtolower($texto);
    $categorias_detectadas = [];
    
    // Prioridad: categorías más específicas primero
    $categorias_keywords = [
        'videojuegos' => ['playstation', 'ps5', 'ps4', 'xbox', 'nintendo', 'switch', 'videojuego', 'juego', 'consola', 'ea sports', 'fc 26', 'fc 25', 'fifa', 'call of duty', 'steam', 'epic games', 'gaming', 'gamer', 'ratón gaming', 'auriculares gaming', 'cascos gaming', 'teclado gaming', 'silla gaming', 'monitor gaming'],
        'electronica' => [
            // Dispositivos móviles
            'iphone', 'samsung', 'android', 'smartphone', 'móvil', 'celular',
            // Computadoras
            'tablet', 'portátil', 'laptop', 'notebook', 'pc', 'ordenador', 'macbook',
            // Audio
            'auriculares', 'headphones', 'altavoz', 'altavoces', 'speaker', 'airpods',
            // Pantallas
            'tv', 'televisor', 'televisión', 'monitor', 'pantalla', 'display',
            // Periféricos
            'ratón', 'mouse', 'teclado', 'keyboard', 'webcam', 'cámara web',
            // Cámaras y vigilancia
            'cámara', 'camara', 'wifi', 'vigilancia', 'security', 'dvr', 'nvr',
            // Otros electrónicos
            'router', 'wifi', 'bluetooth', 'usb', 'cable', 'cargador', 'batería',
            'smartwatch', 'reloj inteligente', 'fitness tracker', 'drone',
            'impresora', 'scanner', 'proyector', 'chromecast', 'fire tv', 'roku'
        ],
        'moda' => ['zapatos', 'zapato', 'ropa', 'camiseta', 'pantalón', 'pantalones', 'vestido', 'chaqueta', 'bolso', 'mochila', 'reloj', 'gafas', 'gafas de sol', 'perfume', 'colonia'],
        'hogar' => ['mueble', 'muebles', 'sofá', 'sofa', 'mesa', 'silla', 'sillas', 'cama', 'colchón', 'almohada', 'toalla', 'toallas', 'cocina', 'nevera', 'frigorífico', 'lavadora', 'secadora', 'aspiradora', 'plancha', 'cafetera', 'batidora', 'microondas', 'horno'],
        'deportes' => ['deporte', 'deportes', 'gimnasio', 'running', 'fútbol', 'futbol', 'baloncesto', 'tenis', 'natación', 'natacion', 'bicicleta', 'bike', 'pesas', 'yoga', 'pilates', 'zapatillas deportivas'],
        'libros' => ['libro', 'libros', 'ebook', 'ebooks', 'kindle', 'lectura', 'novela', 'cuento', 'manual'],
        'amazon' => ['amazon'] // Solo si aparece explícitamente "amazon" como categoría
    ];
    
    // Buscar categorías
    foreach ($categorias_keywords as $categoria => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($texto_lower, $keyword) !== false) {
                if (!in_array($categoria, $categorias_detectadas)) {
                    $categorias_detectadas[] = $categoria;
                }
                break; // Solo una vez por categoría
            }
        }
    }
    
    // Si no se detectó ninguna categoría específica, usar 'general'
    if (empty($categorias_detectadas)) {
        $categorias_detectadas[] = 'general';
    }
    
    return $categorias_detectadas;
}

