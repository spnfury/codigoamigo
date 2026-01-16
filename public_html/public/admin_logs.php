<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';

// Incluir sistema de logging organizado
require_once __DIR__ . '/../inc/logger.php';
include_once __DIR__ . '/../inc/log_monitor.php';
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

// Configuración para logs en tiempo real
$realtimeMode = $_GET['realtime'] ?? 'true';
$lastTimestamp = $_GET['last_timestamp'] ?? time() - 3600; // Última hora por defecto

// Obtener filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_nivel = $_GET['nivel'] ?? '';
$filtro_fuente = $_GET['fuente'] ?? '';
$filtro_fecha_inicio = $_GET['fecha_inicio'] ?? '';
$filtro_fecha_fin = $_GET['fecha_fin'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_tiempo_real = $_GET['tiempo_real'] ?? '';

// Construir filtros para la consulta
$filtros = [];

if ($filtro_nivel) {
    $filtros['level'] = $filtro_nivel;
}

if ($filtro_fuente) {
    $filtros['source'] = $filtro_fuente;
}

    if ($filtro_fecha_inicio) {
    $filtros['date_from'] = $filtro_fecha_inicio . ' 00:00:00';
    }

    if ($filtro_fecha_fin) {
    $filtros['date_to'] = $filtro_fin . ' 23:59:59';
}

if ($filtro_busqueda) {
    $filtros['search'] = $filtro_busqueda;
}

// Configuración de fuentes de logs para los filtros
$logSources = [
    'apache_error' => ['name' => 'Apache Error (codigoamigo.com)'],
    'apache_access' => ['name' => 'Apache Access (codigoamigo.com)'],
    'nginx_error' => ['name' => 'Nginx Error (codigoamigo.com)'],
    'nginx_access' => ['name' => 'Nginx Access (codigoamigo.com)'],
    'php_error' => ['name' => 'PHP Error (codigoamigo.com)'],
    'php_fpm' => ['name' => 'PHP-FPM'],
    'mysql_error' => ['name' => 'MySQL Error'],
    'system' => ['name' => 'System (Hestia)'],
    'hestia_access' => ['name' => 'Hestia Access (mda.codigoamigo.com)']
];

// Datos iniciales (se actualizarán via AJAX)
$logs = [];
$stats = [
    'total_logs' => 0,
    'error_logs' => 0,
    'warning_logs' => 0,
    'info_logs' => 0,
    'recent_errors' => 0
];
$logSourcesStatus = [];

// Obtener logs de MongoDB (mantener compatibilidad)
$collection_logs = getCollectionLogs();
$collection_usuarios = getCollectionUsuarios();

$mongoLogs = [];
try {
    $mongoLogs = $collection_logs->find([], [
        'sort' => ['fecha' => -1],
        'limit' => 50
    ])->toArray();
} catch (Exception $e) {
    // MongoDB no disponible
}

// Obtener usuarios para el filtro
$usuarios_info = [];
try {
    $usuarios_logs = $collection_logs->distinct('usuario_id');
foreach ($usuarios_logs as $usuario_id) {
    if ($usuario_id) {
        $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario_id)]);
        if ($usuario) {
            $usuarios_info[$usuario_id] = $usuario['username'] ?? 'Usuario';
        }
    }
}
} catch (Exception $e) {
    // MongoDB no disponible
}

// Función para obtener icono del tipo de log
function getLogTypeIcon($type) {
    $icons = [
        'error' => 'fas fa-exclamation-triangle text-danger',
        'warning' => 'fas fa-exclamation-circle text-warning',
        'info' => 'fas fa-info-circle text-info',
        'access' => 'fas fa-globe text-primary',
        'system' => 'fas fa-cog text-secondary'
    ];
    return $icons[$type] ?? 'fas fa-info-circle';
}

// Función para obtener color del nivel
function getLogLevelColor($level) {
    $colors = [
        'error' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        'access' => 'primary',
        'system' => 'secondary'
    ];
    return $colors[$level] ?? 'secondary';
}

// Función para obtener nombre de la fuente
function getSourceName($source, $sources) {
    return $sources[$source]['name'] ?? ucfirst(str_replace('_', ' ', $source));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Monitoreo de Logs - CodigoAmigo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .log-entry {
            border-left: 4px solid #dee2e6;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 0 5px 5px 0;
        }
        .log-entry.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .log-entry.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .log-entry.info {
            border-left-color: #17a2b8;
            background: #d1ecf1;
        }
        .log-entry.access {
            border-left-color: #007bff;
            background: #d1ecf1;
        }
        .log-entry.system {
            border-left-color: #6c757d;
            background: #e2e3e5;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .critical-error {
            background: #dc3545 !important;
            color: white;
            border-left-color: #721c24 !important;
        }
        .log-entry {
            border-left: 4px solid;
            margin-bottom: 8px;
            padding: 12px;
            border-radius: 4px;
        }
        .log-error {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        .log-warning {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        .log-info {
            border-left-color: #17a2b8;
            background-color: #d1ecf1;
        }
        .log-level-badge {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
        }
        .log-source-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 8px;
        }
        .log-details {
            margin-top: 4px;
        }
        .log-details strong {
            color: #495057;
        }
        .file-path {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        .url-path {
            font-family: 'Courier New', monospace;
            background-color: #e3f2fd;
            color: #1565c0;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 0.85em;
            font-weight: bold;
            font-size: 0.8rem;
        }
        .url-path a {
            color: #1565c0;
            text-decoration: none;
        }
        .url-path a:hover {
            color: #0d47a1;
            text-decoration: underline;
        }
        .logs-scroll-container {
            max-height: 70vh;
            overflow-y: auto;
            position: relative;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .log-row-selected {
            background-color: #e8f0fe !important;
        }
        .log-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .sticky-thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa !important;
            z-index: 10;
            box-shadow: inset 0 -1px 0 #dee2e6;
        }
        .log-message-cell {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 0.85rem;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .copy-actions {
            background: #fff;
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 11;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo get_admin_sidebar_menu('admin_logs.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Header -->
                <div class="navbar-admin p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h3 mb-0">
                            <i class="fas fa-chart-line text-primary"></i>
                            Panel de Monitoreo de Logs
                        </h1>
                        <div class="d-flex align-items-center gap-3">
                            <div class="auto-refresh">
                                <?php if ($filtro_tiempo_real): ?>
                                <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                                    <i class="fas fa-sync-alt fa-spin"></i> Actualización automática activada
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshLogs()" title="Actualizar logs del sistema">
                                    <i class="fas fa-sync-alt me-1"></i>
                                    Actualizar
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="copyLogs(false)" title="Copiar todos los logs visibles">
                                    <i class="fas fa-copy me-1"></i>
                                    Copiar Todos
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="copyLogs(true)" title="Copiar solo los seleccionados">
                                    <i class="fas fa-check-double me-1"></i>
                                    Copiar Seleccionados
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearLogs()" title="Limpiar todos los logs del sistema">
                                    <i class="fas fa-trash-alt me-1"></i>
                                    Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-4">

        <!-- Estado de Tiempo Real -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="alert-heading mb-1">
                                <i class="fas fa-satellite-dish me-2"></i>
                                Modo Tiempo Real Activado
                            </h6>
                            <p class="mb-0">
                                Los logs se actualizan automáticamente cada <span id="refresh-interval">5</span> segundos
                                <span id="last-update-time" class="ms-2 text-muted"></span>
                            </p>
                        </div>
                        <div>
                            <span id="connection-status" class="badge bg-success">
                                <i class="fas fa-wifi me-1"></i>Conectado
                            </span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" id="error-count"><?php echo $stats['error_logs']; ?></h4>
                                <small>Errores Reales</small>
                            </div>
                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                        </div>
                        <div class="mt-2" id="recent-errors" style="display: none;">
                            <small><i class="fas fa-clock"></i> <span id="recent-errors-count">0</span> en la última hora</small>
                        </div>
                    </div>
                </div>
                                </div>
            <div class="col-md-3">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" id="warning-count"><?php echo $stats['warning_logs']; ?></h4>
                                <small>Advertencias</small>
                            </div>
                            <i class="fas fa-exclamation-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
                                </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" id="info-count"><?php echo $stats['info_logs']; ?></h4>
                                <small>Información</small>
                            </div>
                            <i class="fas fa-info-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
                                </div>
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" id="total-count"><?php echo $stats['total_logs']; ?></h4>
                                <small>Total Logs</small>
                            </div>
                            <i class="fas fa-list fa-2x opacity-75"></i>
                        </div>
                    </div>
                                </div>
                            </div>
                        </div>


        

                    <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                        <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                <label class="form-label">Fuente</label>
                                <select name="fuente" class="form-select">
                                    <option value="">Todas las fuentes</option>
                                    <?php foreach ($logSources as $key => $source): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $filtro_fuente === $key ? 'selected' : ''; ?>>
                                        <?php echo $source['name']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Nivel</label>
                                    <select name="nivel" class="form-select">
                                    <option value="">Todos los niveles</option>
                                    <option value="error" <?php echo $filtro_nivel === 'error' ? 'selected' : ''; ?>>Error</option>
                                    <option value="warning" <?php echo $filtro_nivel === 'warning' ? 'selected' : ''; ?>>Warning</option>
                                    <option value="info" <?php echo $filtro_nivel === 'info' ? 'selected' : ''; ?>>Info</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $filtro_fecha_inicio; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="<?php echo $filtro_fecha_fin; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Búsqueda</label>
                                <input type="text" name="busqueda" class="form-control" placeholder="Buscar en mensajes..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                </div>
                            </form>
                            <div class="row mt-3">
                                <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="tiempo_real" name="tiempo_real" value="1" <?php echo $filtro_tiempo_real ? 'checked' : ''; ?> onchange="toggleAutoRefresh()">
                                    <label class="form-check-label" for="tiempo_real">
                                        <i class="fas fa-sync-alt"></i> Actualización automática (cada 10 segundos)
                                    </label>
                                </div>
                            </div>
                        </div>
                                </div>
                            </div>
                        </div>
                    </div>

        <!-- Errores Críticos -->
        <?php if (!empty($criticalErrors)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle"></i>
                            Errores Críticos Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="logs-scroll-container" style="max-height: 300px;">
                            <div id="critical-logs-list">
                            <?php foreach (array_slice($criticalErrors, 0, 10) as $log): ?>
                            <div class="log-entry critical-error">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-light text-dark me-2 source-badge">
                                                <?php echo getSourceName($log['source'], $logSources); ?>
                                            </span>
                                            <small class="text-light">
                                                <?php echo date('d/m/Y H:i:s', $log['timestamp']); ?>
                                            </small>
                                        </div>
                                        <div class="log-message">
                                            <?php echo htmlspecialchars($log['message']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Logs Principales -->
        <div class="row">
            <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i>
                            Logs del Sistema
                        </h5>
                        <span class="badge bg-secondary" id="logs-count">
                            <span id="logs-count-number">0</span> logs mostrados
                        </span>
                        </div>
                        <div class="card-body">
                        <div class="card-body p-0">
                            <!-- Tabs de navegación -->
                            <ul class="nav nav-tabs nav-fill" id="logTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="system-tab" data-bs-toggle="tab" data-bs-target="#system-logs" type="button" role="tab" aria-controls="system-logs" aria-selected="true">
                                        <i class="fas fa-server me-2"></i>Logs del Sistema
                                        <span class="badge bg-danger ms-2" id="system-logs-count">0</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-logs" type="button" role="tab" aria-controls="activity-logs" aria-selected="false">
                                        <i class="fas fa-history me-2"></i>Actividad
                                        <span class="badge bg-info ms-2" id="activity-logs-count">0</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-logs" type="button" role="tab" aria-controls="security-logs" aria-selected="false">
                                        <i class="fas fa-shield-alt me-2"></i>Seguridad / Ruido
                                        <span class="badge bg-secondary ms-2" id="security-logs-count">0</span>
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="logTabsContent">
                                <!-- Tab: Logs del Sistema -->
                                <div class="tab-pane fade show active" id="system-logs" role="tabpanel" aria-labelledby="system-tab">
                                    <div class="logs-scroll-container">
                                        <table class="table table-hover mb-0" id="logs-table">
                                            <thead class="sticky-thead">
                                                <tr>
                                                    <th style="width: 40px;"><input type="checkbox" id="select-all-system" onchange="toggleSelectAll('system')"></th>
                                                    <th style="width: 50px;">Nivel</th>
                                                    <th style="width: 150px;">Fecha</th>
                                                    <th style="width: 120px;">Fuente</th>
                                                    <th>Mensaje</th>
                                                    <th style="width: 80px;">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="logs-table-body">
                                                <!-- System logs loaded here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Tab: Logs de Actividad -->
                                <div class="tab-pane fade" id="activity-logs" role="tabpanel" aria-labelledby="activity-tab">
                                    <div class="logs-scroll-container">
                                        <table class="table table-hover mb-0" id="activity-table">
                                            <thead class="sticky-thead">
                                                <tr>
                                                    <th style="width: 40px;"><input type="checkbox" id="select-all-activity" onchange="toggleSelectAll('activity')"></th>
                                                    <th style="width: 50px;">Nivel</th>
                                                    <th style="width: 150px;">Fecha</th>
                                                    <th>Mensaje</th>
                                                    <th style="width: 80px;">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="activity-table-body">
                                                 <!-- Activity logs loaded here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tab: Seguridad / Ruido -->
                                <div class="tab-pane fade" id="security-logs" role="tabpanel" aria-labelledby="security-tab">
                                    <div class="logs-scroll-container">
                                        <table class="table table-hover mb-0" id="security-table">
                                            <thead class="sticky-thead">
                                                <tr>
                                                    <th style="width: 40px;"><input type="checkbox" id="select-all-security" onchange="toggleSelectAll('security')"></th>
                                                    <th style="width: 50px;">Nivel</th>
                                                    <th style="width: 150px;">Fecha</th>
                                                    <th style="width: 120px;">Fuente</th>
                                                    <th>Mensaje</th>
                                                    <th style="width: 80px;">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="security-table-body">
                                                 <!-- Security logs loaded here -->
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

    <!-- Modal de Detalles del Log -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Detalles del Log
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <span id="modal-level-badge" class="badge me-2"></span>
                        <span id="modal-source-badge" class="badge bg-secondary me-2"></span>
                        <span id="modal-time" class="text-muted"></span>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Mensaje:</h6>
                        <div class="p-3 bg-light rounded border" style="max-height: 300px; overflow-y: auto;">
                            <pre class="mb-0" id="modal-message" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                        </div>
                    </div>

                    <div id="modal-extra-details">
                        <!-- Extra details injected by JS -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="copyModalContent()">
                        <i class="fas fa-copy me-1"></i> Copiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Instrucciones de Actualización -->
    <div class="modal fade" id="updateInstructionsModal" tabindex="-1" aria-labelledby="updateInstructionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateInstructionsModalLabel">
                        <i class="fas fa-question-circle me-2"></i>
                        Instrucciones de Actualización de Logs
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-terminal me-2"></i>Actualización Manual</h6>
                            <p>Para actualizar los logs manualmente, ejecuta este comando en el servidor:</p>
                            <div class="bg-dark text-light p-3 rounded mb-3">
                                <code>cd /home/admin/web/codigoamigo.com/public_html/logs && ./copy_logs.sh</code>
                                </div>

                            <h6><i class="fas fa-clock me-2"></i>Actualización Automática</h6>
                            <p>Para configurar actualización automática cada 5 minutos:</p>
                            <div class="bg-dark text-light p-3 rounded mb-3">
                                <code>sudo ./setup_cron.sh</code>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle me-2"></i>Información del Sistema</h6>
                            <ul class="list-unstyled">
                                <li><strong>Estado actual:</strong> 
                                    <span class="text-success">Sistema en tiempo real activo</span>
                                </li>
                                <li><strong>Última actualización:</strong> En tiempo real</li>
                                <li><strong>Script disponible:</strong> 
                                    <span class="text-success">Acceso directo a logs</span>
                                </li>
                            </ul>
                            
                            <h6><i class="fas fa-lightbulb me-2"></i>Consejos</h6>
                            <ul class="small">
                                <li>Los logs se actualizan cada 5 minutos automáticamente si está configurado el cron</li>
                                <li>El botón "Actualizar Logs" recarga la página para mostrar los datos más recientes</li>
                                <li>Los logs copiados contienen las últimas 1000 líneas de cada archivo</li>
                                    </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="refreshLogs(); bootstrap.Modal.getInstance(document.getElementById('updateInstructionsModal')).hide();">
                        <i class="fas fa-sync-alt me-1"></i>
                        Actualizar Ahora
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let lastTimestamp = <?php echo $lastTimestamp; ?>;
        let refreshInterval = 5000; // 5 segundos
        let refreshTimer = null;
        let isConnected = true;
        let currentLogs = []; // Almacenar logs para copia masiva

        function toggleSelectAll(type) {
            const master = document.getElementById(`select-all-${type}`);
            const tabId = type === 'system' ? 'system-logs' : 'activity-logs';
            const checkboxes = document.querySelectorAll(`#${tabId} .log-checkbox`);
            checkboxes.forEach(cb => {
                cb.checked = master.checked;
                const row = cb.closest('tr');
                if (master.checked) row.classList.add('log-row-selected');
                else row.classList.remove('log-row-selected');
            });
        }

        function toggleRowSelection(checkbox) {
            const row = checkbox.closest('tr');
            if (checkbox.checked) row.classList.add('log-row-selected');
            else row.classList.remove('log-row-selected');
        }

        function copyLogs(selectedOnly = false) {
            const activeTab = document.querySelector('.tab-pane.active');
            const checkboxes = activeTab.querySelectorAll('.log-checkbox');
            let logsToCopy = [];

            checkboxes.forEach((cb, index) => {
                if (!selectedOnly || cb.checked) {
                    const row = cb.closest('tr');
                    const date = row.cells[2].innerText;
                    const source = activeTab.id === 'system-logs' ? row.cells[3].innerText : 'Activity';
                    const message = row.cells[activeTab.id === 'system-logs' ? 4 : 3].innerText;
                    const level = row.cells[1].querySelector('i').title;

                    logsToCopy.push(`[${date}] [${level.toUpperCase()}] [${source}] ${message}`);
                }
            });

            if (logsToCopy.length === 0) {
                alert(selectedOnly ? 'No hay logs seleccionados' : 'No hay logs para copiar');
                return;
            }

            const textToCopy = logsToCopy.join('\n');
            navigator.clipboard.writeText(textToCopy).then(() => {
                alert(`¡${logsToCopy.length} logs copiados al portapapeles!`);
            }).catch(err => {
                console.error('Error al copiar:', err);
                // Fallback para navegadores antiguos o sin SSL
                const textArea = document.createElement("textarea");
                textArea.value = textToCopy;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Logs copiados (fallback)');
            });
        }
        
        // Función para obtener logs en tiempo real
        async function fetchLogs() {
            try {
                const params = new URLSearchParams({
                    action: 'get_logs',
                    lines: 100,
                    last_timestamp: lastTimestamp,
                    level: document.querySelector('select[name="nivel"]')?.value || '',
                    search: document.querySelector('input[name="busqueda"]')?.value || '',
                    source: document.querySelector('select[name="fuente"]')?.value || ''
                });
                
                const response = await fetch(`logs_simple.php?${params}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    console.log('Logs recibidos:', data.logs.length, 'logs');
                    
                    // Si hay nuevos logs, reconstruir la tabla
                    if (data.logs.length > 0) {
                         // Combinar con los existentes o reemplazar según la lógica (aquí reemplazamos para simplificar la vista de tabla)
                         // Nota: En una implementación ideal, añadiríamos al principio, pero para simplificar el DOM reemplazamos
                        updateLogsDisplay(data.logs);
                    }
                    
                    lastTimestamp = data.timestamp;
                    updateConnectionStatus(true);
                    updateLastUpdateTime();
                } else {
                    console.error('Error fetching logs:', data.error);
                    updateConnectionStatus(false);
                }
            } catch (error) {
                console.error('Error:', error);
                updateConnectionStatus(false);
            }
        }
        
        // Función para obtener estadísticas
        async function fetchStats() {
            try {
               // ... mantener igual ...
                const response = await fetch('logs_simple.php?action=get_stats');
                const data = await response.json();
                
                if (data.success) {
                    updateStatsDisplay(data.stats);
                }
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        }

        // Función para actualizar la visualización de logs
        function updateLogsDisplay(logs) {
            const systemContainer = document.getElementById('logs-table-body');
            const activityContainer = document.getElementById('activity-table-body');
            const securityContainer = document.getElementById('security-table-body');
            
            // 1. Identificar logs de Actividad (Acortador)
            const activityLogs = logs.filter(log => 
                log.source === 'activity' || 
                log.message.toLowerCase().includes('acortador chollo')
            );

            // 2. Identificar logs de Seguridad / Ruido (Bots, Forbidden, etc.)
            const securityKeywords = ['directory index of', 'is forbidden', '.well-known', 'favicon.ico'];
            const securityLogs = logs.filter(log => 
                !activityLogs.includes(log) && 
                (securityKeywords.some(kw => log.message.toLowerCase().includes(kw)) || 
                 log.status_code === 403 || log.status_code === 404)
            );

            // 3. El resto son Logs del Sistema (Errores PHP, Fatal, etc.)
            const systemLogs = logs.filter(log => 
                !activityLogs.includes(log) && !securityLogs.includes(log)
            );
            
            // Actualizar contadores de tabs
            document.getElementById('system-logs-count').textContent = systemLogs.length;
            document.getElementById('activity-logs-count').textContent = activityLogs.length;
            document.getElementById('security-logs-count').textContent = securityLogs.length;
            
            // Renderizar tablas
            renderLogTable(systemContainer, systemLogs, true);
            renderLogTable(activityContainer, activityLogs, false);
            renderLogTable(securityContainer, securityLogs, true);
            
            // Actualizar contador total (cabecera)
            const countElement = document.getElementById('logs-count-number');
            if (countElement) {
                countElement.textContent = logs.length;
            }
        }
        
        function renderLogTable(container, logs, showSource) {
            if (logs.length === 0) {
                const colspan = showSource ? 5 : 4;
                container.innerHTML = `<tr><td colspan="${colspan}" class="text-center p-4 text-muted">No hay logs para mostrar</td></tr>`;
                return;
            }
            
            const html = logs.map(log => {
                const date = new Date(log.timestamp * 1000);
                const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                
                const icon = getLogTypeIcon(log.level);
                // Si es activity, usar azul info, si es error sistema usar rojo
                const rowClass = (log.level === 'error' && log.source !== 'activity') ? 'table-danger' : '';
                
                // Truncar mensaje
                let shortMessage = log.message;
                if (shortMessage.length > 150) {
                    shortMessage = shortMessage.substring(0, 150) + '...';
                }
                
                const logJson = JSON.stringify(log).replace(/'/g, "&#39;");

                let sourceCell = '';
                if (showSource) {
                     sourceCell = `
                        <td>
                            <span class="badge bg-secondary source-badge">
                                ${formatSourceName(log.source)}
                            </span>
                        </td>`;
                }

                return `
                    <tr class="${rowClass}">
                        <td class="text-center">
                            <input type="checkbox" class="log-checkbox" onchange="toggleRowSelection(this)">
                        </td>
                        <td class="text-center">
                            <i class="${icon}" title="${log.level}"></i>
                        </td>
                        <td>
                            <small>${formattedDate}</small>
                        </td>
                        ${sourceCell}
                        <td class="log-message-cell">
                            ${escapeHtml(log.message)}
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick='viewLogDetails(${logJson})'>
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
            
            container.innerHTML = html;
        }
        
        // Ver detalles en Modal
        function viewLogDetails(log) {
            // Set basic info
            const date = new Date(log.timestamp * 1000);
            document.getElementById('modal-time').textContent = date.toLocaleString();
            document.getElementById('modal-message').textContent = log.message;
            
            // Set badges
            const levelBadge = document.getElementById('modal-level-badge');
            levelBadge.textContent = log.level.toUpperCase();
            levelBadge.className = 'badge me-2 bg-' + getLogLevelColor(log.level);
            
            const sourceBadge = document.getElementById('modal-source-badge');
            sourceBadge.textContent = formatSourceName(log.source);
            
            // Extra details
            const extraContainer = document.getElementById('modal-extra-details');
            let extraHtml = '';
            
            if (log.file_path) {
                extraHtml += `<div class="mb-2"><strong>Archivo Log:</strong> <code>${log.file_path}</code></div>`;
            }
            
            if (log.url) {
                extraHtml += `<div class="mb-2"><strong>URL:</strong> <a href="https://www.codigoamigo.com${log.url}" target="_blank">${log.url}</a></div>`;
            }
            
            if (log.status_code) {
                 extraHtml += `<div class="mb-2"><strong>Status Code:</strong> <span class="badge bg-dark">${log.status_code}</span></div>`;
            }

            extraContainer.innerHTML = extraHtml;
            
            // Show Modal
            const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
            modal.show();
        }

        function copyModalContent() {
            const text = document.getElementById('modal-message').textContent;
            navigator.clipboard.writeText(text).then(() => {
                alert('Log copiado al portapapeles');
            });
        }

        function formatSourceName(source) {
            return source.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function getLogTypeIcon(level) {
            const icons = {
                'error': 'fas fa-exclamation-triangle text-danger',
                'warning': 'fas fa-exclamation-circle text-warning',
                'info': 'fas fa-info-circle text-info',
                'access': 'fas fa-globe text-primary',
                'system': 'fas fa-cog text-secondary'
            };
            return icons[level] || 'fas fa-info-circle';
        }

        function getLogLevelColor(level) {
            const colors = {
                'error': 'danger',
                'warning': 'warning',
                'info': 'info',
                'access': 'primary',
                'system': 'secondary'
            };
            return colors[level] || 'secondary';
        }
        
        function escapeHtml(text) {
             if (!text) return text;
             return text
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        function updateStatsDisplay(stats) {
            animateNumber('total-count', stats.total_logs);
            animateNumber('error-count', stats.error_logs);
            animateNumber('warning-count', stats.warning_logs);
            animateNumber('info-count', stats.info_logs);
            
            // Mostrar alerta de errores recientes si hay
            if (stats.recent_errors > 0) {
                document.getElementById('recent-errors').style.display = 'block';
                document.getElementById('recent-errors-count').textContent = stats.recent_errors;
                
                // Si es la primera vez que detectamos errores recientes, mostrar notificación
                if (stats.recent_errors > 0 && document.title.indexOf('(!)') === -1) {
                    document.title = '(!) ' + document.title;
                }
            } else {
                document.getElementById('recent-errors').style.display = 'none';
                document.title = document.title.replace('(!) ', '');
            }
        }
        
        function animateNumber(elementId, target) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            const current = parseInt(element.textContent);
            if (current === target) return;
            
            // Animación simple
            element.classList.add('text-primary'); // Highlight effect
            element.textContent = target;
            setTimeout(() => element.classList.remove('text-primary'), 500);
        }
        
        function updateConnectionStatus(connected) {
            const statusEl = document.getElementById('connection-status');
            isConnected = connected;
            
            if (connected) {
                statusEl.className = 'badge bg-success';
                statusEl.innerHTML = '<i class="fas fa-wifi me-1"></i>Conectado';
            } else {
                statusEl.className = 'badge bg-danger';
                statusEl.innerHTML = '<i class="fas fa-wifi-slash me-1"></i>Desconectado';
            }
        }
        
        function updateLastUpdateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('last-update-time').textContent = 'Actualizado: ' + timeString;
        }

        function refreshLogs() {
            fetchLogs();
            fetchStats();
        }
        
        function clearLogs() {
            if(confirm('¿Estás seguro de que deseas limpiar la vista actual?')) {
                document.getElementById('logs-table-body').innerHTML = '';
                document.getElementById('logs-count-number').textContent = '0';
            }
        }
        
        function showUpdateInstructions() {
            new bootstrap.Modal(document.getElementById('updateInstructionsModal')).show();
        }
        
        function toggleAutoRefresh() {
            const checkbox = document.getElementById('tiempo_real');
            const isActive = checkbox.checked;
            
            const alert = document.querySelector('.alert-success');
            if (alert) alert.style.display = isActive ? 'block' : 'none';
            
            if (isActive) {
                // Iniciar timer
                fetchLogs(); // Primera carga inmediata
                fetchStats();
                refreshTimer = setInterval(() => {
                    fetchLogs();
                    fetchStats();
                }, refreshInterval);
                
                // Actualizar URL sin recargar
                const url = new URL(window.location);
                url.searchParams.set('tiempo_real', '1');
                window.history.pushState({}, '', url);
                
                // Mostrar indicador
                document.querySelector('.alert-info').style.display = 'block';
            } else {
                // Detener timer
                if (refreshTimer) clearInterval(refreshTimer);
                refreshTimer = null;
                
                // Actualizar URL
                const url = new URL(window.location);
                url.searchParams.delete('tiempo_real');
                window.history.pushState({}, '', url);
                
                // Ocultar indicador
                document.querySelector('.alert-info').style.display = 'none';
            }
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', () => {
             // Cargar logs al inicio
            refreshLogs();
            
            // Configurar auto-refresh si está activo
            if (document.getElementById('tiempo_real').checked) {
                toggleAutoRefresh();
            }
        });
    </script>
</body>