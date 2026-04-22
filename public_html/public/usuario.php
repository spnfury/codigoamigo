<?php 
    get_header_modern($title, $description);
    $GLOBALS['header_modern_used'] = true; 
    global $data_usuario;
    
    // Calcular potencial de ganancias
    if (!function_exists('obtener_potencial_completo_usuario')) {
        include_once __DIR__ . '/../myphp/funciones_usuario.php';
    }
    $potencial_data = obtener_potencial_completo_usuario($_SESSION["user_id"]);
    $total_potential = $potencial_data['total_potential'];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<?php
// ═══ CÁLCULO DE TRUST SCORE DEL USUARIO ═══
include_once __DIR__ . '/../myphp/funciones_trust_score.php';

$user_trust_score = 1;
$user_trust_checks = [
    'foto' => false,
    'email' => false,
    'antiguedad_years' => 0,
    'multimarca' => false,
    'votos_positivos' => 0,
    'descripcion' => false,
];

$url_usuario_sin_foto_check = $GLOBALS['url_usuario_sin_foto'] ?? '';
$tiene_foto = false;
if (!empty($data_usuario['img'])) {
    $img = $data_usuario['img'];
    if ($url_usuario_sin_foto_check && $img != $url_usuario_sin_foto_check 
        && strpos($img, 'sin_foto') === false
        && strpos($img, 'default') === false) {
        $tiene_foto = true;
    }
}
$user_trust_checks['foto'] = $tiene_foto;

$user_trust_checks['email'] = !empty($data_usuario['verificado']) || !empty($data_usuario['email_verificado']) || (!empty($data_usuario['estado']) && $data_usuario['estado'] == 1);

$años_cuenta = 0;
if (!empty($data_usuario['fecha_registro'])) {
    $fecha_reg = $data_usuario['fecha_registro'];
    if (is_string($fecha_reg)) {
        $ts = strtotime($fecha_reg);
    } elseif ($fecha_reg instanceof MongoDB\BSON\UTCDateTime) {
        $ts = $fecha_reg->toDateTime()->getTimestamp();
    } else {
        $ts = time();
    }
    $años_cuenta = floor((time() - $ts) / (365.25 * 24 * 3600));
} elseif (!empty($data_usuario['_id']) && $data_usuario['_id'] instanceof MongoDB\BSON\ObjectId) {
    $ts = $data_usuario['_id']->getTimestamp();
    $años_cuenta = floor((time() - $ts) / (365.25 * 24 * 3600));
}
$user_trust_checks['antiguedad_years'] = $años_cuenta;

try {
    $col_codigos_trust = getCollectionCodigos();
    $uid_trust = $_SESSION["user_id"];
    $uid_trust_oid = new MongoDB\BSON\ObjectId($uid_trust);
    
    $marcas_count = count($col_codigos_trust->distinct('marca', [
        'id_usuario' => $uid_trust_oid,
        'estado' => ['$in' => [0, 1]]
    ]));
    $user_trust_checks['multimarca'] = ($marcas_count > 1);
    
    $pipeline_votos = [
        ['$match' => ['id_usuario' => $uid_trust_oid, 'estado' => ['$in' => [0, 1]]]],
        ['$group' => ['_id' => null, 'total_votos' => ['$sum' => '$votos_positivos'], 'has_desc' => ['$sum' => ['$cond' => [['$and' => [['$ne' => ['$descripcion', '']], ['$ne' => ['$descripcion', null]]]], 1, 0]]]]]
    ];
    $votos_result = $col_codigos_trust->aggregate($pipeline_votos)->toArray();
    $user_trust_checks['votos_positivos'] = $votos_result[0]['total_votos'] ?? 0;
    $user_trust_checks['descripcion'] = ($votos_result[0]['has_desc'] ?? 0) > 0;
} catch (Exception $e) {
    // Silently skip
}

$user_trust_score = 1;
if ($tiene_foto) $user_trust_score += 2; else $user_trust_score -= 1;
if ($user_trust_checks['email']) $user_trust_score += 1;
$user_trust_score += max(0, min($años_cuenta, 5));
if ($user_trust_checks['multimarca']) $user_trust_score += 1;
$user_trust_score += $user_trust_checks['votos_positivos'];
if ($user_trust_checks['descripcion']) $user_trust_score += 2;
$user_trust_score = max(1, $user_trust_score);

$es_usuario_vip = es_usuario_vip($_SESSION["user_id"]);
$user_trust = getTrustLevel($user_trust_score, false, $es_usuario_vip);

$thresholds = [1, 4, 8, 15, 999];
$labels_next = ['Verificado', 'De confianza', 'Recomendado', '¡Máximo!'];
$current_threshold_idx = 0;
if ($user_trust_score >= 15) $current_threshold_idx = 3;
elseif ($user_trust_score >= 8) $current_threshold_idx = 2;
elseif ($user_trust_score >= 4) $current_threshold_idx = 1;

$next_threshold = $thresholds[$current_threshold_idx + 1] ?? $user_trust_score;
$current_threshold = $thresholds[$current_threshold_idx];
$progress_pct = ($current_threshold_idx >= 3) ? 100 : min(100, round(($user_trust_score - $current_threshold) / max(1, $next_threshold - $current_threshold) * 100));
$next_label = $labels_next[$current_threshold_idx] ?? '¡Máximo!';
?>

<div class="ep-page">
    <!-- ═══ HERO HEADER ═══ -->
    <div class="ep-hero">
        <div class="ep-hero-inner">
            <div class="ep-hero-profile">
                <div class="ep-avatar-wrap">
                    <img src="<?php echo $data_usuario['img']; ?>" alt="Foto de perfil" class="ep-avatar-img">
                    <div class="ep-avatar-camera">
                        <div class="dropdown">
                            <button class="ep-camera-btn" type="button" data-toggle="dropdown" title="Cambiar foto de perfil">
                                <i class="fas fa-camera"></i>
                            </button>
                            <div class="dropdown-menu shadow">
                                <form id="form_cambiar_foto" enctype="multipart/form-data" action="/cambiar_foto_usuario" method="POST">
                                    <label for="uploadedfile" class="dropdown-item mb-0" style="cursor: pointer;">
                                        <i class="fas fa-upload mr-2 text-primary"></i> Subir nueva foto
                                    </label>
                                    <input type="file" name="uploadedfile" id="uploadedfile" class="d-none" accept="image/jpeg,image/png,image/gif">
                                </form>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item text-danger" type="button" id="eliminar_foto">
                                    <i class="fas fa-trash-alt mr-2"></i> Eliminar foto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ep-hero-info">
                    <div class="ep-hero-name-row">
                        <h1 class="ep-hero-name">
                            <?php echo $data_usuario['username']; ?>
                        </h1>
                        <?php if($es_usuario_vip): ?>
                            <span class="ep-vip-badge"><i class="fas fa-crown"></i> VIP</span>
                        <?php endif; ?>
                        <span class="ep-trust-badge" style="background: <?php echo $user_trust['color']; ?>22; border-color: <?php echo $user_trust['color']; ?>44; color: <?php echo $user_trust['color']; ?>;">
                            <?php for ($i = 0; $i < $user_trust['stars']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                            <?php echo $user_trust['label']; ?>
                        </span>
                    </div>
                    <p class="ep-hero-email"><i class="fas fa-envelope"></i> <?php echo $data_usuario['mail']; ?></p>
                    <p class="ep-hero-member"><i class="fas fa-calendar-alt"></i> Miembro desde hace <?php echo $años_cuenta; ?> año<?php echo $años_cuenta != 1 ? 's' : ''; ?></p>
                </div>
            </div>

            <?php 
                if (session_status() === PHP_SESSION_NONE) session_start();
                if(isset($_SESSION["msg"]) && $_SESSION["msg"] != "") {
                    echo '<div class="alert alert-info small rounded-pill py-2 px-3 mt-3" style="max-width: 500px;">' . htmlspecialchars($_SESSION["msg"]) . '</div>';
                    unset($_SESSION["msg"]);
                } 
            ?>
        </div>
    </div>

    <div class="ep-content">
        <div class="ep-grid">
            <!-- ═══ COLUMNA IZQUIERDA: STATS ═══ -->
            <div class="ep-sidebar">
                <!-- Potencial de ganancias -->
                <div class="ep-card ep-potential-card">
                    <div class="ep-card-label">Potencial de Ganancias</div>
                    <div class="ep-potential-value"><?php echo number_format($total_potential, 2, ',', '.'); ?>€</div>
                    <p class="ep-potential-desc">Tienes usuarios interesados en tus códigos.</p>
                    <a href="/mis-anuncios" class="ep-btn-action">
                        <i class="fas fa-paper-plane"></i> Ir a Mensaje Masivo
                    </a>
                </div>

                <!-- Nivel de confianza -->
                <div class="ep-card ep-trust-card">
                    <div class="ep-card-label"><i class="fas fa-shield-alt" style="color: <?php echo $user_trust['color']; ?>;"></i> Mi Nivel de Confianza</div>
                    
                    <div class="ep-trust-stars">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <?php if ($i < $user_trust['stars']): ?>
                                <i class="fas fa-star" style="color:<?php echo $user_trust['color']; ?>;"></i>
                            <?php else: ?>
                                <i class="far fa-star" style="color:#555;"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <span class="ep-trust-level" style="background: <?php echo $user_trust['color']; ?>22; color: <?php echo $user_trust['color']; ?>;">
                        <?php echo $user_trust['label']; ?>
                    </span>

                    <?php if ($current_threshold_idx < 3): ?>
                    <div class="ep-trust-progress">
                        <div class="ep-trust-progress-meta">
                            <span><?php echo $user_trust_score; ?> pts</span>
                            <span>Siguiente: <?php echo $next_label; ?> (<?php echo $next_threshold; ?>)</span>
                        </div>
                        <div class="ep-trust-progress-bar">
                            <div class="ep-trust-progress-fill" style="width: <?php echo $progress_pct; ?>%; background: linear-gradient(90deg, <?php echo $user_trust['color']; ?>, <?php echo $user_trust['color']; ?>aa);"></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="ep-trust-max"><i class="fas fa-trophy"></i> ¡Nivel máximo alcanzado!</div>
                    <?php endif; ?>

                    <div class="ep-trust-checklist">
                        <?php
                        $checks = [
                            ['key' => 'foto', 'label' => 'Foto de perfil', 'pts' => $user_trust_checks['foto'] ? '+2' : '-1', 'ok' => $user_trust_checks['foto'], 'neutral' => false],
                            ['key' => 'email', 'label' => 'Email verificado', 'pts' => $user_trust_checks['email'] ? '+1' : '0', 'ok' => $user_trust_checks['email'], 'neutral' => false],
                            ['key' => 'antiguedad', 'label' => 'Antigüedad (' . $años_cuenta . ' año' . ($años_cuenta != 1 ? 's' : '') . ')', 'pts' => '+' . min($años_cuenta, 5), 'ok' => $años_cuenta >= 1, 'neutral' => $años_cuenta < 1],
                            ['key' => 'multimarca', 'label' => 'Varias marcas', 'pts' => $user_trust_checks['multimarca'] ? '+1' : '0', 'ok' => $user_trust_checks['multimarca'], 'neutral' => false],
                            ['key' => 'votos', 'label' => 'Votos positivos', 'pts' => '+' . $user_trust_checks['votos_positivos'], 'ok' => $user_trust_checks['votos_positivos'] > 0, 'neutral' => $user_trust_checks['votos_positivos'] == 0],
                            ['key' => 'desc', 'label' => 'Descripción en códigos', 'pts' => $user_trust_checks['descripcion'] ? '+2' : '0', 'ok' => $user_trust_checks['descripcion'], 'neutral' => false],
                        ];
                        foreach ($checks as $c):
                            $color = $c['ok'] ? '#10b981' : ($c['neutral'] ? '#6b7280' : '#ef4444');
                            $icon = $c['ok'] ? 'fa-check-circle' : ($c['neutral'] ? 'fa-clock' : 'fa-times-circle');
                        ?>
                        <div class="ep-check-item">
                            <i class="fas <?php echo $icon; ?>" style="color: <?php echo $color; ?>;"></i>
                            <span class="ep-check-label"><?php echo $c['label']; ?></span>
                            <span class="ep-check-pts"><?php echo $c['pts']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ═══ COLUMNA DERECHA: FORMULARIO ═══ -->
            <div class="ep-main">
                <div class="ep-card">
                    <form id="editar_perfil">
                        <h3 class="ep-section-title"><i class="fas fa-user-edit"></i> Información Personal</h3>
                        
                        <div class="ep-field">
                            <label for="nombre">Nombre de usuario</label>
                            <div class="ep-input-wrap">
                                <i class="fas fa-user"></i>
                                <input type="text" name="nombre" id="nombre" required value="<?php echo $data_usuario['username']; ?>">
                            </div>
                        </div>

                        <div class="ep-field">
                            <label>Correo electrónico</label>
                            <div class="ep-input-wrap ep-input-disabled">
                                <i class="fas fa-envelope"></i>
                                <input type="text" disabled value="<?php echo $data_usuario['mail']; ?>">
                                <input type="hidden" name="correo" id="correo" value="<?php echo $data_usuario['mail']; ?>">
                            </div>
                            <small class="ep-field-hint">Para cambiar tu correo, contacta con soporte.</small>
                        </div>

                        <div class="ep-field-row">
                            <div class="ep-field">
                                <label for="telefono">Teléfono</label>
                                <div class="ep-input-wrap">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" name="telefono" id="telefono" value="<?php echo $data_usuario['telefono'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="ep-field">
                                <label for="whatsapp">WhatsApp</label>
                                <div class="ep-input-wrap">
                                    <i class="fab fa-whatsapp"></i>
                                    <input type="tel" name="whatsapp" id="whatsapp" value="<?php echo $data_usuario['whatsapp'] ?? ''; ?>" placeholder="+34...">
                                </div>
                            </div>
                        </div>

                        <div class="ep-field">
                            <label for="pass">Contraseña</label>
                            <div class="ep-input-wrap">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="pass" id="pass" required value="<?php echo $data_usuario['pass']; ?>">
                                <button class="ep-toggle-pass" type="button"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>

                        <div class="ep-divider"></div>

                        <h3 class="ep-section-title"><i class="fas fa-bell"></i> Preferencias de notificación</h3>

                        <div class="ep-pref">
                            <div class="ep-pref-info">
                                <i class="fas fa-bullhorn ep-pref-icon"></i>
                                <div>
                                    <h6>Notificaciones Push</h6>
                                    <small>Recibir alertas sobre nuevos chollos y ofertas.</small>
                                </div>
                            </div>
                            <label class="ep-switch">
                                <input type="checkbox" name="notis" id="notis" <?php if(($data_usuario['notis'] ?? 0) == 1) echo "checked"; ?>>
                                <span class="ep-slider"></span>
                            </label>
                        </div>

                        <div class="ep-pref">
                            <div class="ep-pref-info">
                                <i class="fas fa-users ep-pref-icon"></i>
                                <div>
                                    <h6>Alertas de competencia</h6>
                                    <small>Recibir avisos cuando otro usuario destaca en tus marcas.</small>
                                </div>
                            </div>
                            <label class="ep-switch">
                                <input type="checkbox" name="email_competencia" id="email_competencia" <?php if(($data_usuario['email_competencia'] ?? 1) == 1) echo "checked"; ?>>
                                <span class="ep-slider"></span>
                            </label>
                        </div>

                        <div class="ep-pref">
                            <div class="ep-pref-info">
                                <i class="fas fa-hand-pointer ep-pref-icon"></i>
                                <div>
                                    <h6>Uso de mis códigos</h6>
                                    <small>Recibir avisos cuando alguien abre o usa tu código.</small>
                                </div>
                            </div>
                            <label class="ep-switch">
                                <input type="checkbox" name="email_aperturas" id="email_aperturas" <?php if(($data_usuario['email_aperturas'] ?? 1) == 1) echo "checked"; ?>>
                                <span class="ep-slider"></span>
                            </label>
                        </div>

                        <div class="ep-pref">
                            <div class="ep-pref-info">
                                <i class="fas fa-star ep-pref-icon"></i>
                                <div>
                                    <h6>Destacados y renovaciones</h6>
                                    <small>Recibir avisos sobre expiración, renovación y saldo de destacados.</small>
                                </div>
                            </div>
                            <label class="ep-switch">
                                <input type="checkbox" name="email_destacados" id="email_destacados" <?php if(($data_usuario['email_destacados'] ?? 1) == 1) echo "checked"; ?>>
                                <span class="ep-slider"></span>
                            </label>
                        </div>

                        <div class="ep-save-row">
                            <button type="submit" class="ep-btn-save" id="modificar_usuario">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
            
<?php get_footer(); ?>

<style>
/* ═══════════════════════════════════════
   EDIT PROFILE — PREMIUM DARK THEME
   ═══════════════════════════════════════ */
:root {
    --ep-bg: #222222;
    --ep-card: #2a2a2a;
    --ep-card-border: rgba(255,255,255,0.08);
    --ep-input-bg: #333;
    --ep-input-border: rgba(255,255,255,0.12);
    --ep-primary: #E30613;
    --ep-white: #ffffff;
    --ep-gray: #aaa;
    --ep-muted: #777;
}

.ep-page {
    background: var(--ep-bg);
    min-height: 80vh;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    padding-bottom: 60px;
}

/* ── HERO ── */
.ep-hero {
    background: linear-gradient(135deg, #161616 0%, #222222 100%);
    border-radius: 24px;
    margin: 30px auto;
    max-width: 1100px;
    padding: 40px 36px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    border: 1px solid var(--ep-card-border);
    position: relative;
    overflow: hidden;
}
.ep-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--ep-primary), #FF4D4D);
}
.ep-hero-profile {
    display: flex;
    align-items: center;
    gap: 28px;
}

/* Avatar */
.ep-avatar-wrap {
    position: relative;
    width: 130px;
    height: 130px;
    flex-shrink: 0;
}
.ep-avatar-img {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(227,6,19,0.3);
    box-shadow: 0 8px 24px rgba(227,6,19,0.25);
}
.ep-avatar-camera {
    position: absolute;
    bottom: 4px;
    right: 4px;
    z-index: 2;
}
.ep-camera-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--ep-primary);
    border: 3px solid #161616;
    color: #fff;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    padding: 0;
}
.ep-camera-btn:hover {
    transform: scale(1.12);
    box-shadow: 0 4px 14px rgba(227,6,19,0.5);
}

/* Hero info */
.ep-hero-info { flex: 1; }
.ep-hero-name-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
.ep-hero-name {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--ep-white);
    margin: 0;
    line-height: 1.2;
}
.ep-vip-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 14px;
    background: linear-gradient(135deg, #ffd700, #ffb300);
    color: #1a1a1a;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.5px;
}
.ep-trust-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    border: 1px solid;
    line-height: 1;
}
.ep-trust-badge .fa-star { font-size: 0.65rem; }
.ep-hero-email, .ep-hero-member {
    color: var(--ep-gray);
    font-size: 0.95rem;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ep-hero-email i, .ep-hero-member i {
    color: var(--ep-primary);
    width: 16px;
    font-size: 0.85rem;
}

/* ── CONTENT GRID ── */
.ep-content {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 16px;
}
.ep-grid {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 24px;
    align-items: start;
}

/* ── CARDS ── */
.ep-card {
    background: var(--ep-card);
    border-radius: 20px;
    padding: 28px;
    border: 1px solid var(--ep-card-border);
    box-shadow: 0 8px 30px rgba(0,0,0,0.25);
}
.ep-card-label {
    font-size: 0.72rem;
    color: var(--ep-gray);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Potential card */
.ep-potential-card { text-align: center; margin-bottom: 20px; }
.ep-potential-value {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--ep-primary);
    margin: 4px 0 6px;
}
.ep-potential-desc {
    font-size: 0.82rem;
    color: var(--ep-muted);
    margin: 0 0 18px;
}
.ep-btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: linear-gradient(135deg, var(--ep-primary), #FF4D4D);
    color: #fff;
    border: none;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 14px rgba(227,6,19,0.3);
}
.ep-btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(227,6,19,0.4);
    color: #fff;
    text-decoration: none;
}

/* Trust card */
.ep-trust-card { text-align: center; }
.ep-trust-stars { font-size: 1.4rem; margin-bottom: 8px; }
.ep-trust-stars .fa-star, .ep-trust-stars .far { margin: 0 1px; }
.ep-trust-level {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 14px;
}
.ep-trust-progress { margin-bottom: 16px; }
.ep-trust-progress-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.7rem;
    color: var(--ep-muted);
    margin-bottom: 5px;
}
.ep-trust-progress-bar {
    background: rgba(255,255,255,0.08);
    border-radius: 10px;
    height: 7px;
    overflow: hidden;
}
.ep-trust-progress-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.6s ease;
}
.ep-trust-max {
    color: #10b981;
    font-size: 0.8rem;
    margin-bottom: 14px;
}
.ep-trust-checklist { text-align: left; }
.ep-check-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    font-size: 0.82rem;
}
.ep-check-item i { width: 16px; font-size: 0.9rem; flex-shrink: 0; }
.ep-check-label { color: #ccc; flex: 1; }
.ep-check-pts { font-size: 0.72rem; color: var(--ep-muted); }

/* ── FORM ── */
.ep-section-title {
    color: var(--ep-primary);
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 22px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(227,6,19,0.2);
}
.ep-section-title i { font-size: 1rem; }

.ep-field {
    margin-bottom: 20px;
}
.ep-field label {
    display: block;
    color: var(--ep-white);
    font-weight: 600;
    font-size: 0.88rem;
    margin-bottom: 6px;
}
.ep-input-wrap {
    display: flex;
    align-items: center;
    background: var(--ep-input-bg);
    border: 1px solid var(--ep-input-border);
    border-radius: 12px;
    overflow: hidden;
    transition: border-color 0.25s, box-shadow 0.25s;
}
.ep-input-wrap:focus-within {
    border-color: var(--ep-primary);
    box-shadow: 0 0 0 3px rgba(227,6,19,0.12);
}
.ep-input-wrap > i {
    padding: 0 14px;
    color: var(--ep-muted);
    font-size: 0.9rem;
    flex-shrink: 0;
}
.ep-input-wrap:focus-within > i { color: var(--ep-primary); }
.ep-input-wrap input {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--ep-white);
    font-size: 0.95rem;
    padding: 14px 14px 14px 0;
    outline: none;
}
.ep-input-wrap input::placeholder { color: var(--ep-muted); }
.ep-input-disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.ep-input-disabled input { cursor: not-allowed; }
.ep-field-hint {
    display: block;
    font-size: 0.78rem;
    color: var(--ep-muted);
    margin-top: 5px;
    padding-left: 2px;
}

.ep-field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Toggle password */
.ep-toggle-pass {
    background: transparent;
    border: none;
    color: var(--ep-muted);
    padding: 0 14px;
    cursor: pointer;
    font-size: 0.95rem;
    transition: color 0.2s;
}
.ep-toggle-pass:hover { color: var(--ep-white); }

.ep-divider {
    height: 1px;
    background: rgba(255,255,255,0.08);
    margin: 30px 0;
}

/* ── PREFERENCES ── */
.ep-pref {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    margin-bottom: 12px;
    background: rgba(255,255,255,0.025);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 14px;
    transition: background 0.2s;
    gap: 16px;
}
.ep-pref:hover { background: rgba(255,255,255,0.05); }
.ep-pref-info {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 0;
}
.ep-pref-icon {
    color: var(--ep-primary);
    font-size: 1.1rem;
    width: 24px;
    flex-shrink: 0;
    text-align: center;
}
.ep-pref-info h6 {
    color: var(--ep-white);
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 2px;
}
.ep-pref-info small {
    color: var(--ep-muted);
    font-size: 0.78rem;
    line-height: 1.3;
}

/* Toggle switch */
.ep-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
    flex-shrink: 0;
}
.ep-switch input { opacity: 0; width: 0; height: 0; }
.ep-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #444;
    border-radius: 26px;
    transition: 0.3s;
}
.ep-slider::before {
    content: '';
    position: absolute;
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: 0.3s;
}
.ep-switch input:checked + .ep-slider { background: var(--ep-primary); }
.ep-switch input:checked + .ep-slider::before { transform: translateX(22px); }

/* ── SAVE BUTTON ── */
.ep-save-row {
    text-align: center;
    margin-top: 36px;
}
.ep-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 48px;
    background: linear-gradient(135deg, var(--ep-primary), #FF4D4D);
    color: #fff;
    border: none;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 6px 20px rgba(227,6,19,0.35);
}
.ep-btn-save:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(227,6,19,0.45);
}
.ep-btn-save:active { transform: translateY(0); }

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
    .ep-grid {
        grid-template-columns: 1fr;
    }
    .ep-hero {
        margin: 15px;
        padding: 28px 20px;
        border-radius: 18px;
    }
    .ep-hero-profile {
        flex-direction: column;
        text-align: center;
    }
    .ep-hero-name-row {
        justify-content: center;
    }
    .ep-hero-email, .ep-hero-member {
        justify-content: center;
    }
    .ep-hero-name {
        font-size: 1.8rem;
    }
    .ep-content {
        padding: 0 15px;
    }
    .ep-field-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}

@media (max-width: 480px) {
    .ep-hero {
        margin: 10px;
        padding: 22px 16px;
    }
    .ep-avatar-wrap {
        width: 100px;
        height: 100px;
    }
    .ep-avatar-img {
        width: 100px;
        height: 100px;
    }
    .ep-hero-name {
        font-size: 1.5rem;
    }
    .ep-card {
        padding: 20px 16px;
        border-radius: 16px;
    }
    .ep-btn-save {
        width: 100%;
        justify-content: center;
        padding: 15px 30px;
    }
}
</style>

<script>
    $(document).ready(function() {
        // Toggle password visibility
        $('.ep-toggle-pass').click(function() {
            var input = $('#pass');
            var icon = $(this).find('i');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

    	$("#eliminar_foto").click(function() {  
    	    if(confirm("¿Estás seguro de eliminar tu foto de perfil?")) {
        		$.post("/remove_photo_user", { 'mail' : $('#correo').val()});
        		location.reload();
    	    }
    	});

    	$("#editar_perfil").submit(function(event) {
    		event.preventDefault();
    		
    		var btn = $("#modificar_usuario");
    		var originalText = btn.html();
    		btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

    		var notis_val = $("#notis").is(":checked") ? '1' : '0';
    		var email_competencia_val = $("#email_competencia").is(":checked") ? '1' : '0';
    		var email_aperturas_val = $("#email_aperturas").is(":checked") ? '1' : '0';
    		var email_destacados_val = $("#email_destacados").is(":checked") ? '1' : '0';

    		$.ajax({
    			type: "POST",
    			url: "/myphp/ajax_actions.php",
    			data: {
    				metodo: "editar_perfil",
    				notis: notis_val,
    				email_competencia: email_competencia_val,
    				email_aperturas: email_aperturas_val,
    				email_destacados: email_destacados_val,
    				nombre: $("#nombre").val(),
    				correo: $("#correo").val(),
    				password: $("#pass").val(),
    				telefono: $("#telefono").val(),
    				whatsapp: $("#whatsapp").val(),
    			}, 
    			cache: false,
    			dataType: 'json',
    			success: function(data){
    			    btn.prop('disabled', false).html(originalText);
    				if (data && data.success) {
    					alert(data.message || "Usuario modificado correctamente");
    					location.reload();
    				} else {
    					alert(data.message || "Error al modificar el usuario");
    				}
    			},
    			error: function(xhr, status, error) {
    			    btn.prop('disabled', false).html(originalText);
    				try {
    					var response = JSON.parse(xhr.responseText);
    					alert(response.message || "Error al modificar");
    				} catch(e) {
    					alert("Error de conexión. Inténtelo de nuevo.");
    				}
    			}
    		});
    	});
    	
        $("#uploadedfile").change(function() {
            if (this.files && this.files[0]) {
                if (this.files[0].size > 2000000) {
                    alert("El archivo es demasiado grande. Máximo 2MB.");
                    $(this).val('');
                    return;
                }
                var fileType = this.files[0].type;
                if (!fileType.match('image.*')) {
                    alert("Solo se permiten imágenes (JPG, PNG).");
                    $(this).val('');
                    return;
                }
                $('.dropdown-menu').removeClass('show');
                $("#form_cambiar_foto").submit();
            }
        });
    });
</script>