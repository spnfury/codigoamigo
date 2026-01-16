<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';
include_once __DIR__ . '/admin_sidebar_menu.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$collection_usuarios = getCollectionUsuarios();
$collection_marcas = getCollectionMarcas();
$collection_codigos = getCollectionCodigos();
$collection_transacciones = getCollectionTransacciones();

// Obtener filtros de fecha
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy

// Convertir fechas a MongoDB UTCDateTime
$fecha_inicio_timestamp = strtotime($fecha_inicio . ' 00:00:00') * 1000;
$fecha_fin_timestamp = strtotime($fecha_fin . ' 23:59:59') * 1000;

$fecha_inicio_mongo = new MongoDB\BSON\UTCDateTime($fecha_inicio_timestamp);
$fecha_fin_mongo = new MongoDB\BSON\UTCDateTime($fecha_fin_timestamp);

// Estadísticas generales
$estadisticas = [
    'total_usuarios' => $collection_usuarios->countDocuments([]),
    'usuarios_nuevos' => $collection_usuarios->countDocuments([
        'fecha_registro' => ['$gte' => $fecha_inicio_mongo, '$lte' => $fecha_fin_mongo]
    ]),
    'total_marcas' => $collection_marcas->countDocuments([]),
    'marcas_nuevas' => $collection_marcas->countDocuments([
        'fecha_publicacion' => ['$gte' => $fecha_inicio, '$lte' => $fecha_fin]
    ]),
    'total_codigos' => $collection_codigos->countDocuments([]),
    'codigos_nuevos' => $collection_codigos->countDocuments([
        'fecha_creacion' => ['$gte' => $fecha_inicio_mongo, '$lte' => $fecha_fin_mongo]
    ]),
    'codigos_activos' => $collection_codigos->countDocuments(['estado' => 0]),
    'codigos_destacados' => $collection_codigos->countDocuments(['destacado' => 1])
];

// Saldo total de usuarios
$pipeline_saldo = [
    ['$group' => [
        '_id' => null,
        'saldo_total' => ['$sum' => '$saldo'],
        'usuarios_con_saldo' => ['$sum' => ['$cond' => [['$gt' => ['$saldo', 0]], 1, 0]]]
    ]]
];
$resultado_saldo = $collection_usuarios->aggregate($pipeline_saldo)->toArray();
$saldo_total = $resultado_saldo[0]['saldo_total'] ?? 0;
$usuarios_con_saldo = $resultado_saldo[0]['usuarios_con_saldo'] ?? 0;

// Transacciones del período
$transacciones_periodo = $collection_transacciones->countDocuments([
    'fecha' => ['$gte' => $fecha_inicio_mongo, '$lte' => $fecha_fin_mongo]
]);

// Ingresos del período
$pipeline_ingresos = [
    ['$match' => [
        'fecha' => ['$gte' => $fecha_inicio_mongo, '$lte' => $fecha_fin_mongo],
        'tipo' => 'recarga',
        'cantidad' => ['$gt' => 0]
    ]],
    ['$group' => [
        '_id' => null,
        'total_ingresos' => ['$sum' => '$cantidad']
    ]]
];
$resultado_ingresos = $collection_transacciones->aggregate($pipeline_ingresos)->toArray();
$ingresos_periodo = $resultado_ingresos[0]['total_ingresos'] ?? 0;

// Usuarios más activos (con más códigos)
$pipeline_usuarios_activos = [
    ['$group' => [
        '_id' => '$id_usuario',
        'total_codigos' => ['$sum' => 1],
        'codigos_activos' => ['$sum' => ['$cond' => [['$eq' => ['$estado', 0]], 1, 0]]],
        'codigos_destacados' => ['$sum' => ['$cond' => [['$eq' => ['$destacado', 1]], 1, 0]]]
    ]],
    ['$sort' => ['total_codigos' => -1]],
    ['$limit' => 10]
];
$usuarios_activos = $collection_codigos->aggregate($pipeline_usuarios_activos)->toArray();

// Enriquecer con datos de usuario
foreach ($usuarios_activos as &$usuario) {
    $usuario_data = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario['_id'])]);
    $usuario['username'] = $usuario_data['username'] ?? 'Usuario';
    $usuario['mail'] = $usuario_data['mail'] ?? '';
    $usuario['saldo'] = $usuario_data['saldo'] ?? 0;
}

// Marcas más populares
$pipeline_marcas_populares = [
    ['$group' => [
        '_id' => '$marca',
        'total_codigos' => ['$sum' => 1],
        'codigos_activos' => ['$sum' => ['$cond' => [['$eq' => ['$estado', 0]], 1, 0]]],
        'codigos_destacados' => ['$sum' => ['$cond' => [['$eq' => ['$destacado', 1]], 1, 0]]]
    ]],
    ['$sort' => ['total_codigos' => -1]],
    ['$limit' => 15]
];
$marcas_populares = $collection_codigos->aggregate($pipeline_marcas_populares)->toArray();

// Códigos más vistos
$pipeline_codigos_vistos = [
    ['$match' => ['totalclicks' => ['$gt' => 0]]],
    ['$sort' => ['totalclicks' => -1]],
    ['$limit' => 10],
    ['$lookup' => [
        'from' => 'usuarios',
        'localField' => 'id_usuario',
        'foreignField' => '_id',
        'as' => 'usuario'
    ]]
];
$codigos_vistos = $collection_codigos->aggregate($pipeline_codigos_vistos)->toArray();

// Estadísticas por día (últimos 30 días)
$estadisticas_diarias = [];
for ($i = 29; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $fecha_inicio_dia = strtotime($fecha . ' 00:00:00') * 1000;
    $fecha_fin_dia = strtotime($fecha . ' 23:59:59') * 1000;
    
    $usuarios_dia = $collection_usuarios->countDocuments([
        'fecha_registro' => [
            '$gte' => new MongoDB\BSON\UTCDateTime($fecha_inicio_dia),
            '$lte' => new MongoDB\BSON\UTCDateTime($fecha_fin_dia)
        ]
    ]);
    
    $codigos_dia = $collection_codigos->countDocuments([
        'fecha_creacion' => [
            '$gte' => new MongoDB\BSON\UTCDateTime($fecha_inicio_dia),
            '$lte' => new MongoDB\BSON\UTCDateTime($fecha_fin_dia)
        ]
    ]);
    
    $estadisticas_diarias[] = [
        'fecha' => $fecha,
        'usuarios' => $usuarios_dia,
        'codigos' => $codigos_dia
    ];
}

// Transacciones recientes
$transacciones_recientes = $collection_transacciones->find(
    [],
    ['sort' => ['fecha' => -1], 'limit' => 20]
)->toArray();

// Enriquecer transacciones con datos de usuario
foreach ($transacciones_recientes as &$transaccion) {
    $usuario_data = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($transaccion['usuario_id'])]);
    $transaccion['username'] = $usuario_data['username'] ?? 'Usuario';
}

$title = "Reportes y Estadísticas - Panel de Administración";
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card-stat:hover {
            transform: translateY(-2px);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo get_admin_sidebar_menu('admin_reportes.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-admin">
                    <div class="container-fluid">
                        <h5 class="mb-0">Reportes y Estadísticas</h5>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">Bienvenido, <?php echo $_SESSION["username"] ?? 'Admin'; ?></span>
                            <a href="https://www.codigoamigo.com/logout" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Salir
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="p-4">
                    <!-- Filtros de fecha -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Filtros de Período</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Fin</label>
                                    <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter me-1"></i>Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Estadísticas principales -->
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-primary"><?php echo number_format($estadisticas['total_usuarios']); ?></h3>
                                    <p class="text-muted mb-0">Total Usuarios</p>
                                    <small class="text-success">+<?php echo number_format($estadisticas['usuarios_nuevos']); ?> en período</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo number_format($estadisticas['total_marcas']); ?></h3>
                                    <p class="text-muted mb-0">Total Marcas</p>
                                    <small class="text-success">+<?php echo number_format($estadisticas['marcas_nuevas']); ?> en período</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-info"><?php echo number_format($estadisticas['total_codigos']); ?></h3>
                                    <p class="text-muted mb-0">Total Códigos</p>
                                    <small class="text-success">+<?php echo number_format($estadisticas['codigos_nuevos']); ?> en período</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-warning"><?php echo number_format($estadisticas['codigos_activos']); ?></h3>
                                    <p class="text-muted mb-0">Códigos Activos</p>
                                    <small class="text-info"><?php echo number_format(($estadisticas['codigos_activos'] / max($estadisticas['total_codigos'], 1)) * 100, 1); ?>% del total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-danger"><?php echo number_format($estadisticas['codigos_destacados']); ?></h3>
                                    <p class="text-muted mb-0">Destacados</p>
                                    <small class="text-info"><?php echo number_format(($estadisticas['codigos_destacados'] / max($estadisticas['total_codigos'], 1)) * 100, 1); ?>% del total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-secondary">€<?php echo number_format($saldo_total, 2); ?></h3>
                                    <p class="text-muted mb-0">Saldo Total</p>
                                    <small class="text-info"><?php echo number_format($usuarios_con_saldo); ?> usuarios con saldo</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de evolución diaria -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Evolución Diaria (Últimos 30 días)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="evolucionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas financieras -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Estadísticas Financieras</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h4 class="text-success">€<?php echo number_format($ingresos_periodo, 2); ?></h4>
                                            <p class="text-muted mb-0">Ingresos en período</p>
                                        </div>
                                        <div class="col-6">
                                            <h4 class="text-info"><?php echo number_format($transacciones_periodo); ?></h4>
                                            <p class="text-muted mb-0">Transacciones en período</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Promedios</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h4 class="text-primary"><?php echo number_format($estadisticas['total_codigos'] / max($estadisticas['total_usuarios'], 1), 1); ?></h4>
                                            <p class="text-muted mb-0">Códigos por usuario</p>
                                        </div>
                                        <div class="col-6">
                                            <h4 class="text-warning">€<?php echo number_format($saldo_total / max($usuarios_con_saldo, 1), 2); ?></h4>
                                            <p class="text-muted mb-0">Saldo promedio</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tablas de rankings -->
                    <div class="row">
                        <!-- Usuarios más activos -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Usuarios Más Activos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Usuario</th>
                                                    <th>Total</th>
                                                    <th>Activos</th>
                                                    <th>Destacados</th>
                                                    <th>Saldo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($usuarios_activos as $usuario): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($usuario['username']); ?></strong>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($usuario['mail']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge bg-primary"><?php echo $usuario['total_codigos']; ?></span></td>
                                                    <td><span class="badge bg-success"><?php echo $usuario['codigos_activos']; ?></span></td>
                                                    <td><span class="badge bg-warning"><?php echo $usuario['codigos_destacados']; ?></span></td>
                                                    <td class="text-success">€<?php echo number_format($usuario['saldo'], 2); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Marcas más populares -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Marcas Más Populares</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Marca</th>
                                                    <th>Total</th>
                                                    <th>Activos</th>
                                                    <th>Destacados</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($marcas_populares as $marca): ?>
                                                <tr>
                                                    <td><strong><?php echo ucfirst($marca['_id']); ?></strong></td>
                                                    <td><span class="badge bg-primary"><?php echo $marca['total_codigos']; ?></span></td>
                                                    <td><span class="badge bg-success"><?php echo $marca['codigos_activos']; ?></span></td>
                                                    <td><span class="badge bg-warning"><?php echo $marca['codigos_destacados']; ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Códigos más vistos -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Códigos Más Vistos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Usuario</th>
                                                    <th>Marca</th>
                                                    <th>Código</th>
                                                    <th>Vistas</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($codigos_vistos as $codigo): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($codigo['usuario'])): ?>
                                                            <strong><?php echo htmlspecialchars($codigo['usuario'][0]['username'] ?? 'Usuario'); ?></strong>
                                                        <?php else: ?>
                                                            <span class="text-muted">Usuario eliminado</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><span class="badge bg-info"><?php echo ucfirst($codigo['marca']); ?></span></td>
                                                    <td><code><?php echo htmlspecialchars($codigo['codigo']); ?></code></td>
                                                    <td><span class="badge bg-primary"><?php echo $codigo['totalclicks']; ?></span></td>
                                                    <td>
                                                        <?php if (($codigo['estado'] ?? 0) == 0): ?>
                                                            <span class="badge bg-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactivo</span>
                                                        <?php endif; ?>
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

                    <!-- Transacciones recientes -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Transacciones Recientes</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Usuario</th>
                                                    <th>Tipo</th>
                                                    <th>Cantidad</th>
                                                    <th>Descripción</th>
                                                    <th>Fecha</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transacciones_recientes as $transaccion): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($transaccion['username']); ?></strong>
                                                        <br><small class="text-muted"><?php echo substr($transaccion['usuario_id'], 0, 8) . '...'; ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $transaccion['tipo'] == 'recarga' ? 'success' : 'info'; ?>">
                                                            <?php echo ucfirst($transaccion['tipo']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="<?php echo $transaccion['cantidad'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                        €<?php echo number_format($transaccion['cantidad'], 2); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($transaccion['descripcion'] ?? ''); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', $transaccion['fecha']->toDateTime()->getTimestamp()); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $transaccion['estado'] == 'completada' ? 'success' : 'warning'; ?>">
                                                            <?php echo ucfirst($transaccion['estado']); ?>
                                                        </span>
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
        // Gráfico de evolución diaria
        const ctx = document.getElementById('evolucionChart').getContext('2d');
        const evolucionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [
                    <?php foreach ($estadisticas_diarias as $dia): ?>
                    '<?php echo date('d/m', strtotime($dia['fecha'])); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Usuarios Nuevos',
                    data: [
                        <?php foreach ($estadisticas_diarias as $dia): ?>
                        <?php echo $dia['usuarios']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Códigos Nuevos',
                    data: [
                        <?php foreach ($estadisticas_diarias as $dia): ?>
                        <?php echo $dia['codigos']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Inicializar DataTables
        $(document).ready(function() {
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


