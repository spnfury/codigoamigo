<?php
include_once __DIR__ . '/inc/logger.php';
/**
 * Procesador de formulario de contacto con reCAPTCHA v3
 */

header('Content-Type: text/plain');

// Incluir el helper de reCAPTCHA
require_once __DIR__ . '/myphp/recaptcha_helper.php';

// Obtener datos del formulario
$nombre = $_POST['nombre'] ?? '';
$email = $_POST['email'] ?? '';
$mensaje = $_POST['mensaje'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$form_type = $_POST['form_type'] ?? 'contacto'; // Identificar tipo de formulario
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

// Verificar datos básicos
if (empty($nombre) || empty($email) || empty($mensaje)) {
    echo "ERROR: Faltan datos del formulario";
    exit;
}

// Verificar email válido
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "ERROR: Email no válido";
    exit;
}

// Verificar reCAPTCHA v3
$resultado = validarRecaptcha($recaptcha_response, $_SERVER['REMOTE_ADDR'] ?? null);

if ($resultado['success']) {
    // Verificar score para v3 (umbral recomendado: > 0.5)
    $score = $resultado['score'] ?? 0;
    if ($score < 0.5) {
        echo "WARNING: Posible spam detectado (score: $score)";
        // Puedes decidir rechazar o marcar como sospechoso
        // exit;
    }

    // Procesar formulario - aquí va tu lógica de envío de email
    $email_enviado = enviarEmailContacto($nombre, $email, $telefono, $mensaje, $form_type);

    if ($email_enviado) {
        echo "SUCCESS: Mensaje enviado correctamente";
    } else {
        echo "ERROR: Error al enviar el mensaje";
    }

} else {
    echo "ERROR: Verificación de reCAPTCHA fallida - " . $resultado['error'];
}

/**
 * Función para enviar email (debes implementar según tu sistema de email)
 */
function enviarEmailContacto($nombre, $email, $telefono, $mensaje, $form_type = 'contacto') {
    // Aquí implementa tu lógica de envío de email
    // Por ejemplo, usando mail() de PHP o alguna librería

    $para = 'info@codigoamigo.com';

    // Diferentes asuntos según el tipo de formulario
    if ($form_type === 'empresarial') {
        $asunto = 'Nuevo mensaje de contacto empresarial - CodigoAmigo.com';
    } else {
        $asunto = 'Nuevo mensaje de contacto - CodigoAmigo.com';
    }

    $contenido = "Nombre: $nombre\n";
    $contenido .= "Email: $email\n";
    if (!empty($telefono)) {
        $contenido .= "Teléfono: $telefono\n";
    }
    $contenido .= "Tipo de formulario: " . ucfirst($form_type) . "\n";
    $contenido .= "Mensaje:\n$mensaje\n";
    $contenido .= "\n---\n";
    $contenido .= "Enviado desde formulario de contacto\n";
    $contenido .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Desconocida');

    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";

    // Descomenta la siguiente línea cuando tengas configurado el envío de email
    // return mail($para, $asunto, $contenido, $headers);

    // Por ahora, solo simulamos que se envió correctamente
    log_info("EMAIL SIMULADO - [$form_type] De: $nombre <$email> - Mensaje: " . substr($mensaje, 0, 100) . "...");
    return true;
}
?>
