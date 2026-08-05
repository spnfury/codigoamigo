<?php
require_once __DIR__ . '/public_html/myphp/funciones_chollos_helpers.php';

function test_breadcrumbs($title, $categories) {
    echo "Test: $title\n";
    echo "Input: " . implode(", ", $categories) . "\n";
    $result = obtenerBreadcrumbsCategorias($categories);
    foreach ($result as $bc) {
        echo " - " . $bc['nombre'] . " -> " . $bc['path'] . "\n";
    }
    echo "---\n";
}

test_breadcrumbs("Single level", ["Videojuegos", "PS5"]);
test_breadcrumbs("Two top levels (Problematic Case)", ["Electronica", "Zapatos", "Deportes"]);
test_breadcrumbs("Hierarchical with reset", ["Moda", "Zapatos", "Electronica", "Portatiles"]);
test_breadcrumbs("Mixed with Amazon (should be filtered out)", ["Videojuegos", "PS4", "Amazon"]);
test_breadcrumbs("Deep hierarchy (if map supports it)", ["Videojuegos", "Accesorios Gaming", "Mandos"]); // Accesorios Gaming is child of Videojuegos
test_breadcrumbs("Unordered top levels", ["Deportes", "Videojuegos"]);
