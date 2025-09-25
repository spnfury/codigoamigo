<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';

// Incluir sistema de logging organizado
require_once __DIR__ . '/../inc/logger.php';
include_once __DIR__ . '/../inc/log_monitor.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

if (!in_array($_SESSION["user_id"], $array_codigos_acceso)) {
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
        .logs-container {
            max-height: 600px;
            overflow-y: auto;
        }
        .auto-refresh {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .source-badge {
            font-size: 0.75rem;
        }
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.75rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .copy-btn:hover {
            opacity: 1;
            background: #495057;
        }
        .copy-btn.copied {
            background: #28a745;
        }
        .log-entry {
            position: relative;
        }
    </style>
</head>
<body class="bg-light">
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
                        <a class="nav-link" href="admin_reportes.php">
                            <i class="fas fa-chart-bar me-2"></i>Reportes
                        </a>
                        <a class="nav-link" href="admin_configuracion.php">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </a>
                        <a class="nav-link active" href="admin_logs.php">
                            <i class="fas fa-file-alt me-2"></i>Logs
                        </a>
                        <hr class="text-white">
                        <a class="nav-link" href="https://www.codigoamigo.com">
                            <i class="fas fa-home me-2"></i>Volver al Sitio
                        </a>
                    </nav>
                </div>
            </div>

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
                                    Actualizar Logs
                            </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearLogs()" title="Limpiar todos los logs del sistema">
                                    <i class="fas fa-trash-alt me-1"></i>
                                    Limpiar Logs
                            </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showUpdateInstructions()" title="Ver instrucciones de actualización">
                                    <i class="fas fa-question-circle me-1"></i>
                                    Ayuda
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
                        <div class="logs-container">
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
                        <div class="logs-container" id="logs-container">
                            <?php foreach ($logs as $log): ?>
                            <div class="log-entry <?php echo $log['level']; ?>">
                                <button class="copy-btn" onclick="copyLogToClipboard(this)" title="Copiar error completo">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="<?php echo getLogTypeIcon($log['level']); ?> me-2"></i>
                                            <span class="badge bg-<?php echo getLogLevelColor($log['level']); ?> me-2">
                                                <?php echo strtoupper($log['level']); ?>
                                            </span>
                                            <span class="badge bg-secondary me-2 source-badge">
                                                <?php echo getSourceName($log['source'], $logSources); ?>
                                            </span>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i:s', $log['timestamp']); ?>
                                            </small>
                                        </div>
                                        <div class="log-message mb-2">
                                            <?php echo htmlspecialchars($log['message']); ?>
                                        </div>
                                        <?php if (isset($log['file']) && isset($log['line'])): ?>
                                        <div class="log-details">
                                            <small class="text-muted">
                                                <i class="fas fa-file"></i>
                                                <?php echo htmlspecialchars($log['file'] . ':' . $log['line']); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (isset($log['url']) && !empty($log['url'])): ?>
                                        <div class="log-details">
                                            <small class="text-muted">
                                                <i class="fas fa-link me-1"></i>
                                                <strong>URL Interna:</strong> <span class="url-path"><?php echo htmlspecialchars($log['url']); ?></span>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (isset($log['external_url']) && !empty($log['external_url'])): ?>
                                        <div class="log-details">
                                            <small class="text-muted">
                                                <i class="fas fa-external-link-alt me-1"></i>
                                                <strong>URL Externa:</strong> <a href="<?php echo htmlspecialchars($log['external_url']); ?>" target="_blank" class="url-path"><?php echo htmlspecialchars($log['external_url']); ?></a>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (isset($log['ip'])): ?>
                                        <div class="log-details">
                                            <small class="text-muted">
                                                <i class="fas fa-globe"></i>
                                                <?php echo htmlspecialchars($log['ip']); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs de MongoDB (si están disponibles) -->
        <?php if (!empty($mongoLogs)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-database"></i>
                            Logs de MongoDB
                        </h5>
                    </div>
                    <div class="card-body">
                                <div class="logs-container">
                            <?php foreach ($mongoLogs as $log): ?>
                                    <div class="log-entry <?php echo $log['nivel'] ?? 'info'; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="<?php echo getLogTypeIcon($log['tipo'] ?? 'info'); ?> me-2"></i>
                                                    <span class="badge bg-<?php echo getLogLevelColor($log['nivel'] ?? 'info'); ?> me-2">
                                                        <?php echo strtoupper($log['nivel'] ?? 'INFO'); ?>
                                                    </span>
                                                    <span class="badge bg-secondary me-2">
                                                        <?php echo ucfirst(str_replace('_', ' ', $log['tipo'] ?? 'info')); ?>
                                                    </span>
                                                    <small class="text-muted">
                                                <?php 
                                                if (isset($log['fecha']) && $log['fecha'] !== null) {
                                                    echo date('d/m/Y H:i:s', $log['fecha']->toDateTime()->getTimestamp());
                                                } else {
                                                    echo 'Fecha no disponible';
                                                }
                                                ?>
                                                    </small>
                                                </div>
                                                <div class="log-message mb-2">
                                                    <?php echo htmlspecialchars($log['mensaje'] ?? ''); ?>
                                                </div>
                                                <div class="log-details">
                                                    <?php if (isset($log['usuario_id']) && $log['usuario_id']): ?>
                                                        <span class="me-3">
                                                            <i class="fas fa-user me-1"></i>
                                                            <?php echo htmlspecialchars($usuarios_info[$log['usuario_id']] ?? 'Usuario desconocido'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (isset($log['ip'])): ?>
                                                        <span class="me-3">
                                                            <i class="fas fa-globe me-1"></i>
                                                            <?php echo htmlspecialchars($log['ip']); ?>
                                                        </span>
                                                    <?php endif; ?>
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
                    updateLogsDisplay(data.logs);
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
                const response = await fetch('logs_simple.php?action=get_stats');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    console.log('Estadísticas recibidas:', data.stats);
                    updateStats(data.stats);
                } else {
                    console.error('Error fetching stats:', data.error);
                }
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        }
        
        // Función para actualizar la visualización de logs
        function updateLogsDisplay(logs) {
            const container = document.getElementById('logs-container');
            const countElement = document.getElementById('logs-count-number');
            
            console.log('Actualizando display con', logs.length, 'logs');
            
            if (logs.length > 0) {
                // Agregar nuevos logs al principio
                logs.forEach(log => {
                    const logElement = createLogElement(log);
                    container.insertBefore(logElement, container.firstChild);
                });
                
                // Limitar a 200 logs para evitar problemas de rendimiento
                const allLogs = container.querySelectorAll('.log-entry');
                if (allLogs.length > 200) {
                    for (let i = 200; i < allLogs.length; i++) {
                        allLogs[i].remove();
                    }
                }
            }
            
            // Actualizar contador
            const totalLogs = container.querySelectorAll('.log-entry').length;
            countElement.textContent = totalLogs;
            
            console.log('Total logs en pantalla:', totalLogs);
            
            // Scroll automático si está en la parte inferior
            const isScrolledToBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 10;
            if (isScrolledToBottom) {
                container.scrollTop = container.scrollHeight;
            }
        }
        
        // Función para crear elemento de log
        function createLogElement(log) {
            const div = document.createElement('div');
            div.className = `log-entry log-${log.level}`;
            
                // Determinar color del badge de nivel
                let levelBadgeClass = 'bg-secondary';
                if (log.level === 'error') levelBadgeClass = 'bg-danger';
                else if (log.level === 'warning') levelBadgeClass = 'bg-warning text-dark';
                else if (log.level === 'info') levelBadgeClass = 'bg-info';
            
            // Determinar color del badge de fuente
            let sourceBadgeClass = 'bg-secondary';
            if (log.source.includes('php')) sourceBadgeClass = 'bg-primary';
            else if (log.source.includes('apache')) sourceBadgeClass = 'bg-success';
            else if (log.source.includes('nginx')) sourceBadgeClass = 'bg-warning text-dark';
            else if (log.source.includes('mysql')) sourceBadgeClass = 'bg-danger';
            
            div.innerHTML = `
                <button class="copy-btn" onclick="copyLogToClipboard(this)" title="Copiar error completo">
                    <i class="fas fa-copy"></i>
                </button>
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2 flex-wrap">
                            <span class="log-level-badge ${levelBadgeClass} me-2">
                                ${log.level.toUpperCase()}
                            </span>
                            <span class="log-source-badge ${sourceBadgeClass} me-2">
                                ${getSourceName(log.source)}
                            </span>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>${formatTimestamp(log.timestamp)}
                            </small>
                        </div>
                        <div class="log-message mb-2">
                            <code class="text-dark">${escapeHtml(log.message)}</code>
                        </div>
                            <div class="log-details mb-1">
                                <small class="text-muted">
                                    <i class="fas fa-file-alt me-1"></i> 
                                    <strong>Archivo:</strong> <span class="file-path">${escapeHtml(log.file_path || 'unknown')}</span>
                                </small>
                            </div>
                            ${log.url ? `<div class="log-details mb-1"><small class="text-muted"><i class="fas fa-link me-1"></i> <strong>URL Interna:</strong> <span class="url-path">${escapeHtml(log.url)}</span></small></div>` : ''}
                            ${log.external_url ? `<div class="log-details mb-1"><small class="text-muted"><i class="fas fa-external-link-alt me-1"></i> <strong>URL Externa:</strong> <a href="${escapeHtml(log.external_url)}" target="_blank" class="url-path">${escapeHtml(log.external_url)}</a></small></div>` : ''}
                            ${log.ip ? `<div class="log-details"><small class="text-muted"><i class="fas fa-globe me-1"></i> IP: ${escapeHtml(log.ip)}</small></div>` : ''}
                            ${log.file ? `<div class="log-details"><small class="text-muted"><i class="fas fa-code me-1"></i> ${escapeHtml(log.file)}:${log.line}</small></div>` : ''}
                    </div>
                </div>
            `;
            return div;
        }
        
        // Función para actualizar estadísticas
        function updateStats(stats) {
            console.log('Actualizando estadísticas:', stats);
            if (stats) {
                document.getElementById('error-count').textContent = stats.error_logs || 0;
                document.getElementById('warning-count').textContent = stats.warning_logs || 0;
                document.getElementById('info-count').textContent = stats.info_logs || 0;
                document.getElementById('total-count').textContent = stats.total_logs || 0;
                
                const recentErrorsDiv = document.getElementById('recent-errors');
                const recentErrorsCount = document.getElementById('recent-errors-count');
                
                if (stats.recent_errors > 0) {
                    recentErrorsCount.textContent = stats.recent_errors;
                    recentErrorsDiv.style.display = 'block';
                } else {
                    recentErrorsDiv.style.display = 'none';
                }
            }
        }
        
        // Función para actualizar estado de conexión
        function updateConnectionStatus(connected) {
            const statusElement = document.getElementById('connection-status');
            isConnected = connected;
            
            if (connected) {
                statusElement.className = 'badge bg-success';
                statusElement.innerHTML = '<i class="fas fa-wifi me-1"></i>Conectado';
            } else {
                statusElement.className = 'badge bg-danger';
                statusElement.innerHTML = '<i class="fas fa-wifi-slash me-1"></i>Desconectado';
            }
        }
        
        // Función para actualizar tiempo de última actualización
        function updateLastUpdateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('last-update-time').textContent = `Última actualización: ${timeString}`;
        }
        
        // Funciones auxiliares
        function getLogTypeIcon(level) {
            const icons = {
                'error': 'fas fa-exclamation-triangle text-danger',
                'warning': 'fas fa-exclamation-circle text-warning',
                'info': 'fas fa-info-circle text-info'
            };
            return icons[level] || 'fas fa-info-circle';
        }
        
        function getLogLevelColor(level) {
            const colors = {
                'error': 'danger',
                'warning': 'warning',
                'info': 'info'
            };
            return colors[level] || 'secondary';
        }
        
        function getSourceName(source) {
            const names = {
                'apache_error': 'Apache Error',
                'apache_access': 'Apache Access',
                'php_error': 'PHP Error',
                'php_fpm': 'PHP-FPM',
                'nginx_error': 'Nginx Error',
                'nginx_access': 'Nginx Access',
                'mysql_error': 'MySQL Error',
                'system': 'System'
            };
            return names[source] || source;
        }
        
        function formatTimestamp(timestamp) {
            const date = new Date(timestamp * 1000);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Función para copiar el contenido del log al portapapeles
        function copyLogToClipboard(button) {
            const logEntry = button.closest('.log-entry');
            const logContent = extractLogContent(logEntry);
            
            // Usar la API del portapapeles si está disponible
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(logContent).then(() => {
                    showCopyFeedback(button);
                }).catch(err => {
                    console.error('Error al copiar al portapapeles:', err);
                    fallbackCopyToClipboard(logContent, button);
                });
            } else {
                // Fallback para navegadores más antiguos o contextos no seguros
                fallbackCopyToClipboard(logContent, button);
            }
        }
        
        // Función para extraer el contenido completo del log
        function extractLogContent(logEntry) {
            const content = [];
            
            // Extraer nivel y fuente
            const levelBadge = logEntry.querySelector('.log-level-badge');
            const sourceBadge = logEntry.querySelector('.log-source-badge');
            const timestamp = logEntry.querySelector('.text-muted');
            
            if (levelBadge) content.push(`Nivel: ${levelBadge.textContent.trim()}`);
            if (sourceBadge) content.push(`Fuente: ${sourceBadge.textContent.trim()}`);
            if (timestamp) content.push(`Timestamp: ${timestamp.textContent.trim()}`);
            
            // Extraer mensaje principal
            const message = logEntry.querySelector('.log-message code');
            if (message) {
                content.push(`\nMensaje:`);
                content.push(message.textContent);
            }
            
            // Extraer detalles adicionales
            const details = logEntry.querySelectorAll('.log-details');
            details.forEach(detail => {
                const text = detail.textContent.trim();
                if (text) {
                    content.push(`\n${text}`);
                }
            });
            
            return content.join('\n');
        }
        
        // Función de respaldo para copiar al portapapeles
        function fallbackCopyToClipboard(text, button) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopyFeedback(button);
                } else {
                    console.error('No se pudo copiar al portapapeles');
                }
            } catch (err) {
                console.error('Error al copiar al portapapeles:', err);
            } finally {
                document.body.removeChild(textArea);
            }
        }
        
        // Función para mostrar feedback visual de copia exitosa
        function showCopyFeedback(button) {
            const originalIcon = button.innerHTML;
            const originalClass = button.className;
            
            // Cambiar a estado "copiado"
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.className = originalClass + ' copied';
            button.title = '¡Copiado!';
            
            // Restaurar después de 2 segundos
            setTimeout(() => {
                button.innerHTML = originalIcon;
                button.className = originalClass;
                button.title = 'Copiar error completo';
            }, 2000);
        }
        
        // Función para iniciar actualización automática
        function startAutoRefresh() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
            }
            
            refreshTimer = setInterval(() => {
                fetchLogs();
                fetchStats();
            }, refreshInterval);
        }
        
        // Función para detener actualización automática
        function stopAutoRefresh() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
                refreshTimer = null;
            }
        }
        
        // Función para actualizar logs manualmente
        function refreshLogs() {
            fetchLogs();
            fetchStats();
        }
        
        // Función para mostrar instrucciones
        function showUpdateInstructions() {
            const modal = new bootstrap.Modal(document.getElementById('updateInstructionsModal'));
            modal.show();
        }
        
        // Función para cambiar modo tiempo real
        function toggleAutoRefresh() {
            const checkbox = document.getElementById('tiempo_real');
            
            if (checkbox.checked) {
                startAutoRefresh();
                document.getElementById('refresh-interval').textContent = '5';
            } else {
                stopAutoRefresh();
                document.getElementById('refresh-interval').textContent = 'manual';
            }
        }
        
        // Inicialización cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar datos iniciales
            fetchLogs();
            fetchStats();
            
            // Iniciar actualización automática
            startAutoRefresh();
            
            // Configurar eventos
            document.getElementById('tiempo_real').addEventListener('change', toggleAutoRefresh);
            
            // Actualizar tiempo de última actualización cada segundo
            setInterval(updateLastUpdateTime, 1000);
        });
        
        // Limpiar timer cuando se cierra la página
        window.addEventListener('beforeunload', function() {
            stopAutoRefresh();
        });

    </script>
</body>
</html>