<?php
// La sesión ya está iniciada en app_with_mongo.php

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['user_id'];

include_once 'myphp/funciones.php';
include_once 'myphp/funciones_afiliados.php';
include_once 'myphp/funciones_usuario.php';

$collection_usuarios = getCollectionUsuarios();
$usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario_id)]);

if (!$usuario) {
    header('Location: login.php');
    exit;
}

// Datos
$urls_result = obtenerUrlsAfiliadosUsuario($usuario_id);
$urls = $urls_result['success'] ? $urls_result['urls'] : [];

$grupos_result = obtenerUrlsAfiliadosAgrupadasPorMarca($usuario_id);
$grupos_marca = $grupos_result['success'] ? $grupos_result['grupos'] : [];
$urls_sin_marca = $grupos_result['success'] ? $grupos_result['sin_marca'] : [];

$stats_result = obtenerEstadisticasIngresosUsuario($usuario_id);
$stats = $stats_result['success'] ? $stats_result['estadisticas'] : [
    'total_ingresos' => 0,
    'promedio_mensual' => 0,
    'urls_activas' => 0,
    'total_registros' => 0
];

$codigos_sin_afiliado_result = obtenerCodigosSinAfiliado($usuario_id);
$marcas_sin_afiliado = $codigos_sin_afiliado_result['success'] ? $codigos_sin_afiliado_result['marcas'] : [];

$redes = getRedesAfiliacion();

// Catálogo de redes únicas en uso
$redes_en_uso = [];
foreach ($urls as $u) {
    $r = $u['red_afiliacion'] ?? '';
    if ($r && !isset($redes_en_uso[$r])) $redes_en_uso[$r] = $redes[$r] ?? ['nombre' => ucfirst($r), 'color' => '#999'];
}

$title = "Mis URLs de Afiliados - CodigoAmigo";
$description = "Catálogo personal de URLs de afiliados por marca y red de afiliación.";

get_header_modern($title, $description, $title, $description);
$GLOBALS['header_modern_used'] = true;
?>

<style>
:root {
    --aff-brand: #E30613;
    --aff-brand-2: #ff6b35;
    --aff-bg: #f7f8fa;
    --aff-text: #1a1a1a;
    --aff-muted: #666;
    --aff-border: #e8e8ea;
}

.aff-wrapper { max-width: 1200px; margin: 0 auto; padding: 1.5rem 1rem 4rem; }

.aff-hero {
    background: linear-gradient(135deg, #fff5f5 0%, #ffeaea 60%, #ffd9d9 100%);
    border: 1px solid #ffd0d0;
    border-radius: 24px;
    padding: 2.5rem 1.5rem;
    margin-bottom: 1.5rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.aff-hero::before {
    content: "";
    position: absolute; inset: 0;
    background: radial-gradient(circle at 20% 20%, rgba(227,6,19,0.08), transparent 60%),
                radial-gradient(circle at 80% 80%, rgba(255,140,0,0.07), transparent 60%);
    pointer-events: none;
}
.aff-hero h1 {
    position: relative; margin: 0 0 .5rem;
    font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800;
    color: var(--aff-text); letter-spacing: -0.02em;
}
.aff-hero h1 .accent {
    background: linear-gradient(90deg, var(--aff-brand), var(--aff-brand-2));
    -webkit-background-clip: text; background-clip: text; color: transparent;
}
.aff-hero p { position: relative; color: #555; margin: 0; font-size: 1rem; }

.aff-stats {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem; margin-bottom: 2rem;
}
.aff-stat {
    background: #fff; border: 1px solid var(--aff-border);
    border-radius: 16px; padding: 1.2rem;
    display: flex; align-items: center; gap: 1rem;
}
.aff-stat .ico {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #fff5f5, #ffeaea);
    color: var(--aff-brand); font-size: 1.2rem; flex-shrink: 0;
}
.aff-stat .num { font-size: 1.5rem; font-weight: 800; color: var(--aff-text); line-height: 1; }
.aff-stat .lbl { font-size: .8rem; color: var(--aff-muted); text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

.aff-toolbar {
    background: #fff; border: 1px solid var(--aff-border);
    border-radius: 16px; padding: 1rem;
    display: flex; gap: .8rem; flex-wrap: wrap; align-items: center;
    margin-bottom: 1.2rem;
}
.aff-search {
    flex: 1; min-width: 240px; position: relative;
}
.aff-search input {
    width: 100%; padding: .7rem .9rem .7rem 2.4rem;
    border: 1px solid var(--aff-border); border-radius: 10px;
    font-size: .95rem; background: var(--aff-bg);
}
.aff-search input:focus { outline: none; border-color: var(--aff-brand); background: #fff; }
.aff-search i { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: #999; }

.aff-filter-red {
    padding: .7rem .9rem; border: 1px solid var(--aff-border);
    border-radius: 10px; background: var(--aff-bg); font-size: .95rem;
    min-width: 180px;
}

.aff-btn-primary {
    background: linear-gradient(135deg, var(--aff-brand), var(--aff-brand-2));
    color: #fff; border: none; padding: .7rem 1.4rem;
    border-radius: 10px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: .5rem;
    text-decoration: none; transition: transform .15s, box-shadow .15s;
}
.aff-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(227,6,19,0.3); color: #fff; }

.aff-btn-ghost {
    background: #fff; color: var(--aff-text); border: 1px solid var(--aff-border);
    padding: .55rem .9rem; border-radius: 8px; font-size: .85rem; font-weight: 600;
    cursor: pointer; display: inline-flex; align-items: center; gap: .4rem;
    text-decoration: none; transition: all .15s;
}
.aff-btn-ghost:hover { border-color: var(--aff-brand); color: var(--aff-brand); }
.aff-btn-ghost.danger:hover { border-color: #dc3545; color: #dc3545; }

.aff-section { margin: 2rem 0; }
.aff-section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1rem; flex-wrap: wrap; gap: .6rem;
}
.aff-section-head h2 {
    font-size: 1.2rem; font-weight: 700; color: var(--aff-text); margin: 0;
    display: flex; align-items: center; gap: .5rem;
}
.aff-section-head h2 i { color: var(--aff-brand); }
.aff-section-head .count {
    background: var(--aff-bg); color: var(--aff-muted);
    padding: .3rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 600;
}

.aff-marca-group {
    background: #fff; border: 1px solid var(--aff-border);
    border-radius: 16px; margin-bottom: 1rem; overflow: hidden;
}
.aff-marca-head {
    padding: 1rem 1.2rem; background: var(--aff-bg);
    display: flex; align-items: center; gap: .8rem;
    border-bottom: 1px solid var(--aff-border);
}
.aff-marca-head .marca-logo {
    width: 40px; height: 40px; border-radius: 8px; object-fit: contain;
    background: #fff; padding: 4px; border: 1px solid var(--aff-border);
}
.aff-marca-head .marca-logo-ph {
    width: 40px; height: 40px; border-radius: 8px;
    background: linear-gradient(135deg, var(--aff-brand), var(--aff-brand-2));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1rem;
}
.aff-marca-head .nombre { font-weight: 700; color: var(--aff-text); flex: 1; }
.aff-marca-head .pill {
    background: #fff; border: 1px solid var(--aff-border);
    padding: .25rem .7rem; border-radius: 999px; font-size: .8rem; color: var(--aff-muted);
}

.aff-url-row {
    padding: 1rem 1.2rem; border-bottom: 1px solid var(--aff-border);
    display: grid; grid-template-columns: auto 1fr auto; gap: 1rem; align-items: center;
}
.aff-url-row:last-child { border-bottom: none; }

.red-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: .3rem .7rem; border-radius: 999px;
    font-size: .75rem; font-weight: 700; color: #fff;
    text-transform: uppercase; letter-spacing: .5px;
    white-space: nowrap;
}
.red-badge.none { background: #adb5bd; }

.aff-url-info { min-width: 0; }
.aff-url-info .plat { font-weight: 600; color: var(--aff-text); margin-bottom: 2px; }
.aff-url-info .link {
    color: var(--aff-muted); font-size: .85rem; word-break: break-all;
    text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
}
.aff-url-info .link:hover { color: var(--aff-brand); }
.aff-url-info .desc { font-size: .8rem; color: #999; margin-top: 4px; }

.aff-url-actions { display: flex; gap: .4rem; flex-wrap: wrap; }

.aff-empty {
    text-align: center; padding: 3rem 1rem;
    background: #fff; border: 1px dashed var(--aff-border); border-radius: 16px;
    color: var(--aff-muted);
}
.aff-empty i { font-size: 2.5rem; color: var(--aff-brand); margin-bottom: 1rem; opacity: .7; }
.aff-empty h3 { color: var(--aff-text); margin: 0 0 .5rem; }
.aff-empty p { margin: 0 0 1.2rem; }

/* Marcas sin afiliado */
.aff-pending-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: .8rem;
}
.aff-pending-card {
    background: #fff; border: 1px solid var(--aff-border);
    border-radius: 12px; padding: 1rem; cursor: pointer;
    display: flex; align-items: center; gap: .8rem;
    transition: all .15s;
}
.aff-pending-card:hover {
    border-color: var(--aff-brand); transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,.05);
}
.aff-pending-card img, .aff-pending-card .ph {
    width: 36px; height: 36px; border-radius: 8px; object-fit: contain; flex-shrink: 0;
}
.aff-pending-card .ph {
    background: var(--aff-bg); display: flex; align-items: center; justify-content: center;
    color: var(--aff-muted);
}
.aff-pending-card .info { flex: 1; min-width: 0; }
.aff-pending-card .info .n { font-weight: 600; font-size: .9rem; color: var(--aff-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.aff-pending-card .info .c { font-size: .75rem; color: var(--aff-muted); }

/* Modal */
.aff-modal {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.55); z-index: 9999;
    align-items: center; justify-content: center; padding: 1rem;
}
.aff-modal.show { display: flex; }
.aff-modal-content {
    background: #fff; border-radius: 16px; max-width: 520px; width: 100%;
    max-height: 90vh; overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.aff-modal-head {
    padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--aff-border);
    display: flex; justify-content: space-between; align-items: center;
}
.aff-modal-head h3 { margin: 0; font-size: 1.1rem; font-weight: 700; }
.aff-modal-close {
    background: none; border: none; font-size: 1.5rem; cursor: pointer;
    color: var(--aff-muted); line-height: 1;
}
.aff-modal-body { padding: 1.5rem; }
.aff-modal-foot {
    padding: 1rem 1.5rem; border-top: 1px solid var(--aff-border);
    display: flex; justify-content: flex-end; gap: .6rem;
}

.aff-field { margin-bottom: 1.1rem; }
.aff-field label {
    display: block; font-weight: 600; font-size: .85rem;
    color: var(--aff-text); margin-bottom: .35rem;
}
.aff-field label .req { color: var(--aff-brand); }
.aff-field input, .aff-field select, .aff-field textarea {
    width: 100%; padding: .65rem .8rem;
    border: 1px solid var(--aff-border); border-radius: 8px;
    font-size: .95rem; background: #fff;
}
.aff-field input:focus, .aff-field select:focus, .aff-field textarea:focus {
    outline: none; border-color: var(--aff-brand);
}
.aff-field .help { font-size: .75rem; color: var(--aff-muted); margin-top: .25rem; }

.aff-marca-autocomplete { position: relative; }
.aff-ac-results {
    position: absolute; top: 100%; left: 0; right: 0;
    background: #fff; border: 1px solid var(--aff-border);
    border-top: none; border-radius: 0 0 8px 8px;
    max-height: 240px; overflow-y: auto; z-index: 10;
    display: none;
}
.aff-ac-item {
    padding: .6rem .8rem; cursor: pointer;
    display: flex; align-items: center; gap: .6rem;
    border-bottom: 1px solid var(--aff-border);
}
.aff-ac-item:hover { background: var(--aff-bg); }
.aff-ac-item img { width: 24px; height: 24px; object-fit: contain; }
.aff-ac-item .nm { font-size: .9rem; }

.aff-toast {
    position: fixed; top: 80px; right: 20px; z-index: 10000;
    padding: 1rem 1.4rem; border-radius: 10px;
    background: #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    border-left: 4px solid #28a745; color: var(--aff-text);
    animation: slideIn .3s ease;
}
.aff-toast.error { border-left-color: #dc3545; }
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

@media (max-width: 640px) {
    .aff-url-row { grid-template-columns: 1fr; }
    .aff-url-actions { justify-content: flex-start; }
}
</style>

<div class="aff-wrapper">
    <section class="aff-hero">
        <h1>Mis <span class="accent">URLs de Afiliados</span></h1>
        <p>Catálogo personal organizado por marca y red de afiliación. Nunca pierdas el enlace de una marca.</p>
    </section>

    <div class="aff-stats">
        <div class="aff-stat">
            <div class="ico"><i class="fa fa-link"></i></div>
            <div>
                <div class="num"><?php echo count($urls); ?></div>
                <div class="lbl">URLs guardadas</div>
            </div>
        </div>
        <div class="aff-stat">
            <div class="ico"><i class="fa fa-tags"></i></div>
            <div>
                <div class="num"><?php echo count($grupos_marca); ?></div>
                <div class="lbl">Marcas cubiertas</div>
            </div>
        </div>
        <div class="aff-stat">
            <div class="ico"><i class="fa fa-sitemap"></i></div>
            <div>
                <div class="num"><?php echo count($redes_en_uso); ?></div>
                <div class="lbl">Redes activas</div>
            </div>
        </div>
        <div class="aff-stat">
            <div class="ico"><i class="fa fa-euro"></i></div>
            <div>
                <div class="num"><?php echo number_format($stats['total_ingresos'], 0); ?>€</div>
                <div class="lbl">Total ingresos</div>
            </div>
        </div>
    </div>

    <div class="aff-toolbar">
        <div class="aff-search">
            <i class="fa fa-search"></i>
            <input type="text" id="affSearch" placeholder="Buscar por marca, plataforma o URL...">
        </div>
        <select class="aff-filter-red" id="affFilterRed">
            <option value="">Todas las redes</option>
            <?php foreach ($redes as $k => $r): ?>
                <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($r['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="aff-btn-primary" onclick="abrirModalUrl()">
            <i class="fa fa-plus"></i> Nueva URL
        </button>
    </div>

    <!-- URLs por marca -->
    <section class="aff-section">
        <div class="aff-section-head">
            <h2><i class="fa fa-folder-open"></i> URLs por marca</h2>
            <span class="count"><?php echo count($grupos_marca); ?> marcas</span>
        </div>

        <div id="affGroups">
            <?php if (empty($grupos_marca) && empty($urls_sin_marca)): ?>
                <div class="aff-empty">
                    <i class="fa fa-link"></i>
                    <h3>Aún no tienes URLs guardadas</h3>
                    <p>Empieza añadiendo el enlace de afiliado de una marca que ya uses.</p>
                    <button class="aff-btn-primary" onclick="abrirModalUrl()">
                        <i class="fa fa-plus"></i> Añadir primera URL
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($grupos_marca as $clave => $g): ?>
                    <div class="aff-marca-group" data-marca="<?php echo htmlspecialchars(strtolower($g['marca_nombre'].' '.$g['marca_clave'])); ?>">
                        <div class="aff-marca-head">
                            <div class="marca-logo-ph"><?php echo htmlspecialchars(strtoupper(substr($g['marca_nombre'], 0, 1))); ?></div>
                            <div class="nombre"><?php echo htmlspecialchars($g['marca_nombre']); ?></div>
                            <span class="pill"><?php echo count($g['urls']); ?> URL<?php echo count($g['urls']) > 1 ? 's' : ''; ?></span>
                        </div>
                        <?php foreach ($g['urls'] as $u):
                            $red_k = $u['red_afiliacion'] ?? '';
                            $red_info = $redes[$red_k] ?? null;
                        ?>
                            <div class="aff-url-row" data-red="<?php echo htmlspecialchars($red_k); ?>" data-search="<?php echo htmlspecialchars(strtolower($u['nombre_plataforma'].' '.$u['url'].' '.$g['marca_nombre'])); ?>">
                                <?php if ($red_info): ?>
                                    <span class="red-badge" style="background: <?php echo htmlspecialchars($red_info['color']); ?>;">
                                        <?php echo htmlspecialchars($red_info['nombre']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="red-badge none">Sin red</span>
                                <?php endif; ?>
                                <div class="aff-url-info">
                                    <div class="plat"><?php echo htmlspecialchars($u['nombre_plataforma']); ?></div>
                                    <a href="<?php echo htmlspecialchars($u['url']); ?>" target="_blank" rel="noopener" class="link">
                                        <i class="fa fa-external-link"></i> <?php echo htmlspecialchars($u['url']); ?>
                                    </a>
                                    <?php if (!empty($u['descripcion'])): ?>
                                        <div class="desc"><?php echo htmlspecialchars($u['descripcion']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="aff-url-actions">
                                    <button class="aff-btn-ghost" onclick="copiarUrl('<?php echo htmlspecialchars(addslashes($u['url'])); ?>')" title="Copiar">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                    <button class="aff-btn-ghost" onclick='editarUrl(<?php echo json_encode([
                                        '_id' => (string)$u['_id'],
                                        'url' => $u['url'],
                                        'nombre_plataforma' => $u['nombre_plataforma'],
                                        'descripcion' => $u['descripcion'],
                                        'red_afiliacion' => $red_k,
                                        'marca_clave' => $u['marca_clave'] ?? '',
                                        'marca_nombre' => $u['marca_nombre'] ?? ''
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Editar">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button class="aff-btn-ghost danger" onclick="eliminarUrl('<?php echo (string)$u['_id']; ?>')" title="Eliminar">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($urls_sin_marca)): ?>
                    <div class="aff-marca-group" data-marca="sin marca">
                        <div class="aff-marca-head">
                            <div class="marca-logo-ph" style="background:#adb5bd;">?</div>
                            <div class="nombre">Sin marca asignada</div>
                            <span class="pill"><?php echo count($urls_sin_marca); ?> URLs</span>
                        </div>
                        <?php foreach ($urls_sin_marca as $u):
                            $red_k = $u['red_afiliacion'] ?? '';
                            $red_info = $redes[$red_k] ?? null;
                        ?>
                            <div class="aff-url-row" data-red="<?php echo htmlspecialchars($red_k); ?>" data-search="<?php echo htmlspecialchars(strtolower($u['nombre_plataforma'].' '.$u['url'])); ?>">
                                <?php if ($red_info): ?>
                                    <span class="red-badge" style="background: <?php echo htmlspecialchars($red_info['color']); ?>;">
                                        <?php echo htmlspecialchars($red_info['nombre']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="red-badge none">Sin red</span>
                                <?php endif; ?>
                                <div class="aff-url-info">
                                    <div class="plat"><?php echo htmlspecialchars($u['nombre_plataforma']); ?></div>
                                    <a href="<?php echo htmlspecialchars($u['url']); ?>" target="_blank" rel="noopener" class="link">
                                        <i class="fa fa-external-link"></i> <?php echo htmlspecialchars($u['url']); ?>
                                    </a>
                                </div>
                                <div class="aff-url-actions">
                                    <button class="aff-btn-ghost" onclick="copiarUrl('<?php echo htmlspecialchars(addslashes($u['url'])); ?>')"><i class="fa fa-copy"></i></button>
                                    <button class="aff-btn-ghost" onclick='editarUrl(<?php echo json_encode([
                                        '_id' => (string)$u['_id'],
                                        'url' => $u['url'],
                                        'nombre_plataforma' => $u['nombre_plataforma'],
                                        'descripcion' => $u['descripcion'],
                                        'red_afiliacion' => $red_k,
                                        'marca_clave' => '',
                                        'marca_nombre' => ''
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fa fa-pencil"></i></button>
                                    <button class="aff-btn-ghost danger" onclick="eliminarUrl('<?php echo (string)$u['_id']; ?>')"><i class="fa fa-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Marcas tuyas sin afiliado -->
    <?php if (!empty($marcas_sin_afiliado)): ?>
        <section class="aff-section">
            <div class="aff-section-head">
                <h2><i class="fa fa-exclamation-circle"></i> Marcas de tus códigos sin URL asignada</h2>
                <span class="count"><?php echo count($marcas_sin_afiliado); ?></span>
            </div>
            <div class="aff-pending-grid">
                <?php foreach ($marcas_sin_afiliado as $clave => $m): ?>
                    <div class="aff-pending-card" onclick="abrirModalUrlConMarca('<?php echo htmlspecialchars($clave); ?>', '<?php echo htmlspecialchars(addslashes($m['nombre_marca'])); ?>')">
                        <?php if (!empty($m['url_imagen'])): ?>
                            <img src="<?php echo htmlspecialchars($m['url_imagen']); ?>" alt="">
                        <?php else: ?>
                            <div class="ph"><i class="fa fa-image"></i></div>
                        <?php endif; ?>
                        <div class="info">
                            <div class="n"><?php echo htmlspecialchars($m['nombre_marca']); ?></div>
                            <div class="c"><?php echo $m['total_codigos']; ?> código<?php echo $m['total_codigos'] > 1 ? 's' : ''; ?> sin enlace</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<!-- Modal Nueva/Editar URL -->
<div class="aff-modal" id="affModalUrl">
    <div class="aff-modal-content">
        <div class="aff-modal-head">
            <h3 id="affModalTitle"><i class="fa fa-plus"></i> Nueva URL de afiliado</h3>
            <button class="aff-modal-close" onclick="cerrarModalUrl()">&times;</button>
        </div>
        <form id="affFormUrl">
            <input type="hidden" id="aff_url_id" value="">
            <div class="aff-modal-body">
                <div class="aff-field">
                    <label>Marca <span class="req">*</span></label>
                    <div class="aff-marca-autocomplete">
                        <input type="text" id="aff_marca_nombre" placeholder="Ej: N26, Hotmart, Trade Republic..." autocomplete="off" required>
                        <input type="hidden" id="aff_marca_clave">
                        <div class="aff-ac-results" id="aff_ac_results"></div>
                    </div>
                    <div class="help">Empieza a escribir para buscar entre las marcas existentes.</div>
                </div>
                <div class="aff-field">
                    <label>Red de afiliación</label>
                    <select id="aff_red">
                        <option value="">— Selecciona —</option>
                        <?php foreach ($redes as $k => $r): ?>
                            <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($r['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="help">Útil si esta marca tiene varios programas (Impact, Awin, etc.).</div>
                </div>
                <div class="aff-field">
                    <label>Nombre / etiqueta <span class="req">*</span></label>
                    <input type="text" id="aff_plataforma" placeholder="Ej: N26 Impact, Hotmart España..." required>
                    <div class="help">Cómo lo identificarás en tu listado.</div>
                </div>
                <div class="aff-field">
                    <label>URL <span class="req">*</span></label>
                    <input type="url" id="aff_url" placeholder="https://..." required>
                </div>
                <div class="aff-field">
                    <label>Notas</label>
                    <textarea id="aff_desc" rows="2" placeholder="Comisión, cookie, observaciones..."></textarea>
                </div>
            </div>
            <div class="aff-modal-foot">
                <button type="button" class="aff-btn-ghost" onclick="cerrarModalUrl()">Cancelar</button>
                <button type="submit" class="aff-btn-primary"><i class="fa fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    const $ = id => document.getElementById(id);
    const modal = $('affModalUrl');

    window.abrirModalUrl = function() {
        $('affModalTitle').innerHTML = '<i class="fa fa-plus"></i> Nueva URL de afiliado';
        $('affFormUrl').reset();
        $('aff_url_id').value = '';
        $('aff_marca_clave').value = '';
        modal.classList.add('show');
    };

    window.abrirModalUrlConMarca = function(clave, nombre) {
        abrirModalUrl();
        $('aff_marca_nombre').value = nombre;
        $('aff_marca_clave').value = clave;
        $('aff_plataforma').focus();
    };

    window.cerrarModalUrl = function() {
        modal.classList.remove('show');
    };

    modal.addEventListener('click', e => { if (e.target === modal) cerrarModalUrl(); });

    window.editarUrl = function(data) {
        $('affModalTitle').innerHTML = '<i class="fa fa-pencil"></i> Editar URL';
        $('aff_url_id').value = data._id;
        $('aff_marca_nombre').value = data.marca_nombre || '';
        $('aff_marca_clave').value = data.marca_clave || '';
        $('aff_red').value = data.red_afiliacion || '';
        $('aff_plataforma').value = data.nombre_plataforma || '';
        $('aff_url').value = data.url || '';
        $('aff_desc').value = data.descripcion || '';
        modal.classList.add('show');
    };

    // Autocomplete marcas
    let acTimer = null;
    $('aff_marca_nombre').addEventListener('input', function() {
        const q = this.value.trim();
        $('aff_marca_clave').value = '';
        clearTimeout(acTimer);
        if (q.length < 2) { $('aff_ac_results').style.display = 'none'; return; }
        acTimer = setTimeout(() => {
            fetch('/ajax/afiliados_handler.php?metodo=buscar_marcas&q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(d => {
                    const box = $('aff_ac_results');
                    if (!d.success || !d.marcas.length) { box.style.display = 'none'; return; }
                    box.innerHTML = d.marcas.map(m =>
                        `<div class="aff-ac-item" data-nombre="${m.nombre}" data-clave="${m.nombre_clave}">
                            ${m.imagen ? `<img src="${m.imagen}" alt="">` : '<div style="width:24px;height:24px;background:#eee;border-radius:4px;"></div>'}
                            <span class="nm">${m.nombre}</span>
                        </div>`
                    ).join('');
                    box.style.display = 'block';
                    box.querySelectorAll('.aff-ac-item').forEach(it => {
                        it.addEventListener('click', () => {
                            $('aff_marca_nombre').value = it.dataset.nombre;
                            $('aff_marca_clave').value = it.dataset.clave;
                            box.style.display = 'none';
                        });
                    });
                });
        }, 250);
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('.aff-marca-autocomplete')) $('aff_ac_results').style.display = 'none';
    });

    // Submit
    $('affFormUrl').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = $('aff_url_id').value;
        const fd = new FormData();
        fd.append('metodo', id ? 'actualizar_url' : 'agregar_url');
        if (id) fd.append('url_id', id);
        fd.append('url', $('aff_url').value);
        fd.append('nombre_plataforma', $('aff_plataforma').value);
        fd.append('descripcion', $('aff_desc').value);
        fd.append('red_afiliacion', $('aff_red').value);
        fd.append('marca_clave', $('aff_marca_clave').value);
        fd.append('marca_nombre', $('aff_marca_nombre').value);

        fetch('/ajax/afiliados_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    toast(id ? 'URL actualizada' : 'URL guardada', 'success');
                    setTimeout(() => location.reload(), 700);
                } else {
                    toast(d.error || 'Error', 'error');
                }
            })
            .catch(() => toast('Error de conexión', 'error'));
    });

    window.eliminarUrl = function(id) {
        if (!confirm('¿Eliminar esta URL? Se quitará del catálogo.')) return;
        const fd = new FormData();
        fd.append('metodo', 'eliminar_url');
        fd.append('url_id', id);
        fetch('/ajax/afiliados_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) { toast('URL eliminada', 'success'); setTimeout(() => location.reload(), 600); }
                else toast(d.error || 'Error', 'error');
            });
    };

    window.copiarUrl = function(url) {
        navigator.clipboard.writeText(url).then(() => toast('URL copiada', 'success'));
    };

    // Filtros
    function aplicarFiltros() {
        const q = $('affSearch').value.toLowerCase().trim();
        const red = $('affFilterRed').value;
        document.querySelectorAll('.aff-marca-group').forEach(g => {
            let visibles = 0;
            g.querySelectorAll('.aff-url-row').forEach(row => {
                const txt = row.dataset.search || '';
                const r = row.dataset.red || '';
                const matchQ = !q || txt.includes(q) || (g.dataset.marca || '').includes(q);
                const matchR = !red || r === red;
                if (matchQ && matchR) { row.style.display = ''; visibles++; }
                else row.style.display = 'none';
            });
            g.style.display = visibles ? '' : 'none';
        });
    }
    $('affSearch').addEventListener('input', aplicarFiltros);
    $('affFilterRed').addEventListener('change', aplicarFiltros);

    function toast(msg, tipo) {
        const t = document.createElement('div');
        t.className = 'aff-toast' + (tipo === 'error' ? ' error' : '');
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3000);
    }
})();
</script>

<?php
get_footer();
