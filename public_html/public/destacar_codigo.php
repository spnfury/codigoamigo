<?php
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

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: /login");
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
    error_log("Error obteniendo saldo del usuario: " . $e->getMessage());
}

// Obtener la información del código
$codigo_id = $_REQUEST["codigo"];
if (!$codigo_id) {
    header("Location: /mis-anuncios");
    exit;
}

$obj_id_codigo = new \MongoDB\BSON\ObjectId($codigo_id);
$codigo = getCodeByID($obj_id_codigo);

// Verificar que el código pertenece al usuario
if (!$codigo || $codigo["id_usuario"] != $_SESSION["user_id"]) {
    // Log del intento de acceso no autorizado
    error_log("Acceso no autorizado a destacar código - Usuario: " . $_SESSION["user_id"] . ", Código: " . $codigo_id . ", Propietario: " . ($codigo ? $codigo["id_usuario"] : "No encontrado"));
    
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

// Configuración de Stripe - Usar los mismos SKUs que el sistema anterior
if($_SESSION["user_id"]=='639899bc6321ee0d0e4010d2' || $_SESSION["user_id"]=='58bd851da54e295b8b52f702' || $_SESSION["user_id"]=='5db1af3a2f55c82b47342172'){ //SI ES USUARIO ADMIN DESTACAR GRATIS
    $stripe_live_publishable_key = "pk_test_yU61XXQMBvqVt4Ah9XD5uk6V";
    $stripe_live_secret_key = "sk_test_ML0vGPIQHfl4iQYVHeflQTZt";
    $sku_destacado_normal = 'sku_GJUimQssXxo3yB';
    $sku_destacado_super = 'sku_GJWab8ArF8ZM6t';
} else { //PRODUCCION 
    $stripe_live_publishable_key = "pk_live_HvgqlImI22optTnSvHKFKDiG00VQ0EZdE9";
    $stripe_live_secret_key = "sk_live_dfMwJTC7REoMy76Bp2PzVoZV00U5KaNCcv";
    $sku_destacado_normal = 'sku_H6K4Pf8TXO6w58'; // Normal 0.99€ - Mismo que el sistema anterior
    $sku_destacado_super = 'sku_H6K69kuQGeL0pV'; // Super 3.99€ - Mismo que el sistema anterior
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
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
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
    background: #ff6b35;
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
    border-color: #ff6b35;
    transform: translateY(-5px);
}

.pricing-card.featured {
    border-color: #ff6b35;
    background: linear-gradient(135deg, #2c2c2c 0%, #3a3a3a 100%);
}

.pricing-card.featured::before {
    content: "MÁS POPULAR";
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: #ff6b35;
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
    color: #ff6b35;
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
    color: #ff6b35;
    margin-right: 10px;
}

.destacar-btn {
    background: #ff6b35;
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
    background: #e55a2b;
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
    color: #ff6b35;
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
    <a href="/mis-anuncios" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Volver a mis anuncios
    </a>

    <div class="destacar-header">
        <h1><i class="fas fa-star"></i> Destacar tu código</h1>
        <p>Obtén mayor visibilidad para tu código de descuento</p>
    </div>

    <!-- Vista previa del código -->
    <div class="code-preview">
        <div class="code-preview-header">
            <div class="code-preview-logo">
                <i class="fas fa-tag"></i>
            </div>
            <div class="code-preview-info">
                <h3><?php echo htmlspecialchars($marca_nombre); ?></h3>
                <p><?php echo htmlspecialchars($codigo['descripcion'] ?? 'Código de descuento'); ?></p>
            </div>
        </div>
        
        <div class="code-preview-details">
            <div class="code-detail-item">
                <div class="label">Código</div>
                <div class="value"><?php echo htmlspecialchars($codigo['codigo'] ?? 'N/A'); ?></div>
            </div>
            <div class="code-detail-item">
                <div class="label">Beneficio</div>
                <div class="value"><?php echo ($codigo['num_beneficio'] ?? 0); ?>€</div>
            </div>
            <div class="code-detail-item">
                <div class="label">Posición actual</div>
                <div class="value">#<?php echo get_posicion_codigo_en_marca($codigo['_id'], $codigo['marca']); ?></div>
            </div>
            <div class="code-detail-item">
                <div class="label">Clicks</div>
                <div class="value"><?php echo $codigo['totalclicks'] ?? 0; ?></div>
            </div>
        </div>
    </div>

    <!-- Opciones de destacar -->
    <div class="pricing-section">
        <div class="pricing-card">
            <div class="pricing-header">
                <h3>Destacado Normal</h3>
            </div>
            <div class="pricing-price">0,99€</div>
            <ul class="pricing-features">
                <li><i class="fas fa-check"></i> Aparece en primera posición</li>
                <li><i class="fas fa-check"></i> Badge "Destacado" visible</li>
                <li><i class="fas fa-check"></i> Mayor visibilidad en la marca</li>
                <li><i class="fas fa-check"></i> Duración: 30 días</li>
            </ul>
            <button class="destacar-btn" id="destacar-normal" data-price="99" data-sku="<?php echo $sku_destacado_normal; ?>" data-tipo="normal">
                <i class="fas fa-star"></i> Destacar Normal
            </button>
        </div>

        <div class="pricing-card featured">
            <div class="pricing-header">
                <h3>Destacado Super</h3>
            </div>
            <div class="pricing-price">3,99€</div>
            <ul class="pricing-features">
                <li><i class="fas fa-check"></i> Aparece en primera posición</li>
                <li><i class="fas fa-check"></i> Badge "Destacado" dorado</li>
                <li><i class="fas fa-check"></i> Aparece en página principal</li>
                <li><i class="fas fa-check"></i> Mayor visibilidad en la marca</li>
                <li><i class="fas fa-check"></i> Duración: 60 días</li>
            </ul>
            <button class="destacar-btn" id="destacar-super" data-price="399" data-sku="<?php echo $sku_destacado_super; ?>" data-tipo="super">
                <i class="fas fa-crown"></i> Destacar Super
            </button>
        </div>
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
                <i class="fas fa-clock"></i>
                <h3>Duración Garantizada</h3>
                <p>Tu código permanecerá destacado durante el tiempo que hayas pagado.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de pago -->
<div class="modal fade" id="modalPago" tabindex="-1" role="dialog" aria-labelledby="modalPagoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="background: #2c2c2c; border: 1px solid #404040; border-radius: 15px;">
            <div class="modal-header" style="border-bottom: 1px solid #404040; padding: 20px 30px;">
                <h5 class="modal-title" id="modalPagoLabel" style="color: white; font-size: 1.5rem; font-weight: 600;">
                    <i class="fas fa-credit-card"></i> Elegir método de pago
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.7;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <!-- Información del destacado -->
                <div class="destacado-info" style="background: #404040; padding: 20px; border-radius: 10px; margin-bottom: 25px;">
                    <h6 style="color: white; margin-bottom: 10px; font-weight: 600;" id="destacadoTitulo">Destacado Normal</h6>
                    <p style="color: #ccc; margin: 0; font-size: 0.9rem;" id="destacadoDescripcion">Aparece en primera posición con badge "Destacado"</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <span style="color: white; font-weight: 600;">Precio:</span>
                        <span style="color: #ff6b35; font-size: 1.2rem; font-weight: 700;" id="destacadoPrecio">0,99€</span>
                    </div>
                </div>

                <!-- Opciones de pago -->
                <div class="payment-options">
                    <h6 style="color: white; margin-bottom: 15px; font-weight: 600;">Selecciona tu método de pago:</h6>
                    
                    <!-- Opción 1: Pago con tarjeta -->
                    <div class="payment-option" style="background: #404040; border: 2px solid #555; border-radius: 10px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" id="opcionTarjeta">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-credit-card" style="font-size: 1.5rem; color: #ff6b35; margin-right: 15px;"></i>
                                <div>
                                    <h6 style="color: white; margin: 0; font-weight: 600;">Pagar con tarjeta</h6>
                                    <p style="color: #ccc; margin: 5px 0 0 0; font-size: 0.9rem;">Visa, Mastercard, American Express</p>
                                </div>
                            </div>
                            <i class="fas fa-arrow-right" style="color: #ff6b35;"></i>
                        </div>
                    </div>

                    <!-- Opción 2: Pago con saldo -->
                    <div class="payment-option" style="background: #404040; border: 2px solid #555; border-radius: 10px; padding: 20px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;" id="opcionSaldo">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-wallet" style="font-size: 1.5rem; color: #ff6b35; margin-right: 15px;"></i>
                                <div>
                                    <h6 style="color: white; margin: 0; font-weight: 600;">Pagar con saldo</h6>
                                    <p style="color: #ccc; margin: 5px 0 0 0; font-size: 0.9rem;" id="saldoDisponible">Usar tu saldo disponible</p>
                                </div>
                            </div>
                            <i class="fas fa-arrow-right" style="color: #ff6b35;"></i>
                        </div>
                    </div>
                </div>



                <!-- Saldo del usuario -->
                <div class="saldo-info" style="background: #1a1a1a; padding: 15px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #ff6b35;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: white; font-weight: 600;">
                            <i class="fas fa-wallet" style="margin-right: 8px; color: #ff6b35;"></i>
                            Tu saldo actual:
                        </span>
                        <span style="color: #ff6b35; font-size: 1.1rem; font-weight: 700;" id="saldoUsuario">0€</span>
                    </div>
                </div>

                
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
var stripe = Stripe('<?php echo $stripe_live_publishable_key; ?>');
var codigoId = '<?php echo $codigo_id; ?>';
var saldoUsuario = <?php echo $saldo_usuario; ?>; // Saldo cargado desde PHP

// Cargar saldo del usuario al cargar la página
$(document).ready(function() {
    // Actualizar el saldo en el modal
    $('#saldoUsuario').text(saldoUsuario + '€');
    actualizarOpcionSaldo();
    
    configurarBotones();
    configurarModal();
});

// El saldo ya se carga desde PHP, no necesitamos AJAX

// Función para configurar los botones de destacar
function configurarBotones() {
    $('#destacar-normal, #destacar-super').on('click', function(e) {
        e.preventDefault();
        
        var tipo = $(this).data('tipo');
        var precio = $(this).data('price');
        var sku = $(this).data('sku');
        
        // Configurar modal con la información del destacado
        configurarModalDestacado(tipo, precio, sku);
        
        // Mostrar modal
        $('#modalPago').modal('show');
    });
}

// Función para configurar el modal con la información del destacado
function configurarModalDestacado(tipo, precio, sku) {
    var titulo = tipo === 'normal' ? 'Destacado Normal' : 'Destacado Super';
    var descripcion = tipo === 'normal' 
        ? 'Aparece en primera posición con badge "Destacado" - Duración: 30 días'
        : 'Aparece en primera posición con badge dorado - Duración: 60 días';
    var precioFormateado = (precio / 100).toFixed(2) + '€';
    
    $('#destacadoTitulo').text(titulo);
    $('#destacadoDescripcion').text(descripcion);
    $('#destacadoPrecio').text(precioFormateado);
    
    // Guardar datos para usar en el pago
    $('#modalPago').data('tipo', tipo);
    $('#modalPago').data('precio', precio);
    $('#modalPago').data('sku', sku);
    
    // Actualizar opción de saldo
    actualizarOpcionSaldo();
}

// Función para actualizar la opción de pago con saldo
function actualizarOpcionSaldo() {
    var precio = $('#modalPago').data('precio');
    var precioEuros = precio / 100;
    
    if (saldoUsuario >= precioEuros) {
        $('#opcionSaldo').removeClass('disabled').css('opacity', '1');
        $('#saldoDisponible').text('Saldo suficiente - ' + (saldoUsuario).toFixed(2) + '€ restantes');
        $('#opcionSaldo').find('h6').css('color', 'white');
    } else {
        $('#opcionSaldo').addClass('disabled').css('opacity', '0.5');
        $('#saldoDisponible').text('Saldo insuficiente - Necesitas ' + (precioEuros - saldoUsuario).toFixed(2) + '€ más');
        $('#opcionSaldo').find('h6').css('color', '#666');
    }
}

// Función para configurar el modal
function configurarModal() {
    // Efectos hover para las opciones de pago
    $('.payment-option').hover(
        function() {
            if (!$(this).hasClass('disabled')) {
                $(this).css('border-color', '#ff6b35');
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
    $('#opcionTarjeta').on('click', function() {
        if (!$(this).hasClass('disabled')) {
            procesarPagoConTarjeta();
        }
    });
    
    // Click en opción de saldo
    $('#opcionSaldo').on('click', function() {
        if (!$(this).hasClass('disabled')) {
            procesarPagoConSaldo();
        }
    });
}

// Función para procesar pago con tarjeta
function procesarPagoConTarjeta() {
    var tipo = $('#modalPago').data('tipo');
    var sku = $('#modalPago').data('sku');
    
    $('#modalPago').modal('hide');
    
    // Mostrar loading en el botón correspondiente
    var boton = tipo === 'normal' ? $('#destacar-normal') : $('#destacar-super');
    boton.addClass('loading').html('<i class="fas fa-spinner fa-spin"></i> Procesando...').prop('disabled', true);
    
    stripe.redirectToCheckout({
        mode: 'payment',
        lineItems: [{
            price: sku,
            quantity: 1
        }],
        clientReferenceId: codigoId,
        billingAddressCollection: 'auto',
        successUrl: '<?php echo $GLOBALS["website"]; ?>felicidades_destacar?session_id={CHECKOUT_SESSION_ID}&codigo=' + codigoId + '&tipo=' + tipo,
        cancelUrl: '<?php echo $GLOBALS["actual_url"]; ?>'
    })
    .then(function (result) {
        if (result.error) {
            boton.removeClass('loading').html(tipo === 'normal' ? '<i class="fas fa-star"></i> Destacar Normal' : '<i class="fas fa-crown"></i> Destacar Super').prop('disabled', false);
            alert('Error: ' + result.error.message);
        }
    });
}

// Función para procesar pago con saldo
function procesarPagoConSaldo() {
    var tipo = $('#modalPago').data('tipo');
    var precio = $('#modalPago').data('precio');
    
    if (saldoUsuario < (precio / 100)) {
        alert('Saldo insuficiente');
        return;
    }
    
    // Crear formulario para enviar petición POST tradicional
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/procesar_destacado_saldo';
    
    // Añadir campos ocultos
    var codigoInput = document.createElement('input');
    codigoInput.type = 'hidden';
    codigoInput.name = 'codigo_id';
    codigoInput.value = codigoId;
    form.appendChild(codigoInput);
    
    var tipoInput = document.createElement('input');
    tipoInput.type = 'hidden';
    tipoInput.name = 'tipo';
    tipoInput.value = tipo;
    form.appendChild(tipoInput);
    
    var precioInput = document.createElement('input');
    precioInput.type = 'hidden';
    precioInput.name = 'precio';
    precioInput.value = precio;
    form.appendChild(precioInput);
    
    // Añadir formulario al DOM y enviarlo
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php get_footer(); ?>
