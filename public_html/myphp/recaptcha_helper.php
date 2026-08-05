<?php
/**
 * Helper para validación de Google reCAPTCHA
 */

// Clave secreta de reCAPTCHA: leída de /private/api_secrets.php (fuera del
// webroot, chmod 600), no hardcodeada. Rotar allí tras la exposición pública.
if (!defined('RECAPTCHA_SECRET_KEY')) {
    if (empty($_ENV['RECAPTCHA_SECRET_KEY'])) {
        $_priv = dirname(__DIR__, 2) . '/private/api_secrets.php';
        if (is_file($_priv)) {
            require_once $_priv;
        }
    }
    $_secret = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';
    if ($_secret === '') {
        $_g = getenv('RECAPTCHA_SECRET_KEY');
        $_secret = ($_g !== false) ? $_g : '';
    }
    define('RECAPTCHA_SECRET_KEY', $_secret);
}

/**
 * Valida el token de reCAPTCHA con Google
 * @param string $recaptcha_response Token de respuesta de reCAPTCHA
 * @param string $remote_ip IP del usuario (opcional)
 * @return array Resultado de la validación
 */
function validarRecaptcha($recaptcha_response, $remote_ip = null) {
    $resultado = array(
        'success' => false,
        'error' => ''
    );
    
    // Verificar que se proporcionó el token
    if (empty($recaptcha_response)) {
        $resultado['error'] = 'Token de reCAPTCHA no proporcionado';
        return $resultado;
    }
    
    // Preparar datos para la verificación
    $data = array(
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha_response
    );
    
    // Añadir IP si está disponible
    if ($remote_ip) {
        $data['remoteip'] = $remote_ip;
    }
    
    // Realizar petición a Google
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        $resultado['error'] = 'Error al conectar con el servidor de reCAPTCHA';
        return $resultado;
    }

    // Guardar respuesta raw para debugging
    $resultado['raw_response'] = $response;

    // Decodificar respuesta
    $response_data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $resultado['error'] = 'Error al decodificar respuesta de reCAPTCHA: ' . json_last_error_msg();
        return $resultado;
    }
    
    // Verificar resultado
    if (isset($response_data['success']) && $response_data['success'] === true) {
        $resultado['success'] = true;

        // Para v3, incluir el score en el resultado
        if (isset($response_data['score'])) {
            $resultado['score'] = $response_data['score'];
        }
        if (isset($response_data['action'])) {
            $resultado['action'] = $response_data['action'];
        }
    } else {
        $resultado['error'] = 'Verificación de reCAPTCHA fallida';
        
        // Log de errores específicos si están disponibles
        if (isset($response_data['error-codes']) && is_array($response_data['error-codes'])) {
            $error_codes = implode(', ', $response_data['error-codes']);
            $resultado['error'] .= ' - Códigos de error: ' . $error_codes;
            error_log("Google reCAPTCHA error codes: " . $error_codes . " (IP: " . ($remote_ip ?? 'N/A') . ")");
        } else {
            error_log("Google reCAPTCHA failed without error codes. Response: " . $response);
        }
    }

    // Incluir respuesta completa para el llamador si es necesario (debugging)
    $resultado['full_response'] = $response_data;
    
    return $resultado;
}

/**
 * Función de conveniencia para validar reCAPTCHA con IP automática
 * @param string $recaptcha_response Token de respuesta de reCAPTCHA
 * @return bool True si la validación es exitosa, false en caso contrario
 */
function verificarRecaptcha($recaptcha_response) {
    $remote_ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $resultado = validarRecaptcha($recaptcha_response, $remote_ip);
    
    if (!$resultado['success']) {
        error_log("reCAPTCHA validation failed: " . $resultado['error']);
    }
    
    return $resultado['success'];
}
?>
