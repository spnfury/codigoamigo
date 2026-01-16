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
$description = "Encuentra respuestas a las preguntas más frecuentes sobre CodigoAmigo.com, cómo compartir códigos, ganar dinero y más.";
$imagen_social = "https://www.codigoamigo.com/img/logo_social_codigoamigo_final.jpg";

// Llamar al header moderno
get_header_modern($title, $description, $title, $description, $imagen_social);
?>

<style>
.page-container {
    max-width: 900px;
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
    max-height: 1000px;
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
}

@media (max-width: 768px) {
    .page-container {
        padding: 15px;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .faq-question {
        padding: 15px 20px;
        font-size: 1rem;
    }
    
    .faq-answer.active {
        padding: 15px 20px;
    }
}
</style>

<div class="page-container">
    <h1 class="page-title">Preguntas Frecuentes</h1>
    <p class="page-description">
        Encuentra respuestas a las preguntas más comunes sobre CodigoAmigo.com
    </p>

    <div class="faq-section">
        <h2>General</h2>
        
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Qué es CodigoAmigo.com?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>CodigoAmigo.com es la comunidad más grande de España dedicada a compartir códigos de descuento verificados. Miles de usuarios comparten sus mejores hallazgos cada día, y tú puedes ganar dinero recomendando códigos a tus amigos.</p>
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
                        <li>Dinero en efectivo</li>
                        <li>Puntos o créditos</li>
                        <li>Servicios gratuitos</li>
                        <li>Descuentos exclusivos</li>
                    </ul>
                    <p>Los beneficios varían según la marca y el tipo de código que compartas.</p>
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Los códigos son verificados?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Sí, todos los códigos publicados en CodigoAmigo.com son verificados antes de ser publicados. Nuestro equipo revisa cada código para asegurar que funciona correctamente y proporciona el descuento prometido.</p>
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
                    <p>¡Absolutamente! CodigoAmigo.com es completamente gratuito para usar. Puedes buscar códigos de descuento, compartir tus propios códigos y ganar dinero sin ningún costo.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="faq-section">
        <h2>Publicar Códigos</h2>
        
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo puedo publicar un código de descuento?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Para publicar un código:</p>
                    <ol>
                        <li>Inicia sesión en tu cuenta (o créala si no tienes una)</li>
                        <li>Haz clic en "Publicar código" en el menú</li>
                        <li>Completa el formulario con la información del código</li>
                        <li>Envía el código para su verificación</li>
                    </ol>
                    <p>Una vez verificado, tu código será publicado y visible para todos los usuarios.</p>
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
                    <p>Sí, puedes publicar códigos de cualquier marca que tenga un programa de referidos o códigos de descuento. Si la marca no está en nuestro listado, puedes sugerirla y la añadiremos.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="faq-section">
        <h2>Cuenta y Perfil</h2>
        
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFAQ(this)">
                <span>¿Cómo creo una cuenta?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    <p>Puedes crear una cuenta de forma gratuita haciendo clic en "Registrarse" en la parte superior de la página. Puedes registrarte con tu email o usando tu cuenta de Google.</p>
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
                    <p>Una vez que inicies sesión, puedes acceder a tus estadísticas desde el menú de usuario. Allí verás información sobre tus códigos publicados, clics recibidos y beneficios obtenidos.</p>
                </div>
            </div>
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

