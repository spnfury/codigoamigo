<?php
/**
 * Página de Códigos Destacados - Champions de Códigos
 * 
 * Muestra todos los códigos que han pagado por destacarse,
 * separados en Super Destacados (premium) y Destacados normales.
 */

// Usar header moderno
get_header_modern($title, $description, $title_social, $description_social, $imagen_social, $links_meta);
$GLOBALS['header_modern_used'] = true;

// Incluir funciones necesarias
if (!function_exists('generate_chollometro_code_card')) {
    include_once __DIR__ . '/../myphp/funciones_chollometro.php';
}
if (!function_exists('getObjectMarca')) {
    include_once __DIR__ . '/../myphp/funciones_marca.php';
}

// Incluir CSS dedicado
echo '<link rel="stylesheet" href="/assets/css/destacados.css">';
echo '<link rel="stylesheet" href="/assets/css/chollometro-style.css">';

$total_super = count($lista_super_destacados);
$total_normal = count($lista_destacados_normales);
$total_all = $total_super + $total_normal;
?>

<!-- Hero Section - Champions -->
<div class="destacados-hero">
    <div class="container">
        <div class="hero-trophy">🏆</div>
        <h1 class="hero-title">
            La <span class="gold-text">Champions</span> de los Códigos
        </h1>
        <p class="hero-subtitle">
            Los códigos más exclusivos y verificados de la comunidad. 
            Solo los mejores llegan aquí.
        </p>
        <div class="hero-counter">
            <span class="counter-pulse"></span>
            <span class="counter-number"><?php echo $total_all; ?></span>
            <span>códigos destacados activos</span>
        </div>
    </div>
</div>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">

    <!-- ============================================
         SUPER DESTACADOS - Plan Premium 3,99€
         ============================================ -->
    <div class="destacados-section super-section">
        <div class="section-header">
            <span class="section-icon">⭐</span>
            <h2 class="section-title">Super Destacados</h2>
            <?php if ($total_super > 0): ?>
                <span class="section-count"><?php echo $total_super; ?> códigos</span>
            <?php endif; ?>
        </div>
        <p class="section-description" style="margin-top: -15px; margin-bottom: 25px;">
            Códigos Premium con máxima visibilidad en toda la web
        </p>

        <?php if (!empty($lista_super_destacados)): ?>
            <div class="super-cards-grid">
                <?php foreach ($lista_super_destacados as $codigo): ?>
                    <div class="super-card-wrapper">
                        <div class="super-badge">
                            <i class="fas fa-crown"></i> Premium
                        </div>
                        <?php echo generate_chollometro_code_card($codigo, true); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-destacados">
                <i class="fas fa-crown" style="color: #FFD700;"></i>
                <h4>Aún no hay Super Destacados</h4>
                <p>Sé el primero en destacar tu código con el plan Premium</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============================================
         CTA Banner intermedio
         ============================================ -->
    <div class="cta-banner">
        <div class="cta-content">
            <h2>¿Quieres que tu código aparezca <span class="gold-text">aquí</span>?</h2>
            <p>Aumenta la visibilidad de tu código y consigue más canjes destacándolo en esta página exclusiva</p>
            
            <div class="cta-benefits">
                <div class="cta-benefit">
                    <i class="fas fa-eye"></i>
                    <span>Mayor visibilidad</span>
                </div>
                <div class="cta-benefit">
                    <i class="fas fa-chart-line"></i>
                    <span>Más canjes</span>
                </div>
                <div class="cta-benefit">
                    <i class="fas fa-star"></i>
                    <span>Prioridad en búsquedas</span>
                </div>
                <div class="cta-benefit">
                    <i class="fas fa-share-alt"></i>
                    <span>Promoción en redes</span>
                </div>
            </div>

            <div class="cta-buttons">
                <a href="/nuevo_codigo" class="btn-cta-primary">
                    <i class="fas fa-plus"></i>
                    Publicar y Destacar Código
                </a>
                <a href="https://t.me/codigoamigocom" target="_blank" class="btn-cta-secondary">
                    <i class="fab fa-telegram"></i>
                    ¿Dudas? Contáctanos
                </a>
            </div>

            <div class="price-tags">
                <div class="price-tag">
                    <div class="price-label">Plan Básico</div>
                    <div class="price-amount">0,99€</div>
                </div>
                <div class="price-tag premium-tag">
                    <div class="price-label">Plan Premium</div>
                    <div class="price-amount">3,99€</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         DESTACADOS NORMALES - Plan Básico 0,99€
         ============================================ -->
    <div class="destacados-section normal-section">
        <div class="section-header">
            <span class="section-icon">🔥</span>
            <h2 class="section-title">Códigos Destacados</h2>
            <?php if ($total_normal > 0): ?>
                <span class="section-count"><?php echo $total_normal; ?> códigos</span>
            <?php endif; ?>
        </div>
        <p class="section-description" style="margin-top: -15px; margin-bottom: 25px;">
            Códigos promocionados y verificados por la comunidad
        </p>

        <?php if (!empty($lista_destacados_normales)): ?>
            <div class="normal-cards-grid">
                <?php foreach ($lista_destacados_normales as $codigo): ?>
                    <div class="normal-card-wrapper">
                        <?php echo generate_chollometro_code_card($codigo, true); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-destacados">
                <i class="fas fa-star"></i>
                <h4>No hay códigos destacados</h4>
                <p>¡Destaca tu código por solo 0,99€!</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php get_footer(); ?>
