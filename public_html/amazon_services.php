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
    .amazon-hero {
        background: linear-gradient(135deg, #131921 0%, #232f3e 100%);
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
        opacity: 0.05;
        background-size: 50%;
        transform: rotate(-10deg) scale(1.5);
        pointer-events: none;
    }
    .amazon-hero .container { position: relative; z-index: 2; }
    .amazon-hero h1 { font-weight: 800; margin-bottom: 20px; font-size: 3.5rem; color: #fff; }
    .amazon-hero p { font-size: 1.4rem; opacity: 0.95; max-width: 800px; margin: 0 auto 40px; color: #eee; }
    
    .service-card {
        border: none;
        border-radius: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        background: white;
        overflow: hidden;
        border: 1px solid #eef0f2;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
    }
    .service-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        border-color: #FF9900;
    }
    .service-logo-container {
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        padding: 0;
        border-bottom: 1px solid #f8f9fa;
        overflow: hidden;
        flex-shrink: 0; /* Prevent logo shrinking */
    }
    .service-logo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .service-card:hover .service-logo {
        transform: scale(1.05);
    }
    .service-body { 
        padding: 30px; 
        display: flex; 
        flex-direction: column; 
        flex: 1; 
        /* Removed fixed min-height to allow natural flow */
    }
    .service-title { 
        font-weight: 800; 
        font-size: 1.6rem; 
        margin-bottom: 20px; 
        color: #111; 
        min-height: 2.4em; /* Force at least 2 lines of height for alignment */
        display: flex;
        align-items: center;
    }
    .service-bullets {
        list-style: none;
        padding: 0;
        margin-bottom: 30px;
        font-size: 1.1rem;
        color: #444;
        flex-grow: 1; /* Pushes button to bottom */
    }
    .service-bullets li {
        margin-bottom: 12px;
        padding-left: 28px;
        position: relative;
        line-height: 1.4;
    }
    .service-bullets li::before {
        content: "\f00c";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        color: #FF9900;
        position: absolute;
        left: 0;
        font-size: 0.9rem;
        top: 3px;
    }
    .btn-amazon {
        background-color: #FF9900;
        border: none;
        color: #111 !important;
        font-weight: 800;
        width: 100%;
        padding: 16px;
        border-radius: 12px;
        transition: all 0.2s;
        text-transform: uppercase;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(255, 153, 0, 0.2);
        margin-top: auto; /* Ensure it sticks to bottom if parent flex changes */
    }
    .btn-amazon:hover {
        background-color: #232f3e;
        color: #fff !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(35, 47, 62, 0.3);
    }
    
    .faq-section { background-color: #f4f6f8; padding: 120px 0; }
    .faq-section h2 { color: #1a1a1a; font-weight: 800; font-size: 3rem; margin-bottom: 60px; }

    .faq-item { background: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #eef0f2; overflow: hidden; }
    .faq-question { padding: 25px 30px; cursor: pointer; font-weight: 700; font-size: 1.15rem; display: flex; justify-content: space-between; align-items: center; color: #131921; transition: background 0.2s; }
    .faq-question:hover { background: #fcfcfc; }
    .faq-answer { padding: 0 30px 25px; display: none; color: #4a5568; line-height: 1.8; font-size: 1.05rem; }
    .faq-question.active { color: #FF9900; }
    .faq-question.active + .faq-answer { display: block; }
    .faq-toggle { transition: transform 0.3s; color: #a0aec0; }
    .faq-question.active .faq-toggle { transform: rotate(180deg); color: #FF9900; }

    .disclosure-text { font-size: 0.9rem; color: #718096; text-align: center; padding: 40px 0; max-width: 800px; margin: 0 auto; line-height: 1.6; }
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
echo '<div class="container py-5 mb-5" id="servicios">';
if (empty($services)) {
    echo '<div class="text-center py-5 text-muted"><h3>Próximamente agregaremos los mejores servicios.</h3></div>';
} else {
    echo '<div class="row g-5">';
    foreach ($services as $srv) {
        // Parsear descripción (separar por · o nueva línea)
        $bullets_raw = $srv['description'];
        $bullets = preg_split('/[·\n]/', $bullets_raw);
        $bullets = array_filter(array_map('trim', $bullets)); // Limpiar vacíos
        
        $img_src = !empty($srv['image_url']) ? $srv['image_url'] : 'https://placehold.co/400x200?text=' . urlencode($srv['title']);
        
        // CAMBIO SISTEMA: Enlace directo + JS tracking
        // Anteriormente: $link = "/go/" . $srv['slug'] . "?origin=landing_card";
        $link = $srv['destination_url']; 
        $slug = $srv['slug'];

        echo '
        <div class="col-md-6 col-lg-4 d-flex">
            <div class="service-card">
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
