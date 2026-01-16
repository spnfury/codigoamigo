<?php
/**
 * Vista para Super Landing
 * Variables disponibles: $landing
 */

// Obtener códigos relacionados
$all_codigos = get_super_landing_codes($landing, 50);

// Función para obtener el mejor código super de una marca específica
$get_super_for_brand = function($brand_slug) use ($all_codigos) {
    foreach ($all_codigos as $c) {
        if (isset($c['tipo_destacado']) && $c['tipo_destacado'] === 'super' && ($c['marca'] === $brand_slug || (isset($c['marca_id']) && $c['marca_id'] === $brand_slug))) {
            return $c;
        }
    }
    return null;
};

$super_codigos = array_filter($all_codigos, function($c) {
    return isset($c['tipo_destacado']) && $c['tipo_destacado'] === 'super';
});
$normal_codigos = array_filter($all_codigos, function($c) {
    return !(isset($c['tipo_destacado']) && $c['tipo_destacado'] === 'super');
});

// For the top carousel, we still use $super_codigos
$codigos = array_values($super_codigos);
?>

<style>
/* Estilos específicos para Super Landing */
.super-landing-hero {
    position: relative;
    padding: 80px 0 60px;
    background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo htmlspecialchars($landing['hero_image'] ?? '/img/hero-default.jpg'); ?>');
    background-size: cover;
    background-position: center;
    color: white;
    text-align: center;
    border-radius: 0 0 20px 20px;
    margin-bottom: 40px;
}

.super-landing-hero h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.super-landing-hero .lead {
    font-size: 1.2rem;
    max-width: 800px;
    margin: 0 auto;
    opacity: 0.9;
}

.sl-section {
    padding: 60px 0;
}

.sl-section-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 40px;
    text-align: center;
    position: relative;
    padding-bottom: 15px;
}

.sl-section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 4px;
    background: #E30613;
    border-radius: 2px;
}

/* Pros & Cons */
.pros-cons-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.pros-box, .cons-box {
    padding: 30px;
    border-radius: 12px;
}

.pros-box {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

.cons-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
}

.pros-box h3, .cons-box h3 {
    margin-top: 0;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.pros-box h3 { color: #166534; }
.cons-box h3 { color: #991b1b; }

.pros-list, .cons-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.pros-list li, .cons-list li {
    margin-bottom: 12px;
    position: relative;
    padding-left: 25px;
}

.pros-list li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #16a34a;
    font-weight: bold;
}

.cons-list li::before {
    content: '✕';
    position: absolute;
    left: 0;
    color: #dc2626;
    font-weight: bold;
}

/* Steps */
.steps-timeline {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
}

.step-item {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.step-number {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: #E30613;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.step-content h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

/* FAQ */
.faq-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 15px;
    padding: 20px;
}

.faq-question {
    font-weight: 600;
    font-size: 1.1rem;
    color: #1f2937;
    margin-bottom: 10px;
}

.faq-answer {
    color: #4b5563;
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
    .pros-cons-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="super-landing-container">
    <!-- Hero Section -->
    <div class="super-landing-hero">
        <div class="container">
            <h1><?php echo htmlspecialchars($landing['title']); ?></h1>
            <?php if (!empty($landing['meta_description'])): ?>
                <p class="lead"><?php echo htmlspecialchars($landing['meta_description']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <!-- Main Content Area -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                
                <!-- Dynamic Sections -->
                <?php if (!empty($landing['sections'])): ?>
                    <?php foreach ($landing['sections'] as $section): ?>
                        
                        <!-- Text/Intro Section -->
                        <?php if ($section['type'] === 'intro' || $section['type'] === 'text'): ?>
                            <div class="sl-section intro-section">
                                <?php if (!empty($section['title'])): ?>
                                    <h2 class="sl-section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                <?php endif; ?>
                                <div class="sl-content">
                                    <?php echo $section['content']; // Assumed to be safe HTML from DB ?>
                                </div>
                                
                                <?php if (isset($section['brand_slot'])): ?>
                                    <?php 
                                    $brand_super_code = $get_super_for_brand($section['brand_slot']);
                                    if ($brand_super_code): ?>
                                        <div class="targeted-super-slot mt-4 mb-4">
                                             <div class="super-featured-header mb-3" style="position: static; margin-bottom: 20px;">
                                                 <i class="fas fa-star text-warning"></i> Recomendado para <?php echo htmlspecialchars($section['brand_slot_name'] ?? $section['brand_slot']); ?>
                                             </div>
                                             <?php echo generate_modern_code_cards([$brand_super_code]); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        
                        <!-- Pros & Cons Section -->
                        <?php elseif ($section['type'] === 'pros_cons'): ?>
                            <div class="sl-section pros-cons-section">
                                <h2 class="sl-section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                <div class="pros-cons-grid">
                                    <div class="pro-card">
                                <h4 class="text-success"><i class="fas fa-thumbs-up"></i> Lo bueno</h4>
                                <ul>
                                    <?php foreach ($section['items']['pros'] as $pro): ?>
                                        <li><i class="fas fa-check text-success"></i> <?php echo $pro; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="con-card">
                                <h4 class="text-danger"><i class="fas fa-thumbs-down"></i> Lo mejorable</h4>
                                <ul>
                                    <?php foreach ($section['items']['cons'] as $con): ?>
                                        <li><i class="fas fa-times text-danger"></i> <?php echo $con; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <style>
                            .pros-cons-grid {
                                display: grid;
                                grid-template-columns: 1fr 1fr;
                                gap: 20px;
                            }
                            .pro-card, .con-card {
                                padding: 25px;
                                border-radius: 12px;
                                color: #333; /* Explicit dark text for contrast */
                            }
                            .pro-card {
                                background-color: #f0f9f0;
                                border: 1px solid #d0e9d0;
                            }
                            .con-card {
                                background-color: #fff5f5;
                                border: 1px solid #fadbd8;
                            }
                            .pro-card h4, .con-card h4 {
                                margin-top: 0;
                                margin-bottom: 20px;
                                font-weight: 700;
                            }
                            .pro-card ul, .con-card ul {
                                list-style: none;
                                padding: 0;
                                margin: 0;
                            }
                            .pro-card li, .con-card li {
                                margin-bottom: 12px;
                                position: relative;
                                padding-left: 0;
                                display: flex;
                                align-items: flex-start;
                                gap: 10px;
                                line-height: 1.5;
                                color: #444; /* Dark gray for list items */
                            }
                            .pro-card i, .con-card i {
                                margin-top: 4px; /* Align icon with text */
                            }
                            @media (max-width: 768px) {
                                .pros-cons-grid { grid-template-columns: 1fr; }
                            }
                        </style>
                    </div>

                        <!-- Steps Section -->
                        <?php elseif ($section['type'] === 'steps'): ?>
                            <div class="sl-section steps-section">
                                <h2 class="sl-section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                <div class="steps-timeline">
                                    <?php foreach ($section['steps'] as $index => $step): ?>
                                        <div class="step-item">
                                            <div class="step-number"><?php echo $index + 1; ?></div>
                                            <div class="step-content">
                                                <h4><?php echo htmlspecialchars($step['title']); ?></h4>
                                                <p><?php echo htmlspecialchars($step['text']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        <!-- FAQ Section -->
                        <?php elseif ($section['type'] === 'faq'): ?>
                            <div class="sl-section faq-section">
                                <h2 class="sl-section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
                                <div class="faq-list">
                                    <?php foreach ($section['faqs'] as $faq): ?>
                                        <div class="faq-item">
                                            <div class="faq-question"><?php echo htmlspecialchars($faq['question']); ?></div>
                                            <div class="faq-answer"><?php echo htmlspecialchars($faq['answer']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Active Codes Section -->
                <?php if (!empty($codigos)): ?>
                    <div class="sl-section codes-section" id="codigos-activos">
                        <h2 class="sl-section-title">Códigos Activos Verificados</h2>
                        
                        <?php 
                        // Note: $super_codigos and $normal_codigos are already defined at the top
                        ?>

                        <!-- Super Featured Box -->
                        <?php if (!empty($super_codigos)): ?>
                            <div class="super-featured-box mb-5">
                                <div class="super-featured-header">
                                    <i class="fas fa-star text-warning"></i> Recomendados por los Editores
                                </div>
                                
                                <?php if (count($super_codigos) > 1): ?>
                                    <!-- Multiple Super Featured Codes - Carousel -->
                                    <div class="super-carousel-wrapper">
                                        <button class="carousel-nav prev" onclick="moveCarousel(-1)">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <div class="super-carousel-container">
                                            <div class="super-carousel-track" id="superCarouselTrack">
                                                <?php foreach ($super_codigos as $index => $codigo): ?>
                                                    <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                                        <?php 
                                                        if (function_exists('generate_modern_code_cards')) {
                                                            echo generate_modern_code_cards([$codigo]);
                                                        }
                                                        ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <button class="carousel-nav next" onclick="moveCarousel(1)">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                        
                                        <!-- Carousel Indicators -->
                                        <div class="carousel-indicators">
                                            <?php foreach ($super_codigos as $index => $codigo): ?>
                                                <span class="indicator <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                      onclick="goToSlide(<?php echo $index; ?>)"></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <script>
                                    let currentSlide = 0;
                                    const totalSlides = <?php echo count($super_codigos); ?>;
                                    
                                    function moveCarousel(direction) {
                                        currentSlide += direction;
                                        if (currentSlide < 0) currentSlide = totalSlides - 1;
                                        if (currentSlide >= totalSlides) currentSlide = 0;
                                        updateCarousel();
                                    }
                                    
                                    function goToSlide(index) {
                                        currentSlide = index;
                                        updateCarousel();
                                    }
                                    
                                    function updateCarousel() {
                                        const slides = document.querySelectorAll('.carousel-slide');
                                        const indicators = document.querySelectorAll('.indicator');
                                        
                                        slides.forEach((slide, index) => {
                                            slide.classList.toggle('active', index === currentSlide);
                                        });
                                        
                                        indicators.forEach((indicator, index) => {
                                            indicator.classList.toggle('active', index === currentSlide);
                                        });
                                    }
                                    
                                    // Auto-advance carousel every 5 seconds
                                    setInterval(() => moveCarousel(1), 5000);
                                    </script>
                                    
                                <?php else: ?>
                                    <!-- Single Super Featured Code -->
                                    <div class="codes-grid-wrapper super-grid">
                                        <?php 
                                        if (function_exists('generate_modern_code_cards')) {
                                            echo generate_modern_code_cards($super_codigos);
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <style>
                                .super-featured-box {
                                    background: #fff9fa;
                                    border: 2px solid #E30613;
                                    border-radius: 12px;
                                    padding: 20px;
                                    position: relative;
                                    margin-bottom: 40px;
                                }
                                .super-featured-header {
                                    background: #E30613;
                                    color: white;
                                    display: inline-block;
                                    padding: 5px 20px;
                                    border-radius: 20px;
                                    font-weight: bold;
                                    font-size: 0.9rem;
                                    position: absolute;
                                    top: -15px;
                                    left: 20px;
                                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                                }
                                
                                /* Carousel Styles */
                                .super-carousel-wrapper {
                                    position: relative;
                                    padding: 20px 60px;
                                }
                                
                                .super-carousel-container {
                                    overflow: hidden;
                                    width: 100%;
                                }
                                
                                .super-carousel-track {
                                    display: flex;
                                    position: relative;
                                }
                                
                                .carousel-slide {
                                    min-width: 100%;
                                    display: none;
                                    transition: opacity 0.3s ease;
                                }
                                
                                .carousel-slide.active {
                                    display: block;
                                }
                                
                                .carousel-nav {
                                    position: absolute;
                                    top: 50%;
                                    transform: translateY(-50%);
                                    background: #E30613;
                                    color: white;
                                    border: none;
                                    width: 40px;
                                    height: 40px;
                                    border-radius: 50%;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 18px;
                                    transition: all 0.3s ease;
                                    z-index: 10;
                                }
                                
                                .carousel-nav:hover {
                                    background: #c40510;
                                    transform: translateY(-50%) scale(1.1);
                                }
                                
                                .carousel-nav.prev {
                                    left: 10px;
                                }
                                
                                .carousel-nav.next {
                                    right: 10px;
                                }
                                
                                .carousel-indicators {
                                    display: flex;
                                    justify-content: center;
                                    gap: 8px;
                                    margin-top: 15px;
                                }
                                
                                .indicator {
                                    width: 10px;
                                    height: 10px;
                                    border-radius: 50%;
                                    background: #ccc;
                                    cursor: pointer;
                                    transition: all 0.3s ease;
                                }
                                
                                .indicator.active {
                                    background: #E30613;
                                    width: 30px;
                                    border-radius: 5px;
                                }
                                
                                .super-grid .code-card {
                                    border-color: #ffd700;
                                    box-shadow: 0 10px 20px rgba(227, 6, 19, 0.1);
                                }
                                
                                @media (max-width: 768px) {
                                    .super-carousel-wrapper {
                                        padding: 20px 50px;
                                    }
                                    .carousel-nav {
                                        width: 35px;
                                        height: 35px;
                                        font-size: 14px;
                                    }
                                }
                            </style>
                        <?php endif; ?>


                        <!-- Standard Codes -->
                        <?php if (!empty($normal_codigos)): ?>
                            <div class="codes-grid-wrapper">
                                <?php 
                                if (function_exists('generate_modern_code_cards')) {
                                    echo generate_modern_code_cards($normal_codigos);
                                }
                                ?>
                                <!-- Promo Card for Grid -->
                                <div class="code-card promo-card-grid">
                                    <div class="promo-content">
                                        <div class="promo-icon"><i class="fas fa-plus-circle"></i></div>
                                        <h3>¿Tu código aquí?</h3>
                                        <p>Únete a la comunidad y gana recompensas.</p>
                                        <a href="/nuevo_codigo?marca_preselected=<?php echo htmlspecialchars($landing_data['linked_brand_slugs'][0] ?? $landing_data['slug']); ?>" class="btn btn-outline-danger btn-sm">Publicar Código</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                <?php else: ?>
                    <div class="sl-section no-codes text-center">
                         <div class="empty-state-card">
                            <i class="fas fa-trophy empty-icon"></i>
                            <h3>¡Sé el primero en aparecer aquí!</h3>
                            <p>Esta guía es visitada por miles de usuarios buscando códigos. <br>Publica el tuyo ahora y comienza a ganar referidos.</p>
                            <a href="/nuevo_codigo?marca_preselected=<?php echo htmlspecialchars($landing_data['linked_brand_slugs'][0] ?? $landing_data['slug']); ?>" class="btn btn-primary btn-lg pulse-button">
                                <i class="fas fa-plus-circle"></i> Publicar mi código GRATIS
                            </a>
                            <p class="small text-muted mt-3"><i class="fas fa-check"></i> Registro en 1 minuto <i class="fas fa-check"></i> Sin coste</p>
                         </div>
                    </div>
                <?php endif; ?>

                <style>
                    /* Promo Card styles */
                    .promo-card-grid {
                        background: #fff5f5;
                        border: 2px dashed #E30613 !important;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        text-align: center;
                        min-height: 250px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                    }
                    .promo-card-grid:hover {
                        background: #ffebeb;
                        transform: translateY(-5px);
                        box-shadow: 0 5px 15px rgba(227, 6, 19, 0.15);
                    }
                    .promo-icon {
                        font-size: 3rem;
                        color: #E30613;
                        margin-bottom: 15px;
                        opacity: 0.5;
                    }
                    .promo-card-grid:hover .promo-icon {
                        opacity: 1;
                        transform: scale(1.1);
                    }
                    .promo-content h3 {
                        font-size: 1.2rem;
                        font-weight: bold;
                        margin-bottom: 10px;
                        color: #333;
                    }
                    .promo-content p {
                        font-size: 0.9rem;
                        color: #666;
                        margin-bottom: 15px;
                    }

                    /* Empty State Styles */
                    .empty-state-card {
                        background: white;
                        padding: 60px 20px;
                        border-radius: 20px;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
                        border: 1px solid #eee;
                        max-width: 600px;
                        margin: 0 auto;
                    }
                    .empty-icon {
                        font-size: 5rem;
                        color: #E30613;
                        margin-bottom: 25px;
                        text-shadow: 0 5px 15px rgba(227, 6, 19, 0.2);
                        animation: float 3s ease-in-out infinite;
                    }
                    .empty-state-card h3 {
                        font-size: 2rem;
                        font-weight: 800;
                        margin-bottom: 15px;
                        color: #333;
                    }
                    .empty-state-card p {
                        font-size: 1.1rem;
                        color: #666;
                        margin-bottom: 30px;
                        line-height: 1.6;
                    }
                    .pulse-button {
                        background: #E30613;
                        border: none;
                        padding: 15px 40px;
                        border-radius: 50px;
                        font-weight: bold;
                        font-size: 1.1rem;
                        box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
                        transition: all 0.3s ease;
                    }
                    .pulse-button:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 25px rgba(227, 6, 19, 0.4);
                        background: #c90511;
                    }
                    @keyframes float {
                        0% { transform: translateY(0px); }
                        50% { transform: translateY(-10px); }
                        100% { transform: translateY(0px); }
                    }
                    
                    /* Ensure grid responsiveness for promo card */
                    .codes-grid-wrapper {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                        gap: 20px;
                    }
                    /* Inherit card styles if possible, otherwise rely on local */
                </style>

            </div>
        </div>
    </div>
</div>
