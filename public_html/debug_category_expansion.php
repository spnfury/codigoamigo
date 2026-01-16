<?php
// Simular entorno
include_once __DIR__ . '/inc/includes.php'; 
include_once __DIR__ . '/myphp/funciones_chollos_helpers.php';
include_once __DIR__ . '/myphp/funciones_chollos.php';

$categoria_slug = 'libros';
$categoria_final = 'Libros';

echo "=== Debug Expansion Categoría: $categoria_final ===\n";

// 1. Variaciones
$variaciones = obtenerVariacionesCategoria($categoria_final);
echo "Variaciones (Keywords):\n";
print_r($variaciones);

// 2. Subcategorías
$subcategorias = obtenerSubcategorias($categoria_final);
echo "\nSubcategorías:\n";
print_r($subcategorias);

// 3. Regex Generada (Lógica de app_with_mongo.php)
$regex_flexible = null;
if (!empty($categoria_slug)) {
    $base = str_replace('-', ' ', $categoria_slug);
    $patron = '';
    for ($i = 0; $i < mb_strlen($base); $i++) {
        $char = mb_substr($base, $i, 1);
        switch ($char) {
            case 'a': $patron .= '[aáAÁ]'; break;
            case 'e': $patron .= '[eéEÉ]'; break;
            case 'i': $patron .= '[iíIÍ]'; break;
            case 'o': $patron .= '[oóOÓ]'; break;
            case 'u': $patron .= '[uúüUÚÜ]'; break;
            case 'n': $patron .= '[nñNÑ]'; break;
            case ' ': $patron .= '[ -]'; break;
            default: $patron .= preg_quote($char);
        }
    }
    echo "\nRegex Pattern: ^" . $patron . "$\n";
}

// 4. Array Final
$categorias_strings = array_unique(array_filter(array_merge(
    [$categoria_final], 
    [$categoria_slug],  
    [str_replace('-', ' ', $categoria_slug)],
    // $subcategorias, // DESACTIVADO: Fuente de contaminación (trae categorías hermanas por co-ocurrencia)
    $variaciones
)));

echo "\nArray Final de Búsqueda:\n";
print_r(array_values($categorias_strings));

/*
// 5. Simular query a backend (opcional, si queremos ver qué devuelve)
$filtros = [
    'categoria' => array_values($categorias_strings),
    'estado' => 1,
    'limite' => 5
];
$chollos = obtenerChollos($filtros);
echo "\nChollos encontrados (Top 5):\n";
foreach ($chollos as $c) {
    echo "- " . $c['titulo'] . " (Cats: " . implode(', ', (array)$c['categoria']) . ")\n";
}
*/
?>
