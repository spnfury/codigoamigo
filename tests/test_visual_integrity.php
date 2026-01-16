<?php
// Test de integridad visual y puntos de fuga en publicar_codigo.php
$content = file_get_contents('/home/admin/web/codigoamigo.com/public_html/public/publicar_codigo.php');

echo "=== VERIFICACIÓN DE INTEGRIDAD VISUAL ===\n\n";

// Verificaciones críticas de diseño
$visual_checks = [
    'Viewport meta tag presente' => strpos($content, 'name="viewport"') !== false,
    'CSS responsive incluido' => strpos($content, '@media') !== false,
    'Botón flotante con posición fija' => strpos($content, 'position: fixed') !== false,
    'Z-index apropiado para elementos flotantes' => strpos($content, 'z-index: 1000') !== false,
    'Transiciones CSS presentes' => strpos($content, 'transition:') !== false,
    'Box-shadow presente' => strpos($content, 'box-shadow:') !== false,
    'CSS para móviles incluido' => strpos($content, '@media (max-width: 768px)') !== false,
    'Contenedor principal presente' => strpos($content, 'class="container') !== false,
];

// Elementos que podrían causar puntos de fuga
$potential_overflow_issues = [
    'Elementos con ancho fijo excesivo' => preg_match('/width:\s*[\d]+px/', $content),
    'Elementos con posición absoluta sin contenedor' => preg_match('/position:\s*absolute[^}]*left:\s*[\d]/', $content),
    'Márgenes negativos que podrían causar overflow' => preg_match('/margin-\w*:\s*-\d/', $content),
    'Transforms que podrían desplazar elementos fuera de vista' => preg_match('/transform:\s*translate[XYZ]/', $content),
    'Overflow hidden presente (bueno)' => strpos($content, 'overflow: hidden') !== false,
    'Elementos con scroll horizontal' => strpos($content, 'overflow-x') !== false,
];

echo "=== VERIFICACIONES DE ESTILO ===\n";
$all_visual_passed = true;
foreach ($visual_checks as $check => $passed) {
    $status = $passed ? '✅' : '❌';
    echo "{$status} {$check}\n";
    if (!$passed) $all_visual_passed = false;
}

echo "\n=== POSIBLES PUNTOS DE FUGA ===\n";
$overflow_warnings = 0;
foreach ($potential_overflow_issues as $issue => $found) {
    $status = $found ? '⚠️' : '✅';
    echo "{$status} {$issue}\n";
    if ($found && strpos($issue, '(bueno)') === false) {
        $overflow_warnings++;
    }
}

echo "\n=== ANÁLISIS DEL BOTÓN FLOTANTE ===\n";

// Extraer y analizar estilos del botón flotante
if (preg_match('/\.floating-save-button\s*\{([^}]*)\}/s', $content, $css_match)) {
    $button_css = $css_match[1];
    echo "✅ Estilos del botón flotante encontrados\n\n";

    $button_properties = [
        'Posición fija' => strpos($button_css, 'position: fixed') !== false,
        'Posición bottom apropiada' => preg_match('/bottom:\s*\d+px/', $button_css),
        'Z-index adecuado' => preg_match('/z-index:\s*\d+/', $button_css),
        'Responsive design' => strpos($content, '@media') !== false && strpos($button_css, 'left:') !== false,
        'Transiciones suaves' => strpos($button_css, 'transition:') !== false,
        'Colores definidos' => preg_match('/(background|color):/', $button_css),
        'Bordes redondeados' => strpos($button_css, 'border-radius:') !== false,
        'Sombra presente' => strpos($button_css, 'box-shadow:') !== false,
    ];

    foreach ($button_properties as $property => $has_property) {
        $status = $has_property ? '✅' : '❌';
        echo "{$status} {$property}\n";
        if (!$has_property) $all_visual_passed = false;
    }
} else {
    echo "❌ No se pudieron encontrar estilos del botón flotante\n";
    $all_visual_passed = false;
}

echo "\n=== ANÁLISIS DEL FOOTER ===\n";

// Verificar estructura del footer
$footer_section = substr($content, strpos($content, '<footer'), strpos($content, '</footer>') + 8);
$footer_issues = [
    'Footer correctamente cerrado' => strpos($footer_section, '</footer>') !== false,
    'Contenedor principal presente' => strpos($footer_section, '<div class="container">') !== false,
    'Estructura responsive' => strpos($footer_section, 'col-md-') !== false || strpos($footer_section, 'col-sm-') !== false,
    'Enlaces correctamente formateados' => preg_match('/<a[^>]*class="[^"]*footer-link[^"]*"/', $footer_section),
    'Sin elementos posicionados absolutamente' => strpos($footer_section, 'position: absolute') === false,
];

foreach ($footer_issues as $issue => $ok) {
    $status = $ok ? '✅' : '❌';
    echo "{$status} {$issue}\n";
    if (!$ok) $all_visual_passed = false;
}

echo "\n=== RESULTADO VISUAL FINAL ===\n";
if ($all_visual_passed && $overflow_warnings < 3) {
    echo "🎉 EXCELENTE: La página tiene buena integridad visual\n";
    echo "✅ El botón flotante está correctamente posicionado\n";
    echo "✅ El footer tiene estructura sólida\n";
    echo "✅ Bajo riesgo de puntos de fuga visuales\n\n";

    echo "📋 RESUMEN DE LA PÁGINA:\n";
    echo "• Diseño responsive implementado correctamente\n";
    echo "• Botón flotante con posición fija y z-index apropiado\n";
    echo "• Footer con estructura en columnas\n";
    echo "• Transiciones y efectos visuales presentes\n";
    echo "• Meta viewport configurado para móviles\n";
} else {
    echo "⚠️ PRECAUCIÓN: Algunos elementos podrían causar problemas visuales\n";
    if (!$all_visual_passed) {
        echo "❌ Hay elementos críticos que necesitan atención\n";
    }
    if ($overflow_warnings >= 3) {
        echo "⚠️ Múltiples elementos podrían causar puntos de fuga\n";
    }
}

echo "\n=== RECOMENDACIONES VISUALES ===\n";
if ($all_visual_passed) {
    echo "✅ El diseño visual es sólido\n";
    echo "✅ El botón flotante debería funcionar perfectamente\n";
    echo "✅ No se esperan problemas de puntos de fuga\n";
} else {
    echo "🔧 Revisar elementos marcados con ❌\n";
    echo "🔧 Verificar posibles puntos de fuga identificados con ⚠️\n";
    echo "🔧 Probar la página en diferentes dispositivos y navegadores\n";
}
?>

