<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

if (!in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$collection_transacciones = getCollectionTransacciones();
$collection_usuarios = getCollectionUsuarios();

// Obtener filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_fecha_inicio = $_GET['fecha_inicio'] ?? '';
$filtro_fecha_fin = $_GET['fecha_fin'] ?? '';
$filtro_cantidad_min = $_GET['cantidad_min'] ?? '';
$filtro_cantidad_max = $_GET['cantidad_max'] ?? '';

// Construir filtros para la consulta
$filtros = [];

if ($filtro_tipo) {
    $filtros['tipo'] = $filtro_tipo;
}

if ($filtro_estado) {
    $filtros['estado'] = $filtro_estado;
}

if ($filtro_usuario) {
    $filtros['usuario_id'] = $filtro_usuario;
}

if ($filtro_fecha_inicio || $filtro_fecha_fin) {
    $filtros['fecha'] = [];
    if ($filtro_fecha_inicio) {
        $filtros['fecha']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($filtro_fecha_inicio . ' 00:00:00') * 1000);
    }
    if ($filtro_fecha_fin) {
        $filtros['fecha']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($filtro_fecha_fin . ' 23:59:59') * 1000);
    }
}

if ($filtro_cantidad_min !== '' || $filtro_cantidad_max !== '') {
    $filtros['cantidad'] = [];
    if ($filtro_cantidad_min !== '') {
        $filtros['cantidad']['$gte'] = (float)$filtro_cantidad_min;
    }
    if ($filtro_cantidad_max !== '') {
        $filtros['cantidad']['$lte'] = (float)$filtro_cantidad_max;
    }
}

// Obtener transacciones con paginación
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$skip = ($page - 1) * $limit;

$transacciones = $collection_transacciones->find($filtros, [
    'sort' => ['fecha' => -1],
    'skip' => $skip,
    'limit' => $limit
])->toArray();

$total_transacciones = $collection_transacciones->countDocuments($filtros);
$total_pages = ceil($total_transacciones / $limit);

// Obtener estadísticas
$estadisticas = [
    'total' => $collection_transacciones->countDocuments([]),
    'hoy' => $collection_transacciones->countDocuments([
        'fecha' => ['$gte' => new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000)]
    ]),
    'completadas' => $collection_transacciones->countDocuments(['estado' => 'completada']),
    'pendientes' => $collection_transacciones->countDocuments(['estado' => 'pendiente']),
    'fallidas' => $collection_transacciones->countDocuments(['estado' => 'fallida'])
];

// Calcular ingresos totales
$pipeline_ingresos = [
    ['$match' => ['tipo' => 'recarga', 'cantidad' => ['$gt' => 0]]],
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$cantidad']]]
];
$resultado_ingresos = $collection_transacciones->aggregate($pipeline_ingresos)->toArray();
$ingresos_totales = $resultado_ingresos[0]['total'] ?? 0;

// Calcular ingresos del período
$filtros_periodo = array_merge($filtros, ['tipo' => 'recarga', 'cantidad' => ['$gt' => 0]]);
$pipeline_ingresos_periodo = [
    ['$match' => $filtros_periodo],
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$cantidad']]]
];
$resultado_ingresos_periodo = $collection_transacciones->aggregate($pipeline_ingresos_periodo)->toArray();
$ingresos_periodo = $resultado_ingresos_periodo[0]['total'] ?? 0;

// Obtener tipos de transacciones únicos
$tipos_transacciones = $collection_transacciones->distinct('tipo');
$estados_transacciones = $collection_transacciones->distinct('estado');

// Obtener usuarios para el filtro
$usuarios_transacciones = $collection_transacciones->distinct('usuario_id');
$usuarios_info = [];
foreach ($usuarios_transacciones as $usuario_id) {
    if ($usuario_id) {
        $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario_id)]);
        if ($usuario) {
            $usuarios_info[$usuario_id] = $usuario['username'] ?? 'Usuario';
        }
    }
}

$title = "Gestión de Transacciones - Panel de Administración";
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
        }
        .cantidad-positiva { color: #28a745; }
        .cantidad-negativa { color: #dc3545; }
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
                        <a class="nav-link active" href="admin_transacciones.php">
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
                        <h5 class="mb-0">Gestión de Transacciones</h5>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">Bienvenido, <?php echo $_SESSION["username"] ?? 'Admin'; ?></span>
                            <a href="https://www.codigoamigo.com/logout" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Salir
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="p-4">
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-primary"><?php echo number_format($estadisticas['total']); ?></h3>
                                    <p class="text-muted mb-0">Total</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo number_format($estadisticas['hoy']); ?></h3>
                                    <p class="text-muted mb-0">Hoy</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo number_format($estadisticas['completadas']); ?></h3>
                                    <p class="text-muted mb-0">Completadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-warning"><?php echo number_format($estadisticas['pendientes']); ?></h3>
                                    <p class="text-muted mb-0">Pendientes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-danger"><?php echo number_format($estadisticas['fallidas']); ?></h3>
                                    <p class="text-muted mb-0">Fallidas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-info">€<?php echo number_format($ingresos_totales, 2); ?></h3>
                                    <p class="text-muted mb-0">Ingresos Totales</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Filtros de Búsqueda</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Tipo</label>
                                    <select name="tipo" class="form-select">
                                        <option value="">Todos</option>
                                        <?php foreach ($tipos_transacciones as $tipo): ?>
                                        <option value="<?php echo htmlspecialchars($tipo); ?>" 
                                                <?php echo $filtro_tipo === $tipo ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(str_replace('_', ' ', $tipo)); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todos</option>
                                        <?php foreach ($estados_transacciones as $estado): ?>
                                        <option value="<?php echo htmlspecialchars($estado); ?>" 
                                                <?php echo $filtro_estado === $estado ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($estado); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Usuario</label>
                                    <select name="usuario" class="form-select">
                                        <option value="">Todos</option>
                                        <?php foreach ($usuarios_info as $usuario_id => $username): ?>
                                        <option value="<?php echo $usuario_id; ?>" 
                                                <?php echo $filtro_usuario === $usuario_id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($username); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Fecha Inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control" 
                                           value="<?php echo htmlspecialchars($filtro_fecha_inicio); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Fecha Fin</label>
                                    <input type="date" name="fecha_fin" class="form-control" 
                                           value="<?php echo htmlspecialchars($filtro_fecha_fin); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i>Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Cantidad Mínima (€)</label>
                                    <input type="number" step="0.01" name="cantidad_min" class="form-control" 
                                           value="<?php echo htmlspecialchars($filtro_cantidad_min); ?>" form="filtros-form">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cantidad Máxima (€)</label>
                                    <input type="number" step="0.01" name="cantidad_max" class="form-control" 
                                           value="<?php echo htmlspecialchars($filtro_cantidad_max); ?>" form="filtros-form">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de transacciones -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Lista de Transacciones</h5>
                            <div>
                                <span class="badge bg-primary me-2"><?php echo number_format($total_transacciones); ?> transacciones</span>
                                <span class="badge bg-success">€<?php echo number_format($ingresos_periodo, 2); ?> en período</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Usuario</th>
                                            <th>Tipo</th>
                                            <th>Cantidad</th>
                                            <th>Estado</th>
                                            <th>Descripción</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transacciones as $transaccion): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted"><?php echo substr($transaccion['_id'], 0, 8) . '...'; ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                         style="width: 32px; height: 32px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($usuarios_info[$transaccion['usuario_id']] ?? 'Usuario'); ?></strong>
                                                        <br><small class="text-muted"><?php echo substr($transaccion['usuario_id'], 0, 8) . '...'; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst(str_replace('_', ' ', $transaccion['tipo'] ?? 'transaccion')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-bold <?php 
                                                    $cantidad = $transaccion['cantidad'] ?? 0;
                                                    echo $cantidad > 0 ? 'cantidad-positiva' : 'cantidad-negativa';
                                                ?>">
                                                    €<?php echo number_format($cantidad, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $estado = $transaccion['estado'] ?? 'pendiente';
                                                $estado_class = $estado == 'completada' ? 'success' : ($estado == 'pendiente' ? 'warning' : 'danger');
                                                ?>
                                                <span class="badge bg-<?php echo $estado_class; ?>">
                                                    <?php echo ucfirst($estado); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                                     title="<?php echo htmlspecialchars($transaccion['descripcion'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars($transaccion['descripcion'] ?? ''); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', $transaccion['fecha']->toDateTime()->getTimestamp()); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" data-bs-target="#modalDetalles" 
                                                            data-transaccion-id="<?php echo $transaccion['_id']; ?>"
                                                            data-transaccion-data="<?php echo htmlspecialchars(json_encode($transaccion)); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($estado == 'pendiente'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            onclick="aprobarTransaccion('<?php echo $transaccion['_id']; ?>')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="rechazarTransaccion('<?php echo $transaccion['_id']; ?>')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginación de transacciones" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&tipo=<?php echo urlencode($filtro_tipo); ?>&estado=<?php echo urlencode($filtro_estado); ?>&usuario=<?php echo urlencode($filtro_usuario); ?>&fecha_inicio=<?php echo urlencode($filtro_fecha_inicio); ?>&fecha_fin=<?php echo urlencode($filtro_fecha_fin); ?>&cantidad_min=<?php echo urlencode($filtro_cantidad_min); ?>&cantidad_max=<?php echo urlencode($filtro_cantidad_max); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles de transacción -->
    <div class="modal fade" id="modalDetalles" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de Transacción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detalles-transaccion">
                        <!-- Los detalles se cargarán aquí -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Modal de detalles
        document.getElementById('modalDetalles').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var transaccionData = JSON.parse(button.getAttribute('data-transaccion-data'));
            
            var html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información General</h6>
                        <table class="table table-sm">
                            <tr><td><strong>ID:</strong></td><td>${transaccionData._id}</td></tr>
                            <tr><td><strong>Tipo:</strong></td><td>${transaccionData.tipo}</td></tr>
                            <tr><td><strong>Estado:</strong></td><td>${transaccionData.estado}</td></tr>
                            <tr><td><strong>Cantidad:</strong></td><td>€${parseFloat(transaccionData.cantidad).toFixed(2)}</td></tr>
                            <tr><td><strong>Fecha:</strong></td><td>${new Date(transaccionData.fecha.$date).toLocaleString()}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Detalles Adicionales</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Usuario ID:</strong></td><td>${transaccionData.usuario_id}</td></tr>
                            <tr><td><strong>Descripción:</strong></td><td>${transaccionData.descripcion || 'N/A'}</td></tr>
                            ${transaccionData.codigo_id ? `<tr><td><strong>Código ID:</strong></td><td>${transaccionData.codigo_id}</td></tr>` : ''}
                            ${transaccionData.admin_id ? `<tr><td><strong>Admin ID:</strong></td><td>${transaccionData.admin_id}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
            `;
            
            document.getElementById('detalles-transaccion').innerHTML = html;
        });

        // Aprobar transacción
        function aprobarTransaccion(transaccionId) {
            if (confirm('¿Estás seguro de aprobar esta transacción?')) {
                // Aquí implementarías la lógica para aprobar la transacción
                alert('Función de aprobación no implementada aún');
            }
        }

        // Rechazar transacción
        function rechazarTransaccion(transaccionId) {
            if (confirm('¿Estás seguro de rechazar esta transacción?')) {
                // Aquí implementarías la lógica para rechazar la transacción
                alert('Función de rechazo no implementada aún');
            }
        }

        // Formulario de filtros
        document.querySelector('form').id = 'filtros-form';
    </script>
    
<?php get_footer(); ?>
</body>
</html>


