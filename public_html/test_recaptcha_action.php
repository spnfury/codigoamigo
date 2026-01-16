<?php
/**
 * Acción de prueba para validar reCAPTCHA
 */

header('Content-Type: text/plain');

// Incluir el helper de reCAPTCHA
require_once __DIR__ . '/myphp/recaptcha_helper.php';

// Obtener datos del formulario
$nombre = $_POST['nombre'] ?? '';
$email = $_POST['email'] ?? '';
$mensaje = $_POST['mensaje'] ?? '';
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

// Verificar datos básicos
if (empty($nombre) || empty($email) || empty($mensaje)) {
    echo "ERROR: Faltan datos del formulario";
    exit;
}

// Verificar reCAPTCHA v3
$resultado = validarRecaptcha($recaptcha_response, $_SERVER['REMOTE_ADDR'] ?? null);

if ($resultado['success']) {
    echo "SUCCESS: reCAPTCHA v3 válido\n";
    echo "Score: " . ($resultado['score'] ?? 'No disponible') . "\n";
    echo "Datos recibidos:\n";
    echo "Nombre: $nombre\n";
    echo "Email: $email\n";
    echo "Mensaje: $mensaje\n";
    echo "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Desconocida') . "\n";

    // Para v3, también verificamos el score (umbral recomendado: > 0.5)
    $score = $resultado['score'] ?? 0;
    if ($score < 0.5) {
        echo "WARNING: Score bajo ($score), podría ser spam\n";
    }

    // Aquí podrías procesar el formulario (enviar email, etc.)
    // processForm($nombre, $email, $mensaje);

} else {
    echo "ERROR: reCAPTCHA inválido\n";
    echo "Error: " . $resultado['error'] . "\n";

    if (isset($resultado['error-codes'])) {
        echo "Códigos de error de Google: " . $resultado['error-codes'] . "\n";
    }
}
?>
