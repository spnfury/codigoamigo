<?php
session_start();

// Verificar permisos de administrador
$admin_ids = [
    '58bd851da54e295b8b52f702', // thevega82@gmail.com
    '5e78170e6b68e6519b7c5df2', // edna
    '639899bc6321ee0d0e4010d2', // aron
    '5c8a10ce2f55c86d6e707d82'  // jose
];

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_id'], $admin_ids)) {
    header('Location: login.php');
    exit;
}

// Incluir funciones
include_once 'myphp/funciones_afiliados.php';
include_once 'myphp/funciones_usuario.php';

// Obtener estadísticas generales
$collection_afiliados = getCollectionAfiliados();
$collection_ingresos = getCollectionIngresosAfiliados();

// Estadísticas generales
$total_urls = $collection_afiliados->countDocuments(['activo' => true]);
$total_ingresos = $collection_ingresos->aggregate([
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$monto']]]
])->toArray();
$total_ingresos = !empty($total_ingresos) ? $total_ingresos[0]['total'] : 0;

$total_registros = $collection_ingresos->countDocuments();

// Obtener URLs más populares
$urls_populares = $collection_afiliados->aggregate([
    ['$match' => ['activo' => true]],
    ['$lookup' => [
        'from' => 'afiliados_ingresos',
        'localField' => '_id',
        'foreignField' => 'url_id',
        'as' => 'ingresos'
    ]],
    ['$addFields' => [
        'total_ingresos' => ['$sum' => '$ingresos.monto'],
        'total_registros' => ['$size' => '$ingresos']
    ]],
    ['$sort' => ['total_ingresos' => -1]],
    ['$limit' => 10]
])->toArray();

// Obtener usuarios más activos
$usuarios_activos = $collection_ingresos->aggregate([
    ['$group' => [
        '_id' => '$usuario_id',
        'total_ingresos' => ['$sum' => '$monto'],
        'total_registros' => ['$sum' => 1]
    ]],
    ['$sort' => ['total_ingresos' => -1]],
    ['$limit' => 10]
])->toArray();

// Obtener ingresos por mes
$ingresos_mensuales = $collection_ingresos->aggregate([
    ['$group' => [
        '_id' => ['$dateToString' => ['format' => '%Y-%m', 'date' => '$fecha_periodo']],
        'total' => ['$sum' => '$monto'],
        'count' => ['$sum' => 1]
    ]],
    ['$sort' => ['_id' => -1]],
    ['$limit' => 12]
])->toArray();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - URLs de Afiliados - CodigoAmigo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .table-modern {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .badge-platform {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark gradient-bg">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-cog me-2"></i>Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="afiliados.php">
                    <i class="fas fa-link me-1"></i>Mis URLs
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-cogs me-2"></i>Admin Panel
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="admin_usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuarios
                        </a>
                        <a class="nav-link" href="admin_marcas.php">
                            <i class="fas fa-tags me-2"></i>Marcas
                        </a>
                        <a class="nav-link" href="admin_codigos.php">
                            <i class="fas fa-code me-2"></i>Códigos
                        </a>
                        <a class="nav-link" href="admin_transacciones.php">
                            <i class="fas fa-credit-card me-2"></i>Transacciones
                        </a>
                        <a class="nav-link active" href="admin_afiliados.php">
                            <i class="fas fa-link me-2"></i>URLs de Afiliados
                        </a>
                        <a class="nav-link" href="admin_reportes.php">
                            <i class="fas fa-chart-bar me-2"></i>Reportes
                        </a>
                        <a class="nav-link" href="admin_configuracion.php">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </a>
                        <a class="nav-link" href="admin_logs.php">
                            <i class="fas fa-file-alt me-2"></i>Logs
                        </a>
                        <hr class="text-white">
                        <a class="nav-link" href="https://www.codigoamigo.com">
                            <i class="fas fa-home me-2"></i>Volver al sitio
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-chart-line me-2"></i>Administración - URLs de Afiliados</h2>
                <p class="text-muted">Gestión completa del sistema de URLs de afiliados</p>
            </div>
        </div>

        <!-- Estadísticas Generales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-link fa-2x mb-2"></i>
                        <h4><?php echo number_format($total_urls); ?></h4>
                        <small>Total URLs Activas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h4><?php echo number_format($total_ingresos, 2); ?>€</h4>
                        <small>Total Ingresos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar fa-2x mb-2"></i>
                        <h4><?php echo number_format($total_registros); ?></h4>
                        <small>Total Registros</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h4><?php echo count($usuarios_activos); ?></h4>
                        <small>Usuarios Activos</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Ingresos Mensuales -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-hover">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2"></i>Ingresos por Mes</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ingresosChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- URLs Más Populares -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card card-hover">
                    <div class="card-header">
                        <h5><i class="fas fa-trophy me-2"></i>URLs Más Populares</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($urls_populares)): ?>
                            <p class="text-muted text-center">No hay datos disponibles</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Plataforma</th>
                                            <th>Ingresos</th>
                                            <th>Registros</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($urls_populares as $url): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-platform">
                                                        <?php echo htmlspecialchars($url['nombre_plataforma']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($url['total_ingresos'], 2); ?>€</td>
                                                <td><?php echo $url['total_registros']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Usuarios Más Activos -->
            <div class="col-md-6">
                <div class="card card-hover">
                    <div class="card-header">
                        <h5><i class="fas fa-users me-2"></i>Usuarios Más Activos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($usuarios_activos)): ?>
                            <p class="text-muted text-center">No hay datos disponibles</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Usuario ID</th>
                                            <th>Ingresos</th>
                                            <th>Registros</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios_activos as $usuario): ?>
                                            <tr>
                                                <td>
                                                    <code><?php echo substr($usuario['_id'], 0, 8); ?>...</code>
                                                </td>
                                                <td><?php echo number_format($usuario['total_ingresos'], 2); ?>€</td>
                                                <td><?php echo $usuario['total_registros']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Todas las URLs -->
        <div class="row">
            <div class="col-12">
                <div class="card table-modern">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Todas las URLs de Afiliados</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Plataforma</th>
                                        <th>URL</th>
                                        <th>Marcas</th>
                                        <th>Ingresos</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $todas_urls = $collection_afiliados->find(['activo' => true], [
                                        'sort' => ['fecha_creacion' => -1],
                                        'limit' => 50
                                    ]);
                                    
                                    foreach ($todas_urls as $url):
                                        // Obtener ingresos de esta URL
                                        $ingresos_url = $collection_ingresos->aggregate([
                                            ['$match' => ['url_id' => $url['_id']]],
                                            ['$group' => ['_id' => null, 'total' => ['$sum' => '$monto']]]
                                        ])->toArray();
                                        $total_ingresos_url = !empty($ingresos_url) ? $ingresos_url[0]['total'] : 0;
                                    ?>
                                        <tr>
                                            <td>
                                                <code><?php echo substr($url['usuario_id'], 0, 8); ?>...</code>
                                            </td>
                                            <td>
                                                <span class="badge badge-platform">
                                                    <?php echo htmlspecialchars($url['nombre_plataforma']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($url['url']); ?>" target="_blank" class="text-decoration-none">
                                                    <?php echo htmlspecialchars(substr($url['url'], 0, 40)) . (strlen($url['url']) > 40 ? '...' : ''); ?>
                                                    <i class="fas fa-external-link-alt ms-1"></i>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if (!empty($url['marcas'])): ?>
                                                    <?php foreach (array_slice($url['marcas'], 0, 3) as $marca): ?>
                                                        <span class="badge bg-secondary me-1">
                                                            <?php echo htmlspecialchars($marca['nombre']); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($url['marcas']) > 3): ?>
                                                        <span class="badge bg-light text-dark">+<?php echo count($url['marcas']) - 3; ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin marcas</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo number_format($total_ingresos_url, 2); ?>€</strong>
                                            </td>
                                            <td>
                                                <?php echo $url['fecha_creacion']->toDateTime()->format('d/m/Y'); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="verDetalles('<?php echo $url['_id']; ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-warning" onclick="editarUrl('<?php echo $url['_id']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="eliminarUrl('<?php echo $url['_id']; ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
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
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="detallesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Detalles de URL
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detallesContent">
                    <!-- Contenido cargado dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de ingresos mensuales
        const ingresosData = <?php echo json_encode($ingresos_mensuales); ?>;
        
        const ctx = document.getElementById('ingresosChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ingresosData.map(item => item._id).reverse(),
                datasets: [{
                    label: 'Ingresos (€)',
                    data: ingresosData.map(item => item.total).reverse(),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Funciones auxiliares
        function verDetalles(urlId) {
            // Implementar vista de detalles
            alert('Función de detalles en desarrollo para URL: ' + urlId);
        }

        function editarUrl(urlId) {
            // Implementar edición
            alert('Función de edición en desarrollo para URL: ' + urlId);
        }

        function eliminarUrl(urlId) {
            if (confirm('¿Estás seguro de que quieres eliminar esta URL?')) {
                // Implementar eliminación
                alert('Función de eliminación en desarrollo para URL: ' + urlId);
            }
        }
    </script>
</body>
</html>
