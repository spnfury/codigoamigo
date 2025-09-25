<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

if (!in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

// Obtener estadísticas generales
$collection_usuarios = getCollectionUsuarios();
$collection_marcas = getCollectionMarcas();
$collection_codigos = getCollectionCodigos();
$collection_transacciones = getCollectionTransacciones();

// Estadísticas del dashboard
$total_usuarios = $collection_usuarios->countDocuments([]);
$total_marcas = $collection_marcas->countDocuments([]);
$total_codigos = $collection_codigos->countDocuments([]);
$total_codigos_activos = $collection_codigos->countDocuments(['estado' => 0]);
$total_codigos_destacados = $collection_codigos->countDocuments(['destacado' => 1]);
$total_codigos_destacados_premium = $collection_codigos->countDocuments(['destacado_social' => 1]);

// Fechas para estadísticas
$fecha_hoy = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
$fecha_30_dias = new MongoDB\BSON\UTCDateTime((time() - 30*24*60*60) * 1000);

// Usuarios nuevos hoy
$usuarios_nuevos_hoy = $collection_usuarios->countDocuments([
    'fecha_registro' => ['$gte' => $fecha_hoy]
]);

// Usuarios nuevos en los últimos 30 días
$usuarios_nuevos_30d = $collection_usuarios->countDocuments([
    'fecha_registro' => ['$gte' => $fecha_30_dias]
]);

// Códigos nuevos hoy (usando timestamp del ObjectId)
$timestamp_hoy = strtotime('today');
$objectId_hoy = new MongoDB\BSON\ObjectId(sprintf('%08x%s', $timestamp_hoy, str_repeat('0', 16)));
$codigos_nuevos_hoy = $collection_codigos->countDocuments([
    '_id' => ['$gte' => $objectId_hoy]
]);

// Códigos nuevos en los últimos 30 días (usando timestamp del ObjectId)
$timestamp_30_dias = time() - 30*24*60*60;
$objectId_30_dias = new MongoDB\BSON\ObjectId(sprintf('%08x%s', $timestamp_30_dias, str_repeat('0', 16)));
$codigos_nuevos_30d = $collection_codigos->countDocuments([
    '_id' => ['$gte' => $objectId_30_dias]
]);

// Códigos actualizados hoy (usando updated_at)
$codigos_actualizados_hoy = $collection_codigos->countDocuments([
    'updated_at' => ['$gte' => $fecha_hoy]
]);

// Códigos actualizados en los últimos 30 días
$codigos_actualizados_30d = $collection_codigos->countDocuments([
    'updated_at' => ['$gte' => $fecha_30_dias]
]);

// Saldo total de todos los usuarios
$pipeline_saldo = [
    ['$group' => [
        '_id' => null,
        'saldo_total' => ['$sum' => '$saldo']
    ]]
];
$resultado_saldo = $collection_usuarios->aggregate($pipeline_saldo);
$saldo_total = 0;
foreach ($resultado_saldo as $doc) {
    $saldo_total = $doc['saldo_total'] ?? 0;
}

// Transacciones recientes
$transacciones_recientes = $collection_transacciones->find(
    [],
    ['sort' => ['fecha' => -1], 'limit' => 10]
)->toArray();

// Usuarios más activos (con más códigos)
$pipeline_usuarios_activos = [
    ['$group' => [
        '_id' => '$id_usuario',
        'total_codigos' => ['$sum' => 1]
    ]],
    ['$sort' => ['total_codigos' => -1]],
    ['$limit' => 5]
];
$usuarios_activos = $collection_codigos->aggregate($pipeline_usuarios_activos)->toArray();

// Marcas más populares
$pipeline_marcas_populares = [
    ['$group' => [
        '_id' => '$marca',
        'total_codigos' => ['$sum' => 1]
    ]],
    ['$sort' => ['total_codigos' => -1]],
    ['$limit' => 10]
];
$marcas_populares = $collection_codigos->aggregate($pipeline_marcas_populares)->toArray();

$title = "Panel de Administración - Dashboard";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
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
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card-stat:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-cogs me-2"></i>Admin Panel
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="admin_dashboard.php">
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

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-admin">
                    <div class="container-fluid">
                        <h5 class="mb-0">Dashboard de Administración</h5>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">Bienvenido, <?php echo $_SESSION["username"] ?? 'Admin'; ?></span>
                            <a href="https://www.codigoamigo.com/logout" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Salir
                            </a>
                        </div>
                    </div>
                </nav>

                <!-- Dashboard Content -->
                <div class="p-4">
                    <!-- Estadísticas principales -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-primary fw-bold small">Total Usuarios</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_usuarios); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users stat-icon text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-success fw-bold small">Total Marcas</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_marcas); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tags stat-icon text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-info fw-bold small">Códigos Activos</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_codigos_activos); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-code stat-icon text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-warning fw-bold small">Saldo Total</div>
                                            <div class="h3 mb-0">€<?php echo number_format($saldo_total, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-euro-sign stat-icon text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas adicionales -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-danger fw-bold small">Códigos Destacados</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_codigos_destacados); ?></div>
                                            <small class="text-muted">Premium: <?php echo number_format($total_codigos_destacados_premium); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-star stat-icon text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-primary fw-bold small">Usuarios Nuevos Hoy</div>
                                            <div class="h3 mb-0"><?php echo number_format($usuarios_nuevos_hoy); ?></div>
                                            <small class="text-muted">30d: <?php echo number_format($usuarios_nuevos_30d); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-plus stat-icon text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-success fw-bold small">Códigos Nuevos Hoy</div>
                                            <div class="h3 mb-0"><?php echo number_format($codigos_nuevos_hoy); ?></div>
                                            <small class="text-muted">30d: <?php echo number_format($codigos_nuevos_30d); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-code-branch stat-icon text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-info fw-bold small">Total Códigos</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_codigos); ?></div>
                                            <small class="text-muted">Activos: <?php echo number_format($total_codigos_activos); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-list stat-icon text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas de actividad -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-secondary fw-bold small">Códigos Actualizados Hoy</div>
                                            <div class="h3 mb-0"><?php echo number_format($codigos_actualizados_hoy); ?></div>
                                            <small class="text-muted">30d: <?php echo number_format($codigos_actualizados_30d); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-edit stat-icon text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-dark fw-bold small">Actividad Total</div>
                                            <div class="h3 mb-0"><?php echo number_format($codigos_nuevos_hoy + $codigos_actualizados_hoy); ?></div>
                                            <small class="text-muted">Hoy (nuevos + actualizados)</small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line stat-icon text-dark"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido principal -->
                    <div class="row">
                        <!-- Transacciones recientes -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-credit-card me-2"></i>Transacciones Recientes
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Usuario</th>
                                                    <th>Tipo</th>
                                                    <th>Cantidad</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transacciones_recientes as $transaccion): ?>
                                                <tr>
                                                    <td><?php echo substr($transaccion['usuario_id'], 0, 8) . '...'; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $transaccion['tipo'] == 'recarga' ? 'success' : 'info'; ?>">
                                                            <?php echo ucfirst($transaccion['tipo']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="<?php echo $transaccion['cantidad'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                        €<?php echo number_format($transaccion['cantidad'], 2); ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', $transaccion['fecha']->toDateTime()->getTimestamp()); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Usuarios más activos -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-trophy me-2"></i>Usuarios Más Activos
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Usuario ID</th>
                                                    <th>Códigos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($usuarios_activos as $usuario): ?>
                                                <tr>
                                                    <td><?php echo substr($usuario['_id'], 0, 8) . '...'; ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $usuario['total_codigos']; ?></span>
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

                    <!-- Marcas más populares -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Marcas Más Populares
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Marca</th>
                                                    <th>Códigos</th>
                                                    <th>Progreso</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $max_codigos = !empty($marcas_populares) ? $marcas_populares[0]['total_codigos'] : 1;
                                                foreach ($marcas_populares as $marca): 
                                                    $porcentaje = ($marca['total_codigos'] / $max_codigos) * 100;
                                                ?>
                                                <tr>
                                                    <td><strong><?php echo ucfirst($marca['_id']); ?></strong></td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo $marca['total_codigos']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="progress" style="height: 8px;">
                                                            <div class="progress-bar bg-success" style="width: <?php echo $porcentaje; ?>%"></div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar DataTables si es necesario
            $('.table').DataTable({
                pageLength: 10,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                }
            });
        });
    </script>
    
<?php get_footer(); ?>
</body>
</html>


