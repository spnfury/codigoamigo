<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';
include_once __DIR__ . '/../myphp/funciones_newsletter.php';
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

$collection_newsletters = getCollectionNewsletters();
$collection_queue = getCollectionNewsletterQueue();

// Si es una petición AJAX para obtener detalles, devolver JSON (debe estar ANTES del bloque POST)
if (isset($_GET['ajax'])) {
    $ajax_action = $_GET['ajax'] ?? '';
    
    if ($ajax_action === 'detalles_newsletter') {
        $newsletter_id = $_GET['newsletter_id'] ?? '';
        if (empty($newsletter_id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID de newsletter no especificado']);
            exit;
        }
        
        $emails = obtenerEmailsNewsletter($newsletter_id, 0, 0); // Sin límite para ver todos
        $conteos = contarEmailsNewsletterPorEstado($newsletter_id);
        
        // Obtener información de la newsletter
        $newsletter = $collection_newsletters->findOne(['_id' => new MongoDB\BSON\ObjectId($newsletter_id)]);
        
        if (!$newsletter) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Newsletter no encontrada']);
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'newsletter' => [
                'titulo' => $newsletter['titulo'] ?? '',
                'asunto' => $newsletter['asunto'] ?? '',
                'estado' => $newsletter['estado'] ?? ''
            ],
            'emails' => $emails,
            'conteos' => $conteos
        ]);
        exit;
    }
    
    if ($ajax_action === 'obtener_newsletter') {
        $newsletter_id = $_GET['newsletter_id'] ?? '';
        if (empty($newsletter_id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID de newsletter no especificado']);
            exit;
        }
        
        // Obtener información de la newsletter
        $newsletter = $collection_newsletters->findOne(['_id' => new MongoDB\BSON\ObjectId($newsletter_id)]);
        
        if (!$newsletter) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Newsletter no encontrada']);
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'newsletter' => [
                'titulo' => $newsletter['titulo'] ?? '',
                'asunto' => $newsletter['asunto'] ?? '',
                'contenido_html' => $newsletter['contenido_html'] ?? '',
                'contenido_texto' => $newsletter['contenido_texto'] ?? '',
                'estado' => $newsletter['estado'] ?? ''
            ]
        ]);
        exit;
    }
}

// Procesar acciones
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'crear_newsletter':
            $datos = [
                'titulo' => $_POST['titulo'] ?? '',
                'asunto' => $_POST['asunto'] ?? '',
                'contenido_html' => $_POST['contenido_html'] ?? '',
                'contenido_texto' => $_POST['contenido_texto'] ?? '',
                'segmentacion' => [],
                'creado_por' => $_SESSION["user_id"]
            ];
            
            $resultado = crearNewsletter($datos);
            
            if ($resultado['success']) {
                $_SESSION['success_message'] = "Newsletter creada correctamente. Se enviará a " . $resultado['total_destinatarios'] . " usuarios.";
            } else {
                $_SESSION['error_message'] = "Error al crear newsletter: " . $resultado['error'];
            }
            break;
            
        case 'procesar_manual':
            // Procesar cola manualmente (útil para testing)
            $resultado = procesarColaNewsletter(300);
            if ($resultado['enviados'] > 0) {
                $_SESSION['success_message'] = "Procesados " . $resultado['enviados'] . " emails. Errores: " . $resultado['errores'];
            } else {
                $_SESSION['error_message'] = "No se procesaron emails. " . ($resultado['limite_alcanzado'] ? 'Límite diario alcanzado.' : 'No hay emails pendientes.');
            }
            break;
            
        case 'eliminar_newsletter':
            $newsletter_id = $_POST['newsletter_id'] ?? '';
            if (empty($newsletter_id)) {
                $_SESSION['error_message'] = "ID de newsletter no especificado";
            } else {
                $resultado = eliminarNewsletter($newsletter_id);
                if ($resultado['success']) {
                    $_SESSION['success_message'] = "Newsletter eliminada correctamente";
                } else {
                    $_SESSION['error_message'] = "Error al eliminar newsletter: " . $resultado['error'];
                }
            }
            break;
            
        case 'cancelar_cola':
            $newsletter_id = $_POST['newsletter_id'] ?? '';
            if (empty($newsletter_id)) {
                $_SESSION['error_message'] = "ID de newsletter no especificado";
            } else {
                $resultado = cancelarColaNewsletter($newsletter_id);
                if ($resultado['success']) {
                    $_SESSION['success_message'] = "Cola cancelada correctamente. " . $resultado['cancelados'] . " emails marcados como cancelados.";
                } else {
                    $_SESSION['error_message'] = "Error al cancelar cola: " . $resultado['error'];
                }
            }
            break;
            
        case 'editar_newsletter':
            $newsletter_id = $_POST['newsletter_id'] ?? '';
            if (empty($newsletter_id)) {
                $_SESSION['error_message'] = "ID de newsletter no especificado";
            } else {
                $datos = [
                    'titulo' => $_POST['titulo'] ?? '',
                    'asunto' => $_POST['asunto'] ?? '',
                    'contenido_html' => $_POST['contenido_html'] ?? '',
                    'contenido_texto' => $_POST['contenido_texto'] ?? ''
                ];
                
                $resultado = editarNewsletter($newsletter_id, $datos);
                if ($resultado['success']) {
                    $_SESSION['success_message'] = "Newsletter editada correctamente";
                } else {
                    $_SESSION['error_message'] = "Error al editar newsletter: " . $resultado['error'];
                }
            }
            break;
            
        case 'reactivar_newsletter':
            $newsletter_id = $_POST['newsletter_id'] ?? '';
            $regenerar_cola = isset($_POST['regenerar_cola']) && $_POST['regenerar_cola'] === '1';
            
            if (empty($newsletter_id)) {
                $_SESSION['error_message'] = "ID de newsletter no especificado";
            } else {
                $resultado = reactivarNewsletter($newsletter_id, $regenerar_cola);
                if ($resultado['success']) {
                    $mensaje = "Newsletter reactivada correctamente";
                    if ($regenerar_cola && $resultado['total_destinatarios'] > 0) {
                        $mensaje .= ". Se han añadido " . $resultado['total_destinatarios'] . " nuevos emails a la cola.";
                    }
                    $_SESSION['success_message'] = $mensaje;
                } else {
                    $_SESSION['error_message'] = "Error al reactivar newsletter: " . $resultado['error'];
                }
            }
            break;
    }
    
    // Redirigir para evitar reenvío de formulario
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Obtener listado de newsletters
$filtro_estado = $_GET['estado'] ?? '';
$filtros = [];
if (!empty($filtro_estado)) {
    $filtros['estado'] = $filtro_estado;
}

$newsletters = [];
if ($collection_newsletters) {
    try {
        $newsletters_cursor = $collection_newsletters->find($filtros, [
            'sort' => ['fecha_creacion' => -1],
            'limit' => 50
        ]);
        $newsletters = iterator_to_array($newsletters_cursor);
    } catch (Throwable $e) {
        log_error("Error al obtener newsletters: " . $e->getMessage());
    }
}

// Obtener estadísticas generales
$emails_enviados_hoy = obtenerEmailsEnviadosHoy();
$emails_disponibles = max(0, 300 - $emails_enviados_hoy);
$pendientes_total = 0;
if ($collection_queue) {
    try {
        $pendientes_total = $collection_queue->countDocuments(['estado' => 'pendiente']);
    } catch (Throwable $e) {
        log_error("Error al contar pendientes: " . $e->getMessage());
    }
}

$page_title = "Gestión de Newsletters";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            padding: 10px 15px;
            margin: 2px 0;
            border-radius: 5px;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white !important;
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
        }
        .table-responsive .table thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 10;
        }
        
        #tablaEmails tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge-estado {
            padding: 5px 10px;
            border-radius: 5px;
        }
        .estado-programada { background: #ffc107; color: #000; }
        .estado-enviando { background: #17a2b8; color: #fff; }
        .estado-completada { background: #28a745; color: #fff; }
        .estado-cancelada { background: #dc3545; color: #fff; }
        .estado-borrador { background: #6c757d; color: #fff; }
        
        /* Asegurar que los diálogos de CKEditor se muestren correctamente en modales */
        .cke_dialog {
            z-index: 10060 !important;
        }
        .modal.show .cke_dialog {
            z-index: 10060 !important;
        }
        .cke_dialog_background_cover {
            z-index: 10055 !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php echo get_admin_sidebar_menu('admin_newsletters.php'); ?>
            
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-envelope-open-text me-2"></i>Gestión de Newsletters</h1>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h5><i class="fas fa-paper-plane me-2"></i>Emails Disponibles Hoy</h5>
                            <h2><?php echo $emails_disponibles; ?> / 300</h2>
                            <small>Enviados hoy: <?php echo $emails_enviados_hoy; ?></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h5><i class="fas fa-clock me-2"></i>Pendientes en Cola</h5>
                            <h2><?php echo $pendientes_total; ?></h2>
                            <small>Esperando envío</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h5><i class="fas fa-list me-2"></i>Total Newsletters</h5>
                            <h2><?php echo count($newsletters); ?></h2>
                            <small>En el sistema</small>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearNewsletter">
                        <i class="fas fa-plus me-2"></i>Crear Nueva Newsletter
                    </button>
                    <form method="POST" action="" style="display: inline-block;">
                        <input type="hidden" name="action" value="procesar_manual">
                        <button type="submit" class="btn btn-success" title="Procesar hasta 300 emails pendientes ahora">
                            <i class="fas fa-paper-plane me-2"></i>Procesar Cola Ahora
                        </button>
                    </form>
                    <a href="?estado=programada" class="btn btn-warning">
                        <i class="fas fa-clock me-2"></i>Programadas
                    </a>
                    <a href="?estado=enviando" class="btn btn-info">
                        <i class="fas fa-spinner me-2"></i>Enviando
                    </a>
                    <a href="?estado=completada" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Completadas
                    </a>
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-list me-2"></i>Todas
                    </a>
                </div>
                
                <!-- Listado de newsletters -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Listado de Newsletters</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($newsletters)): ?>
                            <p class="text-muted">No hay newsletters creadas aún.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Asunto</th>
                                            <th>Estado</th>
                                            <th>Destinatarios</th>
                                            <th>Enviados</th>
                                            <th>Progreso</th>
                                            <th>Fecha Creación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($newsletters as $newsletter): 
                                            $stats = obtenerEstadisticasCompletasNewsletter((string)$newsletter['_id']);
                                        ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($newsletter['titulo'] ?? 'Sin título'); ?></strong></td>
                                                <td><?php echo htmlspecialchars($newsletter['asunto'] ?? ''); ?></td>
                                                <td>
                                                    <span class="badge badge-estado estado-<?php echo $newsletter['estado'] ?? 'borrador'; ?>">
                                                        <?php echo ucfirst($newsletter['estado'] ?? 'borrador'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $newsletter['total_destinatarios'] ?? 0; ?></td>
                                                <td>
                                                    <?php echo $stats['total_enviados']; ?>
                                                    <?php if (isset($stats['total_abiertos'])): ?>
                                                        <br><small class="text-muted">Abiertos: <?php echo $stats['total_abiertos']; ?> (<?php echo $stats['tasa_apertura'] ?? 0; ?>%)</small>
                                                    <?php endif; ?>
                                                    <?php if (isset($stats['total_clics'])): ?>
                                                        <br><small class="text-info">Clics: <?php echo $stats['total_clics']; ?> (<?php echo $stats['tasa_clics'] ?? 0; ?>%)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $stats['porcentaje_completado']; ?>%">
                                                            <?php echo $stats['porcentaje_completado']; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (isset($newsletter['fecha_creacion'])) {
                                                        $fecha = $newsletter['fecha_creacion']->toDateTime();
                                                        echo $fecha->format('d/m/Y H:i');
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="verDetalles('<?php echo (string)$newsletter['_id']; ?>')" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($newsletter['estado'] !== 'enviando'): ?>
                                                    <button class="btn btn-sm btn-primary" onclick="editarNewsletter('<?php echo (string)$newsletter['_id']; ?>')" title="Editar newsletter">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if (in_array($newsletter['estado'] ?? '', ['cancelada', 'completada'])): ?>
                                                    <button class="btn btn-sm btn-success" onclick="reactivarNewsletter('<?php echo (string)$newsletter['_id']; ?>', '<?php echo htmlspecialchars($newsletter['titulo'] ?? 'Sin título', ENT_QUOTES); ?>')" title="Reactivar newsletter">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if ($newsletter['estado'] === 'enviando' || $newsletter['estado'] === 'programada'): ?>
                                                    <button class="btn btn-sm btn-warning" onclick="cancelarCola('<?php echo (string)$newsletter['_id']; ?>', '<?php echo htmlspecialchars($newsletter['titulo'] ?? 'Sin título', ENT_QUOTES); ?>')" title="Cancelar cola de envío">
                                                        <i class="fas fa-stop"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if ($newsletter['estado'] !== 'enviando'): ?>
                                                    <button class="btn btn-sm btn-danger" onclick="eliminarNewsletter('<?php echo (string)$newsletter['_id']; ?>', '<?php echo htmlspecialchars($newsletter['titulo'] ?? 'Sin título', ENT_QUOTES); ?>')" title="Eliminar newsletter">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </td>
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
    </div>
    
    <!-- Modal Crear Newsletter -->
    <div class="modal fade" id="modalCrearNewsletter" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Crear Nueva Newsletter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear_newsletter">
                        
                        <div class="mb-3">
                            <label class="form-label">Título de la Newsletter</label>
                            <input type="text" class="form-control" name="titulo" required placeholder="Ej: Nueva sección de chollos">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Asunto del Email</label>
                            <input type="text" class="form-control" name="asunto" required placeholder="Ej: ¡Descubre nuestra nueva sección de chollos!">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contenido HTML</label>
                            <textarea class="form-control" name="contenido_html" id="contenido_html" rows="10" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contenido Texto (opcional, se genera automáticamente si está vacío)</label>
                            <textarea class="form-control" name="contenido_texto" rows="5" placeholder="Versión en texto plano del email"></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nota:</strong> La newsletter se enviará automáticamente a todos los usuarios activos. 
                            El sistema respeta el límite de 300 emails/día de Brevo y distribuirá los envíos en varios días si es necesario.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Newsletter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Newsletter -->
    <div class="modal fade" id="modalEditarNewsletter" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Newsletter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="formEditarNewsletter" onsubmit="return guardarEdicionNewsletter(event)">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="editar_newsletter">
                        <input type="hidden" name="newsletter_id" id="editar_newsletter_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Título de la Newsletter</label>
                            <input type="text" class="form-control" name="titulo" id="editar_titulo" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Asunto del Email</label>
                            <input type="text" class="form-control" name="asunto" id="editar_asunto" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contenido HTML</label>
                            <textarea class="form-control" name="contenido_html" id="editar_contenido_html" rows="10" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contenido Texto (opcional, se genera automáticamente si está vacío)</label>
                            <textarea class="form-control" name="contenido_texto" id="editar_contenido_texto" rows="5"></textarea>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Nota:</strong> Los cambios solo afectarán a los emails que aún no se han enviado. 
                            Los emails ya enviados mantendrán el contenido original.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Detalles Newsletter -->
    <div class="modal fade" id="modalDetallesNewsletter" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Detalles de Newsletter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detallesLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3">Cargando detalles...</p>
                    </div>
                    
                    <div id="detallesContent" style="display: none;">
                        <div class="mb-4">
                            <h6><i class="fas fa-info-circle me-2"></i>Información General</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Título:</strong> <span id="detallesTitulo"></span></p>
                                    <p><strong>Asunto:</strong> <span id="detallesAsunto"></span></p>
                                    <p><strong>Estado:</strong> <span id="detallesEstado"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Emails:</strong> <span id="detallesTotal"></span></p>
                                    <p><strong>Enviados:</strong> <span class="badge bg-success" id="detallesEnviados">0</span></p>
                                    <p><strong>Pendientes:</strong> <span class="badge bg-warning" id="detallesPendientes">0</span></p>
                                    <p><strong>Errores:</strong> <span class="badge bg-danger" id="detallesErrores">0</span></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary active" onclick="filtrarEmails('todos')">Todos</button>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="filtrarEmails('enviado')">Enviados</button>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="filtrarEmails('pendiente')">Pendientes</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="filtrarEmails('error')">Errores</button>
                            </div>
                            <input type="text" id="buscarEmail" class="form-control form-control-sm d-inline-block w-auto ms-2" placeholder="Buscar por email..." onkeyup="buscarEmail()">
                        </div>
                        
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Email</th>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                        <th>Intentos</th>
                                        <th>Fecha Envío</th>
                                        <th>Error</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaEmails">
                                    <!-- Los emails se cargarán aquí -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div id="detallesError" style="display: none;" class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="detallesErrorMessage"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar CKEditor para crear
        CKEDITOR.replace('contenido_html', {
            height: 300,
            toolbar: [
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Blockquote'] },
                { name: 'links', items: ['Link', 'Unlink'] },
                { name: 'insert', items: ['Image'] },
                { name: 'styles', items: ['Format'] }
            ],
            linkShowAdvancedTab: false,
            linkShowTargetTab: true
        });
        
        // Variable para almacenar la instancia del editor de edición
        let editorEditar = null;
        
        // Función para guardar el contenido del editor antes de enviar el formulario
        function guardarEdicionNewsletter(event) {
            if (editorEditar && editorEditar.status === 'ready') {
                // Actualizar el textarea con el contenido del editor
                editorEditar.updateElement();
            }
            return true; // Continuar con el envío del formulario
        }
        
        let emailsData = [];
        let filtroActual = 'todos';
        
        function verDetalles(newsletterId) {
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalDetallesNewsletter'));
            modal.show();
            
            // Resetear estado
            document.getElementById('detallesLoading').style.display = 'block';
            document.getElementById('detallesContent').style.display = 'none';
            document.getElementById('detallesError').style.display = 'none';
            document.getElementById('tablaEmails').innerHTML = '';
            
            // Cargar datos vía AJAX
            fetch('?ajax=detalles_newsletter&newsletter_id=' + newsletterId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('detallesLoading').style.display = 'none';
                    
                    if (data.success) {
                        emailsData = data.emails;
                        
                        // Mostrar información general
                        document.getElementById('detallesTitulo').textContent = data.newsletter.titulo;
                        document.getElementById('detallesAsunto').textContent = data.newsletter.asunto;
                        
                        const estadoBadge = document.getElementById('detallesEstado');
                        estadoBadge.textContent = data.newsletter.estado;
                        estadoBadge.className = 'badge estado-' + data.newsletter.estado;
                        
                        document.getElementById('detallesTotal').textContent = data.conteos.total;
                        document.getElementById('detallesEnviados').textContent = data.conteos.enviado;
                        document.getElementById('detallesPendientes').textContent = data.conteos.pendiente;
                        document.getElementById('detallesErrores').textContent = data.conteos.error;
                        
                        // Mostrar tabla
                        mostrarEmails(emailsData);
                        document.getElementById('detallesContent').style.display = 'block';
                    } else {
                        document.getElementById('detallesErrorMessage').textContent = data.error || 'Error al cargar los detalles';
                        document.getElementById('detallesError').style.display = 'block';
                    }
                })
                .catch(error => {
                    document.getElementById('detallesLoading').style.display = 'none';
                    document.getElementById('detallesErrorMessage').textContent = 'Error al cargar los detalles: ' + error.message;
                    document.getElementById('detallesError').style.display = 'block';
                });
        }
        
        function mostrarEmails(emails) {
            const tbody = document.getElementById('tablaEmails');
            tbody.innerHTML = '';
            
            if (emails.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay emails para mostrar</td></tr>';
                return;
            }
            
            emails.forEach(email => {
                const tr = document.createElement('tr');
                tr.setAttribute('data-estado', email.estado);
                tr.setAttribute('data-email', email.usuario_email.toLowerCase());
                
                let estadoBadge = '';
                if (email.estado === 'enviado') {
                    estadoBadge = '<span class="badge bg-success"><i class="fas fa-check"></i> Enviado</span>';
                } else if (email.estado === 'pendiente') {
                    estadoBadge = '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pendiente</span>';
                } else if (email.estado === 'error') {
                    estadoBadge = '<span class="badge bg-danger"><i class="fas fa-times"></i> Error</span>';
                }
                
                tr.innerHTML = `
                    <td>${email.usuario_email}</td>
                    <td>${email.usuario_nombre || '-'}</td>
                    <td>${estadoBadge}</td>
                    <td>${email.intentos}</td>
                    <td>${email.fecha_envio || '-'}</td>
                    <td><small class="text-danger">${email.error_message || '-'}</small></td>
                `;
                
                tbody.appendChild(tr);
            });
        }
        
        function filtrarEmails(estado) {
            filtroActual = estado;
            
            // Actualizar botones
            document.querySelectorAll('.btn-group button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Filtrar emails
            let emailsFiltrados = emailsData;
            if (estado !== 'todos') {
                emailsFiltrados = emailsData.filter(e => e.estado === estado);
            }
            
            // Aplicar búsqueda si hay texto
            const busqueda = document.getElementById('buscarEmail').value.toLowerCase();
            if (busqueda) {
                emailsFiltrados = emailsFiltrados.filter(e => 
                    e.usuario_email.toLowerCase().includes(busqueda) ||
                    (e.usuario_nombre && e.usuario_nombre.toLowerCase().includes(busqueda))
                );
            }
            
            mostrarEmails(emailsFiltrados);
        }
        
        function buscarEmail() {
            const busqueda = document.getElementById('buscarEmail').value.toLowerCase();
            let emailsFiltrados = emailsData;
            
            // Aplicar filtro de estado
            if (filtroActual !== 'todos') {
                emailsFiltrados = emailsData.filter(e => e.estado === filtroActual);
            }
            
            // Aplicar búsqueda
            if (busqueda) {
                emailsFiltrados = emailsFiltrados.filter(e => 
                    e.usuario_email.toLowerCase().includes(busqueda) ||
                    (e.usuario_nombre && e.usuario_nombre.toLowerCase().includes(busqueda))
                );
            }
            
            mostrarEmails(emailsFiltrados);
        }
        
        function cancelarCola(newsletterId, titulo) {
            if (!confirm('¿Estás seguro de que deseas cancelar la cola de envío de la newsletter "' + titulo + '"?\n\nEsta acción:\n- Cancelará todos los emails pendientes\n- Detendrá el envío automático\n- Los emails ya enviados NO se cancelarán\n\nEsta acción NO se puede deshacer.')) {
                return;
            }
            
            // Crear formulario y enviar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'cancelar_cola';
            form.appendChild(actionInput);
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'newsletter_id';
            idInput.value = newsletterId;
            form.appendChild(idInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function editarNewsletter(newsletterId) {
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalEditarNewsletter'));
            modal.show();
            
            // Inicializar CKEditor cuando el modal esté visible
            const modalElement = document.getElementById('modalEditarNewsletter');
            const initEditor = () => {
                // Destruir editor anterior si existe
                if (editorEditar) {
                    editorEditar.destroy();
                    editorEditar = null;
                }
                
                // Esperar un momento para que el modal esté completamente visible
                setTimeout(() => {
                    if (typeof CKEDITOR !== 'undefined') {
                        editorEditar = CKEDITOR.replace('editar_contenido_html', {
                            height: 300,
                            toolbar: [
                                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
                                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Blockquote'] },
                                { name: 'links', items: ['Link', 'Unlink'] },
                                { name: 'insert', items: ['Image'] },
                                { name: 'styles', items: ['Format'] }
                            ],
                            linkShowAdvancedTab: false,
                            linkShowTargetTab: true,
                            linkDefaultProtocol: 'https://',
                            removePlugins: 'save'
                        });
                        
                        // Asegurar que los diálogos se muestren correctamente con z-index alto
                        editorEditar.on('dialogShow', function(e) {
                            setTimeout(function() {
                                const dialog = e.data;
                                if (dialog && dialog.getElement()) {
                                    const dialogElement = dialog.getElement().$;
                                    if (dialogElement) {
                                        dialogElement.style.zIndex = '10060';
                                    }
                                }
                            }, 50);
                        });
                    }
                }, 100);
            };
            
            // Inicializar editor cuando el modal se muestre
            modalElement.addEventListener('shown.bs.modal', initEditor, { once: true });
            
            // Cargar datos de la newsletter
            fetch('?ajax=obtener_newsletter&newsletter_id=' + newsletterId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editar_newsletter_id').value = newsletterId;
                        document.getElementById('editar_titulo').value = data.newsletter.titulo;
                        document.getElementById('editar_asunto').value = data.newsletter.asunto;
                        
                        // Esperar a que el editor esté listo antes de cargar datos
                        const loadData = () => {
                            if (editorEditar && editorEditar.status === 'ready') {
                                editorEditar.setData(data.newsletter.contenido_html);
                            } else if (editorEditar) {
                                editorEditar.on('instanceReady', function() {
                                    editorEditar.setData(data.newsletter.contenido_html);
                                });
                            } else {
                                // Fallback: cargar en textarea si el editor no está disponible
                                document.getElementById('editar_contenido_html').value = data.newsletter.contenido_html;
                            }
                        };
                        
                        // Intentar cargar datos después de un breve delay
                        setTimeout(loadData, 300);
                        
                        document.getElementById('editar_contenido_texto').value = data.newsletter.contenido_texto || '';
                    } else {
                        alert('Error al cargar la newsletter: ' + (data.error || 'Error desconocido'));
                        modal.hide();
                    }
                })
                .catch(error => {
                    alert('Error al cargar la newsletter: ' + error.message);
                    modal.hide();
                });
        }
        
        function reactivarNewsletter(newsletterId, titulo) {
            const regenerarCola = confirm('¿Deseas regenerar la cola de envíos?\n\n' +
                'Sí: Se eliminarán los emails pendientes/cancelados y se crearán nuevos para usuarios que aún no recibieron el email.\n\n' +
                'No: Solo se reactivará la newsletter con los emails que ya están en la cola.');
            
            // Crear formulario y enviar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'reactivar_newsletter';
            form.appendChild(actionInput);
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'newsletter_id';
            idInput.value = newsletterId;
            form.appendChild(idInput);
            
            if (regenerarCola) {
                const regenerarInput = document.createElement('input');
                regenerarInput.type = 'hidden';
                regenerarInput.name = 'regenerar_cola';
                regenerarInput.value = '1';
                form.appendChild(regenerarInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function eliminarNewsletter(newsletterId, titulo) {
            if (!confirm('¿Estás seguro de que deseas eliminar la newsletter "' + titulo + '"?\n\nEsta acción eliminará:\n- La newsletter\n- Todos los emails pendientes en la cola\n- Las estadísticas asociadas\n\nEsta acción NO se puede deshacer.')) {
                return;
            }
            
            // Crear formulario y enviar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'eliminar_newsletter';
            form.appendChild(actionInput);
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'newsletter_id';
            idInput.value = newsletterId;
            form.appendChild(idInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>

