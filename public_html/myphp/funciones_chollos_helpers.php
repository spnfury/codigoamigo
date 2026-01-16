<?php

/**
 * Convierte un texto a formato slug SEO-friendly
 * Ejemplo: "Juegos Para PS5" -> "juegos-para-ps5"
 */
function categoriaToSlug($categoria) {
    // Si es un objeto (como BSONArray), intentar convertir a array
    if (is_object($categoria) && method_exists($categoria, 'getArrayCopy')) {
        $categoria = $categoria->getArrayCopy();
    }
    
    // Si es array, tomar el primer elemento (o iterar si fuera necesario, pero slug es único)
    if (is_array($categoria)) {
        $found = '';
        foreach ($categoria as $cat) {
            if (!empty($cat) && is_string($cat)) {
                $found = $cat;
                break;
            }
        }
        $categoria = $found ? $found : (isset($categoria[0]) ? $categoria[0] : '');
    }
    
    $input = (string)$categoria;
    $slug = '';

    // 1. Intentar con Transliterator (Mejor opción para i18n)
    if (class_exists('Transliterator')) {
        $trans = Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
        if ($trans) {
            $slug = $trans->transliterate($input);
            // Limpieza estándar
            $slug = str_replace(' ', '-', $slug);
            $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            return trim($slug, '-');
        }
    }

    // 2. Intentar con iconv (Muy robusto para latín)
    if (function_exists('iconv')) {
        // Aseguramos que la entrada se trate como UTF-8
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $input);
        if ($converted !== false) {
            $slug = strtolower($converted);
            $slug = str_replace(' ', '-', $slug);
            $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            
            // Si el resultado es razonable (no vacío si el input no lo era), devolver
            if (!empty($slug) || empty($input)) {
                return $slug;
            }
        }
    }
    
    // 3. Fallback manual (para hostings limitados o problemas de encoding específicos)
    $unwanted_array = array(
        'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
        'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
        'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
    );
    $slug = strtr($input, $unwanted_array);
    
    // Convertir a minúsculas
    $slug = mb_strtolower($slug, 'UTF-8');
    
    // Reemplazar espacios por guiones
    $slug = str_replace(' ', '-', $slug);
    
    // Eliminar caracteres especiales excepto guiones y letras/números
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    
    // Eliminar guiones múltiples
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Eliminar guiones al inicio y final
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Convierte un slug a formato de categoría legible
 * Ejemplo: "juegos-para-ps5" -> "Juegos Para Ps5"
 */
function slugToCategoria($slug) {
    // Reemplazar guiones por espacios
    $categoria = str_replace('-', ' ', $slug);
    
    // Capitalizar cada palabra
    $categoria = ucwords($categoria);
    
    return $categoria;
}

/**
 * Intenta encontrar el nombre REAL de una categoría en la BD a partir de su slug
 * Esto resuelve el problema de tildes: "auriculares-inalambricos" -> "Auriculares Inalámbricos"
 */
function obtenerCategoriaReal($slug) {
    if (empty($slug)) return '';
    
    // 1. Intentar conversión básica
    $cat_basic = slugToCategoria($slug);
    
    // 2. Buscar en MongoDB usando regex que tolere acentos
    // Construir patrón regex "inteligente"
    $base = str_replace('-', ' ', $slug);
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
            case ' ': $patron .= '[ -]'; break; // Espacio o guión
            default: $patron .= preg_quote($char);
        }
    }
    
    if (!function_exists('getCollectionChollos')) {
        include_once __DIR__ . '/funciones_chollos.php';
    }
    
    try {
        $collection = getCollectionChollos();
        if (!$collection) return $cat_basic;

        // Buscar una categoría que coincida con el patrón
        // Usamos findOne para rapidez
        $doc = $collection->findOne(
            ['categoria' => ['$regex' => '^' . $patron . '$', '$options' => 'i']],
            ['projection' => ['categoria' => 1]]
        );
        
        if ($doc && isset($doc['categoria'])) {
            $raw_cat = $doc['categoria'];
            $cats = [];
            
            // Convertir BSONArray o similar a array de PHP
            if (is_object($raw_cat) && method_exists($raw_cat, 'getArrayCopy')) {
                $cats = $raw_cat->getArrayCopy();
            } else if (is_array($raw_cat)) {
                $cats = $raw_cat;
            } else {
                $cats = [$raw_cat];
            }
            
            // Buscar cuál de las categorías del documento coincide con el slug
            foreach ($cats as $cat) {
                if (!empty($cat) && is_string($cat) && categoriaToSlug($cat) === $slug) {
                    return $cat;
                }
            }
            
            // Fallback: match laxo con regex sobre elementos de tipo string
             foreach ($cats as $cat) {
                 if (!empty($cat) && is_string($cat) && preg_match('/^' . $patron . '$/iu', $cat)) {
                     return $cat;
                 }
            }
        }
    } catch (Exception $e) {
        // Silenciar error y devolver fallback
    }
    
    return $cat_basic;
}

/**
 * Construye una ruta de categoría SEO-friendly
 * Ejemplo: ["Videojuegos", "Juegos De Rol"] -> "videojuegos/juegos-de-rol"
 */
function buildCategoriaPath($categorias) {
    if (!is_array($categorias)) {
        $categorias = [$categorias];
    }
    
    $slugs = array_map('categoriaToSlug', $categorias);
    return implode('/', $slugs);
}

/**
 * Parsea una ruta de categoría y devuelve el array de categorías
 * Ejemplo: "videojuegos/juegos-de-rol/ps5" -> ["Videojuegos", "Juegos De Rol", "Ps5"]
 */
function parseCategoriaPath($path) {
    $slugs = explode('/', trim($path, '/'));
    // Usar obtenerCategoriaReal para cada componente sería ideal pero costoso
    // Por ahora usamos slugToCategoria básico y se confiará en la búsqueda final
    return array_map('slugToCategoria', $slugs);
}

/**
 * Mapa explícito de categorías padre -> hijos
 */
function obtenerMapaCategorias() {
    return [
        'Videojuegos' => ['PS5', 'PS4', 'Xbox', 'Nintendo Switch', 'PC Gaming', 'Accesorios Gaming', 'Juegos Digitales'],
        'Electronica' => ['Móviles', 'Portátiles', 'Tablets', 'Televisores', 'Audio', 'Fotografía', 'Smartwatch', 'Componentes PC'],
        'Hogar' => ['Cocina', 'Muebles', 'Decoración', 'Jardín', 'Bricolaje', 'Iluminación', 'Domótica', 'Limpieza', 'Mascotas'],
        'Moda' => ['Ropa Mujer', 'Ropa Hombre', 'Zapatos', 'Accesorios', 'Ropa Deportiva', 'Joyas', 'Bolsos'],
        'Deportes' => ['Fútbol', 'Fitness', 'Ciclismo', 'Running', 'Padel', 'Montaña', 'Natación'],
        'Libros' => ['Novela', 'Cómic', 'Manga', 'Libros de Texto', 'Ensayo', 'Infantil', 'Ebooks', 'Kindle'],
        'Salud y Belleza' => ['Maquillaje', 'Cuidado Personal', 'Perfumes', 'Parafarmacia', 'Peluquería'],
        'Juguetes' => ['LEGO', 'Juegos de Mesa', 'Muñecas', 'Puzzles', 'Juguetes Educativos']
    ];
}

/**
 * Obtiene variaciones de búsqueda para una categoría (keywords y subcategorías)
 */
function obtenerVariacionesCategoria($categoria) {
    if (empty($categoria)) return [];
    
    // Normalizar
    $cat_norm = mb_strtolower($categoria, 'UTF-8');
    
    // Lista de keywords por categoría (extraída y unificada)
    $keywords = [
        'videojuegos' => ['videojuegos', 'videojuego', 'playstation', 'ps5', 'ps4', 'xbox', 'nintendo', 'switch', 'gaming', 'gamer', 'consola', 'steam', 'juegos'],
        'electronica' => ['electronica', 'tecnologia', 'smartphone', 'movil', 'portatil', 'laptop', 'ordenador', 'pc', 'tablet', 'tv', 'televisor', 'audio', 'auriculares', 'altavoz'],
        'hogar' => ['hogar', 'casa', 'mueble', 'cocina', 'jardin', 'bricolaje', 'herramienta', 'decoracion', 'electrodomestico', 'lavadora', 'nevera'],
        'moda' => ['moda', 'ropa', 'zapato', 'zapatilla', 'camiseta', 'pantalon', 'vestido', 'abrigo', 'chaqueta', 'bolso', 'reloj', 'gafas'],
        'deportes' => ['deporte', 'deportes', 'futbol', 'baloncesto', 'tenis', 'padel', 'gym', 'fitness', 'running', 'correr', 'bicicleta', 'ciclismo'],
        'libros' => ['libro', 'libros', 'ebook', 'kindle', 'lectura', 'novela', 'comic', 'manga', 'cuento', 'literatura']
    ];
    
    // 1. Buscar en keywords directas
    foreach ($keywords as $key => $values) {
        if (strpos($cat_norm, $key) !== false || $key === $cat_norm) {
            return $values;
        }
    }
    
    // 2. Buscar en el mapa jerárquico
    $mapa = obtenerMapaCategorias();
    foreach ($mapa as $padre => $hijos) {
        if (mb_strtolower($padre, 'UTF-8') === $cat_norm) {
            // Devolver hijos y el padre mismo
            $result = array_merge([$padre], $hijos);
            // Convertir a slugs o minúsculas para búsqueda relajada
            return array_map(function($c) { return mb_strtolower($c, 'UTF-8'); }, $result);
        }
    }
    
    return [$cat_norm];
}

/**
 * Obtiene subcategorías únicas que empiezan con una categoría padre
 * Mejorada para usar el mapa explícito y búsqueda en BD
 */
function obtenerSubcategorias($categoria_padre = null) {
    // Incluir función de conexión si no existe
    if (!function_exists('getCollectionChollos')) {
        include_once __DIR__ . '/funciones_chollos.php';
    }
    
    $subcategorias_bd = [];
    $subcategorias_mapa = [];
    
    // 1. Obtener del mapa explícito
    if ($categoria_padre) {
        $mapa = obtenerMapaCategorias();
        $padre_norm = mb_strtolower($categoria_padre, 'UTF-8');
        
        foreach ($mapa as $padre => $hijos) {
            // Coincidencia relajada (ej: "videojuegos" coincide con "Videojuegos")
            if (mb_strtolower($padre, 'UTF-8') === $padre_norm || strpos($padre_norm, mb_strtolower($padre, 'UTF-8')) !== false) {
                $subcategorias_mapa = $hijos;
                break;
            }
        }
    }
    
    // 2. Obtener de la BD (categorías que contienen el padre o empiezan por él)
    $collection = getCollectionChollos();
    if ($collection) {
        try {
            // Pipeline optimizado: solo necesitamos categorías únicas
            // Filtrar documentos que POTENCIALMENTE tengan relación con la categoría padre si se proporciona
            $match = ['estado' => 1];
            if ($categoria_padre) {
                $match['$or'] = [
                    ['categoria' => ['$regex' => '^' . preg_quote($categoria_padre), '$options' => 'i']], // Empieza por Padre...
                    ['categoria' => ['$in' => $subcategorias_mapa]] // O está en el mapa explícito
                ];
            }
            
            $pipeline = [
                ['$match' => $match],
                ['$unwind' => '$categoria'], // Descomponer arrays para analizar cada tag
                ['$group' => ['_id' => '$categoria']],
                ['$limit' => 50] // Límite de seguridad
            ];
            
            $resultado = $collection->aggregate($pipeline);
            
            foreach ($resultado as $doc) {
                $cat = $doc['_id'];
                if (is_string($cat)) {
                    $subcategorias_bd[] = $cat;
                }
            }
        } catch (Exception $e) {
            // Silenciar error, usar solo mapa si falla BD
        }
    }
    
    // Combinar y limpiar
    $finales = array_unique(array_merge($subcategorias_mapa, $subcategorias_bd));
    
    // Filtrar: excluir la propia categoría padre, normalizar case
    if ($categoria_padre) {
        $finales = array_filter($finales, function($cat) use ($categoria_padre) {
            $cat_norm = mb_strtolower($cat, 'UTF-8');
            $padre_norm = mb_strtolower($categoria_padre, 'UTF-8');
            return $cat_norm !== $padre_norm;
        });
    }
    
    sort($finales);
    return array_values($finales);
}
