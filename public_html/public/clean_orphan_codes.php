<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';

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

$collection_codigos = getCollectionCodigos();
$collection_marcas = getCollectionMarcas();

// Obtener todas las marcas activas
$marcas_activas = $collection_marcas->find(['estado' => 1])->toArray();
$marcas_activas_nombres = array_column($marcas_activas, 'nombre_clave');

// Obtener códigos huérfanos de diferentes tipos
$codigos_huerfanos = [];

// 1. Códigos con marcas que no existen o no están activas
$filtro_codigos_huerfanos = [
    'estado' => ['$in' => [0, -1, -2]], // Solo códigos activos o inactivos
    'marca' => ['$nin' => $marcas_activas_nombres, '$ne' => null] // Marca no está en marcas activas y no es null
];

$codigos_marca_invalida = $collection_codigos->find($filtro_codigos_huerfanos)->toArray();

// 2. Códigos sin marca definida (marca es null o vacío)
$filtro_codigos_sin_marca = [
    'estado' => ['$in' => [0, -1, -2]],
    '$or' => [
        ['marca' => null],
        ['marca' => ''],
        ['marca' => ['$exists' => false]]
    ]
];

$codigos_sin_marca = $collection_codigos->find($filtro_codigos_sin_marca)->toArray();

// Combinar ambos tipos
$codigos_huerfanos = array_merge($codigos_marca_invalida, $codigos_sin_marca);

// Procesar eliminación si se confirma
$mensaje_resultado = '';
if (isset($_POST['confirmar_eliminacion']) && $_POST['confirmar_eliminacion'] === 'si') {
    if (!empty($codigos_huerfanos)) {
        $ids_a_eliminar = array_column($codigos_huerfanos, '_id');
        $object_ids = array_map(function($id) {
            return new MongoDB\BSON\ObjectId($id);
        }, $ids_a_eliminar);

        $resultado = $collection_codigos->deleteMany(['_id' => ['$in' => $object_ids]]);
        $mensaje_resultado = "<div class='alert alert-success'>✅ Eliminados {$resultado->getDeletedCount()} códigos huérfanos correctamente.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpiar Códigos Huérfanos - Panel Administrativo</title>
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
        .codigo-text {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.85em;
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
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
                        <a class="nav-link" href="admin_configuracion.php">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </a>
                        <a class="nav-link" href="admin_logs.php">
                            <i class="fas fa-file-alt me-2"></i>Logs
                        </a>
                        <a class="nav-link active" href="clean_orphan_codes.php">
                            <i class="fas fa-trash me-2"></i>Limpiar Códigos
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
                        <h5 class="mb-0">Limpiar Códigos Huérfanos</h5>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">Bienvenido, <?php echo $_SESSION["username"] ?? 'Admin'; ?></span>
                            <a href="https://www.codigoamigo.com/logout" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Salir
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="p-4">
                    <?php if (!empty($mensaje_resultado)): ?>
                        <?php echo $mensaje_resultado; ?>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Atención</h5>
                        <p>Esta herramienta elimina códigos que tienen marcas inexistentes o no tienen marca definida. Estos códigos no se muestran en la página pública y ocupan espacio innecesario en la base de datos.</p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <i class="fas fa-database fa-2x text-primary mb-2"></i>
                                    <h4 class="text-primary"><?php echo number_format($collection_codigos->count()); ?></h4>
                                    <p class="text-muted mb-0">Total Códigos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                    <h4 class="text-success"><?php echo number_format($collection_codigos->count(['estado' => 0])); ?></h4>
                                    <p class="text-muted mb-0">Códigos Activos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <i class="fas fa-pause-circle fa-2x text-warning mb-2"></i>
                                    <h4 class="text-warning"><?php echo number_format($collection_codigos->count(['estado' => -1])); ?></h4>
                                    <p class="text-muted mb-0">Códigos Inactivos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-stat">
                                <div class="card-body text-center">
                                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                    <h4 class="text-danger"><?php echo number_format($collection_codigos->count(['estado' => -2])); ?></h4>
                                    <p class="text-muted mb-0">Códigos Desactivados</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-trash me-2"></i>Códigos Huérfanos Encontrados (<?php echo count($codigos_huerfanos); ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="alert alert-danger">
                                        <strong>Códigos con marca inválida:</strong> <?php echo count($codigos_marca_invalida); ?>
                                        <br><small>Códigos que apuntan a marcas que no existen en la base de datos</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-warning">
                                        <strong>Códigos sin marca:</strong> <?php echo count($codigos_sin_marca); ?>
                                        <br><small>Códigos que no tienen marca definida</small>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($codigos_huerfanos)): ?>
                            <div class="alert alert-info">
                                <strong>Información:</strong> Estos códigos no se muestran en la página pública y ocupan espacio innecesario.
                                Se recomienda eliminarlos para mantener la base de datos limpia.
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID Código</th>
                                            <th>Marca</th>
                                            <th>Estado</th>
                                            <th>Usuario</th>
                                            <th>Tipo Problema</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($codigos_huerfanos, 0, 100) as $codigo): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted font-monospace"><?php echo $codigo['_id']; ?></small>
                                            </td>
                                            <td>
                                                <?php if (isset($codigo['marca']) && !empty($codigo['marca'])): ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($codigo['marca']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin marca</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $estado = $codigo['estado'] ?? 0;
                                                $estado_class = $estado == 0 ? 'success' : ($estado == -1 ? 'warning' : 'danger');
                                                $estado_text = $estado == 0 ? 'Activo' : ($estado == -1 ? 'Inactivo' : 'Desactivado');
                                                ?>
                                                <span class="badge bg-<?php echo $estado_class; ?>"><?php echo $estado_text; ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo substr($codigo['id_usuario'] ?? 'N/A', 0, 8) . '...'; ?></small>
                                            </td>
                                            <td>
                                                <?php if (in_array($codigo, $codigos_marca_invalida)): ?>
                                                    <span class="badge bg-danger">Marca inválida</span>
                                                <?php elseif (in_array($codigo, $codigos_sin_marca)): ?>
                                                    <span class="badge bg-warning">Sin marca</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>

                                        <?php if (count($codigos_huerfanos) > 100): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                <em>... y <?php echo count($codigos_huerfanos) - 100; ?> códigos más</em>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (count($codigos_huerfanos) > 0): ?>
                            <div class="mt-4">
                                <form method="POST" onsubmit="return confirm('¿Estás seguro de eliminar <?php echo count($codigos_huerfanos); ?> códigos huérfanos? Esta acción no se puede deshacer.')">
                                    <button type="submit" name="confirmar_eliminacion" value="si" class="btn btn-danger btn-lg">
                                        <i class="fas fa-trash me-2"></i>Eliminar <?php echo count($codigos_huerfanos); ?> Códigos Huérfanos
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>

                            <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                <h5>¡Excelente!</h5>
                                <p>No se encontraron códigos huérfanos en la base de datos.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
