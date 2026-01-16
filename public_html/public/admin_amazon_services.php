<?php
// admin_amazon_services.php
// Gestión de Servicios Amazon (Enlaces de Afiliado fijos)

session_start();

include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_amazon_services.php';
include_once __DIR__ . '/admin_sidebar_menu.php';

// Verificar permisos de administrador (misma lista que admin_dashboard.php)
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

// Procesar acciones POST
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collection = getCollectionAffiliateLinks();
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create' || $_POST['action'] === 'update') {
            $slug = trim($_POST['slug']);
            $data = [
                'slug' => $slug,
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description']),
                'cta_text' => trim($_POST['cta_text']),
                'destination_url' => trim($_POST['destination_url']),
                'image_url' => trim($_POST['image_url']),
                'category' => $_POST['category'],
                'active' => isset($_POST['active']) ? true : false,
                'order' => (int)$_POST['order'],
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];

            try {
                if ($_POST['action'] === 'create') {
                    // Verificar si existe slug
                    $exists = $collection->findOne(['slug' => $slug]);
                    if ($exists) {
                        throw new Exception("El slug '$slug' ya existe.");
                    }
                    $data['created_at'] = new MongoDB\BSON\UTCDateTime();
                    $collection->insertOne($data);
                    $mensaje = "Servicio creado correctamente.";
                    $tipo_mensaje = 'success';
                } else {
                    // Update
                    $id = new MongoDB\BSON\ObjectId($_POST['id']);
                    $collection->updateOne(['_id' => $id], ['$set' => $data]);
                    $mensaje = "Servicio actualizado correctamente.";
                    $tipo_mensaje = 'success';
                }
            } catch (Exception $e) {
                $mensaje = "Error: " . $e->getMessage();
                $tipo_mensaje = 'danger';
            }
        } elseif ($_POST['action'] === 'delete') {
            try {
                $id = new MongoDB\BSON\ObjectId($_POST['id']);
                $collection->deleteOne(['_id' => $id]);
                $mensaje = "Servicio eliminado correctamente.";
                $tipo_mensaje = 'success';
            } catch (Exception $e) {
                $mensaje = "Error al eliminar: " . $e->getMessage();
                $tipo_mensaje = 'danger';
            }
        }
    }
}

// Obtener servicios para listar
$linksCollection = getCollectionAffiliateLinks();
$services = $linksCollection->find([], ['sort' => ['order' => 1]])->toArray();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Amazon Services - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .wrapper { display: flex; width: 100%; align-items: stretch; }
        .sidebar { min-width: 250px; max-width: 250px; min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; transition: all 0.3s; }
        .sidebar.active { margin-left: -250px; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; }
        .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,0.1); }
        .main-content { width: 100%; background: #f8f9fa; min-height: 100vh; padding: 20px; }
        .service-img-preview { max-width: 50px; max-height: 50px; object-fit: contain; }
    </style>
</head>
<body>
    <div class="wrapper">
        <nav id="sidebar" class="sidebar">
            <div class="p-4">
                <h3>Admin Panel</h3>
            </div>
            <?php echo get_admin_sidebar_menu('admin_amazon_services.php'); ?>
        </nav>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fab fa-amazon text-warning me-2"></i>Gestión de Servicios Amazon</h2>
                <a href="admin_amazon_services_analytics.php" class="btn btn-outline-primary">
                    <i class="fas fa-chart-bar me-1"></i> Ver Analytics
                </a>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Listado de Servicios</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal" onclick="resetForm()">
                            <i class="fas fa-plus"></i> Nuevo Servicio
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Orden</th>
                                    <th>Img</th>
                                    <th>Servicio / Slug</th>
                                    <th>Categoría</th>
                                    <th>Destino</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $srv): ?>
                                <tr>
                                    <td><?php echo $srv['order']; ?></td>
                                    <td>
                                        <?php if (!empty($srv['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($srv['image_url']); ?>" class="service-img-preview">
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-image"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($srv['title']); ?></strong><br>
                                        <small class="text-muted">/go/<?php echo htmlspecialchars($srv['slug']); ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($srv['category']); ?></span></td>
                                    <td>
                                        <small class="text-truncate d-inline-block" style="max-width: 200px;">
                                            <?php echo htmlspecialchars($srv['destination_url']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($srv['active']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                                onclick='editService(<?php echo json_encode(mongoToArray($srv)); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Borrar este servicio?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (string)$srv['_id']; ?>">
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

    <!-- Modal Edición/Creación -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Nuevo Servicio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="serviceId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Slug (único)</label>
                                <input type="text" name="slug" id="slug" class="form-control" required placeholder="ej: prime-video">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tab Order</label>
                                <input type="number" name="order" id="order" class="form-control" value="0">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Título</label>
                                <input type="text" name="title" id="title" class="form-control" required placeholder="Amazon Prime">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Descripción (Bullets, separar por punto medio · o saltos)</label>
                                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Envíos gratis · Videos · Música"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Texto CTA</label>
                                <input type="text" name="cta_text" id="cta_text" class="form-control" required placeholder="Prueba Gratis 30 días">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Categoría</label>
                                <select name="category" id="category" class="form-select">
                                    <option value="general">General</option>
                                    <option value="video">Video / Streaming</option>
                                    <option value="audio">Audio / Música</option>
                                    <option value="lectura">Lectura</option>
                                    <option value="estudiante">Estudiante</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">URL Destino (con Tag)</label>
                                <input type="url" name="destination_url" id="destination_url" class="form-control" required placeholder="https://amazon.es/...?tag=spnfuryy-21">
                                <div class="form-text">Asegúrate de incluir ?tag=spnfuryy-21</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">URL Imagen Logo</label>
                                <input type="url" name="image_url" id="image_url" class="form-control" placeholder="https://...">
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="active" id="active" checked>
                                    <label class="form-check-label" for="active">Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Helper simple para convertir Mongo BSON a JSON para JS -->
    <?php
    function mongoToArray($doc) {
        if (is_object($doc)) $doc = (array)$doc;
        $out = [];
        foreach($doc as $k => $v) {
            if ($v instanceof MongoDB\BSON\ObjectId) $out[$k] = (string)$v;
            elseif ($v instanceof MongoDB\BSON\UTCDateTime) $out[$k] = $v->toDateTime()->format('c');
            elseif (is_array($v) || is_object($v)) $out[$k] = mongoToArray($v);
            else $out[$k] = $v;
        }
        return $out;
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editService(data) {
            document.getElementById('modalTitle').textContent = 'Editar Servicio';
            document.getElementById('formAction').value = 'update';
            document.getElementById('serviceId').value = data._id;
            
            document.getElementById('slug').value = data.slug;
            document.getElementById('slug').readOnly = true; // No permitir cambiar slug al editar para no romper links
            document.getElementById('order').value = data.order;
            document.getElementById('title').value = data.title;
            document.getElementById('description').value = data.description;
            document.getElementById('cta_text').value = data.cta_text;
            document.getElementById('category').value = data.category;
            document.getElementById('destination_url').value = data.destination_url;
            document.getElementById('image_url').value = data.image_url;
            document.getElementById('active').checked = data.active;
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Nuevo Servicio';
            document.getElementById('formAction').value = 'create';
            document.getElementById('serviceId').value = '';
            document.getElementById('slug').value = '';
            document.getElementById('slug').readOnly = false;
            document.querySelector('form').reset();
            document.getElementById('active').checked = true;
        }
    </script>
</body>
</html>
