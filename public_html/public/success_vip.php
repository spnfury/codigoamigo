<?php
/**
 * Página de éxito después de suscribirse a VIP
 */

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión de usuario
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$user_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? '';

// Verificar si ya es VIP (el webhook debería haberlo activado)
$is_vip = es_usuario_vip($user_id);

// FALLBACK: Si no es VIP y tenemos session_id, verificar con Stripe directamente
// Esto cubre el caso donde el webhook falla o tarda demasiado
if (!$is_vip && !empty($session_id)) {
    try {
        require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';
        
        require_once __DIR__ . '/../config/stripe.php';
        $stripe_secret_key = get_stripe_live_secret_key();
        \Stripe\Stripe::setApiKey($stripe_secret_key);
        
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        
        if ($session->payment_status === 'paid') {
            // Verificar que la sesión corresponde al usuario actual
            $session_user_id = $session->metadata->usuario_id ?? null;
            
            if ($session_user_id === $user_id) {
                // Obtener suscripción para calcular expiración
                if (!empty($session->subscription)) {
                    $subscription = \Stripe\Subscription::retrieve($session->subscription);
                    
                    $current_period_end = $subscription->current_period_end ?? time() + (30 * 24 * 60 * 60);
                    $expires_at = new DateTime();
                    $expires_at->setTimestamp($current_period_end);
                    
                    // Activar suscripción VIP manualmente
                    if (activar_vip($user_id, $session->subscription, $expires_at)) {
                        $is_vip = true;
                        log_info("VIP activado por fallback en success_vip.php para usuario $user_id");
                    }
                }
            }
        }
    } catch (Exception $e) {
        log_error("Error en fallback de success_vip.php: " . $e->getMessage());
    }
}

// Obtener información del usuario
$collection_usuarios = getCollectionUsuarios();
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);

// Header
$title = "¡Bienvenido a VIP! | Código Amigo";
$description = "Tu suscripción VIP está activa";
get_header_modern($title, $description, '', '', '', true);
?>

<style>
.success-container {
    max-width: 700px;
    margin: 80px auto;
    padding: 50px;
    text-align: center;
    background: #fff;
    border-radius: 30px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.08);
}

.success-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #ffd700 0%, #E30613 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 35px;
    box-shadow: 0 15px 40px rgba(227, 6, 19, 0.3);
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
    40% {transform: translateY(-20px);}
    60% {transform: translateY(-10px);}
}

.success-icon i {
    font-size: 60px;
    color: white;
}

.success-title {
    font-size: 3rem;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 20px;
    line-height: 1.2;
}

.success-title .vip-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #ffd700 0%, #E30613 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 50px;
    font-size: 1.2rem;
    vertical-align: middle;
    margin-left: 15px;
    box-shadow: 0 5px 20px rgba(227, 6, 19, 0.25);
}

.success-description {
    font-size: 1.25rem;
    color: #555;
    margin-bottom: 40px;
    line-height: 1.6;
    max-width: 90%;
    margin-left: auto;
    margin-right: auto;
}

.benefits-list {
    background: #f8f9fa;
    border-radius: 20px;
    padding: 30px 40px;
    margin-bottom: 40px;
    text-align: left;
    border: 1px solid #eef2f7;
}

.benefits-list h4 {
    color: #1a1a2e;
    margin-bottom: 25px;
    font-weight: 800;
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 15px 0;
    border-bottom: 1px solid #e9ecef;
}

.benefit-item i {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #ffd700 0%, #E30613 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    flex-shrink: 0;
    box-shadow: 0 5px 15px rgba(227, 6, 19, 0.15);
}

.benefit-item span {
    flex: 1;
    font-size: 1.1rem;
    color: #333;
}

.action-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-top: 40px;
}

.btn-primary-vip {
    background: linear-gradient(135deg, #ffd700 0%, #E30613 100%);
    color: white;
    padding: 18px 40px;
    border-radius: 50px;
    font-weight: 800;
    font-size: 1.2rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 10px 30px rgba(227, 6, 19, 0.3);
}

.btn-primary-vip:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(227, 6, 19, 0.4);
    color: white;
    text-decoration: none;
}

.btn-secondary-vip {
    background: white;
    color: #1a1a2e;
    padding: 18px 40px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1.1rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
}

.btn-secondary-vip:hover {
    background: #f8f9fa;
    border-color: #d1d5db;
    color: #1a1a2e;
    text-decoration: none;
    transform: translateY(-2px);
}

.saldo-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 25px;
    padding: 30px;
    margin-bottom: 40px;
    box-shadow: 0 15px 35px rgba(118, 75, 162, 0.25);
    position: relative;
    overflow: hidden;
}

.saldo-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=');
    mask-image: radial-gradient(white, transparent);
    pointer-events: none;
}

.saldo-amount {
    font-size: 3.5rem;
    font-weight: 800;
    margin: 10px 0;
    text-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.waiting-message {
    background: #fff8e1;
    border: 1px solid #ffe57f;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    color: #b38f00;
    font-size: 1.1rem;
    font-weight: 600;
}
</style>

<div class="success-container">
    <div class="success-icon">
        <i class="fas fa-crown"></i>
    </div>
    
    <h1 class="success-title">
        ¡Bienvenido!
        <span class="vip-badge"><i class="fas fa-crown"></i> VIP</span>
    </h1>
    
    <?php if (!$is_vip): ?>
    <div class="waiting-message">
        <i class="fas fa-spinner fa-spin"></i> 
        Tu suscripción se está procesando. Esto puede tardar unos segundos...
    </div>
    <script>
        // Recargar la página después de 5 segundos si no es VIP todavía
        setTimeout(function() {
            location.reload();
        }, 5000);
    </script>
    <?php else: ?>

    <!-- Tracking de conversión VIP (GA4) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-DVE5FZ2SZY"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-DVE5FZ2SZY');
        // Evento purchase una sola vez por sesión de checkout (evita doble conteo en reload)
        (function(){
            var sid = <?php echo json_encode($session_id ?: ('vip_' . $user_id)); ?>;
            var key = 'vip_purchase_' + sid;
            if (!sessionStorage.getItem(key)) {
                sessionStorage.setItem(key, '1');
                gtag('event', 'purchase', {
                    transaction_id: sid,
                    value: 9.99,
                    currency: 'EUR',
                    items: [{ item_id: 'vip_subscription', item_name: 'Suscripción VIP', price: 9.99, quantity: 1 }]
                });
            }
        })();
    </script>

    <p class="success-description">
        Tu suscripción VIP está activa. Ahora tienes acceso a todas las ventajas exclusivas para maximizar tus ganancias.
    </p>
    
    <div class="saldo-card">
        <h5><i class="fas fa-wallet"></i> Tu nuevo saldo</h5>
        <div class="saldo-amount"><?php echo number_format($usuario['saldo'] ?? 0, 2); ?>€</div>
        <small>+10€ añadidos automáticamente</small>
    </div>
    
    <div class="benefits-list">
        <h4><i class="fas fa-gift"></i> Tus nuevos beneficios VIP</h4>
        
        <div class="benefit-item">
            <i class="fas fa-crown"></i>
            <span><strong>Badge VIP Verificado</strong> visible en todos tus códigos</span>
        </div>
        
        <div class="benefit-item">
            <i class="fas fa-comments"></i>
            <span><strong>Chat ilimitado</strong> con usuarios que han visto tus códigos</span>
        </div>
        
        <div class="benefit-item">
            <i class="fas fa-paper-plane"></i>
            <span><strong>Mensajes masivos</strong> a todos tus potenciales clientes</span>
        </div>
        
        <div class="benefit-item">
            <i class="fas fa-wallet"></i>
            <span><strong>10€ de saldo</strong> recargados cada mes automáticamente</span>
        </div>
    </div>
    
    <?php endif; ?>
    
    <div class="action-buttons">
        <a href="/public/mis_viewers.php" class="btn-primary-vip">
            <i class="fas fa-crosshairs"></i> Ver mis leads
        </a>
        <a href="/mis-anuncios" class="btn-secondary-vip">
            <i class="fas fa-code"></i> Mis códigos
        </a>
    </div>
</div>

<?php
include_once __DIR__ . '/../myphp/_footer.php';
?>
