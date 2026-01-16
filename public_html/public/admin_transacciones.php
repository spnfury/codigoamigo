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
$filtro_transaccion_id = $_GET['transaccion_id'] ?? '';

// Obtener parámetros de ordenamiento
$sort_by = $_GET['sort'] ?? 'fecha';
$sort_order = $_GET['order'] ?? 'desc';

// Construir filtros para la consulta
$filtros = [];

if ($filtro_transaccion_id) {
    // Si se busca por ID de transacción, buscar directamente por _id
    try {
        $filtros['_id'] = new MongoDB\BSON\ObjectId($filtro_transaccion_id);
    } catch (Exception $e) {
        // Si el ID no es válido, no aplicar filtro
        $filtros = ['_id' => new MongoDB\BSON\ObjectId('000000000000000000000000')]; // ID inválido para no mostrar nada
    }
} else {
    // Solo aplicar otros filtros si no se está buscando por ID
    if ($filtro_tipo) {
        $filtros['tipo'] = $filtro_tipo;
    }

    if ($filtro_estado) {
        $filtros['estado'] = $filtro_estado;
    }

    if ($filtro_usuario) {
        $filtros['usuario_id'] = $filtro_usuario;
    }
}

// Solo aplicar filtros de fecha y cantidad si no se está buscando por ID
if (!$filtro_transaccion_id) {
    if ($filtro_fecha_inicio || $filtro_fecha_fin) {
        $filtros['fecha'] = [];
        if ($filtro_fecha_inicio) {
            $filtros['fecha']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($filtro_fecha_inicio . ' 00:00:00') * 1000);
        }
        if ($filtro_fecha_fin) {
            $filtros['fecha']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($filtro_fecha_fin . ' 23:59:59') * 1000);
        }
        // Asegurar que el campo fecha existe cuando hay filtros de fecha
        $filtros['fecha']['$exists'] = true;
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
}

// Construir ordenamiento
$sort_direction = $sort_order === 'asc' ? 1 : -1;
$sort_options = [];

switch ($sort_by) {
    case 'fecha':
        // Ordenar por fecha. MongoDB ordena correctamente los UTCDateTime
        // Usar ordenamiento secundario por _id para mantener consistencia cuando hay fechas iguales
        $sort_options = [
            'fecha' => $sort_direction,
            '_id' => $sort_direction  // Mismo orden que fecha para consistencia
        ];
        break;
    case 'id':
        $sort_options = ['_id' => $sort_direction];
        break;
    case 'usuario':
        $sort_options = ['usuario_id' => $sort_direction];
        break;
    case 'tipo':
        $sort_options = ['tipo' => $sort_direction];
        break;
    case 'marca':
        $sort_options = ['marca' => $sort_direction];
        break;
    case 'cantidad':
        $sort_options = ['cantidad' => $sort_direction];
        break;
    case 'metodo':
        $sort_options = ['metodo_pago' => $sort_direction];
        break;
    case 'estado':
        $sort_options = ['estado' => $sort_direction];
        break;
    case 'descripcion':
        $sort_options = ['descripcion' => $sort_direction];
        break;
    default:
        $sort_options = ['fecha' => -1];
        break;
}

// Obtener transacciones con paginación
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$skip = ($page - 1) * $limit;

// Asegurar que el sort_options no esté vacío
if (empty($sort_options)) {
    $sort_options = ['fecha' => -1];
}

// Obtener transacciones con ordenamiento
try {
    // Asegurarse de que sort_options sea un array válido
    if (!is_array($sort_options) || empty($sort_options)) {
        $sort_options = ['fecha' => -1];
    }
    
    // Si el ordenamiento es por fecha, asegurarse de que solo se incluyan documentos con fecha
    // MongoDB puede tener problemas ordenando cuando hay documentos sin el campo fecha
    if ($sort_by === 'fecha') {
        if (!isset($filtros['fecha'])) {
            // Agregar filtro para excluir documentos sin fecha cuando se ordena por fecha
            $filtros['fecha'] = ['$exists' => true, '$ne' => null];
        } elseif (is_array($filtros['fecha'])) {
            // Si ya hay filtros de fecha, asegurarse de que también se requiera que exista
            $filtros['fecha']['$exists'] = true;
        }
    }
    
    $transacciones = $collection_transacciones->find($filtros, [
        'sort' => $sort_options,
        'skip' => $skip,
        'limit' => $limit
    ])->toArray();
    
} catch (Exception $e) {
    error_log("Error al obtener transacciones: " . $e->getMessage());
    $transacciones = [];
}

$total_transacciones = $collection_transacciones->countDocuments($filtros);
$total_pages = ceil($total_transacciones / $limit);

// Función para generar enlaces de ordenamiento
function generarEnlaceOrdenamiento($columna, $texto, $sort_by, $sort_order) {
    $nuevo_orden = ($sort_by === $columna && $sort_order === 'asc') ? 'desc' : 'asc';
    $url = '?sort=' . $columna . '&order=' . $nuevo_orden;
    
    // Mantener filtros existentes
    if (!empty($_GET['tipo'])) $url .= '&tipo=' . urlencode($_GET['tipo']);
    if (!empty($_GET['estado'])) $url .= '&estado=' . urlencode($_GET['estado']);
    if (!empty($_GET['usuario'])) $url .= '&usuario=' . urlencode($_GET['usuario']);
    if (!empty($_GET['fecha_inicio'])) $url .= '&fecha_inicio=' . urlencode($_GET['fecha_inicio']);
    if (!empty($_GET['fecha_fin'])) $url .= '&fecha_fin=' . urlencode($_GET['fecha_fin']);
    if (!empty($_GET['cantidad_min'])) $url .= '&cantidad_min=' . urlencode($_GET['cantidad_min']);
    if (!empty($_GET['cantidad_max'])) $url .= '&cantidad_max=' . urlencode($_GET['cantidad_max']);
    if (!empty($_GET['transaccion_id'])) $url .= '&transaccion_id=' . urlencode($_GET['transaccion_id']);
    
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
            <?php echo get_admin_sidebar_menu('admin_transacciones.php'); ?>

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
                            <form method="GET" class="row g-3" id="filtros-form">
                                <div class="col-md-2">
                                    <label class="form-label">ID Transacción</label>
                                    <input type="text" name="transaccion_id" class="form-control" 
                                           value="<?php echo htmlspecialchars($filtro_transaccion_id); ?>"
                                           placeholder="Buscar por ID">
                                </div>
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
                            <?php if ($filtro_transaccion_id): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Buscando por ID de transacción. Los demás filtros están deshabilitados.
                                        <a href="admin_transacciones.php" class="btn btn-sm btn-outline-secondary ms-2">
                                            <i class="fas fa-times me-1"></i>Limpiar filtros
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tabla de transacciones -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Lista de Transacciones</h5>
                                <?php if ($sort_by !== 'fecha' || $sort_order !== 'desc'): ?>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <small class="text-muted">
                                        <i class="fas fa-sort me-1"></i>
                                        Ordenado por: 
                                        <?php
                                        $columnas = [
                                            'fecha' => 'Fecha',
                                            'id' => 'ID',
                                            'usuario' => 'Usuario',
                                            'tipo' => 'Tipo',
                                            'marca' => 'Marca',
                                            'cantidad' => 'Cantidad',
                                            'metodo' => 'Método',
                                            'estado' => 'Estado',
                                            'descripcion' => 'Descripción'
                                        ];
                                        echo $columnas[$sort_by] ?? 'Fecha';
                                        ?>
                                        <i class="fas fa-sort-<?php echo $sort_order === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                    </small>
                                    <a href="?" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Resetear
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
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
                                            <th><?php echo generarEnlaceOrdenamiento('fecha', 'Fecha', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('id', 'ID', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('usuario', 'Usuario', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('tipo', 'Tipo', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('marca', 'Código/Marca', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('cantidad', 'Cantidad', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('metodo', 'Método', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('estado', 'Estado', $sort_by, $sort_order); ?></th>
                                            <th><?php echo generarEnlaceOrdenamiento('descripcion', 'Descripción', $sort_by, $sort_order); ?></th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transacciones as $transaccion): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    if (isset($transaccion['fecha']) && $transaccion['fecha'] instanceof MongoDB\BSON\UTCDateTime) {
                                                        echo date('d/m/Y H:i', $transaccion['fecha']->toDateTime()->getTimestamp());
                                                    } elseif (isset($transaccion['fecha']) && is_string($transaccion['fecha'])) {
                                                        echo date('d/m/Y H:i', strtotime($transaccion['fecha']));
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted font-monospace" style="font-size: 0.85rem;" 
                                                       title="ID completo: <?php echo (string)$transaccion['_id']; ?>">
                                                    <?php echo (string)$transaccion['_id']; ?>
                                                </small>
                                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" 
                                                        onclick="copiarAlPortapapeles('<?php echo (string)$transaccion['_id']; ?>', this)"
                                                        title="Copiar ID">
                                                    <i class="fas fa-copy" style="font-size: 0.7rem;"></i>
                                                </button>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                         style="width: 32px; height: 32px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                    <div>
                                                        <a href="admin_usuarios.php?usuario_id=<?php echo urlencode($transaccion['usuario_id']); ?>" 
                                                           target="_blank" 
                                                           class="text-decoration-none fw-bold"
                                                           title="Ver detalles del usuario en nueva ventana">
                                                            <?php echo htmlspecialchars($usuarios_info[$transaccion['usuario_id']] ?? 'Usuario'); ?>
                                                            <i class="fas fa-external-link-alt ms-1" style="font-size: 0.7em;"></i>
                                                        </a>
                                                        <br><small class="text-muted"><?php echo substr($transaccion['usuario_id'], 0, 8) . '...'; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst(str_replace('_', ' ', $transaccion['tipo'] ?? 'transaccion')); ?>
                                                    <?php if (isset($transaccion['subtipo']) && $transaccion['subtipo']): ?>
                                                        <br><small style="font-size: 0.7rem; opacity: 0.8;"><?php echo htmlspecialchars($transaccion['subtipo']); ?></small>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($transaccion['codigo_id']) && !empty($transaccion['marca'])): ?>
                                                    <div>
                                                        <a href="/de-<?php echo strtolower($transaccion['marca'] ?? ''); ?>?codigo=<?php echo $transaccion['codigo_id']; ?>" 
                                                           target="_blank" class="badge bg-info text-decoration-none" title="Ver código">
                                                            <?php echo htmlspecialchars($transaccion['marca'] ?? 'N/A'); ?>
                                                        </a>
                                                        <br><small class="text-muted">ID: <?php echo substr($transaccion['codigo_id'], 0, 8); ?>...</small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
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
                                                <span class="badge bg-<?php echo ($transaccion['metodo_pago'] ?? '') === 'tarjeta' ? 'primary' : 'secondary'; ?>">
                                                    <i class="fas fa-<?php echo ($transaccion['metodo_pago'] ?? '') === 'tarjeta' ? 'credit-card' : 'wallet'; ?>"></i>
                                                    <?php echo ucfirst($transaccion['metodo_pago'] ?? 'N/A'); ?>
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
                                        <a class="page-link" href="?page=<?php echo $i; ?>&tipo=<?php echo urlencode($filtro_tipo); ?>&estado=<?php echo urlencode($filtro_estado); ?>&usuario=<?php echo urlencode($filtro_usuario); ?>&fecha_inicio=<?php echo urlencode($filtro_fecha_inicio); ?>&fecha_fin=<?php echo urlencode($filtro_fecha_fin); ?>&cantidad_min=<?php echo urlencode($filtro_cantidad_min); ?>&cantidad_max=<?php echo urlencode($filtro_cantidad_max); ?>&transaccion_id=<?php echo urlencode($filtro_transaccion_id); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>">
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
                            <tr><td><strong>ID:</strong></td><td>
                                <span class="font-monospace">${transaccionData._id}</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" 
                                        onclick="copiarAlPortapapeles('${transaccionData._id}', this)"
                                        title="Copiar ID">
                                    <i class="fas fa-copy" style="font-size: 0.7rem;"></i>
                                </button>
                            </td></tr>
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
                            ${transaccionData.codigo_id ? `<tr><td><strong>Código ID:</strong></td><td><a href="/de-${(transaccionData.marca || '').toLowerCase()}?codigo=${transaccionData.codigo_id}" target="_blank">${transaccionData.codigo_id}</a></td></tr>` : ''}
                            ${transaccionData.marca ? `<tr><td><strong>Marca:</strong></td><td>${transaccionData.marca}</td></tr>` : ''}
                            ${transaccionData.tipo_destacado ? `<tr><td><strong>Tipo Destacado:</strong></td><td><span class="badge bg-warning">${transaccionData.tipo_destacado}</span></td></tr>` : ''}
                            ${transaccionData.metodo_pago ? `<tr><td><strong>Método de Pago:</strong></td><td>${transaccionData.metodo_pago}</td></tr>` : ''}
                            ${transaccionData.stripe_session_id ? `
                                <tr><td><strong>Stripe Session:</strong></td><td><a href="https://dashboard.stripe.com/payments/${transaccionData.stripe_payment_intent || transaccionData.stripe_session_id}" target="_blank" class="text-primary">${transaccionData.stripe_session_id.substring(0, 20)}...</a></td></tr>
                                ${transaccionData.stripe_customer_email ? `<tr><td><strong>Email Cliente:</strong></td><td>${transaccionData.stripe_customer_email}</td></tr>` : ''}
                            ` : ''}
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

        // Si hay un ID de transacción en la URL, hacer scroll al resultado
        <?php if ($filtro_transaccion_id && !empty($transacciones)): ?>
        $(document).ready(function() {
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $('.table').offset().top - 100
                }, 500);
            }, 100);
        });
        <?php endif; ?>

        // Función para copiar al portapapeles
        function copiarAlPortapapeles(texto, btnElement) {
            navigator.clipboard.writeText(texto).then(function() {
                // Mostrar notificación temporal
                var btn = btnElement;
                var originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check" style="font-size: 0.7rem; color: green;"></i>';
                btn.disabled = true;
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }, 1000);
            }).catch(function(err) {
                // Fallback para navegadores antiguos
                var textArea = document.createElement("textarea");
                textArea.value = texto;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    var btn = btnElement;
                    var originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check" style="font-size: 0.7rem; color: green;"></i>';
                    btn.disabled = true;
                    setTimeout(function() {
                        btn.innerHTML = originalHTML;
                        btn.disabled = false;
                    }, 1000);
                } catch (err) {
                    alert('Error al copiar: ' + err);
                }
                document.body.removeChild(textArea);
            });
        }

        // Mejorar experiencia de ordenamiento
        $(document).ready(function() {
            // Añadir tooltips a los enlaces de ordenamiento
            $('th a').on('mouseenter', function() {
                var href = $(this).attr('href');
                var column = href.split('sort=')[1]?.split('&')[0];
                var currentOrder = href.split('order=')[1]?.split('&')[0];
                var newOrder = currentOrder === 'asc' ? 'descendente' : 'ascendente';
                var columnName = $(this).text().trim().replace(/\s*↑|↓|↕/g, '').trim();
                
                $(this).attr('title', 'Ordenar por ' + columnName + ' (' + newOrder + ')');
            });
            
            // Añadir animación a los iconos de ordenamiento
            $('th a i').css('transition', 'all 0.2s ease');
            
            // Resaltar la columna ordenada
            $('th a').each(function() {
                var href = $(this).attr('href');
                var column = href.split('sort=')[1]?.split('&')[0];
                if (column === '<?php echo $sort_by; ?>') {
                    $(this).css('font-weight', 'bold');
                }
            });
        });
    </script>
    
<?php get_footer(); ?>
</body>
</html>


