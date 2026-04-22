<?php
// amazon_services.php
// Landing Page de Servicios Amazon

require_once __DIR__ . '/inc/includes.php';

// Force No-Cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/myphp/funciones.php';
require_once __DIR__ . '/myphp/funciones_modern.php';
require_once __DIR__ . '/myphp/funciones_amazon_services.php';
require_once __DIR__ . '/myphp/_header_modern.php';
require_once __DIR__ . '/myphp/_footer.php';

// Registrar vista
logAmazonSectionView();

// Obtener servicios activos
$collection = getCollectionAffiliateLinks();
$services = $collection->find(['active' => true], ['sort' => ['order' => 1]])->toArray();

// Configuración SEO
$GLOBALS['website'] = 'https://www.codigoamigo.com/';
$GLOBALS['actual_url'] = 'https://www.codigoamigo.com/amazon';
$GLOBALS['header_modern_used'] = true;

get_header_modern(
    "Amazon Prime, Audible, Music y más - Pruébalos Gratis | CodigoAmigo",
    "Descubre los mejores servicios digitales de Amazon con periodos de prueba gratuitos. Prime Video, Music Unlimited, Kindle Unlimited y mucho más.",
    "Servicios Amazon Recomendados",
    "Prueba gratis los mejores servicios de entretenimiento y productividad",
    "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png"
);

// Estilos específicos
echo '
<style>
    :root {
        --amazon-orange: #FF9900;
        --amazon-dark: #131921;
        --amazon-blue: #232f3e;
        --text-main: #111;
        --text-muted: #4a5568;
        --bg-light: #f8f9fa;
    }

    .amazon-hero {
        background: linear-gradient(135deg, var(--amazon-dark) 0%, var(--amazon-blue) 100%);
        color: white;
        padding: 100px 0 80px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .amazon-hero::after {
        content: "";
        position: absolute;
        top: 0; right: 0; bottom: 0; left: 0;
        background: url("https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg") no-repeat center center;
        opacity: 0.03;
        background-size: 40%;
        transform: rotate(-5deg) scale(1.2);
        pointer-events: none;
    }

    .amazon-hero .container { position: relative; z-index: 2; }
    .amazon-hero h1 { 
        font-weight: 800; 
        margin-bottom: 24px; 
        font-size: clamp(2.5rem, 5vw, 3.8rem); 
        color: #fff;
        letter-spacing: -1px;
    }
    .amazon-hero p { 
        font-size: clamp(1.1rem, 2vw, 1.35rem); 
        opacity: 0.9; 
        max-width: 700px; 
        margin: 0 auto 40px; 
        color: #e2e8f0;
        line-height: 1.6;
    }
    
    .services-wrapper {
        background-color: var(--bg-light);
        padding: 80px 0;
    }
    .service-card {
        border: none;
        border-radius: 24px;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        height: 100%;
        min-height: 700px; /* Increased to ensure better alignment across rows */
        background: white;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.08);
        display: flex;
        flex-direction: column;
    }

    .service-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 30px 60px -12px rgba(0,0,0,0.15);
        border-color: var(--amazon-orange);
    }

    .service-logo-container {
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fafbfc; /* Subtly different from card background */
        padding: 40px;
        border-bottom: 1px solid #f1f3f5;
        overflow: hidden;
        flex-shrink: 0;
        position: relative;
    }
    
    .service-logo-container::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: radial-gradient(circle at center, rgba(255,153,0,0.03) 0%, transparent 70%);
        pointer-events: none;
    }

    .service-logo {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .service-card:hover .service-logo {
        transform: scale(1.08);
    }
    .service-body { 
        padding: 35px; 
        display: flex; 
        flex-direction: column; 
        flex: 1; 
    }

    .service-title { 
        font-weight: 800; 
        font-size: 1.5rem; 
        margin-bottom: 25px; 
        color: var(--amazon-dark); 
        line-height: 1.3;
        display: block; /* Changed from flex for better min-height behavior */
        min-height: 2.6em; /* Ensure alignment of bullets regardless of title length */
    }

    .service-bullets {
        list-style: none;
        padding: 0;
        margin-bottom: 35px;
        font-size: 1.05rem;
        color: var(--text-muted);
        flex-grow: 1;
    }

    .service-bullets li {
        margin-bottom: 16px;
        padding-left: 32px;
        position: relative;
        line-height: 1.5;
    }

    .service-bullets li::before {
        content: "\f058";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        color: var(--amazon-orange);
        position: absolute;
        left: 0;
        font-size: 1.1rem;
        top: 2px;
    }
    .btn-amazon {
        background-color: var(--amazon-orange);
        border: none;
        color: var(--amazon-dark) !important;
        font-weight: 800;
        width: 100%;
        padding: 18px;
        border-radius: 16px;
        transition: all 0.3s;
        text-transform: uppercase;
        font-size: 1rem;
        letter-spacing: 1px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 20px rgba(255, 153, 0, 0.25);
        margin-top: auto;
    }

    .btn-amazon:hover {
        background-color: var(--amazon-blue);
        color: #fff !important;
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(35, 47, 62, 0.3);
    }
    
    .faq-section { background-color: white; padding: 100px 0; border-top: 1px solid #f1f3f5; }
    .faq-section h2 { color: var(--amazon-dark); font-weight: 800; font-size: clamp(2rem, 4vw, 3rem); margin-bottom: 60px; }

    .faq-item { background: var(--bg-light); border-radius: 16px; margin-bottom: 20px; border: 1px solid transparent; transition: all 0.3s; }
    .faq-item:hover { border-color: rgba(255, 153, 0, 0.3); background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    
    .faq-question { padding: 25px 35px; cursor: pointer; font-weight: 700; font-size: 1.2rem; display: flex; justify-content: space-between; align-items: center; color: var(--amazon-dark); transition: all 0.3s; }
    .faq-answer { padding: 0 35px 30px; display: none; color: var(--text-muted); line-height: 1.8; font-size: 1.1rem; }
    
    .faq-question.active { color: var(--amazon-orange); }
    .faq-question.active + .faq-answer { display: block; }
    .faq-toggle { transition: transform 0.3s; color: #cbd5e0; }
    .faq-question.active .faq-toggle { transform: rotate(180deg); color: var(--amazon-orange); }

    .disclosure-text { font-size: 0.95rem; color: #94a3b8; text-align: center; padding: 60px 0; max-width: 800px; margin: 0 auto; line-height: 1.7; font-style: italic; }

    .hover-orange:hover {
        color: var(--amazon-orange) !important;
        text-decoration: underline !important;
    }

    @media (max-width: 768px) {
        .amazon-hero { padding: 80px 0 60px; }
        .service-body { padding: 25px; }
        .service-logo-container { height: 140px; padding: 20px; }
    }
</style>
';

// Hero Section
echo '
<div class="amazon-hero">
    <div class="container">
        <h1>Lo mejor de Amazon Digital</h1>
        <p>Prueba gratis los servicios premium de Amazon. Sin compromiso, cancela cuando quieras.</p>
        <a href="#servicios" class="btn btn-lg btn-light fw-bold px-5" style="border-radius: 30px;">Ver Ofertas</a>
    </div>
</div>
';

// Grid Servicios
echo '<div class="services-wrapper" id="servicios">';
echo '<div class="container">';
if (empty($services)) {
    echo '<div class="text-center py-5 text-muted"><h3>Próximamente agregaremos los mejores servicios.</h3></div>';
} else {
    echo '<div class="row g-4 justify-content-center">'; // Centered grid items
    foreach ($services as $srv) {
        // Parsear descripción (separar por · o nueva línea)
        $bullets_raw = $srv['description'];
        $bullets = preg_split('/[·\n]/', $bullets_raw);
        $bullets = array_filter(array_map('trim', $bullets)); // Limpiar vacíos
        
        $img_src = !empty($srv['image_url']) ? $srv['image_url'] : 'https://placehold.co/400x200?text=' . urlencode($srv['title']);
        
        $link = $srv['destination_url']; 
        $slug = $srv['slug'];

        echo '
        <div class="col-md-6 col-lg-4 d-flex mb-4">
            <div class="service-card w-100">
                <div class="service-logo-container">
                    <img src="' . htmlspecialchars($img_src) . '" alt="' . htmlspecialchars($srv['title']) . '" class="service-logo">
                </div>
                <div class="service-body">
                    <h3 class="service-title">' . htmlspecialchars($srv['title']) . '</h3>
                    <ul class="service-bullets">';
                        foreach ($bullets as $b) {
                            echo '<li>' . htmlspecialchars($b) . '</li>';
                        }
        echo '      </ul>
                    <a href="/amazon/' . htmlspecialchars($slug) . '" class="text-decoration-none text-muted mb-4 d-inline-block hover-orange">
                        <i class="fas fa-info-circle me-1"></i> Ver más detalles y opiniones
                    </a>
                    <a href="' . htmlspecialchars($link) . '" 
                       class="btn btn-amazon track-amazon" 
                       data-slug="' . htmlspecialchars($slug) . '" 
                       data-origin="landing_card"
                       rel="nofollow sponsored noopener" 
                       target="_blank">
                        ' . htmlspecialchars($srv['cta_text']) . ' <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>';
    }
    echo '</div>';
}
echo '</div>'; // Container
echo '</div>'; // services-wrapper

// FAQ Section
echo '
<div class="faq-section">
    <div class="container">
        <h2 class="text-center mb-5">Preguntas Frecuentes</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        ¿Son realmente gratis los periodos de prueba?
                        <i class="fas fa-chevron-down faq-toggle"></i>
                    </div>
                    <div class="faq-answer">
                        Sí, Amazon ofrece periodos de prueba (usualmente 30 días) totalmente gratuitos. Puedes disfrutar de todas las ventajas del servicio sin coste alguno durante ese tiempo.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        ¿Puedo cancelar antes de que me cobren?
                        <i class="fas fa-chevron-down faq-toggle"></i>
                    </div>
                    <div class="faq-answer">
                        Absolutamente. Puedes cancelar tu suscripción en cualquier momento desde tu cuenta de Amazon, incluso un minuto después de registrarte, y seguirás disfrutando de los beneficios hasta que termine el periodo de prueba.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        ¿Necesito una cuenta de Amazon nueva?
                        <i class="fas fa-chevron-down faq-toggle"></i>
                    </div>
                    <div class="faq-answer">
                        No, puedes usar tu cuenta actual. Sin embargo, las pruebas gratuitas suelen estar disponibles solo para usuarios que no hayan probado ese servicio específico recientemente.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

// Legal Disclosure
echo '
<div class="container">
    <div class="disclosure-text">
        <p>En calidad de Afiliado de Amazon, obtenemos ingresos por las compras adscritas que cumplen los requisitos aplicables. Esto no supone ningún coste adicional para ti.</p>
    </div>
</div>

<script src="/js/amazon_tracking.js?v=' . (time() + 1) . '"></script>
<script>
function toggleFaq(element) {
    element.classList.toggle("active");
}
</script>
';

get_footer_modern();
?>
