<?php
/**
 * Página de éxito después de suscribirse a VIP
 */

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

// Verificar sesión de usuario
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$user_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? '';

// Verificar si ya es VIP (el webhook debería haberlo activado)
$is_vip = es_usuario_vip($user_id);

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
    max-width: 600px;
    margin: 60px auto;
    padding: 40px;
    text-align: center;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    box-shadow: 0 10px 40px rgba(255, 215, 0, 0.3);
}

.success-icon i {
    font-size: 50px;
    color: white;
}

.success-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 15px;
}

.success-title .vip-badge {
    display: inline-block;
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 1rem;
    vertical-align: middle;
    margin-left: 10px;
}

.success-description {
    font-size: 1.2rem;
    color: #666;
    margin-bottom: 30px;
    line-height: 1.6;
}

.benefits-list {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    text-align: left;
}

.benefits-list h4 {
    color: #1a1a2e;
    margin-bottom: 20px;
    font-weight: 700;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
}

.benefit-item:last-child {
    border-bottom: none;
}

.benefit-item i {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.benefit-item span {
    flex: 1;
    font-size: 1rem;
    color: #333;
}

.benefit-item strong {
    color: #ff8c00;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary-vip {
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    color: white;
    padding: 15px 30px;
    border-radius: 30px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    border: none;
}

.btn-primary-vip:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 140, 0, 0.3);
    color: white;
    text-decoration: none;
}

.btn-secondary-vip {
    background: white;
    color: #333;
    padding: 15px 30px;
    border-radius: 30px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
}

.btn-secondary-vip:hover {
    background: #f8f9fa;
    color: #333;
    text-decoration: none;
}

.saldo-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
}

.saldo-card h5 {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 5px;
}

.saldo-amount {
    font-size: 2.5rem;
    font-weight: 800;
}

.waiting-message {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    color: #856404;
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
            <i class="fas fa-users"></i> Ver mis viewers
        </a>
        <a href="/mis-anuncios" class="btn-secondary-vip">
            <i class="fas fa-code"></i> Mis códigos
        </a>
    </div>
</div>

<?php
include_once __DIR__ . '/../myphp/_footer.php';
?>
