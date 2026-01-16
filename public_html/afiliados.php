<?php
// La sesión ya está iniciada en app_with_mongo.php

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Incluir funciones
include_once 'myphp/funciones.php';
include_once 'myphp/funciones_afiliados.php';
include_once 'myphp/funciones_usuario.php';

// Obtener datos del usuario
$collection_usuarios = getCollectionUsuarios();
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario_id)]);

if (!$usuario) {
    header('Location: login.php');
    exit;
}

// Obtener URLs de afiliados del usuario
$urls_result = obtenerUrlsAfiliadosUsuario($usuario_id);
$urls = $urls_result['success'] ? $urls_result['urls'] : [];

// Obtener estadísticas
$stats_result = obtenerEstadisticasIngresosUsuario($usuario_id);
$stats = $stats_result['success'] ? $stats_result['estadisticas'] : [
    'total_ingresos' => 0,
    'promedio_mensual' => 0,
    'urls_activas' => 0,
    'total_registros' => 0
];

// Obtener códigos sin afiliado asignado (agrupados por marca)
$codigos_sin_afiliado_result = obtenerCodigosSinAfiliado($usuario_id);
$marcas_sin_afiliado = $codigos_sin_afiliado_result['success'] ? $codigos_sin_afiliado_result['marcas'] : [];

// Obtener afiliados con códigos asignados
$afiliados_con_codigos_result = obtenerEstadisticasAfiliadosConCodigos($usuario_id);
$afiliados_con_codigos = $afiliados_con_codigos_result['success'] ? $afiliados_con_codigos_result['afiliados'] : [];

// Configurar variables para el header
$title = "Gestión de Afiliados - CodigoAmigo";
$description = "Organiza tus códigos de descuento con tus URLs de afiliados para un mejor seguimiento de ingresos.";
$title_social = $title;
$description_social = $description;

// Incluir el header estándar
get_header_modern($title, $description, $title_social, $description_social);
$GLOBALS['header_modern_used'] = true; // Marcar que se usó el header moderno para el footer correspondiente
?>

<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
    text-align: center;
}

.page-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.page-header p {
    font-size: 1.2rem;
    opacity: 0.9;
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.stat-card .icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.8;
}

.stat-card .value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #333;
}

.stat-card .label {
    font-size: 1rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f8f9fa;
}

.section-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    color: white;
    text-decoration: none;
}

.marcas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.marca-card {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.marca-card:hover {
    border-color: #667eea;
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.marca-logo {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 10px;
    flex-shrink: 0;
}

.marca-logo-placeholder {
    width: 60px;
    height: 60px;
    background: #e9ecef;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.marca-info {
    flex: 1;
}

.marca-nombre {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin: 0 0 0.5rem 0;
}

.codigos-count {
    font-size: 0.9rem;
    color: #666;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.marca-action {
    color: #667eea;
    font-size: 1.2rem;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.marca-card:hover .marca-action {
    opacity: 1;
}

.codigos-lista {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
}

.codigo-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s ease;
}

.codigo-item:last-child {
    border-bottom: none;
}

.codigo-item:hover {
    background-color: #f8f9fa;
}

.codigo-checkbox {
    flex-shrink: 0;
    margin-top: 0.25rem;
}

.codigo-check {
    transform: scale(1.2);
}

.codigo-info {
    flex: 1;
}

.codigo-texto {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.codigo-descuento {
    font-size: 0.9rem;
    color: #28a745;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.codigo-descripcion {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.codigo-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #999;
}

.codigo-clicks {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.affiliate-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid #667eea;
}

.affiliate-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.affiliate-name {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.codes-count {
    background: #667eea;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.9rem;
    font-weight: 500;
}

.affiliate-url {
    color: #667eea;
    text-decoration: none;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    display: block;
}

.affiliate-url:hover {
    text-decoration: underline;
}

.affiliate-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-info {
    background-color: #17a2b8;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-sm:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    color: white;
    text-decoration: none;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: #333;
}

.empty-state p {
    margin: 0;
}

/* Modal styles */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    border-bottom: none;
}

.modal-title {
    font-weight: 600;
}

.modal-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.control-label {
    font-weight: 500;
    color: #333;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.help-block {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 1rem 2rem;
}

.btn-default {
    background-color: #6c757d;
    color: white;
    border: none;
}

.btn-default:hover {
    background-color: #5a6268;
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .stat-card {
        padding: 1.5rem;
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .codes-slider {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .code-card {
        min-width: auto;
    }
    
    .affiliate-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
}
</style>

<div class="page-header">
    <div class="container">
        <h1><i class="fa fa-link"></i> Gestión de Afiliados</h1>
        <p>Organiza tus códigos de descuento con tus URLs de afiliados para un mejor seguimiento de ingresos</p>
    </div>
</div>

<div class="container">
    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon"><i class="fa fa-euro"></i></div>
            <div class="value"><?php echo number_format($stats['total_ingresos'], 2); ?>€</div>
            <div class="label">Total Ingresos</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fa fa-chart-line"></i></div>
            <div class="value"><?php echo number_format($stats['promedio_mensual'], 2); ?>€</div>
            <div class="label">Promedio Mensual</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fa fa-link"></i></div>
            <div class="value"><?php echo $stats['urls_activas']; ?></div>
            <div class="label">URLs Activas</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fa fa-calendar"></i></div>
            <div class="value"><?php echo $stats['total_registros']; ?></div>
            <div class="label">Registros</div>
        </div>
    </div>

    <!-- Marcas sin afiliado asignado -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fa fa-exclamation-triangle"></i> Marcas sin Afiliado Asignado
            </h2>
            <?php 
            $total_codigos_sin_asignar = 0;
            foreach ($marcas_sin_afiliado as $marca) {
                $total_codigos_sin_asignar += $marca['total_codigos'];
            }
            ?>
            <span class="badge badge-warning"><?php echo $total_codigos_sin_asignar; ?> códigos</span>
        </div>
        
        <?php if (empty($marcas_sin_afiliado)): ?>
            <div class="empty-state">
                <i class="fa fa-check-circle"></i>
                <h3>¡Perfecto!</h3>
                <p>Todos tus códigos tienen una URL de afiliado asignada</p>
            </div>
        <?php else: ?>
            <div class="marcas-grid">
                <?php foreach ($marcas_sin_afiliado as $marca_clave => $marca): ?>
                    <div class="marca-card" onclick="mostrarModalCodigosMarca('<?php echo htmlspecialchars($marca_clave); ?>', '<?php echo htmlspecialchars($marca['nombre_marca']); ?>')">
                        <?php if (!empty($marca['url_imagen'])): ?>
                            <img src="<?php echo htmlspecialchars($marca['url_imagen']); ?>" alt="<?php echo htmlspecialchars($marca['nombre_marca']); ?>" class="marca-logo">
                        <?php else: ?>
                            <div class="marca-logo-placeholder">
                                <i class="fa fa-image"></i>
                            </div>
                        <?php endif; ?>
                        <div class="marca-info">
                            <h3 class="marca-nombre"><?php echo htmlspecialchars($marca['nombre_marca']); ?></h3>
                            <div class="codigos-count">
                                <i class="fa fa-tags"></i>
                                <?php echo $marca['total_codigos']; ?> código<?php echo $marca['total_codigos'] > 1 ? 's' : ''; ?>
                            </div>
                        </div>
                        <div class="marca-action">
                            <i class="fa fa-arrow-right"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Afiliados con códigos asignados -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fa fa-check-circle"></i> Afiliados con Códigos Asignados
            </h2>
            <a href="#" class="btn-primary" onclick="mostrarModalNuevaUrl()">
                <i class="fa fa-plus"></i> Nueva URL
            </a>
        </div>
        
        <?php if (empty($afiliados_con_codigos)): ?>
            <div class="empty-state">
                <i class="fa fa-info-circle"></i>
                <h3>No hay códigos asignados</h3>
                <p>Asigna códigos a tus URLs de afiliados para verlos aquí</p>
            </div>
        <?php else: ?>
            <?php foreach ($afiliados_con_codigos as $afiliado): ?>
                <div class="affiliate-card">
                    <div class="affiliate-header">
                        <h3 class="affiliate-name"><?php echo htmlspecialchars($afiliado['nombre_plataforma']); ?></h3>
                        <span class="codes-count"><?php echo $afiliado['total_codigos']; ?> códigos</span>
                    </div>
                    <a href="<?php echo htmlspecialchars($afiliado['url']); ?>" target="_blank" class="affiliate-url">
                        <i class="fa fa-external-link"></i> <?php echo htmlspecialchars($afiliado['url']); ?>
                    </a>
                    <?php if (!empty($afiliado['descripcion'])): ?>
                        <p style="color: #666; margin: 0.5rem 0;"><?php echo htmlspecialchars($afiliado['descripcion']); ?></p>
                    <?php endif; ?>
                    <div class="affiliate-actions">
                        <button class="btn-sm btn-info" onclick="verCodigosAsignados('<?php echo $afiliado['_id']; ?>', '<?php echo htmlspecialchars($afiliado['nombre_plataforma']); ?>')">
                            <i class="fa fa-eye"></i> Ver Códigos
                        </button>
                        <button class="btn-sm btn-success" onclick="registrarIngreso('<?php echo $afiliado['_id']; ?>')">
                            <i class="fa fa-plus"></i> Ingreso
                        </button>
                        <button class="btn-sm btn-danger" onclick="eliminarUrl('<?php echo $afiliado['_id']; ?>')">
                            <i class="fa fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- URLs sin códigos asignados -->
    <?php if (!empty($urls)): ?>
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa fa-link"></i> URLs sin Códigos Asignados
                </h2>
            </div>
            
            <?php 
            $urls_sin_codigos = array_filter($urls, function($url) use ($afiliados_con_codigos) {
                foreach ($afiliados_con_codigos as $afiliado) {
                    if ($afiliado['_id'] == $url['_id']) {
                        return false;
                    }
                }
                return true;
            });
            ?>
            
            <?php if (empty($urls_sin_codigos)): ?>
                <div class="empty-state">
                    <i class="fa fa-check-circle"></i>
                    <h3>¡Excelente!</h3>
                    <p>Todas tus URLs tienen códigos asignados</p>
                </div>
            <?php else: ?>
                <?php foreach ($urls_sin_codigos as $url): ?>
                    <div class="affiliate-card">
                        <div class="affiliate-header">
                            <h3 class="affiliate-name"><?php echo htmlspecialchars($url['nombre_plataforma']); ?></h3>
                            <span class="codes-count" style="background: #dc3545;">0 códigos</span>
                        </div>
                        <a href="<?php echo htmlspecialchars($url['url']); ?>" target="_blank" class="affiliate-url">
                            <i class="fa fa-external-link"></i> <?php echo htmlspecialchars($url['url']); ?>
                        </a>
                        <?php if (!empty($url['descripcion'])): ?>
                            <p style="color: #666; margin: 0.5rem 0;"><?php echo htmlspecialchars($url['descripcion']); ?></p>
                        <?php endif; ?>
                        <div class="affiliate-actions">
                            <button class="btn-sm btn-success" onclick="registrarIngreso('<?php echo $url['_id']; ?>')">
                                <i class="fa fa-plus"></i> Ingreso
                            </button>
                            <button class="btn-sm btn-danger" onclick="eliminarUrl('<?php echo $url['_id']; ?>')">
                                <i class="fa fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para Nueva URL -->
<div class="modal fade" id="modalNuevaUrl" tabindex="-1" role="dialog" aria-labelledby="modalNuevaUrlLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalNuevaUrlLabel">
                    <i class="fa fa-plus"></i> Nueva URL de Afiliado
                </h4>
            </div>
            <form id="formNuevaUrl" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombre_plataforma" class="control-label">Nombre de la Plataforma *</label>
                        <input type="text" class="form-control" id="nombre_plataforma" name="nombre_plataforma" required>
                        <span class="help-block">Ej: BanaHost, Hotmart, Amazon Associates</span>
                    </div>
                    <div class="form-group">
                        <label for="url" class="control-label">URL de Afiliado *</label>
                        <input type="url" class="form-control" id="url" name="url" required>
                        <span class="help-block">La URL completa donde gestionas tus afiliados</span>
                    </div>
                    <div class="form-group">
                        <label for="descripcion" class="control-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        <span class="help-block">Información adicional sobre esta URL</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Guardar URL
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Códigos de Marca -->
<div class="modal fade" id="modalCodigosMarca" tabindex="-1" role="dialog" aria-labelledby="modalCodigosMarcaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalCodigosMarcaLabel">
                    <i class="fa fa-link"></i> Seleccionar URL de Afiliado
                </h4>
            </div>
            <div class="modal-body">
                <div id="contenidoCodigosMarca">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Cargando información del código...</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="url_afiliado_select_marca" class="control-label">Seleccionar URL de Afiliado</label>
                    <select class="form-control" id="url_afiliado_select_marca">
                        <option value="">Selecciona una URI de afiliado...</option>
                        <option value="crear_nueva">+ Crear nueva URL de afiliado</option>
                    </select>
                </div>
                <div class="form-group" id="nueva_url_group_marca" style="display: none;">
                    <label for="nueva_url_nombre_marca" class="control-label">Nombre de la Plataforma *</label>
                    <input type="text" class="form-control" id="nueva_url_nombre_marca" placeholder="Ej: BanaHost, Hotmart">
                </div>
                <div class="form-group" id="nueva_url_group2_marca" style="display: none;">
                    <label for="nueva_url_url_marca" class="control-label">URL de Afiliado *</label>
                    <input type="url" class="form-control" id="nueva_url_url_marca" placeholder="https://manage.banahosting.com/affiliates.php">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="asignarUrlACodigo()">
                    <i class="fa fa-link"></i> Asignar URL
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Códigos Asignados -->
<div class="modal fade" id="modalVerCodigos" tabindex="-1" role="dialog" aria-labelledby="modalVerCodigosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalVerCodigosLabel">
                    <i class="fa fa-list"></i> Códigos Asignados
                </h4>
            </div>
            <div class="modal-body">
                <div id="contenidoCodigos">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Cargando...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Registrar Ingreso -->
<div class="modal fade" id="modalRegistrarIngreso" tabindex="-1" role="dialog" aria-labelledby="modalRegistrarIngresoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalRegistrarIngresoLabel">
                    <i class="fa fa-dollar"></i> Registrar Ingreso
                </h4>
            </div>
            <form id="formRegistrarIngreso" enctype="multipart/form-data">
                <input type="hidden" id="ingreso_url_id" name="url_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="monto" class="control-label">Monto (€) *</label>
                        <input type="number" class="form-control" id="monto" name="monto" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="periodo" class="control-label">Período *</label>
                        <input type="text" class="form-control" id="periodo" name="periodo" placeholder="Ej: Enero 2025, 2025-01" required>
                        <span class="help-block">Mes y año del ingreso</span>
                    </div>
                    <div class="form-group">
                        <label for="captura" class="control-label">Captura de Pantalla</label>
                        <input type="file" class="form-control" id="captura" name="captura" accept="image/*">
                        <span class="help-block">Opcional: Sube una captura como comprobante</span>
                    </div>
                    <div class="form-group">
                        <label for="notas_ingreso" class="control-label">Notas</label>
                        <textarea class="form-control" id="notas_ingreso" name="notas" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Registrar Ingreso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 3 JS ya está incluido en el footer -->
<script>
let urlIdActual = null;
let codigoIdActual = null;
let marcaActual = null;
let codigoIdMarcaActual = null;
let urlsAfiliados = [];

function mostrarModalNuevaUrl() {
    $('#modalNuevaUrl').modal('show');
    document.getElementById('formNuevaUrl').reset();
}

function mostrarModalCodigosMarca(marcaClave, nombreMarca) {
    marcaActual = marcaClave;
    codigoIdMarcaActual = null;
    document.getElementById('modalCodigosMarcaLabel').innerHTML = '<i class="fa fa-link"></i> Seleccionar URL de Afiliado - ' + nombreMarca;
    
    // Resetear formulario
    document.getElementById('url_afiliado_select_marca').value = '';
    document.getElementById('nueva_url_nombre_marca').value = '';
    document.getElementById('nueva_url_url_marca').value = '';
    document.getElementById('nueva_url_group_marca').style.display = 'none';
    document.getElementById('nueva_url_group2_marca').style.display = 'none';
    
    $('#modalCodigosMarca').modal('show');
    
    // Cargar códigos de la marca
    cargarCodigosMarca(marcaClave);
    
    // Cargar URLs de afiliados
    cargarUrlsAfiliados();
}

function cargarCodigosMarca(marcaClave) {
    fetch(`/ajax/afiliados_handler.php?metodo=obtener_codigos_sin_afiliado_por_marca&marca=${encodeURIComponent(marcaClave)}`)
    .then(response => response.json())
    .then(data => {
        const contenido = document.getElementById('contenidoCodigosMarca');
        
        if (data.success && data.codigos.length > 0) {
            // Tomar solo el primer código (único por marca/usuario)
            const codigo = data.codigos[0];
            codigoIdMarcaActual = String(codigo._id);
            
            let html = '<div class="codigo-item" style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1.5rem; background: #f8f9fa; margin-bottom: 1.5rem;">';
            html += '<div class="codigo-info">';
            html += `<div class="codigo-texto"><strong>${codigo.codigo}</strong></div>`;
            if (codigo.descuento) {
                html += `<div class="codigo-descuento">${codigo.descuento}</div>`;
            }
            if (codigo.descripcion) {
                html += `<div class="codigo-descripcion">${codigo.descripcion}</div>`;
            }
            html += `<div class="codigo-stats">`;
            html += `<span class="codigo-clicks"><i class="fa fa-eye"></i> ${codigo.totalclicks} clicks</span>`;
            html += `</div>`;
            html += '</div>';
            html += '</div>';
            
            contenido.innerHTML = html;
        } else {
            contenido.innerHTML = '<div class="text-center"><p>No hay códigos sin asignar para esta marca</p></div>';
            codigoIdMarcaActual = null;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('contenidoCodigosMarca').innerHTML = '<div class="text-center"><p>Error al cargar códigos</p></div>';
        codigoIdMarcaActual = null;
    });
}

function cargarUrlsAfiliados() {
    fetch('/ajax/afiliados_handler.php?metodo=obtener_urls_usuario')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            urlsAfiliados = data.urls;
            const select = document.getElementById('url_afiliado_select_marca');
            
            // Limpiar opciones existentes excepto la primera y "crear nueva"
            while (select.children.length > 2) {
                select.removeChild(select.lastChild);
            }
            
            // Agregar URLs existentes
            data.urls.forEach(url => {
                const option = document.createElement('option');
                option.value = url._id;
                option.textContent = url.nombre_plataforma;
                select.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Error al cargar URLs:', error);
    });
}

function asignarCodigo() {
    const urlAfiliadoId = document.getElementById('url_afiliado_select').value;
    
    if (!urlAfiliadoId) {
        mostrarMensaje('Por favor selecciona una URL de afiliado', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('metodo', 'asignar_codigo');
    formData.append('codigo_id', codigoIdActual);
    formData.append('url_afiliado_id', urlAfiliadoId);
    
    fetch('/ajax/afiliados_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#modalAsignarCodigo').modal('hide');
            mostrarMensaje('Código asignado correctamente', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error de conexión', 'error');
    });
}

function asignarUrlACodigo() {
    const urlAfiliadoSelect = document.getElementById('url_afiliado_select_marca');
    const nuevaUrlNombre = document.getElementById('nueva_url_nombre_marca');
    const nuevaUrlUrl = document.getElementById('nueva_url_url_marca');
    
    if (!codigoIdMarcaActual) {
        mostrarMensaje('No hay código disponible para asignar', 'error');
        return;
    }
    
    if (urlAfiliadoSelect.value === 'crear_nueva') {
        // Crear nueva URL y asignar
        if (!nuevaUrlNombre.value || !nuevaUrlUrl.value) {
            mostrarMensaje('Por favor completa todos los campos para crear la nueva URL', 'error');
            return;
        }
        
        crearNuevaUrlYAsignarCodigo(nuevaUrlNombre.value, nuevaUrlUrl.value);
    } else if (urlAfiliadoSelect.value) {
        // Asignar a URL existente
        asignarCodigoUnico(codigoIdMarcaActual, urlAfiliadoSelect.value);
    } else {
        mostrarMensaje('Por favor selecciona una URL de afiliado', 'error');
    }
}

function asignarCodigoUnico(codigoId, urlAfiliadoId) {
    if (!codigoId || !urlAfiliadoId) {
        mostrarMensaje('Error: Faltan datos para asignar el código', 'error');
        return;
    }
    
    // Asegurar que ambos valores sean strings
    const codigoIdStr = String(codigoId);
    const urlAfiliadoIdStr = String(urlAfiliadoId);
    
    const formData = new FormData();
    formData.append('metodo', 'asignar_codigo');
    formData.append('codigo_id', codigoIdStr);
    formData.append('url_afiliado_id', urlAfiliadoIdStr);
    
    fetch('/ajax/afiliados_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            $('#modalCodigosMarca').modal('hide');
            mostrarMensaje('URL asignada correctamente', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('Error al asignar URL: ' + (data.error || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error de conexión: ' + error.message, 'error');
    });
}

function crearNuevaUrlYAsignarCodigo(nombre, url) {
    if (!codigoIdMarcaActual) {
        mostrarMensaje('No hay código disponible para asignar', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('metodo', 'agregar_url');
    formData.append('nombre_plataforma', nombre);
    formData.append('url', url);
    
    fetch('/ajax/afiliados_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Verificar que url_id existe
            const urlId = data.url_id || data.id;
            if (!urlId) {
                mostrarMensaje('Error: El servidor no devolvió el ID de la URL', 'error');
                return;
            }
            // Asignar el código único a la nueva URL
            asignarCodigoUnico(codigoIdMarcaActual, urlId);
        } else {
            mostrarMensaje('Error al crear la URL: ' + (data.error || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error de conexión: ' + error.message, 'error');
    });
}


function verCodigosAsignados(urlId, nombrePlataforma) {
    urlIdActual = urlId;
    document.getElementById('modalVerCodigosLabel').innerHTML = '<i class="fa fa-list"></i> Códigos de ' + nombrePlataforma;
    $('#modalVerCodigos').modal('show');
    
    // Cargar códigos asignados
    cargarCodigosAsignados(urlId);
}

function cargarCodigosAsignados(urlId) {
    fetch(`/ajax/afiliados_handler.php?metodo=obtener_codigos_asignados&url_afiliado_id=${urlId}`)
    .then(response => response.json())
    .then(data => {
        const contenido = document.getElementById('contenidoCodigos');
        
        if (data.success && data.codigos.length > 0) {
            let html = '<div class="row">';
            data.codigos.forEach(codigo => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">${codigo.marca}</h5>
                                <p class="card-text"><strong>Código:</strong> ${codigo.codigo}</p>
                                ${codigo.descuento ? `<p class="card-text"><strong>Descuento:</strong> ${codigo.descuento}</p>` : ''}
                                <p class="card-text"><strong>Clicks:</strong> ${codigo.totalclicks}</p>
                                <button class="btn btn-sm btn-danger" onclick="desasignarCodigo('${codigo._id}')">
                                    <i class="fa fa-unlink"></i> Desasignar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            contenido.innerHTML = html;
        } else {
            contenido.innerHTML = '<div class="text-center"><p>No hay códigos asignados a esta URL</p></div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('contenidoCodigos').innerHTML = '<div class="text-center"><p>Error al cargar códigos</p></div>';
    });
}

function desasignarCodigo(codigoId) {
    if (confirm('¿Estás seguro de que quieres desasignar este código?')) {
        const formData = new FormData();
        formData.append('metodo', 'desasignar_codigo');
        formData.append('codigo_id', codigoId);
        
        fetch('/ajax/afiliados_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarMensaje('Código desasignado correctamente', 'success');
                cargarCodigosAsignados(urlIdActual);
                setTimeout(() => location.reload(), 2000);
            } else {
                mostrarMensaje('Error: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarMensaje('Error de conexión', 'error');
        });
    }
}

function registrarIngreso(urlId) {
    urlIdActual = urlId;
    $('#modalRegistrarIngreso').modal('show');
    document.getElementById('formRegistrarIngreso').reset();
    document.getElementById('ingreso_url_id').value = urlId;
}

function eliminarUrl(urlId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta URL? Esta acción no se puede deshacer.')) {
        eliminarUrlAjax(urlId);
    }
}

function eliminarUrlAjax(urlId) {
    const formData = new FormData();
    formData.append('metodo', 'eliminar_url');
    formData.append('url_id', urlId);
    
    fetch('/ajax/afiliados_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje('URL eliminada correctamente', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensaje('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error de conexión', 'error');
    });
}

function mostrarMensaje(mensaje, tipo) {
    const alertClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade in position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            ${mensaje}
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            $(alert).alert('close');
        }
    }, 5000);
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Form Nueva URL
    document.getElementById('formNuevaUrl').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('metodo', 'agregar_url');
        
        fetch('/ajax/afiliados_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#modalNuevaUrl').modal('hide');
                mostrarMensaje('URL agregada correctamente', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarMensaje('Error: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarMensaje('Error de conexión', 'error');
        });
    });
    
    // Form Registrar Ingreso
    document.getElementById('formRegistrarIngreso').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('metodo', 'registrar_ingreso');
        
        fetch('/ajax/afiliados_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#modalRegistrarIngreso').modal('hide');
                mostrarMensaje('Ingreso registrado correctamente', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarMensaje('Error: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarMensaje('Error de conexión', 'error');
        });
    });
    
    // Manejar cambio en el select del modal de marcas
    document.getElementById('url_afiliado_select_marca').addEventListener('change', function() {
        const valor = this.value;
        const nuevaUrlGroup = document.getElementById('nueva_url_group_marca');
        const nuevaUrlGroup2 = document.getElementById('nueva_url_group2_marca');
        
        if (valor === 'crear_nueva') {
            nuevaUrlGroup.style.display = 'block';
            nuevaUrlGroup2.style.display = 'block';
        } else {
            nuevaUrlGroup.style.display = 'none';
            nuevaUrlGroup2.style.display = 'none';
        }
    });
});
</script>

<?php
// Incluir el footer estándar
get_footer();
?>