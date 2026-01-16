<?php
// Script de diagnóstico para sitemap
require_once __DIR__ . '/myphp/funciones_chollos.php';
require_once __DIR__ . '/myphp/funciones_chollos_helpers.php';

echo "Conectando a MongoDB...\n";
$collection = getCollectionChollos();

if (!$collection) {
    die("Error: No se pudo conectar a la colección 'chollos'\n");
}

$count = $collection->countDocuments(['estado' => 1]);
echo "Total chollos activos: $count\n";

echo "Analizando primeros 5 chollos...\n";
$chollos = $collection->find(['estado' => 1], ['limit' => 5, 'projection' => ['categoria' => 1, 'titulo' => 1]]);

foreach ($chollos as $chollo) {
    echo "ID: " . $chollo['_id'] . "\n";
    echo "Título: " . ($chollo['titulo'] ?? 'Sin título') . "\n";
    echo "Categoría Type: " . gettype($chollo['categoria']) . "\n";
    print_r($chollo['categoria']);
    echo "-------------------\n";
}

echo "\nProbando lógica de sitemap...\n";
$chollos_all = $collection->find(['estado' => 1], ['limit' => 50, 'projection' => ['categoria' => 1]]);
$rutas_generadas = 0;

foreach ($chollos_all as $chollo) {
    if (isset($chollo['categoria'])) {
        $cats = $chollo['categoria'];
        // Normalizar a array si es string
        if (!is_array($cats)) {
            $cats = [$cats];
        }
        
        $ruta_actual = '';
        foreach ($cats as $cat) {
            $cat_slug = categoriaToSlug($cat);
            $ruta_actual .= ($ruta_actual ? '/' : '') . $cat_slug;
            echo "Ruta generada: $ruta_actual\n";
            $rutas_generadas++;
        }
    }
}

echo "Total rutas generadas en prueba (50 docs): $rutas_generadas\n";
