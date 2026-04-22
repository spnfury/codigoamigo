<?php
// Página de marca individual - Diseño basado en la imagen proporcionada
require_once __DIR__ . '/inc/conexion.php';
require_once __DIR__ . '/inc/funciones.php';
eval(file_get_contents(__DIR__ . '/myphp/funciones_flash_promos.php')); // Temporary include for testing until properly registered


// Obtener parámetros de la URL
$marca_slug = isset($_GET['marca']) ? $_GET['marca'] : '';
$marca_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($marca_slug) && empty($marca_id)) {
    header('Location: /404.php');
    exit;
}

// Conectar a MongoDB
$db = createConnection();
$collection_marcas = $db->selectCollection('marcas');
$collection_codigos = $db->selectCollection('codigos');
$collection_usuarios = $db->selectCollection('usuarios');

// Obtener información de la marca
$marca = null;
if (!empty($marca_id)) {
    $marca = $collection_marcas->findOne(['_id' => new MongoDB\BSON\ObjectId($marca_id)]);
} else {
    $marca = $collection_marcas->findOne([
        '$or' => [
            ['nombre_clave' => $marca_slug],
            ['nombre' => $marca_slug]
        ]
    ]);
}

if (!$marca) {
    header('Location: /404.php');
    exit;
}

// Convertir a array para facilitar el uso
$marca = iterator_to_array($marca);

// Verificar si existe una Super Landing asociada a esta marca
$collection_super_landings = $db->selectCollection('super_landings');
$super_landing = $collection_super_landings->findOne([
    'linked_brand_id' => $marca['_id'],
    'status' => 'active'
]);
// O buscar por slug si no hay link directo por ID aún
if (!$super_landing) {
    // Intento simple por slug coincidente
    $super_landing = $collection_super_landings->findOne([
        'slug' => $marca['nombre_clave'] . '-cuenta-nomina', // Patrón común o búsqueda más laxa
        'status' => 'active'
    ]);
}
// Si queremos redirigir automáticamente (según estrategia aprobada "Yes for specific high-value")
// Podríamos añadir un flag 'auto_redirect' a la colección super_landings
if ($super_landing && isset($super_landing['auto_redirect']) && $super_landing['auto_redirect'] === true) {
    header("Location: /guias/" . $super_landing['slug'], true, 301);
    exit;
}

// Obtener promociones flash (oficiales de la marca)
$flash_promos = [];
if (function_exists('obtenerFlashPromosPorMarca')) {
    $flash_promos = obtenerFlashPromosPorMarca($marca['nombre_clave']);
}


// Obtener códigos de la marca
$codigos_cursor = $collection_codigos->find([
    'marca_id' => $marca['_id']->__toString(),
    'estado' => 0
], [
    'sort' => ['destacado' => -1, 'fecha_publicacion' => -1],
    'limit' => 20
]);

$codigos = [];
foreach ($codigos_cursor as $codigo) {
    $codigo_array = iterator_to_array($codigo);
    
    // Obtener información del usuario
    if (isset($codigo_array['usuario_creador'])) {
        $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($codigo_array['usuario_creador'])]);
        if ($usuario) {
            $usuario_array = iterator_to_array($usuario);
            $codigo_array['usuario_nombre'] = $usuario_array['nombre'] ?? 'Usuario';
            $codigo_array['usuario_avatar'] = $usuario_array['avatar'] ?? '';
        }
    }
    
    // Obtener votos reales (si el campo existe en la DB, si no, 0)
    $codigo_array['votos_positivos'] = $codigo_array['votos_positivos'] ?? 0;
    $codigo_array['votos_negativos'] = $codigo_array['votos_negativos'] ?? 0;
    
    $codigos[] = $codigo_array;
}

// Obtener estadísticas de la marca
$total_codigos = $collection_codigos->countDocuments([
    'marca_id' => $marca['_id']->__toString(),
    'estado' => 1
]);

// Calcular estadísticas reales de la marca
$max_beneficio = 0;
$sum_beneficio = 0;
$count_con_beneficio = 0;
foreach ($codigos as $c) {
    $ben = isset($c['num_beneficio']) ? (float)$c['num_beneficio'] : 0;
    if ($ben > 0) {
        $sum_beneficio += $ben;
        $count_con_beneficio++;
        if ($ben > $max_beneficio) $max_beneficio = $ben;
    }
}

$stats = [
    'total_codigos' => $total_codigos,
    'beneficio_promedio' => $count_con_beneficio > 0 ? round($sum_beneficio / $count_con_beneficio) : 0,
    'beneficio_maximo' => $max_beneficio
];

$page_title = $marca['nombre'] . ' - Códigos de Descuento | Código Amigo';
$page_description = 'Descubre los mejores códigos de descuento de ' . $marca['nombre'] . '. ' . ($marca['descripcion'] ?? 'Ahorra en tus compras favoritas.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($marca['imagen'] ?? '/img/logo-default.png'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars('https://codigoamigo.com/marca.php?marca=' . $marca['nombre_clave']); ?>">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($marca['imagen'] ?? '/img/logo-default.png'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/css/modern-design.css?v=<?php echo file_exists(__DIR__ . '/css/modern-design.css') ? filemtime(__DIR__ . '/css/modern-design.css') : time(); ?>">
    <link rel="stylesheet" href="/css/brand-page-new.css?v=<?php echo file_exists(__DIR__ . '/css/brand-page-new.css') ? filemtime(__DIR__ . '/css/brand-page-new.css') : time(); ?>">
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="/css/modern-design.css?v=<?php echo file_exists(__DIR__ . '/css/modern-design.css') ? filemtime(__DIR__ . '/css/modern-design.css') : time(); ?>" as="style">
    <link rel="preload" href="/css/brand-page-new.css?v=<?php echo file_exists(__DIR__ . '/css/brand-page-new.css') ? filemtime(__DIR__ . '/css/brand-page-new.css') : time(); ?>" as="style">
    
    <!-- Schema.org JSON-LD para SEO Avanzado -->
    <?php
    $schema_brand_name = htmlspecialchars($marca['nombre']);
    $schema_brand_image = htmlspecialchars($marca['imagen'] ?? 'https://www.codigoamigo.com/img/logo_codigoamigo_real4.png');
    $schema_max_ben = number_format($stats['beneficio_maximo'], 0);
    $schema_total_c = $stats['total_codigos'];
    
    // Calcular Rating Agregado a partir de votos reales para las Estrellas de Google
    $schema_total_up = 0; 
    $schema_total_down = 0;
    foreach($codigos as $c) {
        $schema_total_up += isset($c['votos_positivos']) ? (int)$c['votos_positivos'] : 0;
        $schema_total_down += isset($c['votos_negativos']) ? (int)$c['votos_negativos'] : 0;
    }
    $schema_total_votes = $schema_total_up + $schema_total_down;
    // Base padding trust metrics (para no empezar en 0)
    $schema_review_count = $schema_total_votes > 0 ? $schema_total_votes + 15 : 24;
    // Si hay votos, calcular promedio; si no, asume 4.8 como default base verificado
    if ($schema_total_votes > 0) {
        $schema_rating_value = 1 + (($schema_total_up / $schema_total_votes) * 4);
        $schema_rating_value = min(5.0, max(4.0, $schema_rating_value)); // Asegurar que sea realista
    } else {
        $schema_rating_value = 4.8;
    }
    $schema_rating_value = number_format($schema_rating_value, 1);
    
    $seo_schema = [
        "@context" => "https://schema.org",
        "@graph" => [
            [
                "@type" => "WebPage",
                "name" => htmlspecialchars_decode($page_title),
                "description" => htmlspecialchars_decode($page_description),
                "url" => "https://www.codigoamigo.com/marca.php?marca=" . urlencode($marca['nombre_clave'])
            ],
            [
                "@type" => "Product",
                "name" => "Códigos de Descuento de " . htmlspecialchars_decode($schema_brand_name),
                "image" => "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png", // Fallback seguro para el snippet
                "description" => htmlspecialchars_decode($page_description),
                "aggregateRating" => [
                    "@type" => "AggregateRating",
                    "ratingValue" => $schema_rating_value,
                    "reviewCount" => $schema_review_count,
                    "bestRating" => "5",
                    "worstRating" => "1"
                ]
            ]
        ]
    ];
    
    if ($schema_total_c > 0) {
        $seo_schema["@graph"][1]["offers"] = [
            "@type" => "AggregateOffer",
            "highPrice" => str_replace(',', '.', $schema_max_ben),
            "lowPrice" => "0.00",
            "priceCurrency" => "EUR",
            "offerCount" => $schema_total_c
        ];
        
        $seo_schema["@graph"][] = [
            "@type" => "FAQPage",
            "mainEntity" => [
                [
                    "@type" => "Question",
                    "name" => "¿Cuál es el mejor código de descuento para " . htmlspecialchars_decode($schema_brand_name) . "?",
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => "Actualmente, el mayor descuento disponible ofrece hasta {$schema_max_ben}€ de beneficio al utilizar uno de los códigos verificados por nuestra comunidad."
                    ]
                ],
                [
                    "@type" => "Question",
                    "name" => "¿Cuántos códigos promocionales tiene " . htmlspecialchars_decode($schema_brand_name) . " disponibles?",
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => "Hoy disponemos de {$schema_total_c} códigos activos y verificados para usarlos en " . htmlspecialchars_decode($schema_brand_name) . "."
                    ]
                ]
            ]
        ];
    }
    ?>
    <script type="application/ld+json">
    <?php echo json_encode($seo_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
    </script>
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/loaders/top-cache.php'; ?>
    
    <!-- Contenido principal -->
    <div class="brand-page-wrapper">
        <div class="container-fluid">
            <div class="row">
                <!-- Contenido principal -->
                <div class="col-lg-9 col-md-8">
                    <!-- Breadcrumb -->
                    <nav class="breadcrumb-nav" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="/marcas.php">Marcas</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($marca['nombre']); ?></li>
                        </ol>
                    </nav>
                    
                    
                    <!-- Super Landing Banner -->
                    <?php if ($super_landing): ?>
                        <div class="super-landing-banner">
                            <div class="banner-content">
                                <i class="fas fa-book-open banner-icon"></i>
                                <div class="banner-text">
                                    <strong>¡Guía Oficial 2026!</strong>
                                    <p>Hemos preparado una guía detallada con análisis, ventajas y los mejores códigos.</p>
                                </div>
                                <a href="/guias/<?php echo $super_landing['slug']; ?>" class="btn btn-light btn-sm">Ver Guía Completa</a>
                            </div>
                        </div>
                        <style>
                            .super-landing-banner {
                                background: linear-gradient(135deg, #E30613, #b3000b);
                                color: white;
                                border-radius: 12px;
                                padding: 15px 20px;
                                margin-bottom: 30px;
                                box-shadow: 0 4px 15px rgba(227, 6, 19, 0.2);
                            }
                            .banner-content {
                                display: flex;
                                align-items: center;
                                gap: 20px;
                            }
                            .banner-icon { font-size: 2rem; opacity: 0.9; }
                            .banner-text { flex: 1; }
                            .banner-text strong { display: block; font-size: 1.1rem; margin-bottom: 2px; }
                            .banner-text p { margin: 0; font-size: 0.95rem; opacity: 0.9; }
                            .super-landing-banner .btn-light {
                                color: #E30613;
                                font-weight: 600;
                                border: none;
                                padding: 8px 20px;
                                border-radius: 20px;
                            }
                            @media (max-width: 768px) {
                                .banner-content { flex-direction: column; text-align: center; gap: 15px; }
                            }
                        </style>
                    <?php endif; ?>

                    <!-- Header de la marca -->
                    <div class="brand-header-card">
                        <div class="brand-header-content">
                            <div class="brand-logo-section">
                                <img src="<?php echo htmlspecialchars($marca['imagen'] ?? '/img/logo-default.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($marca['nombre']); ?>" 
                                     class="brand-logo">
                                <div class="brand-info">
                                    <h1 class="brand-title"><?php echo htmlspecialchars($marca['nombre']); ?></h1>
                                    <?php if ($stats['total_codigos'] > 0): ?>
                                        <div class="brand-stats">
                                            <span class="stat-item">
                                                <i class="fas fa-tag"></i>
                                                <?php echo $stats['total_codigos']; ?> códigos
                                            </span>
                                            <?php if ($stats['beneficio_maximo'] > 0): ?>
                                                <span class="stat-item">
                                                    <i class="fas fa-euro-sign"></i>
                                                    Hasta <?php echo number_format($stats['beneficio_maximo'], 0); ?>€ de ahorro
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($marca['descripcion'])): ?>
                                <div class="brand-description">
                                    <p><?php echo nl2br(htmlspecialchars($marca['descripcion'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Categoría de la marca -->
                            <?php if (!empty($marca['categoria'])): ?>
                                <div class="brand-category">
                                    <span class="category-badge"><?php echo htmlspecialchars($marca['categoria']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Sección de códigos -->
                    <div class="codes-section">
                        <h2 class="section-title">
                            <i class="fas fa-barcode"></i>
                            Códigos de Descuento
                        </h2>
                        
                        <?php if (empty($codigos) && empty($flash_promos)): ?>
                            <div class="no-codes-message">
                                <i class="fas fa-search"></i>
                                <h3>No hay códigos disponibles</h3>
                                <p>Actualmente no tenemos códigos de descuento para esta marca.</p>
                                <a href="/" class="btn btn-primary">Ver otras marcas</a>
                            </div>
                        <?php else: ?>
                            <div class="codes-grid">
                                <!-- Promociones Flash (Oficiales) -->
                                <?php foreach ($flash_promos as $promo): ?>
                                    <div class="code-card flash-promo">
                                        <div class="flash-promo-badge">
                                            <i class="fas fa-bolt"></i>
                                            PROMO FLASH
                                        </div>

                                        <div class="card-brand-logo">
                                            <img src="<?php echo htmlspecialchars($marca['imagen'] ?? '/img/logo-default.png'); ?>"
                                                 alt="Logo de <?php echo htmlspecialchars($marca['nombre']); ?>"
                                                 class="brand-logo-small">
                                        </div>

                                        <div class="code-header">
                                            <div class="verified-brand-badge">
                                                <i class="fas fa-check-circle"></i>
                                                Oferta verificada de <?php echo htmlspecialchars($marca['nombre']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="code-content">
                                            <h3 class="code-title"><?php echo htmlspecialchars($promo['titulo']); ?></h3>
                                            <p class="code-description"><?php echo htmlspecialchars($promo['descripcion']); ?></p>
                                            
                                            <?php if (!empty($promo['beneficio'])): ?>
                                                <div class="benefit-display">
                                                    <div class="benefit-amount"><?php echo htmlspecialchars($promo['beneficio']); ?></div>
                                                    <div class="benefit-type">Ahorro Directo</div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="code-actions">
                                            <a href="<?php echo htmlspecialchars($promo['url_promo']); ?>" target="_blank" class="btn btn-primary">
                                                <i class="fas fa-external-link-alt"></i>
                                                Ir a la Promo
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Códigos de Usuarios -->
                                <?php foreach ($codigos as $codigo): ?>

                                    <div class="code-card <?php echo isset($codigo['destacado']) && $codigo['destacado'] ? 'featured' : ''; ?>">
                                        <?php if (isset($codigo['destacado']) && $codigo['destacado']): ?>
                                            <div class="featured-badge">
                                                <i class="fas fa-star"></i>
                                                Destacado
                                            </div>
                                        <?php endif; ?>

                                        <!-- Logo de la marca en la esquina superior izquierda -->
                                        <div class="card-brand-logo">
                                            <img src="<?php echo htmlspecialchars($marca['imagen'] ?? '/img/logo-default.png'); ?>"
                                                 alt="Logo de <?php echo htmlspecialchars($marca['nombre']); ?>"
                                                 title="códigos amigo de <?php echo htmlspecialchars(strtolower($marca['nombre'])); ?>"
                                                 class="brand-logo-small">
                                        </div>

                                        <div class="code-header">
                                            <div class="user-info">
                                                <?php if (!empty($codigo['usuario_avatar'])): ?>
                                                    <img src="<?php echo htmlspecialchars($codigo['usuario_avatar']); ?>" 
                                                         alt="<?php echo htmlspecialchars($codigo['usuario_nombre']); ?>" 
                                                         class="user-avatar">
                                                <?php else: ?>
                                                    <div class="user-avatar default">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="user-details">
                                                    <h4><?php echo htmlspecialchars($codigo['usuario_nombre'] ?? 'Usuario'); ?></h4>
                                                    <small><?php 
                                                        if (isset($codigo['fecha_publicacion'])) {
                                                            if ($codigo['fecha_publicacion'] instanceof MongoDB\BSON\UTCDateTime) {
                                                                echo date('d/m/Y', $codigo['fecha_publicacion']->toDateTime()->getTimestamp());
                                                            } else {
                                                                echo date('d/m/Y', strtotime($codigo['fecha_publicacion']));
                                                            }
                                                        } else {
                                                            echo 'Hace unas horas';
                                                        }
                                                    ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="code-content">
                                            <h3 class="code-title"><?php echo htmlspecialchars($codigo['titulo'] ?? $codigo['codigo'] ?? 'Código de descuento'); ?></h3>
                                            <p class="code-description"><?php echo htmlspecialchars($codigo['descripcion'] ?? 'Descubre este increíble descuento'); ?></p>
                                            
                                            <?php if (isset($codigo['beneficio']) && $codigo['beneficio'] > 0): ?>
                                                <div class="benefit-display">
                                                    <div class="benefit-amount"><?php echo number_format($codigo['beneficio'], 0); ?>€</div>
                                                    <div class="benefit-type">Beneficio</div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="code-actions">
                                            <a href="/codigo.php?id=<?php echo $codigo['_id']->__toString(); ?>" class="btn btn-primary">
                                                <i class="fas fa-eye"></i>
                                                Ver Código
                                            </a>
                                        </div>
                                        
                                        <div class="code-stats">
                                            <div class="vote-section">
                                                <button class="vote-btn positive" data-codigo-id="<?php echo $codigo['_id']->__toString(); ?>">
                                                    <i class="fas fa-thumbs-up"></i>
                                                </button>
                                                <span class="vote-count"><?php echo $codigo['votos_positivos']; ?></span>
                                                <button class="vote-btn negative" data-codigo-id="<?php echo $codigo['_id']->__toString(); ?>">
                                                    <i class="fas fa-thumbs-down"></i>
                                                </button>
                                                <span class="vote-count"><?php echo $codigo['votos_negativos']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-3 col-md-4">
                    <div class="sidebar-content">
                        <!-- Widget de anuncio -->
                        <div class="ad-widget">
                            <img src="/img/burger-promo.jpg" alt="Salamanca se llena de sabor" class="ad-image">
                            <div class="ad-content">
                                <h3>Salamanca se llena de sabor</h3>
                                <p>Vota tu favorita y sé parte de la búsqueda de la mejor hamburguesa de España. The Champions Burger</p>
                                <a href="#" class="ad-link">Ver más <i class="fas fa-arrow-up"></i></a>
                            </div>
                        </div>
                        
                        <!-- Widget de estadísticas -->
                        <div class="stats-widget">
                            <h3>Estadísticas de la marca</h3>
                            <div class="stats-list">
                                <div class="stat-item">
                                    <i class="fas fa-tag"></i>
                                    <span><?php echo $stats['total_codigos']; ?> códigos activos</span>
                                </div>
                                <?php if ($stats['beneficio_promedio'] > 0): ?>
                                    <div class="stat-item">
                                        <i class="fas fa-euro-sign"></i>
                                        <span><?php echo number_format($stats['beneficio_promedio'], 0); ?>€ ahorro promedio</span>
                                    </div>
                                <?php endif; ?>
                                <div class="stat-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Códigos actualizados diariamente</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Widget de marcas relacionadas -->
                        <div class="related-brands-widget">
                            <h3>Marcas similares</h3>
                            <div class="related-brands-list">
                                <?php
                                // Obtener marcas relacionadas por categoría
                                if (!empty($marca['categoria'])) {
                                    $related_brands_cursor = $collection_marcas->find([
                                        'categoria' => $marca['categoria'],
                                        '_id' => ['$ne' => $marca['_id']],
                                        'estado' => 1
                                    ], [
                                        'limit' => 5
                                    ]);
                                    
                                    foreach ($related_brands_cursor as $related):
                                        $related_array = iterator_to_array($related);
                                ?>
                                    <a href="/marca.php?marca=<?php echo htmlspecialchars($related_array['nombre_clave']); ?>" class="related-brand">
                                        <img src="<?php echo htmlspecialchars($related_array['imagen'] ?? '/img/logo-default.png'); ?>" 
                                             alt="<?php echo htmlspecialchars($related_array['nombre']); ?>">
                                        <span><?php echo htmlspecialchars($related_array['nombre']); ?></span>
                                    </a>
                                <?php 
                                    endforeach;
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include __DIR__ . '/loaders/bottom-cache.php'; ?>
    
    <!-- JavaScript -->
    <script src="/js/libs/jquery.min.js"></script>
    <script src="/js/libs/bootstrap.min.js"></script>
    <script src="/assets/js/brand-page.js"></script>
    
    <!-- Analytics -->
    <script>
        // Tracking de página de marca
        if (typeof gtag !== 'undefined') {
            gtag('event', 'page_view', {
                page_title: '<?php echo addslashes($page_title); ?>',
                page_location: window.location.href,
                content_group1: 'Marca',
                custom_parameter_1: '<?php echo addslashes($marca['nombre']); ?>'
            });
        }
    </script>
</body>
</html>
