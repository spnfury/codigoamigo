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
$title = "Blog - CodigoAmigo.com";
$description = "Descubre consejos, guías y noticias sobre códigos de descuento, ahorro y cómo ganar dinero compartiendo códigos.";
$imagen_social = "https://www.codigoamigo.com/img/logo_social_codigoamigo_final.jpg";

// Llamar al header moderno
get_header_modern($title, $description, $title, $description, $imagen_social);

// Artículos del blog (por ahora estáticos, se puede conectar a MongoDB después)
$articulos = [
    [
        'titulo' => 'Cómo ganar dinero compartiendo códigos de descuento',
        'resumen' => 'Aprende las mejores estrategias para maximizar tus ganancias compartiendo códigos de descuento en CodigoAmigo.com.',
        'fecha' => '2024-01-15',
        'categoria' => 'Guías',
        'imagen' => 'https://www.codigoamigo.com/img/blog/ganar-dinero.jpg'
    ],
    [
        'titulo' => 'Los mejores códigos de descuento de este mes',
        'resumen' => 'Descubre los códigos de descuento más populares y con mejores beneficios del mes actual.',
        'fecha' => '2024-01-10',
        'categoria' => 'Ofertas',
        'imagen' => 'https://www.codigoamigo.com/img/blog/mejores-codigos.jpg'
    ],
    [
        'titulo' => 'Cómo verificar que un código de descuento funciona',
        'resumen' => 'Guía completa para verificar la validez de un código de descuento antes de usarlo.',
        'fecha' => '2024-01-05',
        'categoria' => 'Consejos',
        'imagen' => 'https://www.codigoamigo.com/img/blog/verificar-codigo.jpg'
    ],
    [
        'titulo' => 'Trucos para encontrar los mejores descuentos',
        'resumen' => 'Consejos y trucos para encontrar los mejores códigos de descuento y ahorrar más dinero.',
        'fecha' => '2023-12-28',
        'categoria' => 'Consejos',
        'imagen' => 'https://www.codigoamigo.com/img/blog/trucos-descuentos.jpg'
    ]
];
?>

<style>
.blog-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
}

.blog-header {
    text-align: center;
    margin-bottom: 50px;
}

.blog-header h1 {
    font-size: 3rem;
    font-weight: 700;
    color: #E30613;
    margin-bottom: 15px;
}

.blog-header p {
    font-size: 1.2rem;
    color: #cccccc;
    line-height: 1.6;
}

.articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.article-card {
    background: #333;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    display: block;
}

.article-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(227, 6, 19, 0.3);
}

.article-image {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #E30613, #C40510);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
}

.article-content {
    padding: 25px;
}

.article-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 0.9rem;
    color: #999;
}

.article-category {
    background: #E30613;
    color: white;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.article-date {
    color: #999;
}

.article-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 12px;
    line-height: 1.3;
}

.article-summary {
    font-size: 1rem;
    color: #cccccc;
    line-height: 1.6;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #333;
    border-radius: 10px;
}

.empty-state h2 {
    color: #E30613;
    margin-bottom: 15px;
}

.empty-state p {
    color: #cccccc;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .blog-container {
        padding: 15px;
    }
    
    .blog-header h1 {
        font-size: 2rem;
    }
    
    .articles-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}
</style>

<div class="blog-container">
    <div class="blog-header">
        <h1>Blog de CodigoAmigo</h1>
        <p>
            Descubre consejos, guías y noticias sobre códigos de descuento, ahorro y cómo ganar dinero compartiendo códigos
        </p>
    </div>

    <?php if (!empty($articulos)): ?>
        <div class="articles-grid">
            <?php foreach ($articulos as $articulo): ?>
                <a href="#" class="article-card" onclick="return false;">
                    <div class="article-image">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="article-content">
                        <div class="article-meta">
                            <span class="article-category"><?php echo htmlspecialchars($articulo['categoria']); ?></span>
                            <span class="article-date"><?php echo date('d/m/Y', strtotime($articulo['fecha'])); ?></span>
                        </div>
                        <h2 class="article-title"><?php echo htmlspecialchars($articulo['titulo']); ?></h2>
                        <p class="article-summary"><?php echo htmlspecialchars($articulo['resumen']); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h2>Próximamente</h2>
            <p>Estamos preparando contenido increíble para ti. ¡Vuelve pronto!</p>
        </div>
    <?php endif; ?>
</div>

<?php
// Incluir footer
include_once __DIR__ . '/../myphp/_footer.php';
get_footer_modern();
?>

