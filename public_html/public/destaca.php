<?php
// Al inicio del archivo, antes de cargar el header
$anula_adsense = true; // Esta variable será leída por el header para no mostrar Adsense

// Obtener la información del código
$codigo_id = new \MongoDB\BSON\ObjectId($_REQUEST["codigo"]);
$codigo = getCodeByID($codigo_id); // Esta función ya existe en el sistema

$marca = getObjectMarca('nombre_clave', $codigo["marca"]);
$marca_nombre = htmlspecialchars($codigo['marca']); // Obtenemos la marca directamente del código

get_header_new($title, $description, $title_social, $description_social, $imagen_social);



require_once $_SERVER['DOCUMENT_ROOT'] . '/config/stripe.php';
$stripe_live_secret_key = get_stripe_secret_key(null, $_SESSION['user_id'] ?? null);
$is_sandbox_admin = in_array($_SESSION['user_id'] ?? null, ['639899bc6321ee0d0e4010d2', '58bd851da54e295b8b52f702', '5db1af3a2f55c82b47342172'], true);
if ($is_sandbox_admin) {
    $stripe_live_publishable_key = "pk_test_yU61XXQMBvqVt4Ah9XD5uk6V";
    $sku_patrocinado_1 = 'sku_GJUimQssXxo3yB';
    $sku_patrocinado_2 = 'sku_GJWab8ArF8ZM6t';
} else {
    $stripe_live_publishable_key = "pk_live_HvgqlImI22optTnSvHKFKDiG00VQ0EZdE9";
    $sku_patrocinado_1 = 'sku_H6K4Pf8TXO6w58'; //NORMAL 0,99
    $sku_patrocinado_2 = 'sku_H6K69kuQGeL0pV'; //HOME 3,99
}



/* Primero de todo, comprobamos si tenemos que realizar algún cargo */


if(count($_SESSION["compra_lead_sin_validar"]) > 0) {



    $cantidad_stripe = $_SESSION["compra_lead_sin_validar"]["cantidad"];
    $cantidad_original = $cantidad_stripe/100;
    $token_id = $_SESSION["compra_lead_sin_validar"]["token_id"];
    $codigo_operacion = $_SESSION["compra_lead_sin_validar"]["codigo"];
    $lead_id = $_SESSION["compra_lead_sin_validar"]["lead_id"];


    //BCV == destacado normal
    //BCS == destacado social

    try

    {

        \Stripe\Stripe::setApiKey($stripe_live_secret_key);
        $charge = \Stripe\Charge::create(array(
            'amount' => $cantidad_stripe,
            'currency' => 'eur',
            'description' => 'CODIGOAMIGO - '.$lead_id,
            'source' => $token_id));

    }catch(Exception $e)

    {

        /*echo "<pre>";
        print_r($e);
        print_r($charge);*/

    }



    if($charge->status != "succeeded") {
        $pago = "error";
    }else {
        $pago = "ok";
    }

    if($pago == "ok") {

        /*if($codigo_operacion == "BCI") { $operacion = "Búsqueda candidados sin validar"; }*/

        /*
         * OK PATROCINO
         */

        $obj_id_codigo = new \MongoDB\BSON\ObjectId($lead_id);
        $codigo_to_show = getCodeByID($obj_id_codigo);


        añadir_destacado_codigo($codigo_to_show,$codigo_operacion);

        //echo $codigo_to_show;



        /*$wpdb->insert('wp_user_historial_operaciones', array(
            'user_id' => $current_user->ID,
            'codigo_operacion' => "RS",
            'operacion' => "Recargar saldo",
            'cantidad' => $cantidad_original,
        ));

        $wpdb->insert('wp_user_historial_operaciones', array(
            'user_id' => $current_user->ID,
            'id_intento' => $id_intento,
            'codigo_operacion' => $codigo_operacion,
            'operacion' => $operacion,
            'cantidad' => $cantidad_original,
        ));

        $_SESSION["compra_lead_sin_validar"] = array();*/

        ?>
        <script type="text/javascript">
        ga('send', 'event', 'patrocinado', '<?php echo $obj_id_codigo; ?>', '<?php echo $cantidad_stripe; ?>', '', '<?php echo $obj_id_codigo; ?>');
        </script>
        <?


        header("location:".$GLOBALS["website"]);

    }



}

$_SESSION["compra_lead_sin_validar"] = array();
unset($_SESSION["compra_lead_sin_validar"]);
?>

<style>
/* Modern typography and spacing */
:root {
    --primary-color: #4CAF50;
    --primary-dark: #388E3C;
    --text-dark: #2C3E50;
    --text-light: #7F8C8D;
    --spacing: 2rem;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    line-height: 1.6;
    color: var(--text-dark);
    background: #F8F9FA;
}

.container-pricing {
    max-width: 1200px;
    margin: 4rem auto;
    padding: 0 2rem;
}

.pricing-header {
    text-align: center;
    margin-bottom: var(--spacing);
}

.pricing-title {
    font-size: 3rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1.5rem;
}

.pricing-subtitle {
    font-size: 1.6rem;
    margin-bottom: 3rem;
}

.pricing-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing);
    margin-top: var(--spacing);
}

.pricing-card {
    background: white;
    border-radius: 16px;
    padding: var(--spacing);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    transition: transform 0.2s;
}

.pricing-card:hover {
    transform: translateY(-5px);
}

.plan-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.plan-price {
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}

.plan-features {
    margin: 1.5rem 0;
    font-size: 1.1rem;
}

.plan-feature {
    margin: 0.8rem 0;
    display: flex;
    align-items: center;
}

.plan-feature:before {
    content: "✓";
    color: var(--primary-color);
    margin-right: 0.5rem;
}

.pricing-button {
    display: inline-block;
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
    text-align: center;
    color: white;
    background: var(--primary-color);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.pricing-button:hover {
    background: var(--primary-dark);
}

.security-badge {
    text-align: center;
    margin-top: var(--spacing);
    padding: 1.5rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.security-badge p {
    font-size: 1.1rem;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.payment-icons {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    align-items: center;
}

.payment-icons i {
    font-size: 2rem;
    color: #666;
}

.secure-icon {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

.header-preview {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 4rem 0;
    margin-bottom: 3rem;
    text-align: center;
}

.header-preview h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.header-preview .marca {
    color: #FFD700;
    font-weight: 700;
}

.header-preview p {
    font-size: 1.8rem;
    opacity: 0.9;
    margin-top: 1.5rem;
}

.header-preview .codigo {
    background: rgba(255, 255, 255, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-family: monospace;
    font-size: 2rem;
    margin: 1rem 0;
    display: inline-block;
}

.code-preview {
    padding: 1.5rem;
    margin: 1rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.preview-title {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    text-align: center;
    color: #333;
}

.preview-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.preview-before,
.preview-after {
    flex: 1;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.preview-label {
    font-size: 1rem;
    color: #666;
    margin-bottom: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
}

.code-card {
    padding: 1rem;
    border-radius: 8px;
}

.preview-after .code-card {
    background: #FFF8DC;
    border: 1px solid #FFD700;
}

.code-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
}

.code-discount {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #333;
}

.code-meta {
    font-size: 1rem;
    color: #666;
}

.preview-after .code-meta {
    color: #333;
}

.star-icon {
    color: #FFD700;
    margin-right: 0.3rem;
}

.marca-imagen {
    height: 30px;
    width: auto;
    vertical-align: middle;
    margin-right: 8px;
}



.telegram-tooltip {
    position: absolute;
    right: 75px;
    background: #333;
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    font-size: 14px;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.telegram-float:hover .telegram-tooltip {
    opacity: 1;
}

.pricing-card.premium {
    background: #FFFBEB;
    border: 1px solid #FFD700;
    position: relative;
    overflow: hidden;
    padding: 2rem;
}

.pricing-card.premium::before {
    content: "Premium";
    position: absolute;
    top: -5px;
    right: -35px;
    background: #FFD700;
    color: #000000;
    padding: 20px 40px 5px 40px;
    transform: rotate(45deg);
    font-size: 0.9rem;
    font-weight: bold;
}

.pricing-card.premium .plan-name {
    color: #000000;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.pricing-card.premium .plan-price {
    color: #000000;
    font-size: 3rem;
    margin-bottom: 2rem;
    font-weight: 700;
}

.pricing-card.premium .plan-features {
    margin: 2rem 0;
}

.pricing-card.premium .plan-feature {
    color: #000000;
    margin: 1rem 0;
    display: flex;
    align-items: center;
    font-size: 1rem;
}

.pricing-card.premium .plan-feature:before {
    content: "✓";
    color: #000000;
    margin-right: 0.5rem;
}

.pricing-card.premium .pricing-button {
    background: #FFD700;
    color: #000000;
    padding: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
    width: 100%;
    border: none;
    border-radius: 8px;
    margin-top: 1rem;
}

.pricing-card.premium .pricing-button:hover {
    background: #FFC700;
    cursor: pointer;
}

@media (min-width: 768px) {
    .preview-container {
        flex-direction: row;
        gap: 2rem;
    }

    .code-preview {
        padding: 2rem;
        margin: 2rem auto;
    }

    .preview-title {
        font-size: 1.8rem;
    }
}

/* Add these new styles */
.loading {
    position: relative;
    pointer-events: none;
    opacity: 0.7;
}

.loading::after {
    content: "";
    position: absolute;
    width: 20px;
    height: 20px;
    top: 50%;
    left: 50%;
    margin: -10px 0 0 -10px;
    border: 3px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<!-- Agregar Font Awesome en el head -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="header-preview">
    <div class="container">
        <h1>Destaca tu código de <span class="marca">
            <?php 
            if (!empty($marca["imagen"])) {
                echo '<img src="' . htmlspecialchars($marca["imagen"]) . '" alt="' . htmlspecialchars($marca_nombre) . '" class="marca-imagen">';
            }
            echo htmlspecialchars($marca_nombre); 
            ?>
        </span></h1>
        <p>Aumenta la visibilidad de tu código y consigue más canjes</p>
    </div>
</div>



<div class="container-pricing">
    <div class="pricing-header">
        <h1 class="pricing-title">Destaca tu código</h1>
        <p class="pricing-subtitle">Elige el plan que mejor se adapte a tus necesidades</p>
    </div>

    <div class="pricing-cards">
        <div class="pricing-card">
            <div class="plan-name">Plan Básico</div>
            <div class="plan-price">0,99€</div>
            <div class="plan-features">
                <div class="plan-feature">Destacado en listados de marca</div>
                <div class="plan-feature">Mayor visibilidad</div>
                <div class="plan-feature">Prioridad en búsquedas</div>
            </div>
            <button id="checkout-button-normal" class="pricing-button">
                Destacar Código
            </button>
        </div>

        <div class="pricing-card premium">
            <div class="plan-name">Plan Premium</div>
            <div class="plan-price">3,99€</div>
            <div class="plan-features">
                <div class="plan-feature">Destacado en página principal</div>
                <div class="plan-feature">Destacado en listados de marca</div>
                <div class="plan-feature">Promoción en redes sociales</div>
                <div class="plan-feature">Máxima visibilidad</div>
            </div>
            <button id="checkout-button-super" class="pricing-button">
                Destacar Código Premium
            </button>
        </div>
    </div>

    <div class="security-badge">
        <p>
            <i class="fas fa-shield-alt secure-icon"></i>
            Pago 100% Seguro y Garantizado
        </p>
        <div class="payment-icons">
            <i class="fab fa-cc-visa" title="Visa"></i>
            <i class="fab fa-cc-mastercard" title="Mastercard"></i>
            <i class="fab fa-cc-amex" title="American Express"></i>
            <i class="fab fa-stripe" title="Stripe"></i>
            <i class="fas fa-lock" title="Conexión Segura"></i>
        </div>
    </div>
</div>

    <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
    <script src="https://checkout.stripe.com/checkout.js"></script>


    <div id="payment-request-button">
  <!-- A Stripe Element will be inserted here. -->
</div>
    <script type="text/javascript">
    var stripe = Stripe('<?php echo $stripe_live_publishable_key; ?>');

    <?php
    $_SESSION["a_patrocinar"] = $_REQUEST["codigo"];
    ?>

    document.addEventListener('DOMContentLoaded', function() {
        var checkoutButton = document.getElementById('checkout-button-normal');
        var checkoutButtonSuper = document.getElementById('checkout-button-super');

        function handleCheckout(button, priceId) {
            button.classList.add('loading');
            button.textContent = 'Procesando...';

            stripe.redirectToCheckout({
                mode: 'payment',
                lineItems: [{
                    price: priceId,
                    quantity: 1
                }],
                clientReferenceId: '<?php echo $_REQUEST["codigo"]; ?>',
                billingAddressCollection: 'auto',
                successUrl: '<?php echo $GLOBALS["website"]; ?>felicidades?session_id={CHECKOUT_SESSION_ID}',
                cancelUrl: '<?php echo $GLOBALS["actual_url"]; ?>'
            })
            .then(function (result) {
                if (result.error) {
                    button.classList.remove('loading');
                    button.textContent = button === checkoutButton ? 'Destacar Código' : 'Destacar Código Premium';
                    var displayError = document.getElementById('error-message');
                    displayError.textContent = result.error.message;
                }
            });
        }

        checkoutButton.addEventListener('click', function () {
            handleCheckout(this, '<?php echo $sku_patrocinado_1; ?>');
        });

        checkoutButtonSuper.addEventListener('click', function () {
            handleCheckout(this, '<?php echo $sku_patrocinado_2; ?>');
        });
    });
    </script>

<!-- Agregar justo antes del footer -->
<a href="https://t.me/TuUsuarioDeTelegram" target="_blank" class="telegram-float">
    <i class="fa-brands fa-telegram"></i>
    <span class="telegram-tooltip">¿Necesitas ayuda? ¡Escríbenos!</span>
</a>

<?php get_footer(); ?>
}