<?php
/**
 * API endpoint para obtener chollos con paginación (scroll infinito)
 * También soporta filtrado por período de tiempo para widgets
 */

header('Content-Type: application/json; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_chollos.php';
include_once __DIR__ . '/../myphp/funciones_chollos_votos.php';
include_once __DIR__ . '/../myphp/funciones_modern.php';

// Obtener parámetros
$skip = isset($_GET['skip']) ? intval($_GET['skip']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 24;
$categoria = $_GET['categoria'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$format = $_GET['format'] ?? 'json'; // 'json' o 'html'

// Nuevos parámetros para widgets con período
$periodo = $_GET['periodo'] ?? ''; // '24h', '7d', '30d'
$tipo = $_GET['tipo'] ?? ''; // 'calientes', 'populares'

// Si se especifica período y tipo, usar función específica
if (!empty($periodo) && !empty($tipo)) {
    $limite_widget = isset($_GET['limite']) ? intval($_GET['limite']) : 5;
    
    $chollos = [];
    
    // Determinar qué función llamar
    if ($tipo === 'calientes') {
        switch ($periodo) {
            case '24h':
                $chollos = obtenerChollosMasCalientes24h($limite_widget, $categoria);
                break;
            case '7d':
                $chollos = obtenerChollosMasCalientes7d($limite_widget, $categoria);
                break;
            case '30d':
                $chollos = obtenerChollosMasCalientes30d($limite_widget, $categoria);
                break;
        }
    } elseif ($tipo === 'populares') {
        switch ($periodo) {
            case '24h':
                $chollos = obtenerChollosMasPopulares24h($limite_widget, $categoria);
                break;
            case '7d':
                $chollos = obtenerChollosMasPopulares7d($limite_widget, $categoria);
                break;
            case '30d':
                $chollos = obtenerChollosMasPopulares30d($limite_widget, $categoria);
                break;
        }
    }
    
    // Renderizar items como HTML
    $renderItem = function($chollo, $tipo) {
        $categoria = is_array($chollo['categoria']) ? $chollo['categoria'][0] : $chollo['categoria'];
        
        // Generar slug SEO-friendly
        if (!function_exists('categoriaToSlug')) {
            include_once __DIR__ . '/../myphp/funciones_chollos_helpers.php';
        }
        $categoria_slug = categoriaToSlug($categoria);
        
        $url = '/chollos/' . $categoria_slug . '/' . $chollo['id'];
        $imagen = $chollo['imagen'] ?: 'https://via.placeholder.com/80x80?text=Chollo';
        $precio = $chollo['precio_descuento'] ? number_format((float)$chollo['precio_descuento'], 2, ',', '.') . '€' : '';
        $titulo = htmlspecialchars($chollo['titulo']);
        
        $meta = '';
        if ($tipo === 'calientes') {
            $meta = '<span class="deal-badge hot"><i class="fas fa-fire"></i> ' . $chollo['temperatura'] . '°</span>';
        } else {
            $meta = '<span class="deal-badge popular"><i class="fas fa-eye"></i> ' . $chollo['clicks'] . '</span>';
        }
        
        return <<<HTML
        <a href="{$url}" class="deal-item-row">
            <div class="deal-item-image">
                <img src="{$imagen}" alt="{$titulo}" loading="lazy">
            </div>
            <div class="deal-item-content">
                <div class="deal-item-title">{$titulo}</div>
                <div class="deal-item-meta">
                    {$meta}
                    <span class="deal-item-price">{$precio}</span>
                </div>
            </div>
        </a>
HTML;
    };
    
    $html = '';
    foreach ($chollos as $chollo) {
        $html .= $renderItem($chollo, $tipo);
    }
    
    $response = [
        'success' => true,
        'html' => $html,
        'count' => count($chollos),
        'periodo' => $periodo,
        'tipo' => $tipo
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Construir filtros para listado normal
$filtros = [
    'estado' => 1,
    'limite' => $limit,
    'skip' => $skip
];

if (!empty($categoria)) {
    $filtros['categoria'] = $categoria;
}

if (!empty($busqueda)) {
    $filtros['busqueda'] = $busqueda;
}

// Obtener chollos
$chollos = obtenerChollos($filtros);

if ($format === 'html') {
    // Devolver HTML renderizado
    ob_start();
    if (!empty($chollos)) {
        imprimir_grid_chollos($chollos, 3);
    }
    $html = ob_get_clean();
    
    $response = [
        'success' => true,
        'html' => $html,
        'count' => count($chollos),
        'skip' => $skip,
        'limit' => $limit,
        'hasMore' => count($chollos) >= $limit
    ];
} else {
    // Devolver JSON
    $response = [
        'success' => true,
        'chollos' => $chollos,
        'count' => count($chollos),
        'skip' => $skip,
        'limit' => $limit,
        'hasMore' => count($chollos) >= $limit
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

