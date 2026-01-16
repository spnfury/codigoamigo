<?php
/**
 * Página de felicitaciones por publicar un código nuevo
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones.php';
require_once __DIR__ . '/../myphp/funciones_modern.php';
require_once __DIR__ . '/../myphp/_header.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: /login");
    exit;
}

// Verificar que hay información del código publicado
if (!isset($_SESSION['codigo_publicado']) || empty($_SESSION['codigo_publicado'])) {
    header("Location: /mis_codigos");
    exit;
}

// Obtener información del código publicado
$codigo_info = $_SESSION['codigo_publicado'];
$codigo_id = $codigo_info['id'];
$marca = $codigo_info['marca'];
$codigo = $codigo_info['codigo'];
$beneficio = $codigo_info['beneficio'];
$descripcion = $codigo_info['descripcion'];

// Obtener información de la marca
$marca_info = get_brand_info($marca);
$nombre_marca = $marca_info['nombre'] ?? ucfirst($marca);
$imagen_marca = $marca_info['imagen'] ?? '';

// Obtener información del usuario
try {
    if (isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) {
        $usuario_obj = getObjectUser('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
        $usuario_info = $usuario_obj ? get_array_de_usuario($usuario_obj) : [];
        $nombre_usuario = $usuario_info['username'] ?? 'Usuario';
    } else {
        $nombre_usuario = 'Usuario';
    }
} catch (Exception $e) {
    $nombre_usuario = 'Usuario';
}

// Generar URLs
$url_codigo = "/de-" . strtolower($marca) . "?codigo=" . $codigo_id;
$url_marca = "/de-" . strtolower($marca);
$url_mis_codigos = "/mis_codigos";
$url_nuevo_codigo = "/nuevo_codigo";

// Limpiar la información del código publicado de la sesión
unset($_SESSION['codigo_publicado']);

get_header_new(
    "¡Código Publicado! - CodigoAmigo.com",
    "¡Felicitaciones! Has publicado un código descuento exitosamente. Comparte tu código y gana dinero con CodigoAmigo.com",
    "Código Publicado - CodigoAmigo.com",
    "Felicitaciones por publicar tu código descuento",
    "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png",
    ""
);
?>

<div class="container-fluid main_entremedio">
    <div class="container text-center bloque_titulo_home">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12 mensaje">
                    <!-- Animación de confeti -->
                    <div class="success-animation">
                        <div class="confetti">🎉</div>
                        <div class="confetti">🎊</div>
                        <div class="confetti">⭐</div>
                        <div class="confetti">🎈</div>
                        <div class="confetti">🎊</div>
                        <div class="confetti">🎉</div>
                    </div>

                    <h1>¡Felicitaciones <?php echo htmlspecialchars($nombre_usuario); ?>! 🎉</h1>
                    <p class="success-subtitle">Has publicado exitosamente un código descuento</p>

                    <!-- Información del código publicado -->
                    <div class="codigo-publicado-card">
                        <?php if($imagen_marca && $imagen_marca !== 'Sin imagen'): ?>
                            <div class="codigo-logo">
                                <img src="<?php echo htmlspecialchars($imagen_marca); ?>" alt="<?php echo htmlspecialchars($nombre_marca); ?>" class="logo-marca-pequeno">
                            </div>
                        <?php endif; ?>

                        <div class="codigo-detalles">
                            <h2><?php echo htmlspecialchars($nombre_marca); ?></h2>
                            <div class="codigo-beneficio">
                                <span class="beneficio-numero"><?php echo htmlspecialchars($beneficio); ?></span>
                                <span class="beneficio-label">de descuento</span>
                            </div>
                            <div class="codigo-valor">
                                <strong>Código:</strong> <span class="codigo-text"><?php echo htmlspecialchars($codigo); ?></span>
                            </div>
                            <p class="codigo-descripcion"><?php echo htmlspecialchars(substr($descripcion, 0, 150)); ?><?php echo strlen($descripcion) > 150 ? '...' : ''; ?></p>
                        </div>
                    </div>

                    <!-- Botón ver código publicado -->
                    <div class="ver-codigo-action" style="text-align: center; margin: 30px 0;">
                        <a href="<?php echo $url_codigo; ?>" class="btn btn-primary btn-large">
                            <i class="fas fa-eye"></i>
                            Ver mi código publicado
                        </a>
                    </div>

                    <!-- Sección para destacar el código -->
                    <div class="destacar-codigo-section">
                        <div class="destacar-header-promo">
                            <h3>⭐ Destaca tu código y aumenta tu visibilidad</h3>
                            <p class="destacar-subtitle">Haz que tu código se destaque entre los demás y obtén más resultados</p>
                        </div>
                        
                        <div class="destacar-beneficios">
                            <div class="beneficio-item">
                                <i class="fas fa-eye"></i>
                                <div class="beneficio-content">
                                    <strong>Más Visitas</strong>
                                    <p>Tu código aparecerá destacado y recibirá más visitas</p>
                                </div>
                            </div>
                            <div class="beneficio-item">
                                <i class="fas fa-chart-line"></i>
                                <div class="beneficio-content">
                                    <strong>Más Impresiones</strong>
                                    <p>Mayor exposición en búsquedas y listados</p>
                                </div>
                            </div>
                            <div class="beneficio-item">
                                <i class="fas fa-mouse-pointer"></i>
                                <div class="beneficio-content">
                                    <strong>Más Clicks</strong>
                                    <p>Incrementa las conversiones y ganancias</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="destacar-cta">
                            <a href="/destacar_codigo?codigo=<?php echo $codigo_id; ?>" class="btn-destacar-now">
                                <i class="fas fa-star"></i>
                                Destacar mi código ahora
                            </a>
                            <button class="btn-link-secondary" onclick="ocultarDestacar()">
                                Lo haré más tarde
                            </button>
                        </div>
                    </div>

                    <!-- Sección de URL de afiliado (menos relevante) -->
                    <div class="asignar-url-section-minimal" style="display: none;">
                        <p class="text-muted" style="font-size: 0.9rem; margin: 20px 0;">
                            <i class="fas fa-link"></i>
                            <a href="/afiliados" style="color: #667eea; text-decoration: none;">Asignar URL de afiliado</a>
                            para revisar tus ingresos
                        </p>
                    </div>

                    <div class="success-actions">
                        <a href="<?php echo $url_marca; ?>" class="btn btn-outline btn-large">
                            <i class="fas fa-store"></i>
                            Ver todos los códigos de <?php echo htmlspecialchars($nombre_marca); ?>
                        </a>
                    </div>

                    <div class="success-tips">
                        <h3>💡 Consejos para que tu código tenga éxito:</h3>
                        <div class="tips-grid">
                            <div class="tip-item">
                                <i class="fas fa-share-alt"></i>
                                <strong>Comparte tu código</strong>
                                <p>Cuantas más personas lo usen, más ganarás</p>
                            </div>
                            <div class="tip-item">
                                <i class="fas fa-users"></i>
                                <strong>Invita a amigos</strong>
                                <p>Cuéntales sobre tu código descuento</p>
                            </div>
                            <div class="tip-item">
                                <i class="fas fa-star"></i>
                                <strong>Calidad importa</strong>
                                <p>Un buen código genera más uso y ganancias</p>
                            </div>
                        </div>
                    </div>

                    <div class="success-next-steps">
                        <h3>¿Qué quieres hacer ahora?</h3>
                        <div class="next-steps-grid">
                            <a href="<?php echo $url_nuevo_codigo; ?>" class="next-step-card">
                                <i class="fas fa-plus-circle"></i>
                                <h4>Publicar otro código</h4>
                                <p>¿Tienes más códigos para compartir?</p>
                            </a>

                            <a href="<?php echo $url_mis_codigos; ?>" class="next-step-card">
                                <i class="fas fa-list"></i>
                                <h4>Ver mis códigos</h4>
                                <p>Administra todos tus códigos publicados</p>
                            </a>

                            <a href="/compartir" class="next-step-card">
                                <i class="fas fa-share"></i>
                                <h4>Compartir en redes</h4>
                                <p>Comparte tu código en redes sociales</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.success-animation {
    position: relative;
    margin-bottom: 30px;
}

.confetti {
    position: absolute;
    font-size: 2rem;
    animation: confetti-fall 3s ease-in-out infinite;
}

.confetti:nth-child(1) { left: 10%; animation-delay: 0s; }
.confetti:nth-child(2) { left: 20%; animation-delay: 0.5s; }
.confetti:nth-child(3) { left: 70%; animation-delay: 1s; }
.confetti:nth-child(4) { left: 80%; animation-delay: 1.5s; }
.confetti:nth-child(5) { left: 40%; animation-delay: 2s; }
.confetti:nth-child(6) { left: 60%; animation-delay: 2.5s; }

@keyframes confetti-fall {
    0% { transform: translateY(-50px) rotate(0deg); opacity: 1; }
    100% { transform: translateY(50px) rotate(360deg); opacity: 0; }
}

.success-subtitle {
    font-size: 1.3rem;
    color: #666;
    margin: 20px 0 40px 0;
    font-weight: 300;
}

.codigo-publicado-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    margin: 40px auto;
    max-width: 600px;
    display: flex;
    align-items: center;
    gap: 25px;
    border: 2px solid #E30613;
    position: relative;
    overflow: hidden;
}

.codigo-publicado-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #E30613, #FF4D4D, #E30613);
}

.codigo-logo {
    flex-shrink: 0;
}

.logo-marca-pequeno {
    width: 80px;
    height: 60px;
    object-fit: contain;
    border-radius: 8px;
    background: white;
    padding: 8px;
    border: 1px solid #e2e8f0;
}

.codigo-detalles {
    flex: 1;
    text-align: left;
}

.codigo-detalles h2 {
    color: #1e293b;
    font-size: 1.8rem;
    margin-bottom: 15px;
    font-weight: 700;
}

.codigo-beneficio {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 15px;
}

.beneficio-numero {
    font-size: 2rem;
    font-weight: 800;
    color: #E30613;
}

.beneficio-label {
    color: #64748b;
    font-size: 1rem;
}

.codigo-valor {
    margin-bottom: 15px;
}

.codigo-text {
    background: #f1f5f9;
    padding: 4px 8px;
    border-radius: 4px;
    font-family: monospace;
    font-weight: 600;
    color: #1e293b;
    border: 1px solid #e2e8f0;
}

.codigo-descripcion {
    color: #64748b;
    font-size: 0.95rem;
    line-height: 1.5;
    margin: 0;
}

.success-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin: 40px 0;
    flex-wrap: wrap;
}

.btn-large {
    padding: 15px 30px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-large:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(227, 6, 19, 0.3);
}

.success-tips {
    margin: 50px 0;
}

.success-tips h3 {
    color: #1e293b;
    font-size: 1.5rem;
    margin-bottom: 25px;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 25px;
}

.tip-item {
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    border-left: 4px solid #E30613;
}

.tip-item i {
    font-size: 2rem;
    color: #E30613;
    margin-bottom: 15px;
    display: block;
}

.tip-item strong {
    color: #1e293b;
    display: block;
    margin-bottom: 8px;
    font-size: 1.1rem;
}

.tip-item p {
    color: #64748b;
    margin: 0;
    font-size: 0.95rem;
}

.success-next-steps {
    margin: 50px 0;
}

.success-next-steps h3 {
    color: #1e293b;
    font-size: 1.5rem;
    margin-bottom: 25px;
}

.next-steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 25px;
}

.next-step-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    text-decoration: none;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.next-step-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    border-color: #E30613;
}

.next-step-card i {
    font-size: 2.5rem;
    color: #E30613;
    margin-bottom: 15px;
    display: block;
}

.next-step-card h4 {
    color: #1e293b;
    font-size: 1.2rem;
    margin-bottom: 8px;
    font-weight: 600;
}

.next-step-card p {
    color: #64748b;
    margin: 0;
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
    .codigo-publicado-card {
        flex-direction: column;
        text-align: center;
        padding: 20px;
        margin: 20px auto;
    }

    .codigo-detalles {
        text-align: center;
    }

    .codigo-beneficio {
        justify-content: center;
    }

    .success-actions {
        flex-direction: column;
        align-items: center;
    }

    .btn-large {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }

    .next-steps-grid {
        grid-template-columns: 1fr;
    }

    .tips-grid {
        grid-template-columns: 1fr;
    }

    .confetti {
        display: none;
    }

    .destacar-codigo-section {
        padding: 30px 20px;
        margin: 30px auto;
    }

    .destacar-header-promo h3 {
        font-size: 1.5rem;
    }

    .destacar-subtitle {
        font-size: 1rem;
    }

    .destacar-beneficios {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .beneficio-item {
        padding: 15px;
    }

    .btn-destacar-now {
        padding: 15px 30px;
        font-size: 1.1rem;
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
}

/* Estilos para la sección de destacar código */
.destacar-codigo-section {
    background: linear-gradient(135deg, #E30613 0%, #f7931e 100%);
    border-radius: 20px;
    padding: 40px 30px;
    margin: 40px auto;
    max-width: 800px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(227, 6, 19, 0.3);
    position: relative;
    overflow: hidden;
}

.destacar-codigo-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.destacar-header-promo {
    margin-bottom: 30px;
    position: relative;
    z-index: 1;
}

.destacar-header-promo h3 {
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.destacar-subtitle {
    color: rgba(255,255,255,0.95);
    font-size: 1.1rem;
    margin: 0;
}

.destacar-beneficios {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
    position: relative;
    z-index: 1;
}

.beneficio-item {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.3s ease;
}

.beneficio-item:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

.beneficio-item i {
    font-size: 2rem;
    color: white;
    flex-shrink: 0;
    margin-top: 5px;
}

.beneficio-content {
    text-align: left;
    flex: 1;
}

.beneficio-content strong {
    color: white;
    font-size: 1.1rem;
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.beneficio-content p {
    color: rgba(255,255,255,0.9);
    font-size: 0.9rem;
    margin: 0;
    line-height: 1.4;
}

.destacar-cta {
    margin-top: 30px;
    position: relative;
    z-index: 1;
}

.btn-destacar-now {
    background: white;
    color: #E30613;
    padding: 18px 40px;
    font-size: 1.2rem;
    font-weight: 700;
    border-radius: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    border: 2px solid transparent;
}

.btn-destacar-now:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    background: #fff;
    color: #E30613;
}

.btn-destacar-now i {
    font-size: 1.3rem;
}

.btn-link-secondary {
    display: block;
    margin-top: 15px;
    background: transparent;
    border: none;
    color: rgba(255,255,255,0.8);
    font-size: 0.95rem;
    cursor: pointer;
    text-decoration: underline;
    transition: color 0.3s ease;
}

.btn-link-secondary:hover {
    color: white;
}

/* Estilos para la sección de asignación de URL (minimal) */
.asignar-url-section-minimal {
    text-align: center;
    margin: 20px 0;
}

.asignar-url-section-minimal a:hover {
    text-decoration: underline !important;
}
</style>

<!-- Modal para Asignar URL de Afiliado -->
<div class="modal fade" id="modalAsignarUrl" tabindex="-1" role="dialog" aria-labelledby="modalAsignarUrlLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalAsignarUrlLabel">
                    <i class="fa fa-link"></i> Asignar URL de Afiliado
                </h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="control-label">Código a Asignar</label>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <strong><?php echo htmlspecialchars($nombre_marca); ?></strong><br>
                        <span><?php echo htmlspecialchars($codigo); ?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="url_afiliado_select" class="control-label">Seleccionar URL de Afiliado</label>
                    <select class="form-control" id="url_afiliado_select">
                        <option value="">Selecciona una URL de afiliado...</option>
                        <option value="crear_nueva">+ Crear nueva URL de afiliado</option>
                    </select>
                </div>
                <div class="form-group" id="nueva_url_group" style="display: none;">
                    <label for="nueva_url_nombre" class="control-label">Nombre de la Plataforma *</label>
                    <input type="text" class="form-control" id="nueva_url_nombre" placeholder="Ej: BanaHost, Hotmart">
                </div>
                <div class="form-group" id="nueva_url_group2" style="display: none;">
                    <label for="nueva_url_url" class="control-label">URL de Afiliado *</label>
                    <input type="url" class="form-control" id="nueva_url_url" placeholder="https://manage.banahosting.com/affiliates.php">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="asignarUrlAlCodigo()">
                    <i class="fa fa-link"></i> Asignar URL
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let codigoId = '<?php echo $codigo_id; ?>';
let urlsAfiliados = [];

// Cargar URLs de afiliados existentes
document.addEventListener('DOMContentLoaded', function() {
    cargarUrlsAfiliados();
});

function cargarUrlsAfiliados() {
    fetch('/ajax/afiliados_handler.php?metodo=obtener_urls_usuario')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            urlsAfiliados = data.urls;
            const select = document.getElementById('url_afiliado_select');
            
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

function mostrarModalAsignarUrl() {
    $('#modalAsignarUrl').modal('show');
}

function saltarAsignacion() {
    // Ocultar la sección de asignación (ya no existe, pero mantenemos por compatibilidad)
    const section = document.querySelector('.asignar-url-section');
    if (section) {
        section.style.display = 'none';
    }
}

function ocultarDestacar() {
    // Ocultar la sección de destacar
    const destacarSection = document.querySelector('.destacar-codigo-section');
    if (destacarSection) {
        destacarSection.style.display = 'none';
    }
    // Mostrar la sección minimal de URL de afiliado
    const minimalSection = document.querySelector('.asignar-url-section-minimal');
    if (minimalSection) {
        minimalSection.style.display = 'block';
    }
}

// Manejar cambio en el select
document.getElementById('url_afiliado_select').addEventListener('change', function() {
    const valor = this.value;
    const nuevaUrlGroup = document.getElementById('nueva_url_group');
    const nuevaUrlGroup2 = document.getElementById('nueva_url_group2');
    
    if (valor === 'crear_nueva') {
        nuevaUrlGroup.style.display = 'block';
        nuevaUrlGroup2.style.display = 'block';
    } else {
        nuevaUrlGroup.style.display = 'none';
        nuevaUrlGroup2.style.display = 'none';
    }
});

function asignarUrlAlCodigo() {
    const urlAfiliadoSelect = document.getElementById('url_afiliado_select');
    const nuevaUrlNombre = document.getElementById('nueva_url_nombre');
    const nuevaUrlUrl = document.getElementById('nueva_url_url');
    
    if (urlAfiliadoSelect.value === 'crear_nueva') {
        // Crear nueva URL y asignar
        if (!nuevaUrlNombre.value || !nuevaUrlUrl.value) {
            alert('Por favor completa todos los campos para crear la nueva URL');
            return;
        }
        
        crearNuevaUrlYAsignar(nuevaUrlNombre.value, nuevaUrlUrl.value);
    } else if (urlAfiliadoSelect.value) {
        // Asignar a URL existente
        asignarCodigoAAfiliado(urlAfiliadoSelect.value);
    } else {
        alert('Por favor selecciona una URL de afiliado');
    }
}

function crearNuevaUrlYAsignar(nombre, url) {
    const formData = new FormData();
    formData.append('metodo', 'agregar_url');
    formData.append('nombre_plataforma', nombre);
    formData.append('url', url);
    
    fetch('/ajax/afiliados_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ahora asignar el código a la nueva URL
            asignarCodigoAAfiliado(data.url_id);
        } else {
            alert('Error al crear la URL: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

function asignarCodigoAAfiliado(urlAfiliadoId) {
    const formData = new FormData();
    formData.append('metodo', 'asignar_codigo');
    formData.append('codigo_id', codigoId);
    formData.append('url_afiliado_id', urlAfiliadoId);
    
    fetch('/ajax/afiliados_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#modalAsignarUrl').modal('hide');
            
            // Mostrar mensaje de éxito
            const asignarSection = document.querySelector('.asignar-url-section');
            asignarSection.innerHTML = `
                <div style="text-align: center;">
                    <i class="fa fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                    <h3>¡URL asignada correctamente!</h3>
                    <p>Ya sabes dónde revisar tus ingresos de este código</p>
                    <a href="/afiliados" class="btn btn-outline btn-large">
                        <i class="fa fa-cog"></i>
                        Gestionar Afiliados
                    </a>
                </div>
            `;
        } else {
            alert('Error al asignar la URL: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}
</script>

<?php
get_footer();
?>
