<?php
/**
 * Script específico para validar tokens de reCAPTCHA en debug
 */

header('Content-Type: text/plain');

// Incluir el helper de reCAPTCHA
require_once __DIR__ . '/myphp/recaptcha_helper.php';

// Obtener el token
$token = $_POST['token'] ?? '';

if (empty($token)) {
    echo "ERROR: No se recibió token de reCAPTCHA\n";
    exit;
}

echo "=== DEBUG VALIDACIÓN RECAPTCHA ===\n";
echo "Token recibido: " . substr($token, 0, 50) . "...\n";
echo "Longitud del token: " . strlen($token) . " caracteres\n";
echo "IP del cliente: " . ($_SERVER['REMOTE_ADDR'] ?? 'Desconocida') . "\n\n";

// Validar el token
$resultado = validarRecaptcha($token, $_SERVER['REMOTE_ADDR'] ?? null);

echo "Resultado de validación:\n";
echo "- Success: " . ($resultado['success'] ? 'true' : 'false') . "\n";

if (isset($resultado['score'])) {
    echo "- Score: " . $resultado['score'] . "\n";
}

if (isset($resultado['action'])) {
    echo "- Action: " . $resultado['action'] . "\n";
}

if (!$resultado['success']) {
    echo "- Error: " . $resultado['error'] . "\n";
}

echo "\n=== DETALLES TÉCNICOS ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'No disponible') . "\n";
echo "Método de solicitud: " . $_SERVER['REQUEST_METHOD'] . "\n";

// También mostrar la respuesta completa de Google para debugging
if (isset($resultado['raw_response'])) {
    echo "\nRespuesta raw de Google:\n";
    echo $resultado['raw_response'] . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>
