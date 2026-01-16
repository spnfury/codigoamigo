<?php
/**
 * Test script para verificar procesamiento de enlaces de Amazon
 */

require_once __DIR__ . '/../public_html/myphp/funciones_chollos_amazon.php';

$test_cases = [
    [
        'name' => 'Direct Amazon link with ASIN in /dp/',
        'url' => 'https://www.amazon.es/dp/B08DR42HFV',
        'expected_asin' => 'B08DR42HFV',
        'should_have_tag' => true
    ],
    [
        'name' => 'Amazon link with ASIN in path segment',
        'url' => 'https://www.amazon.es/Silla-Oficina-Ergon%C3%B3mica/B0ABCDEFGH/?tag=other-21',
        'expected_asin' => 'B0ABCDEFGH',
        'should_have_tag' => true
    ],
    [
        'name' => 'Amazon search link (should not extract ASIN)',
        'url' => 'https://www.amazon.es/s?k=XIAOMI+15T',
        'expected_asin' => null,
        'should_have_tag' => true
    ],
    [
        'name' => 'Amazon /gp/product/ format',
        'url' => 'https://www.amazon.es/gp/product/B0CDE12345',
        'expected_asin' => 'B0CDE12345',
        'should_have_tag' => true
    ]
];

echo "========================================\n";
echo "Amazon Link Processing Tests\n";
echo "========================================\n\n";

$passed = 0;
$failed = 0;

foreach ($test_cases as $test) {
    echo "Test: {$test['name']}\n";
    echo "URL: {$test['url']}\n";
    
    $asin = extraerASIN($test['url']);
    $converted = convertirEnlaceAmazonConPAAPI($test['url']);
    
    echo "Extracted ASIN: " . ($asin ?? 'NULL') . "\n";
    echo "Converted URL: $converted\n";
    
    $has_tag = strpos($converted, 'tag=spnfuryy-21') !== false;
    echo "Has Tag: " . ($has_tag ? 'YES' : 'NO') . "\n";
    
    $asin_match = ($asin == $test['expected_asin']);
    $tag_match = ($has_tag == $test['should_have_tag']);
    
    // Verificar que NO sea un link de búsqueda si esperamos ASIN
    $is_search = (strpos($converted, '/s?k=') !== false);
    $search_ok = true;
    if ($test['expected_asin'] !== null) {
        $search_ok = !$is_search; // NO debe ser búsqueda si esperamos ASIN
        if ($is_search) {
            echo "ERROR: Converted to search link when direct product link was expected!\n";
        }
    }
    
    $status = ($asin_match && $tag_match && $search_ok) ? '✅ PASS' : '❌ FAIL';
    echo "Status: $status\n";
    
    if ($status === '✅ PASS') {
        $passed++;
    } else {
        $failed++;
        echo "  Failed checks:\n";
        if (!$asin_match) echo "  - ASIN mismatch: expected {$test['expected_asin']}, got $asin\n";
        if (!$tag_match) echo "  - Tag check failed\n";
        if (!$search_ok) echo "  - Incorrectly converted to search link\n";
    }
    
    echo str_repeat('-', 60) . "\n\n";
}

echo "========================================\n";
echo "Results: $passed passed, $failed failed\n";
echo "========================================\n";

exit($failed > 0 ? 1 : 0);
