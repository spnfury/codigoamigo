<?php
require_once __DIR__ . '/public_html/myphp/funciones_chollos.php';
require_once __DIR__ . '/public_html/myphp/funciones_chollos_helpers.php';

$collection = getCollectionChollos();
if (!$collection) {
    die("No collection found\n");
}

$cursor = $collection->find(['estado' => 1]);
$count_wrong = 0;
$mapa = obtenerMapaCategorias();
$top_level_cats = array_keys($mapa);
$top_level_cats[] = 'General';
$top_level_cats_lower = array_map('strtolower', $top_level_cats);

echo "Checking chollos categories...\n";

foreach ($cursor as $doc) {
    $categories = $doc['categoria'] ?? [];
    if (is_object($categories)) {
        if (method_exists($categories, 'getArrayCopy')) {
            $categories = $categories->getArrayCopy();
        } else {
            $categories = (array)$categories;
        }
    }
    
    if (!is_array($categories)) {
        continue;
    }
    
    if (count($categories) > 1) {
        // Check if multiple top-level categories are present
        $top_level_present = [];
        foreach ($categories as $cat) {
            $cat_lower = strtolower($cat);
            if (in_array($cat_lower, $top_level_cats_lower)) {
                $top_level_present[] = $cat;
            }
        }
        
        if (count($top_level_present) > 1) {
            $count_wrong++;
            echo "ID: " . $doc['_id'] . " - Title: " . $doc['titulo'] . "\n";
            echo "Categories: " . implode(", ", $categories) . "\n";
            echo "Multiple top-level: " . implode(", ", $top_level_present) . "\n";
            echo "---\n";
        }
    }
}

echo "Total chollos with potential multiple top-level categories: $count_wrong\n";
