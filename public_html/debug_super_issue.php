<?php
require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/_super_landing_functions.php';

$codigo_id = '5c491a352f55c844162d7ae2';
$db = createConnection();
$codigos_coll = $db->selectCollection('codigos');

try {
    $codigo_obj_id = new MongoDB\BSON\ObjectId($codigo_id);
} catch (Exception $e) {
    $codigo_obj_id = $codigo_id;
}

$codigo = $codigos_coll->findOne(['_id' => $codigo_obj_id]);

if (!$codigo) {
    echo "Código no encontrado\n";
    exit;
}

echo "Código Info:\n";
print_r(iterator_to_array($codigo));

$marca_slug = $codigo['marca'] ?? '';
echo "\nMarca Slug: $marca_slug\n";

$has_super_landing = false;
if (function_exists('get_active_super_landings')) {
    $all_landings = get_active_super_landings(100);
    echo "\nActive Super Landings:\n";
    foreach ($all_landings as $landing) {
        $slugs = [];
        if (isset($landing['linked_brand_slugs'])) {
             $slugs = is_object($landing['linked_brand_slugs']) ? iterator_to_array($landing['linked_brand_slugs']) : $landing['linked_brand_slugs'];
        }
        echo "- " . $landing['title'] . " (Slugs: " . implode(', ', $slugs) . ")\n";
        if (in_array($marca_slug, $slugs)) {
            $has_super_landing = true;
            echo "  Match found!\n";
        }
    }
}

echo "\nHas Super Landing: " . ($has_super_landing ? 'Yes' : 'No') . "\n";
