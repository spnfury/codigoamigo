<?php
// Página de categorías con diseño moderno
include_once __DIR__ . '/../myphp/funciones_modern.php';

// Simular categorías (en un caso real, esto vendría de la base de datos)
$categorias = [
    ['nombre' => 'Moda', 'total_codigos' => 1250],
    ['nombre' => 'Tecnología', 'total_codigos' => 890],
    ['nombre' => 'Hogar', 'total_codigos' => 650],
    ['nombre' => 'Deportes', 'total_codigos' => 420],
    ['nombre' => 'Belleza', 'total_codigos' => 380],
    ['nombre' => 'Viajes', 'total_codigos' => 320],
    ['nombre' => 'Alimentación', 'total_codigos' => 280],
    ['nombre' => 'Jardín', 'total_codigos' => 150],
    ['nombre' => 'Mascotas', 'total_codigos' => 120],
    ['nombre' => 'Libros', 'total_codigos' => 95],
    ['nombre' => 'Música', 'total_codigos' => 80],
    ['nombre' => 'Juegos', 'total_codigos' => 65]
];

// Usar el header moderno
include_once __DIR__ . '/../myphp/_header_modern.php';
include_once __DIR__ . '/../myphp/_footer.php';

echo '<div class="main-content">';
echo '<div class="codes-section">';

// Título de la página
echo '<h1 class="section-title">Nuestras Categorías</h1>';
echo '<p style="text-align: center; color: #ccc; margin-bottom: 3rem; font-size: 1.1rem;">Explora códigos de descuento organizados por categorías</p>';

// Generar grid de categorías
echo '<div class="categories-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;">';
foreach ($categorias as $cat) {
    $nombre = htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8');
    $total = (int) $cat['total_codigos'];
    echo '<a href="/categoria/' . urlencode($cat['nombre']) . '" class="category-card" style="display:block;padding:1.5rem;border-radius:12px;background:rgba(255,255,255,0.05);text-align:center;text-decoration:none;color:inherit;">';
    echo '<div class="category-name" style="font-weight:700;font-size:1.1rem;">' . $nombre . '</div>';
    echo '<div class="category-count" style="color:#888;font-size:0.9rem;">' . $total . ' códigos</div>';
    echo '</a>';
}
echo '</div>';

echo '</div>';
echo '</div>';

// CSS adicional
echo get_modern_additional_css();

// Incluir footer
get_footer();
?>
