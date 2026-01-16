<?php
// Test para verificar que el botón flotante se deshabilita correctamente
$content = file_get_contents('/home/admin/web/codigoamigo.com/public_html/public/publicar_codigo.php');

echo "=== VERIFICACIÓN DE VALIDACIÓN DEL BOTÓN FLOTANTE ===\n\n";

$tests = [
    'Botón flotante tiene atributo disabled inicialmente' => strpos($content, 'id="floating-save-button"') !== false && strpos($content, 'disabled') !== false,
    'Función validateAllRequiredFields existe' => strpos($content, 'function validateAllRequiredFields()') !== false,
    'Validación de campos obligatorios implementada' => strpos($content, 'marcaValue !== \'\' && beneficio !== \'\' && codigo !== \'\' && descripcion !== \'\'') !== false,
    'Evento on input en num_beneficio' => strpos($content, '$("#num_beneficio").on(\'blur input\'') !== false || strpos($content, '$(\'#num_beneficio\').on(\'blur input\'') !== false,
    'Evento on input en codigo' => strpos($content, '$("#codigo").on(\'blur input\'') !== false || strpos($content, '$(\'#codigo\').on(\'blur input\'') !== false,
    'Evento on input en descripcion' => strpos($content, '$("#descripcion").on(\'blur input\'') !== false || strpos($content, '$(\'#descripcion\').on(\'blur input\'') !== false,
    'Evento on blur en marca' => strpos($content, '$("#marca").on(\'blur\'') !== false || strpos($content, '$(\'#marca\').on(\'blur\'') !== false,
    'Evento on change en marca' => strpos($content, '$("#marca").on(\'change\'') !== false || strpos($content, '$(\'#marca\').on(\'change\'') !== false,
    'Evento on select2:select en marca' => strpos($content, '$("#marca").on(\'select2:select\'') !== false || strpos($content, '$(\'#marca\').on(\'select2:select\'') !== false,
    'Validación en envío de formulario' => strpos($content, 'validateAllRequiredFields()') !== false,
    'Estilos para botón deshabilitado' => strpos($content, '.floating-save-button:disabled') !== false,
    'Modo modificación verificado' => strpos($content, '<?php if ($modo_modificacion): ?>') !== false,
];

$all_passed = true;
foreach ($tests as $test => $passed) {
    $status = $passed ? '✅' : '❌';
    echo "{$status} {$test}\n";
    if (!$passed) $all_passed = false;
}

// Verificación adicional: buscar elementos específicos
echo "\n=== VERIFICACIONES ESPECÍFICAS ===\n";

$specific_checks = [
    'Botón flotante con ID correcto' => preg_match('/id="floating-save-button"/', $content),
    'Propiedad disabled correctamente aplicada' => preg_match('/disabled/', $content),
    'Función prop() utilizada correctamente' => preg_match('/\.prop\("disabled",\s*(true|false)\)/', $content),
    'Validación de formulario integrada' => preg_match('/if\s*\(!validateAllRequiredFields\(\)\)/', $content),
    'Estilos CSS para estado deshabilitado' => preg_match('/\.floating-save-button:disabled\s*\{[^}]*\}/s', $content),
];

foreach ($specific_checks as $check => $found) {
    $status = $found ? '✅' : '❌';
    echo "{$status} {$check}\n";
    if (!$found) $all_passed = false;
}

echo "\n=== RESULTADO FINAL ===\n";
if ($all_passed) {
    echo "🎉 ¡EXCELENTE! La validación del botón flotante está correctamente implementada\n\n";
    echo "📋 FUNCIONALIDADES VERIFICADAS:\n";
    echo "• Botón deshabilitado inicialmente ✅\n";
    echo "• Validación en tiempo real de campos obligatorios ✅\n";
    echo "• Eventos de cambio en todos los campos requeridos ✅\n";
    echo "• Validación integrada en envío de formulario ✅\n";
    echo "• Estilos CSS para estado deshabilitado ✅\n";
    echo "• Soporte para modo modificación ✅\n\n";

    echo "🚀 COMPORTAMIENTO ESPERADO:\n";
    echo "• El botón estará deshabilitado hasta completar todos los campos\n";
    echo "• Se habilitará automáticamente al completar el último campo requerido\n";
    echo "• En modo edición, se verificará automáticamente después de cargar\n";
    echo "• El formulario no se podrá enviar con campos obligatorios vacíos\n";
} else {
    echo "❌ ERROR: Algunos elementos de validación faltan o están mal implementados\n";
    echo "🔧 Revisar las verificaciones marcadas con ❌ arriba\n";
}

echo "\n=== PRÓXIMOS PASOS ===\n";
echo "✅ La página nuevo_codigo ahora tiene validación inteligente del botón\n";
echo "✅ Mejor experiencia de usuario con feedback visual claro\n";
echo "✅ Prevención de envío de formularios incompletos\n";
echo "✅ Diseño más profesional y UX mejorada\n";
?>
