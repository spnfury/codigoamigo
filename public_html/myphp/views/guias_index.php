<?php
/**
 * Índice de Guías (/guias).
 * Lista todas las super_landings activas como tarjetas. Mejora el enlazado
 * interno y da una landing canónica a la sección de guías.
 *
 * Variable disponible: $guias (array de super_landings activas).
 */

$guias = $guias ?? [];
$total = count($guias);

// Schema.org: CollectionPage + BreadcrumbList
$schema_collection = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'Guías de CodigoAmigo',
    'description' => 'Guías y comparativas para ahorrar y ganar dinero con códigos amigo: banca, neobancos, finanzas y más.',
    'url' => 'https://www.codigoamigo.com/guias',
];
echo '<script type="application/ld+json">' . json_encode($schema_collection, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

$schema_breadcrumb = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => 'https://www.codigoamigo.com'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Guías', 'item' => 'https://www.codigoamigo.com/guias'],
    ],
];
echo '<script type="application/ld+json">' . json_encode($schema_breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
?>

<style>
.guias-index-hero {
    background: linear-gradient(135deg, #1f1f1f 0%, #0d0d0d 100%);
    color: #fff;
    padding: 60px 20px 50px;
    text-align: center;
    border-radius: 0 0 20px 20px;
    margin-bottom: 40px;
}
.guias-index-hero h1 {
    font-size: 2.6rem;
    font-weight: 800;
    margin-bottom: 14px;
}
.guias-index-hero p {
    font-size: 1.15rem;
    opacity: 0.9;
    max-width: 720px;
    margin: 0 auto;
}
.guias-index-grid {
    max-width: 1200px;
    margin: 0 auto 60px;
    padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
    gap: 28px;
}
.guia-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}
.guia-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 36px rgba(0,0,0,0.12);
}
.guia-card-img {
    height: 160px;
    background-size: cover;
    background-position: center;
    background-color: #16213e;
}
.guia-card-body {
    padding: 22px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.guia-card-body h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 10px;
    line-height: 1.35;
}
.guia-card-body p {
    color: #6b7280;
    font-size: 0.97rem;
    line-height: 1.55;
    flex-grow: 1;
    margin-bottom: 18px;
}
.guia-card-cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #E30613;
    font-weight: 700;
    text-decoration: none;
    font-size: 0.98rem;
}
.guia-card-link { text-decoration: none; color: inherit; display: flex; flex-direction: column; flex-grow: 1; }
.guias-index-empty { text-align: center; color: #888; padding: 60px 20px; }
@media (max-width: 600px) {
    .guias-index-hero h1 { font-size: 2rem; }
}
</style>

<div class="guias-index-hero">
    <h1>Guías para ahorrar y ganar dinero</h1>
    <p>Comparativas y guías prácticas sobre banca, neobancos y finanzas. Elige la mejor opción y consigue dinero gratis con nuestros códigos amigo verificados.</p>
</div>

<?php if ($total > 0): ?>
    <div class="guias-index-grid">
        <?php foreach ($guias as $g):
            $slug = htmlspecialchars($g['slug'] ?? '');
            $titulo = htmlspecialchars($g['title'] ?? ($g['meta_title'] ?? 'Guía'));
            $desc = htmlspecialchars($g['meta_description'] ?? '');
            $img = htmlspecialchars($g['hero_image'] ?? '');
            if ($slug === '') continue;
        ?>
        <div class="guia-card">
            <a href="/guias/<?php echo $slug; ?>" class="guia-card-link">
                <div class="guia-card-img" <?php echo $img ? 'style="background-image:url(\'' . $img . '\')"' : ''; ?>></div>
                <div class="guia-card-body">
                    <h2><?php echo $titulo; ?></h2>
                    <?php if ($desc): ?><p><?php echo $desc; ?></p><?php endif; ?>
                    <span class="guia-card-cta">Leer la guía <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="guias-index-empty">
        <p>Pronto publicaremos nuevas guías. ¡Vuelve pronto!</p>
    </div>
<?php endif; ?>
