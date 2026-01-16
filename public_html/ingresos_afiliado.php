<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$url_id = $_GET['url_id'] ?? '';

if (empty($url_id)) {
    header('Location: afiliados.php');
    exit;
}

// Incluir funciones
include_once 'myphp/funciones_afiliados.php';
include_once 'myphp/funciones_usuario.php';

// Obtener datos de la URL
$collection_afiliados = getCollectionAfiliados();
$url_data = $collection_afiliados->findOne([
    '_id' => new MongoDB\BSON\ObjectId($url_id),
    'usuario_id' => $usuario_id,
    'activo' => true
]);

if (!$url_data) {
    header('Location: afiliados.php');
    exit;
}

// Obtener ingresos de esta URL
$ingresos_result = obtenerIngresosAfiliadosUsuario($usuario_id, $url_id);
$ingresos = $ingresos_result['success'] ? $ingresos_result['ingresos'] : [];

// Obtener estadísticas de esta URL
$collection_ingresos = getCollectionIngresosAfiliados();
$stats = $collection_ingresos->aggregate([
    ['$match' => ['url_id' => new MongoDB\BSON\ObjectId($url_id)]],
    ['$group' => [
        '_id' => null,
        'total_ingresos' => ['$sum' => '$monto'],
        'promedio_mensual' => ['$avg' => '$monto'],
        'total_registros' => ['$sum' => 1],
        'ingreso_maximo' => ['$max' => '$monto'],
        'ingreso_minimo' => ['$min' => '$monto']
    ]]
])->toArray();

$estadisticas = !empty($stats) ? $stats[0] : [
    'total_ingresos' => 0,
    'promedio_mensual' => 0,
    'total_registros' => 0,
    'ingreso_maximo' => 0,
    'ingreso_minimo' => 0
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresos - <?php echo htmlspecialchars($url_data['nombre_plataforma']); ?> - CodigoAmigo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .ingreso-item {
            border-left: 4px solid #28a745;
            background: #f8f9fa;
        }
        .captura-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark gradient-bg">
        <div class="container">
            <a class="navbar-brand" href="afiliados.php">
                <i class="fas fa-arrow-left me-2"></i>Volver a URLs
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="afiliados.php">
                    <i class="fas fa-link me-1"></i>Mis URLs
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
                        <h2>
                            <i class="fas fa-chart-line me-2"></i>
                            <?php echo htmlspecialchars($url_data['nombre_plataforma']); ?>
                        </h2>
                        <p class="text-muted">
                            <i class="fas fa-link me-1"></i>
                            <a href="<?php echo htmlspecialchars($url_data['url']); ?>" target="_blank" class="text-decoration-none">
                                <?php echo htmlspecialchars($url_data['url']); ?>
                                <i class="fas fa-external-link-alt ms-1"></i>
                            </a>
                        </p>
                        <?php if (!empty($url_data['descripcion'])): ?>
                            <p class="text-muted"><?php echo htmlspecialchars($url_data['descripcion']); ?></p>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-success" onclick="registrarNuevoIngreso()">
                        <i class="fas fa-plus me-2"></i>Nuevo Ingreso
                    </button>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h4><?php echo number_format($estadisticas['total_ingresos'], 2); ?>€</h4>
                        <small>Total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar fa-2x mb-2"></i>
                        <h4><?php echo number_format($estadisticas['promedio_mensual'], 2); ?>€</h4>
                        <small>Promedio</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x mb-2"></i>
                        <h4><?php echo $estadisticas['total_registros']; ?></h4>
                        <small>Registros</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-arrow-up fa-2x mb-2"></i>
                        <h4><?php echo number_format($estadisticas['ingreso_maximo'], 2); ?>€</h4>
                        <small>Máximo</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-arrow-down fa-2x mb-2"></i>
                        <h4><?php echo number_format($estadisticas['ingreso_minimo'], 2); ?>€</h4>
                        <small>Mínimo</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-tag fa-2x mb-2"></i>
                        <h4><?php echo count($url_data['marcas'] ?? []); ?></h4>
                        <small>Marcas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Marcas Asociadas -->
        <?php if (!empty($url_data['marcas'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-tags me-2"></i>Marcas Asociadas</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($url_data['marcas'] as $marca): ?>
                                <span class="badge bg-primary me-2 mb-2">
                                    <i class="fas fa-tag me-1"></i>
                                    <?php echo htmlspecialchars($marca['nombre']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lista de Ingresos -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Historial de Ingresos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ingresos)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No hay ingresos registrados</h4>
                                <p class="text-muted">Comienza registrando tu primer ingreso</p>
                                <button class="btn btn-success" onclick="registrarNuevoIngreso()">
                                    <i class="fas fa-plus me-2"></i>Registrar Primer Ingreso
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($ingresos as $ingreso): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card ingreso-item card-hover">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo htmlspecialchars($ingreso['periodo']); ?>
                                                    </h6>
                                                    <span class="badge bg-success fs-6">
                                                        <?php echo number_format($ingreso['monto'], 2); ?>€
                                                    </span>
                                                </div>
                                                
                                                <?php if (!empty($ingreso['notas'])): ?>
                                                    <p class="card-text small text-muted">
                                                        <?php echo htmlspecialchars($ingreso['notas']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($ingreso['captura_path'])): ?>
                                                    <div class="mb-2">
                                                        <img src="<?php echo htmlspecialchars($ingreso['captura_path']); ?>" 
                                                             class="captura-preview" 
                                                             onclick="verCaptura('<?php echo htmlspecialchars($ingreso['captura_path']); ?>')"
                                                             alt="Captura de pantalla">
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Registrado: <?php echo date('d/m/Y H:i', strtotime($ingreso['fecha_registro'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Ingreso -->
    <div class="modal fade" id="registrarIngresoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Registrar Nuevo Ingreso
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="registrarIngresoForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="url_id" value="<?php echo $url_id; ?>">
                        <div class="mb-3">
                            <label for="monto" class="form-label">Monto (€)</label>
                            <input type="number" step="0.01" class="form-control" id="monto" name="monto" required>
                        </div>
                        <div class="mb-3">
                            <label for="periodo" class="form-label">Período</label>
                            <input type="text" class="form-control" id="periodo" name="periodo" required 
                                   placeholder="Enero 2025, 2025-01, etc.">
                        </div>
                        <div class="mb-3">
                            <label for="captura" class="form-label">Captura de Pantalla (opcional)</label>
                            <input type="file" class="form-control" id="captura" name="captura" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label for="notas" class="form-label">Notas (opcional)</label>
                            <textarea class="form-control" id="notas" name="notas" rows="3" 
                                      placeholder="Notas adicionales sobre este ingreso..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Registrar Ingreso
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Captura -->
    <div class="modal fade" id="verCapturaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-image me-2"></i>Captura de Pantalla
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="capturaImagen" src="" class="img-fluid" alt="Captura de pantalla">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Manejar formulario registrar ingreso
        document.getElementById('registrarIngresoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'registrar_ingreso');
            
            fetch('ajax/afiliados_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        });

        // Funciones auxiliares
        function registrarNuevoIngreso() {
            new bootstrap.Modal(document.getElementById('registrarIngresoModal')).show();
        }

        function verCaptura(capturaPath) {
            document.getElementById('capturaImagen').src = capturaPath;
            new bootstrap.Modal(document.getElementById('verCapturaModal')).show();
        }
    </script>
</body>
</html>
