<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: https://www.codigoamigo.com/mis-anuncios");
    exit;
}

$user_id = $_SESSION["user_id"];

// Incluir archivos necesarios (incluye autoloader de Composer)
require_once '../inc/includes.php';
$mongo = createConnection();
$collection_usuarios = getCollectionUsuarios();
$collection_transacciones = $mongo->selectCollection('transacciones');

// Obtener datos del usuario
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
$saldo_actual = $usuario['saldo'] ?? 0;

// Obtener filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';
$filtro_cantidad_min = $_GET['cantidad_min'] ?? '';
$filtro_cantidad_max = $_GET['cantidad_max'] ?? '';

// Construir filtros para la consulta
$filtros = ['usuario_id' => $user_id];

if ($filtro_tipo !== '') {
    $filtros['tipo'] = $filtro_tipo;
}

if ($filtro_fecha_desde !== '') {
    $fecha_desde = new MongoDB\BSON\UTCDateTime(strtotime($filtro_fecha_desde) * 1000);
    $filtros['fecha']['$gte'] = $fecha_desde;
}

if ($filtro_fecha_hasta !== '') {
    $fecha_hasta = new MongoDB\BSON\UTCDateTime(strtotime($filtro_fecha_hasta . ' 23:59:59') * 1000);
    $filtros['fecha']['$lte'] = $fecha_hasta;
}

if ($filtro_cantidad_min !== '') {
    $filtros['cantidad']['$gte'] = (float)$filtro_cantidad_min;
}

if ($filtro_cantidad_max !== '') {
    $filtros['cantidad']['$lte'] = (float)$filtro_cantidad_max;
}

// Obtener transacciones con paginación
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$skip = ($page - 1) * $limit;

$transacciones = $collection_transacciones->find($filtros, [
    'sort' => ['fecha' => -1],
    'skip' => $skip,
    'limit' => $limit
])->toArray();

$total_transacciones = $collection_transacciones->countDocuments($filtros);
$total_pages = ceil($total_transacciones / $limit);

// Calcular estadísticas
$estadisticas = [
    'total_recargas' => $collection_transacciones->countDocuments([
        'usuario_id' => $user_id,
        'tipo' => ['$in' => ['recarga', 'recarga_admin']],
        'cantidad' => ['$gt' => 0]
    ]),
    'total_gastos' => $collection_transacciones->countDocuments([
        'usuario_id' => $user_id,
        'tipo' => ['$in' => ['destacado', 'destacado_splash', 'patrocinado']],
        'cantidad' => ['$lt' => 0]
    ]),
    'ingresos_totales' => 0,
    'gastos_totales' => 0
];

// Calcular ingresos totales
$pipeline_ingresos = [
    ['$match' => [
        'usuario_id' => $user_id,
        'tipo' => ['$in' => ['recarga', 'recarga_admin']],
        'cantidad' => ['$gt' => 0]
    ]],
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$cantidad']]]
];
$resultado_ingresos = $collection_transacciones->aggregate($pipeline_ingresos)->toArray();
$estadisticas['ingresos_totales'] = $resultado_ingresos[0]['total'] ?? 0;

// Calcular gastos totales
$pipeline_gastos = [
    ['$match' => [
        'usuario_id' => $user_id,
        'tipo' => ['$in' => ['destacado', 'destacado_splash', 'patrocinado']],
        'cantidad' => ['$lt' => 0]
    ]],
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$cantidad']]]
];
$resultado_gastos = $collection_transacciones->aggregate($pipeline_gastos)->toArray();
$estadisticas['gastos_totales'] = abs($resultado_gastos[0]['total'] ?? 0);

// Función para formatear el tipo de transacción
function formatearTipoTransaccion($tipo) {
    $tipos = [
        'recarga' => 'Recarga de Saldo',
        'recarga_admin' => 'Recarga Administrativa',
        'destacado' => 'Destacado de Código',
        'destacado_splash' => 'Destacado Masivo',
        'patrocinado' => 'Patrocinio',
        'ajuste_admin' => 'Ajuste Administrativo'
    ];
    return $tipos[$tipo] ?? ucfirst($tipo);
}

// Función para obtener el icono del tipo
function obtenerIconoTipo($tipo) {
    $iconos = [
        'recarga' => 'fas fa-plus-circle text-success',
        'recarga_admin' => 'fas fa-gift text-success',
        'destacado' => 'fas fa-star text-warning',
        'destacado_splash' => 'fas fa-rocket text-warning',
        'patrocinado' => 'fas fa-bullhorn text-info',
        'ajuste_admin' => 'fas fa-cog text-secondary'
    ];
    return $iconos[$tipo] ?? 'fas fa-circle text-muted';
}

$title = "Historial de Recargas - CodigoAmigo";
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
        .historial-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .historial-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .historial-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .historial-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }
        
        .historial-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 10px 0 0 0;
        }
        
        .saldo-actual {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
        }
        
        .saldo-actual h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .estadisticas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .estadistica-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        
        .estadistica-card h4 {
            color: #667eea;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .estadistica-card p {
            color: #6c757d;
            margin: 0;
        }
        
        .filtros-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        .transaccion-item {
            border-bottom: 1px solid #e9ecef;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .transaccion-item:hover {
            background: #f8f9fa;
        }
        
        .transaccion-item:last-child {
            border-bottom: none;
        }
        
        .transaccion-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .transaccion-info h5 {
            margin: 0 0 5px 0;
            font-weight: 600;
        }
        
        .transaccion-info p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .transaccion-cantidad {
            text-align: right;
        }
        
        .transaccion-cantidad h4 {
            margin: 0;
            font-weight: 700;
        }
        
        .cantidad-positiva {
            color: #28a745;
        }
        
        .cantidad-negativa {
            color: #dc3545;
        }
        
        .pagination {
            justify-content: center;
            margin: 30px 0;
        }
        
        .btn-filtrar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-filtrar:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
            color: white;
        }
        
        .no-transacciones {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-transacciones i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .historial-header h1 {
                font-size: 2rem;
            }
            
            .estadisticas-grid {
                grid-template-columns: 1fr;
            }
            
            .transaccion-item {
                padding: 15px;
            }
            
            .transaccion-cantidad {
                text-align: left;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="historial-container">
        <div class="container">
            <div class="historial-card">
                <!-- Header -->
                <div class="historial-header">
                    <h1><i class="fas fa-history me-3"></i>Historial de Recargas</h1>
                    <p>Extracto completo de tu cuenta de saldo</p>
                </div>
                
                <div class="p-4">
                    <!-- Saldo Actual -->
                    <div class="saldo-actual">
                        <h3><i class="fas fa-wallet me-2"></i>Saldo Actual</h3>
                        <h2><?php echo number_format($saldo_actual, 2); ?>€</h2>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="estadisticas-grid">
                        <div class="estadistica-card">
                            <h4><?php echo $estadisticas['total_recargas']; ?></h4>
                            <p>Recargas Realizadas</p>
                        </div>
                        <div class="estadistica-card">
                            <h4><?php echo $estadisticas['total_gastos']; ?></h4>
                            <p>Gastos Realizados</p>
                        </div>
                        <div class="estadistica-card">
                            <h4><?php echo number_format($estadisticas['ingresos_totales'], 2); ?>€</h4>
                            <p>Total Ingresado</p>
                        </div>
                        <div class="estadistica-card">
                            <h4><?php echo number_format($estadisticas['gastos_totales'], 2); ?>€</h4>
                            <p>Total Gastado</p>
                        </div>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="filtros-section">
                        <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtrar Transacciones</h5>
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tipo de Transacción</label>
                                <select name="tipo" class="form-select">
                                    <option value="">Todos los tipos</option>
                                    <option value="recarga" <?php echo $filtro_tipo === 'recarga' ? 'selected' : ''; ?>>Recarga de Saldo</option>
                                    <option value="recarga_admin" <?php echo $filtro_tipo === 'recarga_admin' ? 'selected' : ''; ?>>Recarga Administrativa</option>
                                    <option value="destacado" <?php echo $filtro_tipo === 'destacado' ? 'selected' : ''; ?>>Destacado de Código</option>
                                    <option value="destacado_splash" <?php echo $filtro_tipo === 'destacado_splash' ? 'selected' : ''; ?>>Destacado Masivo</option>
                                    <option value="patrocinado" <?php echo $filtro_tipo === 'patrocinado' ? 'selected' : ''; ?>>Patrocinio</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Desde</label>
                                <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtro_fecha_desde; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtro_fecha_hasta; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Cantidad Mín.</label>
                                <input type="number" step="0.01" name="cantidad_min" class="form-control" value="<?php echo $filtro_cantidad_min; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Cantidad Máx.</label>
                                <input type="number" step="0.01" name="cantidad_max" class="form-control" value="<?php echo $filtro_cantidad_max; ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-filtrar w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Lista de Transacciones -->
                    <?php if (empty($transacciones)): ?>
                        <div class="no-transacciones">
                            <i class="fas fa-receipt"></i>
                            <h4>No hay transacciones</h4>
                            <p>No se encontraron transacciones con los filtros aplicados.</p>
                        </div>
                    <?php else: ?>
                        <div class="transacciones-list">
                            <?php foreach ($transacciones as $transaccion): ?>
                                <div class="transaccion-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="transaccion-icon <?php echo obtenerIconoTipo($transaccion['tipo']); ?>">
                                                <i class="<?php echo explode(' ', obtenerIconoTipo($transaccion['tipo']))[1]; ?>"></i>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="transaccion-info">
                                                <h5><?php echo formatearTipoTransaccion($transaccion['tipo']); ?></h5>
                                                <p><?php echo $transaccion['descripcion']; ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('d/m/Y H:i', $transaccion['fecha']->toDateTime()->getTimestamp()); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="transaccion-cantidad">
                                                <h4 class="<?php echo $transaccion['cantidad'] > 0 ? 'cantidad-positiva' : 'cantidad-negativa'; ?>">
                                                    <?php echo ($transaccion['cantidad'] > 0 ? '+' : '') . number_format($transaccion['cantidad'], 2); ?>€
                                                </h4>
                                                <small class="text-muted">
                                                    <?php echo $transaccion['estado'] === 'completada' ? 'Completada' : ucfirst($transaccion['estado']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginación de transacciones">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i> Anterior
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                Siguiente <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Botón de Volver -->
                    <div class="text-center mt-4">
                        <a href="/mis-anuncios" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Volver a Mis Anuncios
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
<?php get_footer(); ?>
</body>
</html>
