<?php
// Script de prueba para el formulario de contacto

// Incluir archivos necesarios
include_once __DIR__ . '/myphp/email_helper.php';
include_once __DIR__ . '/config/email_config.php';

// Datos de prueba
$datos_prueba = array(
    'nombre' => 'Test Usuario',
    'correo' => 'test@test.com',
    'telefono' => '123456789',
    'mensaje' => 'Este es un mensaje de prueba para verificar el funcionamiento del formulario de contacto.',
    'origin' => 'Test - Formulario contacto usuarios'
);

$url_logo_web = 'https://www.codigoamigo.com/img/logo_codigoamigo.png';

echo "Probando envío de email de contacto...\n";
echo "Datos de prueba:\n";
print_r($datos_prueba);
echo "\n";

// Probar la función de envío
$resultado = enviarEmailContacto($datos_prueba, $url_logo_web);

echo "Resultado del envío:\n";
print_r($resultado);

if ($resultado['success']) {
    echo "\n✅ Email enviado correctamente usando: " . $resultado['method'] . "\n";
} else {
    echo "\n❌ Error enviando email: " . $resultado['error'] . "\n";
}
?>
