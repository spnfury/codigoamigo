<?php
// Script para probar envío de emails a diferentes destinatarios

// Incluir archivos necesarios
include_once __DIR__ . '/myphp/email_helper.php';
include_once __DIR__ . '/config/email_config.php';

echo "=== PRUEBA DE ENVÍO A MÚLTIPLES DESTINATARIOS ===\n\n";

// Lista de emails de prueba
$emails_prueba = [
    'thevega82@gmail.com',
    'luiss.garces@gmail.com',
    'sgonzalvezp@gmail.com'
];

$url_logo_web = 'https://www.codigoamigo.com/img/logo_codigoamigo.png';

foreach ($emails_prueba as $email) {
    echo "Enviando email de prueba a: $email\n";
    
    $datos_prueba = array(
        'nombre' => 'Test Sistema',
        'correo' => 'test@codigoamigo.com',
        'telefono' => '123456789',
        'mensaje' => "Email de prueba enviado el " . date('Y-m-d H:i:s') . " para verificar la entrega.",
        'origin' => 'Test Sistema - Verificación múltiple'
    );
    
    $resultado = enviarEmailConBrevo(
        $email,
        "Usuario de Prueba",
        "Prueba de Sistema - " . date('H:i:s'),
        "<h2>Email de Prueba</h2><p>Este es un email de prueba enviado el " . date('Y-m-d H:i:s') . "</p><p>Si recibes este email, el sistema funciona correctamente.</p>",
        "Email de prueba enviado el " . date('Y-m-d H:i:s') . " - Si recibes este email, el sistema funciona correctamente.",
        "noreply@codigoamigo.com",
        "Código Amigo"
    );
    
    if ($resultado['success']) {
        echo "  ✅ Enviado correctamente usando: " . $resultado['method'] . "\n";
    } else {
        echo "  ❌ Error: " . $resultado['error'] . "\n";
    }
    
    echo "\n";
    sleep(2); // Pausa entre envíos
}

echo "=== INSTRUCCIONES ===\n";
echo "1. Revisa las bandejas de entrada de todos los emails\n";
echo "2. Revisa las carpetas de SPAM\n";
echo "3. Si algunos emails llegan y otros no, puede ser:\n";
echo "   - Problema de filtros de spam específicos\n";
echo "   - Configuración de dominio en Brevo\n";
echo "   - Límites de envío por destinatario\n\n";

echo "=== FIN DE LA PRUEBA ===\n";
?>
