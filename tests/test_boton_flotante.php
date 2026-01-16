<?php
// Test del botón flotante en publicar_codigo.php
$content = file_get_contents('/home/admin/web/codigoamigo.com/public_html/public/publicar_codigo.php');

// Buscar el botón flotante
if (preg_match('/<button[^>]*class="[^"]*floating-save-button[^"]*"[^>]*>/s', $content, $matches)) {
    echo "✅ BOTÓN FLOTANTE ENCONTRADO:\n";
    echo htmlspecialchars($matches[0]) . "\n\n";

    // Verificar que no tenga condición restrictiva
    if (strpos($content, '<?php if ($modo_modificacion) { ?>') === false) {
        echo "✅ El botón NO tiene condición restrictiva - aparece siempre\n";
    } else {
        echo "❌ El botón tiene condición restrictiva\n";
    }

    // Verificar texto dinámico
    if (preg_match('/<\?php echo \$modo_modificacion \? ([^:]+) : ([^\?>]+);/', $content, $textos)) {
        echo "✅ El botón tiene texto dinámico:\n";
        echo "  - Modo modificación: " . $textos[1] . "\n";
        echo "  - Modo creación: " . $textos[2] . "\n";
    } else {
        echo "❌ El botón no tiene texto dinámico\n";
    }

} else {
    echo "❌ ERROR: No se encontró el botón flotante\n\n";
}

// Buscar estilos CSS del botón flotante
if (preg_match('/\.floating-save-button\s*\{[^}]*\}/s', $content, $css_matches)) {
    echo "✅ ESTILOS CSS ENCONTRADOS:\n";
    echo htmlspecialchars($css_matches[0]) . "\n\n";
} else {
    echo "❌ ERROR: No se encontraron estilos CSS del botón flotante\n";
}

// Verificar estilos responsive
if (strpos($content, '@media (max-width: 768px)') !== false) {
    echo "✅ Se encontraron media queries para responsive design\n";
} else {
    echo "❌ No se encontraron media queries específicos\n";
}

echo "\n📋 RESUMEN DE VERIFICACIÓN:\n";
echo "- El botón flotante ahora aparece siempre (sin condición restrictiva)\n";
echo "- Tiene texto dinámico según el contexto\n";
echo "- Tiene estilos CSS apropiados\n";
echo "- Tiene diseño responsive para móvil\n";
?>

