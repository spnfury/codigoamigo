<?php
// La sesión ya está iniciada en app_with_mongo.php

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header("Location: /");
    exit;
}

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';
include_once __DIR__ . '/inc/funciones.php';

// Obtener datos del usuario
$usuario = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
if (!$usuario) {
    header("Location: /");
    exit;
}

// Generar o obtener código de referido
$codigo_referido = generarCodigoReferido($_SESSION["user_id"], $data_usuario);

// Obtener lista de amigos referidos
$amigos_referidos = obtenerAmigosReferidos($_SESSION["user_id"]);

// Obtener estadísticas de referidos
$estadisticas = obtenerEstadisticasReferidos($_SESSION["user_id"]);

// Configurar variables para el header
$title = "Invita a tus amigos y gana dinero - Código Amigo";
$description = "Invita a tus amigos a Código Amigo. Tu amigo recibe 5€ al registrarse y tú ganas otros 5€ cuando publique su primer código. ¡Comparte tu código de referido!";
$title_social = "Invita a tus amigos y gana dinero";
$description_social = "Tu amigo gana 5€ al registrarse y tú ganas 5€ cuando publique su primer código.";

// Incluir header
get_header_modern($title, $description, $title_social, $description_social);
?>

<div class="referral-page">
    <div class="referral-container">

        <!-- Hero Header -->
        <div class="referral-hero">
            <div class="referral-hero-glow"></div>
            <div class="referral-hero-content">
                <div class="referral-hero-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <h1 class="referral-hero-title">Invita a tus amigos</h1>
                <p class="referral-hero-subtitle">
                    ¡Gana dinero compartiendo! Tu amigo recibe <strong>5€ al registrarse</strong> y tú ganas <strong>otros 5€</strong> cuando publique su primer código.
                </p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="referral-stats">
            <div class="referral-stat-card">
                <div class="referral-stat-icon" style="--stat-color: #E30613;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="referral-stat-value" style="color: #E30613;"><?php echo $estadisticas['total_referidos']; ?></div>
                <div class="referral-stat-label">Amigos invitados</div>
            </div>
            <div class="referral-stat-card">
                <div class="referral-stat-icon" style="--stat-color: #00c853;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="referral-stat-value" style="color: #00c853;"><?php echo $estadisticas['referidos_verificados']; ?></div>
                <div class="referral-stat-label">Verificados</div>
            </div>
            <div class="referral-stat-card">
                <div class="referral-stat-icon" style="--stat-color: #00c853;">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="referral-stat-value" style="color: #00c853;"><?php echo $estadisticas['dinero_ganado']; ?>€</div>
                <div class="referral-stat-label">Dinero ganado</div>
            </div>
            <div class="referral-stat-card">
                <div class="referral-stat-icon" style="--stat-color: #ffab00;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="referral-stat-value" style="color: #ffab00;"><?php echo $estadisticas['dinero_pendiente']; ?>€</div>
                <div class="referral-stat-label">Pendiente</div>
            </div>
        </div>

        <!-- Referral Code & Share Section -->
        <div class="referral-code-card">
            <h2 class="referral-section-title">
                <i class="fas fa-code"></i> Tu código de referido
            </h2>

            <div class="referral-code-display">
                <span class="referral-code-text" id="referral-code"><?php echo $codigo_referido; ?></span>
                <button class="referral-btn-copy" onclick="copiarCodigo()" title="Copiar código">
                    <i class="fas fa-copy"></i> Copiar código
                </button>
            </div>

            <div class="referral-link-section">
                <h4 class="referral-link-title">
                    <i class="fas fa-link"></i> Enlace de referido
                </h4>
                <div class="referral-link-input-wrapper">
                    <input type="text" class="referral-link-input" id="referral-link" value="<?php echo htmlspecialchars('https://www.codigoamigo.com/registro?ref=' . $codigo_referido); ?>" readonly>
                    <button class="referral-btn-copy-link" onclick="copiarEnlace()" title="Copiar enlace">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <!-- Share Buttons -->
            <div class="referral-share">
                <h4 class="referral-share-title">
                    <i class="fas fa-share-alt"></i> Compartir en redes sociales
                </h4>
                <div class="referral-share-buttons">
                    <button class="referral-share-btn referral-share-whatsapp" onclick="compartirWhatsApp()">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </button>
                    <button class="referral-share-btn referral-share-facebook" onclick="compartirFacebook()">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </button>
                    <button class="referral-share-btn referral-share-twitter" onclick="compartirTwitter()">
                        <i class="fab fa-twitter"></i> Twitter
                    </button>
                    <button class="referral-share-btn referral-share-email" onclick="compartirEmail()">
                        <i class="fas fa-envelope"></i> Email
                    </button>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div class="referral-how-it-works">
            <h2 class="referral-section-title">
                <i class="fas fa-question-circle"></i> ¿Cómo funciona?
            </h2>
            <div class="referral-steps">
                <div class="referral-step">
                    <div class="referral-step-number" style="--step-color: #E30613;">1</div>
                    <div class="referral-step-content">
                        <h4 class="referral-step-title">Comparte tu código</h4>
                        <p class="referral-step-desc">Comparte tu código de referido o enlace con tus amigos</p>
                    </div>
                </div>
                <div class="referral-step-connector"></div>
                <div class="referral-step">
                    <div class="referral-step-number" style="--step-color: #00c853;">2</div>
                    <div class="referral-step-content">
                        <h4 class="referral-step-title">Tu amigo se registra</h4>
                        <p class="referral-step-desc">Tu amigo se registra usando tu código de referido</p>
                    </div>
                </div>
                <div class="referral-step-connector"></div>
                <div class="referral-step">
                    <div class="referral-step-number" style="--step-color: #ffd700;">3</div>
                    <div class="referral-step-content">
                        <h4 class="referral-step-title">¡Ganáis 5€ cada uno!</h4>
                        <p class="referral-step-desc">Tu amigo recibe 5€ al verificar su email. Tú ganas otros 5€ cuando publique su primer código.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referral Friends List -->
        <?php if (!empty($amigos_referidos)): ?>
        <div class="referral-friends-card">
            <h2 class="referral-section-title">
                <i class="fas fa-users"></i> Tus amigos referidos
            </h2>
            <div class="referral-friends-list">
                <?php foreach ($amigos_referidos as $amigo): ?>
                <div class="referral-friend-row">
                    <div class="referral-friend-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="referral-friend-info">
                        <span class="referral-friend-name"><?php echo htmlspecialchars($amigo['username']); ?></span>
                        <span class="referral-friend-date"><?php echo $amigo['fecha_registro']; ?></span>
                    </div>
                    <div class="referral-friend-status">
                        <?php if ($amigo['estado'] == 1): ?>
                            <span class="referral-badge referral-badge-verified">
                                <i class="fas fa-check-circle"></i> Verificado
                            </span>
                        <?php else: ?>
                            <span class="referral-badge referral-badge-pending">
                                <i class="fas fa-clock"></i> Pendiente
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="referral-friend-reward">
                        <?php if ($amigo['estado'] == 1): ?>
                            <span class="referral-reward-earned">+5€</span>
                        <?php else: ?>
                            <span class="referral-reward-pending">Pendiente</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="referral-empty-state">
            <div class="referral-empty-icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <h3 class="referral-empty-title">Aún no has invitado a nadie</h3>
            <p class="referral-empty-desc">¡Comparte tu código de referido para empezar a ganar dinero!</p>
        </div>
        <?php endif; ?>

    </div>
</div>

<style>
/* ===== Referral Page ===== */
.referral-page {
    min-height: 100vh;
    background-color: #2C2C2C;
    padding: 30px 15px 60px;
}

.referral-container {
    max-width: 800px;
    margin: 0 auto;
}

/* --- Hero --- */
.referral-hero {
    position: relative;
    background: linear-gradient(135deg, #1a1a2e 0%, #2C2C2C 40%, #E30613 100%);
    border-radius: 20px;
    padding: 50px 30px;
    text-align: center;
    margin-bottom: 30px;
    overflow: hidden;
    border: 1px solid rgba(227, 6, 19, 0.3);
}

.referral-hero-glow {
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(227, 6, 19, 0.3) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.referral-hero-content {
    position: relative;
    z-index: 1;
}

.referral-hero-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2rem;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.15);
    animation: pulse-glow 3s ease-in-out infinite;
}

@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 20px rgba(227, 6, 19, 0.2); }
    50% { box-shadow: 0 0 40px rgba(227, 6, 19, 0.4); }
}

.referral-hero-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 12px 0;
    letter-spacing: -0.5px;
}

.referral-hero-subtitle {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.85);
    margin: 0;
    line-height: 1.6;
    max-width: 500px;
    margin: 0 auto;
}

.referral-hero-subtitle strong {
    color: #ffd700;
    font-weight: 700;
}

/* --- Stats --- */
.referral-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.referral-stat-card {
    background: #2A2A2A;
    border: 1px solid #444;
    border-radius: 16px;
    padding: 24px 16px;
    text-align: center;
    transition: all 0.3s ease;
}

.referral-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    border-color: #555;
}

.referral-stat-icon {
    width: 44px;
    height: 44px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 1.1rem;
    color: var(--stat-color, #E30613);
}

.referral-stat-value {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 4px;
    line-height: 1.2;
}

.referral-stat-label {
    font-size: 0.8rem;
    color: #999;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* --- Section Titles --- */
.referral-section-title {
    color: #fff;
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0 0 24px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.referral-section-title i {
    color: #E30613;
    font-size: 1.2rem;
}

/* --- Code Card --- */
.referral-code-card {
    background: #2A2A2A;
    border: 1px solid #444;
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 30px;
}

.referral-code-display {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    background: rgba(227, 6, 19, 0.08);
    border: 2px dashed rgba(227, 6, 19, 0.3);
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 28px;
}

.referral-code-text {
    font-size: 2rem;
    font-weight: 800;
    color: #E30613;
    letter-spacing: 3px;
    font-family: 'Courier New', monospace;
}

.referral-btn-copy {
    background: linear-gradient(135deg, #E30613, #ff4444);
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.referral-btn-copy:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(227, 6, 19, 0.3);
}

.referral-btn-copy:active {
    transform: translateY(0);
}

/* --- Link Section --- */
.referral-link-section {
    margin-bottom: 28px;
}

.referral-link-title {
    color: #ccc;
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.referral-link-title i {
    color: #E30613;
}

.referral-link-input-wrapper {
    display: flex;
    align-items: stretch;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #555;
    background: #333;
}

.referral-link-input {
    flex: 1;
    background: #333;
    border: none;
    color: #ccc;
    padding: 14px 16px;
    font-size: 0.9rem;
    font-family: 'Courier New', monospace;
    outline: none;
}

.referral-btn-copy-link {
    background: #E30613;
    color: #fff;
    border: none;
    padding: 14px 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1rem;
}

.referral-btn-copy-link:hover {
    background: #ff2233;
}

/* --- Share Buttons --- */
.referral-share {
    text-align: center;
}

.referral-share-title {
    color: #ccc;
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 16px 0;
}

.referral-share-title i {
    color: #E30613;
}

.referral-share-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.referral-share-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 50px;
    border: none;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.referral-share-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.referral-share-whatsapp { background: #25d366; }
.referral-share-facebook { background: #1877f2; }
.referral-share-twitter { background: #1da1f2; }
.referral-share-email { background: #555; }

.referral-share-whatsapp:hover { background: #1fb855; }
.referral-share-facebook:hover { background: #166bdb; }
.referral-share-twitter:hover { background: #1a91da; }
.referral-share-email:hover { background: #666; }

/* --- How It Works --- */
.referral-how-it-works {
    background: #2A2A2A;
    border: 1px solid #444;
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 30px;
}

.referral-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
}

.referral-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex: 1;
    padding: 0 12px;
}

.referral-step-number {
    width: 56px;
    height: 56px;
    background: var(--step-color, #E30613);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    font-weight: 800;
    margin-bottom: 14px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.referral-step:hover .referral-step-number {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
}

.referral-step-connector {
    width: 50px;
    height: 2px;
    background: linear-gradient(90deg, #555, #777, #555);
    flex-shrink: 0;
    margin-bottom: 40px;
}

.referral-step-title {
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 6px 0;
}

.referral-step-desc {
    color: #999;
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.5;
}

/* --- Friends List --- */
.referral-friends-card {
    background: #2A2A2A;
    border: 1px solid #444;
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 30px;
}

.referral-friends-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.referral-friend-row {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border-bottom: 1px solid #3a3a3a;
    transition: background 0.2s ease;
    border-radius: 10px;
}

.referral-friend-row:last-child {
    border-bottom: none;
}

.referral-friend-row:hover {
    background: rgba(255, 255, 255, 0.03);
}

.referral-friend-avatar {
    width: 42px;
    height: 42px;
    background: #444;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 1rem;
    flex-shrink: 0;
}

.referral-friend-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.referral-friend-name {
    color: #fff;
    font-weight: 600;
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.referral-friend-date {
    color: #777;
    font-size: 0.8rem;
}

.referral-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.referral-badge-verified {
    background: rgba(0, 200, 83, 0.15);
    color: #00c853;
    border: 1px solid rgba(0, 200, 83, 0.3);
}

.referral-badge-pending {
    background: rgba(255, 171, 0, 0.15);
    color: #ffab00;
    border: 1px solid rgba(255, 171, 0, 0.3);
}

.referral-reward-earned {
    color: #00c853;
    font-weight: 800;
    font-size: 1.1rem;
}

.referral-reward-pending {
    color: #777;
    font-size: 0.85rem;
}

/* --- Empty State --- */
.referral-empty-state {
    background: #2A2A2A;
    border: 1px solid #444;
    border-radius: 20px;
    padding: 60px 30px;
    text-align: center;
}

.referral-empty-icon {
    font-size: 4rem;
    color: #444;
    margin-bottom: 20px;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.referral-empty-title {
    color: #ccc;
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0 0 10px 0;
}

.referral-empty-desc {
    color: #777;
    font-size: 0.95rem;
    margin: 0;
}

/* --- Toast Notification --- */
.referral-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 99999;
    background: rgba(42, 42, 42, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid #555;
    border-radius: 14px;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
    transform: translateX(120%);
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    min-width: 280px;
}

.referral-toast.show {
    transform: translateX(0);
}

.referral-toast.success {
    border-color: rgba(0, 200, 83, 0.5);
}

.referral-toast.success i {
    color: #00c853;
}

.referral-toast.error {
    border-color: rgba(227, 6, 19, 0.5);
}

.referral-toast.error i {
    color: #E30613;
}

.referral-toast i {
    font-size: 1.2rem;
}

/* --- Responsive --- */
@media (max-width: 768px) {
    .referral-page {
        padding: 15px 10px 40px;
    }

    .referral-hero {
        padding: 35px 20px;
        border-radius: 16px;
    }

    .referral-hero-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }

    .referral-hero-title {
        font-size: 1.8rem;
    }

    .referral-hero-subtitle {
        font-size: 0.95rem;
    }

    .referral-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .referral-stat-value {
        font-size: 1.5rem;
    }

    .referral-code-card,
    .referral-how-it-works,
    .referral-friends-card,
    .referral-empty-state {
        padding: 24px 18px;
        border-radius: 16px;
    }

    .referral-code-display {
        flex-direction: column;
        gap: 12px;
        padding: 20px 16px;
    }

    .referral-code-text {
        font-size: 1.5rem;
    }

    .referral-link-input {
        font-size: 0.8rem;
        padding: 12px;
    }

    .referral-share-btn {
        padding: 8px 14px;
        font-size: 0.8rem;
    }

    .referral-steps {
        flex-direction: column;
        gap: 0;
    }

    .referral-step {
        flex-direction: row;
        text-align: left;
        gap: 16px;
        padding: 12px 0;
    }

    .referral-step-number {
        margin-bottom: 0;
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        font-size: 1.2rem;
    }

    .referral-step-connector {
        width: 2px;
        height: 24px;
        margin-bottom: 0;
        margin-left: 23px;
    }

    .referral-friend-row {
        flex-wrap: wrap;
        gap: 10px;
        padding: 14px 12px;
    }

    .referral-friend-info {
        flex: 1;
        min-width: 100px;
    }

    .referral-friend-status {
        order: 4;
    }

    .referral-friend-reward {
        order: 5;
        margin-left: auto;
    }
}

@media (max-width: 480px) {
    .referral-hero-title {
        font-size: 1.5rem;
    }

    .referral-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .referral-share-buttons {
        flex-direction: column;
        align-items: stretch;
    }

    .referral-share-btn {
        justify-content: center;
    }
}
</style>

<script>
// Función para copiar código
function copiarCodigo() {
    const codigo = '<?php echo htmlspecialchars($codigo_referido); ?>';
    navigator.clipboard.writeText(codigo).then(function() {
        mostrarNotificacion('¡Código copiado al portapapeles!', 'success');
    }).catch(function(err) {
        console.error('Error al copiar: ', err);
        mostrarNotificacion('Error al copiar el código', 'error');
    });
}

// Función para copiar enlace
function copiarEnlace() {
    const enlace = document.getElementById('referral-link').value;
    navigator.clipboard.writeText(enlace).then(function() {
        mostrarNotificacion('¡Enlace copiado al portapapeles!', 'success');
    }).catch(function(err) {
        console.error('Error al copiar: ', err);
        mostrarNotificacion('Error al copiar el enlace', 'error');
    });
}

// Función para compartir en WhatsApp
function compartirWhatsApp() {
    const referralUrl = 'https://www.codigoamigo.com/registro?ref=<?php echo htmlspecialchars($codigo_referido); ?>';
    const texto = '¡Únete a Código Amigo y encuentra los mejores descuentos! Usa mi código de referido: <?php echo htmlspecialchars($codigo_referido); ?> - ' + referralUrl;
    const url = 'https://wa.me/?text=' + encodeURIComponent(texto);
    window.open(url, '_blank');
}

// Función para compartir en Facebook
function compartirFacebook() {
    const referralUrl = 'https://www.codigoamigo.com/registro?ref=<?php echo htmlspecialchars($codigo_referido); ?>';
    const url = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(referralUrl);
    window.open(url, '_blank', 'width=600,height=400');
}

// Función para compartir en Twitter
function compartirTwitter() {
    const referralUrl = 'https://www.codigoamigo.com/registro?ref=<?php echo htmlspecialchars($codigo_referido); ?>';
    const texto = '¡Únete a Código Amigo y encuentra los mejores descuentos! Usa mi código: <?php echo htmlspecialchars($codigo_referido); ?>';
    const url = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(texto) + '&url=' + encodeURIComponent(referralUrl);
    window.open(url, '_blank', 'width=600,height=400');
}

// Función para compartir por email
function compartirEmail() {
    const referralUrl = 'https://www.codigoamigo.com/registro?ref=<?php echo htmlspecialchars($codigo_referido); ?>';
    const asunto = 'Únete a Código Amigo - Código de referido';
    const cuerpo = `¡Hola!\n\nTe invito a unirte a Código Amigo, donde puedes encontrar los mejores códigos de descuento y cupones verificados.\n\nUsa mi código de referido: <?php echo htmlspecialchars($codigo_referido); ?>\n\nRegístrate aquí: ${referralUrl}\n\n¡Espero verte pronto en Código Amigo!`;
    const url = 'mailto:?subject=' + encodeURIComponent(asunto) + '&body=' + encodeURIComponent(cuerpo);
    window.location.href = url;
}

// Notificación toast premium
function mostrarNotificacion(mensaje, tipo) {
    // Remove any existing toast
    const old = document.querySelector('.referral-toast');
    if (old) old.remove();

    const toast = document.createElement('div');
    toast.className = 'referral-toast ' + tipo;
    const icon = tipo === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    toast.innerHTML = '<i class="' + icon + '"></i> ' + mensaje;
    document.body.appendChild(toast);

    // Trigger slide-in
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            toast.classList.add('show');
        });
    });

    // Auto-remove after 3s
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() { toast.remove(); }, 400);
    }, 3000);
}
</script>

<?php
// Incluir footer
include_once __DIR__ . '/myphp/_footer.php';
?>
