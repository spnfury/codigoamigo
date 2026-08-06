<?php
// amazon_service_detail.php
// Detalle de Servicio Amazon - SEO Optimized

require_once __DIR__ . '/inc/includes.php';
require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_modern.php';
require_once __DIR__ . '/myphp/funciones_amazon_services.php';

$slug = $_GET['slug'] ?? '';
log_info("Amazon Detail Executed for slug: " . $slug);
$service = getAmazonServiceBySlug($slug);

if (!$service) {
    log_warning("Service not found for slug: " . $slug);
    header("Location: /amazon");
    exit;
}

// Registrar vista
logAmazonServiceClick($slug, $_SERVER['HTTP_REFERER'] ?? '', 'service_detail_view');

// Configuración SEO
$GLOBALS['website'] = 'https://www.codigoamigo.com/';
$GLOBALS['actual_url'] = 'https://www.codigoamigo.com/amazon/' . $slug;
$GLOBALS['header_modern_used'] = true;

require_once __DIR__ . '/myphp/_header_modern.php';
require_once __DIR__ . '/myphp/_footer.php';

get_header_modern(
    $service['seo_title'] ?? ($service['title'] . " - CodigoAmigo"),
    $service['meta_description'] ?? $service['description'],
    $service['title'],
    $service['h1'] ?? $service['title'],
    $service['image_url']
);

// Estilos específicos
?>
<style>
    :root {
        --amazon-orange: #FF9900;
        --amazon-dark: #131921;
        --amazon-blue: #232f3e;
        --text-main: #1f2937;
        --text-muted: #4b5563;
        --bg-light: #f3f4f6;
        --accent-glow: rgba(255, 153, 0, 0.15);
    }

    .detail-hero {
        background: linear-gradient(135deg, var(--amazon-dark) 0%, var(--amazon-blue) 100%);
        color: white;
        padding: 120px 0 80px;
        position: relative;
        overflow: hidden;
    }

    .detail-hero::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: radial-gradient(circle at 70% 30%, var(--accent-glow) 0%, transparent 50%);
        pointer-events: none;
    }

    .service-badge {
        display: inline-block;
        background: var(--amazon-orange);
        color: var(--amazon-dark);
        padding: 6px 16px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 24px;
    }

    .detail-hero h1 {
        font-weight: 800;
        font-size: clamp(2.2rem, 5vw, 3.5rem);
        line-height: 1.1;
        margin-bottom: 24px;
        letter-spacing: -2px;
    }

    .detail-hero .lead {
        font-size: 1.25rem;
        opacity: 0.9;
        max-width: 650px;
        line-height: 1.6;
    }

    .content-section {
        padding: 80px 0;
        background: white;
    }

    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 50px;
    }

    .feature-card {
        background: #fff;
        padding: 40px;
        border-radius: 24px;
        border: 1px solid #e5e7eb;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05);
        border-color: var(--amazon-orange);
    }

    .feature-icon {
        width: 54px;
        height: 54px;
        background: var(--amazon-orange);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        font-size: 1.5rem;
        margin-bottom: 24px;
    }

    .cta-card {
        background: var(--amazon-dark);
        border-radius: 30px;
        padding: 60px;
        color: white;
        text-align: center;
        margin-top: 60px;
        position: relative;
        overflow: hidden;
    }

    .cta-card::after {
        content: "";
        position: absolute;
        bottom: -50px; right: -50px;
        width: 200px; height: 200px;
        background: var(--amazon-orange);
        opacity: 0.1;
        filter: blur(50px);
        border-radius: 50%;
    }

    .btn-massive {
        padding: 24px 48px;
        font-size: 1.25rem;
        font-weight: 800;
        border-radius: 20px;
        background: var(--amazon-orange);
        color: var(--amazon-dark);
        border: none;
        transition: all 0.3s;
        box-shadow: 0 10px 30px rgba(255, 153, 0, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }

    .btn-massive:hover {
        transform: scale(1.05);
        background: #ffaa33;
        color: var(--amazon-dark);
        box-shadow: 0 15px 40px rgba(255, 153, 0, 0.4);
    }

    .faq-container {
        max-width: 800px;
        margin: 60px auto 0;
    }

    .faq-q {
        font-weight: 700;
        font-size: 1.2rem;
        margin-bottom: 12px;
        color: var(--amazon-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .faq-q::before {
        content: "Q.";
        color: var(--amazon-orange);
        font-size: 1.4rem;
    }

    .faq-a {
        color: var(--text-muted);
        line-height: 1.7;
        margin-bottom: 40px;
        padding-left: 32px;
    }

    .section-title {
        font-weight: 800;
        font-size: 2.5rem;
        letter-spacing: -1px;
        margin-bottom: 40px;
    }

    @media (max-width: 768px) {
        .detail-hero { padding: 100px 0 60px; }
        .cta-card { padding: 40px 20px; }
        .btn-massive { width: 100%; justify-content: center; }
    }
</style>

<div class="detail-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="service-badge">Recomendado</span>
                <h1><?= htmlspecialchars($service['h1']) ?></h1>
                <p class="lead"><?= htmlspecialchars($service['intro_text']) ?></p>
                <div class="mt-5 d-flex gap-3 flex-wrap">
                    <a href="<?= htmlspecialchars($service['destination_url']) ?>" 
                       class="btn-massive track-amazon" 
                       data-slug="<?= htmlspecialchars($slug) ?>"
                       data-origin="detail_hero"
                       rel="nofollow sponsored noopener" 
                       target="_blank">
                        <?= htmlspecialchars($service['cta_text']) ?>
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <img src="<?= htmlspecialchars($service['image_url']) ?>" 
                     alt="<?= htmlspecialchars($service['title']) ?>" 
                     style="max-width: 250px; filter: drop-shadow(0 0 30px rgba(255,255,255,0.2));">
            </div>
        </div>
    </div>
</div>

<div class="content-section">
    <div class="container">
        <h2 class="section-title text-center">Características Principales</h2>
        <div class="feature-grid">
            <?php foreach ($service['features'] as $index => $feat): ?>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas <?= ['fa-bolt', 'fa-tv', 'fa-music', 'fa-book', 'fa-tag', 'fa-users', 'fa-globe', 'fa-mobile-alt'][$index % 8] ?>"></i>
                    </div>
                    <h3><?= htmlspecialchars($feat['title']) ?></h3>
                    <p><?= htmlspecialchars($feat['text']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cta-card">
            <h2>Empieza tu prueba gratuita hoy</h2>
            <p class="mb-5 opacity-75">Únete a millones de usuarios que ya disfrutan de <?= htmlspecialchars($service['title']) ?>. Cancelación flexible en cualquier momento.</p>
            <a href="<?= htmlspecialchars($service['destination_url']) ?>" 
               class="btn-massive track-amazon"
               data-slug="<?= htmlspecialchars($slug) ?>"
               data-origin="detail_bottom_cta"
               rel="nofollow sponsored noopener" 
               target="_blank">
                Comenzar gratis
            </a>
            <div class="mt-4 text-sm opacity-50">
                Cancelable en cualquier momento desde tu cuenta de Amazon.
            </div>
        </div>
    </div>
</div>

<div class="content-section" style="background: var(--bg-light);">
    <div class="container">
        <h2 class="section-title text-center">Preguntas Frecuentes</h2>
        <div class="faq-container">
            <?php foreach ($service['faq'] as $faq): ?>
                <div class="faq-item">
                    <div class="faq-q"><?= htmlspecialchars($faq['q']) ?></div>
                    <div class="faq-a"><?= htmlspecialchars($faq['a']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="text-center text-muted small" style="max-width: 800px; margin: 0 auto; line-height: 1.6; font-style: italic;">
        <p>En calidad de Afiliado de Amazon, obtenemos ingresos por las compras adscritas que cumplen los requisitos aplicables. Esto nos permite seguir ofreciendo contenido de calidad sin coste adicional para ti.</p>
    </div>
</div>

<!-- Structured Data (JSON-LD) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "<?= addslashes($service['title']) ?>",
  "description": "<?= addslashes($service['meta_description']) ?>",
  "image": "<?= $service['image_url'] ?>",
  "brand": {
    "@type": "Brand",
    "name": "Amazon"
  },
  "offers": {
    "@type": "Offer",
    "url": "<?= $service['destination_url'] ?>",
    "priceCurrency": "EUR",
    "price": "0.00",
    "availability": "https://schema.org/InStock"
  }
}
</script>

<script src="/js/amazon_tracking.js?v=<?= time() ?>"></script>

<?php
get_footer_modern();
?>
