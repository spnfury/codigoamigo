<?php
/**
 * Ficha individual de un código de descuento.
 * URL: /codigo/{marca}-{shortId}
 * 
 * Variables disponibles desde la ruta (app.php):
 *   $ficha_codigo       - Array con datos del código
 *   $ficha_marca        - Objeto/array con datos de la marca
 *   $ficha_publicador   - Array con datos del usuario publicador
 *   $ficha_beneficio_text - String con el beneficio formateado (ej: "10€", "20%")
 *   $title, $description, etc. - Variables SEO
 */

// Header
get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);

// Funciones necesarias
include_once __DIR__ . '/../myphp/funciones_modern.php';

// Variables de la ficha
$codigo = $ficha_codigo;
$marca = $ficha_marca;
$publicador = $ficha_publicador;
$beneficio_text = $ficha_beneficio_text;

$nombre_marca = $marca['nombre'] ?? ucfirst($codigo['marca']);
$imagen_marca = $marca['imagen'] ?? '/img/logo_codigoamigo.png';
$marca_clave = $marca['nombre_clave'] ?? $codigo['marca'];

// Avatar del publicador
$pub_username = htmlspecialchars($publicador['username'] ?? 'Usuario');
$pub_img = $publicador['img'] ?? '/img/user-default.png';
$pub_user_id = isset($publicador['_id']) ? (string)$publicador['_id'] : '';
$es_vip = !empty($publicador['is_vip']) || !empty($publicador['vip']) || !empty($publicador['suscripcion_vip']);
$es_premium = !empty($publicador['pro_user']);

// Código value
$codigo_valor = $codigo['codigo'] ?? '';
$es_url = (strpos($codigo_valor, 'http') !== false);
$codigo_simple = '';
if ($es_url && !empty($codigo['codigo_simple'])) {
    $codigo_simple = $codigo['codigo_simple'];
} elseif ($es_url) {
    if (strpos($codigo_valor, '=') !== false) {
        $codigo_simple = substr($codigo_valor, strrpos($codigo_valor, '=') + 1);
    } else {
        $codigo_simple = substr($codigo_valor, strrpos($codigo_valor, '/') + 1);
    }
}

// Descripción
$desc = htmlspecialchars($codigo['descripcion'] ?? '');

// Fecha
$fecha_pub = '';
if (!empty($codigo['fecha_publicacion'])) {
    if (function_exists('formatDateAgoLarge')) {
        $fecha_pub = formatDateAgoLarge($codigo['fecha_publicacion']);
    }
}

// Vistas
$total_clicks = $codigo['totalclicks'] ?? 0;

// Valoraciones
$num_valoraciones = $codigo['num_valoraciones'] ?? 0;
$valoracion_positiva = $codigo['valoracion_positiva'] ?? 0;
$valoracion_negativa = $codigo['valoracion_negativa'] ?? 0;

// Destacado
$es_destacado = !empty($codigo['destacado']) && $codigo['destacado'] > 0;
$es_super = isset($codigo['tipo_destacado']) && $codigo['tipo_destacado'] === 'super';

// Trust score estrellas
$stars = 0;
if (function_exists('getBrandCodesWithScores')) {
    try {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_trust_score.php';
        $brand_codes = getBrandCodesWithScores($marca_clave);
        foreach ($brand_codes as $bc) {
            if ($bc['codigo_id'] === (string)$codigo['_id']) {
                $stars = $bc['trust_stars'] ?? 0;
                break;
            }
        }
    } catch (Throwable $e) {
        // Silently ignore
    }
}

// URL de la ficha para compartir
$share_url = 'https://www.codigoamigo.com/codigo/' . strtolower($marca_clave) . '-' . substr((string)$codigo['_id'], -8);
$share_title = urlencode("Código de descuento $nombre_marca" . ($beneficio_text ? " – $beneficio_text" : ""));

// Schema JSON-LD
$schema_offer = [
    '@context' => 'https://schema.org',
    '@type' => 'Offer',
    'name' => "Código de descuento " . $nombre_marca,
    'description' => $desc,
    'price' => '0',
    'priceCurrency' => 'EUR',
    'availability' => 'https://schema.org/InStock',
    'url' => $share_url,
    'seller' => [
        '@type' => 'Person',
        'name' => $pub_username
    ]
];
if (!empty($codigo['num_beneficio'])) {
    $schema_offer['discount'] = $beneficio_text;
}
?>

<style>
/* ===== Ficha Código - Dark Modern Design ===== */
.ficha-wrapper {
    max-width: 720px;
    margin: 0 auto;
    padding: 20px 15px 60px;
}

/* Breadcrumb */
.ficha-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.ficha-breadcrumb a {
    color: #60a5fa;
    text-decoration: none;
    transition: color 0.2s;
}
.ficha-breadcrumb a:hover { color: #93c5fd; }
.ficha-breadcrumb .sep { color: #4b5563; }

/* Card principal */
.ficha-card {
    background: linear-gradient(145deg, #1a1a2e, #16213e);
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.08);
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

/* Header de marca */
.ficha-brand-header {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 25px 28px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.ficha-brand-logo {
    width: 64px;
    height: 64px;
    border-radius: 14px;
    object-fit: contain;
    background: rgba(255,255,255,0.1);
    padding: 8px;
    border: 1px solid rgba(255,255,255,0.15);
    flex-shrink: 0;
}
.ficha-brand-info { flex: 1; }
.ficha-brand-name {
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 4px;
}
.ficha-brand-beneficio {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(16,185,129,0.15);
    color: #10b981;
    font-size: 1.1rem;
    font-weight: 700;
    padding: 4px 14px;
    border-radius: 20px;
    border: 1px solid rgba(16,185,129,0.3);
}
.ficha-badge-destacado {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 12px;
    margin-left: 8px;
}
.ficha-badge-destacado.gold {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #1e3a5f;
}
.ficha-badge-destacado.normal {
    background: rgba(245,158,11,0.2);
    color: #fbbf24;
}

/* Sección del código */
.ficha-code-section {
    padding: 24px 28px;
}
.ficha-code-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 10px;
}
.ficha-code-box {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(255,255,255,0.06);
    border: 2px dashed rgba(16,185,129,0.5);
    border-radius: 14px;
    padding: 16px 20px;
    transition: all 0.3s;
}
.ficha-code-box:hover {
    border-color: rgba(16,185,129,0.8);
    background: rgba(255,255,255,0.08);
}
.ficha-code-value {
    flex: 1;
    font-size: 1.3rem;
    font-weight: 800;
    color: #10b981;
    letter-spacing: 1.5px;
    font-family: 'Courier New', monospace;
    word-break: break-all;
    line-height: 1.4;
}
.ficha-code-value a {
    color: #10b981;
    text-decoration: none;
}
.ficha-code-value a:hover {
    text-decoration: underline;
}
.ficha-btn-copy {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 12px 22px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.ficha-btn-copy:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16,185,129,0.4);
}
.ficha-btn-copy.copied {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

/* Código simple (cuando hay URL + código) */
.ficha-code-simple {
    margin-top: 14px;
    padding: 12px 16px;
    background: rgba(255,255,255,0.04);
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.08);
}
.ficha-code-simple-label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 6px;
}
.ficha-code-simple-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #60a5fa;
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

/* Descripción */
.ficha-description {
    padding: 0 28px 20px;
}
.ficha-desc-text {
    font-size: 0.95rem;
    color: #9ca3af;
    line-height: 1.7;
    background: rgba(255,255,255,0.03);
    padding: 16px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.05);
    white-space: pre-wrap;
}

/* Info del publicador */
.ficha-publisher {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 28px;
    border-top: 1px solid rgba(255,255,255,0.06);
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.ficha-pub-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,0.15);
    flex-shrink: 0;
}
.ficha-pub-avatar.vip-border {
    border: 2px solid #f59e0b;
    box-shadow: 0 0 10px rgba(245,158,11,0.4);
}
.ficha-pub-info { flex: 1; }
.ficha-pub-name {
    font-weight: 600;
    color: #fff;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ficha-pub-vip-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #1e3a5f;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 8px;
}
.ficha-pub-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 4px;
    font-size: 0.8rem;
    color: #6b7280;
}
.ficha-pub-stars {
    color: #fbbf24;
    letter-spacing: 1px;
}
.ficha-pub-date i, .ficha-pub-views i {
    margin-right: 4px;
}

/* Votación */
.ficha-voting {
    padding: 18px 28px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.ficha-vote-label {
    font-size: 0.9rem;
    color: #9ca3af;
    font-weight: 500;
}
.ficha-vote-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 22px;
    padding: 8px 18px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #9ca3af;
    cursor: pointer;
    transition: all 0.25s ease;
}
.ficha-vote-btn:hover {
    transform: translateY(-1px);
}
.ficha-vote-btn.up:hover, .ficha-vote-btn.up.active {
    background: rgba(16,185,129,0.15);
    border-color: rgba(16,185,129,0.4);
    color: #10b981;
}
.ficha-vote-btn.down:hover, .ficha-vote-btn.down.active {
    background: rgba(239,68,68,0.15);
    border-color: rgba(239,68,68,0.4);
    color: #ef4444;
}
.ficha-vote-score {
    font-size: 0.85rem;
    font-weight: 700;
    color: #fff;
    min-width: 20px;
    text-align: center;
}

/* Acciones */
.ficha-actions {
    padding: 20px 28px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: center;
}
.ficha-btn-alt {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.2);
    color: #9ca3af;
    border-radius: 25px;
    padding: 10px 25px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.ficha-btn-alt:hover {
    border-color: #10b981;
    color: #10b981;
}
.ficha-btn-primary-link {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 14px 32px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 25px rgba(16,185,129,0.3);
}
.ficha-btn-primary-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(16,185,129,0.5);
    color: #fff;
}

/* Compartir */
.ficha-share {
    padding: 18px 28px 24px;
    border-top: 1px solid rgba(255,255,255,0.06);
}
.ficha-share-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 12px;
}
.ficha-share-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.ficha-share-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    color: white;
}
.ficha-share-btn:hover {
    transform: translateY(-2px);
    color: white;
}
.ficha-share-btn.whatsapp { background: #25d366; }
.ficha-share-btn.whatsapp:hover { box-shadow: 0 6px 15px rgba(37,211,102,0.4); }
.ficha-share-btn.twitter { background: #000; }
.ficha-share-btn.twitter:hover { box-shadow: 0 6px 15px rgba(0,0,0,0.4); }
.ficha-share-btn.facebook { background: #1877f2; }
.ficha-share-btn.facebook:hover { box-shadow: 0 6px 15px rgba(24,119,242,0.4); }
.ficha-share-btn.telegram { background: #0088cc; }
.ficha-share-btn.telegram:hover { box-shadow: 0 6px 15px rgba(0,136,204,0.4); }
.ficha-share-btn.copy-link { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); }
.ficha-share-btn.copy-link:hover { background: rgba(255,255,255,0.15); }

/* CTA Ver todos */
.ficha-cta-all {
    margin-top: 24px;
    text-align: center;
}
.ficha-cta-all a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #60a5fa;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    padding: 12px 24px;
    border-radius: 12px;
    background: rgba(96,165,250,0.08);
    border: 1px solid rgba(96,165,250,0.2);
    transition: all 0.3s;
}
.ficha-cta-all a:hover {
    background: rgba(96,165,250,0.15);
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 600px) {
    .ficha-wrapper { padding: 15px 10px 40px; }
    .ficha-brand-header { padding: 20px; gap: 14px; }
    .ficha-brand-logo { width: 50px; height: 50px; }
    .ficha-brand-name { font-size: 1.1rem; }
    .ficha-code-section { padding: 20px; }
    .ficha-code-box { flex-direction: column; padding: 14px; }
    .ficha-code-value { font-size: 1rem; text-align: center; }
    .ficha-btn-copy { width: 100%; justify-content: center; }
    .ficha-description { padding: 0 20px 16px; }
    .ficha-publisher { padding: 16px 20px; }
    .ficha-voting { padding: 16px 20px; }
    .ficha-actions { padding: 16px 20px; }
    .ficha-share { padding: 16px 20px 20px; }
    .ficha-share-buttons { flex-direction: column; }
    .ficha-share-btn { justify-content: center; }
}
</style>

<!-- Schema JSON-LD -->
<script type="application/ld+json">
<?php echo json_encode($schema_offer, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
</script>
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => 'https://www.codigoamigo.com'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $nombre_marca, 'item' => 'https://www.codigoamigo.com/de-' . $marca_clave],
        ['@type' => 'ListItem', 'position' => 3, 'name' => 'Código de ' . $pub_username]
    ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
</script>

<div class="main_entremedio">
<div class="ficha-wrapper">

    <!-- Breadcrumb -->
    <nav class="ficha-breadcrumb">
        <a href="/">Inicio</a>
        <span class="sep">›</span>
        <a href="/de-<?php echo htmlspecialchars($marca_clave); ?>"><?php echo htmlspecialchars($nombre_marca); ?></a>
        <span class="sep">›</span>
        <span>Código de <?php echo $pub_username; ?></span>
    </nav>

    <!-- Card principal -->
    <div class="ficha-card">

        <!-- Header de marca -->
        <div class="ficha-brand-header">
            <a href="/de-<?php echo htmlspecialchars($marca_clave); ?>">
                <img src="<?php echo htmlspecialchars($imagen_marca); ?>" alt="Logo <?php echo htmlspecialchars($nombre_marca); ?>" class="ficha-brand-logo" loading="lazy">
            </a>
            <div class="ficha-brand-info">
                <h1 class="ficha-brand-name">
                    <?php echo htmlspecialchars($nombre_marca); ?>
                    <?php if ($es_destacado): ?>
                        <span class="ficha-badge-destacado <?php echo $es_super ? 'gold' : 'normal'; ?>">
                            <i class="fas fa-<?php echo $es_super ? 'crown' : 'star'; ?>"></i>
                            <?php echo $es_super ? 'Super Destacado' : 'Destacado'; ?>
                        </span>
                    <?php endif; ?>
                </h1>
                <?php if ($beneficio_text): ?>
                    <div class="ficha-brand-beneficio">
                        <span>💰</span> <?php echo htmlspecialchars($beneficio_text); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Código -->
        <div class="ficha-code-section">
            <?php if ($es_url): ?>
                <div class="ficha-code-label">Enlace de referido</div>
            <?php else: ?>
                <div class="ficha-code-label">Tu código de descuento</div>
            <?php endif; ?>

            <div class="ficha-code-box" id="fichaCodeBox">
                <div class="ficha-code-value">
                    <?php if ($es_url): ?>
                        <a href="<?php echo htmlspecialchars($codigo_valor); ?>" target="_blank" rel="nofollow noopener"><?php echo htmlspecialchars($codigo_valor); ?></a>
                    <?php else: ?>
                        <?php echo htmlspecialchars($codigo_valor); ?>
                    <?php endif; ?>
                </div>
                <button class="ficha-btn-copy" onclick="fichaCopiaCodigo('<?php echo addslashes($codigo_valor); ?>', this)" id="btnFichaCopy">
                    <i class="fas fa-copy"></i> Copiar
                </button>
            </div>

            <?php if ($es_url && $codigo_simple): ?>
                <div class="ficha-code-simple">
                    <div class="ficha-code-simple-label">Código extraído del enlace:</div>
                    <div class="ficha-code-simple-value"><?php echo htmlspecialchars($codigo_simple); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Descripción -->
        <?php if ($desc): ?>
        <div class="ficha-description">
            <div class="ficha-desc-text"><?php echo nl2br($desc); ?></div>
        </div>
        <?php endif; ?>

        <!-- Info del publicador -->
        <div class="ficha-publisher">
            <a href="<?php echo link_usuario($publicador['username'] ?? '', $pub_user_id); ?>">
                <img src="<?php echo htmlspecialchars($pub_img); ?>" 
                     alt="<?php echo $pub_username; ?>" 
                     class="ficha-pub-avatar <?php echo $es_vip ? 'vip-border' : ''; ?>"
                     onerror="this.src='/img/user-default.png'" loading="lazy">
            </a>
            <div class="ficha-pub-info">
                <div class="ficha-pub-name">
                    <a href="<?php echo link_usuario($publicador['username'] ?? '', $pub_user_id); ?>" style="color:white;text-decoration:none;">
                        <?php echo $pub_username; ?>
                    </a>
                    <?php if ($es_vip): ?>
                        <span class="ficha-pub-vip-badge"><i class="fas fa-crown"></i> VIP</span>
                    <?php endif; ?>
                    <?php if ($es_premium): ?>
                        <span style="font-size:0.7rem;color:#10b981;">✔️ Premium</span>
                    <?php endif; ?>
                </div>
                <div class="ficha-pub-meta">
                    <?php if ($stars > 0): ?>
                        <span class="ficha-pub-stars">
                            <?php for ($s = 0; $s < $stars; $s++) echo '★'; ?>
                            <?php for ($s = $stars; $s < 5; $s++) echo '☆'; ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($fecha_pub): ?>
                        <span class="ficha-pub-date"><i class="far fa-clock"></i> <?php echo $fecha_pub; ?></span>
                    <?php endif; ?>
                    <span class="ficha-pub-views"><i class="fas fa-eye"></i> <?php echo $total_clicks; ?></span>
                </div>
            </div>
        </div>

        <!-- Votación -->
        <div class="ficha-voting">
            <span class="ficha-vote-label">¿Te ha funcionado?</span>
            <button class="ficha-vote-btn up" onclick="fichaVotar('si', '<?php echo $codigo['_id']; ?>', this)">
                <i class="fas fa-thumbs-up"></i> Sí <span id="fichaVoteSi"><?php echo $valoracion_positiva; ?></span>
            </button>
            <button class="ficha-vote-btn down" onclick="fichaVotar('no', '<?php echo $codigo['_id']; ?>', this)">
                <i class="fas fa-thumbs-down"></i> No
            </button>
            <span class="ficha-vote-score"><?php echo $num_valoraciones; ?>°</span>
        </div>

        <!-- Acciones -->
        <div class="ficha-actions">
            <a href="/de-<?php echo htmlspecialchars($marca_clave); ?>" class="ficha-btn-alt">
                <i class="fas fa-sync-alt"></i> ¿No funciona? Prueba otro
            </a>
        </div>

        <!-- Compartir -->
        <div class="ficha-share">
            <div class="ficha-share-label">Compartir este código</div>
            <div class="ficha-share-buttons">
                <a href="whatsapp://send?text=<?php echo $share_title; ?>%20<?php echo urlencode($share_url); ?>" class="ficha-share-btn whatsapp" data-action="share/whatsapp/share">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="https://x.com/intent/tweet?url=<?php echo urlencode($share_url); ?>&text=<?php echo $share_title; ?>" target="_blank" class="ficha-share-btn twitter" onclick="window.open(this.href); return false;">
                    <i class="fab fa-x-twitter"></i> X
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($share_url); ?>" target="_blank" class="ficha-share-btn facebook" onclick="window.open(this.href); return false;">
                    <i class="fab fa-facebook-f"></i> Facebook
                </a>
                <a href="https://telegram.me/share/url?url=<?php echo urlencode($share_url); ?>&text=<?php echo $share_title; ?>" target="_blank" class="ficha-share-btn telegram">
                    <i class="fab fa-telegram-plane"></i> Telegram
                </a>
                <button class="ficha-share-btn copy-link" onclick="fichaComparte('<?php echo $share_url; ?>', this)">
                    <i class="fas fa-link"></i> Copiar enlace
                </button>
            </div>
        </div>
    </div>

    <!-- CTA Ver todos los códigos -->
    <div class="ficha-cta-all">
        <a href="/de-<?php echo htmlspecialchars($marca_clave); ?>">
            <i class="fas fa-list"></i> Ver todos los códigos de <?php echo htmlspecialchars($nombre_marca); ?>
        </a>
    </div>

</div>
</div>

<script>
function fichaCopiaCodigo(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="fas fa-copy"></i> Copiar';
        }, 2500);
    }).catch(function() {
        // Fallback
        var temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="fas fa-copy"></i> Copiar';
        }, 2500);
    });
}

function fichaComparte(url, btn) {
    navigator.clipboard.writeText(url).then(function() {
        btn.innerHTML = '<i class="fas fa-check"></i> ¡Enlace copiado!';
        setTimeout(function() {
            btn.innerHTML = '<i class="fas fa-link"></i> Copiar enlace';
        }, 2500);
    });
}

function fichaVotar(tipo, codigoId, btn) {
    if (btn.classList.contains('active')) return;
    
    var formData = new FormData();
    formData.append('metodo', 'votar_codigo');
    formData.append('codigo_id', codigoId);
    formData.append('tipo', tipo);
    
    fetch('/ajax/obtener_codigo.php', {
        method: 'POST',
        body: formData
    }).then(function() {
        btn.classList.add('active');
        if (tipo === 'si') {
            var counter = document.getElementById('fichaVoteSi');
            if (counter) counter.textContent = parseInt(counter.textContent) + 1;
        }
        // Disable both buttons
        document.querySelectorAll('.ficha-vote-btn').forEach(function(b) {
            b.style.pointerEvents = 'none';
            b.style.opacity = '0.6';
        });
    }).catch(function(e) {
        console.error('Error votando:', e);
    });
}
</script>

<?php
// Footer
if (function_exists('get_footer_new')) {
    get_footer_new();
}
?>
