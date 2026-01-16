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
    $authJsonPath = __DIR__ . '/../private/google_credentials.json';
    
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
    $cannibalization = $seoService->getCannibalizationIssues($siteUrl, $startDate, $endDate);

    if (isset($performanceRows['error'])) {
        throw new Exception("Error API Google: " . $performanceRows['error']);
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
                                                        <td><?php echo htmlspecialchars($row->keys[0]); ?></td>
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
                                                            <td class="fw-bold"><?php echo htmlspecialchars($row->keys[0]); ?></td>
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
                                                            <td><?php echo htmlspecialchars($row->keys[0]); ?></td>
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

                                        // Listener Botón "Nuevo Título" (Low CTR) -> Ahora hace optimización completa
                                        document.querySelectorAll('.btn-optimize-title').forEach(btn => {
                                            btn.addEventListener('click', function() {
                                                autoOptimizeWeb(this, this.dataset.keyword, this.dataset.brand);
                                            });
                                        });

                                        // Listener Botón "Optimizar IA" (Low Hanging Fruit) -> Ahora hace optimización completa
                                        document.querySelectorAll('.btn-optimize').forEach(btn => {
                                            btn.addEventListener('click', function() {
                                                autoOptimizeWeb(this, this.dataset.keyword, this.dataset.brand);
                                            });
                                        });

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
