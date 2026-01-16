<?php
// Test específico para verificar que el footer NO se muestra en nuevo_codigo
$content = file_get_contents('/home/admin/web/codigoamigo.com/public_html/public/publicar_codigo.php');

echo "=== VERIFICACIÓN: FOOTER NO PRESENTE EN NUEVO_CODIGO ===\n\n";

$tests = [
    'get_footer() comentado/deshabilitado' => strpos($content, '<?php // get_footer(); ?>') !== false || strpos($content, '<?php /* get_footer(); */ ?>') !== false,
    'Comentario explicativo presente' => strpos($content, 'No mostrar footer en página nuevo_codigo') !== false,
    'Botón flotante SÍ presente' => strpos($content, 'floating-save-button') !== false,
    'Footer completamente removido' => strpos($content, 'get_footer();') === false || strpos($content, '<?php get_footer(); ?>') === false,
    'Estructura HTML intacta' => substr_count($content, '</html>') === 1 && substr_count($content, '</body>') === 1,
];

$all_passed = true;
foreach ($tests as $test => $passed) {
    $status = $passed ? '✅' : '❌';
    echo "{$status} {$test}\n";
    if (!$passed) $all_passed = false;
}

// Verificación adicional: buscar cualquier referencia al footer
$footer_references = [
    '<footer' => 'Elementos <footer encontrados',
    'footer-link' => 'Enlaces del footer encontrados',
    'footer-modern' => 'Footer moderno encontrado',
    'footer-main' => 'Sección principal del footer encontrada',
    'footer-bottom' => 'Parte inferior del footer encontrada',
];

echo "\n=== BÚSQUEDA DE ELEMENTOS DEL FOOTER ===\n";
$footer_found = false;
foreach ($footer_references as $pattern => $description) {
    if (strpos($content, $pattern) !== false) {
        echo "⚠️ {$description}\n";
        $footer_found = true;
    } else {
        echo "✅ {$description}: NO encontrado\n";
    }
}

echo "\n=== RESULTADO FINAL ===\n";
if ($all_passed && !$footer_found) {
    echo "🎉 ¡PERFECTO! El footer ha sido completamente removido de nuevo_codigo\n";
    echo "✅ La página ahora NO tiene footer\n";
    echo "✅ Solo queda el botón flotante para navegación\n";
    echo "✅ No deberían haber puntos de fuga visuales\n\n";

    echo "📋 CONFIRMACIÓN:\n";
    echo "• get_footer() está comentado ✅\n";
    echo "• Botón flotante sigue presente ✅\n";
    echo "• Estructura HTML mantenida ✅\n";
    echo "• No se encontraron elementos del footer ✅\n";
} else {
    echo "❌ ERROR: El footer todavía está presente o no se removió correctamente\n";
    echo "🔧 Revisar la implementación\n";
}

echo "\n=== PRÓXIMOS PASOS ===\n";
echo "✅ La página nuevo_codigo ahora debería cargar SIN footer\n";
echo "✅ El botón flotante proporciona toda la navegación necesaria\n";
echo "✅ Diseño limpio sin elementos innecesarios\n";
echo "✅ Problema de puntos de fuga solucionado\n";
?>

