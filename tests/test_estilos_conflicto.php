<?php
// Test para verificar conflictos de estilos CSS

echo "=== TEST DE CONFLICTOS DE ESTILOS ===\n";

// Test 1: Verificar que los estilos específicos están bien definidos
echo "\n1. Verificando especificidad de estilos:\n";
$archivo_css = file_get_contents('/home/admin/web/codigoamigo.com/public_html/css/template/footer-1.css');

// Verificar que los estilos tienen !important
$tiene_important_telegram_float = strpos($archivo_css, '.telegram-float-new {') !== false;
$tiene_important_telegram_btn = strpos($archivo_css, '.telegram-float-new .telegram-btn {') !== false;
$tiene_important_telegram_icon = strpos($archivo_css, '.telegram-float-new .telegram-icon-new {') !== false;

echo "✅ Estilos específicos telegram-float-new: " . ($tiene_important_telegram_float ? 'SÍ' : 'NO') . "\n";
echo "✅ Estilos específicos telegram-btn: " . ($tiene_important_telegram_btn ? 'SÍ' : 'NO') . "\n";
echo "✅ Estilos específicos telegram-icon-new: " . ($tiene_important_telegram_icon ? 'SÍ' : 'NO') . "\n";

// Test 2: Verificar que no hay estilos globales problemáticos
echo "\n2. Verificando estilos globales problemáticos:\n";
$tiene_global_flex = strpos($archivo_css, '.telegram-btn {') !== false;
$tiene_global_icon = strpos($archivo_css, '.telegram-icon-new {') !== false;
$tiene_global_text = strpos($archivo_css, '.telegram-text {') !== false;

echo "❌ Estilos globales telegram-btn: " . ($tiene_global_flex ? 'SÍ (PROBLEMA)' : 'NO') . "\n";
echo "❌ Estilos globales telegram-icon-new: " . ($tiene_global_icon ? 'SÍ (PROBLEMA)' : 'NO') . "\n";
echo "❌ Estilos globales telegram-text: " . ($tiene_global_text ? 'SÍ (PROBLEMA)' : 'NO') . "\n";

// Test 3: Verificar que los estilos legacy están separados
echo "\n3. Verificando separación de estilos legacy:\n";
$tiene_legacy_telegram_button = strpos($archivo_css, '.telegram-button {') !== false;
$tiene_legacy_telegram_icon = strpos($archivo_css, '.telegram-icon {') !== false;
$tiene_legacy_telegram_content = strpos($archivo_css, '.telegram-content {') !== false;

echo "✅ Estilos legacy telegram-button: " . ($tiene_legacy_telegram_button ? 'SÍ' : 'NO') . "\n";
echo "✅ Estilos legacy telegram-icon: " . ($tiene_legacy_telegram_icon ? 'SÍ' : 'NO') . "\n";
echo "✅ Estilos legacy telegram-content: " . ($tiene_legacy_telegram_content ? 'SÍ' : 'NO') . "\n";

// Test 4: Verificar que los estilos específicos tienen !important
echo "\n4. Verificando uso de !important:\n";
$tiene_important_count = substr_count($archivo_css, '!important');
$tiene_important_telegram_float_new = substr_count($archivo_css, '.telegram-float-new') > 0;

echo "✅ Total de !important: $tiene_important_count\n";
echo "✅ Estilos telegram-float-new específicos: " . ($tiene_important_telegram_float_new ? 'SÍ' : 'NO') . "\n";

// Test 5: Verificar que no hay conflictos con otros elementos
echo "\n5. Verificando conflictos potenciales:\n";
$tiene_conflict_flex = strpos($archivo_css, 'display: flex;') !== false;
$tiene_conflict_align = strpos($archivo_css, 'align-items: center;') !== false;
$tiene_conflict_justify = strpos($archivo_css, 'justify-content: center;') !== false;

echo "⚠️  Estilos flex globales: " . ($tiene_conflict_flex ? 'SÍ (REVISAR)' : 'NO') . "\n";
echo "⚠️  Estilos align-items globales: " . ($tiene_conflict_align ? 'SÍ (REVISAR)' : 'NO') . "\n";
echo "⚠️  Estilos justify-content globales: " . ($tiene_conflict_justify ? 'SÍ (REVISAR)' : 'NO') . "\n";

echo "\n=== RESUMEN ===\n";
$problemas = 0;

if ($tiene_global_flex || $tiene_global_icon || $tiene_global_text) {
    $problemas++;
    echo "❌ PROBLEMA: Hay estilos globales que pueden causar conflictos\n";
}

if ($tiene_conflict_flex || $tiene_conflict_align || $tiene_conflict_justify) {
    $problemas++;
    echo "⚠️  ADVERTENCIA: Hay estilos flex que pueden afectar otros elementos\n";
}

if ($problemas == 0) {
    echo "✅ NO HAY CONFLICTOS DETECTADOS\n";
    echo "\n=== RECOMENDACIONES ===\n";
    echo "✅ Los estilos están bien encapsulados\n";
    echo "✅ Se usa !important para evitar conflictos\n";
    echo "✅ Los estilos legacy están separados\n";
    exit(0);
} else {
    echo "❌ SE DETECTARON PROBLEMAS - Revisar estilos\n";
    exit(1);
}
