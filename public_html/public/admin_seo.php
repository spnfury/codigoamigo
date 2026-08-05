<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload
include_once __DIR__ . '/../pro/app/Services/SeoService.php';
include_once __DIR__ . '/admin_sidebar_menu.php';

use Casinuevo\Services\SeoService;

// Verificar permisos de administrador
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702", //thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", //edna
    "639899bc6321ee0d0e4010d2", //aron
    "5c8a10ce2f55c86d6e707d82"  //jose
];

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$error_msg = '';
$metrics = [];
$top_queries = [];
$top_pages = [];
$total_clicks = 0;
$total_impressions = 0;
$avg_ctr = 0;
$avg_position = 0;

try {
    $authJsonPath = dirname(__DIR__, 2) . '/private/google_credentials.json';
    
    if (!file_exists($authJsonPath)) {
        throw new Exception("No se encontró el archivo de credenciales en private/google_credentials.json");
    }

    $seoService = new SeoService($authJsonPath);
    $siteUrl = 'sc-domain:codigoamigo.com'; // Domain property detected
    

    // Fechas: Últimos 28 días
    $endDate = date('Y-m-d', strtotime('-2 days')); // GSC tiene retraso de ~2 días
    $startDate = date('Y-m-d', strtotime('-30 days'));

    // Obtener métricas generales (por fecha para gráfica)
    $performanceRows = $seoService->getPerformanceMetrics($siteUrl, $startDate, $endDate);
    
    // Obtener Nuevas Métricas Avanzadas (Ajustamos sensibilidad según tráfico del sitio)
    $lowHangingFruit = $seoService->getLowHangingFruit($siteUrl, $startDate, $endDate, 30); // Bajamos a 30 impresiones
    $lowCTR = $seoService->getLowCTR($siteUrl, $startDate, $endDate, 100, 2.0); // Bajamos a 100 impresiones y CTR < 2%
    $zombies = $seoService->getZombiePages($siteUrl, $startDate, $endDate);
    $zombies = $seoService->getZombiePages($siteUrl, $startDate, $endDate);
    $cannibalization = $seoService->getCannibalizationIssues($siteUrl, $startDate, $endDate);
    
    // --- NUEVO: Content Decay, Preguntas, Tendencias y Expansión ---
    $prevStart = date('Y-m-d', strtotime('-60 days'));
    $prevEnd = date('Y-m-d', strtotime('-30 days'));
    $decayData = $seoService->getContentDecay($siteUrl, $startDate, $endDate, $prevStart, $prevEnd);
    $questionsData = $seoService->getQuestionOpportunities($siteUrl, $startDate, $endDate);
    $emergingTrends = $seoService->getEmergingKeywords($siteUrl, $startDate, $endDate, $prevStart, $prevEnd);
    $brandOpps = $seoService->getBrandOpportunities($siteUrl, $startDate, $endDate);

    // --- NUEVO: Verificar FAQs ya creadas para el sistema de oportunidades ---
    $existing_faqs_map = [];
    if (!empty($questionsData) && !isset($questionsData['error'])) {
        require_once __DIR__ . '/../myphp/funciones_faq.php';
        $faq_collection = getCollectionFAQs();
        $brands_involved = [];
        foreach ($questionsData as $row) {
            if (isset($row->keys[1]) && preg_match('/\/de-([^\/]+)/', $row->keys[1], $m)) {
                $brands_involved[] = $m[1];
            }
        }
        if (!empty($brands_involved)) {
            $unique_brands = array_values(array_unique($brands_involved));
            $cursor = $faq_collection->find(['marca_clave' => ['$in' => $unique_brands]], ['projection' => ['marca_clave' => 1, 'titulo' => 1]]);
            foreach ($cursor as $doc) {
                // Normalizamos título para comparar: minúsculas, sin espacios extra y sin interrogaciones al final
                $normalized_title = trim(mb_strtolower($doc['titulo']));
                $normalized_title = preg_replace('/[¿?!\.]/', '', $normalized_title);
                $key = $doc['marca_clave'] . '|' . $normalized_title;
                $existing_faqs_map[$key] = true;
            }
        }
    }

    // --- NUEVO: Augmentar datos con estado de optimización de marca ---
    $all_slugs = [];
    foreach ([$lowHangingFruit, $lowCTR] as $list) {
        if (is_array($list)) {
            foreach ($list as $row) {
                if (isset($row->keys[1]) && preg_match('/\/de-([^\/]+)/', $row->keys[1], $m)) {
                    $all_slugs[] = $m[1];
                }
            }
        }
    }
    
    $optimization_dates = [];
    if (!empty($all_slugs)) {
        require_once __DIR__ . '/../myphp/funciones_marca.php';
        $collection = getCollectionMarcas();
        $unique_slugs = array_values(array_unique($all_slugs));
        $marcas_docs = $collection->find(['nombre_clave' => ['$in' => $unique_slugs]], ['projection' => ['nombre_clave' => 1, 'ultima_optimizacion_ia' => 1]]);
        foreach ($marcas_docs as $doc) {
            if (isset($doc['ultima_optimizacion_ia'])) {
                $optimization_dates[$doc['nombre_clave']] = $doc['ultima_optimizacion_ia'];
            }
        }
    }
    // --- NUEVO: Obtener eventos de optimización para la gráfica ---
    $opt_events_raw = $collection->find(
        ['ultima_optimizacion_ia' => ['$exists' => true, '$ne' => '']],
        ['projection' => ['ultima_optimizacion_ia' => 1]]
    );
    $events_by_date = [];
    foreach ($opt_events_raw as $doc) {
        $date_key = date('d/m', strtotime($doc['ultima_optimizacion_ia']));
        $events_by_date[$date_key] = ($events_by_date[$date_key] ?? 0) + 1;
    }
    // --------------------------------------------------------------

    // Estadísticas globales de optimización
    $total_marcas = $collection->countDocuments();
    $marcas_optimizadas = $collection->countDocuments(['ultima_optimizacion_ia' => ['$exists' => true, '$ne' => '']]);
    $percent_opt = ($total_marcas > 0) ? round(($marcas_optimizadas / $total_marcas) * 100, 1) : 0;

    // Calcular totales
    if (!empty($performanceRows)) {
        $count = 0;
        foreach ($performanceRows as $row) {
            $total_clicks += $row->clicks;
            $total_impressions += $row->impressions;
            $avg_ctr += $row->ctr;
            $avg_position += $row->position;
            $count++;
        }
        if ($count > 0) {
            $avg_ctr = ($avg_ctr / $count) * 100; // Porcentaje
            $avg_position = $avg_position / $count;
        }
    }

    // Obtener Top Queries
    $top_queries = $seoService->getTopQueries($siteUrl, $startDate, $endDate, 20);

    // Obtener Top Pages
    $top_pages = $seoService->getTopPages($siteUrl, $startDate, $endDate, 20);

} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Dashboard - Google Search Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .main-content { padding: 2rem; }
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        .icon-stat { font-size: 2rem; margin-bottom: 1rem; opacity: 0.8; }
        .nav-tabs .nav-link { background: #f8f9fa; border: none; color: #6c757d; font-weight: 500; }
        .nav-tabs .nav-link.active { background: #fff; border-bottom: 2px solid #0d6efd; color: #0d6efd; }
        .nav-tabs { border-bottom: 1px solid #dee2e6; margin-bottom: 20px; }
    </style>
    <style>
        .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
        .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
        .scale-75 { transform: scale(0.75); }
        .accordion-item { position: relative; }
        .accordion-header { padding-right: 140px; }
        .keyword-link { cursor: pointer; color: #0d6efd; text-decoration: none; border-bottom: 1px dashed #0d6efd; transition: all 0.2s; }
        .keyword-link:hover { color: #0a58ca; border-bottom-style: solid; background-color: rgba(13, 110, 253, 0.05); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo get_admin_sidebar_menu('admin_seo.php'); ?>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0"><i class="fab fa-google me-2 text-primary"></i>SEO Dashboard</h4>
                    <span class="text-muted small">Datos últimos 30 días</span>
                </div>

                <?php if ($error_msg): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?>
                        <br>
                        <small>Asegúrate de que el email de la Service Account (<code>tiktok-uploader@youtube-3-462712.iam.gserviceaccount.com</code>) tenga acceso a la propiedad en Search Console.</small>
                    </div>
                <?php else: ?>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md">
                            <div class="card card-stat bg-white p-3 text-center">
                                <i class="fas fa-mouse-pointer icon-stat text-primary"></i>
                                <h3 class="fw-bold"><?php echo number_format($total_clicks); ?></h3>
                                <p class="text-muted mb-0">Total Clicks</p>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="card card-stat bg-white p-3 text-center">
                                <i class="fas fa-eye icon-stat text-info"></i>
                                <h3 class="fw-bold"><?php echo number_format($total_impressions); ?></h3>
                                <p class="text-muted mb-0">Total Impresiones</p>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="card card-stat bg-white p-3 text-center">
                                <i class="fas fa-percentage icon-stat text-success"></i>
                                <h3 class="fw-bold"><?php echo number_format($avg_ctr, 2); ?>%</h3>
                                <p class="text-muted mb-0">CTR Promedio</p>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="card card-stat bg-white p-3 text-center">
                                <i class="fas fa-sort-amount-down icon-stat text-warning"></i>
                                <h3 class="fw-bold"><?php echo number_format($avg_position, 1); ?></h3>
                                <p class="text-muted mb-0">Posición Media</p>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="card card-stat bg-white p-3 text-center">
                                <i class="fas fa-magic icon-stat text-purple" style="color: #6f42c1;"></i>
                                <h3 class="fw-bold"><?php echo $percent_opt; ?>%</h3>
                                <p class="text-muted mb-0">Procesado IA</p>
                                <small class="text-muted" style="font-size: 0.7rem;"><?php echo $marcas_optimizadas; ?>/<?php echo $total_marcas; ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- TABS -->
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">📊 Visión General</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="opportunities-tab" data-bs-toggle="tab" data-bs-target="#opportunities" type="button" role="tab">🚀 Oportunidades SEO</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="health-tab" data-bs-toggle="tab" data-bs-target="#health" type="button" role="tab">🏥 Salud del Sitio</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="insights-tab" data-bs-toggle="tab" data-bs-target="#insights" type="button" role="tab">🧠 Insights Avanzados</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="trends-tab" data-bs-toggle="tab" data-bs-target="#trends" type="button" role="tab">🔥 Tendencias 🔥</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="expansion-tab" data-bs-toggle="tab" data-bs-target="#expansion" type="button" role="tab">✨ Expansión</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="myTabContent">
                        
                        <!-- TAB GENERAL -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <!-- Gráfica de Rendimiento -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white py-3">
                                    <h5 class="mb-0">Rendimiento Temporal</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="performanceChart" height="80"></canvas>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Top Queries -->
                                <div class="col-md-6">
                                    <div class="card shadow-sm h-100">
                                        <div class="card-header bg-white py-3">
                                            <h5 class="mb-0">Top Keywords</h5>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Query</th>
                                                        <th class="text-end">Clicks</th>
                                                        <th class="text-end">Impr.</th>
                                                        <th class="text-end">Pos.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($top_queries as $row): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="keyword-link" onclick="showKeywordEvolution('<?php echo htmlspecialchars($row->keys[0]); ?>')">
                                                                <?php echo htmlspecialchars($row->keys[0]); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end"><?php echo number_format($row->clicks); ?></td>
                                                        <td class="text-end"><?php echo number_format($row->impressions); ?></td>
                                                        <td class="text-end"><?php echo number_format($row->position, 1); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Top Pages -->
                                <div class="col-md-6">
                                    <div class="card shadow-sm h-100">
                                        <div class="card-header bg-white py-3">
                                            <h5 class="mb-0">Páginas más visitadas</h5>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Página</th>
                                                        <th class="text-end">Clicks</th>
                                                        <th class="text-end">Impr.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($top_pages as $row): ?>
                                                    <tr>
                                                        <td class="small text-truncate" style="max-width: 250px;">
                                                            <a href="<?php echo htmlspecialchars($row->keys[0]); ?>" target="_blank" class="text-decoration-none">
                                                                <?php echo htmlspecialchars(str_replace('https://www.codigoamigo.com', '', $row->keys[0])); ?>
                                                            </a>
                                                        </td>
                                                        <td class="text-end"><?php echo number_format($row->clicks); ?></td>
                                                        <td class="text-end"><?php echo number_format($row->impressions); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB OPORTUNIDADES -->
                        <div class="tab-pane fade" id="opportunities" role="tabpanel">
                            <div class="row">
                                <!-- Low Hanging Fruit -->
                                <div class="col-12 mb-4">
                                    <div class="card shadow-sm border-warning">
                                        <div class="card-header bg-warning bg-opacity-10 py-3">
                                            <h5 class="mb-0 text-warning text-dark"><i class="fas fa-apple-alt me-2"></i>Fruta al Alcance (Low Hanging Fruit)</h5>
                                            <small class="text-muted">Keywords en posición 4-20 con muchas impresiones. ¡Mejora el contenido para subir al Top 3!</small>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Keyword</th>
                                                        <th>Página</th>
                                                        <th class="text-center">Posición</th>
                                                        <th class="text-center">Impresiones</th>
                                                        <th class="text-center">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($lowHangingFruit)): ?>
                                                        <tr><td colspan="5" class="text-center py-3">No hay datos suficientes aún.</td></tr>
                                                    <?php else: ?>
                                                        <?php 
                                                            foreach ($lowHangingFruit as $row): 
                                                                $page_url = $row->keys[1];
                                                                $brand_slug = '';
                                                                if (preg_match('/\/de-([^\/]+)/', $page_url, $m)) {
                                                                    $brand_slug = $m[1];
                                                                }
                                                        ?>
                                                        <tr>
                                                            <td class="fw-bold">
                                                                <span class="keyword-link" onclick="showKeywordEvolution('<?php echo htmlspecialchars($row->keys[0]); ?>')">
                                                                    <?php echo htmlspecialchars($row->keys[0]); ?>
                                                                </span>
                                                            </td>
                                                            <td class="small text-truncate" style="max-width: 300px;">
                                                                <a href="<?php echo htmlspecialchars($row->keys[1]); ?>" target="_blank"><?php echo htmlspecialchars(str_replace('https://www.codigoamigo.com', '', $row->keys[1])); ?></a>
                                                            </td>
                                                            <td class="text-center text-warning fw-bold"><?php echo number_format($row->position, 1); ?></td>
                                                            <td class="text-center"><?php echo number_format($row->impressions); ?></td>
                                                            <td class="text-center">
                                                                <button class="btn btn-sm btn-outline-primary btn-optimize" 
                                                                        data-keyword="<?php echo htmlspecialchars($row->keys[0]); ?>" 
                                                                        data-page="<?php echo htmlspecialchars($row->keys[1]); ?>"
                                                                        data-brand="<?php echo htmlspecialchars($brand_slug); ?>">
                                                                    <i class="fas fa-magic"></i> Optimizar IA
                                                                </button>
                                                                <?php if (isset($optimization_dates[$brand_slug])): ?>
                                                                    <div class="mt-1">
                                                                        <span class="badge bg-success-soft text-success border border-success border-opacity-25" style="font-size: 0.7rem;">
                                                                            <i class="fas fa-check-double scale-75"></i> Optimizado: <?php echo date('d/m/y', strtotime($optimization_dates[$brand_slug])); ?>
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Low CTR -->
                                <div class="col-12">
                                    <div class="card shadow-sm border-info">
                                        <div class="card-header bg-info bg-opacity-10 py-3">
                                            <h5 class="mb-0 text-info text-dark"><i class="fas fa-mouse me-2"></i>Mejorar CTR (Títulos Aburridos)</h5>
                                            <small class="text-muted">Páginas que se muestran mucho pero reciben pocos clics. ¡Mejora el Título y la Meta Descripción!</small>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Keyword</th>
                                                        <th>Página</th>
                                                        <th class="text-center">CTR</th>
                                                        <th class="text-center">Impresiones</th>
                                                        <th class="text-center">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($lowCTR)): ?>
                                                        <tr><td colspan="5" class="text-center py-3">No hay datos suficientes aún.</td></tr>
                                                    <?php else: ?>
                                                        <?php 
                                                            foreach ($lowCTR as $row): 
                                                                $page_url = $row->keys[1];
                                                                $brand_slug = '';
                                                                if (preg_match('/\/de-([^\/]+)/', $page_url, $m)) {
                                                                    $brand_slug = $m[1];
                                                                }
                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <span class="keyword-link" onclick="showKeywordEvolution('<?php echo htmlspecialchars($row->keys[0]); ?>')">
                                                                    <?php echo htmlspecialchars($row->keys[0]); ?>
                                                                </span>
                                                            </td>
                                                            <td class="small text-truncate" style="max-width: 300px;">
                                                                <a href="<?php echo htmlspecialchars($row->keys[1]); ?>" target="_blank"><?php echo htmlspecialchars(str_replace('https://www.codigoamigo.com', '', $row->keys[1])); ?></a>
                                                            </td>
                                                            <td class="text-center text-danger fw-bold"><?php echo number_format($row->ctr * 100, 2); ?>%</td>
                                                            <td class="text-center"><?php echo number_format($row->impressions); ?></td>
                                                            <td class="text-center">
                                                                <button class="btn btn-sm btn-outline-info btn-optimize-title"
                                                                        data-keyword="<?php echo htmlspecialchars($row->keys[0]); ?>"
                                                                        data-brand="<?php echo htmlspecialchars($brand_slug); ?>">
                                                                    <i class="fas fa-pen-fancy"></i> Nuevo Título
                                                                </button>
                                                                <?php if (isset($optimization_dates[$brand_slug])): ?>
                                                                    <div class="mt-1">
                                                                        <span class="badge bg-success-soft text-success border border-success border-opacity-25" style="font-size: 0.7rem;">
                                                                            <i class="fas fa-check-double scale-75"></i> Optimizado: <?php echo date('d/m/y', strtotime($optimization_dates[$brand_slug])); ?>
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB SALUD -->
                        <div class="tab-pane fade" id="health" role="tabpanel">
                             <div class="row">
                                <!-- Canibalización -->
                                <div class="col-12 mb-4">
                                    <div class="card shadow-sm border-danger">
                                        <div class="card-header bg-danger bg-opacity-10 py-3">
                                            <h5 class="mb-0 text-danger"><i class="fas fa-exclamation-circle me-2"></i>Posible Canibalización</h5>
                                            <small class="text-muted">Keywords donde compiten varias de tus páginas.</small>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($cannibalization)): ?>
                                                <p class="text-center text-muted my-3">¡Bien! No se detectaron problemas graves de canibalización.</p>
                                            <?php else: ?>
                                                <div class="accordion" id="accordionCannibal">
                                                <?php $i=0; foreach ($cannibalization as $query => $pages): $i++; ?>
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $i; ?>">
                                                                Keyword: <strong>"<?php echo htmlspecialchars($query); ?>"</strong>  &nbsp;<span class="badge bg-danger"><?php echo count($pages); ?> páginas compitiendo</span>
                                                            </button>
                                                        </h2>
                                                        <div class="position-absolute top-50 end-0 translate-middle-y me-3" style="z-index: 10;">
                                                            <button class="btn btn-sm btn-success auto-fix-btn" 
                                                                    data-keyword="<?php echo htmlspecialchars($query); ?>"
                                                                    data-pages='<?php echo json_encode($pages); ?>'
                                                                    onclick="event.stopPropagation();">
                                                                <i class="fas fa-magic me-1"></i> Auto-Fix 301
                                                            </button>
                                                        </div>
                                                        <div id="collapse<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionCannibal">
                                                            <div class="accordion-body">
                                                                <table class="table table-sm">
                                                                    <thead><tr><th>Página</th><th class="text-end">Clicks</th><th class="text-end">Impr.</th></tr></thead>
                                                                    <tbody>
                                                                    <?php foreach ($pages as $p): ?>
                                                                        <tr>
                                                                            <td class="small text-truncate"><a href="<?php echo htmlspecialchars($p['page']); ?>" target="_blank"><?php echo htmlspecialchars(str_replace('https://www.codigoamigo.com', '', $p['page'])); ?></a></td>
                                                                            <td class="text-end"><?php echo $p['clicks']; ?></td>
                                                                            <td class="text-end"><?php echo $p['impressions']; ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                                <div class="alert alert-warning py-2 mb-0 small">
                                                                    <i class="fas fa-lightbulb me-1"></i> Consejo: Fusiona estas páginas o cambia el enfoque de la menos importante.
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Zombies -->
                                <div class="col-12">
                                    <div class="card shadow-sm border-secondary">
                                        <div class="card-header bg-secondary bg-opacity-10 py-3">
                                            <h5 class="mb-0 text-secondary"><i class="fas fa-skull me-2"></i>Contenido Zombie</h5>
                                            <small class="text-muted">Páginas indexadas sin clics en el último mes. Considera eliminarlas o actualizarlas.</small>
                                        </div>
                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Página</th>
                                                        <th class="text-end">Impresiones</th>
                                                        <th class="text-end">Clicks</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($zombies as $row): ?>
                                                    <tr>
                                                        <td class="small text-truncate" style="max-width: 400px;">
                                                            <a href="<?php echo htmlspecialchars($row->keys[0]); ?>" target="_blank" class="text-secondary text-decoration-none">
                                                                <?php echo htmlspecialchars(str_replace('https://www.codigoamigo.com', '', $row->keys[0])); ?>
                                                            </a>
                                                        </td>
                                                        <td class="text-end text-muted"><?php echo number_format($row->impressions); ?></td>
                                                        <td class="text-end text-muted">0</td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- TAB INSIGHTS AVANZADOS -->
                        <div class="tab-pane fade" id="insights" role="tabpanel">
                             <div class="row">
                                <!-- Content Decay -->
                                <div class="col-12 mb-4">
                                    <div class="card shadow-sm border-danger">
                                        <div class="card-header bg-danger bg-opacity-10 py-3">
                                            <h5 class="mb-0 text-danger"><i class="fas fa-chart-line fa-flip-vertical me-2"></i>Decadencia de Contenido</h5>
                                            <small class="text-muted">Páginas que han perdido tráfico significativo respecto al mes anterior. ¡Actualízalas!</small>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Página</th>
                                                        <th class="text-center">Antes</th>
                                                        <th class="text-center">Ahora</th>
                                                        <th class="text-center">Pérdida</th>
                                                        <th class="text-center">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($decayData) || isset($decayData['error'])): ?>
                                                        <tr><td colspan="5" class="text-center py-3">¡Bien! No se detectó decadencia significativa recientemente.</td></tr>
                                                    <?php else: ?>
                                                        <?php foreach ($decayData as $row): 
                                                            $brand_slug = '';
                                                            $opt_keyword = 'actualizar contenido';
                                                            if (preg_match('/\/de-([^\/]+)/', $row['page'], $m)) {
                                                                $brand_slug = $m[1];
                                                                $opt_keyword = 'código promocional ' . str_replace('-', ' ', $brand_slug);
                                                            }
                                                        ?>
                                                        <tr>
                                                            <td class="small text-truncate" style="max-width: 350px;">
                                                                <a href="<?php echo htmlspecialchars($row['page']); ?>" target="_blank" class="text-danger text-decoration-none">
                                                                    <?php echo htmlspecialchars(str_replace('https://www.codigoamigo.com', '', $row['page'])); ?>
                                                                </a>
                                                            </td>
                                                            <td class="text-center text-muted"><?php echo number_format($row['prev_clicks']); ?></td>
                                                            <td class="text-center fw-bold"><?php echo number_format($row['curr_clicks']); ?></td>
                                                            <td class="text-center text-danger">
                                                                <i class="fas fa-arrow-down small"></i> <?php echo number_format(abs($row['diff'])); ?> 
                                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill ms-1"><?php echo number_format($row['percent'], 1); ?>%</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <button class="btn btn-sm btn-outline-danger btn-optimize" 
                                                                        data-page="<?php echo htmlspecialchars($row['page']); ?>"
                                                                        data-brand="<?php echo htmlspecialchars($brand_slug); ?>"
                                                                        data-keyword="<?php echo htmlspecialchars($opt_keyword); ?>">
                                                                    <i class="fas fa-sync-alt"></i> Actualizar
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Question Opportunities -->
                                <div class="col-12">
                                    <div class="card shadow-sm border-primary">
                                        <div class="card-header bg-primary bg-opacity-10 py-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-0 text-primary"><i class="fas fa-question-circle me-2"></i>Oportunidades de Preguntas (FAQ)</h5>
                                                <small class="text-muted">Keywords tipo pregunta con buen tráfico (Muestra top 5000).</small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div id="bulkStatus" class="me-3 small text-muted" style="display:none;"></div>
                                                <button class="btn btn-outline-primary btn-sm ms-2" id="toggleCreatedFaqsBtn" onclick="toggleCreatedFaqs()" data-hidden="true">
                                                    <i class="fas fa-eye me-1"></i> Ver Creadas
                                                </button>
                                                <button class="btn btn-primary btn-sm ms-2" onclick="bulkCreateAllFAQs()">
                                                    <i class="fas fa-robot me-1"></i> Crear Todas (Masivo)
                                                </button>
                                            </div>
                                        </div>
                                        <div id="bulkProgressContainer" class="progress mb-0" style="height: 5px; display:none; border-radius: 0;">
                                            <div id="bulkProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Pregunta</th>
                                                        <th>Página Ranking</th>
                                                        <th class="text-center">Posición</th>
                                                        <th class="text-center">Impresiones</th>
                                                        <th class="text-center">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($questionsData) || isset($questionsData['error'])): ?>
                                                        <tr><td colspan="5" class="text-center py-3">No se encontraron preguntas relevantes.</td></tr>
                                                    <?php else: ?>
                                                            <?php foreach ($questionsData as $row): 
                                                                $page_url = $row->keys[1];
                                                                $brand_slug = '';
                                                                if (preg_match('/\/de-([^\/]+)/', $page_url, $m)) {
                                                                    $brand_slug = $m[1];
                                                                }

                                                                // Verificar si ya existe
                                                                $q_normalized = trim(mb_strtolower($row->keys[0]));
                                                                $q_normalized = preg_replace('/[¿?!\.]/', '', $q_normalized);
                                                                $check_key = $brand_slug . '|' . $q_normalized;
                                                                $already_exists = isset($existing_faqs_map[$check_key]);
                                                        ?>
                                                        <tr class="<?php echo $already_exists ? 'faq-row-created' : 'faq-row-pending'; ?>" <?php echo $already_exists ? 'style="display:none;"' : ''; ?>>
                                                            <td class="fw-bold text-primary">
                                                                <span class="keyword-link" onclick="showKeywordEvolution('<?php echo htmlspecialchars($row->keys[0]); ?>')">
                                                                    <?php echo htmlspecialchars($row->keys[0]); ?>
                                                                </span>
                                                            </td>
                                                            <td class="small text-truncate" style="max-width: 300px;">
                                                                <a href="<?php echo htmlspecialchars($row->keys[1]); ?>" target="_blank"><?php echo htmlspecialchars(str_replace('https://www.codigoamigo.com', '', $row->keys[1])); ?></a>
                                                            </td>
                                                            <td class="text-center"><?php echo number_format($row->position, 1); ?></td>
                                                            <td class="text-center"><?php echo number_format($row->impressions); ?></td>
                                                            <td class="text-center">
                                                                <?php if ($already_exists): ?>
                                                                    <span class="badge bg-success rounded-pill px-3 py-2">
                                                                        <i class="fas fa-check-circle me-1"></i> Creada
                                                                    </span>
                                                                <?php else: ?>
                                                                    <button class="btn btn-sm btn-outline-primary btn-generate-faq" 
                                                                            data-question="<?php echo htmlspecialchars($row->keys[0]); ?>"
                                                                            data-brand="<?php echo htmlspecialchars($brand_slug); ?>">
                                                                        <i class="fas fa-magic"></i> Crear FAQ
                                                                    </button>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                             </div>
                        </div>

                        <!-- TAB TENDENCIAS -->
                        <div class="tab-pane fade" id="trends" role="tabpanel">
                            <div class="card shadow-sm border-danger mb-4">
                                <div class="card-header bg-danger bg-opacity-10 py-3">
                                    <h5 class="mb-0 text-danger"><i class="fas fa-fire me-2"></i>Tendencias Emergentes</h5>
                                    <small class="text-muted">Keywords con alto crecimiento en los últimos 30 días comparado con el mes anterior.</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Keyword</th>
                                                <th class="text-center">Antes (Impr.)</th>
                                                <th class="text-center">Ahora (Impr.)</th>
                                                <th class="text-center">Crecimiento</th>
                                                <th class="text-center">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($emergingTrends)): ?>
                                                <tr><td colspan="5" class="text-center py-3">No se detectaron tendencias explosivas recientemente.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($emergingTrends as $row): ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($row['query']); ?></td>
                                                    <td class="text-center text-muted"><?php echo number_format($row['prev_impr']); ?></td>
                                                    <td class="text-center fw-bold"><?php echo number_format($row['curr_impr']); ?></td>
                                                    <td class="text-center text-success">
                                                        <i class="fas fa-chart-line small"></i> +<?php echo number_format($row['diff']); ?>
                                                        <span class="badge bg-success bg-opacity-25 text-success rounded-pill ms-1">+<?php echo number_format($row['percent'], 1); ?>%</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-danger btn-optimize-title" 
                                                                data-keyword="<?php echo htmlspecialchars($row['query']); ?>">
                                                            <i class="fas fa-rocket"></i> Capitalizar
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- TAB EXPANSIÓN -->
                        <div class="tab-pane fade" id="expansion" role="tabpanel">
                            <div class="card shadow-sm border-purple mb-4" style="border-color: #6f42c1 !important;">
                                <div class="card-header py-3" style="background-color: rgba(111, 66, 193, 0.1);">
                                    <h5 class="mb-0" style="color: #6f42c1;"><i class="fas fa-expand-arrows-alt me-2"></i>Oportunidades de Nuevas Marcas / Paginas</h5>
                                    <small class="text-muted">Keywords transaccionales donde el ranking actual es pobre o redirige a la home/búsqueda.</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Intento de Búsqueda</th>
                                                <th>Página Actual</th>
                                                <th class="text-center">Posición</th>
                                                <th class="text-center">Impresiones</th>
                                                <th class="text-center">Potencial</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($brandOpps)): ?>
                                                <tr><td colspan="5" class="text-center py-3">No hay oportunidades de expansión detectadas.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($brandOpps as $row): ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($row->keys[0]); ?></td>
                                                    <td class="small text-truncate" style="max-width: 300px;">
                                                        <a href="<?php echo htmlspecialchars($row->keys[1]); ?>" target="_blank" class="text-muted">
                                                            <?php echo htmlspecialchars(str_replace('https://www.codigoamigo.com', '', $row->keys[1])); ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-center"><?php echo number_format($row->position, 1); ?></td>
                                                    <td class="text-center"><?php echo number_format($row->impressions); ?></td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-purple text-white" style="background-color: #6f42c1;"
                                                                onclick="window.open('https://www.google.com/search?q=<?php echo urlencode($row->keys[0]); ?>', '_blank')">
                                                            <i class="fas fa-plus-circle"></i> Crear Landing
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Script Gráfica -->
                    <script>
                        const ctx = document.getElementById('performanceChart').getContext('2d');
                        const dates = <?php echo json_encode(array_map(function($r) { return date('d/m', strtotime($r->keys[0])); }, $performanceRows)); ?>;
                        const clicks = <?php echo json_encode(array_map(function($r) { return $r->clicks; }, $performanceRows)); ?>;
                        const impressions = <?php echo json_encode(array_map(function($r) { return $r->impressions; }, $performanceRows)); ?>;
                        const eventsByDate = <?php echo json_encode($events_by_date); ?>;
                        
                        // Encontrar el valor máximo de clics para posicionar los marcadores arriba
                        const maxClicks = Math.max(...clicks, 10);
                        const eventMarkers = dates.map(d => eventsByDate[d] ? maxClicks * 0.95 : null);

                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: dates,
                                datasets: [{
                                    label: 'Clicks',
                                    data: clicks,
                                    borderColor: '#0d6efd', // Primary
                                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                    yAxisID: 'y',
                                    tension: 0.3,
                                    fill: true
                                }, {
                                    label: 'Impresiones',
                                    data: impressions,
                                    borderColor: '#0dcaf0', // Info
                                    backgroundColor: 'rgba(13, 202, 240, 0.1)',
                                    yAxisID: 'y1',
                                    tension: 0.3,
                                    fill: true,
                                    hidden: true // Oculto por defecto para no distorsionar escala
                                }, {
                                    label: 'Optimización IA (Evento)',
                                    data: eventMarkers,
                                    borderColor: '#ffc107', // Warning/Gold
                                    backgroundColor: '#ffc107',
                                    pointStyle: 'star',
                                    pointRadius: 10,
                                    pointHoverRadius: 15,
                                    showLine: false, // Solo puntos
                                    yAxisID: 'y'
                                }]
                            },
                            options: {
                                responsive: true,
                                interaction: {
                                    mode: 'index',
                                    intersect: false,
                                },
                                scales: {
                                    y: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                        title: { display: true, text: 'Clicks' }
                                    },
                                    y1: {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        grid: { drawOnChartArea: false },
                                        title: { display: true, text: 'Impresiones' }
                                    }
                                }
                            }
                        });
                    </script>

                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función reutilizable para generar título
        async function generateOptimizedTitle(btn, keyword) {
            const originalBtnText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            btn.disabled = true;

            try {
                const response = await fetch('../ajax/generate_seo_title.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ keyword: keyword })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const modalHtml = `
                        <div class="modal fade" id="resultModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">✨ Título Optimizado con IA</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted mb-1">Para la keyword: <strong>${keyword}</strong></p>
                                        <div class="p-3 bg-light rounded border mb-3">
                                            <h5 class="mb-0 text-primary">${data.optimized_title}</h5>
                                        </div>
                                        <p class="small text-muted"><i class="fas fa-info-circle"></i> Copia este título y úsalo en tu página para mejorar el CTR.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        <button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText('${data.optimized_title.replace(/'/g, "\\'")}')">Copiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    const oldModal = document.getElementById('resultModal');
                    if (oldModal) oldModal.remove();
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    const modal = new bootstrap.Modal(document.getElementById('resultModal'));
                    modal.show();
                } else {
                    alert('Error: ' + (data.error || 'No se pudo generar el título'));
                }
            } catch (error) {
                alert('Error de conexión');
            } finally {
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
            }
        }

        // Función para optimización automática de la web
                                        async function autoOptimizeWeb(btn, keyword, brand) {
                                            if (!brand) {
                                                alert('No se pudo identificar la marca para optimizar.');
                                                return;
                                            }

                                            const originalBtnText = btn.innerHTML;
                                            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Optimizando Web...';
                                            btn.disabled = true;

                                            try {
                                                const formData = new FormData();
                                                formData.append('brand', brand);
                                                formData.append('keyword', keyword);

                                                const response = await fetch('../ajax/optimize_brand_content.php', {
                                                    method: 'POST',
                                                    body: formData
                                                });
                                                
                                                const data = await response.json();
                                                
                                                if (data.success) {
                                                    const modalHtml = `
                                                        <div class="modal fade" id="resultModal" tabindex="-1">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header ${data.modified ? 'bg-success' : 'bg-info'} text-white">
                                                                        <h5 class="modal-title">${data.modified ? '🚀 ¡Web Actualizada!' : '✅ Contenido Confirmado'}</h5>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="alert ${data.modified ? 'alert-success' : 'alert-info'}">
                                                                            <i class="fas ${data.modified ? 'fa-check-circle' : 'fa-info-circle'} me-2"></i> ${data.message}
                                                                        </div>
                                                                        <h6 class="fw-bold mb-3">Vista previa del contenido:</h6>
                                                                        <table class="table table-sm table-bordered">
                                                                            <tr class="table-light"><th>Campo</th><th>Contenido</th></tr>
                                                                            <tr><td><strong>H1</strong></td><td>${data.data.h1}</td></tr>
                                                                            <tr><td><strong>H2</strong></td><td>${data.data.h2}</td></tr>
                                                                            <tr><td><strong>Meta Desc</strong></td><td class="small">${data.data.descripcion}</td></tr>
                                                                            <tr><td><strong>Sobre la Marca</strong></td><td class="small">${data.data.seo_que_es}</td></tr>
                                                                            <tr><td><strong>Tips Ahorro</strong></td><td class="small">${data.data.seo_tips}</td></tr>
                                                                        </table>
                                                                        <div class="text-center mt-3">
                                                                            <a href="/de-${brand}" target="_blank" class="btn btn-outline-primary">
                                                                                <i class="fas fa-external-link-alt"></i> Ver cambios en la web
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
                                                    const oldModal = document.getElementById('resultModal');
                                                    if (oldModal) oldModal.remove();
                                                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                                                    const modal = new bootstrap.Modal(document.getElementById('resultModal'));
                                                    modal.show();
                                                } else {
                                                    alert('Error: ' + (data.error || 'No se pudo optimizar la web'));
                                                }
                                            } catch (error) {
                                                alert('Error de conexión');
                                            } finally {
                                                btn.innerHTML = originalBtnText;
                                                btn.disabled = false;
                                            }
                                        }

                                        // --- NUEVO: Función para mostrar evolución de Keyword ---
                                        let evolutionChart = null;

                                        async function showKeywordEvolution(keyword) {
                                            // 1. Mostrar modal con loading
                                            const modalHtml = `
                                                <div class="modal fade" id="evolutionModal" tabindex="-1">
                                                    <div class="modal-dialog modal-xl">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"><i class="fas fa-chart-line me-2 text-primary"></i>Evolución: <strong>${keyword}</strong></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div id="evoLoading" class="text-center py-5">
                                                                    <div class="spinner-border text-primary" role="status"></div>
                                                                    <p class="mt-2 text-muted">Obteniendo datos de Search Console...</p>
                                                                </div>
                                                                <div id="evoContent" style="display:none;">
                                                                    <canvas id="evoChart" height="100"></canvas>
                                                                    <div class="alert alert-info mt-3 small">
                                                                        <i class="fas fa-info-circle"></i> Mostrando datos de los últimos 90 días.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            `;
                                            
                                            // Limpiar modales viejos
                                            const oldModal = document.getElementById('evolutionModal');
                                            if (oldModal) oldModal.remove();
                                            
                                            document.body.insertAdjacentHTML('beforeend', modalHtml);
                                            const modal = new bootstrap.Modal(document.getElementById('evolutionModal'));
                                            modal.show();

                                            // 2. Fetch data
                                            try {
                                                const response = await fetch(`../ajax/get_keyword_evolution.php?keyword=${encodeURIComponent(keyword)}`);
                                                const data = await response.json();

                                                if (data.success) {
                                                    document.getElementById('evoLoading').style.display = 'none';
                                                    document.getElementById('evoContent').style.display = 'block';
                                                    renderEvolutionChart(data.data);
                                                } else {
                                                    alert('Error: ' + (data.error || 'No se pudieron cargar los datos'));
                                                    modal.hide();
                                                }
                                            } catch (error) {
                                                console.error(error);
                                                alert('Error de conexión al cargar datos');
                                                modal.hide();
                                            }
                                        }

                                        function renderEvolutionChart(data) {
                                            const ctx = document.getElementById('evoChart').getContext('2d');
                                            
                                            if (evolutionChart) {
                                                evolutionChart.destroy();
                                            }

                                            evolutionChart = new Chart(ctx, {
                                                type: 'line',
                                                data: {
                                                    labels: data.labels,
                                                    datasets: [
                                                        {
                                                            label: 'Clicks',
                                                            data: data.clicks,
                                                            borderColor: '#0d6efd',
                                                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                                            yAxisID: 'y',
                                                            tension: 0.3,
                                                            fill: true,
                                                            order: 2
                                                        },
                                                        {
                                                            label: 'Posición Media',
                                                            data: data.position,
                                                            borderColor: '#ffc107',
                                                            backgroundColor: 'rgba(255, 193, 7, 0.1)',
                                                            yAxisID: 'y1',
                                                            tension: 0.3,
                                                            borderDash: [5, 5],
                                                            fill: false,
                                                            order: 1
                                                        }
                                                    ]
                                                },
                                                options: {
                                                    responsive: true,
                                                    interaction: {
                                                        mode: 'index',
                                                        intersect: false,
                                                    },
                                                    plugins: {
                                                        legend: { position: 'top' }
                                                    },
                                                    scales: {
                                                        y: {
                                                            type: 'linear',
                                                            display: true,
                                                            position: 'left',
                                                            title: { display: true, text: 'Clicks' },
                                                            beginAtZero: true
                                                        },
                                                        y1: {
                                                            type: 'linear',
                                                            display: true,
                                                            position: 'right',
                                                            reverse: true, // Posición 1 es mejor (arriba)
                                                            grid: { drawOnChartArea: false },
                                                            title: { display: true, text: 'Posición' },
                                                            min: 1
                                                        }
                                                    }
                                                }
                                            });
                                        }


                                        // Listener Delegado para botones de optimización
                                        document.addEventListener('click', function(e) {
                                            const btnOptimize = e.target.closest('.btn-optimize');
                                            const btnOptimizeTitle = e.target.closest('.btn-optimize-title');
                                            const btnFAQ = e.target.closest('.btn-generate-faq');

                                            if (btnOptimize) {
                                                console.log('Optimizar clickado', btnOptimize.dataset);
                                                autoOptimizeWeb(btnOptimize, btnOptimize.dataset.keyword, btnOptimize.dataset.brand);
                                            } else if (btnOptimizeTitle) {
                                                console.log('Optimizar Título clickado', btnOptimizeTitle.dataset);
                                                autoOptimizeWeb(btnOptimizeTitle, btnOptimizeTitle.dataset.keyword, btnOptimizeTitle.dataset.brand);
                                            } else if (btnFAQ) {
                                                openFAQGenerator(btnFAQ.dataset.question, btnFAQ.dataset.brand);
                                            }
                                        });

                                        // --- NUEVO: FAQ Generator Logic ---
                                        async function openFAQGenerator(question, brand) {
                                            if (!brand) {
                                                alert('No se pudo identificar la marca para esta pregunta.');
                                                return;
                                            }

                                            // 1. Mostrar modal inicial
                                            const modalHtml = `
                                                <div class="modal fade" id="faqModal" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title"><i class="fas fa-robot me-2"></i>Generador de FAQ con IA</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <h6 class="fw-bold mb-3">Pregunta a responder:</h6>
                                                                <div class="p-3 bg-light rounded mb-3 border">
                                                                    <i class="fas fa-question-circle text-primary me-2"></i> <strong>${question}</strong>
                                                                </div>

                                                                <div id="faqLoading" class="text-center py-4">
                                                                    <div class="spinner-border text-primary" role="status"></div>
                                                                    <p class="mt-2 text-muted">Redactando la mejor respuesta...</p>
                                                                </div>

                                                                <div id="faqEditor" style="display:none;">
                                                                    <label class="form-label">Respuesta Generada (editable):</label>
                                                                    <textarea id="faqAnswer" class="form-control mb-3" rows="5"></textarea>
                                                                    <div class="alert alert-info small">
                                                                        <i class="fas fa-info-circle"></i> Revisa la respuesta antes de guardar. Se añadirá a la descripción de la marca.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="button" class="btn btn-success" id="btnSaveFAQ" style="display:none;" onclick="saveFAQ('${question.replace(/'/g, "\\'")}', '${brand}')">
                                                                    <i class="fas fa-save me-2"></i> Guardar en Web
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            `;
                                            
                                            const oldModal = document.getElementById('faqModal');
                                            if (oldModal) oldModal.remove();
                                            document.body.insertAdjacentHTML('beforeend', modalHtml);
                                            const modal = new bootstrap.Modal(document.getElementById('faqModal'));
                                            modal.show();

                                            // 2. Generar respuesta
                                            try {
                                                const formData = new FormData();
                                                formData.append('brand', brand);
                                                formData.append('question', question);

                                                const response = await fetch('../ajax/generate_faq.php', {
                                                    method: 'POST',
                                                    body: formData
                                                });
                                                const data = await response.json();

                                                if (data.success) {
                                                    document.getElementById('faqLoading').style.display = 'none';
                                                    document.getElementById('faqEditor').style.display = 'block';
                                                    document.getElementById('faqAnswer').value = data.answer;
                                                    document.getElementById('btnSaveFAQ').style.display = 'inline-block';
                                                } else {
                                                    alert('Error: ' + (data.error || 'No se pudo generar respuesta'));
                                                    modal.hide();
                                                }
                                            } catch (error) {
                                                console.error(error);
                                                alert('Error de conexión');
                                                modal.hide();
                                            }
                                        }

                                        async function saveFAQ(question, brand) {
                                            const answer = document.getElementById('faqAnswer').value;
                                            const btn = document.getElementById('btnSaveFAQ');
                                            
                                            if (!answer.trim()) {
                                                alert('La respuesta no puede estar vacía');
                                                return;
                                            }

                                            btn.disabled = true;
                                            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                                            try {
                                                const formData = new FormData();
                                                formData.append('brand', brand);
                                                formData.append('question', question);
                                                formData.append('answer', answer);

                                                const response = await fetch('../ajax/save_faq.php', {
                                                    method: 'POST',
                                                    body: formData
                                                });
                                                const data = await response.json();

                                                if (data.success) {
                                                    alert('✅ FAQ guardada correctamente en la web.');
                                                    bootstrap.Modal.getInstance(document.getElementById('faqModal')).hide();
                                                    
                                                    // Marcar como creada visualmente sin recargar
                                                    const btnOrig = document.querySelector(`.btn-generate-faq[data-brand="${brand}"][data-question="${question}"]`);
                                                    if (btnOrig) {
                                                        const row = btnOrig.closest('tr');
                                                        row.classList.add('faq-row-created');
                                                        row.classList.remove('faq-row-pending');
                                                        btnOrig.parentElement.innerHTML = '<span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i> Creada</span>';
                                                        
                                                        // Ocultar si el toggle está en "Ver Creadas" (osea, ocultas por defecto)
                                                        const toggleBtn = document.getElementById('toggleCreatedFaqsBtn');
                                                        if (toggleBtn && toggleBtn.dataset.hidden === "true") {
                                                            row.style.display = 'none';
                                                        }
                                                    }
                                                } else {
                                                    alert('Error al guardar: ' + data.error);
                                                }
                                            } catch (error) {
                                                alert('Error de conexión al guardar');
                                            } finally {
                                                btn.disabled = false;
                                                btn.innerHTML = '<i class="fas fa-save me-2"></i> Guardar en Web';
                                            }
                                        }

                                        // --- NUEVO: Batch processing for Bulk Creation ---
                                        async function bulkCreateAllFAQs() {
                                            const allRows = document.querySelectorAll('.btn-generate-faq');
                                            const total = allRows.length;
                                            
                                            if (total === 0) {
                                                alert('No hay FAQs pendientes para crear en el listado actual.');
                                                return;
                                            }

                                            if (!confirm(`¿Estás seguro de crear ${total} FAQs masivamente usando IA?\n\nEsto puede tardar varios minutos y consumirá créditos de la API.`)) {
                                                return;
                                            }

                                            // Setup UI
                                            const container = document.getElementById('bulkProgressContainer');
                                            const bar = document.getElementById('bulkProgressBar');
                                            const status = document.getElementById('bulkStatus');
                                            container.style.display = 'flex';
                                            status.style.display = 'block';
                                            
                                            const batchSize = 10;
                                            let processed = 0;
                                            
                                            // Extract items
                                            const items = [];
                                            allRows.forEach(btn => {
                                                items.push({
                                                    brand: btn.dataset.brand,
                                                    question: btn.dataset.question
                                                });
                                            });

                                            for (let i = 0; i < items.length; i += batchSize) {
                                                const batch = items.slice(i, i + batchSize);
                                                
                                                status.innerText = `Procesando ${i + 1} de ${items.length}...`;
                                                
                                                try {
                                                    const formData = new FormData();
                                                    batch.forEach((item, index) => {
                                                        formData.append(`items[${index}][brand]`, item.brand);
                                                        formData.append(`items[${index}][question]`, item.question);
                                                    });

                                                    const response = await fetch('../ajax/bulk_process_faqs.php', {
                                                        method: 'POST',
                                                        body: formData
                                                    });
                                                    const data = await response.json();

                                                    if (data.success) {
                                                       // Mark individual rows as created
                                                       batch.forEach(item => {
                                                           const btn = document.querySelector(`.btn-generate-faq[data-brand="${item.brand}"][data-question="${item.question}"]`);
                                                           if (btn) {
                                                               const row = btn.closest('tr');
                                                               row.classList.add('faq-row-created');
                                                               row.classList.remove('faq-row-pending');
                                                               btn.parentElement.innerHTML = '<span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i> Creada</span>';
                                                               
                                                               // Ocultar si el toggle está en modo ocultar
                                                               const toggleBtn = document.getElementById('toggleCreatedFaqsBtn');
                                                               if (toggleBtn && toggleBtn.dataset.hidden === "true") {
                                                                   row.style.display = 'none';
                                                               }
                                                           }
                                                       });
                                                    }
                                                } catch (e) {
                                                    console.error('Error en lote:', e);
                                                }

                                                processed += batch.length;
                                                const percent = (processed / total) * 100;
                                                bar.style.width = percent + '%';
                                            }

                                            status.innerText = '✅ Proceso masivo completado';
                                            setTimeout(() => {
                                                container.style.display = 'none';
                                                status.style.display = 'none';
                                            }, 5000);
                                        }

                                        function toggleCreatedFaqs() {
                                            const btn = document.getElementById('toggleCreatedFaqsBtn');
                                            const isHidden = btn.dataset.hidden === "true";
                                            const rows = document.querySelectorAll('.faq-row-created');
                                            
                                            rows.forEach(row => {
                                                row.style.display = isHidden ? 'table-row' : 'none';
                                            });
                                            
                                            if (isHidden) {
                                                btn.dataset.hidden = "false";
                                                btn.innerHTML = '<i class="fas fa-eye-slash me-1"></i> Ocultar Creadas';
                                                btn.classList.remove('btn-outline-primary');
                                                btn.classList.add('btn-primary');
                                            } else {
                                                btn.dataset.hidden = "true";
                                                btn.innerHTML = '<i class="fas fa-eye me-1"></i> Ver Creadas';
                                                btn.classList.remove('btn-primary');
                                                btn.classList.add('btn-outline-primary');
                                            }
                                        }

                                        /* Eliminado listeners individuales antiguos para evitar duplicados si se recarga dinámicamente */

                                        // Listener Botón "Auto-Fix 301" (Cannibalization)
                                        document.querySelectorAll('.auto-fix-btn').forEach(btn => {
                                            btn.addEventListener('click', async function() {
                                                const keyword = this.dataset.keyword;
                                                const pages = JSON.parse(this.dataset.pages);
                                                
                                                if (!confirm(`¿Crear redirecciones 301 automáticas para "${keyword}"?\n\nSe redirigirán ${pages.length - 1} página(s) a la más fuerte.`)) {
                                                    return;
                                                }
                                                
                                                const originalText = this.innerHTML;
                                                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                                                this.disabled = true;
                                                
                                                try {
                                                    const response = await fetch('../ajax/auto_fix_cannibalization.php', {
                                                        method: 'POST',
                                                        headers: { 'Content-Type': 'application/json' },
                                                        body: JSON.stringify({ keyword, pages })
                                                    });
                                                    
                                                    const data = await response.json();
                                                    
                                                    if (data.success) {
                                                        const modalHtml = `
                                                            <div class="modal fade" id="autoFixModal" tabindex="-1">
                                                                <div class="modal-dialog modal-lg">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header bg-success text-white">
                                                                            <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Auto-Fix Completado</h5>
                                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <div class="alert alert-success">
                                                                                <i class="fas fa-magic me-2"></i> ${data.message}
                                                                            </div>
                                                                            <h6 class="fw-bold mb-3">Página ganadora (más clicks):</h6>
                                                                            <div class="p-3 bg-light rounded mb-3">
                                                                                <i class="fas fa-trophy text-warning me-2"></i>
                                                                                <code>${data.winner}</code>
                                                                            </div>
                                                                            <h6 class="fw-bold mb-3">Redirecciones 301 creadas:</h6>
                                                                            <table class="table table-sm">
                                                                                <thead><tr><th>Desde</th><th></th><th>Hacia</th></tr></thead>
                                                                                <tbody>
                                                                                    ${data.redirects.map(r => `
                                                                                        <tr>
                                                                                            <td><code class="small">${r.from}</code></td>
                                                                                            <td class="text-center"><i class="fas fa-arrow-right text-success"></i></td>
                                                                                            <td><code class="small">${r.to}</code></td>
                                                                                        </tr>
                                                                                    `).join('')}
                                                                                </tbody>
                                                                            </table>
                                                                            <div class="alert alert-info mt-3">
                                                                                <i class="fas fa-info-circle me-2"></i>
                                                                                <strong>Siguiente paso:</strong> Implementa las redirecciones en tu servidor web (.htaccess o configuración de Nginx).
                                                                                <br>Archivo generado: <code>${data.htaccess_file}</code>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                                            <button type="button" class="btn btn-primary" onclick="location.reload()">Recargar Dashboard</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        `;
                                                        const oldModal = document.getElementById('autoFixModal');
                                                        if (oldModal) oldModal.remove();
                                                        document.body.insertAdjacentHTML('beforeend', modalHtml);
                                                        const modal = new bootstrap.Modal(document.getElementById('autoFixModal'));
                                                        modal.show();
                                                        
                                                        // Marcar como resuelto visualmente
                                                        this.classList.remove('btn-success');
                                                        this.classList.add('btn-secondary');
                                                        this.innerHTML = '<i class="fas fa-check me-1"></i> Resuelto';
                                                    } else {
                                                        alert('Error: ' + (data.error || 'No se pudo completar el auto-fix'));
                                                        this.innerHTML = originalText;
                                                        this.disabled = false;
                                                    }
                                                } catch (error) {
                                                    alert('Error de conexión: ' + error.message);
                                                    this.innerHTML = originalText;
                                                    this.disabled = false;
                                                }
                                            });
                                        });
    </script>
</body>
</html>
