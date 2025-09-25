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

$collection_configuracion = getCollectionConfiguracion();

// Configuraciones por defecto
$configuraciones_default = [
    'sistema' => [
        'nombre_sitio' => 'CodigoAmigo',
        'descripcion_sitio' => 'La mejor plataforma para compartir códigos de descuento',
        'url_sitio' => 'https://www.codigoamigo.com',
        'email_contacto' => 'contacto@codigoamigo.com',
        'telefono_contacto' => '',
        'direccion_contacto' => '',
        'idioma_default' => 'es',
        'zona_horaria' => 'Europe/Madrid',
        'moneda' => 'EUR',
        'simbolo_moneda' => '€'
    ],
    'usuarios' => [
        'registro_abierto' => true,
        'verificacion_email' => false,
        'saldo_inicial' => 0,
        'zumbidos_iniciales' => 1,
        'max_codigos_por_usuario' => 50,
        'max_codigos_por_marca' => 1
    ],
    'codigos' => [
        'moderacion_automatica' => false,
        'destacado_costo' => 5.00,
        'destacado_duracion_dias' => 7,
        'codigo_min_length' => 3,
        'codigo_max_length' => 50,
        'descripcion_max_length' => 500
    ],
    'marcas' => [
        'auto_crear_marcas' => true,
        'categoria_default' => 'General',
        'imagen_default' => '/img/no_image.png',
        'max_marcas_por_usuario' => 10
    ],
    'pagos' => [
        'stripe_public_key' => '',
        'stripe_secret_key' => '',
        'stripe_webhook_secret' => '',
        'paypal_client_id' => '',
        'paypal_client_secret' => '',
        'paypal_mode' => 'sandbox'
    ],
    'email' => [
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'email_from' => 'noreply@codigoamigo.com',
        'email_from_name' => 'CodigoAmigo'
    ],
    'seo' => [
        'meta_title' => 'CodigoAmigo - Códigos de Descuento Gratis',
        'meta_description' => 'Encuentra los mejores códigos de descuento y ofertas exclusivas. Ahorra dinero en tus compras online.',
        'meta_keywords' => 'códigos descuento, cupones, ofertas, ahorro',
        'google_analytics' => '',
        'google_tag_manager' => '',
        'facebook_pixel' => ''
    ],
    'seguridad' => [
        'max_intentos_login' => 5,
        'tiempo_bloqueo_minutos' => 15,
        'requerir_https' => true,
        'session_timeout_minutos' => 60,
        'log_actividad' => true
    ],
    'notificaciones' => [
        'email_nuevo_codigo' => true,
        'email_codigo_destacado' => true,
        'email_saldo_bajo' => true,
        'email_saldo_bajo_limite' => 1.00,
        'push_notifications' => false
    ]
];

// Obtener configuraciones actuales
$configuraciones_actuales = $collection_configuracion->findOne(['tipo' => 'sistema']);
if (!$configuraciones_actuales) {
    // Crear configuraciones por defecto si no existen
    $collection_configuracion->insertOne([
        'tipo' => 'sistema',
        'configuraciones' => $configuraciones_default,
        'fecha_creacion' => new MongoDB\BSON\UTCDateTime(),
        'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
    ]);
    $configuraciones_actuales = ['configuraciones' => $configuraciones_default];
}

$configuraciones = $configuraciones_actuales['configuraciones'];

// Procesar actualizaciones
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_config') {
        $seccion = $_POST['seccion'] ?? '';
        $nuevas_configuraciones = $configuraciones;
        
        // Actualizar configuraciones según la sección
        switch ($seccion) {
            case 'sistema':
                $nuevas_configuraciones['sistema'] = [
                    'nombre_sitio' => $_POST['nombre_sitio'] ?? '',
                    'descripcion_sitio' => $_POST['descripcion_sitio'] ?? '',
                    'url_sitio' => $_POST['url_sitio'] ?? '',
                    'email_contacto' => $_POST['email_contacto'] ?? '',
                    'telefono_contacto' => $_POST['telefono_contacto'] ?? '',
                    'direccion_contacto' => $_POST['direccion_contacto'] ?? '',
                    'idioma_default' => $_POST['idioma_default'] ?? 'es',
                    'zona_horaria' => $_POST['zona_horaria'] ?? 'Europe/Madrid',
                    'moneda' => $_POST['moneda'] ?? 'EUR',
                    'simbolo_moneda' => $_POST['simbolo_moneda'] ?? '€'
                ];
                break;
                
            case 'usuarios':
                $nuevas_configuraciones['usuarios'] = [
                    'registro_abierto' => isset($_POST['registro_abierto']),
                    'verificacion_email' => isset($_POST['verificacion_email']),
                    'saldo_inicial' => (float)($_POST['saldo_inicial'] ?? 0),
                    'zumbidos_iniciales' => (int)($_POST['zumbidos_iniciales'] ?? 1),
                    'max_codigos_por_usuario' => (int)($_POST['max_codigos_por_usuario'] ?? 50),
                    'max_codigos_por_marca' => (int)($_POST['max_codigos_por_marca'] ?? 1)
                ];
                break;
                
            case 'codigos':
                $nuevas_configuraciones['codigos'] = [
                    'moderacion_automatica' => isset($_POST['moderacion_automatica']),
                    'destacado_costo' => (float)($_POST['destacado_costo'] ?? 5.00),
                    'destacado_duracion_dias' => (int)($_POST['destacado_duracion_dias'] ?? 7),
                    'codigo_min_length' => (int)($_POST['codigo_min_length'] ?? 3),
                    'codigo_max_length' => (int)($_POST['codigo_max_length'] ?? 50),
                    'descripcion_max_length' => (int)($_POST['descripcion_max_length'] ?? 500)
                ];
                break;
                
            case 'marcas':
                $nuevas_configuraciones['marcas'] = [
                    'auto_crear_marcas' => isset($_POST['auto_crear_marcas']),
                    'categoria_default' => $_POST['categoria_default'] ?? 'General',
                    'imagen_default' => $_POST['imagen_default'] ?? '/img/no_image.png',
                    'max_marcas_por_usuario' => (int)($_POST['max_marcas_por_usuario'] ?? 10)
                ];
                break;
                
            case 'pagos':
                $nuevas_configuraciones['pagos'] = [
                    'stripe_public_key' => $_POST['stripe_public_key'] ?? '',
                    'stripe_secret_key' => $_POST['stripe_secret_key'] ?? '',
                    'stripe_webhook_secret' => $_POST['stripe_webhook_secret'] ?? '',
                    'paypal_client_id' => $_POST['paypal_client_id'] ?? '',
                    'paypal_client_secret' => $_POST['paypal_client_secret'] ?? '',
                    'paypal_mode' => $_POST['paypal_mode'] ?? 'sandbox'
                ];
                break;
                
            case 'email':
                $nuevas_configuraciones['email'] = [
                    'smtp_host' => $_POST['smtp_host'] ?? '',
                    'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
                    'smtp_username' => $_POST['smtp_username'] ?? '',
                    'smtp_password' => $_POST['smtp_password'] ?? '',
                    'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
                    'email_from' => $_POST['email_from'] ?? 'noreply@codigoamigo.com',
                    'email_from_name' => $_POST['email_from_name'] ?? 'CodigoAmigo'
                ];
                break;
                
            case 'seo':
                $nuevas_configuraciones['seo'] = [
                    'meta_title' => $_POST['meta_title'] ?? '',
                    'meta_description' => $_POST['meta_description'] ?? '',
                    'meta_keywords' => $_POST['meta_keywords'] ?? '',
                    'google_analytics' => $_POST['google_analytics'] ?? '',
                    'google_tag_manager' => $_POST['google_tag_manager'] ?? '',
                    'facebook_pixel' => $_POST['facebook_pixel'] ?? ''
                ];
                break;
                
            case 'seguridad':
                $nuevas_configuraciones['seguridad'] = [
                    'max_intentos_login' => (int)($_POST['max_intentos_login'] ?? 5),
                    'tiempo_bloqueo_minutos' => (int)($_POST['tiempo_bloqueo_minutos'] ?? 15),
                    'requerir_https' => isset($_POST['requerir_https']),
                    'session_timeout_minutos' => (int)($_POST['session_timeout_minutos'] ?? 60),
                    'log_actividad' => isset($_POST['log_actividad'])
                ];
                break;
                
            case 'notificaciones':
                $nuevas_configuraciones['notificaciones'] = [
                    'email_nuevo_codigo' => isset($_POST['email_nuevo_codigo']),
                    'email_codigo_destacado' => isset($_POST['email_codigo_destacado']),
                    'email_saldo_bajo' => isset($_POST['email_saldo_bajo']),
                    'email_saldo_bajo_limite' => (float)($_POST['email_saldo_bajo_limite'] ?? 1.00),
                    'push_notifications' => isset($_POST['push_notifications'])
                ];
                break;
        }
        
        // Actualizar en la base de datos
        $result = $collection_configuracion->updateOne(
            ['tipo' => 'sistema'],
            [
                '$set' => [
                    'configuraciones' => $nuevas_configuraciones,
                    'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime(),
                    'ultimo_admin' => $_SESSION["user_id"]
                ]
            ]
        );
        
        if ($result->getModifiedCount() > 0) {
            $_SESSION['success_message'] = "Configuraciones actualizadas correctamente";
            $configuraciones = $nuevas_configuraciones;
        } else {
            $_SESSION['error_message'] = "No se realizaron cambios en las configuraciones";
        }
    }
}

$title = "Configuración del Sistema - Panel de Administración";
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
        .config-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .config-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }
        .config-body {
            padding: 20px;
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
                        <a class="nav-link active" href="admin_configuracion.php">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </a>
                        <a class="nav-link" href="admin_logs.php">
                            <i class="fas fa-file-alt me-2"></i>Logs
                        </a>
                        <hr class="text-white">
                        <a class="nav-link" href="https://www.codigoamigo.com">
                            <i class="fas fa-home me-2"></i>Volver al sitioo
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-admin">
                    <div class="container-fluid">
                        <h5 class="mb-0">Configuración del Sistema</h5>
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
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Configuración del Sistema -->
                    <div class="config-section">
                        <div class="config-header">
                            <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Configuración General del Sistema</h5>
                        </div>
                        <div class="config-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_config">
                                <input type="hidden" name="seccion" value="sistema">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nombre del Sitio</label>
                                        <input type="text" class="form-control" name="nombre_sitio" 
                                               value="<?php echo htmlspecialchars($configuraciones['sistema']['nombre_sitio']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">URL del Sitio</label>
                                        <input type="url" class="form-control" name="url_sitio" 
                                               value="<?php echo htmlspecialchars($configuraciones['sistema']['url_sitio']); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Descripción del Sitio</label>
                                    <textarea class="form-control" name="descripcion_sitio" rows="3"><?php echo htmlspecialchars($configuraciones['sistema']['descripcion_sitio']); ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Email de Contacto</label>
                                        <input type="email" class="form-control" name="email_contacto" 
                                               value="<?php echo htmlspecialchars($configuraciones['sistema']['email_contacto']); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Teléfono de Contacto</label>
                                        <input type="tel" class="form-control" name="telefono_contacto" 
                                               value="<?php echo htmlspecialchars($configuraciones['sistema']['telefono_contacto']); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Idioma por Defecto</label>
                                        <select class="form-select" name="idioma_default">
                                            <option value="es" <?php echo $configuraciones['sistema']['idioma_default'] == 'es' ? 'selected' : ''; ?>>Español</option>
                                            <option value="en" <?php echo $configuraciones['sistema']['idioma_default'] == 'en' ? 'selected' : ''; ?>>English</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Zona Horaria</label>
                                        <select class="form-select" name="zona_horaria">
                                            <option value="Europe/Madrid" <?php echo $configuraciones['sistema']['zona_horaria'] == 'Europe/Madrid' ? 'selected' : ''; ?>>Europe/Madrid</option>
                                            <option value="Europe/London" <?php echo $configuraciones['sistema']['zona_horaria'] == 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                                            <option value="America/New_York" <?php echo $configuraciones['sistema']['zona_horaria'] == 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Moneda</label>
                                        <div class="row">
                                            <div class="col-6">
                                                <select class="form-select" name="moneda">
                                                    <option value="EUR" <?php echo $configuraciones['sistema']['moneda'] == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                                    <option value="USD" <?php echo $configuraciones['sistema']['moneda'] == 'USD' ? 'selected' : ''; ?>>USD</option>
                                                    <option value="GBP" <?php echo $configuraciones['sistema']['moneda'] == 'GBP' ? 'selected' : ''; ?>>GBP</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <input type="text" class="form-control" name="simbolo_moneda" 
                                                       value="<?php echo htmlspecialchars($configuraciones['sistema']['simbolo_moneda']); ?>" placeholder="€">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Guardar Configuración
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Configuración de Usuarios -->
                    <div class="config-section">
                        <div class="config-header">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Configuración de Usuarios</h5>
                        </div>
                        <div class="config-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_config">
                                <input type="hidden" name="seccion" value="usuarios">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="registro_abierto" 
                                                   <?php echo $configuraciones['usuarios']['registro_abierto'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Registro abierto para nuevos usuarios</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="verificacion_email" 
                                                   <?php echo $configuraciones['usuarios']['verificacion_email'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Verificación de email obligatoria</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Saldo Inicial (€)</label>
                                        <input type="number" step="0.01" class="form-control" name="saldo_inicial" 
                                               value="<?php echo $configuraciones['usuarios']['saldo_inicial']; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Zumbidos Iniciales</label>
                                        <input type="number" class="form-control" name="zumbidos_iniciales" 
                                               value="<?php echo $configuraciones['usuarios']['zumbidos_iniciales']; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Máx. Códigos por Usuario</label>
                                        <input type="number" class="form-control" name="max_codigos_por_usuario" 
                                               value="<?php echo $configuraciones['usuarios']['max_codigos_por_usuario']; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Máx. Códigos por Marca</label>
                                        <input type="number" class="form-control" name="max_codigos_por_marca" 
                                               value="<?php echo $configuraciones['usuarios']['max_codigos_por_marca']; ?>">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Guardar Configuración
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Configuración de Códigos -->
                    <div class="config-section">
                        <div class="config-header">
                            <h5 class="mb-0"><i class="fas fa-code me-2"></i>Configuración de Códigos</h5>
                        </div>
                        <div class="config-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_config">
                                <input type="hidden" name="seccion" value="codigos">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="moderacion_automatica" 
                                                   <?php echo $configuraciones['codigos']['moderacion_automatica'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Moderación automática de códigos</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Costo de Destacado (€)</label>
                                        <input type="number" step="0.01" class="form-control" name="destacado_costo" 
                                               value="<?php echo $configuraciones['codigos']['destacado_costo']; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Duración Destacado (días)</label>
                                        <input type="number" class="form-control" name="destacado_duracion_dias" 
                                               value="<?php echo $configuraciones['codigos']['destacado_duracion_dias']; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Longitud Mín. Código</label>
                                        <input type="number" class="form-control" name="codigo_min_length" 
                                               value="<?php echo $configuraciones['codigos']['codigo_min_length']; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Longitud Máx. Código</label>
                                        <input type="number" class="form-control" name="codigo_max_length" 
                                               value="<?php echo $configuraciones['codigos']['codigo_max_length']; ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Longitud Máx. Descripción</label>
                                    <input type="number" class="form-control" name="descripcion_max_length" 
                                           value="<?php echo $configuraciones['codigos']['descripcion_max_length']; ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Guardar Configuración
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Configuración de Pagos -->
                    <div class="config-section">
                        <div class="config-header">
                            <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Configuración de Pagos</h5>
                        </div>
                        <div class="config-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_config">
                                <input type="hidden" name="seccion" value="pagos">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Stripe Public Key</label>
                                        <input type="text" class="form-control" name="stripe_public_key" 
                                               value="<?php echo htmlspecialchars($configuraciones['pagos']['stripe_public_key']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Stripe Secret Key</label>
                                        <input type="password" class="form-control" name="stripe_secret_key" 
                                               value="<?php echo htmlspecialchars($configuraciones['pagos']['stripe_secret_key']); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stripe Webhook Secret</label>
                                    <input type="password" class="form-control" name="stripe_webhook_secret" 
                                           value="<?php echo htmlspecialchars($configuraciones['pagos']['stripe_webhook_secret']); ?>">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">PayPal Client ID</label>
                                        <input type="text" class="form-control" name="paypal_client_id" 
                                               value="<?php echo htmlspecialchars($configuraciones['pagos']['paypal_client_id']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">PayPal Client Secret</label>
                                        <input type="password" class="form-control" name="paypal_client_secret" 
                                               value="<?php echo htmlspecialchars($configuraciones['pagos']['paypal_client_secret']); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">PayPal Mode</label>
                                    <select class="form-select" name="paypal_mode">
                                        <option value="sandbox" <?php echo $configuraciones['pagos']['paypal_mode'] == 'sandbox' ? 'selected' : ''; ?>>Sandbox</option>
                                        <option value="live" <?php echo $configuraciones['pagos']['paypal_mode'] == 'live' ? 'selected' : ''; ?>>Live</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Guardar Configuración
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Configuración de Email -->
                    <div class="config-section">
                        <div class="config-header">
                            <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Configuración de Email</h5>
                        </div>
                        <div class="config-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_config">
                                <input type="hidden" name="seccion" value="email">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" name="smtp_host" 
                                               value="<?php echo htmlspecialchars($configuraciones['email']['smtp_host']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" name="smtp_port" 
                                               value="<?php echo $configuraciones['email']['smtp_port']; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control" name="smtp_username" 
                                               value="<?php echo htmlspecialchars($configuraciones['email']['smtp_username']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Password</label>
                                        <input type="password" class="form-control" name="smtp_password" 
                                               value="<?php echo htmlspecialchars($configuraciones['email']['smtp_password']); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Encryption</label>
                                        <select class="form-select" name="smtp_encryption">
                                            <option value="tls" <?php echo $configuraciones['email']['smtp_encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo $configuraciones['email']['smtp_encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="" <?php echo $configuraciones['email']['smtp_encryption'] == '' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email From</label>
                                        <input type="email" class="form-control" name="email_from" 
                                               value="<?php echo htmlspecialchars($configuraciones['email']['email_from']); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email From Name</label>
                                    <input type="text" class="form-control" name="email_from_name" 
                                           value="<?php echo htmlspecialchars($configuraciones['email']['email_from_name']); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Guardar Configuración
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Configuración de SEO -->
                    <div class="config-section">
                        <div class="config-header">
                            <h5 class="mb-0"><i class="fas fa-search me-2"></i>Configuración de SEO</h5>
                        </div>
                        <div class="config-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_config">
                                <input type="hidden" name="seccion" value="seo">
                                <div class="mb-3">
                                    <label class="form-label">Meta Title</label>
                                    <input type="text" class="form-control" name="meta_title" 
                                           value="<?php echo htmlspecialchars($configuraciones['seo']['meta_title']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Meta Description</label>
                                    <textarea class="form-control" name="meta_description" rows="3"><?php echo htmlspecialchars($configuraciones['seo']['meta_description']); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Meta Keywords</label>
                                    <input type="text" class="form-control" name="meta_keywords" 
                                           value="<?php echo htmlspecialchars($configuraciones['seo']['meta_keywords']); ?>">
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Google Analytics</label>
                                        <input type="text" class="form-control" name="google_analytics" 
                                               value="<?php echo htmlspecialchars($configuraciones['seo']['google_analytics']); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Google Tag Manager</label>
                                        <input type="text" class="form-control" name="google_tag_manager" 
                                               value="<?php echo htmlspecialchars($configuraciones['seo']['google_tag_manager']); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Facebook Pixel</label>
                                        <input type="text" class="form-control" name="facebook_pixel" 
                                               value="<?php echo htmlspecialchars($configuraciones['seo']['facebook_pixel']); ?>">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Guardar Configuración
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Configuración de Seguridad -->
                    <div class="config-section">
                        <div class="config-header">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Configuración de Seguridad</h5>
                        </div>
                        <div class="config-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_config">
                                <input type="hidden" name="seccion" value="seguridad">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Máx. Intentos de Login</label>
                                        <input type="number" class="form-control" name="max_intentos_login" 
                                               value="<?php echo $configuraciones['seguridad']['max_intentos_login']; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tiempo de Bloqueo (minutos)</label>
                                        <input type="number" class="form-control" name="tiempo_bloqueo_minutos" 
                                               value="<?php echo $configuraciones['seguridad']['tiempo_bloqueo_minutos']; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="requerir_https" 
                                                   <?php echo $configuraciones['seguridad']['requerir_https'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Requerir HTTPS</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Timeout de Sesión (minutos)</label>
                                        <input type="number" class="form-control" name="session_timeout_minutos" 
                                               value="<?php echo $configuraciones['seguridad']['session_timeout_minutos']; ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="log_actividad" 
                                               <?php echo $configuraciones['seguridad']['log_actividad'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Registrar actividad de usuarios</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Guardar Configuración
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Auto-save functionality (opcional)
        $('form').on('submit', function(e) {
            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.html();
            
            submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');
            submitBtn.prop('disabled', true);
            
            // Re-enable button after 3 seconds (in case of error)
            setTimeout(function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }, 3000);
        });
    </script>
    
<?php get_footer(); ?>
</body>
</html>


