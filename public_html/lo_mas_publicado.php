<?php
include_once __DIR__ . '/myphp/funciones.php';
include_once __DIR__ . '/myphp/funciones_usuario.php';

$collection_codigos  = getCollectionCodigos();
$collection_usuarios = getCollectionUsuarios();
$collection_viewers  = function_exists('getCollectionCodeViewers') ? getCollectionCodeViewers() : null;

// Top 20 publicadores por: total códigos activos + clicks acumulados
$top_publicadores = $collection_codigos->aggregate([
    ['$match' => ['estado' => ['$in' => [0, -1, 1]]]],
    ['$group' => [
        '_id' => '$id_usuario',
        'total_codigos' => ['$sum' => 1],
        'total_clicks'  => ['$sum' => ['$add' => [
            ['$ifNull' => ['$totalclicks', 0]],
            ['$ifNull' => ['$clicks', 0]],
        ]]],
    ]],
    ['$match' => ['total_codigos' => ['$gte' => 2]]],
    ['$sort' => ['total_clicks' => -1, 'total_codigos' => -1]],
    ['$limit' => 20],
])->toArray();

// Hidratar con datos de usuario
$ranking = [];
foreach ($top_publicadores as $row) {
    $u = $collection_usuarios->findOne(
        ['_id' => $row['_id']],
        ['projection' => ['username' => 1, 'img' => 1, 'fecha_registro' => 1]]
    );
    if (!$u) continue;
    $ranking[] = [
        'user_id'       => (string)$row['_id'],
        'username'      => trim($u['username'] ?? 'Usuario'),
        'avatar'        => $u['img'] ?? '',
        'total_codigos' => (int)$row['total_codigos'],
        'total_clicks'  => (int)$row['total_clicks'],
    ];
}

$title = "Top publicadores - CodigoAmigo";
$description = "Ranking de los usuarios que más códigos publican y más clicks generan en CodigoAmigo. Únete y aparece en el top.";
$title_social = $title;
$description_social = $description;

get_header_modern($title, $description, $title_social, $description_social);
$GLOBALS['header_modern_used'] = true;
?>
<style>
.ranking-hero {
    background: linear-gradient(135deg, #E30613 0%, #C40510 100%);
    color: #fff;
    padding: 3rem 1rem;
    text-align: center;
}
.ranking-hero h1 { margin: 0; font-size: 2.2rem; }
.ranking-hero p { margin: 0.6rem 0 0; opacity: 0.95; font-size: 1.05rem; }
.ranking-cta {
    display: inline-block;
    margin-top: 1.2rem;
    background: linear-gradient(135deg, #ffd700, #f39c12);
    color: #1a1a2e;
    padding: 0.8rem 1.8rem;
    border-radius: 30px;
    font-weight: 700;
    text-decoration: none;
}
.ranking-table {
    max-width: 900px;
    margin: 2rem auto;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.ranking-row {
    display: grid;
    grid-template-columns: 60px 80px 1fr 120px 120px;
    align-items: center;
    padding: 14px 20px;
    border-bottom: 1px solid #eee;
}
.ranking-row:last-child { border-bottom: none; }
.ranking-row.header {
    background: #f8f9fa;
    font-weight: 700;
    color: #555;
    font-size: 0.85rem;
    text-transform: uppercase;
}
.ranking-row.podium-1 { background: linear-gradient(90deg, #fff8e1, #fff); }
.ranking-row.podium-2 { background: linear-gradient(90deg, #f5f5f5, #fff); }
.ranking-row.podium-3 { background: linear-gradient(90deg, #fff3e0, #fff); }
.rank-number { font-size: 1.4rem; font-weight: 700; color: #888; }
.rank-medal { font-size: 1.6rem; }
.rank-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; background: #ddd; }
.rank-name { font-weight: 600; color: #1a1a2e; }
.rank-stat { text-align: right; font-weight: 600; color: #333; }
@media (max-width: 700px) {
    .ranking-row { grid-template-columns: 50px 60px 1fr 70px; padding: 10px 12px; gap: 8px; font-size: 0.9rem; }
    .ranking-row .col-codigos { display: none; }
}
</style>

<div class="ranking-hero">
    <h1>🏆 Top publicadores de CodigoAmigo</h1>
    <p>Los usuarios que más códigos publican y más clicks consiguen cada mes.</p>
    <?php if (empty($_SESSION['user_id'])): ?>
        <a href="/registro" class="ranking-cta">Regístrate y empieza a publicar</a>
    <?php else: ?>
        <a href="/?page=publicar_codigo" class="ranking-cta">Publicar mi código</a>
    <?php endif; ?>
</div>

<div class="ranking-table">
    <div class="ranking-row header">
        <div>#</div>
        <div></div>
        <div>Publicador</div>
        <div class="rank-stat col-codigos">Códigos</div>
        <div class="rank-stat">Clicks</div>
    </div>
    <?php if (empty($ranking)): ?>
        <div style="padding: 40px; text-align: center; color: #888;">Aún no hay datos suficientes para mostrar el ranking.</div>
    <?php else: ?>
        <?php foreach ($ranking as $i => $r):
            $pos = $i + 1;
            $podium_class = $pos === 1 ? 'podium-1' : ($pos === 2 ? 'podium-2' : ($pos === 3 ? 'podium-3' : ''));
            $medal = $pos === 1 ? '🥇' : ($pos === 2 ? '🥈' : ($pos === 3 ? '🥉' : ''));
            $avatar = !empty($r['avatar']) ? htmlspecialchars($r['avatar'], ENT_QUOTES, 'UTF-8') : 'https://www.codigoamigo.com/img/no_image.png';
        ?>
        <div class="ranking-row <?= $podium_class ?>">
            <div class="rank-number">
                <?php if ($medal): ?><span class="rank-medal"><?= $medal ?></span><?php else: ?>#<?= $pos ?><?php endif; ?>
            </div>
            <div>
                <img src="<?= $avatar ?>" alt="" class="rank-avatar" onerror="this.src='https://www.codigoamigo.com/img/no_image.png';">
            </div>
            <div class="rank-name"><?= htmlspecialchars($r['username'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="rank-stat col-codigos"><?= number_format($r['total_codigos'], 0, ',', '.') ?></div>
            <div class="rank-stat"><?= number_format($r['total_clicks'], 0, ',', '.') ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div style="text-align: center; padding: 2rem 1rem; color: #666;">
    <p>¿Quieres aparecer en el ranking?</p>
    <p style="font-size: 0.95rem;">Publica tus códigos de descuento y referidos. Cuantos más clicks recibas, más arriba en el top.</p>
    <p>
        <strong>🎁 Regalo:</strong> +1€ en tu saldo al publicar tu primer código.
    </p>
</div>

<?php get_footer(); ?>
