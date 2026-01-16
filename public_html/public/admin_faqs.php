<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';
include_once __DIR__ . '/../myphp/funciones_faq.php';
include_once __DIR__ . '/../myphp/funciones_marca.php';
include_once __DIR__ . '/admin_sidebar_menu.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    exit;
}

// Configuración básica
ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);

// Procesar acciones
$mensaje = '';
$tipo_mensaje = '';

if ($_POST) {
    if (isset($_POST['accion'])) {
        $marca_clave = $_POST['marca_clave'] ?? '';
        
        try {
            switch ($_POST['accion']) {
                case 'crear_faq':
                    if (!empty($marca_clave) && !empty($_POST['titulo']) && !empty($_POST['respuesta'])) {
                        $orden = intval($_POST['orden'] ?? 0);
                        $id = crearFAQ($marca_clave, $_POST['titulo'], $_POST['respuesta'], $orden);
                        if ($id) {
                            $_SESSION['success_message'] = 'FAQ creada exitosamente';
                        } else {
                            $_SESSION['error_message'] = 'Error al crear la FAQ';
                        }
                    }
                    break;
                    
                case 'actualizar_faq':
                    $faq_id = $_POST['faq_id'] ?? '';
                    if (!empty($faq_id) && !empty($_POST['titulo']) && !empty($_POST['respuesta'])) {
                        $orden = intval($_POST['orden'] ?? 0);
                        $activa = isset($_POST['activa']) ? true : false;
                        if (actualizarFAQ($faq_id, $_POST['titulo'], $_POST['respuesta'], $orden, $activa)) {
                            $_SESSION['success_message'] = 'FAQ actualizada exitosamente';
                        } else {
                            $_SESSION['error_message'] = 'Error al actualizar la FAQ';
                        }
                    }
                    break;
                    
                case 'eliminar_faq':
                    $faq_id = $_POST['faq_id'] ?? '';
                    if (!empty($faq_id)) {
                        if (eliminarFAQ($faq_id)) {
                            $_SESSION['success_message'] = 'FAQ eliminada exitosamente';
                        } else {
                            $_SESSION['error_message'] = 'Error al eliminar la FAQ';
                        }
                    }
                    break;
                    
                case 'toggle_activa':
                    $faq_id = $_POST['faq_id'] ?? '';
                    if (!empty($faq_id)) {
                        if (toggleFAQActiva($faq_id)) {
                            $_SESSION['success_message'] = 'Estado de FAQ actualizado';
                        } else {
                            $_SESSION['error_message'] = 'Error al actualizar el estado';
                        }
                    }
                    break;
                    
                case 'importar_bulk':
                    if (!empty($marca_clave) && !empty($_POST['texto_faqs'])) {
                        $faqs_creadas = importarFAQsBulk($marca_clave, $_POST['texto_faqs']);
                        if ($faqs_creadas) {
                            $_SESSION['success_message'] = count($faqs_creadas) . ' FAQs importadas exitosamente';
                        } else {
                            $_SESSION['error_message'] = 'Error al importar las FAQs';
                        }
                    }
                    break;
                    
                case 'generar_ia':
                    if (!empty($marca_clave)) {
                        $marca_obj = getObjectMarca('nombre_clave', $marca_clave);
                        $api_provider = $_POST['api_provider'] ?? 'perplexity';
                        
                        if ($marca_obj) {
                            $faqs_generadas = generarFAQsConIA($marca_clave, $marca_obj['nombre'], $api_provider);
                            if ($faqs_generadas) {
                                $_SESSION['success_message'] = count($faqs_generadas) . ' FAQs generadas con IA exitosamente';
                            } else {
                                $_SESSION['error_message'] = 'Error al generar FAQs con IA. Revisa los logs para más detalles.';
                            }
                        }
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        }
        
        header("Location: admin_faqs.php?marca=" . urlencode($marca_clave));
        exit;
    }
}

// Obtener parámetros
$marca_clave = $_GET['marca'] ?? '';
$faq_id = $_GET['editar'] ?? '';

// Obtener todas las marcas para el selector
$todas_marcas = get_all_marcas_panel_control(3000);

// Obtener FAQs de la marca seleccionada
$faqs = [];
$estadisticas = [];
if (!empty($marca_clave)) {
    $faqs = getFAQsByMarca($marca_clave, false); // Todas, incluyendo inactivas
    $estadisticas = getEstadisticasFAQs($marca_clave);
}

// Obtener FAQ específica para editar
$faq_editar = null;
if (!empty($faq_id)) {
    $faq_editar = getFAQByID($faq_id);
}

$title = "Administración de FAQs - CodigoAmigo";
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
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .faq-item {
            border: 1px solid #dee2e6;
            border-radius: 12px;
            margin-bottom: 15px;
            padding: 20px;
            background: white;
            transition: all 0.3s ease;
        }
        .faq-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }
        .faq-item.inactiva {
            background: #f8f9fa;
            opacity: 0.7;
        }
        .faq-titulo {
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .faq-respuesta {
            color: #636e72;
            font-size: 0.95rem;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .faq-meta {
            font-size: 0.85rem;
            color: #b2bec3;
            display: flex;
            gap: 15px;
        }
        .stats-card {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: none;
            box-shadow: 0 10px 20px rgba(108, 92, 231, 0.2);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 5px;
        }
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .ai-generator {
            background: linear-gradient(135deg, #e17055 0%, #fab1a0 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: none;
            box-shadow: 0 10px 20px rgba(225, 112, 85, 0.2);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 20px 25px;
        }
        .card-header h5 {
            margin-bottom: 0;
            font-weight: 600;
            color: #2d3436;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #dfe6e9;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
            border-color: #6c5ce7;
        }
        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }
        .btn-primary:hover {
            background-color: #5849c4;
            border-color: #5849c4;
            transform: translateY(-1px);
        }
        .import-textarea {
            min-height: 150px;
            font-family: inherit;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo get_admin_sidebar_menu('admin_faqs.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-admin">
                    <div class="container-fluid">
                        <h5 class="mb-0">Administración de FAQs</h5>
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
                            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Cabecera y Selector -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h2 class="fw-bold mb-1">Preguntas Frecuentes de Marcas</h2>
                            <p class="text-muted">Gestiona las respuestas automáticas para mejorar el SEO y UX.</p>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-2">
                                <form method="GET" id="formMarca">
                                    <select name="marca" class="form-select border-0 bg-light" onchange="this.form.submit()">
                                        <option value="">Selecciona una marca...</option>
                                        <?php foreach ($todas_marcas as $m): ?>
                                            <option value="<?php echo htmlspecialchars($m['nombre_clave']); ?>" 
                                                    <?php echo $marca_clave === $m['nombre_clave'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($m['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($marca_clave)): ?>
                        <?php $marca_seleccionada = getObjectMarca('nombre_clave', $marca_clave); ?>
                        
                        <div class="row">
                            <!-- Columna Izquierda: Estadísticas e IA -->
                            <div class="col-lg-4">
                                <div class="stats-card">
                                    <h5 class="mb-4"><i class="fas fa-chart-line me-2"></i>Estadísticas</h5>
                                    <div class="row g-3">
                                        <div class="col-6 text-center">
                                            <div class="stats-number"><?php echo $estadisticas['total']; ?></div>
                                            <div class="stats-label">Total</div>
                                        </div>
                                        <div class="col-6 text-center">
                                            <div class="stats-number"><?php echo $estadisticas['activas']; ?></div>
                                            <div class="stats-label">Activas</div>
                                        </div>
                                        <div class="col-6 text-center mt-3">
                                            <div class="stats-number"><?php echo $estadisticas['generadas_ia']; ?></div>
                                            <div class="stats-label">IA</div>
                                        </div>
                                        <div class="col-6 text-center mt-3">
                                            <div class="stats-number"><?php echo $estadisticas['manuales']; ?></div>
                                            <div class="stats-label">Manual</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="ai-generator">
                                    <h5><i class="fas fa-robot me-2"></i>Generador IA</h5>
                                    <p class="small opacity-75 mb-4">Genera 8-10 preguntas automáticamente basadas en la marca.</p>
                                    <form method="POST">
                                        <input type="hidden" name="marca_clave" value="<?php echo htmlspecialchars($marca_clave); ?>">
                                        <input type="hidden" name="accion" value="generar_ia">
                                        <div class="mb-3">
                                            <select name="api_provider" class="form-select bg-light border-0">
                                                <option value="perplexity">Perplexity AI (Auto-fallback)</option>
                                                <option value="groq">Groq AI (Rápido)</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-light w-100 fw-bold text-danger" onclick="return confirm('¿Generar FAQs con IA? Esto añadirá nuevas preguntas automáticamente.')">
                                            <i class="fas fa-magic me-2"></i>Generar ahora
                                        </button>
                                    </form>
                                </div>

                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5><i class="fas fa-upload me-2"></i>Importación Masiva</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="marca_clave" value="<?php echo htmlspecialchars($marca_clave); ?>">
                                            <input type="hidden" name="accion" value="importar_bulk">
                                            <textarea name="texto_faqs" class="form-control import-textarea bg-light mb-3" placeholder="¿Pregunta?
Respuesta..."></textarea>
                                            <button type="submit" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-file-import me-2"></i>Importar Texto
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna Derecha: CRUD y Lista -->
                            <div class="col-lg-8">
                                <!-- Formulario Crear/Editar -->
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5>
                                            <i class="fas fa-<?php echo $faq_editar ? 'edit' : 'plus'; ?> me-2"></i> 
                                            <?php echo $faq_editar ? 'Editar FAQ' : 'Añadir Nueva FAQ'; ?>
                                        </h5>
                                        <?php if ($faq_editar): ?>
                                            <a href="?marca=<?php echo urlencode($marca_clave); ?>" class="btn btn-sm btn-light">
                                                <i class="fas fa-times me-1"></i>Cancelar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="marca_clave" value="<?php echo htmlspecialchars($marca_clave); ?>">
                                            <input type="hidden" name="accion" value="<?php echo $faq_editar ? 'actualizar_faq' : 'crear_faq'; ?>">
                                            <?php if ($faq_editar): ?>
                                                <input type="hidden" name="faq_id" value="<?php echo $faq_editar['_id']; ?>">
                                            <?php endif; ?>
                                            
                                            <div class="row g-3">
                                                <div class="col-md-9">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-uppercase text-muted">Pregunta</label>
                                                        <input type="text" class="form-control" name="titulo" value="<?php echo $faq_editar ? htmlspecialchars($faq_editar['titulo']) : ''; ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-uppercase text-muted">Orden</label>
                                                        <input type="number" class="form-control" name="orden" value="<?php echo $faq_editar ? $faq_editar['orden'] : count($faqs); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-uppercase text-muted">Respuesta (HTML permitido)</label>
                                                        <textarea class="form-control" name="respuesta" rows="4" required><?php echo $faq_editar ? htmlspecialchars($faq_editar['respuesta']) : ''; ?></textarea>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                    <?php if ($faq_editar): ?>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="activa" id="faqActiva" <?php echo $faq_editar['activa'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="faqActiva">Activa</label>
                                                        </div>
                                                    <?php else: ?>
                                                        <div></div>
                                                    <?php endif; ?>
                                                    
                                                    <button type="submit" class="btn btn-primary px-5">
                                                        <i class="fas fa-save me-2"></i><?php echo $faq_editar ? 'Actualizar' : 'Guardar FAQ'; ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Lista de FAQs -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-list me-2"></i>FAQs Existentes (<?php echo count($faqs); ?>)</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($faqs)): ?>
                                            <div class="text-center py-5">
                                                <i class="fas fa-question-circle fa-3x text-light mb-3"></i>
                                                <p class="text-muted">No hay preguntas registradas para esta marca.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($faqs as $faq): ?>
                                                <div class="faq-item <?php echo !$faq['activa'] ? 'inactiva' : ''; ?>">
                                                    <div class="d-flex justify-content-between align-start">
                                                        <div class="faq-titulo">
                                                            <?php echo htmlspecialchars($faq['titulo']); ?>
                                                            <?php if (isset($faq['generada_ia']) && $faq['generada_ia']): ?>
                                                                <span class="badge bg-info-subtle text-info border border-info-subtle ms-2">IA</span>
                                                            <?php endif; ?>
                                                            <?php if (!$faq['activa']): ?>
                                                                <span class="badge bg-secondary-subtle text-secondary ms-2">Inactiva</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="dropdown">
                                                            <button class="btn btn-link link-dark p-0" data-bs-toggle="dropdown">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                                <li>
                                                                    <a class="dropdown-item" href="?marca=<?php echo urlencode($marca_clave); ?>&editar=<?php echo $faq['_id']; ?>">
                                                                        <i class="fas fa-edit me-2 text-primary"></i>Editar
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <form method="POST">
                                                                        <input type="hidden" name="marca_clave" value="<?php echo htmlspecialchars($marca_clave); ?>">
                                                                        <input type="hidden" name="accion" value="toggle_activa">
                                                                        <input type="hidden" name="faq_id" value="<?php echo $faq['_id']; ?>">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="fas fa-<?php echo $faq['activa'] ? 'eye-slash' : 'eye'; ?> me-2 text-warning"></i>
                                                                            <?php echo $faq['activa'] ? 'Desactivar' : 'Activar'; ?>
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <form method="POST" onsubmit="return confirm('¿Eliminar esta FAQ definitivamente?')">
                                                                        <input type="hidden" name="marca_clave" value="<?php echo htmlspecialchars($marca_clave); ?>">
                                                                        <input type="hidden" name="accion" value="eliminar_faq">
                                                                        <input type="hidden" name="faq_id" value="<?php echo $faq['_id']; ?>">
                                                                        <button type="submit" class="dropdown-item text-danger">
                                                                            <i class="fas fa-trash me-2"></i>Eliminar
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    <div class="faq-respuesta">
                                                        <?php echo nl2br(htmlspecialchars(substr($faq['respuesta'], 0, 160))); ?>
                                                        <?php if (strlen($faq['respuesta']) > 160): ?>...<?php endif; ?>
                                                    </div>
                                                    <div class="faq-meta">
                                                        <span><i class="fas fa-sort me-1"></i>Posición: <?php echo $faq['orden']; ?></span>
                                                        <span><i class="fas fa-calendar-alt me-1"></i><?php echo $faq['fecha_creacion']->toDateTime()->format('d/m/Y'); ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
