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

$collection_marcas = getCollectionMarcas();
$collection_codigos = getCollectionCodigos();
$collection_categorias = getCollectionCategoriasEvo();

// Procesar acciones
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_marca':
            $nombre = trim($_POST['nombre']);
            $categoria = $_POST['categoria'];
            $descripcion = trim($_POST['descripcion']);
            $url = trim($_POST['url']);
            $url_register = trim($_POST['url_register']);
            
            // Verificar si la marca ya existe
            $marca_existente = $collection_marcas->findOne(['nombre_clave' => strtolower($nombre)]);
            if ($marca_existente) {
                $_SESSION['error_message'] = "La marca '$nombre' ya existe";
            } else {
                $nueva_marca = [
                    'estado' => 1,
                    'nombre' => $nombre,
                    'nombre_clave' => strtolower($nombre),
                    'categoria' => $categoria,
                    'categoria_clave' => strtolower($categoria),
                    'imagen' => '/img/no_image.png',
                    'descripcion' => $descripcion,
                    'descripcion_larga' => $descripcion,
                    'fecha_publicacion' => date('d-m-Y H:i', strtotime('now')),
                    'usuario_creador' => $_SESSION["user_id"],
                    'url' => $url,
                    'url_register' => $url_register,
                    'aviso' => 'Marca creada por administrador'
                ];
                
                $result = $collection_marcas->insertOne($nueva_marca);
                if ($result->getInsertedId()) {
                    $_SESSION['success_message'] = "Marca '$nombre' creada correctamente";
                } else {
                    $_SESSION['error_message'] = "Error al crear la marca";
                }
            }
            break;
            
        case 'update_marca':
            $marca_id = $_POST['marca_id'];
            $nombre = trim($_POST['nombre']);
            $categoria = $_POST['categoria'];
            $descripcion = trim($_POST['descripcion']);
            $url = trim($_POST['url']);
            $url_register = trim($_POST['url_register']);
            $imagen = trim($_POST['imagen']);
            
            // Primero obtener la marca actual para preservar el nombre_clave original
            $marca_actual = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_id)]);
            $nombre_clave_original = $marca_actual['nombre_clave'] ?? strtolower($nombre);
            
            $update_data = [
                'nombre' => $nombre,
                'categoria' => $categoria,
                'categoria_clave' => strtolower($categoria),
                'descripcion' => $descripcion,
                'descripcion_larga' => $descripcion,
                'url' => $url,
                'url_register' => $url_register,
                'imagen' => $imagen
            ];
            
            // Solo actualizar nombre_clave si el nombre realmente cambió
            if ($marca_actual && $marca_actual['nombre'] !== $nombre) {
                $nuevo_nombre_clave = strtolower($nombre);
                $update_data['nombre_clave'] = $nuevo_nombre_clave;
                
                // Actualizar todos los códigos asociados a esta marca
                $collection_codigos = getCollectionCodigos();
                $collection_codigos->updateMany(
                    ['marca' => $nombre_clave_original],
                    ['$set' => ['marca' => $nuevo_nombre_clave]]
                );
            }
            
            // CORRECCIÓN ADICIONAL: Buscar y corregir códigos con inconsistencias de mayúsculas/minúsculas
            $collection_codigos = getCollectionCodigos();
            
            // Buscar códigos que usen el nombre actual en mayúsculas/minúsculas mixtas
            $codigos_inconsistentes = $collection_codigos->find([
                '$or' => [
                    ['marca' => $marca_actual['nombre']], // Nombre exacto
                    ['marca' => strtoupper($marca_actual['nombre'])], // Todo mayúsculas
                    ['marca' => strtolower($marca_actual['nombre'])], // Todo minúsculas
                    ['marca' => ucfirst(strtolower($marca_actual['nombre']))], // Primera mayúscula
                ]
            ])->toArray();
            
            // Corregir códigos inconsistentes
            foreach ($codigos_inconsistentes as $codigo) {
                if ($codigo['marca'] !== $nombre_clave_original) {
                    $collection_codigos->updateOne(
                        ['_id' => $codigo['_id']],
                        ['$set' => ['marca' => $nombre_clave_original]]
                    );
                }
            }
            
            $result = $collection_marcas->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($marca_id)],
                ['$set' => $update_data]
            );
            
            if ($result->getModifiedCount() > 0) {
                $_SESSION['success_message'] = "Marca actualizada correctamente";
            } else {
                $_SESSION['error_message'] = "No se realizaron cambios en la marca";
            }
            break;
            
        case 'toggle_estado':
            $marca_id = $_POST['marca_id'];
            $nuevo_estado = $_POST['nuevo_estado'];
            
            $collection_marcas->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($marca_id)],
                ['$set' => ['estado' => (int)$nuevo_estado]]
            );
            
            $_SESSION['success_message'] = "Estado de la marca actualizado";
            break;
            
        case 'delete_marca':
            $marca_id = $_POST['marca_id'];
            
            // Verificar si tiene códigos asociados
            $codigos_count = $collection_codigos->countDocuments(['marca' => ['$regex' => '^' . preg_quote($marca_id, '/') . '$', '$options' => 'i']]);
            
            if ($codigos_count > 0) {
                $_SESSION['error_message'] = "No se puede eliminar la marca porque tiene $codigos_count códigos asociados";
            } else {
                $result = $collection_marcas->deleteOne(['_id' => new MongoDB\BSON\ObjectId($marca_id)]);
                if ($result->getDeletedCount() > 0) {
                    $_SESSION['success_message'] = "Marca eliminada correctamente";
                } else {
                    $_SESSION['error_message'] = "Error al eliminar la marca";
                }
            }
            break;
            
        case 'fusionar_marcas':
            $marca_origen_id = $_POST['marca_origen_id'];
            $marca_destino_id = $_POST['marca_destino_id'];
            
            // Obtener información de las marcas
            $marca_origen = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_origen_id)]);
            $marca_destino = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_destino_id)]);
            
            if (!$marca_origen || !$marca_destino) {
                $_SESSION['error_message'] = "Una o ambas marcas no existen";
            } else {
                // Actualizar códigos para usar la marca destino
                $result_codigos = $collection_codigos->updateMany(
                    ['marca' => $marca_origen['nombre_clave']],
                    ['$set' => ['marca' => $marca_destino['nombre_clave']]]
                );
                
                // Eliminar marca origen
                $collection_marcas->deleteOne(['_id' => new MongoDB\BSON\ObjectId($marca_origen_id)]);
                
                $_SESSION['success_message'] = "Marcas fusionadas correctamente. Se actualizaron {$result_codigos->getModifiedCount()} códigos.";
            }
            break;
    }
    
    header('Location: admin_marcas.php');
    exit;
}

// Obtener filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Obtener parámetros de ordenamiento
$sort_field = $_GET['sort'] ?? 'fecha_publicacion';
$sort_direction = $_GET['dir'] ?? 'desc';


// Construir filtros para la consulta
$filtros = [];
if ($filtro_estado !== '') {
    $filtros['estado'] = (int)$filtro_estado;
}
if ($filtro_categoria) {
    $filtros['categoria'] = $filtro_categoria;
}
if ($filtro_busqueda) {
    $filtros['$or'] = [
        ['nombre' => ['$regex' => $filtro_busqueda, '$options' => 'i']],
        ['descripcion' => ['$regex' => $filtro_busqueda, '$options' => 'i']]
    ];
}

// Obtener marcas con paginación
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 20);
$skip = ($page - 1) * $limit;

// Validar límite para evitar valores extremos
$opciones_limit = [10, 20, 50, 100, 200, 500, 1000, 'todos'];
if (!in_array($limit, $opciones_limit) && $limit !== 'todos') {
    $limit = 20;
}

// Si se selecciona "todos", obtener todos los resultados
if ($limit === 'todos') {
    $limit = 999999; // Número muy grande para obtener todos
}

// Construir ordenamiento
$sort_options = [];
$sort_direction_value = ($sort_direction === 'asc') ? 1 : -1;

switch ($sort_field) {
    case 'nombre':
        $sort_options['nombre'] = $sort_direction_value;
        break;
    case 'categoria':
        $sort_options['categoria'] = $sort_direction_value;
        break;
    case 'estado':
        $sort_options['estado'] = $sort_direction_value;
        break;
    case 'fecha_publicacion':
        $sort_options['fecha_publicacion'] = $sort_direction_value;
        break;
    case 'codigos':
        // Para ordenar por número de códigos, usamos _id como fallback
        // En una implementación más avanzada se podría usar aggregation pipeline
        $sort_options['_id'] = $sort_direction_value;
        break;
    default:
        $sort_options['fecha_publicacion'] = -1;
        break;
}

// Si se ordena por códigos, usar aggregation pipeline
if ($sort_field === 'codigos') {
    $pipeline = [];
    
    // Añadir $match solo si hay filtros
    if (!empty($filtros)) {
        $pipeline[] = ['$match' => $filtros];
    }
    
    $pipeline[] = [
        '$lookup' => [
            'from' => 'codigos',
            'localField' => 'nombre_clave',
            'foreignField' => 'marca',
            'as' => 'codigos_relacionados'
        ]
    ];
    
    $pipeline[] = [
        '$addFields' => [
            'codigos_count' => ['$size' => '$codigos_relacionados']
        ]
    ];
    
    // ORDENAMIENTO COMPLETO: Primero ordenar TODOS los resultados
    $pipeline[] = ['$sort' => ['codigos_count' => $sort_direction_value]];
    
    // Luego aplicar paginación
    $pipeline[] = ['$skip' => $skip];
    $pipeline[] = ['$limit' => $limit];
    
    $pipeline[] = [
        '$project' => [
            'codigos_relacionados' => 0,
            'codigos_count' => 0
        ]
    ];
    
    $marcas = $collection_marcas->aggregate($pipeline)->toArray();
} else {
    // Ordenamiento optimizado: usar índices de MongoDB directamente
    $marcas = $collection_marcas->find($filtros, [
        'sort' => $sort_options,
        'skip' => $skip,
        'limit' => $limit
    ])->toArray();
}

$total_marcas = $collection_marcas->countDocuments($filtros);
$total_pages = ceil($total_marcas / $limit);

// Obtener categorías para el filtro
$categorias = $collection_categorias->find([], ['sort' => ['nombre' => 1]])->toArray();

// Obtener estadísticas
$estadisticas = [
    'total' => $collection_marcas->countDocuments([]),
    'activas' => $collection_marcas->countDocuments(['estado' => 1]),
    'inactivas' => $collection_marcas->countDocuments(['estado' => 0]),
    'sin_imagen' => $collection_marcas->countDocuments(['imagen' => '/img/no_image.png'])
];

// Obtener marcas más populares (con más códigos)
$pipeline_populares = [
    ['$group' => [
        '_id' => '$marca',
        'total_codigos' => ['$sum' => 1]
    ]],
    ['$sort' => ['total_codigos' => -1]],
    ['$limit' => 10]
];
$marcas_populares = $collection_codigos->aggregate($pipeline_populares)->toArray();

$title = "Gestión de Marcas - Panel de Administración";
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
        .marca-imagen {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        /* Estilos para el enlace de marca */
        .marca-link {
            color: #333 !important;
            transition: all 0.3s ease;
        }
        
        .marca-link:hover {
            color: #FF6B35 !important;
            text-decoration: none !important;
            transform: translateX(2px);
        }
        
        .marca-link i {
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .marca-link:hover i {
            opacity: 1;
        }
        
        /* Estilos para encabezados ordenables */
        .sortable-header {
            color: #333 !important;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .sortable-header:hover {
            color: #FF6B35 !important;
            text-decoration: none !important;
        }
        
        .sortable-header i {
            font-size: 0.8em;
        }
        
        .sortable-header:hover i {
            color: #FF6B35 !important;
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
                        <a class="nav-link active" href="admin_marcas.php">
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
                        <h5 class="mb-0">Gestión de Marcas</h5>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalCrearMarca">
                                <i class="fas fa-plus me-1"></i>Nueva Marca
                            </button>
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
                                    <h3 class="text-primary"><?php echo number_format($estadisticas['total']); ?></h3>
                                    <p class="text-muted mb-0">Total Marcas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo number_format($estadisticas['activas']); ?></h3>
                                    <p class="text-muted mb-0">Activas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-danger"><?php echo number_format($estadisticas['inactivas']); ?></h3>
                                    <p class="text-muted mb-0">Inactivas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <h3 class="text-warning"><?php echo number_format($estadisticas['sin_imagen']); ?></h3>
                                    <p class="text-muted mb-0">Sin Imagen</p>
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
                                        <option value="">Todas</option>
                                        <option value="1" <?php echo $filtro_estado === '1' ? 'selected' : ''; ?>>Activa</option>
                                        <option value="0" <?php echo $filtro_estado === '0' ? 'selected' : ''; ?>>Inactiva</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Categoría</label>
                                    <select name="categoria" class="form-select">
                                        <option value="">Todas</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo htmlspecialchars($categoria['nombre']); ?>" 
                                                <?php echo $filtro_categoria === $categoria['nombre'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Buscar</label>
                                    <input type="text" name="busqueda" class="form-control" 
                                           placeholder="Nombre o descripción" value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Mostrar</label>
                                    <select name="limit" class="form-select">
                                        <option value="10" <?php echo $limit === 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="20" <?php echo $limit === 20 ? 'selected' : ''; ?>>20</option>
                                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                                        <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200</option>
                                        <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500</option>
                                        <option value="1000" <?php echo $limit === 1000 ? 'selected' : ''; ?>>1000</option>
                                        <option value="todos" <?php echo $limit === 'todos' || $limit >= 999999 ? 'selected' : ''; ?>>Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
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

                    <!-- Tabla de marcas -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Lista de Marcas</h5>
                                <?php if ($sort_field && $sort_field !== 'fecha_publicacion'): ?>
                                    <small class="text-muted">
                                        Ordenado por: <strong><?php echo ucfirst($sort_field); ?></strong> 
                                        (<?php echo $sort_direction === 'asc' ? 'Ascendente' : 'Descendente'; ?>)
                                    </small>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-primary"><?php echo number_format($total_marcas); ?> marcas</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Imagen</th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'nombre', 'dir' => ($sort_field === 'nombre' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Nombre
                                                    <?php if ($sort_field === 'nombre'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'categoria', 'dir' => ($sort_field === 'categoria' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Categoría
                                                    <?php if ($sort_field === 'categoria'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'estado', 'dir' => ($sort_field === 'estado' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Estado
                                                    <?php if ($sort_field === 'estado'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'codigos', 'dir' => ($sort_field === 'codigos' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Códigos
                                                    <?php if ($sort_field === 'codigos'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'fecha_publicacion', 'dir' => ($sort_field === 'fecha_publicacion' && $sort_direction === 'asc') ? 'desc' : 'asc'])); ?>" 
                                                   class="sortable-header text-decoration-none">
                                                    Fecha
                                                    <?php if ($sort_field === 'fecha_publicacion'): ?>
                                                        <i class="fas fa-sort-<?php echo $sort_direction === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($marcas as $marca): 
                                            // Contar códigos de esta marca
                                            $codigos_count = $collection_codigos->countDocuments(['marca' => $marca['nombre_clave']]);
                                        ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($marca['imagen'] ?? '/img/no_image.png'); ?>" 
                                                     class="marca-imagen" 
                                                     onerror="this.src='/img/no_image.png'">
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>
                                                        <a href="https://www.codigoamigo.com/de-<?php echo urlencode($marca['nombre_clave']); ?>" 
                                                           target="_blank" 
                                                           class="text-decoration-none marca-link"
                                                           title="Ver página de la marca: <?php echo htmlspecialchars($marca['nombre']); ?>">
                                                            <?php echo htmlspecialchars($marca['nombre']); ?>
                                                            <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                                        </a>
                                                    </strong>
                                                    <?php if (isset($marca['descripcion']) && $marca['descripcion']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($marca['descripcion'], 0, 50)) . '...'; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($marca['categoria'] ?? 'Sin categoría'); ?></span>
                                            </td>
                                            <td>
                                                <?php if (($marca['estado'] ?? 0) == 1): ?>
                                                    <span class="badge bg-success">Activa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $codigos_count; ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    if (isset($marca['fecha_publicacion'])) {
                                                        echo date('d/m/Y', strtotime($marca['fecha_publicacion']));
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#modalEditarMarca" 
                                                            data-marca-id="<?php echo $marca['_id']; ?>"
                                                            data-marca-nombre="<?php echo htmlspecialchars($marca['nombre']); ?>"
                                                            data-marca-categoria="<?php echo htmlspecialchars($marca['categoria'] ?? ''); ?>"
                                                            data-marca-descripcion="<?php echo htmlspecialchars($marca['descripcion'] ?? ''); ?>"
                                                            data-marca-url="<?php echo htmlspecialchars($marca['url'] ?? ''); ?>"
                                                            data-marca-url-register="<?php echo htmlspecialchars($marca['url_register'] ?? ''); ?>"
                                                            data-marca-imagen="<?php echo htmlspecialchars($marca['imagen'] ?? ''); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick="toggleEstado('<?php echo $marca['_id']; ?>', <?php echo $marca['estado'] ?? 0; ?>)">
                                                        <i class="fas fa-toggle-<?php echo ($marca['estado'] ?? 0) == 1 ? 'on' : 'off'; ?>"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" data-bs-target="#modalFusionar" 
                                                            data-marca-id="<?php echo $marca['_id']; ?>"
                                                            data-marca-nombre="<?php echo htmlspecialchars($marca['nombre']); ?>">
                                                        <i class="fas fa-compress-arrows-alt"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarMarca('<?php echo $marca['_id']; ?>', '<?php echo htmlspecialchars($marca['nombre']); ?>', <?php echo $codigos_count; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginación de marcas">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&estado=<?php echo $filtro_estado; ?>&categoria=<?php echo urlencode($filtro_categoria); ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Marcas más populares -->
                    <?php if (!empty($marcas_populares)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Marcas Más Populares</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($marcas_populares as $marca_popular): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="d-flex align-items-center p-2 border rounded">
                                        <div class="me-3">
                                            <span class="badge bg-primary fs-6"><?php echo $marca_popular['total_codigos']; ?></span>
                                        </div>
                                        <div>
                                            <strong><?php echo ucfirst($marca_popular['_id']); ?></strong>
                                            <br><small class="text-muted">códigos</small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear marca -->
    <div class="modal fade" id="modalCrearMarca" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nueva Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_marca">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de la Marca *</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria" class="form-select" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria['nombre']); ?>">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL Principal</label>
                                <input type="url" class="form-control" name="url" placeholder="https://...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL de Registro</label>
                                <input type="url" class="form-control" name="url_register" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Marca</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar marca -->
    <div class="modal fade" id="modalEditarMarca" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_marca">
                        <input type="hidden" name="marca_id" id="edit_marca_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de la Marca *</label>
                                <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria" class="form-select" id="edit_categoria" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria['nombre']); ?>">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL Principal</label>
                                <input type="url" class="form-control" name="url" id="edit_url" placeholder="https://...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL de Registro</label>
                                <input type="url" class="form-control" name="url_register" id="edit_url_register" placeholder="https://...">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL de Imagen</label>
                            <input type="url" class="form-control" name="imagen" id="edit_imagen" placeholder="https://...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Marca</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para fusionar marcas -->
    <div class="modal fade" id="modalFusionar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fusionar Marcas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="fusionar_marcas">
                        <input type="hidden" name="marca_origen_id" id="fusion_origen_id">
                        <div class="mb-3">
                            <label class="form-label">Marca Origen (se eliminará)</label>
                            <input type="text" class="form-control" id="fusion_origen_nombre" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Marca Destino (recibirá los códigos)</label>
                            <select name="marca_destino_id" class="form-select" required>
                                <option value="">Seleccionar marca destino</option>
                                <?php 
                                $todas_marcas = $collection_marcas->find([], ['sort' => ['nombre' => 1]])->toArray();
                                foreach ($todas_marcas as $marca): 
                                ?>
                                <option value="<?php echo $marca['_id']; ?>">
                                    <?php echo htmlspecialchars($marca['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Advertencia:</strong> Esta acción moverá todos los códigos de la marca origen a la marca destino y eliminará la marca origen. Esta acción no se puede deshacer.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Fusionar Marcas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Modal de editar marca
        document.getElementById('modalEditarMarca').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('edit_marca_id').value = button.getAttribute('data-marca-id');
            document.getElementById('edit_nombre').value = button.getAttribute('data-marca-nombre');
            document.getElementById('edit_categoria').value = button.getAttribute('data-marca-categoria');
            document.getElementById('edit_descripcion').value = button.getAttribute('data-marca-descripcion');
            document.getElementById('edit_url').value = button.getAttribute('data-marca-url');
            document.getElementById('edit_url_register').value = button.getAttribute('data-marca-url-register');
            document.getElementById('edit_imagen').value = button.getAttribute('data-marca-imagen');
        });

        // Modal de fusionar
        document.getElementById('modalFusionar').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('fusion_origen_id').value = button.getAttribute('data-marca-id');
            document.getElementById('fusion_origen_nombre').value = button.getAttribute('data-marca-nombre');
        });

        // Toggle estado
        function toggleEstado(marcaId, currentEstado) {
            if (confirm('¿Estás seguro de cambiar el estado de esta marca?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_estado">
                    <input type="hidden" name="marca_id" value="${marcaId}">
                    <input type="hidden" name="nuevo_estado" value="${currentEstado == 1 ? 0 : 1}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Eliminar marca
        function eliminarMarca(marcaId, marcaNombre, codigosCount) {
            if (codigosCount > 0) {
                alert(`No se puede eliminar la marca "${marcaNombre}" porque tiene ${codigosCount} códigos asociados.`);
                return;
            }
            
            if (confirm(`¿Estás seguro de eliminar la marca "${marcaNombre}"? Esta acción no se puede deshacer.`)) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_marca">
                    <input type="hidden" name="marca_id" value="${marcaId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
    
<?php get_footer(); ?>
</body>
</html>


