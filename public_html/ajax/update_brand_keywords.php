<?php
/**
 * AJAX endpoint para actualizar keywords SEO de una marca
 * Combina Google Suggest + Google Search Console
 */
session_start();
header('Content-Type: application/json');

// Verificar permisos de administrador
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702", //thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", //edna
    "639899bc6321ee0d0e4010d2", //aron
    "5c8a10ce2f55c86d6e707d82"  //jose
];

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../myphp/funciones_keywords_marca.php';
include_once __DIR__ . '/../pro/app/Services/SeoService.php';

use Casinuevo\Services\SeoService;

// Obtener datos del request
$data = json_decode(file_get_contents('php://input'), true);
$brand_slug = $data['brand_slug'] ?? ($_GET['brand_slug'] ?? '');
$is_preview = ($_SERVER['REQUEST_METHOD'] === 'GET');

if (empty($brand_slug)) {
    echo json_encode(['success' => false, 'error' => 'brand_slug requerido']);
    exit;
}

// Preview mode: just read stored keywords from MongoDB
if ($is_preview) {
    $stored = get_brand_keywords($brand_slug);
    if ($stored && !empty($stored['keywords'])) {
        echo json_encode([
            'success' => true,
            'keywords' => $stored['keywords'],
            'total_keywords' => $stored['total_keywords'] ?? count($stored['keywords']),
            'updated_at' => $stored['updated_at'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No hay keywords almacenadas']);
    }
    exit;
}

try {
    // 1. Obtener info de la marca desde MongoDB
    include_once __DIR__ . '/../myphp/funciones_marca.php';
    $marca_info = getObjectMarca('nombre_clave', $brand_slug);
    
    if (!$marca_info) {
        echo json_encode(['success' => false, 'error' => "Marca '$brand_slug' no encontrada"]);
        exit;
    }
    
    $brand_name = $marca_info['nombre'] ?? ucfirst($brand_slug);
    
    // 2. Obtener keywords de Google Suggest
    $suggest_keywords = get_suggest_keywords_for_brand($brand_name, $brand_slug);
    
    // 3. Obtener keywords de Google Search Console
    $gsc_keywords = [];
    $authJsonPath = dirname(__DIR__, 2) . '/private/google_credentials.json';
    
    if (file_exists($authJsonPath)) {
        $seoService = new SeoService($authJsonPath);
        $siteUrl = 'sc-domain:codigoamigo.com';
        $endDate = date('Y-m-d', strtotime('-2 days'));
        $startDate = date('Y-m-d', strtotime('-30 days'));
        
        $gsc_result = $seoService->getKeywordsForBrand($siteUrl, $brand_slug, $startDate, $endDate);
        
        if (!isset($gsc_result['error'])) {
            $gsc_keywords = $gsc_result;
        }
    }
    
    // 4. Combinar y deduplicar
    $merged_keywords = merge_and_deduplicate_keywords($suggest_keywords, $gsc_keywords);
    
    // 5. Guardar en MongoDB
    $saved = save_brand_keywords($brand_slug, $merged_keywords);
    
    // 6. Preparar resumen por tipo
    $type_counts = ['transactional' => 0, 'informational' => 0, 'product' => 0, 'navigational' => 0, 'generic' => 0];
    foreach ($merged_keywords as $kw) {
        $type = $kw['type'] ?? 'generic';
        if (isset($type_counts[$type])) {
            $type_counts[$type]++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'brand' => $brand_name,
        'brand_slug' => $brand_slug,
        'total_keywords' => count($merged_keywords),
        'sources' => [
            'suggest' => count($suggest_keywords),
            'gsc' => count($gsc_keywords)
        ],
        'types' => $type_counts,
        'keywords' => $merged_keywords, // Full list for preview
        'saved' => $saved
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
