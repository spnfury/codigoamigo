<?php

/**
 * Lógica principal del Bot de Chollos para procesar mensajes de Telegram
 * Extrae info, reescribe con IA, detecta categorías y publica.
 */

require_once __DIR__ . '/funciones_chollos.php';
require_once __DIR__ . '/funciones_chollos_groq.php';
require_once __DIR__ . '/funciones_chollos_fuentes.php';
require_once __DIR__ . '/funciones_chollos_amazon.php';

/**
 * Procesa un mensaje de Telegram y lo convierte en un chollo
 */
function procesarMensajeTelegram($mensaje, $fuente_id = null) {
    if (empty($mensaje['text']) && empty($mensaje['caption'])) {
        return ['success' => false, 'error' => 'Mensaje vacío'];
    }

    $texto_original = $mensaje['text'] ?? $mensaje['caption'] ?? '';
    
    // 1. Extraer información básica (Título, Precio, Enlace)
    $info = extraerInfoChollo($texto_original);
    
    if (empty($info['enlace'])) {
        return ['success' => false, 'error' => 'No se encontró enlace en el mensaje'];
    }

    // Expandir y limpiar enlace de Amazon antes de guardar
    if (esEnlaceAmazon($info['enlace'])) {
        // No pasamos ID de chollo porque aún no existe
        $info['enlace'] = expandirAcortadorAmazon($info['enlace']);
        $info['asin'] = extraerASIN($info['enlace']);
        // Limpiamos el enlace (quitamos tags previos si los hay)
        $info['enlace'] = convertirEnlaceAmazon($info['enlace']);
    }

    // 2. Procesar con Groq (Reescribir y Categorizar)
    $resultado_groq = procesarCholloConGroq($info['titulo'], $info['descripcion'], $texto_original);
    
    if ($resultado_groq['success']) {
        $info['titulo_ia'] = $resultado_groq['titulo_reescrito'];
        $info['descripcion_ia'] = $resultado_groq['descripcion_reescrita'];
        $info['categoria_ia'] = $resultado_groq['categoria']; // Array jerárquico
    }

    // 3. Manejar imagen si el mensaje tiene foto
    if (!empty($mensaje['photo'])) {
        // Obtener la foto de mayor resolución
        $photo = end($mensaje['photo']);
        $file_id = $photo['file_id'];
        
        // Descargar foto de Telegram
        $ruta_imagen = descargarFotoTelegram($file_id);
        if ($ruta_imagen) {
            $info['imagen'] = $ruta_imagen;
        }
    }

    // 4. Guardar en Base de Datos
    $datos_chollo = [
        'titulo' => $info['titulo_ia'] ?? $info['titulo'],
        'descripcion' => $info['descripcion_ia'] ?? $info['descripcion'],
        'precio_original' => $info['precio_original'],
        'precio_descuento' => $info['precio_descuento'],
        'porcentaje_descuento' => $info['porcentaje_descuento'],
        'enlace' => $info['enlace'],
        'enlace_original' => $info['enlace'], // Guardamos el expandido/limpio como original también
        'asin' => $info['asin'] ?? null,
        'imagen' => $info['imagen'],
        'categoria' => $info['categoria_ia'] ?? ['General'],
        'fuente' => 'telegram',
        'fuente_id' => $fuente_id,
        'mensaje_original_id' => $mensaje['message_id'] ?? null,
        'texto_original' => $texto_original,
        'estado' => 1, // Activo por defecto
        'texto_reescrito' => $resultado_groq['success']
    ];

    $resultado_guardado = crearChollo($datos_chollo);
    
    if ($resultado_guardado['success']) {
        $chollo_id = $resultado_guardado['id'];
        
        // 5. Opcional: Publicar en nuestro canal de Telegram
        if (defined('TELEGRAM_CHOLLOS_AUTO_PUBLICAR') && TELEGRAM_CHOLLOS_AUTO_PUBLICAR) {
            publicarCholloEnTelegram($chollo_id);
        }
        
        return ['success' => true, 'id' => $chollo_id];
    }

    return ['success' => false, 'error' => 'Error al guardar en BD: ' . ($resultado_guardado['error'] ?? 'desconocido')];
}

/**
 * Descarga una foto de los servidores de Telegram
 */
function descargarFotoTelegram($file_id) {
    $token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
    if (empty($token)) return null;

    // 1. Obtener la ruta del archivo
    $url_get_file = "https://api.telegram.org/bot{$token}/getFile?file_id={$file_id}";
    $response = file_get_contents($url_get_file);
    $result = json_decode($response, true);

    if (isset($result['ok']) && $result['ok']) {
        $file_path = $result['result']['file_path'];
        $url_download = "https://api.telegram.org/file/bot{$token}/{$file_path}";
        
        // 2. Descargar y guardar localmente
        $ext = pathinfo($file_path, PATHINFO_EXTENSION);
        $nombre_archivo = 'chollo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $ruta_destino = __DIR__ . '/../public/uploads/chollos/' . $nombre_archivo;
        
        // Asegurar directorio
        if (!is_dir(dirname($ruta_destino))) {
            mkdir(dirname($ruta_destino), 0755, true);
        }

        if (copy($url_download, $ruta_destino)) {
            return '/uploads/chollos/' . $nombre_archivo;
        }
    }

    return null;
}

/**
 * Publica un chollo en un canal de Telegram
 */
function publicarCholloEnTelegram($chollo_id, $chat_id = null) {
    if (empty($chat_id)) {
        $chat_id = defined('TELEGRAM_CHOLLOS_CHAT_ID_SALIDA') ? TELEGRAM_CHOLLOS_CHAT_ID_SALIDA : '';
    }
    
    if (empty($chat_id)) return false;

    $chollo = obtenerCholloPorId($chollo_id);
    if (!$chollo) return false;

    $token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
    if (empty($token)) return false;

    // Construir mensaje atractivo
    $titulo = mb_strtoupper($chollo['titulo']);
    $precio = $chollo['precio_descuento'] ? number_format($chollo['precio_descuento'], 2, ',', '.') . '€' : '';
    $precio_original = $chollo['precio_original'] ? ' (PVP: ' . number_format($chollo['precio_original'], 2, ',', '.') . '€)' : '';
    $descuento = $chollo['porcentaje_descuento'] ? '🔥 ' . $chollo['porcentaje_descuento'] . '% DTO!' : '';
    $url = 'https://www.codigoamigo.com/chollo/' . $chollo_id;

    $mensaje = "{$titulo}\n\n";
    if ($precio) {
        $mensaje .= "💰 PRECIO: {$precio}{$precio_original}\n";
    }
    if ($descuento) {
        $mensaje .= "{$descuento}\n";
    }
    
    $mensaje .= "\n" . mb_substr($chollo['descripcion'], 0, 300) . "...\n\n";
    $mensaje .= "🛒 COMPRAR AQUÍ:\n{$url}";

    // Enviar con imagen si existe
    if (!empty($chollo['imagen'])) {
        $ruta_completa = __DIR__ . '/../public' . $chollo['imagen'];
        if (file_exists($ruta_completa)) {
            $post_fields = [
                'chat_id'   => $chat_id,
                'photo'     => new CURLFile(realpath($ruta_completa)),
                'caption'   => $mensaje,
                'parse_mode' => 'HTML'
            ];
            $url_api = "https://api.telegram.org/bot{$token}/sendPhoto";
        } else {
            // Si la imagen no existe en disco, enviar solo texto
            $post_fields = [
                'chat_id' => $chat_id,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false
            ];
            $url_api = "https://api.telegram.org/bot{$token}/sendMessage";
        }
    } else {
        $post_fields = [
            'chat_id' => $chat_id,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false
        ];
        $url_api = "https://api.telegram.org/bot{$token}/sendMessage";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
    curl_setopt($ch, CURLOPT_URL, $url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $output = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($output, true);
    if (isset($result['ok']) && $result['ok']) {
        // Marcar como publicado
        actualizarChollo($chollo_id, ['publicado_telegram' => true]);
        return true;
    }

    return false;
}
