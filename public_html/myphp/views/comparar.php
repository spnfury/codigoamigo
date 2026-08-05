<?php
/**
 * Vista para Comparar Marcas (Comparativa automática)
 * Variables disponibles: $marca_a, $marca_b, $codigos_a, $codigos_b, $title, $description
 * URL: /comparar/{marca_a}-vs-{marca_b}
 */

// Extraer datos útiles de cada marca
$nombre_a = $marca_a['nombre'] ?? ucfirst($marca_a['nombre_clave'] ?? '');
$nombre_b = $marca_b['nombre'] ?? ucfirst($marca_b['nombre_clave'] ?? '');
$slug_a = $marca_a['nombre_clave'] ?? '';
$slug_b = $marca_b['nombre_clave'] ?? '';
$img_a = $marca_a['imagen'] ?? '';
$img_b = $marca_b['imagen'] ?? '';
$desc_a = $marca_a['descripcion'] ?? $marca_a['descripción'] ?? 'Códigos disponibles para ' . $nombre_a;
$desc_b = $marca_b['descripcion'] ?? $marca_b['descripción'] ?? 'Códigos disponibles para ' . $nombre_b;
$cat_a = $marca_a['categoria'] ?? 'General';
$cat_b = $marca_b['categoria'] ?? 'General';

// Calcular stats
$num_codigos_a = count($codigos_a);
$num_codigos_b = count($codigos_b);
$max_benefit_a = 0;
$max_benefit_b = 0;
$vip_count_a = 0;
$vip_count_b = 0;

foreach ($codigos_a as $c) {
    $b = isset($c['num_beneficio']) ? (float)$c['num_beneficio'] : 0;
    if ($b > $max_benefit_a) $max_benefit_a = $b;
    if (!empty($c['destacado']) && $c['destacado'] != 0) $vip_count_a++;
}
foreach ($codigos_b as $c) {
    $b = isset($c['num_beneficio']) ? (float)$c['num_beneficio'] : 0;
    if ($b > $max_benefit_b) $max_benefit_b = $b;
    if (!empty($c['destacado']) && $c['destacado'] != 0) $vip_count_b++;
}

// Determinar el "ganador" de cada categoría
$winner_codes = $num_codigos_a > $num_codigos_b ? 'a' : ($num_codigos_b > $num_codigos_a ? 'b' : 'tie');
$winner_benefit = $max_benefit_a > $max_benefit_b ? 'a' : ($max_benefit_b > $max_benefit_a ? 'b' : 'tie');

$year = date('Y');
$month_es = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$current_month = $month_es[date('n')-1];
?>

<?php
// ============================================================
// Schema.org Structured Data
// ============================================================
$compare_url = 'https://www.codigoamigo.com/comparar/' . $slug_a . '-vs-' . $slug_b;

// Article Schema
$schema_article = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $title,
    'description' => $description,
    'url' => $compare_url,
    'author' => ['@type' => 'Organization', 'name' => 'CodigoAmigo', 'url' => 'https://www.codigoamigo.com'],
    'publisher' => ['@type' => 'Organization', 'name' => 'CodigoAmigo', 'logo' => ['@type' => 'ImageObject', 'url' => 'https://www.codigoamigo.com/images/logo.png']],
    'datePublished' => date('Y-m-d'),
    'dateModified' => date('Y-m-d')
];
echo '<script type="application/ld+json">' . json_encode($schema_article, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

// BreadcrumbList
$schema_bc = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => 'https://www.codigoamigo.com'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Comparar', 'item' => 'https://www.codigoamigo.com/comparar'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => "$nombre_a vs $nombre_b", 'item' => $compare_url]
    ]
];
echo '<script type="application/ld+json">' . json_encode($schema_bc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

// FAQPage Schema (auto-generated)
$faqs = [
    ['q' => "¿Qué es mejor, $nombre_a o $nombre_b?", 'a' => "Ambas tienen ventajas. $nombre_a ofrece un beneficio máximo de {$max_benefit_a}€ con {$num_codigos_a} códigos activos, mientras que $nombre_b ofrece hasta {$max_benefit_b}€ con {$num_codigos_b} códigos. La mejor opción depende de tus necesidades."],
    ['q' => "¿Tienen código de invitación $nombre_a y $nombre_b?", 'a' => "Sí, en CodigoAmigo encontrarás códigos de invitación verificados para ambas. $nombre_a tiene {$num_codigos_a} códigos activos y $nombre_b tiene {$num_codigos_b}."],
    ['q' => "¿Puedo usar códigos de $nombre_a y $nombre_b a la vez?", 'a' => "Sí, puedes registrarte en ambas plataformas y usar un código de invitación en cada una para obtener el máximo beneficio."],
    ['q' => "¿Cuál ofrece más descuento, $nombre_a o $nombre_b?", 'a' => ($max_benefit_a >= $max_benefit_b ? "$nombre_a ofrece hasta {$max_benefit_a}€, frente a los {$max_benefit_b}€ de $nombre_b." : "$nombre_b ofrece hasta {$max_benefit_b}€, frente a los {$max_benefit_a}€ de $nombre_a.")]
];
$faq_schema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
foreach ($faqs as $f) {
    $faq_schema['mainEntity'][] = ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]];
}
echo '<script type="application/ld+json">' . json_encode($faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
?>

<style>
/* Comparison Page Styles */
.compare-container { max-width: 1100px; margin: 0 auto; padding: 20px 15px; }

.compare-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 20px;
    padding: 50px 30px;
    text-align: center;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
}
.compare-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(227,6,19,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.compare-hero h1 {
    font-size: 2.2rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 15px;
    position: relative;
}
.compare-hero .lead {
    color: rgba(255,255,255,0.7);
    font-size: 1.1rem;
    max-width: 700px;
    margin: 0 auto 30px;
    position: relative;
}
.compare-hero-brands {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 30px;
    position: relative;
}
.compare-brand-logo {
    background: white;
    border-radius: 20px;
    padding: 20px;
    width: 140px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    transition: transform 0.3s ease;
}
.compare-brand-logo:hover { transform: scale(1.05); }
.compare-brand-logo img { max-width: 110px; max-height: 65px; object-fit: contain; }
.compare-vs-badge {
    background: linear-gradient(135deg, #E30613, #ff4757);
    color: white;
    font-weight: 800;
    font-size: 1.2rem;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 5px 20px rgba(227,6,19,0.4);
    flex-shrink: 0;
}
.compare-update {
    color: rgba(255,255,255,0.5);
    font-size: 0.85rem;
    margin-top: 20px;
    position: relative;
}

/* Stats Table */
.compare-table {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 30px;
    border: 1px solid rgba(255,255,255,0.08);
}
.compare-row {
    display: grid;
    grid-template-columns: 1fr 200px 1fr;
    align-items: center;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.compare-row:last-child { border-bottom: none; }
.compare-row-header {
    background: rgba(227,6,19,0.1);
    font-weight: 700;
}
.compare-cell {
    padding: 18px 20px;
    text-align: center;
    color: #e5e7eb;
    font-size: 1rem;
}
.compare-cell-label {
    text-align: center;
    color: rgba(255,255,255,0.6);
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.compare-cell-header {
    font-weight: 800;
    font-size: 1.1rem;
    color: #fff;
}
.compare-cell-winner {
    color: #4ade80;
    font-weight: 700;
}
.compare-cell .winner-badge {
    display: inline-block;
    background: rgba(74,222,128,0.15);
    color: #4ade80;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-left: 8px;
}
.compare-cell-value {
    font-size: 1.3rem;
    font-weight: 700;
}

/* Action buttons */
.compare-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 40px;
}
.compare-action-card {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
}
.compare-action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.compare-action-brand { font-size: 1.3rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
.compare-action-benefit { color: #4ade80; font-size: 1.1rem; font-weight: 600; margin-bottom: 15px; }
.compare-action-btn {
    display: inline-block;
    padding: 14px 30px;
    border-radius: 14px;
    font-weight: 700;
    font-size: 1rem;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}
.compare-action-btn-primary {
    background: linear-gradient(135deg, #E30613, #ff4757);
    color: white;
    box-shadow: 0 6px 20px rgba(227,6,19,0.3);
}
.compare-action-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(227,6,19,0.4); color: white; text-decoration: none; }

/* FAQ section */
.compare-faq { margin-bottom: 40px; }
.compare-faq h2 {
    font-size: 1.8rem;
    font-weight: 800;
    color: #fff;
    text-align: center;
    margin-bottom: 30px;
}
.compare-faq-item {
    background: linear-gradient(135deg, rgba(26,26,46,0.8), rgba(22,33,62,0.8));
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    margin-bottom: 12px;
    overflow: hidden;
}
.compare-faq-question {
    padding: 18px 20px;
    color: #fff;
    font-weight: 600;
    font-size: 1.05rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.3s;
}
.compare-faq-question:hover { background: rgba(255,255,255,0.03); }
.compare-faq-question i { color: rgba(255,255,255,0.4); transition: transform 0.3s; }
.compare-faq-answer {
    padding: 0 20px 18px;
    color: rgba(255,255,255,0.7);
    line-height: 1.7;
    display: none;
}
.compare-faq-item.active .compare-faq-answer { display: block; }
.compare-faq-item.active .compare-faq-question i { transform: rotate(180deg); }

/* Related comparisons */
.compare-related { margin-bottom: 40px; }
.compare-related h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    text-align: center;
    margin-bottom: 25px;
}
.compare-related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}
.compare-related-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    text-decoration: none;
    color: #e5e7eb;
    transition: all 0.3s ease;
}
.compare-related-card:hover {
    background: rgba(255,255,255,0.06);
    transform: translateY(-2px);
    color: #fff;
    text-decoration: none;
}
.compare-related-card img { width: 36px; height: 36px; border-radius: 8px; object-fit: contain; background: white; padding: 4px; }
.compare-related-card span { font-weight: 600; }
.compare-related-vs { color: rgba(255,255,255,0.4); font-size: 0.8rem; margin: 0 4px; }

/* Responsive */
@media (max-width: 768px) {
    .compare-hero h1 { font-size: 1.6rem; }
    .compare-hero-brands { gap: 15px; }
    .compare-brand-logo { width: 100px; height: 70px; padding: 12px; }
    .compare-brand-logo img { max-width: 80px; max-height: 50px; }
    .compare-vs-badge { width: 40px; height: 40px; font-size: 1rem; }
    .compare-row { grid-template-columns: 1fr 120px 1fr; }
    .compare-cell { padding: 12px 10px; font-size: 0.9rem; }
    .compare-actions { grid-template-columns: 1fr; }
    .compare-related-grid { grid-template-columns: 1fr; }
}
</style>

<div class="compare-container">
    <!-- Hero -->
    <div class="compare-hero">
        <h1>Código amigo <?php echo htmlspecialchars($nombre_a); ?> vs <?php echo htmlspecialchars($nombre_b); ?></h1>
        <p class="lead">Comparativa actualizada de códigos de descuento y beneficios entre <?php echo htmlspecialchars($nombre_a); ?> y <?php echo htmlspecialchars($nombre_b); ?> para <?php echo $current_month . ' ' . $year; ?></p>
        
        <div class="compare-hero-brands">
            <a href="/de-<?php echo htmlspecialchars($slug_a); ?>" class="compare-brand-logo">
                <?php if ($img_a): ?>
                    <img src="<?php echo htmlspecialchars($img_a); ?>" alt="<?php echo htmlspecialchars($nombre_a); ?>">
                <?php else: ?>
                    <span style="font-size:1.5rem;font-weight:700;color:#333;"><?php echo htmlspecialchars($nombre_a); ?></span>
                <?php endif; ?>
            </a>
            
            <div class="compare-vs-badge">VS</div>
            
            <a href="/de-<?php echo htmlspecialchars($slug_b); ?>" class="compare-brand-logo">
                <?php if ($img_b): ?>
                    <img src="<?php echo htmlspecialchars($img_b); ?>" alt="<?php echo htmlspecialchars($nombre_b); ?>">
                <?php else: ?>
                    <span style="font-size:1.5rem;font-weight:700;color:#333;"><?php echo htmlspecialchars($nombre_b); ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <p class="compare-update"><i class="fas fa-sync-alt"></i> Última actualización: <?php echo date('d/m/Y'); ?></p>
    </div>

    <!-- Comparison Table -->
    <div class="compare-table">
        <!-- Header -->
        <div class="compare-row compare-row-header">
            <div class="compare-cell compare-cell-header"><?php echo htmlspecialchars($nombre_a); ?></div>
            <div class="compare-cell compare-cell-label">Criterio</div>
            <div class="compare-cell compare-cell-header"><?php echo htmlspecialchars($nombre_b); ?></div>
        </div>
        
        <!-- Códigos activos -->
        <div class="compare-row">
            <div class="compare-cell compare-cell-value <?php echo $winner_codes === 'a' ? 'compare-cell-winner' : ''; ?>">
                <?php echo $num_codigos_a; ?>
                <?php if ($winner_codes === 'a'): ?><span class="winner-badge">✓ Más</span><?php endif; ?>
            </div>
            <div class="compare-cell compare-cell-label"><i class="fas fa-ticket-alt"></i> Códigos activos</div>
            <div class="compare-cell compare-cell-value <?php echo $winner_codes === 'b' ? 'compare-cell-winner' : ''; ?>">
                <?php echo $num_codigos_b; ?>
                <?php if ($winner_codes === 'b'): ?><span class="winner-badge">✓ Más</span><?php endif; ?>
            </div>
        </div>
        
        <!-- Beneficio máximo -->
        <div class="compare-row">
            <div class="compare-cell compare-cell-value <?php echo $winner_benefit === 'a' ? 'compare-cell-winner' : ''; ?>">
                <?php echo $max_benefit_a; ?>€
                <?php if ($winner_benefit === 'a'): ?><span class="winner-badge">✓ Mayor</span><?php endif; ?>
            </div>
            <div class="compare-cell compare-cell-label"><i class="fas fa-coins"></i> Beneficio máximo</div>
            <div class="compare-cell compare-cell-value <?php echo $winner_benefit === 'b' ? 'compare-cell-winner' : ''; ?>">
                <?php echo $max_benefit_b; ?>€
                <?php if ($winner_benefit === 'b'): ?><span class="winner-badge">✓ Mayor</span><?php endif; ?>
            </div>
        </div>
        
        <!-- Códigos destacados -->
        <div class="compare-row">
            <div class="compare-cell compare-cell-value"><?php echo $vip_count_a; ?></div>
            <div class="compare-cell compare-cell-label"><i class="fas fa-star"></i> Códigos destacados</div>
            <div class="compare-cell compare-cell-value"><?php echo $vip_count_b; ?></div>
        </div>
        
        <!-- Categoría -->
        <div class="compare-row">
            <div class="compare-cell"><?php echo htmlspecialchars($cat_a); ?></div>
            <div class="compare-cell compare-cell-label"><i class="fas fa-tag"></i> Categoría</div>
            <div class="compare-cell"><?php echo htmlspecialchars($cat_b); ?></div>
        </div>
    </div>

    <?php
    // ============================================
    // Load brand features data
    // ============================================
    include_once __DIR__ . '/../brand_features_data.php';
    $features_a = get_brand_features($slug_a);
    $features_b = get_brand_features($slug_b);
    $has_features = ($features_a || $features_b);
    
    if ($has_features):
        // Get category-specific labels
        $cat_key = $features_a['categoria_clave'] ?? ($features_b['categoria_clave'] ?? '');
        $feature_labels = get_category_comparison_labels($cat_key);
    ?>
    
    <!-- Product Feature Comparison -->
    <h2 style="font-size:1.6rem;font-weight:800;color:#fff;text-align:center;margin:40px 0 25px;">
        <i class="fas fa-chart-bar" style="color:#E30613;"></i> Comparativa detallada del producto
    </h2>
    
    <div class="compare-table">
        <div class="compare-row compare-row-header">
            <div class="compare-cell compare-cell-header"><?php echo htmlspecialchars($nombre_a); ?></div>
            <div class="compare-cell compare-cell-label">Característica</div>
            <div class="compare-cell compare-cell-header"><?php echo htmlspecialchars($nombre_b); ?></div>
        </div>
        
        <?php foreach ($feature_labels as $fl): 
            $val_a = $features_a['features'][$fl['key']] ?? '—';
            $val_b = $features_b['features'][$fl['key']] ?? '—';
        ?>
            <div class="compare-row">
                <div class="compare-cell"><?php echo htmlspecialchars($val_a); ?></div>
                <div class="compare-cell compare-cell-label"><i class="<?php echo $fl['icon']; ?>"></i> <?php echo htmlspecialchars($fl['label']); ?></div>
                <div class="compare-cell"><?php echo htmlspecialchars($val_b); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pros / Cons / Rating Cards -->
    <h2 style="font-size:1.6rem;font-weight:800;color:#fff;text-align:center;margin:40px 0 25px;">
        <i class="fas fa-balance-scale" style="color:#E30613;"></i> Ventajas y desventajas
    </h2>
    
    <div class="compare-proscons-grid">
        <?php foreach ([['data' => $features_a, 'name' => $nombre_a, 'slug' => $slug_a, 'img' => $img_a], ['data' => $features_b, 'name' => $nombre_b, 'slug' => $slug_b, 'img' => $img_b]] as $brand): 
            $fd = $brand['data'];
            if (!$fd) continue;
            $rating = $fd['rating'] ?? 0;
            $full_stars = floor($rating);
            $half_star = ($rating - $full_stars) >= 0.5;
        ?>
        <div class="compare-proscons-card">
            <div class="proscons-header">
                <?php if ($brand['img']): ?>
                    <img src="<?php echo htmlspecialchars($brand['img']); ?>" alt="<?php echo htmlspecialchars($brand['name']); ?>" class="proscons-logo">
                <?php endif; ?>
                <div>
                    <div class="proscons-brand-name"><?php echo htmlspecialchars($brand['name']); ?></div>
                    <div class="proscons-rating">
                        <?php for ($i = 0; $i < $full_stars; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                        <?php if ($half_star): ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
                        <?php for ($i = $full_stars + ($half_star ? 1 : 0); $i < 5; $i++): ?><i class="far fa-star"></i><?php endfor; ?>
                        <span><?php echo $rating; ?>/5</span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($fd['ventajas'])): ?>
            <div class="proscons-section">
                <div class="proscons-label proscons-pros"><i class="fas fa-check-circle"></i> Ventajas</div>
                <ul class="proscons-list">
                    <?php foreach ($fd['ventajas'] as $v): ?>
                        <li class="proscons-pro"><?php echo htmlspecialchars($v); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($fd['desventajas'])): ?>
            <div class="proscons-section">
                <div class="proscons-label proscons-cons"><i class="fas fa-times-circle"></i> Desventajas</div>
                <ul class="proscons-list">
                    <?php foreach ($fd['desventajas'] as $d): ?>
                        <li class="proscons-con"><?php echo htmlspecialchars($d); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($fd['ideal_para'])): ?>
            <div class="proscons-ideal">
                <i class="fas fa-user-check"></i>
                <strong>Ideal para:</strong> <?php echo htmlspecialchars($fd['ideal_para']); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <style>
    .compare-proscons-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
    .compare-proscons-card {
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px;
        padding: 25px;
        transition: all 0.3s ease;
    }
    .compare-proscons-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.3); }
    .proscons-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.08); }
    .proscons-logo { width: 50px; height: 50px; border-radius: 12px; object-fit: contain; background: white; padding: 6px; }
    .proscons-brand-name { font-size: 1.2rem; font-weight: 800; color: #fff; }
    .proscons-rating { display: flex; align-items: center; gap: 4px; margin-top: 4px; }
    .proscons-rating i { color: #fbbf24; font-size: 0.85rem; }
    .proscons-rating span { color: rgba(255,255,255,0.6); font-size: 0.85rem; font-weight: 600; margin-left: 6px; }
    .proscons-section { margin-bottom: 15px; }
    .proscons-label { font-weight: 700; font-size: 0.9rem; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    .proscons-pros { color: #4ade80; }
    .proscons-cons { color: #f87171; }
    .proscons-list { list-style: none; padding: 0; margin: 0; }
    .proscons-list li { padding: 6px 0; color: rgba(255,255,255,0.8); font-size: 0.92rem; line-height: 1.5; padding-left: 20px; position: relative; }
    .proscons-pro::before { content: '✓'; position: absolute; left: 0; color: #4ade80; font-weight: 700; }
    .proscons-con::before { content: '✗'; position: absolute; left: 0; color: #f87171; font-weight: 700; }
    .proscons-ideal {
        margin-top: 15px;
        padding: 12px 15px;
        background: rgba(59,130,246,0.1);
        border: 1px solid rgba(59,130,246,0.2);
        border-radius: 10px;
        color: rgba(255,255,255,0.8);
        font-size: 0.9rem;
        line-height: 1.5;
    }
    .proscons-ideal i { color: #60a5fa; margin-right: 6px; }
    .proscons-ideal strong { color: #fff; }
    @media (max-width: 768px) {
        .compare-proscons-grid { grid-template-columns: 1fr; }
    }
    </style>
    
    <?php endif; // has_features ?>

    <!-- CTAs -->
    <div class="compare-actions">
        <div class="compare-action-card">
            <div class="compare-action-brand"><?php echo htmlspecialchars($nombre_a); ?></div>
            <?php if ($max_benefit_a > 0): ?>
                <div class="compare-action-benefit">Hasta <?php echo $max_benefit_a; ?>€ de beneficio</div>
            <?php else: ?>
                <div class="compare-action-benefit"><?php echo $num_codigos_a; ?> códigos disponibles</div>
            <?php endif; ?>
            <a href="/de-<?php echo htmlspecialchars($slug_a); ?>" class="compare-action-btn compare-action-btn-primary">
                <i class="fas fa-eye"></i> Ver códigos <?php echo htmlspecialchars($nombre_a); ?>
            </a>
        </div>
        <div class="compare-action-card">
            <div class="compare-action-brand"><?php echo htmlspecialchars($nombre_b); ?></div>
            <?php if ($max_benefit_b > 0): ?>
                <div class="compare-action-benefit">Hasta <?php echo $max_benefit_b; ?>€ de beneficio</div>
            <?php else: ?>
                <div class="compare-action-benefit"><?php echo $num_codigos_b; ?> códigos disponibles</div>
            <?php endif; ?>
            <a href="/de-<?php echo htmlspecialchars($slug_b); ?>" class="compare-action-btn compare-action-btn-primary">
                <i class="fas fa-eye"></i> Ver códigos <?php echo htmlspecialchars($nombre_b); ?>
            </a>
        </div>
    </div>
    
    <!-- FAQ -->
    <div class="compare-faq">
        <h2>Preguntas frecuentes</h2>
        <?php foreach ($faqs as $faq): ?>
            <div class="compare-faq-item">
                <div class="compare-faq-question" onclick="this.parentElement.classList.toggle('active')">
                    <?php echo htmlspecialchars($faq['q']); ?>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="compare-faq-answer"><?php echo htmlspecialchars($faq['a']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Related Comparisons -->
    <?php if (!empty($related_brands)): ?>
    <div class="compare-related">
        <h2>Otras comparativas populares</h2>
        <div class="compare-related-grid">
            <?php foreach (array_slice($related_brands, 0, 6) as $rel): ?>
                <?php
                $rel_slug = $rel['nombre_clave'] ?? '';
                $rel_name = $rel['nombre'] ?? ucfirst($rel_slug);
                $rel_img = $rel['imagen'] ?? '';
                // Create comparison URLs with both brands
                $pair_slugs = [$slug_a, $rel_slug];
                sort($pair_slugs);
                $compare_pair_url = '/comparar/' . $pair_slugs[0] . '-vs-' . $pair_slugs[1];
                if ($rel_slug === $slug_a || $rel_slug === $slug_b) continue;
                ?>
                <a href="<?php echo $compare_pair_url; ?>" class="compare-related-card">
                    <?php if ($rel_img): ?>
                        <img src="<?php echo htmlspecialchars($rel_img); ?>" alt="<?php echo htmlspecialchars($rel_name); ?>">
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($nombre_a); ?></span>
                    <span class="compare-related-vs">vs</span>
                    <span><?php echo htmlspecialchars($rel_name); ?></span>
                </a>
                <a href="/comparar/<?php echo htmlspecialchars($rel_slug); ?>-vs-<?php echo htmlspecialchars($slug_b); ?>" class="compare-related-card">
                    <?php if ($rel_img): ?>
                        <img src="<?php echo htmlspecialchars($rel_img); ?>" alt="<?php echo htmlspecialchars($rel_name); ?>">
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($rel_name); ?></span>
                    <span class="compare-related-vs">vs</span>
                    <span><?php echo htmlspecialchars($nombre_b); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
