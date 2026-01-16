<?php
// Inicializar variables globales
$GLOBALS['website'] = 'https://www.codigoamigo.com/';
$GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_modern.php';
include_once __DIR__ . '/../myphp/_header_modern.php';

$GLOBALS['header_modern_used'] = true;

// Inicializar detector de móviles
if (!isset($detect)) {
    $detect = new Mobile_Detect();
}
$GLOBALS['detect'] = $detect;

// Título y descripción de la página
$title = "Sobre Nosotros - CodigoAmigo.com";
$description = "Conoce más sobre CodigoAmigo.com, la comunidad más grande de España para compartir códigos de descuento verificados.";
$imagen_social = "https://www.codigoamigo.com/img/logo_social_codigoamigo_final.jpg";

// Llamar al header moderno
get_header_modern($title, $description, $title, $description, $imagen_social);
?>

<style>
.about-container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 20px;
}

.about-hero {
    text-align: center;
    margin-bottom: 60px;
    padding: 40px 20px;
    background: linear-gradient(135deg, #2c2c2c 0%, #404040 100%);
    border-radius: 15px;
}

.about-hero h1 {
    font-size: 3rem;
    font-weight: 700;
    color: #E30613;
    margin-bottom: 20px;
}

.about-hero p {
    font-size: 1.3rem;
    color: #cccccc;
    line-height: 1.8;
    max-width: 800px;
    margin: 0 auto;
}

.about-section {
    margin-bottom: 50px;
    padding: 30px;
    background: #333;
    border-radius: 10px;
}

.about-section h2 {
    font-size: 2rem;
    color: #E30613;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #E30613;
}

.about-section p {
    font-size: 1.1rem;
    color: #cccccc;
    line-height: 1.8;
    margin-bottom: 15px;
}

.about-section ul {
    color: #cccccc;
    line-height: 1.8;
    margin-left: 20px;
    margin-bottom: 15px;
}

.about-section li {
    margin-bottom: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 40px 0;
}

.stat-card {
    background: #2c2c2c;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    border: 2px solid #404040;
    transition: all 0.3s ease;
}

.stat-card:hover {
    border-color: #E30613;
    transform: translateY(-5px);
}

.stat-number {
    font-size: 3rem;
    font-weight: 700;
    color: #E30613;
    margin-bottom: 10px;
}

.stat-label {
    font-size: 1.1rem;
    color: #cccccc;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-top: 30px;
}

.value-card {
    background: #2c2c2c;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
}

.value-icon {
    font-size: 3rem;
    color: #E30613;
    margin-bottom: 15px;
}

.value-card h3 {
    font-size: 1.5rem;
    color: #ffffff;
    margin-bottom: 15px;
}

.value-card p {
    color: #cccccc;
    line-height: 1.6;
}

.cta-section {
    background: linear-gradient(135deg, #E30613 0%, #C40510 100%);
    padding: 50px 30px;
    border-radius: 15px;
    text-align: center;
    margin-top: 50px;
}

.cta-section h2 {
    color: white;
    font-size: 2.5rem;
    margin-bottom: 20px;
    border: none;
    padding: 0;
}

.cta-section p {
    color: white;
    font-size: 1.2rem;
    margin-bottom: 30px;
}

.cta-button {
    display: inline-block;
    padding: 15px 40px;
    background: white;
    color: #E30613;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .about-container {
        padding: 15px;
    }
    
    .about-hero h1 {
        font-size: 2rem;
    }
    
    .about-hero p {
        font-size: 1.1rem;
    }
    
    .about-section {
        padding: 20px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .values-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="about-container">
    <div class="about-hero">
        <h1>Sobre CodigoAmigo.com</h1>
        <p>
            Somos la comunidad más grande de España dedicada a compartir códigos de descuento verificados. 
            Nuestra misión es ayudar a las personas a ahorrar dinero mientras ganan beneficios compartiendo 
            sus mejores hallazgos con la comunidad.
        </p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">+12.500</div>
            <div class="stat-label">Usuarios Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">+5.000</div>
            <div class="stat-label">Miembros en Telegram</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">100%</div>
            <div class="stat-label">Códigos Verificados</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Soporte Disponible</div>
        </div>
    </div>

    <div class="about-section">
        <h2>Nuestra Historia</h2>
        <p>
            CodigoAmigo.com nació de la idea de crear una plataforma donde las personas pudieran compartir 
            códigos de descuento de forma segura y verificada. Comenzamos como un pequeño proyecto y 
            hemos crecido hasta convertirnos en la comunidad más grande de España en este sector.
        </p>
        <p>
            Creemos que compartir códigos de descuento no solo ayuda a ahorrar dinero, sino que también 
            permite a las personas ganar beneficios mientras ayudan a otros. Nuestra plataforma facilita 
            este intercambio de manera segura y transparente.
        </p>
    </div>

    <div class="about-section">
        <h2>Nuestra Misión</h2>
        <p>
            Nuestra misión es simple pero poderosa: ayudar a las personas a ahorrar dinero mientras 
            ganan beneficios compartiendo códigos de descuento verificados. Queremos crear una comunidad 
            donde todos puedan beneficiarse mutuamente.
        </p>
        <p>Nos comprometemos a:</p>
        <ul>
            <li>Verificar todos los códigos antes de publicarlos</li>
            <li>Proporcionar una plataforma segura y fácil de usar</li>
            <li>Ofrecer soporte 24/7 a nuestros usuarios</li>
            <li>Mantener la transparencia en todos nuestros procesos</li>
            <li>Crear una comunidad activa y colaborativa</li>
        </ul>
    </div>

    <div class="about-section">
        <h2>Nuestros Valores</h2>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Seguridad</h3>
                <p>Verificamos todos los códigos para garantizar que funcionan correctamente y son seguros de usar.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-users"></i></div>
                <h3>Comunidad</h3>
                <p>Creemos en el poder de la comunidad y en ayudar a otros mientras te ayudas a ti mismo.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-handshake"></i></div>
                <h3>Transparencia</h3>
                <p>Mantenemos procesos transparentes y claros para que todos sepan cómo funciona la plataforma.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-heart"></i></div>
                <h3>Compromiso</h3>
                <p>Estamos comprometidos con el éxito de nuestros usuarios y con mejorar continuamente la plataforma.</p>
            </div>
        </div>
    </div>

    <div class="about-section">
        <h2>¿Cómo Funciona?</h2>
        <p>
            CodigoAmigo.com es muy fácil de usar:
        </p>
        <ol style="color: #cccccc; line-height: 1.8; margin-left: 20px;">
            <li><strong>Busca códigos:</strong> Explora miles de códigos de descuento verificados organizados por categorías y marcas.</li>
            <li><strong>Comparte códigos:</strong> Publica tus propios códigos de descuento y gana beneficios cuando otros los usen.</li>
            <li><strong>Gana dinero:</strong> Recibe beneficios en efectivo, puntos o servicios cuando tus códigos sean utilizados.</li>
            <li><strong>Únete a la comunidad:</strong> Conecta con otros usuarios, comparte experiencias y descubre nuevas oportunidades.</li>
        </ol>
    </div>

    <div class="cta-section">
        <h2>¿Listo para empezar?</h2>
        <p>Únete a nuestra comunidad y comienza a ahorrar mientras ganas dinero</p>
        <a href="/nuevo_codigo" class="cta-button">Publicar mi primer código</a>
    </div>
</div>

<?php
// Incluir footer
include_once __DIR__ . '/../myphp/_footer.php';
get_footer_modern();
?>

