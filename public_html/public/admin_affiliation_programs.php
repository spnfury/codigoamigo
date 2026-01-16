<?php
session_start();

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/admin_sidebar_menu.php';

$array_codigos_acceso = ["58bd851da54e295b8b52f702", "5e78170e6b68e6519b7c5df2", "639899bc6321ee0d0e4010d2", "5c8a10ce2f55c86d6e707d82"];
if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: /');
    die();
}

$col_programs = getCollectionAffiliationPrograms();
$col_networks = getCollectionAffiliationNetworks();
$col_marcas = getCollectionMarcas();

// Manejar Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $new_prog = [
            'brand_id' => $_POST['brand_id'], // nombre_clave de la marca
            'network_id' => new MongoDB\BSON\ObjectId($_POST['network_id']),
            'program_id_in_network' => $_POST['program_id_in_network'] ?? '',
            'url' => $_POST['url'] ?? '',
            'status' => 'active',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];
        $col_programs->insertOne($new_prog);
    } elseif ($action === 'delete') {
        $id = new MongoDB\BSON\ObjectId($_POST['id']);
        $col_programs->deleteOne(['_id' => $id]);
    }
}

$programs = $col_programs->find()->toArray();
$networks = $col_networks->find(['status' => 'active'])->toArray();
$marcas = $col_marcas->find(['estado' => 1], ['sort' => ['nombre' => 1], 'projection' => ['nombre' => 1, 'nombre_clave' => 1]])->toArray();

// Mapear redes por ID para fácil acceso
$networks_map = [];
foreach ($networks as $net) {
    $networks_map[(string)$net['_id']] = $net['name'];
}

$title = "Mapeo de Programas de Afiliación - Admin";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 2px 0; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .main-content { background-color: #f8f9fa; min-height: 100vh; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .select2-container { width: 100% !important; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php echo get_admin_sidebar_menu('admin_affiliation_programs.php'); ?>
            
            <div class="col-md-9 col-lg-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-briefcase me-2"></i>Programas y Mapeos</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">
                        <i class="fas fa-plus me-2"></i>Añadir Mapeo
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Marca</th>
                                    <th>Red</th>
                                    <th>Program ID</th>
                                    <th>URL Destino</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($programs as $prog): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($prog['brand_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($networks_map[(string)$prog['network_id']] ?? 'Desconocida'); ?></td>
                                    <td><code><?php echo htmlspecialchars($prog['program_id_in_network'] ?? '-'); ?></code></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars(substr($prog['url'] ?? '-', 0, 50)); ?>...</small></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('¿Eliminar este mapeo?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (string)$prog['_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
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

    <!-- Modal Nuevo Mapeo -->
    <div class="modal fade" id="addProgramModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Añadir Mapeo de Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Marca</label>
                        <select name="brand_id" class="form-select select2-marcas" required>
                            <option value="">Selecciona una marca...</option>
                            <?php foreach ($marcas as $m): ?>
                            <option value="<?php echo $m['nombre_clave']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Red de Afiliación</label>
                        <select name="network_id" class="form-select" required>
                            <?php foreach ($networks as $n): ?>
                            <option value="<?php echo (string)$n['_id']; ?>"><?php echo htmlspecialchars($n['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ID del Programa en la Red</label>
                        <input type="text" name="program_id_in_network" class="form-control" placeholder="Ej: 12345">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL Directa (opcional)</label>
                        <input type="url" name="url" class="form-control" placeholder="https://...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Mapeo</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-marcas').select2({
                dropdownParent: $('#addProgramModal')
            });
        });
    </script>
</body>
</html>
