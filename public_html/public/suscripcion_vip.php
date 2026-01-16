<?php
/**
 * Página de Suscripción VIP
 * Landing page premium para vender la suscripción VIP de 9,99€/mes
 */

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

// Verificar si el usuario está logueado
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$is_vip = $is_logged_in ? es_usuario_vip($_SESSION['user_id']) : false;

// Si ya es VIP, mostrar estado de suscripción
$usuario = null;
$viewers_data = null;
if ($is_logged_in) {
    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);
    $viewers_data = obtener_viewers_usuario($_SESSION['user_id']);
}

// Header
$title = "Suscripción VIP | Código Amigo";
$description = "Hazte VIP y maximiza tus ganancias con códigos de referido. Badge verificado, chat ilimitado y 10€ de saldo mensual.";
get_header_modern($title, $description, '', '', '', true);
?>

<style>
/* Hero Section */
.vip-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    padding: 80px 0;
    position: relative;
    overflow: hidden;
}

.vip-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffd700' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.5;
}

.vip-hero .container {
    position: relative;
    z-index: 1;
}

.vip-crown {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    box-shadow: 0 20px 60px rgba(255, 215, 0, 0.4);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.vip-crown i {
    font-size: 60px;
    color: white;
}

.vip-hero h1 {
    font-size: 3.5rem;
    font-weight: 800;
    color: white;
    text-align: center;
    margin-bottom: 20px;
}

.vip-hero h1 span {
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.vip-subtitle {
    font-size: 1.4rem;
    color: rgba(255, 255, 255, 0.8);
    text-align: center;
    max-width: 700px;
    margin: 0 auto 40px;
    line-height: 1.6;
}

/* Pricing Card */
.pricing-card {
    background: white;
    border-radius: 25px;
    padding: 50px 40px;
    max-width: 450px;
    margin: 0 auto;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.pricing-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #ffd700 0%, #ff8c00 100%);
}

.pricing-header {
    text-align: center;
    margin-bottom: 30px;
}

.pricing-badge {
    display: inline-block;
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
    margin-bottom: 20px;
}

.pricing-amount {
    font-size: 4rem;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1;
}

.pricing-amount span {
    font-size: 1.5rem;
    color: #666;
    font-weight: 500;
}

.pricing-period {
    font-size: 1.1rem;
    color: #666;
}

/* Benefits List */
.pricing-benefits {
    margin: 30px 0;
}

.benefit-row {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.benefit-row:last-child {
    border-bottom: none;
}

.benefit-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.benefit-icon i {
    color: white;
    font-size: 16px;
}

.benefit-text {
    flex: 1;
}

.benefit-text h5 {
    font-size: 1rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 3px;
}

.benefit-text p {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
}

/* CTA Button */
.btn-subscribe {
    width: 100%;
    padding: 18px;
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    border: none;
    border-radius: 50px;
    color: white;
    font-size: 1.2rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-subscribe:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(255, 140, 0, 0.4);
}

.btn-subscribe:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-subscribe.loading .btn-text {
    display: none;
}

.btn-subscribe .spinner {
    display: none;
}

.btn-subscribe.loading .spinner {
    display: inline-block;
}

/* Already VIP */
.already-vip {
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 20px;
}

.already-vip i {
    font-size: 40px;
    color: white;
    margin-bottom: 10px;
}

.already-vip h4 {
    color: white;
    font-weight: 700;
    margin-bottom: 5px;
}

.already-vip p {
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
}

/* Login Required */
.login-required {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    margin-top: 20px;
}

.login-required a {
    color: #ff8c00;
    font-weight: 600;
}

/* Stats Section */
.vip-stats {
    padding: 80px 0;
    background: #f8f9fa;
}

.stat-box {
    text-align: center;
    padding: 30px;
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    font-size: 1rem;
    color: #666;
}

/* FAQ Section */
.vip-faq {
    padding: 80px 0;
}

.vip-faq h2 {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 50px;
}

.faq-item {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    cursor: pointer;
    transition: all 0.3s ease;
}

.faq-item:hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
    color: #1a1a2e;
}

.faq-question i {
    transition: transform 0.3s ease;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    display: none;
    margin-top: 15px;
    color: #666;
    line-height: 1.6;
}

.faq-item.active .faq-answer {
    display: block;
}

/* Potential Earnings */
.potential-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 30px;
    color: white;
    margin-bottom: 30px;
}

.potential-card h4 {
    font-weight: 700;
    margin-bottom: 15px;
}

.potential-amount {
    font-size: 3rem;
    font-weight: 800;
}

@media (max-width: 768px) {
    .vip-hero h1 {
        font-size: 2.5rem;
    }
    
    .pricing-card {
        margin: 0 15px;
        padding: 30px 20px;
    }
    
    .pricing-amount {
        font-size: 3rem;
    }
}
</style>

<div class="vip-hero">
    <div class="container">
        <div class="vip-crown">
            <i class="fas fa-crown"></i>
        </div>
        
        <h1>Hazte <span>VIP</span></h1>
        
        <p class="vip-subtitle">
            Maximiza tus ganancias contactando directamente con usuarios que han visto tus códigos. 
            Convierte visualizaciones en dinero real.
        </p>
        
        <div class="pricing-card">
            <?php if ($is_vip): ?>
                <div class="already-vip">
                    <i class="fas fa-check-circle"></i>
                    <h4>¡Ya eres VIP!</h4>
                    <p>Tu suscripción está activa hasta <?php 
                        if (isset($usuario['vip_expires_at'])) {
                            $expires = $usuario['vip_expires_at'];
                            if ($expires instanceof MongoDB\BSON\UTCDateTime) {
                                echo $expires->toDateTime()->format('d/m/Y');
                            } else {
                                echo $expires;
                            }
                        } else {
                            echo 'sin fecha';
                        }
                    ?></p>
                </div>
                
                <?php if ($viewers_data && $viewers_data['total_viewers'] > 0): ?>
                <div class="potential-card">
                    <h4><i class="fas fa-chart-line"></i> Tu potencial de ganancias</h4>
                    <div class="potential-amount"><?php echo number_format($viewers_data['total_potencial'], 0); ?>€</div>
                    <p><?php echo $viewers_data['total_viewers']; ?> usuarios han visto tus códigos</p>
                </div>
                <?php endif; ?>
                
                <a href="/public/mis_viewers.php" class="btn-subscribe">
                    <i class="fas fa-users"></i>
                    <span class="btn-text">Ver mis viewers</span>
                </a>
            <?php else: ?>
                <div class="pricing-header">
                    <span class="pricing-badge"><i class="fas fa-crown"></i> SUSCRIPCIÓN VIP</span>
                    <div class="pricing-amount">9,99€ <span>/mes</span></div>
                    <div class="pricing-period">Facturación mensual, cancela cuando quieras</div>
                </div>
                
                <div class="pricing-benefits">
                    <div class="benefit-row">
                        <div class="benefit-icon"><i class="fas fa-crown"></i></div>
                        <div class="benefit-text">
                            <h5>Badge VIP Verificado</h5>
                            <p>Destácate del resto de usuarios con un badge dorado exclusivo</p>
                        </div>
                    </div>
                    
                    <div class="benefit-row">
                        <div class="benefit-icon"><i class="fas fa-comments"></i></div>
                        <div class="benefit-text">
                            <h5>Chat ilimitado con Viewers</h5>
                            <p>Contacta directamente con usuarios que han visto tus códigos</p>
                        </div>
                    </div>
                    
                    <div class="benefit-row">
                        <div class="benefit-icon"><i class="fas fa-paper-plane"></i></div>
                        <div class="benefit-text">
                            <h5>Mensajes masivos</h5>
                            <p>Envía mensajes a todos tus potenciales clientes de una vez</p>
                        </div>
                    </div>
                    
                    <div class="benefit-row">
                        <div class="benefit-icon"><i class="fas fa-wallet"></i></div>
                        <div class="benefit-text">
                            <h5>10€ de saldo mensual</h5>
                            <p>Recibe 10€ cada mes para destacar tus códigos o promociones</p>
                        </div>
                    </div>
                </div>
                
                <?php if ($is_logged_in): ?>
                    <button class="btn-subscribe" id="btnSubscribe">
                        <i class="fas fa-bolt"></i>
                        <span class="btn-text">Suscribirme ahora</span>
                        <span class="spinner"><i class="fas fa-spinner fa-spin"></i></span>
                    </button>
                <?php else: ?>
                    <button class="btn-subscribe" onclick="showLoginModal('Inicia sesión para suscribirte a VIP', window.location.href); return false;">
                        <i class="fas fa-user"></i>
                        <span class="btn-text">Iniciar sesión para suscribirse</span>
                    </button>
                    <div class="login-required">
                        <p>¿No tienes cuenta? <a href="/registro">Regístrate gratis</a></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stats Section -->
<section class="vip-stats">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-number">500€+</div>
                    <div class="stat-label">Potencial medio de ganancias</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-number">10€</div>
                    <div class="stat-label">Saldo gratis cada mes</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-number">∞</div>
                    <div class="stat-label">Mensajes ilimitados</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="vip-faq">
    <div class="container">
        <h2>Preguntas frecuentes</h2>
        
        <div class="faq-item">
            <div class="faq-question">
                <span>¿Cómo funciona el sistema de Viewers?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Cuando una persona ve uno de tus códigos de referido, si está registrada, queda registrada como "viewer" de ese código. 
                Como VIP, puedes contactar directamente con esos usuarios para ayudarles a completar el proceso y así ambos ganáis el beneficio. 
                Es una situación win-win: ellos reciben ayuda personalizada y tú aumentas tus conversiones.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                <span>¿Cuándo recibo mis 10€ de saldo?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Recibes 10€ automáticamente el primer día de tu suscripción y cada vez que se renueva (cada mes). 
                Este saldo lo puedes usar para destacar tus códigos o cualquier otra promoción dentro de la plataforma.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                <span>¿Puedo cancelar cuando quiera?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Sí, puedes cancelar tu suscripción en cualquier momento desde tu panel de usuario. 
                Si cancelas, mantendrás los beneficios VIP hasta el final del período que ya has pagado.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                <span>¿Qué es el Badge VIP Verificado?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Es un distintivo dorado que aparece junto a tu nombre y en todos tus códigos. 
                Esto te da credibilidad extra y hace que tus códigos destaquen sobre el resto, 
                lo que aumenta la probabilidad de que los usuarios confíen en ti y usen tus códigos.
            </div>
        </div>
    </div>
</section>

<script>
// FAQ Accordion
document.querySelectorAll('.faq-item').forEach(item => {
    item.addEventListener('click', () => {
        item.classList.toggle('active');
    });
});

// Subscribe button
<?php if ($is_logged_in && !$is_vip): ?>
document.getElementById('btnSubscribe').addEventListener('click', async function() {
    const btn = this;
    btn.classList.add('loading');
    btn.disabled = true;
    
    try {
        const response = await fetch('/crear_sesion_suscripcion_vip.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error || 'No se pudo crear la sesión de pago'
            });
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error de conexión. Inténtalo de nuevo.'
        });
        btn.classList.remove('loading');
        btn.disabled = false;
    }
});
<?php endif; ?>
</script>

<?php
include_once __DIR__ . '/../myphp/_footer.php';
?>
