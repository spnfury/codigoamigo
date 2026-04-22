<?php
session_start();

include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_sitemaps.php';
include_once __DIR__ . '/admin_sidebar_menu.php';

$array_codigos_acceso = [
    "58bd851da54e295b8b52f702",
    "5e78170e6b68e6519b7c5df2",
    "639899bc6321ee0d0e4010d2",
    "5c8a10ce2f55c86d6e707d82"
];

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$mensaje_exito = '';
$mensaje_error = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'regenerar_todos') {
        $opciones = [
            'incluir_codigos' => isset($_POST['incluir_codigos']) && $_POST['incluir_codigos'] === '1',
            'incluir_chollos' => isset($_POST['incluir_chollos']) && $_POST['incluir_chollos'] === '1',
            'limite_codigos' => intval($_POST['limite_codigos'] ?? 10000),
            'limite_chollos' => intval($_POST['limite_chollos'] ?? 5000)
        ];
        
        $resultados = generarTodosLosSitemaps($opciones);
        
        if ($resultados['resumen']['exitosos'] > 0) {
            $mensaje_exito = "Sitemaps regenerados exitosamente. " . 
                           $resultados['resumen']['exitosos'] . " sitemaps generados con " . 
                           $resultados['resumen']['total_urls'] . " URLs totales.";
        } else {
            $mensaje_error = "Error al regenerar sitemaps. Revisa los logs para más detalles.";
        }
    } elseif ($action === 'regenerar_individual') {
        $tipo = $_POST['tipo'] ?? '';
        $resultado = null;
        
        switch ($tipo) {
            case 'principal':
                $resultado = generarSitemapPrincipal(
                    isset($_POST['incluir_codigos']) && $_POST['incluir_codigos'] === '1'
                );
                break;
            case 'marcas':
                $resultado = generarSitemapMarcas();
                break;
            case 'categorias':
                $resultado = generarSitemapCategorias();
                break;
            case 'codigos':
                $limite = intval($_POST['limite_codigos'] ?? 10000);
                $resultado = generarSitemapCodigos($limite);
                break;
            case 'comparativas':
                $limite = intval($_POST['limite_comparativas'] ?? 3000);
                $resultado = generarSitemapComparativas($limite);
                break;
            case 'guias':
                $resultado = generarSitemapGuias();
                break;
            case 'estaticas':
                $resultado = generarSitemapEstaticas();
                break;
        }
        
        if ($resultado && isset($resultado['success']) && $resultado['success']) {
            registrarGeneracionSitemap($tipo, $resultado);
            $mensaje_exito = "Sitemap de " . $tipo . " regenerado exitosamente";
            if (isset($resultado['total_urls'])) {
                $mensaje_exito .= " (" . $resultado['total_urls'] . " URLs)";
            }
        } else {
            $mensaje_error = "Error al regenerar sitemap de " . $tipo . ": " . ($resultado['error'] ?? 'Error desconocido');
        }
    }
    
    if ($mensaje_exito || $mensaje_error) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$estadisticas = obtenerEstadisticasSitemaps();
$historial = obtenerHistorialSitemaps(20);

$page_title = "Gestión de Sitemaps";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .nav-link { color: rgba(255,255,255,0.8); padding: 0.75rem 1rem; border-radius: 0.25rem; margin-bottom: 0.25rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); margin-bottom: 1.5rem; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { opacity: 0.9; }
        .badge-success { background-color: #28a745; }
        .badge-danger { background-color: #dc3545; }
        .stat-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 0.5rem; padding: 1.5rem; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php echo get_admin_sidebar_menu('admin_sitemaps.php'); ?>
            
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4">
                <h1 class="mb-4"><i class="fas fa-sitemap me-2"></i><?php echo $page_title; ?></h1>
                
                <?php if ($mensaje_exito): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $mensaje_exito; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($mensaje_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $mensaje_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h5><i class="fas fa-file-code me-2"></i>Total URLs</h5>
                            <h2><?php echo number_format($estadisticas['total_urls'] ?? 0); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h5><i class="fas fa-calendar-check me-2"></i>Última Generación</h5>
                            <small><?php echo $estadisticas['ultima_generacion'] ?? 'Nunca'; ?></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <h5><i class="fas fa-check-circle me-2"></i>Sitemaps Activos</h5>
                            <h2><?php 
                                $activos = 0;
                                foreach ($estadisticas['sitemaps'] ?? [] as $sitemap) {
                                    if (isset($sitemap['existe']) && $sitemap['existe']) $activos++;
                                }
                                echo $activos;
                            ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <h5><i class="fas fa-history me-2"></i>Generaciones</h5>
                            <h2><?php echo count($historial); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sync me-2"></i>Regenerar Sitemaps</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="regenerar_todos">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="incluir_codigos" value="1" id="incluir_codigos">
                                        <label class="form-check-label" for="incluir_codigos">
                                            Incluir códigos en sitemap (puede ser muy grande)
                                        </label>
                                    </div>
                                    <div class="form-group mt-2">
                                        <label>Límite de códigos:</label>
                                        <input type="number" name="limite_codigos" value="10000" min="1000" max="50000" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Límite de comparativas (pares marca vs marca):</label>
                                        <input type="number" name="limite_comparativas" value="3000" min="100" max="10000" class="form-control">
                                    </div>
                                    <small class="text-muted">Las comparativas se generan automáticamente para marcas de la misma categoría.</small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sync me-2"></i>Regenerar Todos los Sitemaps
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Estado de Sitemaps</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>URLs</th>
                                        <th>Tamaño</th>
                                        <th>Última Modificación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $tipos_sitemaps = [
                                        'principal'    => ['nombre' => 'Principal (Index)', 'icon' => 'fa-home'],
                                        'marcas'       => ['nombre' => 'Marcas', 'icon' => 'fa-tags'],
                                        'categorias'   => ['nombre' => 'Categorías', 'icon' => 'fa-folder'],
                                        'comparativas' => ['nombre' => 'Comparativas', 'icon' => 'fa-chart-bar'],
                                        'guias'        => ['nombre' => 'Guías', 'icon' => 'fa-book'],
                                        'estaticas'    => ['nombre' => 'Páginas Estáticas', 'icon' => 'fa-file'],
                                        'codigos'      => ['nombre' => 'Códigos', 'icon' => 'fa-code'],
                                    ];
                                    
                                    foreach ($tipos_sitemaps as $tipo => $info):
                                        $sitemap = $estadisticas['sitemaps'][$tipo] ?? ['existe' => false];
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="fas <?php echo $info['icon']; ?> me-2"></i>
                                            <?php echo $info['nombre']; ?>
                                        </td>
                                        <td>
                                            <?php if ($sitemap['existe'] ?? false): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">No existe</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($sitemap['existe'] ?? false): ?>
                                                <?php echo number_format($sitemap['total_urls'] ?? 0); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($sitemap['existe'] ?? false): ?>
                                                <?php echo number_format(($sitemap['tamaño'] ?? 0) / 1024, 2); ?> KB
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $sitemap['fecha_modificacion'] ?? 'N/A'; ?>
                                        </td>
                                        <td>
                                            <?php if ($sitemap['existe'] ?? false && isset($sitemap['url'])): ?>
                                                <a href="<?php echo $sitemap['url']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt"></i> Ver
                                                </a>
                                            <?php endif; ?>
                                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('¿Regenerar sitemap de <?php echo $info['nombre']; ?>?');">
                                                <input type="hidden" name="action" value="regenerar_individual">
                                                <input type="hidden" name="tipo" value="<?php echo $tipo; ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-sync"></i> Regenerar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Generaciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Resultado</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($historial)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No hay historial de generaciones</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($historial as $log): ?>
                                        <tr>
                                            <td><?php echo $log['fecha']; ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo ucfirst($log['tipo']); ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $resultado = $log['resultado'] ?? [];
                                                if (isset($resultado['resumen'])) {
                                                    echo $resultado['resumen']['exitosos'] . " exitosos, " . 
                                                         $resultado['resumen']['total_urls'] . " URLs";
                                                } elseif (isset($resultado['success']) && $resultado['success']) {
                                                    echo "✓ Exitoso";
                                                    if (isset($resultado['total_urls'])) {
                                                        echo " (" . $resultado['total_urls'] . " URLs)";
                                                    }
                                                } else {
                                                    echo "✗ Error";
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo $log['usuario_id'] ?? 'Sistema'; ?></td>
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
