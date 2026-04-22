<?php
// Inicializar variables globales
$GLOBALS['website'] = 'https://www.codigoamigo.com/';
$GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_modern.php';
include_once __DIR__ . '/../myphp/_header_modern.php';
include_once __DIR__ . '/../myphp/funciones_faq_frontend.php';

$GLOBALS['header_modern_used'] = true;

// Inicializar detector de móviles
if (!isset($detect)) {
    $detect = new Mobile_Detect();
}
$GLOBALS['detect'] = $detect;

// Título y descripción de la página
$title = "Preguntas Frecuentes - CodigoAmigo.com";
$description = "Encuentra respuestas a las preguntas más frecuentes sobre CodigoAmigo.com, cómo destacar tus códigos, beneficios VIP, niveles de confianza y más.";
$imagen_social = "https://www.codigoamigo.com/img/logo_social_codigoamigo_final.jpg";

// Llamar al header moderno
get_header_modern($title, $description, $title, $description, $imagen_social);
?>

<style>
.page-container {
    max-width: 960px;
    margin: 40px auto;
    padding: 20px;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #E30613;
    margin-bottom: 10px;
    text-align: center;
}

.page-description {
    font-size: 1.1rem;
    color: #cccccc;
    text-align: center;
    margin-bottom: 40px;
    line-height: 1.6;
}

/* FAQ Sections */
.faq-section {
    margin-bottom: 50px;
}

.faq-section h2 {
    font-size: 1.8rem;
    color: #ffffff;
    margin-bottom: 25px;
    padding-bottom: 10px;
    border-bottom: 2px solid #E30613;
}

.faq-item {
    background: #333;
    border-radius: 8px;
    margin-bottom: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item:hover {
    box-shadow: 0 4px 12px rgba(227, 6, 19, 0.2);
}

.faq-question {
    background: none;
    border: none;
    width: 100%;
    padding: 20px 25px;
    text-align: left;
    font-size: 1.05rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.faq-question:hover {
    background: #404040;
}

.faq-question.active {
    background: #404040;
    color: #E30613;
}

.faq-icon {
    font-size: 1.2rem;
    color: #E30613;
    transition: transform 0.3s ease;
    flex-shrink: 0;
    margin-left: 15px;
}

.faq-question.active .faq-icon {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 25px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
    background: #2c2c2c;
}

.faq-answer.active {
    padding: 20px 25px;
    max-height: 2000px;
}

.faq-answer-content {
    color: #cccccc;
    line-height: 1.8;
    font-size: 1rem;
}

.faq-answer-content p {
    margin-bottom: 12px;
}

.faq-answer-content p:last-child {
    margin-bottom: 0;
}

.faq-answer-content ul, .faq-answer-content ol {
    margin: 12px 0;
    padding-left: 25px;
}

.faq-answer-content li {
    margin-bottom: 8px;
}

.faq-answer-content a {
    color: #E30613;
    text-decoration: none;
}

.faq-answer-content a:hover {
    text-decoration: underline;
}

/* CTA Banners */
.cta-banner {
    background: linear-gradient(135deg, #E30613 0%, #f7931e 100%);
    border-radius: 16px;
    padding: 35px 30px;
    text-align: center;
    margin: 40px 0;
    position: relative;
    overflow: hidden;
}

.cta-banner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
}

.cta-banner h3 {
    color: white;
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}

.cta-banner p {
    color: rgba(255,255,255,0.9);
    font-size: 1.05rem;
    margin-bottom: 20px;
    position: relative;
    z-index: 1;
}

.cta-btn {
    display: inline-block;
    padding: 14px 35px;
    background: white;
    color: #E30613;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.cta-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    color: #E30613;
    text-decoration: none;
}

.cta-btn-dark {
    background: #1a1a2e;
    color: #10b981;
    border: 2px solid #10b981;
}

.cta-btn-dark:hover {
    background: #10b981;
    color: white;
}

/* Pricing Cards Inline */
.pricing-comparison {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.pricing-mini-card {
    background: #404040;
    border-radius: 14px;
    padding: 25px;
    text-align: center;
    border: 2px solid #555;
    transition: all 0.3s ease;
    position: relative;
}

.pricing-mini-card:hover {
    border-color: #E30613;
    transform: translateY(-3px);
}

.pricing-mini-card.popular {
    border-color: #E30613;
    background: linear-gradient(145deg, #404040, #4a3030);
}

.pricing-mini-card.popular::before {
    content: "⭐ MÁS POPULAR";
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: #E30613;
    color: white;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
}

.pricing-mini-card.gold {
    border-color: #FFD700;
    background: linear-gradient(145deg, #404040, #4a4530);
}

.pricing-mini-card h4 {
    color: white;
    margin-bottom: 8px;
    font-size: 1.15rem;
}

.pricing-mini-card .price {
    font-size: 2rem;
    font-weight: 800;
    color: #E30613;
    margin-bottom: 5px;
}

.pricing-mini-card.gold .price {
    color: #FFD700;
}

.pricing-mini-card .duration {
    color: #999;
    font-size: 0.85rem;
    margin-bottom: 12px;
}

.pricing-mini-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.pricing-mini-card ul li {
    color: #ccc;
    padding: 5px 0;
    font-size: 0.9rem;
}

.pricing-mini-card ul li i {
    color: #10b981;
    margin-right: 8px;
    width: 16px;
}

/* Trust Level Visual */
.trust-level-visual {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 15px 0;
}

.trust-row {
    display: flex;
    align-items: center;
    gap: 15px;
    background: #404040;
    border-radius: 10px;
    padding: 12px 18px;
    transition: all 0.2s ease;
}

.trust-row:hover {
    background: #4a4a4a;
    transform: translateX(5px);
}

.trust-stars {
    min-width: 100px;
    font-size: 0.95rem;
}

.trust-label-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    min-width: 110px;
    text-align: center;
}

.trust-description {
    color: #aaa;
    font-size: 0.9rem;
    flex: 1;
}

/* VIP Banner */
.vip-highlight-box {
    background: linear-gradient(145deg, #1a1a2e, #16213e);
    border: 1px solid rgba(245,158,11,0.3);
    border-radius: 16px;
    padding: 30px;
    margin: 25px 0;
}

.vip-highlight-box h4 {
    color: #fbbf24;
    font-size: 1.3rem;
    margin-bottom: 15px;
}

.vip-highlight-box ul {
    list-style: none;
    padding: 0;
}

.vip-highlight-box ul li {
    color: #ddd;
    padding: 8px 0;
    font-size: 0.95rem;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.vip-highlight-box ul li:last-child {
    border-bottom: none;
}

.vip-highlight-box ul li i {
    color: #fbbf24;
    margin-right: 10px;
    width: 20px;
}

/* Contact */
.contact-section {
    background: #333;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    margin-top: 50px;
}

.contact-section h3 {
    color: #E30613;
    margin-bottom: 15px;
}

.contact-section p {
    color: #cccccc;
    margin-bottom: 20px;
}

.contact-button {
    display: inline-block;
    padding: 12px 30px;
    background: #E30613;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.contact-button:hover {
    background: #C40510;
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

@media (max-width: 768px) {
    .page-container { padding: 15px; }
    .page-title { font-size: 2rem; }
    .faq-question { padding: 15px 20px; font-size: 1rem; }
    .faq-answer.active { padding: 15px 20px; }
    .pricing-comparison { grid-template-columns: 1fr; }
    .trust-row { flex-wrap: wrap; }
    .cta-banner { padding: 25px 20px; }
    .cta-banner h3 { font-size: 1.3rem; }
}
</style>

<div class="page-container">
    <h1 class="page-title">Preguntas Frecuentes</h1>
    <p class="page-description">
        Todo lo que necesitas saber para sacarle el máximo partido a CodigoAmigo.com
    </p>

    <!-- ==================== SECTION 1: GENERAL ==================== -->
    <div class="faq-section">
        <h2><i class="fas fa-info-circle" style="color: #E30613; margin-right: 10px;"></i>General</h2>
        
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Qué es CodigoAmigo.com?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>CodigoAmigo.com es la <strong>comunidad más grande de España</strong> dedicada a compartir códigos de descuento verificados. Miles de usuarios comparten sus mejores hallazgos cada día, y tú puedes <strong>ganar dinero</strong> recomendando códigos a tus amigos.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo puedo ganar dinero compartiendo códigos?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Cuando compartes un código de descuento y alguien lo usa, puedes recibir beneficios como:</p>
                    <ul>
                        <li>💰 <strong>Dinero en efectivo</strong></li>
                        <li>🎫 <strong>Puntos o créditos</strong></li>
                        <li>🎁 <strong>Servicios gratuitos</strong></li>
                        <li>🏷️ <strong>Descuentos exclusivos</strong></li>
                    </ul>
                    <p>Los beneficios varían según la marca y el tipo de código que compartas. Cuanto más visible sea tu código, más posibilidades tienes de generar ingresos.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Es gratis usar CodigoAmigo.com?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>¡Sí! Publicar y buscar códigos es <strong>100% gratuito</strong>. Opcionalmente, puedes <strong>destacar tus códigos</strong> desde solo <strong>0,99€</strong> para multiplicar tus resultados y aparecer en las primeras posiciones.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== CTA: PUBLICAR CÓDIGO ==================== -->
    <div class="cta-banner" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
        <h3>🎉 ¿Todavía no has publicado tu código?</h3>
        <p>Es gratis, tardas 30 segundos y puedes empezar a ganar dinero hoy mismo</p>
        <a href="/nuevo_codigo" class="cta-btn">Publicar mi código ahora</a>
    </div>

    <!-- ==================== SECTION 2: DESTACAR CÓDIGO ==================== -->
    <div class="faq-section">
        <h2><i class="fas fa-star" style="color: #fbbf24; margin-right: 10px;"></i>Destacar tu Código</h2>
        
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Qué es destacar un código y por qué debería hacerlo?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Destacar tu código significa <strong>ponerlo en primera posición</strong> dentro de la página de una marca, por delante de todos los códigos normales. Los códigos destacados obtienen:</p>
                    <ul>
                        <li>👁️ Hasta <strong>5x más visibilidad</strong> que un código normal</li>
                        <li>⭐ Un <strong>badge Destacado</strong> visible para los usuarios</li>
                        <li>📈 Más clicks = más personas usando tu código = <strong>más ganancias</strong></li>
                        <li>🏆 Prioridad sobre otros códigos de la misma marca</li>
                    </ul>
                    <p>Es la forma más rápida de <strong>multiplicar tus resultados</strong>.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cuánto cuesta destacar un código?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Tenemos <strong>3 opciones</strong> para que elijas la que mejor se adapte a ti:</p>
                    
                    <div class="pricing-comparison">
                        <div class="pricing-mini-card">
                            <h4>⭐ Normal</h4>
                            <div class="price">0,99€</div>
                            <div class="duration">7 días</div>
                            <ul>
                                <li><i class="fas fa-check"></i> Primera posición en la marca</li>
                                <li><i class="fas fa-check"></i> Badge "Destacado"</li>
                                <li><i class="fas fa-check"></i> Prioridad sobre normales</li>
                            </ul>
                        </div>
                        <div class="pricing-mini-card popular">
                            <h4>👑 Super</h4>
                            <div class="price">3,99€</div>
                            <div class="duration">14 días</div>
                            <ul>
                                <li><i class="fas fa-check"></i> Todo lo del Normal +</li>
                                <li><i class="fas fa-check"></i> Badge dorado premium</li>
                                <li><i class="fas fa-check"></i> Aparece en la página principal</li>
                                <li><i class="fas fa-check"></i> Prioridad sobre destacados normales</li>
                            </ul>
                        </div>
                        <div class="pricing-mini-card gold">
                            <h4>🏆 Guía Oficial</h4>
                            <div class="price" style="color: #FFD700;">9,99€</div>
                            <div class="duration">30 días</div>
                            <ul>
                                <li><i class="fas fa-check"></i> Todo lo del Super +</li>
                                <li><i class="fas fa-check"></i> Posición #1 en Guías Oficiales</li>
                                <li><i class="fas fa-check"></i> "Recomendado por Editores"</li>
                                <li><i class="fas fa-check"></i> Miles de visitas mensuales</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo destaco mi código?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Es muy fácil y solo tardarás <strong>1 minuto</strong>:</p>
                    <ol>
                        <li>📋 Ve a <a href="/mis-codigos"><strong>Mis Códigos</strong></a> y selecciona el código que quieres destacar</li>
                        <li>⭐ Haz clic en <strong>"Destacar"</strong></li>
                        <li>🎯 Elige el plan que prefieras (Normal, Super o Guía)</li>
                        <li>💳 Paga con <strong>tarjeta</strong> o con tu <strong>saldo</strong> disponible</li>
                        <li>🚀 ¡Tu código aparece inmediatamente en primera posición!</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Puedo pagar con mi saldo de la plataforma?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>¡Sí! Puedes usar tu <strong>saldo acumulado</strong> en CodigoAmigo para destacar tus códigos. Cuando vayas a pagar, verás la opción <strong>"Pagar con saldo"</strong> si tienes fondos suficientes.</p>
                    <p>También disponemos de <strong>auto-renovación inteligente</strong>: activa esta opción y tu código se renueva automáticamente desde tu saldo cuando expire, sin interrupciones.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Qué es la auto-renovación?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>La auto-renovación permite que tu código <strong>se mantenga destacado sin interrupciones</strong>. Cuando el periodo actual expire, se renovará automáticamente desde tu saldo disponible.</p>
                    <p>Puedes <strong>desactivarla en cualquier momento</strong> desde la gestión de tus códigos.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== CTA: DESTACAR ==================== -->
    <div class="cta-banner">
        <h3>⭐ Destaca tu código desde solo 0,99€</h3>
        <p>Los códigos destacados reciben hasta 5x más clicks. Multiplica tus ganancias hoy.</p>
        <a href="/mis-codigos" class="cta-btn">Destacar mi código ahora</a>
    </div>

    <!-- ==================== SECTION: SUSCRIPCIÓN VIP ==================== -->
    <div class="faq-section" style="position: relative;">
        <h2><i class="fas fa-crown" style="color: #ffd700; margin-right: 10px;"></i>Suscripción VIP — 9,99€/mes</h2>

        <!-- ── Free vs VIP Comparison ── -->
        <div style="background: linear-gradient(145deg, #1a1a2e, #16213e); border: 2px solid rgba(255,215,0,0.25); border-radius: 20px; padding: 30px; margin-bottom: 25px;">
            <h3 style="text-align:center; margin-bottom: 5px; font-size: 1.4rem;">
                <span style="color: #9ca3af;">Free</span> vs <span style="background: linear-gradient(135deg, #ffd700, #E30613); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">VIP</span>
            </h3>
            <p style="text-align:center; color: #888; margin-bottom: 25px; font-size: 0.9rem;">Compara y decide</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Free Column -->
                <div style="background: rgba(255,255,255,0.03); border: 1px solid #404040; border-radius: 14px; padding: 22px;">
                    <div style="text-align:center; margin-bottom: 18px;">
                        <div style="font-size:0.9rem; color:#888; font-weight:600;">FREE</div>
                        <div style="font-size:2rem; font-weight:800; color: #ccc;">0€<span style="font-size:0.8rem;color:#777;">/mes</span></div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-check-circle" style="color:#28a745;font-size:14px;flex-shrink:0;"></i><span style="color:#aaa;font-size:0.88rem;">Publicar códigos</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-check-circle" style="color:#28a745;font-size:14px;flex-shrink:0;"></i><span style="color:#aaa;font-size:0.88rem;">Estadísticas básicas</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-times-circle" style="color:#ef4444;font-size:14px;flex-shrink:0;"></i><span style="color:#666;font-size:0.88rem;">Sin badge verificado</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-times-circle" style="color:#ef4444;font-size:14px;flex-shrink:0;"></i><span style="color:#666;font-size:0.88rem;">Chat limitado</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-times-circle" style="color:#ef4444;font-size:14px;flex-shrink:0;"></i><span style="color:#666;font-size:0.88rem;">Sin mensajes masivos</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-times-circle" style="color:#ef4444;font-size:14px;flex-shrink:0;"></i><span style="color:#666;font-size:0.88rem;">Sin saldo mensual</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-times-circle" style="color:#ef4444;font-size:14px;flex-shrink:0;"></i><span style="color:#666;font-size:0.88rem;">Sin borde dorado</span></div>
                    </div>
                </div>
                <!-- VIP Column -->
                <div style="background: rgba(255,215,0,0.04); border: 2px solid rgba(255,215,0,0.35); border-radius: 14px; padding: 22px; position: relative;">
                    <div style="position:absolute;top:-10px;right:15px;background:linear-gradient(135deg,#ffd700,#E30613);color:white;padding:3px 12px;border-radius:15px;font-size:0.7rem;font-weight:700;">RECOMENDADO</div>
                    <div style="text-align:center; margin-bottom: 18px;">
                        <div style="font-size:0.9rem; background:linear-gradient(135deg,#ffd700,#E30613);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-weight:700;">👑 VIP</div>
                        <div style="font-size:2rem; font-weight:800; color: white;">9,99€<span style="font-size:0.8rem;color:#999;">/mes</span></div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-check-circle" style="color:#ffd700;font-size:14px;flex-shrink:0;"></i><span style="color:#eee;font-size:0.88rem;">Publicar códigos</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-check-circle" style="color:#ffd700;font-size:14px;flex-shrink:0;"></i><span style="color:#eee;font-size:0.88rem;">Estadísticas avanzadas</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-crown" style="color:#ffd700;font-size:14px;flex-shrink:0;"></i><span style="color:white;font-weight:600;font-size:0.88rem;">Badge VIP dorado verificado</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-comments" style="color:#ffd700;font-size:14px;flex-shrink:0;"></i><span style="color:white;font-weight:600;font-size:0.88rem;">Chat ilimitado con viewers</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-paper-plane" style="color:#ffd700;font-size:14px;flex-shrink:0;"></i><span style="color:white;font-weight:600;font-size:0.88rem;">Mensajes masivos</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-wallet" style="color:#ffd700;font-size:14px;flex-shrink:0;"></i><span style="color:#ffd700;font-weight:700;font-size:0.88rem;">+10€ de saldo GRATIS/mes</span></div>
                        <div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-star" style="color:#ffd700;font-size:14px;flex-shrink:0;"></i><span style="color:white;font-weight:600;font-size:0.88rem;">Borde dorado en tus códigos</span></div>
                    </div>
                </div>
            </div>

            <div style="text-align:center; margin-top: 25px;">
                <a href="/public/mis_viewers.php" style="display:inline-block;padding:14px 40px;background:linear-gradient(135deg,#ffd700,#E30613);color:white;text-decoration:none;border-radius:50px;font-weight:700;font-size:1.1rem;box-shadow:0 8px 30px rgba(255,215,0,0.3);transition:all 0.3s ease;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 40px rgba(255,215,0,0.4)'" onmouseout="this.style.transform='';this.style.boxShadow='0 8px 30px rgba(255,215,0,0.3)'"><i class="fas fa-crown"></i> Hacerme VIP ahora</a>
                <p style="color:#888;font-size:0.8rem;margin-top:12px;">Facturación mensual. Cancela cuando quieras. Sin compromiso.</p>
            </div>
        </div>

        <!-- ── VIP FAQs ── -->
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Qué incluye la suscripción VIP?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>La suscripción VIP por <strong>9,99€/mes</strong> incluye:</p>
                    <ul>
                        <li>👑 <strong>Badge VIP dorado verificado</strong> — Un distintivo premium que aparece junto a tu nombre y en todos tus códigos, generando máxima confianza</li>
                        <li>💰 <strong>10€ de saldo GRATIS cada mes</strong> — Recibes 10€ de saldo mensual para destacar tus códigos. Esto significa que <strong>el VIP prácticamente se paga solo</strong>, ya que con ese saldo puedes destacar hasta 10 códigos al mes</li>
                        <li>💬 <strong>Chat ilimitado con Viewers</strong> — Contacta directamente con usuarios que han visto tus códigos para ayudarles a completar el proceso</li>
                        <li>📨 <strong>Mensajes masivos</strong> — Envía mensajes a todos tus potenciales clientes de una vez para multiplicar tus conversiones</li>
                        <li>✨ <strong>Borde dorado en tus códigos</strong> — Tus códigos destacan visualmente con un borde dorado exclusivo</li>
                        <li>📊 <strong>Estadísticas avanzadas</strong> — Accede a métricas detalladas de rendimiento</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo funcionan los 10€ de saldo gratis?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Cada mes recibes <strong>10€ de saldo automáticamente</strong> en tu cuenta:</p>
                    <ul>
                        <li>💸 Se acreditan el <strong>primer día</strong> de tu suscripción y cada vez que se renueva</li>
                        <li>⭐ Puedes usarlos para <strong>destacar tus códigos</strong> (desde 0,99€ por destacado, eso son hasta <strong>10 destacados gratis al mes</strong>)</li>
                        <li>🎯 También sirven para <strong>promociones</strong> y otros servicios de la plataforma</li>
                        <li>🔑 <strong>El VIP cuesta 9,99€ y te dan 10€ de saldo</strong>, así que básicamente estás ganando dinero al suscribirte</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Qué son los Viewers y cómo me ayudan a ganar más?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Los <strong>Viewers</strong> son usuarios registrados que han visto uno de tus códigos de referido. Con el VIP puedes:</p>
                    <ul>
                        <li>👁️ <strong>Ver quién ha visto tus códigos</strong> — Sabes exactamente qué usuarios están interesados</li>
                        <li>💬 <strong>Contactarles por chat</strong> — Escríbeles directamente para ayudarles a usar tu código</li>
                        <li>📨 <strong>Enviar mensajes masivos</strong> — Contacta a todos a la vez con un solo clic</li>
                        <li>💰 <strong>Convertir visitas en ganancias</strong> — Es una situación win-win: ellos reciben ayuda personalizada y tú aumentas tus conversiones</li>
                    </ul>
                    <p><strong>Sin VIP</strong>, solo ves que alguien miró tu código pero no puedes contactarle. <strong>Con VIP</strong>, conviertes esas visitas en dinero real.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Por qué el badge VIP genera más confianza?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>El <strong>badge VIP dorado</strong> <span style="display:inline-flex;align-items:center;gap:3px;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#1e3a5f;font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:10px;"><i class="fas fa-crown"></i> VIP</span> aparece en:</p>
                    <ul>
                        <li>Tu <strong>nombre de usuario</strong> en toda la plataforma</li>
                        <li>Todos tus <strong>códigos publicados</strong> (con borde dorado)</li>
                        <li>Tu <strong>avatar</strong> en la lista de códigos (brillo dorado)</li>
                        <li>Las <strong>tarjetas de código</strong> cuando un usuario los ve</li>
                    </ul>
                    <p>Los usuarios ven el badge y saben que eres un miembro <strong>comprometido y verificado</strong>, lo que hace que confíen más en tus códigos y los usen con más frecuencia.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Puedo cancelar la suscripción VIP cuando quiera?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>¡Sí, absolutamente! <strong>Sin compromiso de permanencia</strong>:</p>
                    <ul>
                        <li>✅ Cancela desde tu <strong>panel de usuario</strong> en cualquier momento</li>
                        <li>✅ Mantienes los beneficios VIP hasta el <strong>final del período</strong> que ya has pagado</li>
                        <li>✅ El saldo que ya tienes en tu cuenta <strong>no se pierde</strong> al cancelar</li>
                        <li>✅ Puedes <strong>volver a suscribirte</strong> cuando quieras</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== CTA: VIP ==================== -->
    <div class="cta-banner" style="background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%); border: 2px solid rgba(255,215,0,0.3);">
        <div style="font-size: 3rem; margin-bottom: 15px; position: relative; z-index: 1;">👑</div>
        <h3 style="font-size: 1.5rem;">Hazte VIP por 9,99€/mes</h3>
        <p>Recibes <strong>10€ de saldo gratis</strong> + badge dorado + chat con viewers + mensajes masivos</p>
        <a href="/public/mis_viewers.php" class="cta-btn" style="background: linear-gradient(135deg, #ffd700, #E30613); color: white;"><i class="fas fa-crown"></i> Suscribirme ahora</a>
        <p style="color: rgba(255,255,255,0.5); font-size: 0.75rem; margin-top: 10px; margin-bottom: 0; position: relative; z-index: 1;">Cancela cuando quieras · Saldo incluido · Sin permanencia</p>
    </div>

    <!-- ==================== SECTION 3: VOTOS Y CONFIANZA ==================== -->
    <div class="faq-section">
        <h2><i class="fas fa-shield-alt" style="color: #3b82f6; margin-right: 10px;"></i>Votos y Niveles de Confianza</h2>
        
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo puedo votar un código?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Cuando revelas un código en la página de una marca, verás los botones <strong>👍 Sí</strong> y <strong>👎 No</strong> debajo del código con la pregunta "¿Te ha funcionado?".</p>
                    <p>Haz clic en el botón correspondiente para registrar tu voto. Necesitas <a href="/login">iniciar sesión</a> para poder votar. Solo puedes votar una vez por código, pero puedes cambiar tu voto en cualquier momento.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Qué son los niveles de confianza?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Cada código tiene un <strong>nivel de confianza</strong> basado en la calidad del código y la actividad del usuario que lo publica:</p>
                    
                    <div class="trust-level-visual">
                        <div class="trust-row">
                            <span class="trust-stars">⭐⭐⭐⭐⭐</span>
                            <span class="trust-label-badge" style="background: rgba(245,158,11,0.2); color: #fbbf24;">Premium</span>
                            <span class="trust-description">Código destacado / promocionado</span>
                        </div>
                        <div class="trust-row">
                            <span class="trust-stars">⭐⭐⭐⭐</span>
                            <span class="trust-label-badge" style="background: rgba(16,185,129,0.2); color: #34d399;">Recomendado</span>
                            <span class="trust-description">Usuario muy activo y bien valorado</span>
                        </div>
                        <div class="trust-row">
                            <span class="trust-stars">⭐⭐⭐</span>
                            <span class="trust-label-badge" style="background: rgba(59,130,246,0.2); color: #60a5fa;">De confianza</span>
                            <span class="trust-description">Usuario con buen historial</span>
                        </div>
                        <div class="trust-row">
                            <span class="trust-stars">⭐⭐</span>
                            <span class="trust-label-badge" style="background: rgba(107,114,128,0.15); color: #9ca3af;">Verificado</span>
                            <span class="trust-description">Perfil básico completo</span>
                        </div>
                        <div class="trust-row">
                            <span class="trust-stars">⭐</span>
                            <span class="trust-label-badge" style="background: rgba(156,163,175,0.1); color: #6b7280;">Nuevo</span>
                            <span class="trust-description">Usuario recién registrado</span>
                        </div>
                    </div>
                    
                    <p style="margin-top: 15px;">Los usuarios prefieren códigos con niveles de confianza más altos. <strong>Destacar tu código te da el nivel Premium (5 estrellas)</strong> automáticamente.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo puedo subir de nivel de confianza?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Puedes mejorar tu nivel de confianza con estas acciones:</p>
                    <ul>
                        <li>📷 <strong>Sube una foto de perfil</strong> real</li>
                        <li>📧 <strong>Verifica tu email</strong></li>
                        <li>⏳ <strong>Antigüedad de la cuenta</strong> (hasta 5 años)</li>
                        <li>🏷️ <strong>Publica códigos</strong> en varias marcas</li>
                        <li>👍 <strong>Recibe votos positivos</strong> en tus códigos</li>
                        <li>✏️ <strong>Añade descripciones detalladas</strong> a tus códigos</li>
                    </ul>
                    
                    <div class="vip-highlight-box">
                        <h4><i class="fas fa-bolt"></i> ¿Quieres el máximo nivel de inmediato?</h4>
                        <p style="color: #ddd;">Al <strong>destacar tu código</strong> obtienes automáticamente el nivel <strong>Premium (5 estrellas)</strong>, lo que genera más confianza y más clicks. Desde solo <strong>0,99€</strong>.</p>
                        <a href="/mis-codigos" style="display: inline-block; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #1e3a5f; padding: 10px 25px; border-radius: 25px; text-decoration: none; font-weight: 700; margin-top: 10px;">⭐ Destacar mi código</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Qué pasa cuando alguien vota mi código?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Los votos afectan a tu <strong>reputación</strong> y al nivel de confianza de tus códigos:</p>
                    <ul>
                        <li>👍 <strong>Votos positivos</strong> mejoran tu Trust Score y dan más visibilidad a tus códigos</li>
                        <li>👎 <strong>Votos negativos</strong> pueden reducir la visibilidad de un código</li>
                        <li>📊 Cuantos más votos positivos recibas, más rápido subirás de nivel</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== SECTION 4: PUBLICAR ==================== -->
    <div class="faq-section">
        <h2><i class="fas fa-plus-circle" style="color: #10b981; margin-right: 10px;"></i>Publicar Códigos</h2>
        
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo publico un código de descuento?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Es muy sencillo:</p>
                    <ol>
                        <li><a href="/login">Inicia sesión</a> en tu cuenta (o créala gratis)</li>
                        <li>Haz clic en <a href="/nuevo_codigo"><strong>"Publicar código"</strong></a></li>
                        <li>Completa el formulario con la información del código</li>
                        <li>¡Tu código será visible inmediatamente!</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Puedo publicar códigos de cualquier marca?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>¡Sí! Puedes publicar códigos de cualquier marca que tenga un programa de referidos o códigos de descuento. Si la marca no está en nuestro listado, puedes sugerirla y la añadiremos.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo hago que más gente use mi código?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Hay varias formas de <strong>maximizar la visibilidad</strong> de tu código:</p>
                    <ul>
                        <li>⭐ <strong>Destácalo</strong> desde 0,99€ para aparecer en primera posición</li>
                        <li>✏️ Añade una <strong>descripción detallada</strong> explicando qué ofrece</li>
                        <li>📷 Completa tu perfil con <strong>foto</strong> para generar confianza</li>
                        <li>👍 Pide a amigos que <strong>voten positivo</strong> tu código</li>
                        <li>🔄 Mantén tu código <strong>actualizado</strong></li>
                    </ul>
                    <p style="margin-top: 15px; background: rgba(227,6,19,0.1); padding: 15px; border-radius: 10px; border-left: 4px solid #E30613;">
                        <strong>💡 Consejo Pro:</strong> Los códigos destacados con el plan <strong>Super (3,99€)</strong> aparecen también en la página principal, lo que multiplica enormemente tu alcance.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== CTA: MAXIMIZAR GANANCIAS ==================== -->
    <div class="cta-banner" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 1px solid rgba(245,158,11,0.3);">
        <h3>👑 Maximiza tus ganancias como profesional</h3>
        <p>Los usuarios con códigos destacados ganan hasta <strong>5 veces más</strong> que los que no destacan</p>
        <a href="/mis-codigos" class="cta-btn cta-btn-dark">⭐ Ver mis códigos y destacar</a>
    </div>

    <!-- ==================== SECTION 5: CUENTA ==================== -->
    <div class="faq-section">
        <h2><i class="fas fa-user-circle" style="color: #8b5cf6; margin-right: 10px;"></i>Cuenta y Perfil</h2>
        
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo creo una cuenta?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Puedes crear una cuenta de forma <strong>gratuita</strong> haciendo clic en <a href="/login">"Registrarse"</a> en la parte superior de la página. Puedes registrarte con tu <strong>email</strong> o usando tu cuenta de <strong>Google</strong>.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo puedo ver mis estadísticas?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Una vez que inicies sesión, puedes acceder a tus estadísticas desde el menú de usuario. Allí verás información sobre tus <strong>códigos publicados</strong>, <strong>clicks recibidos</strong>, <strong>votos</strong> y <strong>beneficios obtenidos</strong>.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Qué métodos de pago aceptáis?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Para destacar códigos aceptamos:</p>
                    <ul>
                        <li>💳 <strong>Tarjeta de crédito/débito</strong> — Visa, Mastercard, American Express (pago seguro con Stripe)</li>
                        <li>💰 <strong>Saldo de la plataforma</strong> — Usa tu saldo acumulado para destacar sin coste adicional</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== FINAL CTA ==================== -->
    <div class="cta-banner" style="background: linear-gradient(135deg, #E30613 0%, #c70510 100%);">
        <h3>🚀 ¿Listo para empezar?</h3>
        <p>Únete a miles de usuarios que ya están ganando dinero compartiendo sus códigos</p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; position: relative; z-index: 1;">
            <a href="/nuevo_codigo" class="cta-btn">Publicar mi código</a>
            <a href="/login" class="cta-btn" style="background: transparent; border: 2px solid white; color: white;">Crear cuenta gratis</a>
        </div>
    </div>

    <div class="contact-section">
        <h3>¿No encuentras la respuesta que buscas?</h3>
        <p>Estamos aquí para ayudarte. Contáctanos y te responderemos lo antes posible.</p>
        <a href="/contacto" class="contact-button">Contactar con soporte</a>
    </div>
</div>

<script>
function toggleFAQ(button) {
    const answer = button.nextElementSibling;
    const isActive = button.classList.contains('active');
    
    // Cerrar todos los demás FAQs
    document.querySelectorAll('.faq-question').forEach(q => {
        if (q !== button) {
            q.classList.remove('active');
            q.nextElementSibling.classList.remove('active');
        }
    });
    
    // Toggle del FAQ actual
    button.classList.toggle('active');
    answer.classList.toggle('active');
}
</script>

<?php
// Incluir footer
include_once __DIR__ . '/../myphp/_footer.php';
get_footer_modern();
?>
