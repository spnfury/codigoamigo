<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';
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

$collection_email_logs = getCollectionEmailLogs();

// Obtener filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_enviado = $_GET['enviado'] ?? '';
$filtro_metodo = $_GET['metodo'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_fecha_inicio = $_GET['fecha_inicio'] ?? '';
$filtro_fecha_fin = $_GET['fecha_fin'] ?? '';

// Construir filtros
$filtros = [];

if ($filtro_tipo) {
    $filtros['tipo'] = $filtro_tipo;
}

if ($filtro_enviado !== '') {
    $filtros['enviado'] = (bool)$filtro_enviado;
}

if ($filtro_metodo) {
    $filtros['metodo'] = $filtro_metodo;
}

if ($filtro_busqueda) {
    $filtros['$or'] = [
        ['to_email' => ['$regex' => $filtro_busqueda, '$options' => 'i']],
        ['subject' => ['$regex' => $filtro_busqueda, '$options' => 'i']],
        ['to_name' => ['$regex' => $filtro_busqueda, '$options' => 'i']]
    ];
}

if ($filtro_usuario) {
    try {
        $filtros['usuario_id'] = new MongoDB\BSON\ObjectId($filtro_usuario);
    } catch (Exception $e) {
        $filtros['usuario_id'] = $filtro_usuario;
    }
}

if ($filtro_fecha_inicio || $filtro_fecha_fin) {
    $date_filter = [];
    if ($filtro_fecha_inicio) {
        $date_filter['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($filtro_fecha_inicio . ' 00:00:00') * 1000);
    }
    if ($filtro_fecha_fin) {
        $date_filter['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($filtro_fecha_fin . ' 23:59:59') * 1000);
    }
    if (!empty($date_filter)) {
        $filtros['fecha'] = $date_filter;
    }
}

// Paginación
$page = (int)($_GET['page'] ?? 1);
$limit = 100;
$skip = ($page - 1) * $limit;

// Obtener logs
$email_logs = $collection_email_logs->find(
    $filtros,
    ['sort' => ['fecha' => -1], 'skip' => $skip, 'limit' => $limit]
)->toArray();

$total_logs = $collection_email_logs->countDocuments($filtros);
$total_pages = ceil($total_logs / $limit);

// Obtener estadísticas
$stats = [
    'total' => $collection_email_logs->countDocuments([]),
    'enviados' => $collection_email_logs->countDocuments(['enviado' => true]),
    'error' => $collection_email_logs->countDocuments(['enviado' => false]),
    'hoy' => $collection_email_logs->countDocuments(['fecha' => ['$gte' => new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000)]])
];

// Funciones de formateo
function formatearFechaLog($fecha) {
    if ($fecha instanceof MongoDB\BSON\UTCDateTime) {
        return date('d/m/Y H:i:s', $fecha->toDateTime()->getTimestamp());
    } elseif (is_string($fecha)) {
        return date('d/m/Y H:i:s', strtotime($fecha));
    }
    return 'N/A';
}

function formatearTipoEmail($tipo) {
    $tipos = [
        'recarga_saldo' => 'Recarga Saldo',
        'notificacion' => 'Notificación',
        'activacion' => 'Activación Cuenta',
        'restablecimiento' => 'Restablecer Contraseña',
        'notificacion_codigo' => 'Notificación Código',
        'contacto' => 'Formulario Contacto',
        'publicacion' => 'Publicación Código'
    ];
    return $tipos[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
}

$title = "Logs de Emails - Admin";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
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
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .success-email { border-left: 4px solid #28a745; }
        .error-email { border-left: 4px solid #dc3545; }
        .email-preview-iframe {
            width: 100%;
            min-height: 400px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: white;
        }
        .html-source-code {
            max-height: 500px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .modal-lg { max-width: 900px; }
        .info-detail-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo get_admin_sidebar_menu('admin_email_logs.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-admin">
                    <div class="container-fluid">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope me-2"></i>Logs de Emails Enviados
                        </h5>
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
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                    <h4 class="text-primary"><?php echo number_format($stats['total']); ?></h4>
                                    <p class="text-muted mb-0">Total Emails</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                    <h4 class="text-success"><?php echo number_format($stats['enviados']); ?></h4>
                                    <p class="text-muted mb-0">Enviados</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                    <h4 class="text-danger"><?php echo number_format($stats['error']); ?></h4>
                                    <p class="text-muted mb-0">Errores</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-day fa-2x text-info mb-2"></i>
                                    <h4 class="text-info"><?php echo number_format($stats['hoy']); ?></h4>
                                    <p class="text-muted mb-0">Hoy</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Tipo</label>
                                    <select name="tipo" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="recarga_saldo" <?php echo $filtro_tipo == 'recarga_saldo' ? 'selected' : ''; ?>>Recarga Saldo</option>
                                        <option value="notificacion" <?php echo $filtro_tipo == 'notificacion' ? 'selected' : ''; ?>>Notificación</option>
                                        <option value="activacion" <?php echo $filtro_tipo == 'activacion' ? 'selected' : ''; ?>>Activación</option>
                                        <option value="restablecimiento" <?php echo $filtro_tipo == 'restablecimiento' ? 'selected' : ''; ?>>Restablecimiento</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Estado</label>
                                    <select name="enviado" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="1" <?php echo $filtro_enviado === '1' ? 'selected' : ''; ?>>Enviados</option>
                                        <option value="0" <?php echo $filtro_enviado === '0' ? 'selected' : ''; ?>>Errores</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Método</label>
                                    <select name="metodo" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="Brevo SMTP" <?php echo $filtro_metodo == 'Brevo SMTP' ? 'selected' : ''; ?>>Brevo</option>
                                        <option value="SendGrid" <?php echo $filtro_metodo == 'SendGrid' ? 'selected' : ''; ?>>SendGrid</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Búsqueda</label>
                                    <input type="text" name="busqueda" class="form-control" placeholder="Email, asunto..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                                </div>
                                <div class="col-md-3">
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

                    <!-- Lista de Logs -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                Historial de Emails (<?php echo number_format($total_logs); ?> registros)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Asunto</th>
                                            <th>Destinatario</th>
                                            <th>Estado</th>
                                            <th>Método</th>
                                            <th>Detalles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($email_logs)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                No hay registros de emails
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($email_logs as $log): ?>
                                        <tr class="<?php echo $log['enviado'] ? 'success-email' : 'error-email'; ?>">
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo formatearFechaLog($log['fecha'] ?? $log['fecha_humana'] ?? ''); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo formatearTipoEmail($log['tipo'] ?? ''); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div title="<?php echo htmlspecialchars($log['subject'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars(substr($log['subject'] ?? '', 0, 40)); ?>
                                                    <?php if (strlen($log['subject'] ?? '') > 40): ?>...<?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($log['to_email'] ?? ''); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($log['enviado'] ?? false): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Enviado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>Error
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($log['metodo'] ?? 'Desconocido'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if (isset($log['html_body']) && !empty($log['html_body'])): ?>
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#emailViewerModal"
                                                            onclick="loadEmailContent(<?php echo htmlspecialchars(json_encode($log), ENT_QUOTES, 'UTF-8'); ?>)">
                                                        <i class="fas fa-envelope-open me-1"></i>Ver mensaje
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (isset($log['error']) && !empty($log['error'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger ms-1" 
                                                            data-bs-toggle="tooltip" 
                                                            title="<?php echo htmlspecialchars($log['error']); ?>">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (isset($log['detalles']) && is_array($log['detalles']) && !empty($log['detalles'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-info ms-1" 
                                                            data-bs-toggle="popover" 
                                                            data-bs-content="<?php echo htmlspecialchars(json_encode($log['detalles'], JSON_PRETTY_PRINT)); ?>">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginación de logs">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&tipo=<?php echo $filtro_tipo; ?>&enviado=<?php echo $filtro_enviado; ?>&metodo=<?php echo $filtro_metodo; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>">
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

    <!-- Modal para visualizar email -->
    <div class="modal fade" id="emailViewerModal" tabindex="-1" aria-labelledby="emailViewerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailViewerModalLabel">
                        <i class="fas fa-envelope me-2"></i>Visualizar Email
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs mb-3" id="emailViewerTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab">
                                <i class="fas fa-eye me-1"></i>Vista Previa
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="source-tab" data-bs-toggle="tab" data-bs-target="#source" type="button" role="tab">
                                <i class="fas fa-code me-1"></i>Código HTML
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                                <i class="fas fa-info-circle me-1"></i>Información
                            </button>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content" id="emailViewerTabContent">
                        <!-- Vista Previa -->
                        <div class="tab-pane fade show active" id="preview" role="tabpanel">
                            <iframe id="emailPreviewFrame" class="email-preview-iframe" sandbox="allow-same-origin"></iframe>
                        </div>

                        <!-- Código HTML -->
                        <div class="tab-pane fade" id="source" role="tabpanel">
                            <div class="d-flex justify-content-end mb-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="copyHtmlToClipboard()">
                                    <i class="fas fa-copy me-1"></i>Copiar HTML
                                </button>
                            </div>
                            <div id="htmlSourceCode" class="html-source-code"></div>
                        </div>

                        <!-- Información -->
                        <div class="tab-pane fade" id="info" role="tabpanel">
                            <div id="emailInfoContent">
                                <!-- Se llenará dinámicamente con JS -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Inicializar popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        // Variable global para almacenar el contenido del email actual
        let currentEmailData = null;

        // Función para cargar el contenido del email en el modal
        function loadEmailContent(emailLog) {
            currentEmailData = emailLog;
            
            // Cargar vista previa HTML
            const iframe = document.getElementById('emailPreviewFrame');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(emailLog.html_body || '<p class="text-muted">No hay contenido HTML disponible</p>');
            iframeDoc.close();
            
            // Cargar código fuente HTML
            const sourceCode = document.getElementById('htmlSourceCode');
            sourceCode.textContent = emailLog.html_body || 'No hay contenido HTML disponible';
            
            // Cargar información del email
            loadEmailInfo(emailLog);
        }

        // Función para mostrar la información del email
        function loadEmailInfo(emailLog) {
            const infoContent = document.getElementById('emailInfoContent');
            
            const fecha = emailLog.fecha_humana || 'N/A';
            const tipo = emailLog.tipo || 'N/A';
            const metodo = emailLog.metodo || 'Desconocido';
            const estado = emailLog.enviado ? '<span class="badge bg-success">Enviado</span>' : '<span class="badge bg-danger">Error</span>';
            const error = emailLog.error || '';
            
            let html = `
                <div class="info-detail-item">
                    <strong><i class="fas fa-calendar me-2"></i>Fecha:</strong> ${fecha}
                </div>
                <div class="info-detail-item">
                    <strong><i class="fas fa-tag me-2"></i>Tipo:</strong> ${tipo}
                </div>
                <div class="info-detail-item">
                    <strong><i class="fas fa-paper-plane me-2"></i>Destinatario:</strong> ${emailLog.to_name || 'N/A'} &lt;${emailLog.to_email || 'N/A'}&gt;
                </div>
                <div class="info-detail-item">
                    <strong><i class="fas fa-envelope me-2"></i>Asunto:</strong> ${emailLog.subject || 'N/A'}
                </div>
                <div class="info-detail-item">
                    <strong><i class="fas fa-server me-2"></i>Método de envío:</strong> ${metodo}
                </div>
                <div class="info-detail-item">
                    <strong><i class="fas fa-check-circle me-2"></i>Estado:</strong> ${estado}
                </div>
            `;
            
            if (error) {
                html += `
                    <div class="info-detail-item bg-danger bg-opacity-10">
                        <strong><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Error:</strong>
                        <div class="mt-2 text-danger">${error}</div>
                    </div>
                `;
            }
            
            if (emailLog.detalles && Object.keys(emailLog.detalles).length > 0) {
                html += `
                    <div class="info-detail-item">
                        <strong><i class="fas fa-info-circle me-2"></i>Detalles adicionales:</strong>
                        <pre class="mt-2 mb-0" style="font-size: 12px;">${JSON.stringify(emailLog.detalles, null, 2)}</pre>
                    </div>
                `;
            }
            
            infoContent.innerHTML = html;
        }

        // Función para copiar HTML al portapapeles
        function copyHtmlToClipboard() {
            if (!currentEmailData || !currentEmailData.html_body) {
                alert('No hay contenido HTML para copiar');
                return;
            }
            
            navigator.clipboard.writeText(currentEmailData.html_body)
                .then(() => {
                    // Mostrar feedback visual
                    const btn = event.target.closest('button');
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check me-1"></i>¡Copiado!';
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-success');
                    
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-primary');
                    }, 2000);
                })
                .catch(err => {
                    alert('Error al copiar: ' + err);
                });
        }
    </script>

<?php get_footer(); ?>
</body>
</html>

