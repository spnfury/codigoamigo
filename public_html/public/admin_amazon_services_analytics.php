<?php
// admin_amazon_services_analytics.php
// Panel de métricas para Servicios Amazon

session_start();

include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_amazon_services.php';
include_once __DIR__ . '/admin_sidebar_menu.php';

// Auth check
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; 
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; 
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; 
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82";

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

// Filtro fecha (Similar a tracking existente)
$filtro_fecha = $_GET['fecha'] ?? 'ultima_semana';
$fecha_inicio = new DateTime();
$fecha_fin = new DateTime();

switch ($filtro_fecha) {
    case 'hoy':
        $fecha_inicio->setTime(0, 0, 0);
        $fecha_fin->setTime(23, 59, 59);
        break;
    case 'ayer':
        $fecha_inicio->modify('-1 day')->setTime(0, 0, 0);
        $fecha_fin->modify('-1 day')->setTime(23, 59, 59);
        break;
    case 'ultima_semana':
        $fecha_inicio->modify('-7 days')->setTime(0, 0, 0);
        $fecha_fin->setTime(23, 59, 59);
        break;
    case 'ultimo_mes':
        $fecha_inicio->modify('-30 days')->setTime(0, 0, 0);
        $fecha_fin->setTime(23, 59, 59);
        break;
}

// Obtener Clicks
$collectionClicks = getCollectionAffiliateClicks();
$condiciones = [
    'created_at' => [
        '$gte' => new MongoDB\BSON\UTCDateTime($fecha_inicio->getTimestamp() * 1000),
        '$lte' => new MongoDB\BSON\UTCDateTime($fecha_fin->getTimestamp() * 1000)
    ]
];

$clicks = $collectionClicks->find($condiciones, ['sort' => ['created_at' => -1]])->toArray();

// Procesar datos
$total_clicks = 0;
$clicks_por_slug = [];
$clicks_por_dia = [];
$clicks_por_device = ['desktop' => 0, 'mobile' => 0];

// Array de fechas para gráfica
$period = new DatePeriod(
    $fecha_inicio,
    new DateInterval('P1D'),
    $fecha_fin->modify('+1 second') // Incluir fin
);

foreach ($period as $dt) {
    $clicks_por_dia[$dt->format('Y-m-d')] = 0;
}

foreach ($clicks as $click) {
    if ($click['slug'] === 'view_section_amazon') continue; // Ignorar vistas generales para conteo de clicks de servicio
    
    $total_clicks++;
    
    // Por servicio
    $slug = $click['slug'];
    if (!isset($clicks_por_slug[$slug])) $clicks_por_slug[$slug] = 0;
    $clicks_por_slug[$slug]++;
    
    // Por fecha
    $fecha = $click['created_at']->toDateTime()->format('Y-m-d');
    if (isset($clicks_por_dia[$fecha])) {
        $clicks_por_dia[$fecha]++;
    }
    
    // Por dispositivo
    $device = isset($click['device']) ? $click['device'] : 'desktop';
    if (isset($clicks_por_device[$device])) $clicks_por_device[$device]++;
    else $clicks_por_device[$device] = 1;
}

// Vistas de la landing page
$visits_cond = $condiciones;
$visits_cond['slug'] = 'view_section_amazon';
$visitas_total = $collectionClicks->countDocuments($visits_cond);

// CTR Global
$ctr = $visitas_total > 0 ? round(($total_clicks / $visitas_total) * 100, 2) : 0;

arsort($clicks_por_slug); // Ordenar por más clicks

// Nombres de servicios map
$collectionLinks = getCollectionAffiliateLinks();
$links = $collectionLinks->find([], ['projection' => ['slug' => 1, 'title' => 1]])->toArray();
$slug_map = [];
foreach ($links as $link) {
    $slug_map[$link['slug']] = $link['title'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Analytics Amazon Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .wrapper { display: flex; width: 100%; align-items: stretch; }
        .sidebar { min-width: 250px; max-width: 250px; min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; }
        .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,0.1); }
        .main-content { width: 100%; background: #f8f9fa; min-height: 100vh; padding: 20px; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .stat-value { font-size: 2rem; font-weight: bold; color: #2c3e50; }
        .stat-label { color: #7f8c8d; text-transform: uppercase; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="wrapper">
        <nav class="sidebar">
            <div class="p-4"><h3>Admin Panel</h3></div>
            <?php echo get_admin_sidebar_menu('admin_amazon_services.php'); ?>
        </nav>

        <div class="main-content">
            <div class="d-flex justify-content-between mb-4">
                <h2><i class="fas fa-chart-line text-primary me-2"></i>Analytics Amazon Services</h2>
                <form class="d-flex gap-2">
                    <select name="fecha" class="form-select" onchange="this.form.submit()">
                        <option value="hoy" <?php echo $filtro_fecha=='hoy'?'selected':'';?>>Hoy</option>
                        <option value="ayer" <?php echo $filtro_fecha=='ayer'?'selected':'';?>>Ayer</option>
                        <option value="ultima_semana" <?php echo $filtro_fecha=='ultima_semana'?'selected':'';?>>Última Semana</option>
                        <option value="ultimo_mes" <?php echo $filtro_fecha=='ultimo_mes'?'selected':'';?>>Último Mes</option>
                    </select>
                    <a href="admin_amazon_services.php" class="btn btn-outline-secondary">Gestión</a>
                </form>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Visitas Landing</div>
                        <div class="stat-value text-primary"><?php echo number_format($visitas_total); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Total Clics (Servicios)</div>
                        <div class="stat-value text-success"><?php echo number_format($total_clicks); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">CTR Global</div>
                        <div class="stat-value text-warning"><?php echo $ctr; ?>%</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">Mobile vs Desktop</div>
                        <div class="stat-value text-info" style="font-size: 1.2rem;">
                            <i class="fas fa-mobile-alt"></i> <?php echo $clicks_por_device['mobile']; ?> | 
                            <i class="fas fa-desktop"></i> <?php echo $clicks_por_device['desktop']; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white"><strong>Evolución de Clics</strong></div>
                        <div class="card-body">
                            <canvas id="chartClicks"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white"><strong>Ranking Servicios</strong></div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php $i=0; foreach($clicks_por_slug as $slug => $count): if($i++ > 9) break; ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-light text-dark me-2"><?php echo $i; ?></span>
                                        <?php echo htmlspecialchars($slug_map[$slug] ?? $slug); ?>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo $count; ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-white"><strong>Registro Detallado (Últimos 50)</strong></div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped mb-0 text-small">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Servicio</th>
                                <th>Origen</th>
                                <th>Referer</th>
                                <th>Dispositivo</th>
                                <th>IP Hash</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(array_slice($clicks, 0, 50) as $cl): ?>
                            <tr>
                                <td><?php echo $cl['created_at']->toDateTime()->setTimezone(new DateTimeZone('Europe/Madrid'))->format('d/m/Y H:i:s'); ?></td>
                                <td>
                                    <?php if($cl['slug'] === 'view_section_amazon'): ?>
                                        <span class="badge bg-secondary">Page View</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?php echo $cl['slug']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($cl['page_origin'] ?? '-'); ?></td>
                                <td><small class="text-muted"><?php echo substr(htmlspecialchars($cl['referrer'] ?? ''), 0, 40); ?></small></td>
                                <td><?php echo $cl['device'] ?? '-'; ?></td>
                                <td><small class="text-muted"><?php echo substr($cl['ip_hash'] ?? '', 0, 8); ?>...</small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        const ctx = document.getElementById('chartClicks');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($clicks_por_dia)); ?>,
                datasets: [{
                    label: 'Clics Diarios',
                    data: <?php echo json_encode(array_values($clicks_por_dia)); ?>,
                    borderColor: '#764ba2',
                    tension: 0.1,
                    fill: true,
                    backgroundColor: 'rgba(118, 75, 162, 0.1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
</body>
</html>
