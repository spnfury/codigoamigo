<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';
include_once __DIR__ . '/../myphp/funciones_email.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

if (!in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$collection_usuarios = getCollectionUsuarios();
$collection_transacciones = getCollectionTransacciones();
$collection_codigos = getCollectionCodigos();

// Procesar acciones
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_saldo':
            $user_id = $_POST['user_id'];
            $nuevo_saldo = (float)$_POST['nuevo_saldo'];
            $motivo = $_POST['motivo'] ?? 'Ajuste manual por administrador';
            
            // Obtener saldo actual
            $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
            $saldo_actual = $usuario['saldo'] ?? 0;
            $diferencia = $nuevo_saldo - $saldo_actual;
            
            // Actualizar saldo
            $collection_usuarios->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($user_id)],
                ['$set' => ['saldo' => $nuevo_saldo]]
            );
            
            // Registrar transacción
            $transaccion = [
                'usuario_id' => $user_id,
                'tipo' => 'ajuste_admin',
                'cantidad' => $diferencia,
                'descripcion' => $motivo,
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'estado' => 'completada',
                'admin_id' => $_SESSION["user_id"]
            ];
            $collection_transacciones->insertOne($transaccion);
            
            $_SESSION['success_message'] = "Saldo actualizado correctamente";
            break;
            
        case 'toggle_estado':
            $user_id = $_POST['user_id'];
            $nuevo_estado = $_POST['nuevo_estado'];
            
            $collection_usuarios->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($user_id)],
                ['$set' => ['estado' => (int)$nuevo_estado]]
            );
            
            $_SESSION['success_message'] = "Estado del usuario actualizado";
            break;
            
        case 'add_saldo':
            $user_id = $_POST['user_id'];
            $cantidad = (float)$_POST['cantidad'];
            $motivo = $_POST['motivo'] ?? 'Recarga manual por administrador';
            
            // Obtener saldo actual
            $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
            $saldo_actual = $usuario['saldo'] ?? 0;
            $nuevo_saldo = $saldo_actual + $cantidad;
            
            // Actualizar saldo
            $collection_usuarios->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($user_id)],
                ['$set' => ['saldo' => $nuevo_saldo]]
            );
            
            // Registrar transacción
            $transaccion = [
                'usuario_id' => $user_id,
                'tipo' => 'recarga_admin',
                'cantidad' => $cantidad,
                'descripcion' => $motivo,
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'estado' => 'completada',
                'admin_id' => $_SESSION["user_id"]
            ];
            $collection_transacciones->insertOne($transaccion);
            
            // Enviar email de notificación
            enviarEmailSaldoCargado($usuario, $cantidad, $nuevo_saldo, $motivo);
            
            $_SESSION['success_message'] = "Saldo añadido correctamente y email enviado al usuario";
            break;
    }
    
    header('Location: admin_usuarios.php');
    exit;
}

// Obtener filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_saldo_min = $_GET['saldo_min'] ?? '';
$filtro_saldo_max = $_GET['saldo_max'] ?? '';

// Obtener parámetros de ordenamiento
$sort_by = $_GET['sort'] ?? 'fecha_registro';
$sort_order = $_GET['order'] ?? 'desc';

// Construir filtros para la consulta
$filtros = [];
if ($filtro_estado !== '') {
    $filtros['estado'] = (int)$filtro_estado;
}
if ($filtro_busqueda) {
    $filtros['$or'] = [
        ['username' => ['$regex' => $filtro_busqueda, '$options' => 'i']],
        ['mail' => ['$regex' => $filtro_busqueda, '$options' => 'i']]
    ];
}
if ($filtro_saldo_min !== '' || $filtro_saldo_max !== '') {
    $filtros['saldo'] = [];
    if ($filtro_saldo_min !== '') {
        $filtros['saldo']['$gte'] = (float)$filtro_saldo_min;
    }
    if ($filtro_saldo_max !== '') {
        $filtros['saldo']['$lte'] = (float)$filtro_saldo_max;
    }
}

// Construir ordenamiento
$sort_direction = $sort_order === 'asc' ? 1 : -1;
$sort_options = [];

switch ($sort_by) {
    case 'usuario':
        $sort_options = ['username' => $sort_direction];
        break;
    case 'email':
        $sort_options = ['mail' => $sort_direction];
        break;
    case 'estado':
        $sort_options = ['estado' => $sort_direction];
        break;
    case 'saldo':
        $sort_options = ['saldo' => $sort_direction];
        break;
    case 'zumbidos':
        $sort_options = ['zumbido_saldo' => $sort_direction];
        break;
    case 'fecha_registro':
    default:
        $sort_options = ['_id' => $sort_direction];
        break;
}

// Obtener usuarios con paginación
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$skip = ($page - 1) * $limit;

$usuarios = $collection_usuarios->find($filtros, [
    'sort' => $sort_options,
    'skip' => $skip,
    'limit' => $limit
])->toArray();

$total_usuarios = $collection_usuarios->countDocuments($filtros);
$total_pages = ceil($total_usuarios / $limit);

// Función para generar enlaces de ordenamiento
function generarEnlaceOrdenamiento($columna, $texto, $sort_by, $sort_order) {
    $nuevo_orden = ($sort_by === $columna && $sort_order === 'asc') ? 'desc' : 'asc';
    $url = '?sort=' . $columna . '&order=' . $nuevo_orden;
    
    // Mantener filtros existentes
    if (!empty($_GET['estado'])) $url .= '&estado=' . $_GET['estado'];
    if (!empty($_GET['busqueda'])) $url .= '&busqueda=' . urlencode($_GET['busqueda']);
    if (!empty($_GET['saldo_min'])) $url .= '&saldo_min=' . $_GET['saldo_min'];
    if (!empty($_GET['saldo_max'])) $url .= '&saldo_max=' . $_GET['saldo_max'];
    
    $icono = '';
    if ($sort_by === $columna) {
        $icono = $sort_order === 'asc' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
    } else {
        $icono = ' <i class="fas fa-sort text-muted"></i>';
    }
    
    return '<a href="' . $url . '" class="text-decoration-none text-dark">' . $texto . $icono . '</a>';
}

// Obtener estadísticas
$estadisticas = [
    'total' => $collection_usuarios->countDocuments([]),
    'activos' => $collection_usuarios->countDocuments(['estado' => 1]),
    'inactivos' => $collection_usuarios->countDocuments(['estado' => 0]),
    'con_saldo' => $collection_usuarios->countDocuments(['saldo' => ['$gt' => 0]])
];

$title = "Gestión de Usuarios - Panel de Administración";
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
        .saldo-positivo { color: #28a745; }
        .saldo-negativo { color: #dc3545; }
        .saldo-cero { color: #6c757d; }
        
        /* Estilos para ordenamiento */
        .table th {
            position: relative;
        }
        .table th a {
            color: inherit;
            text-decoration: none;
            display: block;
            width: 100%;
            padding: 0.75rem;
            margin: -0.75rem;
        }
        .table th a:hover {
            color: #0d6efd;
            text-decoration: none;
        }
        .table th a i {
            margin-left: 5px;
            font-size: 0.8em;
        }
        .table th a i.fa-sort {
            opacity: 0.3;
        }
        .table th a:hover i.fa-sort {
            opacity: 0.6;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
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
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="admin_usuarios.php">
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
                        <h5 class="mb-0">Gestión de Usuarios</h5>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">Bienvenido, <?php echo $_SESSION["username"] ?? 'Admin'; ?></span>
                            <a href="https://www.codigoamigo.com/logout" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Salir
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="p-4">
                    <!-- Mensajes -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-primary"><?php echo number_format($estadisticas['total']); ?></h3>
                                    <p class="text-muted mb-0">Total Usuarios</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo number_format($estadisticas['activos']); ?></h3>
                                    <p class="text-muted mb-0">Activos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-danger"><?php echo number_format($estadisticas['inactivos']); ?></h3>
                                    <p class="text-muted mb-0">Inactivos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-warning"><?php echo number_format($estadisticas['con_saldo']); ?></h3>
                                    <p class="text-muted mb-0">Con Saldo</p>
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
                                <div class="col-md-3">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="1" <?php echo $filtro_estado === '1' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="0" <?php echo $filtro_estado === '0' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Buscar</label>
                                    <input type="text" name="busqueda" class="form-control" 
                                           placeholder="Usuario o email" value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Saldo Mín.</label>
                                    <input type="number" name="saldo_min" class="form-control" 
                                           placeholder="0" value="<?php echo htmlspecialchars($filtro_saldo_min); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Saldo Máx.</label>
                                    <input type="number" name="saldo_max" class="form-control" 
                                           placeholder="1000" value="<?php echo htmlspecialchars($filtro_saldo_max); ?>">
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
                        </div>
                    </div>

                    <!-- Tabla de usuarios -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Lista de Usuarios</h5>
                                <?php if ($sort_by !== 'fecha_registro' || $sort_order !== 'desc'): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">
                                        <i class="fas fa-sort me-1"></i>
                                        Ordenado por: 
                                        <?php
                                        $columnas = [
                                            'usuario' => 'Usuario',
                                            'email' => 'Email', 
                                            'estado' => 'Estado',
                                            'saldo' => 'Saldo',
                                            'zumbidos' => 'Zumbidos',
                                            'fecha_registro' => 'Fecha de Registro'
                                        ];
                                        echo $columnas[$sort_by] ?? 'Fecha de Registro';
                                        ?>
                                        <i class="fas fa-sort-<?php echo $sort_order === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                    </small>
                                    <a href="?" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Resetear
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-primary"><?php echo number_format($total_usuarios); ?> usuarios</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th><?php echo generarEnlaceOrdenamiento('fecha_registro', 'ID', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('usuario', 'Usuario', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('email', 'Email', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('estado', 'Estado', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('saldo', 'Saldo', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('zumbidos', 'Zumbidos', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('fecha_registro', 'Registro', $sort_by, $sort_order); ?></th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted"><?php echo substr($usuario['_id'], 0, 8) . '...'; ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (isset($usuario['img']) && $usuario['img']): ?>
                                                        <img src="<?php echo htmlspecialchars($usuario['img']); ?>" 
                                                             class="rounded-circle me-2" width="32" height="32" 
                                                             onerror="this.src='https://via.placeholder.com/32'">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                             style="width: 32px; height: 32px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($usuario['username'] ?? 'Sin nombre'); ?></strong>
                                                        <?php if (isset($usuario['type'])): ?>
                                                            <br><small class="text-muted"><?php echo ucfirst($usuario['type']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($usuario['mail'] ?? 'Sin email'); ?></td>
                                            <td>
                                                <?php if (($usuario['estado'] ?? 0) == 1): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="fw-bold <?php 
                                                    $saldo = $usuario['saldo'] ?? 0;
                                                    echo $saldo > 0 ? 'saldo-positivo' : ($saldo < 0 ? 'saldo-negativo' : 'saldo-cero');
                                                ?>">
                                                    €<?php echo number_format($saldo, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $usuario['zumbido_saldo'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    if (isset($usuario['fecha_registro'])) {
                                                        echo date('d/m/Y', strtotime($usuario['fecha_registro']));
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#modalSaldo" 
                                                            data-user-id="<?php echo $usuario['_id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($usuario['username'] ?? 'Usuario'); ?>"
                                                            data-current-saldo="<?php echo $saldo; ?>">
                                                        <i class="fas fa-euro-sign"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            data-bs-toggle="modal" data-bs-target="#modalAddSaldo" 
                                                            data-user-id="<?php echo $usuario['_id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($usuario['username'] ?? 'Usuario'); ?>">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick="toggleEstado('<?php echo $usuario['_id']; ?>', <?php echo $usuario['estado'] ?? 0; ?>)">
                                                        <i class="fas fa-toggle-<?php echo ($usuario['estado'] ?? 0) == 1 ? 'on' : 'off'; ?>"></i>
                                                    </button>
                                                    <a href="https://www.codigoamigo.com/usuario/<?php echo $usuario['username'] ?? $usuario['_id']; ?>" 
                                                       class="btn btn-sm btn-outline-info" target="_blank">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginación de usuarios">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&estado=<?php echo $filtro_estado; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>&saldo_min=<?php echo $filtro_saldo_min; ?>&saldo_max=<?php echo $filtro_saldo_max; ?>">
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

    <!-- Modal para ajustar saldo -->
    <div class="modal fade" id="modalSaldo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajustar Saldo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_saldo">
                        <input type="hidden" name="user_id" id="modal_user_id">
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="modal_user_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nuevo Saldo (€)</label>
                            <input type="number" step="0.01" class="form-control" name="nuevo_saldo" id="modal_nuevo_saldo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo</label>
                            <textarea class="form-control" name="motivo" rows="3" placeholder="Motivo del ajuste..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Saldo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para añadir saldo -->
    <div class="modal fade" id="modalAddSaldo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        Cargar Saldo al Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_saldo">
                        <input type="hidden" name="user_id" id="modal_add_user_id">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nota:</strong> Se enviará un email automático al usuario notificándole del incremento de saldo.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Usuario</label>
                                    <input type="text" class="form-control" id="modal_add_user_name" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Saldo Actual</label>
                                    <input type="text" class="form-control" id="modal_add_current_saldo" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Cantidad a Añadir (€)</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-lg" name="cantidad" required placeholder="0.00">
                            </div>
                            <div class="form-text">Introduce la cantidad que deseas añadir al saldo del usuario</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Motivo de la Recarga</label>
                            <textarea class="form-control" name="motivo" rows="3" placeholder="Ej: Compensación por problema técnico, Bono promocional, etc." required></textarea>
                            <div class="form-text">Este motivo aparecerá en el email enviado al usuario</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-muted">Saldo Actual</h6>
                                        <h4 class="text-primary" id="preview_current_saldo">0.00€</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Nuevo Saldo</h6>
                                        <h4 id="preview_new_saldo">0.00€</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-plus-circle me-2"></i>Cargar Saldo y Enviar Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Modal de saldo
        document.getElementById('modalSaldo').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var userId = button.getAttribute('data-user-id');
            var userName = button.getAttribute('data-user-name');
            var currentSaldo = button.getAttribute('data-current-saldo');
            
            document.getElementById('modal_user_id').value = userId;
            document.getElementById('modal_user_name').value = userName;
            document.getElementById('modal_nuevo_saldo').value = currentSaldo;
        });

        // Modal de añadir saldo
        document.getElementById('modalAddSaldo').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var userId = button.getAttribute('data-user-id');
            var userName = button.getAttribute('data-user-name');
            var currentSaldo = parseFloat(button.getAttribute('data-current-saldo')) || 0;
            
            document.getElementById('modal_add_user_id').value = userId;
            document.getElementById('modal_add_user_name').value = userName;
            document.getElementById('modal_add_current_saldo').value = currentSaldo.toFixed(2) + '€';
            document.getElementById('preview_current_saldo').textContent = currentSaldo.toFixed(2) + '€';
            document.getElementById('preview_new_saldo').textContent = currentSaldo.toFixed(2) + '€';
            
            // Limpiar el campo de cantidad
            document.querySelector('input[name="cantidad"]').value = '';
        });
        
        // Actualizar preview del nuevo saldo en tiempo real
        document.querySelector('input[name="cantidad"]').addEventListener('input', function() {
            var currentSaldo = parseFloat(document.getElementById('modal_add_current_saldo').value.replace('€', '')) || 0;
            var cantidad = parseFloat(this.value) || 0;
            var nuevoSaldo = currentSaldo + cantidad;
            
            document.getElementById('preview_new_saldo').textContent = nuevoSaldo.toFixed(2) + '€';
        });
        
        // Mejorar experiencia de ordenamiento
        document.addEventListener('DOMContentLoaded', function() {
            // Añadir tooltips a los enlaces de ordenamiento
            const sortLinks = document.querySelectorAll('th a');
            sortLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    const column = this.href.split('sort=')[1]?.split('&')[0];
                    const currentOrder = this.href.split('order=')[1]?.split('&')[0];
                    const newOrder = currentOrder === 'asc' ? 'descendente' : 'ascendente';
                    
                    this.title = `Ordenar por ${this.textContent.trim()} (${newOrder})`;
                });
            });
            
            // Añadir animación a los iconos de ordenamiento
            const sortIcons = document.querySelectorAll('th a i');
            sortIcons.forEach(icon => {
                icon.style.transition = 'all 0.2s ease';
            });
        });

        // Toggle estado
        function toggleEstado(userId, currentEstado) {
            if (confirm('¿Estás seguro de cambiar el estado de este usuario?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_estado">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="nuevo_estado" value="${currentEstado == 1 ? 0 : 1}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
    
<?php get_footer(); ?>
</body>
</html>

