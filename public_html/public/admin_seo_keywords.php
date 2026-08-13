<?php
/**
 * Panel de Administración - Keywords SEO por Marca
 * Gestiona las keywords de Google Suggest + GSC para cada página de marca
 */
session_start();

include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../myphp/funciones_marca.php';
include_once __DIR__ . '/../myphp/funciones_keywords_marca.php';
include_once __DIR__ . '/admin_sidebar_menu.php';

// Verificar permisos de administrador
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702",
    "5e78170e6b68e6519b7c5df2",
    "639899bc6321ee0d0e4010d2",
    "5c8a10ce2f55c86d6e707d82"
];

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

// Obtener todas las marcas con su estado de keywords
$collection = getCollectionMarcas();
$all_brands = $collection->find(
    [],
    [
        'projection' => [
            'nombre' => 1, 
            'nombre_clave' => 1, 
            'imagen' => 1,
            'seo_keywords.updated_at' => 1, 
            'seo_keywords.total_keywords' => 1
        ],
        'sort' => ['nombre' => 1]
    ]
);
$brands_list = iterator_to_array($all_brands);

// Contar estadísticas
$total_brands = count($brands_list);
$brands_with_keywords = 0;
$total_keywords = 0;
foreach ($brands_list as $b) {
    if (isset($b['seo_keywords']['total_keywords']) && $b['seo_keywords']['total_keywords'] > 0) {
        $brands_with_keywords++;
        $total_keywords += $b['seo_keywords']['total_keywords'];
    }
}
$brands_pending = $total_brands - $brands_with_keywords;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keywords SEO - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .main-content { padding: 2rem; }
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-3px); }
        .brand-row { transition: background 0.2s; }
        .brand-row:hover { background: #f0f7ff; }
        .brand-logo { width: 32px; height: 32px; object-fit: contain; border-radius: 6px; }
        .badge-type { font-size: 0.7rem; padding: 3px 8px; border-radius: 10px; font-weight: 500; }
        .badge-transactional { background: rgba(245,158,11,0.15); color: #b45309; }
        .badge-informational { background: rgba(139,92,246,0.15); color: #6d28d9; }
        .badge-product { background: rgba(59,130,246,0.15); color: #1d4ed8; }
        .badge-generic { background: rgba(107,114,128,0.15); color: #374151; }
        .badge-navigational { background: rgba(16,185,129,0.15); color: #047857; }
        .kw-preview { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; }
        .kw-preview.open { max-height: 2000px; }
        .progress-container { display: none; }
        .progress-container.active { display: block; }
        .search-input { border-radius: 25px; padding: 10px 20px; border: 2px solid #dee2e6; transition: border-color 0.3s; }
        .search-input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.15); outline: none; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php echo get_admin_sidebar_menu('admin_seo_keywords.php'); ?>
            
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0"><i class="fas fa-key me-2 text-warning"></i>Keywords SEO por Marca</h4>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" id="searchBrand" class="form-control search-input" placeholder="🔍 Buscar marca..." style="width: 250px;">
                        <button class="btn btn-warning btn-sm" onclick="bulkUpdateAll()" id="btnBulkUpdate">
                            <i class="fas fa-sync-alt me-1"></i> Actualizar Todas
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card card-stat bg-white p-3 text-center">
                            <i class="fas fa-tags text-primary" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                            <h3 class="fw-bold mb-0"><?php echo number_format($total_brands); ?></h3>
                            <p class="text-muted mb-0 small">Total Marcas</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stat bg-white p-3 text-center">
                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                            <h3 class="fw-bold mb-0" id="statWithKeywords"><?php echo number_format($brands_with_keywords); ?></h3>
                            <p class="text-muted mb-0 small">Con Keywords</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stat bg-white p-3 text-center">
                            <i class="fas fa-clock text-warning" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                            <h3 class="fw-bold mb-0" id="statPending"><?php echo number_format($brands_pending); ?></h3>
                            <p class="text-muted mb-0 small">Pendientes</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stat bg-white p-3 text-center">
                            <i class="fas fa-key text-info" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                            <h3 class="fw-bold mb-0" id="statTotalKw"><?php echo number_format($total_keywords); ?></h3>
                            <p class="text-muted mb-0 small">Total Keywords</p>
                        </div>
                    </div>
                </div>

                <!-- Bulk Progress -->
                <div class="progress-container mb-3" id="bulkProgressContainer">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted" id="bulkStatusText">Actualizando...</span>
                        <span class="small text-muted" id="bulkProgressText">0/0</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" id="bulkProgressBar" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Brands Table -->
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="brandsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Marca</th>
                                    <th class="text-center">Keywords</th>
                                    <th class="text-center">Tipos</th>
                                    <th class="text-center">Última Actualización</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($brands_list as $brand): 
                                    $slug = $brand['nombre_clave'] ?? '';
                                    $name = $brand['nombre'] ?? ucfirst($slug);
                                    $img = $brand['imagen'] ?? '';
                                    $has_kw = isset($brand['seo_keywords']['total_keywords']) && $brand['seo_keywords']['total_keywords'] > 0;
                                    $kw_count = $brand['seo_keywords']['total_keywords'] ?? 0;
                                    $updated = $brand['seo_keywords']['updated_at'] ?? '';
                                ?>
                                <tr class="brand-row" id="row-<?php echo htmlspecialchars($slug); ?>" data-slug="<?php echo htmlspecialchars($slug); ?>" data-name="<?php echo htmlspecialchars(mb_strtolower($name)); ?>">
                                    <td>
                                        <?php if ($img && $img !== 'Sin imagen'): ?>
                                            <img src="<?php echo htmlspecialchars($img); ?>" alt="" class="brand-logo" loading="lazy">
                                        <?php else: ?>
                                            <div class="brand-logo bg-light d-flex align-items-center justify-content-center"><i class="fas fa-tag text-muted"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($name); ?></strong>
                                        <br><small class="text-muted">/de-<?php echo htmlspecialchars($slug); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold kw-count" id="count-<?php echo htmlspecialchars($slug); ?>">
                                            <?php if ($has_kw): ?>
                                                <span class="text-success"><?php echo number_format($kw_count); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="text-center" id="types-<?php echo htmlspecialchars($slug); ?>">
                                        <?php if ($has_kw): ?>
                                            <span class="text-muted small">Actualizado</span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" id="date-<?php echo htmlspecialchars($slug); ?>">
                                        <?php if ($updated): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($updated); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">—</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary btn-update-kw" data-slug="<?php echo htmlspecialchars($slug); ?>" onclick="updateBrandKeywords('<?php echo htmlspecialchars($slug); ?>', this)">
                                            <i class="fas fa-sync-alt"></i> Actualizar
                                        </button>
                                        <?php if ($has_kw): ?>
                                            <button class="btn btn-sm btn-outline-secondary ms-1" onclick="togglePreview('<?php echo htmlspecialchars($slug); ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr id="preview-<?php echo htmlspecialchars($slug); ?>" style="display: none;">
                                    <td colspan="6" class="bg-light p-3">
                                        <div id="preview-content-<?php echo htmlspecialchars($slug); ?>">
                                            <div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Search filter
    document.getElementById('searchBrand').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.brand-row').forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const slug = row.getAttribute('data-slug') || '';
            row.style.display = (name.includes(query) || slug.includes(query)) ? '' : 'none';
        });
    });

    // Update single brand
    async function updateBrandKeywords(slug, btn) {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        try {
            const response = await fetch('/ajax/update_brand_keywords.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ brand_slug: slug })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update count
                document.getElementById('count-' + slug).innerHTML = 
                    '<span class="text-success">' + data.total_keywords + '</span>';
                
                // Update types
                const types = data.types || {};
                let typesHtml = '';
                if (types.transactional) typesHtml += '<span class="badge badge-type badge-transactional me-1">💰 ' + types.transactional + '</span>';
                if (types.product) typesHtml += '<span class="badge badge-type badge-product me-1">📦 ' + types.product + '</span>';
                if (types.informational) typesHtml += '<span class="badge badge-type badge-informational me-1">❓ ' + types.informational + '</span>';
                if (types.generic) typesHtml += '<span class="badge badge-type badge-generic me-1">🔗 ' + types.generic + '</span>';
                document.getElementById('types-' + slug).innerHTML = typesHtml;
                
                // Update date
                document.getElementById('date-' + slug).innerHTML = '<small class="text-success">Ahora mismo ✓</small>';
                
                btn.innerHTML = '<i class="fas fa-check text-success"></i> OK';
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-outline-success');
                
                // Show preview button if not already there
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('btn-outline-success');
                    btn.classList.add('btn-outline-primary');
                    btn.disabled = false;
                }, 2000);
            } else {
                btn.innerHTML = '<i class="fas fa-times text-danger"></i> Error';
                console.error(data.error);
                setTimeout(() => { btn.innerHTML = originalHtml; btn.disabled = false; }, 3000);
            }
        } catch (e) {
            btn.innerHTML = '<i class="fas fa-times text-danger"></i>';
            console.error(e);
            setTimeout(() => { btn.innerHTML = originalHtml; btn.disabled = false; }, 3000);
        }
    }

    // Toggle keyword preview
    function togglePreview(slug) {
        const previewRow = document.getElementById('preview-' + slug);
        const isVisible = previewRow.style.display !== 'none';
        
        if (isVisible) {
            previewRow.style.display = 'none';
        } else {
            previewRow.style.display = '';
            loadPreview(slug);
        }
    }

    async function loadPreview(slug) {
        const container = document.getElementById('preview-content-' + slug);
        container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando keywords...</div>';
        
        try {
            const response = await fetch('/ajax/update_brand_keywords.php?brand_slug=' + encodeURIComponent(slug));
            const data = await response.json();
            
            if (data.success && data.keywords) {
                let html = '<div class="d-flex flex-wrap gap-1">';
                data.keywords.forEach(kw => {
                    let badgeClass = 'badge-generic';
                    if (kw.type === 'transactional') badgeClass = 'badge-transactional';
                    else if (kw.type === 'product') badgeClass = 'badge-product';
                    else if (kw.type === 'informational') badgeClass = 'badge-informational';
                    else if (kw.type === 'navigational') badgeClass = 'badge-navigational';
                    
                    let impStr = kw.impressions > 0 ? ' (' + kw.impressions + ' imp.)' : '';
                    let srcIcon = kw.source === 'both' ? '🔗' : (kw.source === 'gsc' ? '📊' : '🔍');
                    
                    html += '<span class="badge badge-type ' + badgeClass + '">' + srcIcon + ' ' + kw.kw + impStr + '</span>';
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-muted">No hay keywords. Pulsa "Actualizar" primero.</div>';
            }
        } catch (e) {
            container.innerHTML = '<div class="text-danger">Error al cargar</div>';
        }
    }

    // Bulk update all brands
    async function bulkUpdateAll() {
        const btn = document.getElementById('btnBulkUpdate');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';
        
        const container = document.getElementById('bulkProgressContainer');
        container.classList.add('active');
        
        const rows = document.querySelectorAll('.brand-row');
        const slugs = [];
        rows.forEach(r => slugs.push(r.getAttribute('data-slug')));
        
        const total = slugs.length;
        let completed = 0;
        let errors = 0;
        
        for (const slug of slugs) {
            completed++;
            document.getElementById('bulkProgressText').textContent = completed + '/' + total;
            document.getElementById('bulkStatusText').textContent = 'Procesando: ' + slug;
            document.getElementById('bulkProgressBar').style.width = ((completed / total) * 100) + '%';
            
            try {
                const response = await fetch('/ajax/update_brand_keywords.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ brand_slug: slug })
                });
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('count-' + slug).innerHTML = '<span class="text-success">' + data.total_keywords + '</span>';
                    document.getElementById('date-' + slug).innerHTML = '<small class="text-success">✓</small>';
                } else {
                    errors++;
                }
            } catch (e) {
                errors++;
            }
            
            // Small delay between brands to respect rate limits
            await new Promise(resolve => setTimeout(resolve, 500));
        }
        
        document.getElementById('bulkStatusText').textContent = '✅ Completado! ' + (errors > 0 ? errors + ' errores' : 'Sin errores');
        document.getElementById('bulkProgressBar').classList.remove('bg-warning');
        document.getElementById('bulkProgressBar').classList.add(errors > 0 ? 'bg-danger' : 'bg-success');
        
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Actualizar Todas';
        
        // Recargar la página después de un momento
        setTimeout(() => location.reload(), 3000);
    }
    </script>
</body>
</html>
