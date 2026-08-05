<?php
// Debug script to check N26 FAQ rendering
require_once __DIR__ . '/../myphp/funciones_modern.php';
include_once __DIR__ . '/../myphp/funciones_faq_frontend.php';
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';

$marca = 'n26';
$marca_info = get_brand_info($marca);
$nombre_marca = $marca_info['nombre'] ?? ucfirst($marca);

echo "<h1>Debug N26 FAQs</h1>";
echo "<h2>Marca Info:</h2>";
echo "<pre>" . print_r($marca_info, true) . "</pre>";

echo "<h2>SEO FAQ from DB:</h2>";
$seo_faq = $marca_info['seo_faq'] ?? '';
echo "<textarea style='width:100%;height:100px'>" . htmlspecialchars($seo_faq) . "</textarea>";

// Simulate the marca_detalle.php logic
$seo_faq_extra = '';
$override_faq_extra = '';

// Check if AI content exists
if (file_exists(__DIR__ . '/../myphp/ai_content_data.php')) {
    include_once __DIR__ . '/../myphp/ai_content_data.php';
    if (function_exists('get_ai_brand_content')) {
        $ai_content = get_ai_brand_content($marca);
        if ($ai_content && isset($ai_content['faqs']) && is_array($ai_content['faqs'])) {
            $ai_faq_str = "";
            foreach ($ai_content['faqs'] as $faq) {
                $ai_faq_str .= $faq['question'] . '|' . $faq['answer'] . "\n";
            }
            $override_faq_extra = $ai_faq_str;
        }
    }
}

// Set seo_faq_extra
if (isset($override_faq_extra) && !empty($override_faq_extra)) {
    $seo_faq_extra = $override_faq_extra;
} else {
    $seo_faq_extra = "
¿Caducan los cupones de $nombre_marca?|Sí, las ofertas tienen tiempo limitado. Te recomendamos usarlos cuanto antes.
¿Funcionan para todos los usuarios?|La mayoría sirven para nuevos registros, aunque a veces hay para antiguos clientes.
";
}

echo "<h2>SEO FAQ Extra (Generic):</h2>";
echo "<textarea style='width:100%;height:100px'>" . htmlspecialchars($seo_faq_extra) . "</textarea>";

// Combine
$seo_faq = $seo_faq_extra . ($seo_faq ?? '');

echo "<h2>Combined SEO FAQ:</h2>";
echo "<textarea style='width:100%;height:150px'>" . htmlspecialchars($seo_faq) . "</textarea>";

// Load dynamic FAQs
$faq_lines_to_display = [];

if (function_exists('getFAQsByMarca')) {
    $dynamic_faqs = getFAQsByMarca($marca, true);
    echo "<h2>Dynamic FAQs from DB:</h2>";
    echo "<pre>" . print_r($dynamic_faqs, true) . "</pre>";
    foreach ($dynamic_faqs as $df) {
        $faq_lines_to_display[] = [
            'q' => $df['titulo'] ?? $df['pregunta'],
            'a' => $df['respuesta']
        ];
    }
}

// Parse seo_faq
if (!empty($seo_faq)) {
    $lines = preg_split('/\r\n|\r|\n/', $seo_faq);
    foreach ($lines as $line) {
        if (strpos($line, '|') !== false) {
            list($q, $a) = array_map('trim', explode('|', $line, 2));
            if ($q && $a) $faq_lines_to_display[] = ['q' => $q, 'a' => $a];
        }
    }
}

echo "<h2>Final FAQ Lines to Display:</h2>";
echo "<pre>" . print_r($faq_lines_to_display, true) . "</pre>";

echo "<h2>Rendered HTML:</h2>";
foreach($faq_lines_to_display as $faq) {
    echo '<h3 style="font-size: 1.2rem; color: #E30613; margin-top: 25px; margin-bottom: 10px;">' . htmlspecialchars($faq['q']) . '</h3>';
    echo '<p>' . htmlspecialchars($faq['a']) . '</p>';
}
?>
