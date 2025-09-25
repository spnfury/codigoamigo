<?php
// Página de tiendas con diseño moderno
include_once __DIR__ . '/../myphp/funciones_modern.php';

// Simular tiendas (en un caso real, esto vendría de la base de datos)
$tiendas = [
    ['nombre' => 'Amazon', 'categoria' => 'Tecnología', 'total_codigos' => 450, 'descuento_max' => '30%'],
    ['nombre' => 'Zara', 'categoria' => 'Moda', 'total_codigos' => 320, 'descuento_max' => '25%'],
    ['nombre' => 'Nike', 'categoria' => 'Deportes', 'total_codigos' => 280, 'descuento_max' => '40%'],
    ['nombre' => 'Sephora', 'categoria' => 'Belleza', 'total_codigos' => 250, 'descuento_max' => '20%'],
    ['nombre' => 'IKEA', 'categoria' => 'Hogar', 'total_codigos' => 200, 'descuento_max' => '15%'],
    ['nombre' => 'Booking', 'categoria' => 'Viajes', 'total_codigos' => 180, 'descuento_max' => '35%'],
    ['nombre' => 'Carrefour', 'categoria' => 'Alimentación', 'total_codigos' => 150, 'descuento_max' => '10%'],
    ['nombre' => 'Decathlon', 'categoria' => 'Deportes', 'total_codigos' => 140, 'descuento_max' => '25%'],
    ['nombre' => 'El Corte Inglés', 'categoria' => 'Moda', 'total_codigos' => 130, 'descuento_max' => '20%'],
    ['nombre' => 'MediaMarkt', 'categoria' => 'Tecnología', 'total_codigos' => 120, 'descuento_max' => '30%'],
    ['nombre' => 'Leroy Merlin', 'categoria' => 'Jardín', 'total_codigos' => 100, 'descuento_max' => '15%'],
    ['nombre' => 'Fnac', 'categoria' => 'Libros', 'total_codigos' => 90, 'descuento_max' => '25%']
];

// Usar el header moderno
include_once __DIR__ . '/../myphp/_header_modern.php';

echo '<div class="main-content">';
echo '<div class="codes-section">';

// Título de la página
echo '<h1 class="section-title">Nuestras Tiendas</h1>';
echo '<p style="text-align: center; color: #ccc; margin-bottom: 3rem; font-size: 1.1rem;">Descubre códigos de descuento de tus tiendas favoritas</p>';

// Grid de tiendas
echo '<div class="stores-grid">';
foreach($tiendas as $tienda) {
    echo '<div class="store-card">';
    echo '<div class="store-header">';
    echo '<div class="store-name">' . htmlspecialchars($tienda['nombre']) . '</div>';
    echo '<div class="store-category">' . htmlspecialchars($tienda['categoria']) . '</div>';
    echo '</div>';
    echo '<div class="store-stats">';
    echo '<div class="store-stat">';
    echo '<i class="fas fa-tag"></i>';
    echo '<span>' . $tienda['total_codigos'] . ' códigos</span>';
    echo '</div>';
    echo '<div class="store-stat">';
    echo '<i class="fas fa-percentage"></i>';
    echo '<span>Hasta ' . $tienda['descuento_max'] . '</span>';
    echo '</div>';
    echo '</div>';
    echo '<button class="store-button" onclick="viewStore(\'' . htmlspecialchars($tienda['nombre']) . '\')">';
    echo '<i class="fas fa-eye"></i> Ver Códigos';
    echo '</button>';
    echo '</div>';
}
echo '</div>';

echo '</div>';
echo '</div>';

// CSS adicional para tiendas
echo '
<style>
.stores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.store-card {
    background-color: var(--light-gray);
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #555;
}

.store-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.4);
}

.store-header {
    margin-bottom: 1.5rem;
}

.store-name {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary-orange);
    margin-bottom: 0.5rem;
}

.store-category {
    color: var(--text-gray);
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.store-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.store-stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-gray);
    font-size: 0.9rem;
}

.store-stat i {
    color: var(--primary-orange);
}

.store-button {
    background-color: var(--primary-orange);
    color: var(--text-white);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.store-button:hover {
    background-color: #E55A2B;
}

@media (max-width: 768px) {
    .stores-grid {
        grid-template-columns: 1fr;
    }
    
    .store-stats {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>';

// JavaScript para funcionalidad de tiendas
echo '
<script>
function viewStore(storeName) {
    // Implementar navegación a códigos de la tienda
    console.log("Ver códigos de:", storeName);
    // window.location.href = "/tienda/" + encodeURIComponent(storeName);
}
</script>';

// Incluir footer
get_footer();
?>
