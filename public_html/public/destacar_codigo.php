<?php
include_once __DIR__ . '/../inc/logger.php';
// Al inicio del archivo, antes de cargar el header
$anula_adsense = true; // Esta variable será leída por el header para no mostrar Adsense

// Definir variables globales necesarias
if (strstr($_SERVER['SERVER_NAME'], "dev.")) {
    $GLOBALS["website"] = "https://dev.codigoamigo.com/";
} else {
    $GLOBALS["website"] = "https://www.codigoamigo.com/";
}

$http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$GLOBALS["actual_url"] = $http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

// Verificar que el usuario esté logueado.
// Se pasa la URL actual como redirect: sin él, quien llega desde un email de
// campaña con la sesión caducada se logueaba y aterrizaba en el home — la
// intención de compra se perdía justo antes de pagar.
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    $volver = '/destacar_codigo' . (!empty($_GET['codigo']) ? '?codigo=' . urlencode($_GET['codigo']) : '');
    header("Location: /login?redirect=" . urlencode($volver));
    exit;
}

// Obtener el saldo del usuario
$saldo_usuario = 0;
try {
    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
    if ($usuario && isset($usuario['saldo'])) {
        $saldo_usuario = (float) $usuario['saldo'];
    }
} catch (Exception $e) {
    log_error("Error obteniendo saldo del usuario: " . $e->getMessage());
}

// Obtener la información del código
$codigo_id = $_REQUEST["codigo"];
if (!$codigo_id) {
    header("Location: /mis-anuncios");
    exit;
}

// Mismo caso que destaca.php: parámetro inválido (enlace viejo, código
// borrado, bot) rompe la construcción del ObjectId con string no-hex —
// tratar como "no encontrado" en vez de reventar la página del botón
// principal de pago.
try {
    $obj_id_codigo = new \MongoDB\BSON\ObjectId($codigo_id);
} catch (\Throwable $e) {
    header("Location: /mis-anuncios");
    exit;
}

// Defensivo: garantizar que las funciones de negocio están cargadas antes de usarlas.
// myphp/funciones.php está envuelto en un guard global if(!function_exists('getFechaActualCorregida'))
// que, en ciertos órdenes de include, se salta el archivo entero y deja getCodeByID() sin definir.
if (!function_exists('getCodeByID')) {
    require_once __DIR__ . '/../myphp/funciones.php';
}

$codigo = getCodeByID($obj_id_codigo);

// Verificar que el código pertenece al usuario
if (!$codigo || $codigo["id_usuario"] != $_SESSION["user_id"]) {
    // Log del intento de acceso no autorizado
    log_warning("Acceso no autorizado a destacar código - Usuario: " . $_SESSION["user_id"] . ", Código: " . $codigo_id . ", Propietario: " . ($codigo ? $codigo["id_usuario"] : "No encontrado"));
    
    // Mostrar mensaje de error y redirigir
    $_SESSION['error_message'] = 'No tienes permisos para destacar este código.';
    header("Location: /mis-anuncios");
    exit;
}

$marca = getObjectMarca('nombre_clave', $codigo["marca"]);
$marca_nombre = htmlspecialchars($marca['nombre'] ?? $codigo['marca']);

$title = "Destacar código " . $marca_nombre . " - CodigoAmigo.com";
$description = "Destaca tu código de descuento para " . $marca_nombre . " y obtén mayor visibilidad";

get_header_modern($title, $description);

// Configuración de Stripe - secret key desde helper; publishable + SKUs cambian según admin sandbox
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/stripe.php';
$stripe_live_secret_key = get_stripe_secret_key(null, $_SESSION['user_id'] ?? null);
$is_sandbox_admin = in_array($_SESSION['user_id'] ?? null, ['639899bc6321ee0d0e4010d2', '58bd851da54e295b8b52f702', '5db1af3a2f55c82b47342172'], true);
if ($is_sandbox_admin) {
    $stripe_live_publishable_key = "pk_test_yU61XXQMBvqVt4Ah9XD5uk6V";
    $sku_destacado_normal = 'sku_GJUimQssXxo3yB';
    $sku_destacado_super = 'sku_GJWab8ArF8ZM6t';
} else {
    $stripe_live_publishable_key = "pk_live_HvgqlImI22optTnSvHKFKDiG00VQ0EZdE9";
    $sku_destacado_normal = 'sku_H6K4Pf8TXO6w58'; // Normal 0.99€
    $sku_destacado_super = 'sku_H6K69kuQGeL0pV'; // Super 3.99€
}

// Check if this brand has a Super Landing
$has_super_landing = false;
$super_landing_title = '';
$path_functions = realpath(__DIR__ . '/../myphp/funciones.php');
$path_sl = realpath(__DIR__ . '/../myphp/_super_landing_functions.php');

if ($path_sl && file_exists($path_sl)) {
    // Use include_once to prevent redeclaration errors if functions.php was already loaded by header
    include_once $path_functions;
    include_once $path_sl;
}
if (function_exists('get_active_super_landings')) {
    $all_sl = get_active_super_landings(50);
    foreach ($all_sl as $sl) {
        if (isset($sl['linked_brand_slugs'])) {
            $brand_slugs = is_object($sl['linked_brand_slugs']) ? iterator_to_array($sl['linked_brand_slugs']) : $sl['linked_brand_slugs'];
            $match = in_array($codigo['marca'], $brand_slugs);
            if ($match) {
                $has_super_landing = true;
                $super_landing_title = $sl['title'] ?? 'Guía Oficial';
                $super_landing_slug = $sl['slug'] ?? '';
                break;
            }
        }
    }
}
?>

<style>
/* Estilos modernos para la página de destacar */
.destacar-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #1a1a1a;
    min-height: 100vh;
}

.destacar-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 20px;
    background: linear-gradient(135deg, #E30613 0%, #f7931e 100%);
    border-radius: 20px;
    color: white;
}

.destacar-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.destacar-header p {
    font-size: 1.2rem;
    opacity: 0.9;
}

.code-preview {
    background: #2c2c2c;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 40px;
    border: 2px solid #404040;
}

.code-preview-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.code-preview-logo {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    margin-right: 20px;
    background: #E30613;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.code-preview-info h3 {
    color: white;
    font-size: 1.5rem;
    margin: 0 0 5px 0;
}

.code-preview-info p {
    color: #ccc;
    margin: 0;
}

.code-preview-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.code-detail-item {
    background: #404040;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
}

.code-detail-item .label {
    color: #999;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.code-detail-item .value {
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
}

.pricing-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.pricing-card {
    background: #2c2c2c;
    border-radius: 20px;
    padding: 30px;
    text-align: center;
    border: 2px solid #404040;
    transition: all 0.3s ease;
    position: relative;
}

.pricing-card:hover {
    border-color: #E30613;
    transform: translateY(-5px);
}

.pricing-card.featured {
    border-color: #E30613;
    background: linear-gradient(135deg, #2c2c2c 0%, #3a3a3a 100%);
}

.pricing-card.featured::before {
    content: "MÁS POPULAR";
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: #E30613;
    color: white;
    padding: 5px 20px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}

.pricing-header h3 {
    color: white;
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.pricing-price {
    font-size: 3rem;
    font-weight: 700;
    color: #E30613;
    margin-bottom: 20px;
}

.pricing-features {
    list-style: none;
    padding: 0;
    margin-bottom: 30px;
}

.pricing-features li {
    color: #ccc;
    padding: 8px 0;
    border-bottom: 1px solid #404040;
}

.pricing-features li:last-child {
    border-bottom: none;
}

.pricing-features li i {
    color: #E30613;
    margin-right: 10px;
}

.destacar-btn {
    background: #E30613;
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.destacar-btn:hover {
    background: #C40510;
    transform: translateY(-2px);
}

.destacar-btn.loading {
    background: #666;
    cursor: not-allowed;
    transform: none;
}

.destacar-btn:disabled {
    background: #666;
    cursor: not-allowed;
    opacity: 0.7;
}

.benefits-section {
    background: #2c2c2c;
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 40px;
}

.benefits-section h2 {
    color: white;
    text-align: center;
    margin-bottom: 30px;
    font-size: 2rem;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.benefit-item {
    text-align: center;
    padding: 20px;
}

.benefit-item i {
    font-size: 3rem;
    color: #E30613;
    margin-bottom: 15px;
}

.benefit-item h3 {
    color: white;
    margin-bottom: 10px;
}

.benefit-item p {
    color: #ccc;
    line-height: 1.6;
}

.back-btn {
    background: #404040;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    margin-bottom: 30px;
}

.back-btn:hover {
    background: #555;
    color: white;
    text-decoration: none;
}

@media (max-width: 768px) {
    .destacar-container {
        padding: 10px;
    }
    
    .destacar-header h1 {
        font-size: 2rem;
    }
    
    .pricing-section {
        grid-template-columns: 1fr;
    }
    
    .code-preview-details {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="destacar-container">

    <div class="destacar-header">
        <h1><i class="fas fa-star"></i> Destacar tu código de <?php echo htmlspecialchars($marca_nombre); ?></h1>
        <p>¡Aumenta tus ganancias y haz que tu código sea el primero que vean los usuarios!</p>
    </div>

    <!-- Opciones de destacar -->
    <div class="pricing-section">
        <div class="pricing-card">
            <div class="pricing-header">
                <h3>Destacado Normal</h3>
            </div>
            <div class="pricing-price">0,99€</div>
            <ul class="pricing-features">
                <li><i class="fas fa-check"></i> Destacado durante <strong>7 días</strong> en la página de la marca</li>
                <li><i class="fas fa-check"></i> Badge "Destacado" visible</li>
                <li><i class="fas fa-check"></i> Posición prioritaria frente a códigos normales</li>
            </ul>
            <button class="destacar-btn" id="destacar-normal" data-price="99" data-sku="<?php echo $sku_destacado_normal; ?>" data-tipo="normal">
                <i class="fas fa-star"></i> Destacar 7 días
            </button>
        </div>

        <div class="pricing-card featured">
            <div class="pricing-header">
                <h3>Destacado Super</h3>
            </div>
            <div class="pricing-price">3,99€</div>
            <ul class="pricing-features">
                <li><i class="fas fa-check"></i> Destacado durante <strong>14 días</strong> en la marca</li>
                <li><i class="fas fa-check"></i> Badge dorado "Super Destacado"</li>
                <li><i class="fas fa-check"></i> Prioridad sobre destacados normales</li>
                <li><i class="fas fa-check"></i> Aparece en el carrusel de la página principal</li>
                <li><i class="fas fa-random"></i> Rotación inteligente: máxima visibilidad al comprar, visibilidad garantizada toda la semana</li>
            </ul>
            <button class="destacar-btn" id="destacar-super" data-price="399" data-sku="<?php echo $sku_destacado_super; ?>" data-tipo="super">
                <i class="fas fa-crown"></i> Destacar 14 días
            </button>
        </div>
        
        <?php if ($has_super_landing): ?>
        <div class="pricing-card super-landing-card" style="border-color: #FFD700;">
            <div class="super-landing-badge" style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #FFD700, #E30613); color: #333; padding: 5px 20px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; box-shadow: 0 4px 10px rgba(255, 215, 0, 0.4);">
                ★ GUÍA OFICIAL ★
            </div>
            <div class="pricing-header">
                <h3>Super Destacado en Guías</h3>
                <p style="color: #ccc; font-size: 0.9rem; margin: 10px 0;">Aparece en: <a href="/guias/<?php echo $super_landing_slug; ?>" target="_blank" style="color: #FFD700; text-decoration: underline; font-weight: bold;"><?php echo htmlspecialchars($super_landing_title); ?></a></p>
            </div>
            <div class="pricing-price" style="color: #FFD700;">9,99€</div>
            <ul class="pricing-features">
                <li><i class="fas fa-check"></i> Posición #1 en la Guía Oficial</li>
                <li><i class="fas fa-check"></i> Sección "Recomendados por Editores"</li>
                <li><i class="fas fa-check"></i> Miles de visitas mensuales</li>
                <li><i class="fas fa-check"></i> Carrusel si hay varios (visibilidad rotativa)</li>
                <li><i class="fas fa-check"></i> Duración: 30 días garantizados</li>
            </ul>
            <button class="destacar-btn" id="destacar-guia" data-price="999" data-sku="super_landing_999" data-tipo="super_landing" style="background: linear-gradient(135deg, #FFD700, #E30613); color: #333;">
                <i class="fas fa-trophy"></i> Destacar en Guía
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Beneficios -->
    <div class="benefits-section">
        <h2>¿Por qué destacar tu código?</h2>
        <div class="benefits-grid">
            <div class="benefit-item">
                <i class="fas fa-eye"></i>
                <h3>Mayor Visibilidad</h3>
                <p>Tu código aparecerá en la primera posición de la marca, aumentando las posibilidades de ser usado.</p>
            </div>
            <div class="benefit-item">
                <i class="fas fa-star"></i>
                <h3>Badge Destacado</h3>
                <p>Los usuarios verán claramente que tu código está destacado y es confiable.</p>
            </div>
            <div class="benefit-item">
                <i class="fas fa-chart-line"></i>
                <h3>Más Clicks</h3>
                <p>Los códigos destacados reciben hasta 5 veces más clicks que los normales.</p>
            </div>
            <div class="benefit-item">
                <i class="fas fa-sync-alt"></i>
                <h3>Sistema Rotativo Justo</h3>
                <p>Nuestro carrusel de inicio rota los códigos súper destacados para que nunca te quedes estancado debajo de otros usuarios.</p>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Modal de pago - Sistema Overlay Custom (igual que login) -->
<div class="login-modal-overlay" id="modalPago" style="z-index: 99999;">
    <div class="login-modal-content" style="max-width: 600px; background: #2c2c2c; border: 1px solid #404040; color: white;">
        <div class="login-modal-header" style="background: transparent; border-bottom: 1px solid #404040;">
            <h3 style="color: white;"><i class="fas fa-credit-card"></i> Elegir método de pago</h3>
            <button class="login-modal-close" style="color: white; opacity: 0.7;">&times;</button>
        </div>
        
        <div class="login-modal-body" style="padding: 30px;">
            <!-- Información del destacado -->
            <div class="destacado-info" style="background: #404040; padding: 25px; border-radius: 10px; margin-bottom: 25px; border-left: 4px solid #E30613;">
                <h6 style="color: white; margin-bottom: 15px; font-weight: 600; font-size: 1.3rem;" id="destacadoTitulo">Destacado Normal</h6>
                <ul id="destacadoCaracteristicas" style="list-style: none; padding: 0; margin: 0;">
                    <li style="color: #ccc; padding: 8px 0; border-bottom: 1px solid #555; display: flex; align-items: center;">
                        <i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i>
                        <span>Aparece en primera posición</span>
                    </li>
                    <li style="color: #ccc; padding: 8px 0; border-bottom: 1px solid #555; display: flex; align-items: center;">
                        <i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i>
                        <span id="badgeInfo">Badge "Destacado" visible</span>
                    </li>
                    <li style="color: #ccc; padding: 8px 0; border-bottom: 1px solid #555; display: flex; align-items: center;">
                        <i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i>
                        <span>Mayor visibilidad en la marca</span>
                    </li>
                    <li style="color: #ccc; padding: 8px 0; border-bottom: 1px solid #555; display: none; align-items: center;" id="extraFeature">
                        <i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i>
                        <span>Aparece en página principal</span>
                    </li>
                    <li style="color: #ccc; padding: 8px 0; display: flex; align-items: center;">
                        <i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i>
                        <span>Opción de auto-renovación desde tu saldo</span>
                    </li>
                </ul>
            </div>

            <!-- Opciones de pago -->
            <div class="payment-options">
                <h6 style="color: white; margin-bottom: 15px; font-weight: 600; font-size: 1.2rem;">Selecciona tu método de pago:</h6>
                
                <!-- Opción 1: Pago con tarjeta -->
                <div class="payment-option" style="background: #404040; border: 2px solid #555; border-radius: 10px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" id="opcionTarjeta">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center;">
                            <i class="fas fa-credit-card" style="font-size: 1.5rem; color: #E30613; margin-right: 15px;"></i>
                            <div>
                                <h6 style="color: white; margin: 0; font-weight: 600; font-size: 1.1rem;">Pagar con tarjeta</h6>
                                <p style="color: #ccc; margin: 5px 0 0 0; font-size: 1rem;">Visa, Mastercard, American Express</p>
                            </div>
                        </div>
                        <i class="fas fa-arrow-right" style="color: #E30613;"></i>
                    </div>
                </div>

                <!-- Opción 2: Pago con saldo -->
                <div class="payment-option" style="background: #404040; border: 2px solid #555; border-radius: 10px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" id="opcionSaldo">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center;">
                            <i class="fas fa-coins" style="font-size: 1.5rem; color: #E30613; margin-right: 15px;"></i>
                            <div>
                                <h6 style="color: white; margin: 0; font-weight: 600; font-size: 1.1rem;">Pagar con saldo</h6>
                                <p style="color: #ccc; margin: 5px 0 0 0; font-size: 1rem; color: #E30613;" id="saldoDisponible">Usar tu saldo disponible</p>
                            </div>
                        </div>
                        <i class="fas fa-arrow-right" style="color: #E30613;"></i>
                    </div>
                </div>
            </div>

            <!-- Auto-renovar -->
            <div style="background: #353535; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #454545;">
                <label style="display: flex; align-items: center; cursor: pointer; margin: 0; gap: 12px;" for="autoRenovarCheck">
                    <input type="checkbox" id="autoRenovarCheck" style="width: 20px; height: 20px; accent-color: #28a745; cursor: pointer;">
                    <div>
                        <span style="color: white; font-weight: 600; font-size: 1rem;">🔄 Auto-renovar al expirar</span>
                        <p style="color: #aaa; font-size: 0.85rem; margin: 4px 0 0 0;">Se renovará automáticamente desde tu saldo cuando expire</p>
                    </div>
                </label>
            </div>

            <!-- Saldo del usuario y precio -->
            <div class="saldo-info" style="background: #1a1a1a; padding: 15px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #E30613;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: white; font-weight: 600; font-size: 1.2rem;">
                        <i class="fas fa-tag" style="margin-right: 8px; color: #E30613;"></i>
                        Precio:
                    </span>
                    <span style="color: #E30613; font-size: 1.5rem; font-weight: 700;" id="destacadoPrecio">0,99€</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>

<script src="https://js.stripe.com/v3/"></script>
<script>
var stripe = Stripe('<?php echo $stripe_live_publishable_key; ?>');
var codigoId = '<?php echo $codigo_id; ?>';
var saldoUsuario = <?php echo $saldo_usuario; ?>; // Saldo cargado desde PHP

// Cargar saldo del usuario al cargar la página
$(document).ready(function() {
    console.log("Sistema de destacar inicializado");
    
    // Verify custom modal logic
    const modal = document.getElementById('modalPago');
    const closeBtn = modal.querySelector('.login-modal-close');
    
    // Close on button click
    closeBtn.onclick = function() {
        closeModal();
    };
    
    // Close on outside click
    modal.onclick = function(e) {
        if (e.target === modal) {
            closeModal();
        }
    };
    
    configurarBotones();
    configurarModalHandlers();
});

// Helper to open modal
function openModal() {
    const modal = document.getElementById('modalPago');
    modal.style.display = 'flex';
    // Small delay to allow display change before adding active class (for transition)
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);
    document.body.style.overflow = 'hidden';
}

// Helper to close modal
function closeModal() {
    const modal = document.getElementById('modalPago');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

// El saldo ya se carga desde PHP, no necesitamos AJAX

// Función para configurar los botones de destacar
function configurarBotones() {

    $('#destacar-normal, #destacar-super, #destacar-guia').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log("Click en destacar: " + $(this).data('tipo'));
        
        var tipo = $(this).data('tipo');
        var precio = $(this).data('price');
        var sku = $(this).data('sku');
        
        // Configurar modal con la información del destacado
        configurarModalDestacado(tipo, precio, sku);
        
        // Mostrar modal usando custom logic
        openModal();
    });
}


// Función para configurar el modal con la información del destacado
function configurarModalDestacado(tipo, precio, sku) {
    var titulo = tipo === 'normal' ? 'Destacado Normal' : (tipo === 'super_landing' ? 'Super Destacado en Guía' : 'Destacado Super');
    var precioFormateado = (precio / 100).toFixed(2) + '€';
    
    $('#destacadoTitulo').text(titulo);
    $('#destacadoPrecio').text(precioFormateado);
    
    // Actualizar características según el tipo
    if (tipo === 'normal') {
        $('#badgeInfo').text('Badge "Destacado" visible');
        $('#extraFeature').css('display', 'none');
    } else if (tipo === 'super_landing') {
        $('#badgeInfo').text('Badge "Destacado" dorado + Posición #1 en Guía');
        $('#extraFeature').html('<i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i><span>Sección "Recomendados por Editores"</span>').css('display', 'flex');
    } else {
        $('#badgeInfo').text('Badge "Destacado" dorado');
        $('#extraFeature').html('<i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i><span>Aparece en página principal</span>').css('display', 'flex');
    }
    
    // Guardar datos para usar en el pago
    $('#modalPago').data('tipo', tipo);
    $('#modalPago').data('precio', precio);
    $('#modalPago').data('sku', sku);
    
    // Actualizar opción de saldo
    actualizarOpcionSaldo();
}

// Función para actualizar la opción de pago con saldo
function actualizarOpcionSaldo() {
    // Verificar si el modal tiene datos, si no usar defaults o salir
    var precio = $('#modalPago').data('precio');
    if (!precio) return;
    
    var precioEuros = precio / 100;
    
    if (saldoUsuario >= precioEuros) {
        var saldoRestante = saldoUsuario - precioEuros;
        $('#opcionSaldo').removeClass('disabled').css('opacity', '1');
        $('#saldoDisponible').text(saldoRestante.toFixed(2) + '€ restantes');
        $('#opcionSaldo').find('h6').css('color', 'white');
    } else {
        $('#opcionSaldo').addClass('disabled').css('opacity', '0.5');
        $('#saldoDisponible').text('Saldo insuficiente - Necesitas ' + (precioEuros - saldoUsuario).toFixed(2) + '€ más');
        $('#opcionSaldo').find('h6').css('color', '#666');
    }
}

// Función para configurar handlers del modal
function configurarModalHandlers() {
    // Efectos hover para las opciones de pago
    $('.payment-option').hover(
        function() {
            if (!$(this).hasClass('disabled')) {
                $(this).css('border-color', '#E30613');
                $(this).css('transform', 'translateY(-2px)');
            }
        },
        function() {
            if (!$(this).hasClass('disabled')) {
                $(this).css('border-color', '#555');
                $(this).css('transform', 'translateY(0)');
            }
        }
    );
    
    // Click en opción de tarjeta
    $('#opcionTarjeta').off('click').on('click', function() {
        if (!$(this).hasClass('disabled')) {
            procesarPagoConTarjeta();
        }
    });
    
    // Click en opción de saldo
    $('#opcionSaldo').off('click').on('click', function() {
        if (!$(this).hasClass('disabled')) {
            procesarPagoConSaldo();
        }
    });
}

// Función para procesar pago con tarjeta
function procesarPagoConTarjeta() {
    var tipo = $('#modalPago').data('tipo');
    var sku = $('#modalPago').data('sku');
    
    closeModal();
    
    // Mostrar loading en el botón correspondiente
    var boton = tipo === 'normal' ? $('#destacar-normal') : (tipo === 'super_landing' ? $('#destacar-guia') : $('#destacar-super'));
    boton.addClass('loading').html('<i class="fas fa-spinner fa-spin"></i> Procesando...').prop('disabled', true);
    
    // Crear sesión en el servidor con metadata completa
    $.ajax({
        url: '/crear_sesion_destacar.php',
        method: 'POST',
        dataType: 'json',
        data: {
            codigo_id: codigoId,
            tipo: tipo,
            sku: sku,
            auto_renovar: $('#autoRenovarCheck').is(':checked') ? '1' : '0'
        },
        success: function(response) {
            if (response.error) {
                var btnHtml = tipo === 'normal' ? '<i class="fas fa-star"></i> Destacar Normal' : (tipo === 'super_landing' ? '<i class="fas fa-trophy"></i> Destacar en Guía' : '<i class="fas fa-crown"></i> Destacar Super');
                boton.removeClass('loading').html(btnHtml).prop('disabled', false);
                alert('Error: ' + response.error);
            } else {
                // Redirigir a Stripe Checkout
                window.location.href = response.url;
            }
        },
        error: function(xhr, status, error) {
            var btnHtml = tipo === 'normal' ? '<i class="fas fa-star"></i> Destacar Normal' : (tipo === 'super_landing' ? '<i class="fas fa-trophy"></i> Destacar en Guía' : '<i class="fas fa-crown"></i> Destacar Super');
            boton.removeClass('loading').html(btnHtml).prop('disabled', false);
            alert('Error al crear la sesión de pago. Por favor, intenta de nuevo.');
        }
    });
}

// Función para procesar pago con saldo
function procesarPagoConSaldo() {
    var tipo = $('#modalPago').data('tipo');
    var precio = $('#modalPago').data('precio');
    var sku = $('#modalPago').data('sku');
    
    // Validación previa de saldo
    if (saldoUsuario < (precio / 100)) {
        alert('Saldo insuficiente. Por favor, recarga tu saldo o selecciona otro método de pago.');
        return;
    }
    
    // UI Feedback: Mostrar Overlay de Carga sobre el modal
    var $modalContent = $('#modalPago .login-modal-content');
    
    // Verificar que el contenedor tenga posición relativa para el absolute del overlay
    if ($modalContent.css('position') === 'static') {
        $modalContent.css('position', 'relative');
    }

    var $loadingOverlay = $('<div id="paymentLoading" style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(44, 44, 44, 0.98); z-index:100; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:12px; opacity:0; transition: opacity 0.3s ease;">' +
        '<div style="background: rgba(227, 6, 19, 0.15); padding: 25px; border-radius: 50%; margin-bottom: 20px; box-shadow: 0 0 20px rgba(227, 6, 19, 0.2);">' +
        '<i class="fas fa-circle-notch fa-spin fa-3x" style="color:#E30613;"></i></div>' +
        '<h4 style="color:white; font-weight:600; margin-bottom:10px; font-size: 1.4rem;">Procesando pago...</h4>' +
        '<p style="color:#aaa; text-align:center; max-width:80%; font-size: 0.95rem; line-height: 1.5;">Estamos confirmando tu destacado.<br>Por favor, no cierres esta ventana.</p>' +
        '</div>');
        
    $modalContent.append($loadingOverlay);
    
    // Animar entrada
    setTimeout(function() {
        $loadingOverlay.css('opacity', 1);
    }, 10);
    
    // Ocultar botón de cerrar para evitar interrupciones
    $('.login-modal-close').hide();
    
    // Enviar petición AJAX
    $.ajax({
        url: '/procesar_destacado_saldo',
        method: 'POST',
        data: {
            codigo_id: codigoId,
            tipo: tipo,
            precio: precio,
            sku: sku,
            auto_renovar: $('#autoRenovarCheck').is(':checked') ? '1' : '0'
        },
        dataType: 'json',
        success: function(response) {
            // Éxito: Mostrar estado de completado
            var $content = $loadingOverlay.find('div, h4, p'); // Elementos internos
            
            // Efecto de transición suave
            $content.fadeOut(200, function() {
                $loadingOverlay.empty().append(
                    '<div style="opacity:0; transform: translateY(10px); transition: all 0.4s ease; display:flex; flex-direction:column; align-items:center;">' +
                    '<div style="background: rgba(40, 167, 69, 0.15); padding: 25px; border-radius: 50%; margin-bottom: 20px; box-shadow: 0 0 20px rgba(40, 167, 69, 0.2);">' +
                    '<i class="fas fa-check fa-3x" style="color:#28a745;"></i></div>' +
                    '<h4 style="color:white; font-weight:600; margin-bottom:10px; font-size: 1.4rem;">¡Pago Completado!</h4>' +
                    '<p style="color:#aaa; text-align:center; font-size: 0.95rem;">Tu anuncio ha sido destacado correctamente.</p>' +
                    '</div>'
                );
                
                // Mostrar ticks de éxito
                setTimeout(function() {
                    $loadingOverlay.children().css({opacity: 1, transform: 'translateY(0)'});
                }, 50);
            });
            
            // Redirigir después de breve pausa para leer el mensaje
            setTimeout(function() {
                window.location.href = '/mis-anuncios?success=destacado_ok';
            }, 2000);
        },
        error: function(xhr, status, error) {
            // Error: Quitar overlay y mostrar mensaje
            $loadingOverlay.fadeOut(300, function() {
                $(this).remove();
                $('.login-modal-close').show();
            });
            
            var msg = 'Ha ocurrido un error al procesar el pago.';
            if(xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            } else if (status === 'timeout') {
                msg = 'El servidor está tardando demasiado en responder, pero tu pago podría haberse procesado. Por favor verifica tus anuncios.';
            }
            
            alert(msg);
        },
        timeout: 60000 // Timeout de 60 segundos por la lentitud de emails
    });
}
</script>
