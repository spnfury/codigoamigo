<?php
// Script de prueba para verificar entrega de emails

// Incluir archivos necesarios
include_once __DIR__ . '/myphp/email_helper.php';
include_once __DIR__ . '/config/email_config.php';

echo "=== PRUEBA DE ENTREGA DE EMAILS ===\n\n";

// 1. Verificar configuración actual
echo "1. CONFIGURACIÓN ACTUAL:\n";
echo "   Destino: thevega82@gmail.com\n";
echo "   Servidor SMTP: " . BREVO_SMTP_HOST . "\n";
echo "   Puerto: " . BREVO_SMTP_PORT . "\n";
echo "   Usuario SMTP: " . BREVO_SMTP_USERNAME . "\n";
echo "   Remitente: info@codigoamigo.com\n\n";

// 2. Enviar email de prueba
echo "2. ENVIANDO EMAIL DE PRUEBA...\n";

$datos_prueba = array(
    'nombre' => 'Test Sistema',
    'correo' => 'test@codigoamigo.com',
    'telefono' => '123456789',
    'mensaje' => 'Este es un email de prueba para verificar que el sistema de contacto funciona correctamente. Fecha: ' . date('Y-m-d H:i:s'),
    'origin' => 'Test Sistema - Verificación'
);

$url_logo_web = 'https://www.codigoamigo.com/img/logo_codigoamigo.png';

$resultado = enviarEmailContacto($datos_prueba, $url_logo_web);

echo "   Resultado: ";
if ($resultado['success']) {
    echo "✅ ÉXITO - Email enviado usando: " . $resultado['method'] . "\n";
} else {
    echo "❌ ERROR - " . $resultado['error'] . "\n";
}

echo "\n3. INSTRUCCIONES PARA VERIFICAR:\n";
echo "   - Revisa la bandeja de entrada de thevega82@gmail.com\n";
echo "   - Revisa la carpeta de SPAM/No deseados\n";
echo "   - Busca emails de 'Código Amigo' o 'info@codigoamigo.com'\n";
echo "   - Si no aparece, puede ser un problema de configuración de Brevo\n\n";

echo "4. POSIBLES SOLUCIONES:\n";
echo "   - Verificar configuración de dominio en Brevo\n";
echo "   - Comprobar que el dominio codigoamigo.com esté verificado\n";
echo "   - Revisar límites de envío en Brevo\n";
echo "   - Considerar usar un email de remitente diferente\n\n";

echo "=== FIN DE LA PRUEBA ===\n";
?>
