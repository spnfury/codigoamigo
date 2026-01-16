<?php
session_start();

// TEMPORAL: Comentar verificación de login para pruebas
// Verificar si el usuario está logueado
// if (!isset($_SESSION['usuario_id'])) {
//     header('Location: login.php');
//     exit;
// }

// TEMPORAL: Usar un ID de usuario de prueba
$usuario_id = $_SESSION['usuario_id'] ?? '58bd851da54e295b8b52f702'; // ID de admin para pruebas

// Incluir funciones
include_once 'myphp/funciones_afiliados.php';
include_once 'myphp/funciones_usuario.php';

// Obtener datos del usuario
$collection_usuarios = getCollectionUsuarios();
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario_id)]);

if (!$usuario) {
    // TEMPORAL: Crear usuario de prueba
    $usuario = [
        '_id' => new MongoDB\BSON\ObjectId($usuario_id),
        'email' => 'test@codigoamigo.com',
        'username' => 'test_user'
    ];
}

// Obtener URLs de afiliados del usuario
$urls_result = obtenerUrlsAfiliadosUsuario($usuario_id);
$urls = $urls_result['success'] ? $urls_result['urls'] : [];

// Obtener estadísticas
$stats_result = obtenerEstadisticasIngresosUsuario($usuario_id);
$estadisticas = $stats_result['success'] ? $stats_result['estadisticas'] : [];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis URLs de Afiliados - CodigoAmigo (PRUEBA)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .url-card {
            border-left: 4px solid #667eea;
        }
        .brand-tag {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin: 2px;
            display: inline-block;
        }
        .test-banner {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Banner de Prueba -->
    <div class="test-banner">
        🧪 MODO PRUEBA - Sistema de URLs de Afiliados funcionando correctamente
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark gradient-bg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-link me-2"></i>CodigoAmigo
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="panel_usuario.php">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($usuario['email']); ?>
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-chart-line me-2"></i>Mis URLs de Afiliados</h2>
                        <p class="text-muted">Gestiona tus enlaces de afiliados y registra tus ingresos</p>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>¡Sistema funcionando!</strong> La ruta /afiliados está operativa.
                        </div>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaUrlModal">
                        <i class="fas fa-plus me-2"></i>Nueva URL
                    </button>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h4><?php echo number_format($estadisticas['total_ingresos'] ?? 0, 2); ?>€</h4>
                        <small>Total Ingresos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar fa-2x mb-2"></i>
                        <h4><?php echo number_format($estadisticas['promedio_mensual'] ?? 0, 2); ?>€</h4>
                        <small>Promedio Mensual</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-link fa-2x mb-2"></i>
                        <h4><?php echo count($urls); ?></h4>
                        <small>URLs Activas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x mb-2"></i>
                        <h4><?php echo $estadisticas['total_registros'] ?? 0; ?></h4>
                        <small>Registros</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de URLs -->
        <div class="row">
            <?php if (empty($urls)): ?>
                <div class="col-12">
                    <div class="card text-center py-5">
                        <div class="card-body">
                            <i class="fas fa-link fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No tienes URLs de afiliados registradas</h4>
                            <p class="text-muted">Comienza agregando tu primera URL de afiliado</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaUrlModal">
                                <i class="fas fa-plus me-2"></i>Agregar Primera URL
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($urls as $url): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card url-card card-hover h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title">
                                        <i class="fas fa-external-link-alt me-2"></i>
                                        <?php echo htmlspecialchars($url['nombre_plataforma']); ?>
                                    </h5>
                                </div>
                                
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-link me-1"></i>
                                        <?php echo htmlspecialchars(substr($url['url'], 0, 50)) . (strlen($url['url']) > 50 ? '...' : ''); ?>
                                    </small>
                                </p>
                                
                                <?php if (!empty($url['descripcion'])): ?>
                                    <p class="card-text"><?php echo htmlspecialchars($url['descripcion']); ?></p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($url['fecha_creacion'])); ?>
                                    </small>
                                    <a href="<?php echo htmlspecialchars($url['url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i>Visitar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
