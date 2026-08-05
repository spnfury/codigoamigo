<?php
/**
 * Bulk Keyword Enrichment
 * Ejecuta el enriquecimiento de keywords (Google Suggest + GSC) para TODAS las marcas.
 * Incluye un delay entre llamadas para no saturar la API de Google.
 * 
 * Uso: php /home/admin/web/codigoamigo.com/public_html/cron/bulk_keywords.php
 */
set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_keywords_marca.php';
require_once __DIR__ . '/../pro/app/Services/SeoService.php';

use Casinuevo\Services\SeoService;

$db = createConnection();
$authJsonPath = dirname(__DIR__, 2) . '/private/google_credentials.json';

// Inicializar GSC si hay credenciales
$seoService = null;
if (file_exists($authJsonPath)) {
    try {
        $seoService = new SeoService($authJsonPath);
        echo "✅ Google Search Console conectado.\n";
    } catch (Exception $e) {
        echo "⚠️ GSC no disponible: " . $e->getMessage() . "\n";
    }
}

$siteUrl = 'sc-domain:codigoamigo.com';
$endDate = date('Y-m-d', strtotime('-2 days'));
$startDate = date('Y-m-d', strtotime('-30 days'));

// Obtener todas las marcas activas
$marcas = $db->marcas->find(['estado' => 1], [
    'projection' => ['nombre' => 1, 'nombre_clave' => 1]
])->toArray();

$total = count($marcas);
$updated = 0;
$skipped = 0;
$errors = 0;

echo "Procesando $total marcas...\n\n";

foreach ($marcas as $i => $m) {
    $slug = $m['nombre_clave'] ?? '';
    $name = $m['nombre'] ?? ucfirst($slug);
    
    if (empty($slug)) {
        $skipped++;
        continue;
    }
    
    // Verificar si ya tiene keywords recientes (menos de 7 días)
    $existing = get_brand_keywords($slug);
    if ($existing && !empty($existing['keywords'])) {
        $updated_at = $existing['updated_at'] ?? '';
        if ($updated_at && strtotime($updated_at) > strtotime('-7 days')) {
            $skipped++;
            continue;
        }
    }
    
    try {
        // Google Suggest
        $suggest = get_suggest_keywords_for_brand($name, $slug);
        
        // Google Search Console
        $gsc = [];
        if ($seoService) {
            try {
                $gsc_result = $seoService->getKeywordsForBrand($siteUrl, $slug, $startDate, $endDate);
                if (is_array($gsc_result) && !isset($gsc_result['error'])) {
                    $gsc = $gsc_result;
                }
            } catch (Exception $e) {
                // GSC puede fallar para marcas sin datos, es normal
            }
        }
        
        $merged = merge_and_deduplicate_keywords($suggest, $gsc);
        
        if (!empty($merged)) {
            save_brand_keywords($slug, $merged);
            $updated++;
            echo "[" . ($i + 1) . "/$total] $name: " . count($merged) . " keywords\n";
        } else {
            $skipped++;
        }
        
        // Delay de 200ms para no saturar
        usleep(200000);
        
    } catch (Exception $e) {
        $errors++;
        echo "⚠️ Error en $name: " . $e->getMessage() . "\n";
    }
}

echo "\n=== RESULTADO ===\n";
echo "Total: $total | Actualizadas: $updated | Sin cambios: $skipped | Errores: $errors\n";
