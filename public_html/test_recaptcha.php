<?php
// Script de prueba para reCAPTCHA

include_once __DIR__ . '/myphp/recaptcha_helper.php';

echo "=== PRUEBA DE RECAPTCHA ===\n\n";

// 1. Probar con token vacío
echo "1. Probando con token vacío:\n";
$resultado1 = verificarRecaptcha('');
echo "   Resultado: " . ($resultado1 ? "✅ Válido" : "❌ Inválido") . "\n\n";

// 2. Probar con token inválido
echo "2. Probando con token inválido:\n";
$resultado2 = verificarRecaptcha('token_invalido_123');
echo "   Resultado: " . ($resultado2 ? "✅ Válido" : "❌ Inválido") . "\n\n";

// 3. Probar función de validación completa
echo "3. Probando función de validación completa:\n";
$resultado3 = validarRecaptcha('token_invalido_123');
echo "   Success: " . ($resultado3['success'] ? "true" : "false") . "\n";
echo "   Error: " . $resultado3['error'] . "\n\n";

echo "=== CONFIGURACIÓN ACTUAL ===\n";
echo "Clave secreta: " . RECAPTCHA_SECRET_KEY . "\n";
echo "Clave pública: 6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS\n\n";

echo "=== INSTRUCCIONES ===\n";
echo "1. Visita https://www.codigoamigo.com/contacto\n";
echo "2. Completa el formulario\n";
echo "3. Marca el checkbox de reCAPTCHA\n";
echo "4. Envía el formulario\n";
echo "5. Verifica que funcione correctamente\n\n";

echo "=== FIN DE LA PRUEBA ===\n";
?>
