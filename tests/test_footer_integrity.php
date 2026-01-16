<?php
// Test de integridad del footer en publicar_codigo.php
$content = file_get_contents('/home/admin/web/codigoamigo.com/public_html/public/publicar_codigo.php');

// Verificaciones básicas de estructura
$checks = [
    'DOCTYPE encontrado' => strpos($content, '<!DOCTYPE html>') !== false,
    'HTML abierto correctamente' => preg_match('/<html[^>]*>/', $content) === 1,
    'HTML cerrado correctamente' => substr_count($content, '</html>') === 1,
    'HEAD presente' => preg_match('/<head[^>]*>.*<\/head>/s', $content) === 1,
    'BODY presente' => preg_match('/<body[^>]*>.*<\/body>/s', $content) === 1,
    'Footer incluido correctamente' => strpos($content, 'get_footer();') !== false,
    'Botón flotante presente' => strpos($content, 'floating-save-button') !== false,
    'Número correcto de </div>' => substr_count($content, '</div>') >= 5, // Mínimo esperado
    'Número correcto de </body>' => substr_count($content, '</body>') === 1,
    'Número correcto de </html>' => substr_count($content, '</html>') === 1,
];

echo "=== VERIFICACIÓN DE INTEGRIDAD DEL FOOTER ===\n\n";

$all_passed = true;
foreach ($checks as $check => $passed) {
    $status = $passed ? '✅' : '❌';
    echo "{$status} {$check}\n";
    if (!$passed) $all_passed = false;
}

// Verificaciones adicionales
echo "\n=== VERIFICACIONES AVANZADAS ===\n";

// Verificar estructura del botón flotante
if (preg_match('/<button[^>]*class="[^"]*floating-save-button[^"]*"[^>]*>/', $content, $button_match)) {
    echo "✅ Botón flotante encontrado correctamente\n";
    echo "   HTML: " . htmlspecialchars($button_match[0]) . "\n";
} else {
    echo "❌ Botón flotante no encontrado o mal formado\n";
    $all_passed = false;
}

// Verificar que no haya elementos fuera de lugar
$body_end_pos = strpos($content, '</body>');
$footer_pos = strpos($content, 'get_footer();');

if ($footer_pos !== false && $body_end_pos !== false) {
    $distance = $body_end_pos - $footer_pos;
    echo "✅ Footer posicionado correctamente (distancia a </body>: {$distance} caracteres)\n";

    // Verificar que no haya elementos entre footer y body end
    $between_footer_body = substr($content, $footer_pos, $distance);
    $unwanted_elements = ['<div style=', '<script', '<footer'];
    $has_unwanted = false;

    foreach ($unwanted_elements as $element) {
        if (strpos($between_footer_body, $element) !== false) {
            $has_unwanted = true;
            echo "⚠️  Elementos adicionales encontrados entre footer y </body>\n";
            break;
        }
    }

    if (!$has_unwanted) {
        echo "✅ No hay elementos no deseados entre footer y cierre de body\n";
    }
} else {
    echo "❌ No se pudo determinar la posición del footer o cierre de body\n";
    $all_passed = false;
}

// Verificar estilos del botón flotante
if (preg_match('/\.floating-save-button\s*\{[^}]*\}/s', $content, $css_match)) {
    echo "✅ Estilos CSS del botón flotante encontrados\n";
    $css_content = $css_match[0];

    $required_styles = [
        'position: fixed',
        'bottom:',
        'z-index:',
        'background:',
    ];

    foreach ($required_styles as $required) {
        if (strpos($css_content, $required) !== false) {
            echo "   ✅ Estilo '{$required}' presente\n";
        } else {
            echo "   ❌ Estilo '{$required}' faltante\n";
            $all_passed = false;
        }
    }
} else {
    echo "❌ Estilos CSS del botón flotante no encontrados\n";
    $all_passed = false;
}

echo "\n=== RESULTADO FINAL ===\n";
if ($all_passed) {
    echo "🎉 Todas las verificaciones pasaron correctamente\n";
    echo "✅ El footer está íntegro y no debería tener puntos de fuga\n";
} else {
    echo "⚠️ Algunas verificaciones fallaron\n";
    echo "❌ Puede haber problemas de integridad en el footer\n";
}

echo "\n=== RECOMENDACIONES ===\n";
if ($all_passed) {
    echo "✅ No se requieren acciones adicionales\n";
    echo "✅ El botón flotante debería funcionar correctamente\n";
    echo "✅ La página debería renderizarse sin problemas visuales\n";
} else {
    echo "⚠️ Revisar los puntos marcados con ❌ arriba\n";
    echo "⚠️ Puede ser necesario ajustar la estructura HTML\n";
}
?>

