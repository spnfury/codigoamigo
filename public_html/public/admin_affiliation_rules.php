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

$col_rules = getCollectionAffiliationRules();
$col_networks = getCollectionAffiliationNetworks();
$col_marcas = getCollectionMarcas();

// Manejar Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $brand_id = $_POST['brand_id'];
        $priority = $_POST['priority'] ?? []; // Array de IDs de redes
        
        $col_rules->updateOne(
            ['brand_id' => $brand_id],
            ['$set' => [
                'priority_order' => $priority,
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]],
            ['upsert' => true]
        );
    } elseif ($action === 'delete') {
        $brand_id = $_POST['brand_id'];
        $col_rules->deleteOne(['brand_id' => $brand_id]);
    }
}

$rules = $col_rules->find()->toArray();
$networks = $col_networks->find(['status' => 'active'])->toArray();
$marcas = $col_marcas->find(['estado' => 1], ['sort' => ['nombre' => 1], 'projection' => ['nombre' => 1, 'nombre_clave' => 1]])->toArray();

// Mapear redes por ID para fácil acceso
$networks_map = [];
foreach ($networks as $net) {
    $networks_map[(string)$net['_id']] = $net['name'];
}

$title = "Reglas de Prioridad de Afiliación - Admin";
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
        .priority-badge { cursor: move; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php echo get_admin_sidebar_menu('admin_affiliation_rules.php'); ?>
            
            <div class="col-md-9 col-lg-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-sort-amount-down me-2"></i>Reglas de Prioridad</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRuleModal">
                        <i class="fas fa-plus me-2"></i>Nueva Regla
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Marca / Contexto</th>
                                    <th>Orden de Prioridad</th>
                                    <th>Última Actualización</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rules as $rule): ?>
                                <tr>
                                    <td><strong><?php echo ($rule['brand_id'] === 'default') ? '<span class="badge bg-dark">DEFAULT</span>' : htmlspecialchars($rule['brand_id']); ?></strong></td>
                                    <td>
                                        <?php if (!empty($rule['priority_order'])): ?>
                                            <?php foreach ($rule['priority_order'] as $index => $net_id): ?>
                                                <span class="badge bg-info me-1">
                                                    <?php echo ($index + 1) . '. ' . htmlspecialchars($networks_map[$net_id] ?? $net_id); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin orden definido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo isset($rule['updated_at']) ? $rule['updated_at']->toDateTime()->format('d/m/Y H:i') : '-'; ?></small></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('¿Eliminar esta regla?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="brand_id" value="<?php echo $rule['brand_id']; ?>">
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

    <!-- Modal Nueva Regla -->
    <div class="modal fade" id="addRuleModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST">
                <input type="hidden" name="action" value="save">
                <div class="modal-header">
                    <h5 class="modal-title">Configurar Prioridad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Marca</label>
                        <select name="brand_id" class="form-select select2-marcas" required>
                            <option value="default">POR DEFECTO (Global)</option>
                            <?php foreach ($marcas as $m): ?>
                            <option value="<?php echo $m['nombre_clave']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Selecciona Redes por Orden (Click para añadir)</label>
                        <div id="networks-pool" class="mb-2">
                            <?php foreach ($networks as $n): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary m-1 net-selector" data-id="<?php echo (string)$n['_id']; ?>" data-name="<?php echo htmlspecialchars($n['name']); ?>">
                                    <?php echo htmlspecialchars($n['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <label class="form-label mt-2">Prioridad Final:</label>
                        <ul id="priority-list" class="list-group">
                            <!-- Se llena dinámicamente -->
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Regla</button>
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
                dropdownParent: $('#addRuleModal')
            });

            $('.net-selector').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                addNetworkToPriority(id, name);
            });

            function addNetworkToPriority(id, name) {
                if ($(`#net-item-${id}`).length) return;
                
                const item = `
                    <li class="list-group-item d-flex justify-content-between align-items-center" id="net-item-${id}">
                        <span><i class="fas fa-grip-vertical me-2"></i> ${name}</span>
                        <input type="hidden" name="priority[]" value="${id}">
                        <button type="button" class="btn btn-sm btn-danger remove-net" onclick="$(this).parent().remove()"><i class="fas fa-times"></i></button>
                    </li>
                `;
                $('#priority-list').append(item);
            }
        });
    </script>
</body>
</html>
