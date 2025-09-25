<?php
// Página de demostración del diseño moderno
include_once __DIR__ . '/../myphp/funciones_modern.php';

// Simular datos de códigos para la demostración
$demo_codigos = [
    [
        '_id' => 'demo1',
        'marca' => 'Amazon',
        'descripcion' => 'Obtén un 20% de descuento en tu primera compra con este código exclusivo. Válido para productos seleccionados.',
        'num_beneficio' => 50,
        'num_valoraciones' => 125
    ],
    [
        '_id' => 'demo2',
        'marca' => 'Nike',
        'descripcion' => 'Descuento del 30% en calzado deportivo. Perfecto para renovar tu guardarropa deportivo.',
        'num_beneficio' => 75,
        'num_valoraciones' => 89
    ],
    [
        '_id' => 'demo3',
        'marca' => 'Zara',
        'descripcion' => '15% de descuento en toda la colección de moda. No incluye outlet ni rebajas.',
        'num_beneficio' => 25,
        'num_valoraciones' => 67
    ],
    [
        '_id' => 'demo4',
        'marca' => 'Sephora',
        'descripcion' => 'Descuento del 25% en cosméticos y productos de belleza. Válido para compras superiores a 30€.',
        'num_beneficio' => 40,
        'num_valoraciones' => 156
    ],
    [
        '_id' => 'demo5',
        'marca' => 'Booking',
        'descripcion' => '10% de descuento en reservas de hoteles. Perfecto para tu próxima escapada.',
        'num_beneficio' => 60,
        'num_valoraciones' => 203
    ],
    [
        '_id' => 'demo6',
        'marca' => 'IKEA',
        'descripcion' => 'Descuento del 15% en muebles y decoración para el hogar. Haz tu casa más acogedora.',
        'num_beneficio' => 35,
        'num_valoraciones' => 94
    ]
];

// Usar el header moderno
include_once __DIR__ . '/../myphp/_header_modern.php';

echo '<div class="main-content">';
echo '<div class="codes-section">';

// Título de la página
echo '<h1 class="section-title">Diseño Moderno - CodigoAmigo.com</h1>';
echo '<p style="text-align: center; color: #ccc; margin-bottom: 3rem; font-size: 1.1rem;">Demostración del nuevo diseño basado en el mockup</p>';

// Características del diseño
echo '<div class="features-section">';
echo '<h2 class="section-title">Características del Nuevo Diseño</h2>';
echo '<div class="features-grid">';
echo '<div class="feature-card">';
echo '<div class="feature-icon"><i class="fas fa-palette"></i></div>';
echo '<div class="feature-title">Diseño Moderno</div>';
echo '<div class="feature-description">Esquema de colores gris oscuro y naranja, inspirado en el mockup</div>';
echo '</div>';
echo '<div class="feature-card">';
echo '<div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>';
echo '<div class="feature-title">Responsive</div>';
echo '<div class="feature-description">Adaptable a todos los dispositivos móviles y desktop</div>';
echo '</div>';
echo '<div class="feature-card">';
echo '<div class="feature-icon"><i class="fas fa-search"></i></div>';
echo '<div class="feature-title">Búsqueda Avanzada</div>';
echo '<div class="feature-description">Búsqueda en tiempo real con interfaz intuitiva</div>';
echo '</div>';
echo '<div class="feature-card">';
echo '<div class="feature-icon"><i class="fas fa-tags"></i></div>';
echo '<div class="feature-title">Categorías</div>';
echo '<div class="feature-description">Organización por categorías con iconos representativos</div>';
echo '</div>';
echo '<div class="feature-card">';
echo '<div class="feature-icon"><i class="fas fa-store"></i></div>';
echo '<div class="feature-title">Tiendas</div>';
echo '<div class="feature-description">Página dedicada a tiendas con estadísticas</div>';
echo '</div>';
echo '<div class="feature-card">';
echo '<div class="feature-icon"><i class="fas fa-pagination"></i></div>';
echo '<div class="feature-title">Paginación</div>';
echo '<div class="feature-description">Navegación moderna entre páginas de códigos</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Demostración de códigos
echo '<h2 class="section-title">Códigos de Demostración</h2>';
echo '<div class="codes-grid">';
echo generate_modern_code_cards($demo_codigos);
echo '</div>';

// Demostración de paginación
echo generate_modern_pagination(150, 1, 6);

echo '</div>';
echo '</div>';

// CSS adicional para características
echo '
<style>
.features-section {
    margin: 4rem 0;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.feature-card {
    background-color: var(--light-gray);
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    transition: transform 0.3s ease;
    border: 1px solid #555;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    font-size: 3rem;
    color: var(--primary-orange);
    margin-bottom: 1rem;
}

.feature-title {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--text-white);
    margin-bottom: 1rem;
}

.feature-description {
    color: var(--text-gray);
    line-height: 1.5;
}

@media (max-width: 768px) {
    .features-grid {
        grid-template-columns: 1fr;
    }
}
</style>';

// JavaScript para funcionalidad de demostración
echo '
<script>
function viewCode(codeId) {
    alert("Ver código: " + codeId + "\\n\\nEsta es una demostración del nuevo diseño.");
}

// Efectos de hover mejorados
$(document).ready(function() {
    $(".code-card").hover(
        function() {
            $(this).css("transform", "translateY(-8px)");
        },
        function() {
            $(this).css("transform", "translateY(0)");
        }
    );
});
</script>';
?>
