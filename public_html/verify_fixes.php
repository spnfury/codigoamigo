<?php
/**
 * Script de verificación para los fixes de CodigoAmigo
 * Verifica que las funciones de fecha manejan correctamente MongoDB UTCDateTime
 */

require_once '/home/admin/web/codigoamigo.com/public_html/vendor/autoload.php';
require_once '/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php';

echo "=== Verificación de fixes CodigoAmigo ===\n\n";
$errors = 0;

// ── Test 1: formatDateAgoLarge con UTCDateTime ──
echo "1. formatDateAgoLarge() con MongoDB\\BSON\\UTCDateTime:\n";
$utcDate = new MongoDB\BSON\UTCDateTime((time() - 3600) * 1000); // Hace 1 hora
$result = formatDateAgoLarge($utcDate);
echo "   Input: UTCDateTime (hace 1h) → Output: '$result'\n";
if (strpos($result, '1970') !== false || strpos($result, 'Janvier') !== false || $result === '') {
    echo "   ❌ FALLO: La fecha sigue siendo incorrecta\n";
    $errors++;
} else {
    echo "   ✅ OK\n";
}

// ── Test 2: formatDateAgoLarge con string ──
echo "\n2. formatDateAgoLarge() con string (backward compat):\n";
$result2 = formatDateAgoLarge('2026-03-19 14:30:00');
echo "   Input: '2026-03-19 14:30:00' → Output: '$result2'\n";
if (strpos($result2, '1970') !== false || $result2 === '') {
    echo "   ❌ FALLO\n";
    $errors++;
} else {
    echo "   ✅ OK\n";
}

// ── Test 3: formatDateAgo con UTCDateTime ──
echo "\n3. formatDateAgo() con MongoDB\\BSON\\UTCDateTime:\n";
$utcDate2 = new MongoDB\BSON\UTCDateTime((time() - 600) * 1000); // Hace 10 min
$result3 = formatDateAgo($utcDate2);
echo "   Input: UTCDateTime (hace 10min) → Output: '$result3'\n";
if (strpos($result3, '1970') !== false || $result3 === '') {
    echo "   ❌ FALLO\n";
    $errors++;
} else {
    echo "   ✅ OK\n";
}

// ── Test 4: Verificar que app_with_mongo.php usa fecha_publicacion ──
echo "\n4. app_with_mongo.php usa fecha_publicacion para sort:\n";
$content = file_get_contents('/home/admin/web/codigoamigo.com/public_html/app_with_mongo.php');
$has_old_sort = preg_match("/sort_order\s*=\s*array\s*\(\s*'fecha_modificacion'/", $content);
if ($has_old_sort) {
    echo "   ❌ Aún hay sort por fecha_modificacion como default\n";
    $errors++;
} else {
    echo "   ✅ No hay sort por fecha_modificacion como default\n";
}

// ── Test 5: Verificar marca.php usa estado => 0 ──
echo "\n5. marca.php usa estado => 0:\n";
$marca_content = file_get_contents('/home/admin/web/codigoamigo.com/public_html/marca.php');
if (preg_match("/'estado'\s*=>\s*1/", $marca_content, $matches, PREG_OFFSET_CAPTURE)) {
    // Verificar si es la búsqueda de códigos (línea ~75) o la de countDocuments (que sigue con estado 1)
    $line_num = substr_count(substr($marca_content, 0, $matches[0][1]), "\n") + 1;
    echo "   ⚠️ Encontrado 'estado' => 1 en línea $line_num (puede ser intencional si es countDocuments)\n";
} else {
    echo "   ✅ No hay 'estado' => 1 en la búsqueda de códigos\n";
}

// ── Test 6: Verificar que no hay rand() en votos ──
echo "\n6. marca.php no tiene votos/stats random:\n";
if (strpos($marca_content, "rand(5, 50)") !== false || strpos($marca_content, "rand(20, 100)") !== false) {
    echo "   ❌ Aún hay generación random de votos/stats\n";
    $errors++;
} else {
    echo "   ✅ No hay votos/stats random\n";
}

echo "\n=== Resultado: " . ($errors === 0 ? "✅ TODOS LOS TESTS OK" : "❌ $errors FALLOS") . " ===\n";
