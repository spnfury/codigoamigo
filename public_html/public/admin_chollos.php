<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_chollos.php';
include_once __DIR__ . '/../myphp/funciones_chollos_fuentes.php';
include_once __DIR__ . '/../myphp/funciones_chollos_amazon.php';
include_once __DIR__ . '/../myphp/funciones_chollos_groq.php';
include_once __DIR__ . '/admin_sidebar_menu.php';

// Verificar permisos de administrador
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702", //thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", //edna
    "639899bc6321ee0d0e4010d2", //aron
    "5c8a10ce2f55c86d6e707d82"  //jose
];

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$collection_chollos = getCollectionChollos();
$categorias = obtenerCategoriasChollos();

// Procesar acciones
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'crear_chollo':
            $datos = [
                'titulo' => trim($_POST['titulo'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'precio_original' => !empty($_POST['precio_original']) ? floatval($_POST['precio_original']) : null,
                'precio_descuento' => !empty($_POST['precio_descuento']) ? floatval($_POST['precio_descuento']) : null,
                'porcentaje_descuento' => !empty($_POST['porcentaje_descuento']) ? intval($_POST['porcentaje_descuento']) : null,
                'enlace' => trim($_POST['enlace'] ?? ''),
                'imagen' => trim($_POST['imagen'] ?? ''),
                'categoria' => $_POST['categoria'] ?? 'general',
                'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d H:i:s'),
                'fecha_fin' => $_POST['fecha_fin'] ?? null,
                'fuente' => $_POST['fuente'] ?? 'manual',
                'estado' => isset($_POST['estado']) ? intval($_POST['estado']) : 1
            ];
            
            // Convertir enlace de Amazon si es necesario
            if (!empty($datos['enlace']) && esEnlaceAmazon($datos['enlace'])) {
                $datos['enlace_original'] = $datos['enlace'];
                $datos['enlace'] = convertirEnlaceAmazon($datos['enlace']);
            }
            
            // Reescribir texto con Groq si está activado
            if (isset($_POST['reescribir_automatico']) && $_POST['reescribir_automatico'] == '1') {
                if (!empty($datos['titulo'])) {
                    $resultado_titulo = reescribirTextoGroq($datos['titulo'], 'titulo');
                    if ($resultado_titulo['success']) {
                        $datos['titulo'] = $resultado_titulo['texto_reescrito'];
                        $datos['texto_reescrito'] = true;
                    }
                }
                
                if (!empty($datos['descripcion'])) {
                    $resultado_desc = reescribirTextoGroq($datos['descripcion'], 'descripcion');
                    if ($resultado_desc['success']) {
                        $datos['descripcion'] = $resultado_desc['texto_reescrito'];
                    }
                }
            }
            
            $resultado = crearChollo($datos);
            if ($resultado['success']) {
                $_SESSION['success_message'] = "Chollo creado correctamente";
            } else {
                $_SESSION['error_message'] = $resultado['error'] ?? "Error al crear el chollo";
            }
            break;
            
        case 'actualizar_chollo':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                $_SESSION['error_message'] = "ID requerido";
                break;
            }
            
            $datos = [];
            if (isset($_POST['titulo'])) $datos['titulo'] = trim($_POST['titulo']);
            if (isset($_POST['descripcion'])) $datos['descripcion'] = trim($_POST['descripcion']);
            if (isset($_POST['precio_original'])) $datos['precio_original'] = !empty($_POST['precio_original']) ? floatval($_POST['precio_original']) : null;
            if (isset($_POST['precio_descuento'])) $datos['precio_descuento'] = !empty($_POST['precio_descuento']) ? floatval($_POST['precio_descuento']) : null;
            if (isset($_POST['porcentaje_descuento'])) $datos['porcentaje_descuento'] = !empty($_POST['porcentaje_descuento']) ? intval($_POST['porcentaje_descuento']) : null;
            if (isset($_POST['enlace'])) {
                $enlace = trim($_POST['enlace']);
                if (esEnlaceAmazon($enlace)) {
                    $datos['enlace'] = convertirEnlaceAmazon($enlace);
                } else {
                    $datos['enlace'] = $enlace;
                }
            }
            if (isset($_POST['imagen'])) $datos['imagen'] = trim($_POST['imagen']);
            if (isset($_POST['categoria'])) $datos['categoria'] = $_POST['categoria'];
            if (isset($_POST['fecha_inicio'])) $datos['fecha_inicio'] = $_POST['fecha_inicio'];
            if (isset($_POST['fecha_fin'])) $datos['fecha_fin'] = $_POST['fecha_fin'];
            if (isset($_POST['fuente'])) $datos['fuente'] = $_POST['fuente'];
            if (isset($_POST['estado'])) $datos['estado'] = intval($_POST['estado']);
            
            $resultado = actualizarChollo($id, $datos);
            if ($resultado['success']) {
                $_SESSION['success_message'] = "Chollo actualizado correctamente";
            } else {
                $_SESSION['error_message'] = $resultado['error'] ?? "Error al actualizar el chollo";
            }
            break;
            
        case 'eliminar_chollo':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                $_SESSION['error_message'] = "ID requerido";
                break;
            }
            
            $resultado = eliminarChollo($id);
            if ($resultado['success']) {
                $_SESSION['success_message'] = "Chollo eliminado correctamente";
            } else {
                $_SESSION['error_message'] = $resultado['error'] ?? "Error al eliminar el chollo";
            }
            break;
            
        case 'toggle_estado':
            $id = $_POST['id'] ?? '';
            $nuevo_estado = intval($_POST['nuevo_estado'] ?? 0);
            
            $resultado = actualizarChollo($id, ['estado' => $nuevo_estado]);
            if ($resultado['success']) {
                $_SESSION['success_message'] = "Estado actualizado";
            } else {
                $_SESSION['error_message'] = "Error al actualizar el estado";
            }
            break;
            
        case 'bulk_action':
            $chollo_ids = $_POST['chollo_ids'] ?? [];
            $bulk_action = $_POST['bulk_action'] ?? '';
            
            if (empty($chollo_ids)) {
                $_SESSION['error_message'] = "No se seleccionaron chollos";
            } else {
                $object_ids = array_map(function($id) {
                    return new MongoDB\BSON\ObjectId($id);
                }, $chollo_ids);
                
                switch ($bulk_action) {
                    case 'activate':
                        $result = $collection_chollos->updateMany(
                            ['_id' => ['$in' => $object_ids]],
                            ['$set' => ['estado' => 1, 'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()]]
                        );
                        $_SESSION['success_message'] = "Se activaron {$result->getModifiedCount()} chollos";
                        break;
                        
                    case 'deactivate':
                        $result = $collection_chollos->updateMany(
                            ['_id' => ['$in' => $object_ids]],
                            ['$set' => ['estado' => 0, 'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()]]
                        );
                        $_SESSION['success_message'] = "Se desactivaron {$result->getModifiedCount()} chollos";
                        break;
                        
                    case 'delete':
                        $result = $collection_chollos->deleteMany(['_id' => ['$in' => $object_ids]]);
                        $_SESSION['success_message'] = "Se eliminaron {$result->getDeletedCount()} chollos";
                        break;
                }
            }
            break;
            
        case 'crear_fuente':
            $datos = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'tipo' => $_POST['tipo'] ?? 'telegram',
                'url' => trim($_POST['url'] ?? ''),
                'configuracion' => json_decode($_POST['configuracion'] ?? '{}', true),
                'activo' => isset($_POST['activo']) ? (bool)$_POST['activo'] : true
            ];
            
            $resultado = crearFuente($datos);
            if ($resultado['success']) {
                $_SESSION['success_message'] = "Fuente creada correctamente";
            } else {
                $_SESSION['error_message'] = $resultado['error'] ?? "Error al crear la fuente";
            }
            break;
            
        case 'actualizar_fuente':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                $_SESSION['error_message'] = "ID requerido";
                break;
            }
            
            $datos = [];
            if (isset($_POST['nombre'])) $datos['nombre'] = trim($_POST['nombre']);
            if (isset($_POST['tipo'])) $datos['tipo'] = $_POST['tipo'];
            if (isset($_POST['url'])) $datos['url'] = trim($_POST['url']);
            if (isset($_POST['configuracion'])) $datos['configuracion'] = json_decode($_POST['configuracion'], true);
            if (isset($_POST['activo'])) $datos['activo'] = (bool)$_POST['activo'];
            
            $resultado = actualizarFuente($id, $datos);
            if ($resultado['success']) {
                $_SESSION['success_message'] = "Fuente actualizada correctamente";
            } else {
                $_SESSION['error_message'] = $resultado['error'] ?? "Error al actualizar la fuente";
            }
            break;
            
        case 'eliminar_fuente':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                $_SESSION['error_message'] = "ID requerido";
                break;
            }
            
            $resultado = eliminarFuente($id);
            if ($resultado['success']) {
                $_SESSION['success_message'] = "Fuente eliminada correctamente";
            } else {
                $_SESSION['error_message'] = $resultado['error'] ?? "Error al eliminar la fuente";
            }
            break;
            
        case 'toggle_fuente_estado':
            $id = $_POST['id'] ?? '';
            $nuevo_estado = isset($_POST['nuevo_estado']) ? (bool)$_POST['nuevo_estado'] : false;
            
            $resultado = actualizarFuente($id, ['activo' => $nuevo_estado]);
            if ($resultado['success']) {
                $_SESSION['success_message'] = "Estado de fuente actualizado";
            } else {
                $_SESSION['error_message'] = "Error al actualizar el estado";
            }
            break;
    }
    
    // Redirigir para evitar reenvío
    $redirect_params = $_GET;
    $redirect_url = 'admin_chollos.php?' . http_build_query($redirect_params);
    header('Location: ' . $redirect_url);
    exit;
}

// Obtener filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_fuente = $_GET['fuente'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = intval($_GET['limit'] ?? 50);

// Construir filtros para la consulta
$filtros = [];
if ($filtro_estado !== '') {
    $filtros['estado'] = intval($filtro_estado);
}

// Filtro por categoría - puede ser string o array en la BD
if ($filtro_categoria) {
    // Buscar tanto si categoría es string como si es array que contiene el valor
    $filtros['$or'] = [
        ['categoria' => $filtro_categoria], // Si es string exacto
        ['categoria' => ['$elemMatch' => ['$eq' => $filtro_categoria]]] // Si es array que contiene el valor
    ];
}

// Filtro por fuente
if ($filtro_fuente) {
    $filtros['fuente'] = $filtro_fuente;
}

// Filtro por búsqueda - combinar con categoría si existe
if ($filtro_busqueda) {
    $busqueda_or = [
        ['titulo' => ['$regex' => $filtro_busqueda, '$options' => 'i']],
        ['descripcion' => ['$regex' => $filtro_busqueda, '$options' => 'i']]
    ];
    
    // Si ya hay un $or (de categoría), usar $and para combinar
    if (isset($filtros['$or'])) {
        $filtros = [
            '$and' => [
                ['$or' => $filtros['$or']],
                ['$or' => $busqueda_or]
            ]
        ];
    } else {
        $filtros['$or'] = $busqueda_or;
    }
}

// Paginación
$skip = ($page - 1) * $limit;

// Obtener chollos
$chollos_cursor = $collection_chollos->find($filtros, [
    'sort' => ['fecha_creacion' => -1],
    'skip' => $skip,
    'limit' => $limit
]);

// Convertir cursor a array de forma segura
$chollos = [];
try {
    $chollos = $chollos_cursor->toArray();
} catch (Exception $e) {
    // Si falla toArray(), iterar manualmente
    foreach ($chollos_cursor as $doc) {
        $chollos[] = $doc;
    }
}

$total_chollos = $collection_chollos->countDocuments($filtros);
$total_pages = ceil($total_chollos / $limit);

// Estadísticas
$estadisticas = obtenerEstadisticasChollos();

// Obtener fuentes (todas, sin filtro)
$fuentes = obtenerFuentes([]);
$estadisticas_fuentes = obtenerEstadisticasFuentes();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Chollos - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo get_admin_sidebar_menu('admin_chollos.php'); ?>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <nav class="navbar navbar-admin mb-4">
                    <div class="container-fluid">
                        <h5 class="mb-0">Gestión de Chollos</h5>
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

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-primary"><?php echo number_format($estadisticas['total'] ?? 0); ?></h3>
                                    <p class="text-muted mb-0">Total</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo number_format($estadisticas['activos'] ?? 0); ?></h3>
                                    <p class="text-muted mb-0">Activos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-danger"><?php echo number_format($estadisticas['inactivos'] ?? 0); ?></h3>
                                    <p class="text-muted mb-0">Inactivos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-secondary"><?php echo number_format($total_chollos); ?></h3>
                                    <p class="text-muted mb-0">Filtrados</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón para crear nuevo chollo -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearChollo">
                                <i class="fas fa-plus me-2"></i>Crear Nuevo Chollo
                            </button>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImportarTexto">
                                <i class="fas fa-file-import me-2"></i>Importar desde Texto
                            </button>
                            <a href="configurar_webhook_telegram.php" class="btn btn-info">
                                <i class="fa-brands fa-telegram me-2"></i>Configurar Webhook Telegram
                            </a>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalGestionFuentes">
                                <i class="fas fa-database me-2"></i>Gestionar Fuentes
                            </button>
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
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="1" <?php echo $filtro_estado === '1' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="0" <?php echo $filtro_estado === '0' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Categoría</label>
                                    <select name="categoria" class="form-select">
                                        <option value="">Todas</option>
                                        <?php foreach ($categorias as $key => $nombre): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $filtro_categoria === $key ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($nombre); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Fuente</label>
                                    <select name="fuente" class="form-select">
                                        <option value="">Todas</option>
                                        <option value="telegram" <?php echo $filtro_fuente === 'telegram' ? 'selected' : ''; ?>>Telegram</option>
                                        <option value="manual" <?php echo $filtro_fuente === 'manual' ? 'selected' : ''; ?>>Manual</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Buscar</label>
                                    <input type="text" name="busqueda" class="form-control" 
                                           placeholder="Título o descripción" value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Mostrar</label>
                                    <select name="limit" class="form-select">
                                        <option value="20" <?php echo $limit === 20 ? 'selected' : ''; ?>>20</option>
                                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Acciones masivas -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="POST" id="bulkForm">
                                <input type="hidden" name="action" value="bulk_action">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <label class="form-label">Acción masiva:</label>
                                        <select name="bulk_action" class="form-select" id="bulkAction">
                                            <option value="">Seleccionar acción</option>
                                            <option value="activate">Activar seleccionados</option>
                                            <option value="deactivate">Desactivar seleccionados</option>
                                            <option value="delete">Eliminar seleccionados</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-outline-primary" onclick="selectAll()">
                                            <i class="fas fa-check-square me-1"></i>Seleccionar todos
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="deselectAll()">
                                            <i class="fas fa-square me-1"></i>Deseleccionar
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-warning" id="bulkSubmit" disabled>
                                            <i class="fas fa-cogs me-1"></i>Ejecutar acción
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted" id="selectedCount">0 seleccionados</span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de chollos -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Lista de Chollos</h5>
                            <span class="badge bg-primary"><?php echo number_format($total_chollos); ?> chollos</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="30">
                                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                            </th>
                                            <th>Título</th>
                                            <th>Categoría</th>
                                            <th>Precio</th>
                                            <th>Descuento</th>
                                            <th>Fuente</th>
                                            <th>Clicks</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($chollos)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">
                                                No hay chollos que mostrar
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($chollos as $chollo): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="chollo-checkbox" value="<?php echo $chollo['_id']; ?>" 
                                                       onchange="updateSelectedCount()">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars(substr($chollo['titulo'] ?? 'Sin título', 0, 50)); ?></strong>
                                                <?php if (strlen($chollo['titulo'] ?? '') > 50): ?>...<?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php 
                                                    $categoria_chollo = $chollo['categoria'] ?? 'general';
                                                    // Manejar categoría que puede ser array o string
                                                    if (is_array($categoria_chollo)) {
                                                        $categoria_chollo = !empty($categoria_chollo) ? $categoria_chollo[0] : 'general';
                                                    }
                                                    // Si es objeto BSONArray, convertir a array
                                                    if (is_object($categoria_chollo) && method_exists($categoria_chollo, 'toArray')) {
                                                        $categoria_array = $categoria_chollo->toArray();
                                                        $categoria_chollo = !empty($categoria_array) ? $categoria_array[0] : 'general';
                                                    }
                                                    echo htmlspecialchars($categorias[$categoria_chollo] ?? 'General'); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($chollo['precio_descuento']): ?>
                                                    <strong class="text-success"><?php echo number_format($chollo['precio_descuento'], 2, ',', '.'); ?> €</strong>
                                                    <?php if ($chollo['precio_original']): ?>
                                                        <br><small class="text-muted text-decoration-line-through"><?php echo number_format($chollo['precio_original'], 2, ',', '.'); ?> €</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($chollo['porcentaje_descuento']): ?>
                                                    <span class="badge bg-danger">-<?php echo $chollo['porcentaje_descuento']; ?>%</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($chollo['fuente'] ?? 'manual'); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $clicks = $chollo['clicks'] ?? 0;
                                                $estadisticas = obtenerEstadisticasChollo((string)$chollo['_id']);
                                                ?>
                                                <strong class="text-primary"><?php echo number_format($clicks); ?></strong>
                                                <?php if ($estadisticas['clicks_hoy'] > 0): ?>
                                                    <br><small class="text-success">+<?php echo $estadisticas['clicks_hoy']; ?> hoy</small>
                                                <?php endif; ?>
                                                <br>
                                                <button type="button" class="btn btn-sm btn-outline-info mt-1" 
                                                        onclick="verEstadisticasChollo('<?php echo $chollo['_id']; ?>')" 
                                                        title="Ver estadísticas detalladas">
                                                    <i class="fas fa-chart-line"></i> Stats
                                                </button>
                                            </td>
                                            <td>
                                                <?php if ($chollo['estado'] == 1): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    if (isset($chollo['fecha_creacion']) && $chollo['fecha_creacion'] instanceof MongoDB\BSON\UTCDateTime) {
                                                        echo date('d/m/Y H:i', $chollo['fecha_creacion']->toDateTime()->getTimestamp());
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            onclick="editarChollo('<?php echo $chollo['_id']; ?>')" 
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick="reescribirChollo('<?php echo $chollo['_id']; ?>')" 
                                                            title="Reescribir con IA">
                                                        <i class="fas fa-magic"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            onclick="publicarTelegram('<?php echo $chollo['_id']; ?>')" 
                                                            title="Publicar en Telegram">
                                                        <i class="fa-brands fa-telegram"></i>
                                                    </button>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro?');">
                                                        <input type="hidden" name="action" value="toggle_estado">
                                                        <input type="hidden" name="id" value="<?php echo $chollo['_id']; ?>">
                                                        <input type="hidden" name="nuevo_estado" value="<?php echo $chollo['estado'] == 1 ? 0 : 1; ?>">
                                                        <button type="submit" class="btn btn-sm btn-<?php echo $chollo['estado'] == 1 ? 'warning' : 'success'; ?>" 
                                                                title="<?php echo $chollo['estado'] == 1 ? 'Desactivar' : 'Activar'; ?>">
                                                            <i class="fas fa-<?php echo $chollo['estado'] == 1 ? 'eye-slash' : 'eye'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este chollo?');">
                                                        <input type="hidden" name="action" value="eliminar_chollo">
                                                        <input type="hidden" name="id" value="<?php echo $chollo['_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginación">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Anterior</a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Siguiente</a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Chollo -->
    <div class="modal fade" id="modalCrearChollo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Chollo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formCrearChollo">
                    <input type="hidden" name="action" value="crear_chollo">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Precio Original</label>
                                    <input type="number" name="precio_original" class="form-control" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Precio Descuento</label>
                                    <input type="number" name="precio_descuento" class="form-control" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">% Descuento</label>
                                    <input type="number" name="porcentaje_descuento" class="form-control" min="0" max="100">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Enlace *</label>
                            <input type="url" name="enlace" class="form-control" required>
                            <small class="text-muted">Los enlaces de Amazon se convertirán automáticamente con tu ID de afiliado</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen URL</label>
                            <input type="url" name="imagen" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categoría</label>
                                    <select name="categoria" class="form-select">
                                        <?php foreach ($categorias as $key => $nombre): ?>
                                        <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($nombre); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fuente</label>
                                    <select name="fuente" class="form-select">
                                        <option value="manual">Manual</option>
                                        <option value="telegram">Telegram</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="reescribir_automatico" value="1" id="reescribirAuto">
                                <label class="form-check-label" for="reescribirAuto">
                                    Reescribir texto automáticamente con Groq AI
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Chollo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Importar desde Texto -->
    <div class="modal fade" id="modalImportarTexto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Importar Chollo desde Texto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pega el texto del chollo (desde Telegram, etc.)</label>
                        <textarea id="textoImportar" class="form-control" rows="10" 
                                  placeholder="Pega aquí el texto completo del chollo..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select id="categoriaImportar" class="form-select">
                            <?php foreach ($categorias as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($nombre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="procesarImportacion()">
                        <i class="fas fa-magic me-2"></i>Procesar y Extraer Información
                    </button>
                    <div id="resultadoImportacion" class="mt-3" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gestión de Fuentes -->
    <div class="modal fade" id="modalGestionFuentes" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gestión de Fuentes de Datos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearFuente" onclick="$('#modalGestionFuentes').modal('hide');">
                            <i class="fas fa-plus me-2"></i>Agregar Nueva Fuente
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>URL</th>
                                    <th>Estado</th>
                                    <th>Última Sincronización</th>
                                    <th>Mensajes Procesados</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fuentes)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No hay fuentes configuradas
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($fuentes as $fuente): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($fuente['nombre']); ?></strong></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($fuente['tipo']); ?></span></td>
                                    <td><small><?php echo htmlspecialchars($fuente['url']); ?></small></td>
                                    <td>
                                        <?php if ($fuente['activo']): ?>
                                            <span class="badge bg-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo $fuente['ultima_sincronizacion'] ? htmlspecialchars($fuente['ultima_sincronizacion']) : 'Nunca'; ?>
                                        </small>
                                    </td>
                                    <td><?php echo number_format($fuente['mensajes_procesados']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Cambiar estado de esta fuente?');">
                                                <input type="hidden" name="action" value="toggle_fuente_estado">
                                                <input type="hidden" name="id" value="<?php echo $fuente['id']; ?>">
                                                <input type="hidden" name="nuevo_estado" value="<?php echo $fuente['activo'] ? '0' : '1'; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $fuente['activo'] ? 'warning' : 'success'; ?>" 
                                                        title="<?php echo $fuente['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                    <i class="fas fa-<?php echo $fuente['activo'] ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    onclick="editarFuente('<?php echo $fuente['id']; ?>')" 
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta fuente?');">
                                                <input type="hidden" name="action" value="eliminar_fuente">
                                                <input type="hidden" name="id" value="<?php echo $fuente['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Estadísticas de Fuentes</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <small class="text-muted">Total: <strong><?php echo number_format($estadisticas_fuentes['total'] ?? 0); ?></strong></small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Activas: <strong><?php echo number_format($estadisticas_fuentes['activas'] ?? 0); ?></strong></small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Inactivas: <strong><?php echo number_format($estadisticas_fuentes['inactivas'] ?? 0); ?></strong></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Fuente -->
    <div class="modal fade" id="modalCrearFuente" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Nueva Fuente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formCrearFuente">
                    <input type="hidden" name="action" value="crear_fuente">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required 
                                   placeholder="Ej: Canal WolfVVI">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo" class="form-select" required>
                                <option value="telegram">Telegram</option>
                                <option value="rss">RSS</option>
                                <option value="api">API</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL *</label>
                            <input type="text" name="url" class="form-control" required 
                                   placeholder="Ej: canalwolfvvi (sin @) o https://...">
                            <small class="text-muted">Para Telegram, solo el username sin @. Para otros tipos, la URL completa.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Configuración (JSON)</label>
                            <textarea name="configuracion" class="form-control" rows="3" 
                                      placeholder='{"key": "value"}'>{}</textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="activo" value="1" id="fuenteActiva" checked>
                                <label class="form-check-label" for="fuenteActiva">
                                    Fuente activa
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Fuente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAllCheckbox').checked;
            document.querySelectorAll('.chollo-checkbox').forEach(cb => cb.checked = selectAll);
            updateSelectedCount();
        }

        function selectAll() {
            document.querySelectorAll('.chollo-checkbox').forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheckbox').checked = true;
            updateSelectedCount();
        }

        function deselectAll() {
            document.querySelectorAll('.chollo-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheckbox').checked = false;
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const selected = document.querySelectorAll('.chollo-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = selected + ' seleccionados';
            document.getElementById('bulkSubmit').disabled = selected === 0 || document.getElementById('bulkAction').value === '';
        }

        document.getElementById('bulkAction').addEventListener('change', function() {
            updateSelectedCount();
        });

        document.getElementById('bulkForm').addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.chollo-checkbox:checked');
            if (selected.length === 0) {
                e.preventDefault();
                alert('Selecciona al menos un chollo');
                return false;
            }
            
            const action = document.getElementById('bulkAction').value;
            if (!action) {
                e.preventDefault();
                alert('Selecciona una acción');
                return false;
            }
            
            if (action === 'delete' && !confirm('¿Estás seguro de eliminar los chollos seleccionados?')) {
                e.preventDefault();
                return false;
            }
            
            // Añadir IDs seleccionados al formulario
            const formData = new FormData(this);
            selected.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'chollo_ids[]';
                input.value = cb.value;
                this.appendChild(input);
            });
        });

        function editarChollo(id) {
            // TODO: Implementar modal de edición
            alert('Funcionalidad de edición en desarrollo. Por ahora usa el panel de administración.');
        }

        function reescribirChollo(id) {
            if (!confirm('¿Reescribir el texto de este chollo con Groq AI?')) return;
            
            fetch('/ajax/chollos_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'metodo=reescribir_texto&id=' + id + '&tipo=ambos'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Texto reescrito correctamente. Recarga la página para ver los cambios.');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(err => {
                alert('Error de conexión: ' + err);
            });
        }

        function publicarTelegram(id) {
            if (!confirm('¿Publicar este chollo en el canal de Telegram?')) return;
            
            fetch('/ajax/chollos_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'metodo=publicar_telegram&id=' + id
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Chollo publicado en Telegram correctamente');
                } else {
                    alert('Error: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(err => {
                alert('Error de conexión: ' + err);
            });
        }

        function procesarImportacion() {
            const texto = document.getElementById('textoImportar').value;
            const categoria = document.getElementById('categoriaImportar').value;
            
            if (!texto.trim()) {
                alert('Por favor, pega el texto del chollo');
                return;
            }
            
            fetch('/ajax/chollos_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'metodo=importar_desde_texto&texto=' + encodeURIComponent(texto) + '&categoria=' + categoria
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const resultado = document.getElementById('resultadoImportacion');
                    resultado.style.display = 'block';
                    resultado.innerHTML = '<div class="alert alert-success">Información extraída correctamente. Revisa los datos y crea el chollo.</div>' +
                        '<form method="POST" id="formCrearDesdeImportacion">' +
                        '<input type="hidden" name="action" value="crear_chollo">' +
                        '<input type="hidden" name="titulo" value="' + (data.datos.titulo || '').replace(/"/g, '&quot;') + '">' +
                        '<input type="hidden" name="descripcion" value="' + (data.datos.descripcion || '').replace(/"/g, '&quot;') + '">' +
                        '<input type="hidden" name="precio_original" value="' + (data.datos.precio_original || '') + '">' +
                        '<input type="hidden" name="precio_descuento" value="' + (data.datos.precio_descuento || '') + '">' +
                        '<input type="hidden" name="porcentaje_descuento" value="' + (data.datos.porcentaje_descuento || '') + '">' +
                        '<input type="hidden" name="enlace" value="' + (data.datos.enlace || '').replace(/"/g, '&quot;') + '">' +
                        '<input type="hidden" name="imagen" value="' + (data.datos.imagen || '').replace(/"/g, '&quot;') + '">' +
                        '<input type="hidden" name="categoria" value="' + categoria + '">' +
                        '<input type="hidden" name="fuente" value="telegram">' +
                        '<input type="hidden" name="estado" value="0">' +
                        '<div class="mb-3"><strong>Título:</strong> ' + (data.datos.titulo || 'N/A') + '</div>' +
                        '<div class="mb-3"><strong>Precio:</strong> ' + (data.datos.precio_descuento || 'N/A') + ' €</div>' +
                        '<div class="mb-3"><strong>Enlace:</strong> <a href="' + (data.datos.enlace || '#') + '" target="_blank">Ver</a></div>' +
                        '<button type="submit" class="btn btn-success">Crear Chollo</button>' +
                        '</form>';
                } else {
                    alert('Error: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(err => {
                alert('Error de conexión: ' + err);
            });
        }

        function editarFuente(id) {
            // TODO: Implementar modal de edición de fuente
            alert('Funcionalidad de edición en desarrollo. Por ahora elimina y crea una nueva fuente.');
        }

        function verEstadisticasChollo(cholloId) {
            fetch('/ajax/chollos_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'metodo=obtener_estadisticas&chollo_id=' + cholloId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const stats = data.estadisticas;
                    const modalBody = document.getElementById('estadisticasCholloBody');
                    modalBody.innerHTML = `
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-primary">${stats.total_clicks || 0}</h3>
                                        <small class="text-muted">Total Clicks</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-success">${stats.clicks_hoy || 0}</h3>
                                        <small class="text-muted">Hoy</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-info">${stats.clicks_semana || 0}</h3>
                                        <small class="text-muted">Esta Semana</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-warning">${stats.clicks_mes || 0}</h3>
                                        <small class="text-muted">Este Mes</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h5>Últimos Clicks</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>IP</th>
                                        <th>Referer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${(stats.ultimos_clicks || []).map(click => `
                                        <tr>
                                            <td>${click.fecha || 'N/A'}</td>
                                            <td><small>${click.ip || 'unknown'}</small></td>
                                            <td><small>${click.referer || 'Directo'}</small></td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="3" class="text-center text-muted">No hay clicks registrados</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    `;
                    const modal = new bootstrap.Modal(document.getElementById('modalEstadisticasChollo'));
                    modal.show();
                } else {
                    alert('Error al obtener estadísticas: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(err => {
                alert('Error de conexión: ' + err);
            });
        }
    </script>

    <!-- Modal Estadísticas Chollo -->
    <div class="modal fade" id="modalEstadisticasChollo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Estadísticas de Clicks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="estadisticasCholloBody">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

