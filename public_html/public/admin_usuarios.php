<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';
include_once __DIR__ . '/../myphp/funciones_email.php';
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
            
            // Obtener saldo actual (saldo anterior)
            $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
            $saldo_anterior = $usuario['saldo'] ?? 0;
            $diferencia = $nuevo_saldo - $saldo_anterior;
            
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
                'admin_id' => $_SESSION["user_id"],
                'saldo_anterior' => $saldo_anterior,
                'saldo_nuevo' => $nuevo_saldo
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

        case 'toggle_vip':
            $user_id = $_POST['user_id'];
            $accion_vip = $_POST['accion_vip'] ?? '';
            try {
                if ($accion_vip === 'activar') {
                    $expires = new DateTime();
                    $expires->modify('+1 month');
                    $sub_id = 'direct_activation_' . time();
                    $ok = activar_vip($user_id, $sub_id, $expires);
                    $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok
                        ? "VIP activado manualmente (1 mes)"
                        : "No se pudo activar VIP";
                } else {
                    $ok = desactivar_vip($user_id);
                    $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok
                        ? "VIP desactivado"
                        : "No se pudo desactivar VIP";
                }
            } catch (Throwable $e) {
                $_SESSION['error_message'] = "Error toggle VIP: " . $e->getMessage();
            }
            break;
            
        case 'add_saldo':
            $user_id = $_POST['user_id'];
            $cantidad = (float)$_POST['cantidad'];
            $motivo = $_POST['motivo'] ?? 'Recarga manual por administrador';
            
            // Obtener saldo actual (saldo anterior)
            $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
            $saldo_anterior = $usuario['saldo'] ?? 0;
            $nuevo_saldo = $saldo_anterior + $cantidad;
            
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
                'admin_id' => $_SESSION["user_id"],
                'saldo_anterior' => $saldo_anterior,
                'saldo_nuevo' => $nuevo_saldo
            ];
            $collection_transacciones->insertOne($transaccion);
            
            // Incluir funciones de email avanzadas
            include_once __DIR__ . '/../myphp/funciones_email.php';
            include_once __DIR__ . '/../myphp/email_helper.php';
            
            // Enviar email usando el método avanzado (Brevo con fallback) y registrar en el log
            $to_email = $usuario['mail'] ?? '';
            $to_name = $usuario['username'] ?? 'Usuario';
            $subject = "¡Felicidades! Tu saldo ha sido incrementado - CodigoAmigo";
            $html_content = crearPlantillaEmailSaldo($to_name, $cantidad, $nuevo_saldo, $motivo);
            
            $resultado_email = enviarEmailConBrevoYRegistrar(
                $to_email, 
                $to_name, 
                $subject, 
                $html_content, 
                'recarga_saldo', 
                $user_id, 
                ['cantidad' => $cantidad, 'saldo_anterior' => $saldo_actual, 'saldo_nuevo' => $nuevo_saldo, 'motivo' => $motivo]
            );
            
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
$filtro_usuario_id = $_GET['usuario_id'] ?? '';
$filtro_vip = $_GET['vip'] ?? '';

// Obtener parámetros de ordenamiento
$sort_by = $_GET['sort'] ?? 'fecha_registro';
$sort_order = $_GET['order'] ?? 'desc';

// Construir filtros para la consulta
$filtros = [];

if ($filtro_usuario_id) {
    // Si se busca por ID de usuario, buscar directamente por _id
    try {
        $filtros['_id'] = new MongoDB\BSON\ObjectId($filtro_usuario_id);
    } catch (Exception $e) {
        // Si el ID no es válido, no aplicar filtro (mostrar nada)
        $filtros = ['_id' => new MongoDB\BSON\ObjectId('000000000000000000000000')]; // ID inválido para no mostrar nada
    }
} else {
    // Solo aplicar otros filtros si no se está buscando por ID
    if ($filtro_estado !== '') {
        $filtros['estado'] = (int)$filtro_estado;
    }
    if ($filtro_busqueda) {
        $filtros['$or'] = [
            ['username' => ['$regex' => $filtro_busqueda, '$options' => 'i']],
            ['mail' => ['$regex' => $filtro_busqueda, '$options' => 'i']]
        ];
    }
}
// Solo aplicar filtros de saldo si no se está buscando por ID
if (!$filtro_usuario_id) {
    if ($filtro_saldo_min !== '' || $filtro_saldo_max !== '') {
        $filtros['saldo'] = [];
        if ($filtro_saldo_min !== '') {
            $filtros['saldo']['$gte'] = (float)$filtro_saldo_min;
        }
        if ($filtro_saldo_max !== '') {
            $filtros['saldo']['$lte'] = (float)$filtro_saldo_max;
        }
    }
    if ($filtro_vip === '1') {
        $filtros['is_vip'] = true;
    } elseif ($filtro_vip === '0') {
        $filtros['$or'] = [['is_vip' => ['$exists' => false]], ['is_vip' => false]];
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
    if (!empty($_GET['usuario_id'])) $url .= '&usuario_id=' . urlencode($_GET['usuario_id']);
    if (isset($_GET['vip']) && $_GET['vip'] !== '') $url .= '&vip=' . urlencode($_GET['vip']);

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
    'con_saldo' => $collection_usuarios->countDocuments(['saldo' => ['$gt' => 0]]),
    'vip' => $collection_usuarios->countDocuments(['is_vip' => true])
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
            <?php echo get_admin_sidebar_menu('admin_usuarios.php'); ?>

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
                        <div class="col-md-3 col-6 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-primary"><?php echo number_format($estadisticas['total']); ?></h3>
                                    <p class="text-muted mb-0">Total Usuarios</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo number_format($estadisticas['activos']); ?></h3>
                                    <p class="text-muted mb-0">Activos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-warning"><?php echo number_format($estadisticas['con_saldo']); ?></h3>
                                    <p class="text-muted mb-0">Con Saldo</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <a href="?vip=1" class="text-decoration-none">
                                <div class="card card-stat" style="border:1px solid #ffd700;">
                                    <div class="card-body text-center">
                                        <h3 style="color:#d4a017;"><i class="fas fa-crown"></i> <?php echo number_format($estadisticas['vip']); ?></h3>
                                        <p class="text-muted mb-0">VIP Activos</p>
                                    </div>
                                </div>
                            </a>
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
                                    <label class="form-label">ID Usuario</label>
                                    <input type="text" name="usuario_id" class="form-control" 
                                           value="<?php echo htmlspecialchars($filtro_usuario_id); ?>"
                                           placeholder="Buscar por ID">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="1" <?php echo $filtro_estado === '1' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="0" <?php echo $filtro_estado === '0' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">VIP</label>
                                    <select name="vip" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="1" <?php echo $filtro_vip === '1' ? 'selected' : ''; ?>>Solo VIP</option>
                                        <option value="0" <?php echo $filtro_vip === '0' ? 'selected' : ''; ?>>No VIP</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
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
                            <?php if ($filtro_usuario_id): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Buscando por ID de usuario. Los demás filtros están deshabilitados.
                                        <a href="admin_usuarios.php" class="btn btn-sm btn-outline-secondary ms-2">
                                            <i class="fas fa-times me-1"></i>Limpiar filtros
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
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
                                            <th>VIP</th>
                                            <th><?php echo generarEnlaceOrdenamiento('saldo', 'Saldo', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('fecha_registro', 'Registro', $sort_by, $sort_order); ?></th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted font-monospace"><?php echo $usuario['_id']; ?></small>
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
                                                <?php
                                                $u_is_vip = !empty($usuario['is_vip']);
                                                $u_vip_exp = $usuario['vip_expires_at'] ?? null;
                                                if ($u_vip_exp instanceof MongoDB\BSON\UTCDateTime) {
                                                    $u_vip_exp = $u_vip_exp->toDateTime()->format('d/m/Y');
                                                } else {
                                                    $u_vip_exp = '';
                                                }
                                                ?>
                                                <?php if ($u_is_vip): ?>
                                                    <span class="badge" style="background:#d4a017;color:#fff;" title="Expira: <?php echo $u_vip_exp; ?>">
                                                        <i class="fas fa-crown"></i> VIP
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">—</span>
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
                                                    <button type="button"
                                                            class="btn btn-sm <?php echo $u_is_vip ? 'btn-warning' : 'btn-outline-warning'; ?>"
                                                            style="<?php echo $u_is_vip ? 'background:#d4a017;border-color:#d4a017;color:#fff;' : 'border-color:#d4a017;color:#d4a017;'; ?>"
                                                            title="<?php echo $u_is_vip ? 'Desactivar VIP' : 'Activar VIP (1 mes)'; ?>"
                                                            onclick="toggleVip('<?php echo $usuario['_id']; ?>', <?php echo $u_is_vip ? 'true' : 'false'; ?>, '<?php echo htmlspecialchars($usuario['username'] ?? 'Usuario', ENT_QUOTES); ?>')">
                                                        <i class="fas fa-crown"></i>
                                                    </button>
                                                    <a href="admin_usuario_detalle.php?id=<?php echo $usuario['_id']; ?>"
                                                       class="btn btn-sm btn-outline-info" title="Ver detalle completo del usuario">
                                                        <i class="fas fa-user"></i>
                                                    </a>
                                                    <a href="admin_codigos.php?usuario=<?php echo $usuario['_id']; ?>"
                                                       class="btn btn-sm btn-outline-secondary" title="Ver códigos del usuario">
                                                        <i class="fas fa-code"></i>
                                                    </a>
                                                    <a href="https://www.codigoamigo.com/usuario/<?php echo $usuario['username'] ?? $usuario['_id']; ?>"
                                                       class="btn btn-sm btn-outline-dark" target="_blank">
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
                                        <a class="page-link" href="?page=<?php echo $i; ?>&estado=<?php echo $filtro_estado; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>&saldo_min=<?php echo $filtro_saldo_min; ?>&saldo_max=<?php echo $filtro_saldo_max; ?>&usuario_id=<?php echo urlencode($filtro_usuario_id); ?>&vip=<?php echo urlencode($filtro_vip); ?>">
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

        // Toggle VIP
        function toggleVip(userId, esVip, username) {
            const mensaje = esVip
                ? `¿Desactivar VIP de "${username}"?\n\nNo se cancela la suscripción en Stripe automáticamente.`
                : `¿Activar VIP de "${username}" durante 1 mes?\n\nEsto añade +10€ de saldo (bonus VIP).`;
            if (!confirm(mensaje)) return;
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_vip">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="accion_vip" value="${esVip ? 'desactivar' : 'activar'}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Si hay un ID de usuario en la URL, hacer scroll al resultado
        <?php if ($filtro_usuario_id && !empty($usuarios)): ?>
        $(document).ready(function() {
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $('.table').offset().top - 100
                }, 500);
            }, 100);
        });
        <?php endif; ?>
    </script>
    
<?php get_footer(); ?>
</body>
</html>

