<?php
session_start();
require_once __DIR__ . '/inc/conexion.php';
// Necessary for createConnection()
require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_codigo.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$user_id = $_SESSION['user_id'];
$codigo_id = $_GET['codigo_id'] ?? null;

if (!$codigo_id) {
    $_SESSION['msg_error'] = "Código no especificado";
    header('Location: /mis-anuncios');
    exit;
}

// Get code details
$db = createConnection();
$codigos_coll = $db->selectCollection('codigos');

try {
    $codigo_obj_id = new MongoDB\BSON\ObjectId($codigo_id);
} catch (Exception $e) {
    $codigo_obj_id = $codigo_id;
}

$codigo = $codigos_coll->findOne(['_id' => $codigo_obj_id]);

if (!$codigo) {
    $_SESSION['msg_error'] = "Código no encontrado";
    header('Location: /mis-anuncios');
    exit;
}

// Check ownership
if ((string)($codigo['id_usuario'] ?? '') !== $user_id && ($codigo['usuario_creador'] ?? '') !== $user_id) {
    $_SESSION['msg_error'] = "No tienes permiso para modificar este código";
    header('Location: /mis-anuncios');
    exit;
}

// Obtener saldo del usuario
$saldo_usuario = 0;
try {
    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
    $saldo_usuario = $usuario['saldo'] ?? 0;
} catch (Exception $e) { }

// Check if Super Landing exists for this brand
include_once __DIR__ . '/myphp/_super_landing_functions.php';
$marca_slug = $codigo['marca'] ?? '';
$has_super_landing = false;
$super_landing_title = '';

if (function_exists('get_active_super_landings')) {
    $all_landings = get_active_super_landings(50);
    foreach ($all_landings as $landing) {
        if (isset($landing['linked_brand_slugs'])) {
             // Handle both array and BSONArray
             $slugs = is_object($landing['linked_brand_slugs']) ? iterator_to_array($landing['linked_brand_slugs']) : $landing['linked_brand_slugs'];
             if (in_array($marca_slug, $slugs)) {
                $has_super_landing = true;
                $super_landing_title = $landing['title'];
                break;
             }
        }
    }
}

// Get header
include_once __DIR__ . '/myphp/_header_modern.php';
get_header_modern('Destacar Código Super - Código Amigo', 'Destaca tu código en la posición premium de las Guías');
?>

<div class="container" style="padding: 40px 20px; max-width: 800px;">
    <div class="upgrade-super-container">
        <div class="text-center mb-4">
            <?php if (isset($_SESSION['msg_error'])): ?>
                <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['msg_info'])): ?>
                <div class="alert alert-info" style="background-color: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $_SESSION['msg_info']; unset($_SESSION['msg_info']); ?>
                </div>
            <?php endif; ?>
            <i class="fas fa-trophy" style="font-size: 4rem; color: #E30613; margin-bottom: 20px;"></i>
            <h1 style="font-size: 2.5rem; font-weight: 800; color: white; margin-bottom: 10px;">
                Destaca tu Código como SUPER
            </h1>
            <p style="font-size: 1.2rem; color: #e0e0e0;">
                La posición más privilegiada en las Guías Oficiales
            </p>
        </div>

        <?php if (!$has_super_landing): ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-info-circle"></i> Esta marca aún no tiene una Guía Oficial activa.
                <br>El destacado Super solo está disponible para marcas con Guías Oficiales.
            </div>
            <div class="text-center mt-4">
                <a href="/mis-anuncios" class="btn btn-default">Volver a Mis Códigos</a>
            </div>
        <?php else: ?>
            <div class="current-code-info" style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 30px;">
                <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 15px;">Tu Código:</h3>
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <p style="margin: 5px 0; color: #333;"><strong>Marca:</strong> <?php echo htmlspecialchars($codigo['marca_nombre'] ?? $codigo['marca']); ?></p>
                        <p style="margin: 5px 0; color: #333;"><strong>Beneficio:</strong> <?php echo htmlspecialchars($codigo['num_beneficio'] ?? ''); ?> <?php echo (isset($codigo['descuento']) && $codigo['descuento'] === 'euros') ? '€' : '%'; ?></p>
                        <p style="margin: 5px 0; color: #333;"><strong>Código:</strong> <code><?php echo htmlspecialchars($codigo['codigo'] ?? ''); ?></code></p>
                    </div>
                    <div>
                        <?php if (isset($codigo['tipo_destacado']) && $codigo['tipo_destacado'] === 'super'): ?>
                            <span class="badge" style="background: #E30613; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px;">
                                <i class="fas fa-star"></i> YA ES SUPER
                            </span>
                        <?php else: ?>
                            <span class="badge" style="background: #ccc; color: #666; padding: 8px 16px; border-radius: 20px; font-size: 14px;">
                                Normal
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="benefits-section" style="margin-bottom: 30px;">
                <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 20px; text-align: center;">
                    ¿Qué obtienes con Super Destacado?
                </h3>
                <div class="row">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="benefit-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; height: 100%;">
                            <i class="fas fa-star" style="font-size: 2.5rem; color: #E30613; margin-bottom: 15px;"></i>
                            <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 10px;">Posición #1</h4>
                            <p style="color: #333; font-size: 0.95rem; line-height: 1.4;">Aparece en la caja destacada "Recomendados por los Editores"</p>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="benefit-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; height: 100%;">
                            <i class="fas fa-eye" style="font-size: 2.5rem; color: #E30613; margin-bottom: 15px;"></i>
                            <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 10px;">Máxima Visibilidad</h4>
                            <p style="color: #666; font-size: 0.9rem;">Miles de visitas mensuales en la Guía "<?php echo htmlspecialchars($super_landing_title); ?>"</p>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="benefit-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; height: 100%;">
                            <i class="fas fa-users" style="font-size: 2.5rem; color: #E30613; margin-bottom: 15px;"></i>
                            <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 10px;">Más Referidos</h4>
                            <p style="color: #333; font-size: 0.95rem; line-height: 1.4;">Aumenta tus conversiones y ganancias significativamente</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pricing-section" style="background: linear-gradient(135deg, #E30613, #ff4d4d); padding: 40px; border-radius: 12px; text-align: center; color: white; margin-bottom: 30px;">
                <h3 style="font-size: 2rem; font-weight: 800; margin-bottom: 15px;">Precio Especial de Lanzamiento</h3>
                <div style="font-size: 4rem; font-weight: 900; margin: 20px 0;">9,99€</div>
                <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 25px;">Pago único · Destacado durante 30 días</p>
                
                <?php if (isset($codigo['tipo_destacado']) && $codigo['tipo_destacado'] === 'super'): ?>
                    <button class="btn btn-lg" style="background: white; color: #E30613; padding: 15px 50px; font-size: 1.2rem; font-weight: 700; border: none; border-radius: 50px; cursor: not-allowed;" disabled>
                        <i class="fas fa-check"></i> YA ESTÁS DESTACADO COMO SUPER
                    </button>
                <?php else: ?>
                    <form action="/procesar_super_destacado.php" method="POST" id="form-super-destacado-card" style="display: none;">
                        <input type="hidden" name="codigo_id" value="<?php echo htmlspecialchars($codigo_id); ?>">
                    </form>
                    
                    <button type="button" id="btn-super-destacar" class="btn btn-lg pulse-btn" onclick="openSuperModal()" style="background: white; color: #E30613; padding: 15px 50px; font-size: 1.2rem; font-weight: 700; border: none; border-radius: 50px; transition: all 0.3s ease; cursor: pointer;">
                        <i class="fas fa-crown"></i> DESTACAR COMO SUPER AHORA
                    </button>
                <?php endif; ?>
            </div>

            <div class="faq-section">
                <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 20px; text-align: center;">Preguntas Frecuentes</h3>
                <div class="faq-item" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 10px;">¿Cuánto tiempo dura el destacado?</h4>
                    <p style="color: #333; margin: 0; line-height: 1.6;">El destacado Super tiene una duración de 30 días desde el momento del pago.</p>
                </div>
                <div class="faq-item" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 10px;">¿Qué pasa si hay varios códigos Super?</h4>
                    <p style="color: #333; margin: 0; line-height: 1.6;">Los códigos Super se muestran en un carrusel rotativo, todos con la misma visibilidad premium.</p>
                </div>
                <div class="faq-item" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 10px;">¿Puedo renovar el destacado?</h4>
                    <p style="color: #333; margin: 0; line-height: 1.6;">Sí, puedes renovar tu destacado Super cuando expire o antes si lo deseas.</p>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="/mis-anuncios" class="btn btn-default">
                    <i class="fas fa-arrow-left"></i> Volver a Mis Códigos
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>




<!-- Modal de pago - Sistema Overlay Custom -->
<div class="login-modal-overlay" id="modalPago" style="z-index: 99999; display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center;">
    <div class="login-modal-content" style="max-width: 600px; width: 90%; background: #2c2c2c; border: 1px solid #404040; color: white; border-radius: 12px; position: relative;">
        <div class="login-modal-header" style="background: transparent; border-bottom: 1px solid #404040; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: white; margin: 0; font-size: 1.5rem;"><i class="fas fa-credit-card"></i> Elegir método de pago</h3>
            <button class="login-modal-close" onclick="closeModal()" style="color: white; opacity: 0.7; background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        
        <div class="login-modal-body" style="padding: 30px;">
            <!-- Información del destacado -->
            <div class="destacado-info" style="background: #404040; padding: 25px; border-radius: 10px; margin-bottom: 25px; border-left: 4px solid #E30613;">
                <h6 style="color: white; margin-bottom: 15px; font-weight: 600; font-size: 1.3rem;">Destacado Super</h6>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="color: #ccc; padding: 8px 0; border-bottom: 1px solid #555; display: flex; align-items: center;">
                        <i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i>
                        <span>Aparece en primera posición</span>
                    </li>
                    <li style="color: #ccc; padding: 8px 0; border-bottom: 1px solid #555; display: flex; align-items: center;">
                        <i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i>
                        <span>Badge "Destacado" dorado</span>
                    </li>
                    <li style="color: #ccc; padding: 8px 0; border-bottom: 1px solid #555; display: flex; align-items: center;">
                        <i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i>
                        <span>Mayor visibilidad en la marca</span>
                    </li>
                    <li style="color: #ccc; padding: 8px 0; border-bottom: 1px solid #555; display: flex; align-items: center;">
                        <i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i>
                        <span>Aparece en página principal</span>
                    </li>
                    <li style="color: #ccc; padding: 8px 0; display: flex; align-items: center;">
                        <i class="fas fa-check" style="color: #E30613; margin-right: 10px; font-size: 1rem;"></i>
                        <span>Sin fecha límite: mantienes el #1 hasta que otro te supere</span>
                    </li>
                </ul>
            </div>

            <!-- Opciones de pago -->
            <div class="payment-options">
                <h6 style="color: white; margin-bottom: 15px; font-weight: 600; font-size: 1.2rem;">Selecciona tu método de pago:</h6>
                
                <!-- Opción 1: Pago con tarjeta -->
                <div class="payment-option" onclick="procesarPagoConTarjeta()" style="background: #404040; border: 2px solid #555; border-radius: 10px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#E30613'" onmouseout="this.style.borderColor='#555'">
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
                <?php 
                $precio = 9.99;
                $tiene_saldo = $saldo_usuario >= $precio;
                $style_saldo = $tiene_saldo ? "background: #404040; border: 2px solid #555; cursor: pointer;" : "background: #333; border: 2px solid #444; opacity: 0.6; cursor: not-allowed;";
                $onclick_saldo = $tiene_saldo ? "procesarPagoConSaldo()" : "";
                $mouse_saldo = $tiene_saldo ? "onmouseover=\"this.style.borderColor='#E30613'\" onmouseout=\"this.style.borderColor='#555'\"" : "";
                ?>
                <div class="payment-option" onclick="<?php echo $onclick_saldo; ?>" style="<?php echo $style_saldo; ?> border-radius: 10px; padding: 20px; margin-bottom: 15px; transition: all 0.3s ease;" <?php echo $mouse_saldo; ?>>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center;">
                            <i class="fas fa-coins" style="font-size: 1.5rem; color: #E30613; margin-right: 15px;"></i>
                            <div>
                                <h6 style="color: white; margin: 0; font-weight: 600; font-size: 1.1rem;">Pagar con saldo</h6>
                                <p style="color: <?php echo $tiene_saldo ? '#ccc' : '#E30613'; ?>; margin: 5px 0 0 0; font-size: 1rem;">
                                    <?php if ($tiene_saldo): ?>
                                        <?php echo number_format($saldo_usuario - $precio, 2); ?>€ restantes
                                    <?php else: ?>
                                        Saldo insuficiente - Necesitas <?php echo number_format($precio - $saldo_usuario, 2); ?>€ más
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php if($tiene_saldo): ?>
                        <i class="fas fa-arrow-right" style="color: #E30613;"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Precio -->
            <div class="saldo-info" style="background: #1a1a1a; padding: 15px; border-radius: 8px; margin-bottom: 5px; border-left: 4px solid #E30613;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: white; font-weight: 600; font-size: 1.2rem;">
                        <i class="fas fa-tag" style="margin-right: 8px; color: #E30613;"></i>
                        Precio:
                    </span>
                    <span style="color: #E30613; font-size: 1.5rem; font-weight: 700;">9,99€</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openSuperModal() {
        console.log('Opening modal via inline click');
        document.getElementById('modalPago').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('modalPago').style.display = 'none';
        document.body.style.overflow = '';
    }

    function procesarPagoConTarjeta() {
        // Enviar el formulario existente
        document.getElementById('form-super-destacado-card').submit();
    }

    function procesarPagoConSaldo() {
        if (!confirm('¿Estás seguro de que quieres pagar 9,99€ con tu saldo?')) {
            return;
        }

        // Mostrar loading UI
        var modalContent = document.querySelector('.login-modal-content');
        var loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'paymentLoading';
        loadingOverlay.style.cssText = 'position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(44, 44, 44, 0.98); z-index:100; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:12px; transition: opacity 0.3s ease;';
        loadingOverlay.innerHTML = '<div style="background: rgba(227, 6, 19, 0.15); padding: 25px; border-radius: 50%; margin-bottom: 20px;"><i class="fas fa-circle-notch fa-spin fa-3x" style="color:#E30613;"></i></div><h4 style="color:white; font-weight:600; margin-bottom:10px;">Procesando pago...</h4>';
        
        modalContent.appendChild(loadingOverlay);

        // AJAX POST
        var formData = new FormData();
        formData.append('codigo_id', '<?php echo $codigo_id; ?>');
        formData.append('tipo', 'super_landing');
        formData.append('precio', '999'); // 999 cents = 9.99 EUR

        fetch('/procesar_destacado_saldo', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
             // Handle redirects manually if needed, or JSON
             if (response.redirected) {
                 window.location.href = response.url;
                 return;
             }
             return response.json();
        })
        .then(data => {
            if (data && data.error) {
                alert('Error: ' + data.error);
                loadingOverlay.remove();
            } else if (data) {
                // Success assumed if no error and no redirect (though redirects usually handled above)
                window.location.reload(); 
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // If the response is not JSON (e.g. strict redirect 302 handled by browser), we might end up here or catch block
            // Checking if we are still on the same page
            // If the fetch followed a redirect, response.ok would be true.
            // Let's assume redirects work. Use failover:
             window.location.reload();
        });
    }
    
    // Close modal on outside click
    window.onclick = function(event) {
        var modal = document.getElementById('modalPago');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

<style>
    .pulse-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(227, 6, 19, 0.3);
    }
    
    @media (max-width: 768px) {
        .benefits-section .col-md-4 {
            margin-bottom: 15px;
        }
        
        .pricing-section {
            padding: 30px 20px;
        }
        
        .pulse-btn {
            width: 100%;
            font-size: 1rem !important;
            padding: 12px 30px !important;
        }
    }
</style>

<?php
// Close page with footer
if (function_exists('get_footer_modern')) {
    get_footer_modern();
} else {
    include_once __DIR__ . '/myphp/_footer.php';
    get_footer();
}
?>
