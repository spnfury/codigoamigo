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

// Obtener estadísticas generales
$collection_usuarios = getCollectionUsuarios();
$collection_marcas = getCollectionMarcas();
$collection_codigos = getCollectionCodigos();
$collection_transacciones = getCollectionTransacciones();

// Estadísticas del dashboard
$total_usuarios = $collection_usuarios->countDocuments([]);
$total_marcas = $collection_marcas->countDocuments([]);
$total_codigos = $collection_codigos->countDocuments([]);
$total_codigos_activos = $collection_codigos->countDocuments(['estado' => 0]);
$total_codigos_destacados = $collection_codigos->countDocuments(['destacado' => 1]);
$total_codigos_destacados_premium = $collection_codigos->countDocuments(['destacado_social' => 1]);

// Fechas para estadísticas
$fecha_hoy = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
$fecha_30_dias = new MongoDB\BSON\UTCDateTime((time() - 30*24*60*60) * 1000);

// Usuarios nuevos hoy
$usuarios_nuevos_hoy = $collection_usuarios->countDocuments([
    'fecha_registro' => ['$gte' => $fecha_hoy]
]);

// Usuarios nuevos en los últimos 30 días
$usuarios_nuevos_30d = $collection_usuarios->countDocuments([
    'fecha_registro' => ['$gte' => $fecha_30_dias]
]);

// Códigos nuevos hoy (usando timestamp del ObjectId)
$timestamp_hoy = strtotime('today');
$objectId_hoy = new MongoDB\BSON\ObjectId(sprintf('%08x%s', $timestamp_hoy, str_repeat('0', 16)));
$codigos_nuevos_hoy = $collection_codigos->countDocuments([
    '_id' => ['$gte' => $objectId_hoy]
]);

// Códigos nuevos en los últimos 30 días (usando timestamp del ObjectId)
$timestamp_30_dias = time() - 30*24*60*60;
$objectId_30_dias = new MongoDB\BSON\ObjectId(sprintf('%08x%s', $timestamp_30_dias, str_repeat('0', 16)));
$codigos_nuevos_30d = $collection_codigos->countDocuments([
    '_id' => ['$gte' => $objectId_30_dias]
]);

// Códigos actualizados hoy (usando fecha_modificacion)
try {
    $codigos_actualizados_hoy = $collection_codigos->countDocuments([
        'fecha_modificacion' => ['$gte' => date('Y-m-d', strtotime('today'))]
    ]);
} catch (Exception $e) {
    error_log("Error en conteo de actualizados hoy: " . $e->getMessage());
    $codigos_actualizados_hoy = 0;
}

// Códigos actualizados en los últimos 30 días
try {
    $codigos_actualizados_30d = $collection_codigos->countDocuments([
        'fecha_modificacion' => ['$gte' => date('Y-m-d', strtotime('-30 days'))]
    ]);
} catch (Exception $e) {
    error_log("Error en conteo de actualizados 30d: " . $e->getMessage());
    $codigos_actualizados_30d = 0;
}

// Estadísticas de códigos destacados de hoy
try {
    $codigos_destacados_hoy = $collection_codigos->countDocuments([
        'destacado' => 1,
        'fecha_modificacion' => ['$gte' => date('Y-m-d', strtotime('today'))]
    ]);

    $codigos_destacados_premium_hoy = $collection_codigos->countDocuments([
        'destacado_social' => 1,
        'fecha_modificacion' => ['$gte' => date('Y-m-d', strtotime('today'))]
    ]);
} catch (Exception $e) {
    error_log("Error en conteo de destacados hoy: " . $e->getMessage());
    $codigos_destacados_hoy = 0;
    $codigos_destacados_premium_hoy = 0;
}

// Datos para gráfica de códigos destacados (últimos 30 días)
try {
    // Usar fecha de creación (_id) para agrupar por días
    $pipeline_destacados_tiempo = [
        ['$match' => [
            'destacado' => ['$in' => [1, 2]]
        ]],
        ['$group' => [
            '_id' => [
                'year' => ['$year' => '$_id'],
                'month' => ['$month' => '$_id'],
                'day' => ['$dayOfMonth' => '$_id']
            ],
            'total_destacados' => ['$sum' => 1],
            'destacados_normal' => ['$sum' => ['$cond' => [['$eq' => ['$destacado', 1]], 1, 0]]],
            'destacados_premium' => ['$sum' => ['$cond' => [['$eq' => ['$destacado_social', 1]], 1, 0]]]
        ]],
        ['$sort' => ['_id' => 1]],
        ['$limit' => 30]
    ];
    $datos_grafica_destacados = $collection_codigos->aggregate($pipeline_destacados_tiempo)->toArray();
} catch (Exception $e) {
    error_log("Error en agregación de destacados: " . $e->getMessage());
    $datos_grafica_destacados = [];
}

// Códigos destacados recientes (últimos 10)
try {
    $codigos_destacados_recientes = $collection_codigos->find(
        ['destacado' => ['$in' => [1, 2]]],
        [
            'sort' => ['fecha_modificacion' => -1],
            'limit' => 10,
            'projection' => [
                'codigo' => 1,
                'marca' => 1,
                'destacado' => 1,
                'destacado_social' => 1,
                'fecha_modificacion' => 1,
                'totalclicks' => 1,
                'total_impressions' => 1
            ]
        ]
    )->toArray();
} catch (Exception $e) {
    error_log("Error en consulta de destacados recientes: " . $e->getMessage());
    $codigos_destacados_recientes = [];
}

// Estadísticas de impresiones y clicks
try {
    $pipeline_impressions = [
        ['$group' => [
            '_id' => null,
            'total_impressions' => ['$sum' => ['$ifNull' => ['$total_impressions', 0]]],
            'total_clicks' => ['$sum' => ['$ifNull' => ['$totalclicks', 0]]]
        ]]
    ];
    $resultado_impressions = $collection_codigos->aggregate($pipeline_impressions)->toArray();
    $total_impressions_global = $resultado_impressions[0]['total_impressions'] ?? 0;
    $total_clicks_global = $resultado_impressions[0]['total_clicks'] ?? 0;
    $conversion_rate_global = $total_impressions_global > 0 ? round(($total_clicks_global / $total_impressions_global) * 100, 2) : 0;
} catch (Exception $e) {
    error_log("Error en agregación de impresiones: " . $e->getMessage());
    $total_impressions_global = 0;
    $total_clicks_global = 0;
    $conversion_rate_global = 0;
}

// Saldo total de todos los usuarios
$pipeline_saldo = [
    ['$group' => [
        '_id' => null,
        'saldo_total' => ['$sum' => '$saldo']
    ]]
];
$resultado_saldo = $collection_usuarios->aggregate($pipeline_saldo);
$saldo_total = 0;
foreach ($resultado_saldo as $doc) {
    $saldo_total = $doc['saldo_total'] ?? 0;
}

// Función helper para convertir objetos MongoDB a formato JSON-friendly
function mongoToArray($data) {
    if (is_array($data)) {
        return array_map('mongoToArray', $data);
    } elseif (is_object($data)) {
        if ($data instanceof MongoDB\BSON\ObjectId) {
            return (string)$data;
        } elseif ($data instanceof MongoDB\BSON\UTCDateTime) {
            return [
                '$date' => $data->toDateTime()->format('c')
            ];
        } else {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = mongoToArray($value);
            }
            return $result;
        }
    }
    return $data;
}

// Transacciones recientes (obtener todos los campos para el detalle)
try {
    $transacciones_recientes = $collection_transacciones->find(
        [],
        [
            'sort' => ['fecha' => -1],
            'limit' => 10
        ]
    )->toArray();
    
    // Obtener información del usuario para cada transacción
    foreach ($transacciones_recientes as &$transaccion) {
        if (isset($transaccion['usuario_id']) && !empty($transaccion['usuario_id'])) {
            try {
                $usuario = getObjectUser('_id', new MongoDB\BSON\ObjectId($transaccion['usuario_id']));
                if ($usuario) {
                    $transaccion['usuario_nombre'] = $usuario['username'] ?? 'Usuario sin nombre';
                    $transaccion['usuario_img'] = $usuario['img'] ?? '';
                } else {
                    $transaccion['usuario_nombre'] = 'Usuario no encontrado';
                    $transaccion['usuario_img'] = '';
                }
            } catch (Exception $e) {
                error_log("Error al obtener usuario para transacción: " . $e->getMessage());
                $transaccion['usuario_nombre'] = 'Error al cargar';
                $transaccion['usuario_img'] = '';
            }
        } else {
            $transaccion['usuario_nombre'] = 'Sin usuario';
            $transaccion['usuario_img'] = '';
        }
    }
    unset($transaccion); // Liberar referencia
} catch (Exception $e) {
    error_log("Error en consulta de transacciones recientes: " . $e->getMessage());
    $transacciones_recientes = [];
}

// Usuarios más activos (con más códigos)
$pipeline_usuarios_activos = [
    ['$group' => [
        '_id' => '$id_usuario',
        'total_codigos' => ['$sum' => 1]
    ]],
    ['$sort' => ['total_codigos' => -1]],
    ['$limit' => 5]
];
$usuarios_activos = $collection_codigos->aggregate($pipeline_usuarios_activos)->toArray();

// Marcas más populares
$pipeline_marcas_populares = [
    ['$group' => [
        '_id' => '$marca',
        'total_codigos' => ['$sum' => 1]
    ]],
    ['$sort' => ['total_codigos' => -1]],
    ['$limit' => 10]
];
$marcas_populares = $collection_codigos->aggregate($pipeline_marcas_populares)->toArray();

$title = "Panel de Administración - Dashboard";
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
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card-stat:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .transaccion-row {
            transition: background-color 0.2s;
        }
        .transaccion-row:hover {
            background-color: #f0f0f0 !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo get_admin_sidebar_menu('admin_dashboard.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-admin">
                    <div class="container-fluid">
                        <h5 class="mb-0">Dashboard de Administración</h5>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">Bienvenido, <?php echo $_SESSION["username"] ?? 'Admin'; ?></span>
                            <a href="https://www.codigoamigo.com/logout" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Salir
                            </a>
                        </div>
                    </div>
                </nav>

                <!-- Dashboard Content -->
                <div class="p-4">
                    <!-- Estadísticas principales -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-primary fw-bold small">Total Usuarios</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_usuarios); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users stat-icon text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-success fw-bold small">Total Marcas</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_marcas); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tags stat-icon text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-info fw-bold small">Códigos Activos</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_codigos_activos); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-code stat-icon text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-warning fw-bold small">Saldo Total</div>
                                            <div class="h3 mb-0">€<?php echo number_format($saldo_total, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-euro-sign stat-icon text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas adicionales -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-danger fw-bold small">Códigos Destacados</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_codigos_destacados); ?></div>
                                            <small class="text-muted">Premium: <?php echo number_format($total_codigos_destacados_premium); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-star stat-icon text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-info fw-bold small">Total Impresiones</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_impressions_global); ?></div>
                                            <small class="text-muted">Clicks: <?php echo number_format($total_clicks_global); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-eye stat-icon text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-success fw-bold small">Tasa Conversión</div>
                                            <div class="h3 mb-0"><?php echo $conversion_rate_global; ?>%</div>
                                            <small class="text-muted">Clicks/Impresiones</small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line stat-icon text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-warning fw-bold small">Destacados Hoy</div>
                                            <div class="h3 mb-0"><?php echo number_format($codigos_destacados_hoy); ?></div>
                                            <small class="text-muted">Premium hoy: <?php echo number_format($codigos_destacados_premium_hoy); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-star-half-alt stat-icon text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-primary fw-bold small">Usuarios Nuevos Hoy</div>
                                            <div class="h3 mb-0"><?php echo number_format($usuarios_nuevos_hoy); ?></div>
                                            <small class="text-muted">30d: <?php echo number_format($usuarios_nuevos_30d); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-plus stat-icon text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <?php 
                            // Calcular timestamp de hoy para el filtro
                            $timestamp_hoy_link = strtotime('today');
                            $objectId_hoy_link = new MongoDB\BSON\ObjectId(sprintf('%08x%s', $timestamp_hoy_link, str_repeat('0', 16)));
                            $objectId_hoy_link_str = (string)$objectId_hoy_link;
                            ?>
                            <a href="admin_codigos.php?filtro_nuevos_hoy=1" class="text-decoration-none" style="color: inherit;">
                                <div class="card card-stat h-100" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" 
                                     onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" 
                                     onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col">
                                                <div class="text-uppercase text-success fw-bold small">Códigos Nuevos Hoy</div>
                                                <div class="h3 mb-0"><?php echo number_format($codigos_nuevos_hoy); ?></div>
                                                <small class="text-muted">30d: <?php echo number_format($codigos_nuevos_30d); ?></small>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-code-branch stat-icon text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-info fw-bold small">Total Códigos</div>
                                            <div class="h3 mb-0"><?php echo number_format($total_codigos); ?></div>
                                            <small class="text-muted">Activos: <?php echo number_format($total_codigos_activos); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-list stat-icon text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas de actividad -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-secondary fw-bold small">Códigos Actualizados Hoy</div>
                                            <div class="h3 mb-0"><?php echo number_format($codigos_actualizados_hoy); ?></div>
                                            <small class="text-muted">30d: <?php echo number_format($codigos_actualizados_30d); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-edit stat-icon text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card card-stat h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-uppercase text-dark fw-bold small">Actividad Total</div>
                                            <div class="h3 mb-0"><?php echo number_format($codigos_nuevos_hoy + $codigos_actualizados_hoy); ?></div>
                                            <small class="text-muted">Hoy (nuevos + actualizados)</small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line stat-icon text-dark"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido principal -->
                    <div class="row">
                        <!-- Transacciones recientes -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-credit-card me-2"></i>Transacciones Recientes
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm transacciones-table">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Usuario</th>
                                                    <th>Tipo</th>
                                                    <th>Método</th>
                                                    <th>Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transacciones_recientes as $transaccion): ?>
                                                <tr style="cursor: pointer;" 
                                                    class="transaccion-row" 
                                                    data-transaccion-id="<?php echo (string)$transaccion['_id']; ?>"
                                                    data-transaccion-data="<?php echo htmlspecialchars(json_encode(mongoToArray($transaccion), JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">
                                                    <td>
                                                        <?php
                                                        if (isset($transaccion['fecha'])) {
                                                            if (is_string($transaccion['fecha'])) {
                                                                echo date('d/m/Y H:i', strtotime($transaccion['fecha']));
                                                            } elseif (is_object($transaccion['fecha']) && method_exists($transaccion['fecha'], 'toDateTime')) {
                                                                echo date('d/m/Y H:i', $transaccion['fecha']->toDateTime()->getTimestamp());
                                                            } else {
                                                                echo 'N/A';
                                                            }
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="admin_usuario_detalle.php?id=<?php echo htmlspecialchars($transaccion['usuario_id']); ?>" 
                                                           class="text-primary text-decoration-none d-flex align-items-center gap-2" 
                                                           onclick="event.stopPropagation();"
                                                           title="Ver detalle del usuario">
                                                            <?php if (!empty($transaccion['usuario_img'] ?? '')): ?>
                                                                <img src="<?php echo htmlspecialchars($transaccion['usuario_img']); ?>" 
                                                                     alt="<?php echo htmlspecialchars($transaccion['usuario_nombre'] ?? 'Usuario'); ?>"
                                                                     class="rounded-circle" 
                                                                     style="width: 32px; height: 32px; object-fit: cover;"
                                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                                <div class="bg-secondary rounded-circle d-none align-items-center justify-content-center" 
                                                                     style="width: 32px; height: 32px;">
                                                                    <i class="fas fa-user text-white" style="font-size: 14px;"></i>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                                                     style="width: 32px; height: 32px;">
                                                                    <i class="fas fa-user text-white" style="font-size: 14px;"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <span><?php echo htmlspecialchars($transaccion['usuario_nombre'] ?? substr($transaccion['usuario_id'], 0, 8) . '...'); ?></span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo ($transaccion['tipo'] ?? '') == 'recarga' ? 'success' : 'info'; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $transaccion['tipo'] ?? 'transaccion')); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $metodo_pago = $transaccion['metodo_pago'] ?? '';
                                                        if ($metodo_pago == 'tarjeta') {
                                                            echo '<span class="badge bg-primary"><i class="fas fa-credit-card me-1"></i>Tarjeta</span>';
                                                        } elseif ($metodo_pago == 'wallet' || $metodo_pago == 'saldo') {
                                                            echo '<span class="badge bg-secondary"><i class="fas fa-wallet me-1"></i>Saldo</span>';
                                                        } else {
                                                            echo '<span class="badge bg-light text-dark">N/A</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="<?php echo ($transaccion['cantidad'] ?? 0) > 0 ? 'text-success' : 'text-danger'; ?>">
                                                        €<?php echo number_format($transaccion['cantidad'] ?? 0, 2); ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Usuarios más activos -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-trophy me-2"></i>Usuarios Más Activos
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Usuario ID</th>
                                                    <th>Códigos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($usuarios_activos as $usuario): ?>
                                                <tr>
                                                    <td><?php echo substr($usuario['_id'], 0, 8) . '...'; ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $usuario['total_codigos']; ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Marcas más populares -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Marcas Más Populares
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Marca</th>
                                                    <th>Códigos</th>
                                                    <th>Progreso</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $max_codigos = !empty($marcas_populares) ? $marcas_populares[0]['total_codigos'] : 1;
                                                foreach ($marcas_populares as $marca): 
                                                    $porcentaje = ($marca['total_codigos'] / $max_codigos) * 100;
                                                ?>
                                                <tr>
                                                    <td><strong><?php echo ucfirst($marca['_id']); ?></strong></td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo $marca['total_codigos']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="progress" style="height: 8px;">
                                                            <div class="progress-bar bg-success" style="width: <?php echo $porcentaje; ?>%"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfica de códigos destacados por tiempo -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Evolución de Códigos Destacados (30 días)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficaDestacados" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Códigos destacados recientes -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-star me-2"></i>Códigos Destacados Recientes
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Código</th>
                                                    <th>Marca</th>
                                                    <th>Tipo</th>
                                                    <th>Clics</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($codigos_destacados_recientes as $codigo): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars(substr($codigo['codigo'], 0, 20)); ?></code></td>
                                                    <td><strong><?php echo htmlspecialchars($codigo['marca']); ?></strong></td>
                                                    <td>
                                                        <?php if (isset($codigo['destacado_social']) && $codigo['destacado_social'] == 1): ?>
                                                            <span class="badge bg-warning">Premium</span>
                                                        <?php elseif (isset($codigo['destacado']) && $codigo['destacado'] == 1): ?>
                                                            <span class="badge bg-info">Normal</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo number_format($codigo['totalclicks'] ?? 0); ?></td>
                                                    <td><?php echo isset($codigo['fecha_modificacion']) ? date('d/m/Y H:i', strtotime($codigo['fecha_modificacion'])) : 'N/A'; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
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

    <!-- Modal para detalles de transacción -->
    <div class="modal fade" id="modalDetalleTransaccion" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-credit-card me-2"></i>Detalles de Transacción
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detalles-transaccion-content">
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
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar DataTables si es necesario (excluyendo la tabla de transacciones)
            $('.table').not('.transacciones-table').DataTable({
                pageLength: 10,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                }
            });

            // Variable global para almacenar datos de transacción
            var transaccionDataActual = null;

            // Manejar click en filas de transacciones
            $(document).on('click', '.transaccion-row', function(e) {
                e.preventDefault();
                var transaccionDataStr = $(this).attr('data-transaccion-data');
                if (transaccionDataStr) {
                    try {
                        transaccionDataActual = JSON.parse(transaccionDataStr);
                        $('#modalDetalleTransaccion').modal('show');
                    } catch (e) {
                        console.error('Error al parsear datos de transacción:', e);
                        alert('Error al cargar los datos de la transacción');
                    }
                }
            });

            // Modal de detalles de transacción
            $('#modalDetalleTransaccion').on('show.bs.modal', function (event) {
                if (!transaccionDataActual) {
                    $('#detalles-transaccion-content').html('<p class="text-danger">Error: No se pudieron cargar los datos de la transacción.</p>');
                    return;
                }
                
                var transaccionData = transaccionDataActual;
                
                // Función para formatear fecha
                function formatearFecha(fecha) {
                    if (!fecha) return 'N/A';
                    try {
                        var dateObj;
                        if (typeof fecha === 'string') {
                            dateObj = new Date(fecha);
                        } else if (fecha.$date) {
                            dateObj = new Date(fecha.$date);
                        } else {
                            return 'N/A';
                        }
                        
                        if (isNaN(dateObj.getTime())) {
                            return 'N/A';
                        }
                        
                        var day = String(dateObj.getDate()).padStart(2, '0');
                        var month = String(dateObj.getMonth() + 1).padStart(2, '0');
                        var year = dateObj.getFullYear();
                        var hours = String(dateObj.getHours()).padStart(2, '0');
                        var minutes = String(dateObj.getMinutes()).padStart(2, '0');
                        var seconds = String(dateObj.getSeconds()).padStart(2, '0');
                        
                        return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + ':' + seconds;
                    } catch (e) {
                        return 'N/A';
                    }
                }

                // Función para obtener el color del badge según el estado
                function getEstadoBadge(estado) {
                    if (estado === 'completada') return 'success';
                    if (estado === 'pendiente') return 'warning';
                    if (estado === 'fallida') return 'danger';
                    return 'secondary';
                }

                // Construir HTML del detalle
                var html = '<div class="row">';
                
                // Columna izquierda - Información General
                html += '<div class="col-md-6">';
                html += '<h6 class="border-bottom pb-2 mb-3"><i class="fas fa-info-circle me-2"></i>Información General</h6>';
                html += '<table class="table table-sm table-borderless">';
                html += '<tr><td class="fw-bold" style="width: 40%;">ID Transacción:</td><td><code>' + transaccionData._id + '</code></td></tr>';
                html += '<tr><td class="fw-bold">Tipo:</td><td><span class="badge bg-info">' + (transaccionData.tipo ? transaccionData.tipo.replace('_', ' ').charAt(0).toUpperCase() + transaccionData.tipo.replace('_', ' ').slice(1) : 'N/A') + '</span></td></tr>';
                if (transaccionData.subtipo) {
                    html += '<tr><td class="fw-bold">Subtipo:</td><td><span class="badge bg-secondary">' + transaccionData.subtipo + '</span></td></tr>';
                }
                html += '<tr><td class="fw-bold">Estado:</td><td><span class="badge bg-' + getEstadoBadge(transaccionData.estado) + '">' + (transaccionData.estado ? transaccionData.estado.charAt(0).toUpperCase() + transaccionData.estado.slice(1) : 'N/A') + '</span></td></tr>';
                html += '<tr><td class="fw-bold">Cantidad:</td><td><span class="fw-bold ' + (transaccionData.cantidad > 0 ? 'text-success' : 'text-danger') + '">€' + parseFloat(transaccionData.cantidad || 0).toFixed(2) + '</span></td></tr>';
                html += '<tr><td class="fw-bold">Fecha:</td><td>' + formatearFecha(transaccionData.fecha) + '</td></tr>';
                html += '<tr><td class="fw-bold">Método de Pago:</td><td><span class="badge bg-' + (transaccionData.metodo_pago === 'tarjeta' ? 'primary' : 'secondary') + '"><i class="fas fa-' + (transaccionData.metodo_pago === 'tarjeta' ? 'credit-card' : 'wallet') + '"></i> ' + (transaccionData.metodo_pago ? transaccionData.metodo_pago.charAt(0).toUpperCase() + transaccionData.metodo_pago.slice(1) : 'N/A') + '</span></td></tr>';
                html += '</table>';
                html += '</div>';

                // Columna derecha - Detalles Adicionales
                html += '<div class="col-md-6">';
                html += '<h6 class="border-bottom pb-2 mb-3"><i class="fas fa-user me-2"></i>Información del Usuario</h6>';
                html += '<table class="table table-sm table-borderless">';
                html += '<tr><td class="fw-bold" style="width: 40%;">Usuario ID:</td><td><code>' + (transaccionData.usuario_id || 'N/A') + '</code></td></tr>';
                if (transaccionData.descripcion) {
                    html += '<tr><td class="fw-bold">Descripción:</td><td>' + transaccionData.descripcion + '</td></tr>';
                }
                if (transaccionData.codigo_id) {
                    html += '<tr><td class="fw-bold">Código ID:</td><td><a href="/de-' + (transaccionData.marca || '').toLowerCase() + '?codigo=' + transaccionData.codigo_id + '" target="_blank" class="text-primary">' + transaccionData.codigo_id + ' <i class="fas fa-external-link-alt"></i></a></td></tr>';
                }
                if (transaccionData.marca) {
                    html += '<tr><td class="fw-bold">Marca:</td><td><span class="badge bg-info">' + transaccionData.marca + '</span></td></tr>';
                }
                if (transaccionData.tipo_destacado) {
                    html += '<tr><td class="fw-bold">Tipo Destacado:</td><td><span class="badge bg-warning">' + transaccionData.tipo_destacado + '</span></td></tr>';
                }
                if (transaccionData.paquete) {
                    html += '<tr><td class="fw-bold">Paquete:</td><td><span class="badge bg-success">€' + transaccionData.paquete + '</span></td></tr>';
                }
                html += '</table>';

                // Información de Stripe si existe
                if (transaccionData.stripe_session_id || transaccionData.stripe_payment_intent) {
                    html += '<h6 class="border-bottom pb-2 mb-3 mt-4"><i class="fab fa-stripe me-2"></i>Información de Stripe</h6>';
                    html += '<table class="table table-sm table-borderless">';
                    if (transaccionData.stripe_session_id) {
                        html += '<tr><td class="fw-bold" style="width: 40%;">Session ID:</td><td><code>' + transaccionData.stripe_session_id + '</code></td></tr>';
                    }
                    if (transaccionData.stripe_payment_intent) {
                        html += '<tr><td class="fw-bold">Payment Intent:</td><td><a href="https://dashboard.stripe.com/payments/' + transaccionData.stripe_payment_intent + '" target="_blank" class="text-primary">' + transaccionData.stripe_payment_intent + ' <i class="fas fa-external-link-alt"></i></a></td></tr>';
                    }
                    if (transaccionData.stripe_customer_email) {
                        html += '<tr><td class="fw-bold">Email Cliente:</td><td>' + transaccionData.stripe_customer_email + '</td></tr>';
                    }
                    if (transaccionData.stripe_payment_status) {
                        html += '<tr><td class="fw-bold">Estado Pago:</td><td><span class="badge bg-' + (transaccionData.stripe_payment_status === 'paid' ? 'success' : 'warning') + '">' + transaccionData.stripe_payment_status + '</span></td></tr>';
                    }
                    html += '</table>';
                }

                // Información adicional
                if (transaccionData.admin_id || transaccionData.sincronizado_manual) {
                    html += '<h6 class="border-bottom pb-2 mb-3 mt-4"><i class="fas fa-cog me-2"></i>Información Adicional</h6>';
                    html += '<table class="table table-sm table-borderless">';
                    if (transaccionData.admin_id) {
                        html += '<tr><td class="fw-bold" style="width: 40%;">Admin ID:</td><td><code>' + transaccionData.admin_id + '</code></td></tr>';
                    }
                    if (transaccionData.sincronizado_manual) {
                        html += '<tr><td class="fw-bold">Sincronizado Manual:</td><td><span class="badge bg-warning">Sí</span></td></tr>';
                    }
                    if (transaccionData.fecha_sincronizacion) {
                        html += '<tr><td class="fw-bold">Fecha Sincronización:</td><td>' + formatearFecha(transaccionData.fecha_sincronizacion) + '</td></tr>';
                    }
                    html += '</table>';
                }

                html += '</div>';
                html += '</div>';

                $('#detalles-transaccion-content').html(html);
            });

            // Crear gráfica de códigos destacados
            <?php
            // Preparar datos para la gráfica
            $labels = [];
            $datos_normal = [];
            $datos_premium = [];
            $datos_total = [];

            if (!empty($datos_grafica_destacados)) {
                foreach ($datos_grafica_destacados as $dato) {
                    if (isset($dato['_id']['day']) && isset($dato['_id']['month'])) {
                        $fecha = $dato['_id']['day'] . '/' . $dato['_id']['month'];
                        $labels[] = $fecha;
                        $datos_normal[] = $dato['destacados_normal'] ?? 0;
                        $datos_premium[] = $dato['destacados_premium'] ?? 0;
                        $datos_total[] = $dato['total_destacados'] ?? 0;
                    }
                }
            }

            // Si no hay datos, crear datos de ejemplo para mostrar la gráfica
            if (empty($labels)) {
                $labels = ['No hay datos'];
                $datos_normal = [0];
                $datos_premium = [0];
                $datos_total = [0];
            }
            ?>

            const ctx = document.getElementById('graficaDestacados').getContext('2d');
            const graficaDestacados = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Códigos Destacados Normales',
                        data: <?php echo json_encode($datos_normal); ?>,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.1
                    }, {
                        label: 'Códigos Destacados Premium',
                        data: <?php echo json_encode($datos_premium); ?>,
                        borderColor: 'rgb(255, 193, 7)',
                        backgroundColor: 'rgba(255, 193, 7, 0.2)',
                        tension: 0.1
                    }, {
                        label: 'Total Códigos Destacados',
                        data: <?php echo json_encode($datos_total); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Evolución de Códigos Destacados (Últimos 30 días)'
                        },
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Número de Códigos'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Fecha'
                            }
                        }
                    }
                }
            });
        });
    </script>
    
<?php get_footer(); ?>
</body>
</html>


