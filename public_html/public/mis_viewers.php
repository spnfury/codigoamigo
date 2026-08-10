<?php
/**
 * Panel de Leads - VIP Feature
 * Muestra usuarios registrados que han visto los códigos del usuario actual
 * Replanteado como "Mis Leads" con métricas útiles y diseño dark mode
 */

require_once __DIR__ . '/../inc/includes.php';
require_once __DIR__ . '/../myphp/funciones_usuario.php';

// Anular publicidad en esta página
$anula_adsense = true;

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión de usuario
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /login?redirect=/public/mis_viewers.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_vip = es_usuario_vip($user_id);

// Datos usuario para panel "Mi suscripción"
$usuario = getCollectionUsuarios()->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
$vip_cancel_pending = $is_vip && !empty($usuario['vip_cancel_pending']);
$vip_retention_used = !empty($usuario['vip_retention_applied']);
$vip_expires_fmt = '';
if (isset($usuario['vip_expires_at']) && $usuario['vip_expires_at'] instanceof MongoDB\BSON\UTCDateTime) {
    $vip_expires_fmt = $usuario['vip_expires_at']->toDateTime()->format('d/m/Y');
}
$saldo_fmt = number_format($usuario['saldo'] ?? 0, 2, ',', '.');

// Obtener datos de viewers/leads
$viewers_data = obtener_viewers_usuario($user_id);
$viewers = $viewers_data['viewers'] ?? [];
$total_viewers = $viewers_data['total_viewers'] ?? 0;
$total_potencial = $viewers_data['total_potencial'] ?? 0;

// Prueba social: nº de publicadores VIP activos (para upsell)
$total_vips_activos = 0;
try {
    $coll_u = getCollectionUsuarios();
    if ($coll_u) {
        $total_vips_activos = $coll_u->countDocuments([
            'is_vip' => true,
            'vip_expires_at' => ['$gt' => new MongoDB\BSON\UTCDateTime()]
        ]);
    }
} catch (Throwable $e) {
    log_error("No se pudo contar VIPs activos en mis_viewers: " . $e->getMessage());
}

// Calcular métricas extra
$codigos_vistos_ids = [];
$codigo_counts = [];
$leads_nuevos = 0;
$ahora = time();
$siete_dias = 7 * 24 * 60 * 60;

foreach ($viewers as &$viewer) {
    // Contar códigos distintos
    $cid = $viewer['codigo_id'] ?? '';
    if ($cid && !in_array($cid, $codigos_vistos_ids)) {
        $codigos_vistos_ids[] = $cid;
    }
    
    // Contar vistas por código para encontrar el top
    if ($cid) {
        if (!isset($codigo_counts[$cid])) {
            $codigo_counts[$cid] = ['count' => 0, 'marca' => $viewer['codigo_marca'] ?? 'Desconocida'];
        }
        $codigo_counts[$cid]['count']++;
    }
    
    // Contar leads nuevos (últimos 7 días)
    if (isset($viewer['viewed_at'])) {
        $viewed_ts = null;
        if ($viewer['viewed_at'] instanceof MongoDB\BSON\UTCDateTime) {
            $viewed_ts = $viewer['viewed_at']->toDateTime()->getTimestamp();
        }
        if ($viewed_ts && ($ahora - $viewed_ts) < $siete_dias) {
            $leads_nuevos++;
            $viewer['is_new'] = true;
        } else {
            $viewer['is_new'] = false;
        }
        
        // Generar timestamp relativo
        if ($viewed_ts) {
            $diff = $ahora - $viewed_ts;
            if ($diff < 3600) {
                $viewer['tiempo_relativo'] = 'Hace ' . max(1, floor($diff / 60)) . ' min';
            } elseif ($diff < 86400) {
                $viewer['tiempo_relativo'] = 'Hace ' . floor($diff / 3600) . 'h';
            } elseif ($diff < 172800) {
                $viewer['tiempo_relativo'] = 'Ayer';
            } elseif ($diff < 604800) {
                $viewer['tiempo_relativo'] = 'Hace ' . floor($diff / 86400) . ' días';
            } else {
                $viewer['tiempo_relativo'] = $viewer['viewed_at']->toDateTime()->format('d/m/Y');
            }
        } else {
            $viewer['tiempo_relativo'] = '';
        }
    } else {
        $viewer['is_new'] = false;
        $viewer['tiempo_relativo'] = '';
    }
}
unset($viewer);

$total_codigos_vistos = count($codigos_vistos_ids);

// Contar tabs
$count_conseguidos = 0;
$count_activos = 0;
$count_no_contactados = 0;
// Beneficio realmente cobrado, separado del que aún está por cerrar.
// Antes la cabecera solo enseñaba $total_potencial (la suma de TODOS los leads
// como si fueran a convertir). En todo el sitio hay 1 lead completado sobre
// 4.238, así que esa cifra prometía cientos de euros a quien había ganado 5.
$total_ganado = 0;
$total_en_juego = 0;
foreach ($viewers as $v) {
    $beneficio = (float)($v['codigo_beneficio'] ?? 0);
    if (!empty($v['completado'])) {
        $count_conseguidos++;
        $total_ganado += $beneficio;
    } else {
        $count_activos++;
        $total_en_juego += $beneficio;
        if (empty($v['contacted'])) {
            $count_no_contactados++;
        }
    }
}

/** Formato español: 2.827€, no 2,827€ (number_format por defecto usa el inglés). */
function fmt_eur($n) {
    return number_format((float)$n, 0, ',', '.') . '€';
}

// Encontrar código top
$codigo_top_marca = '—';
$codigo_top_count = 0;
if (!empty($codigo_counts)) {
    arsort($codigo_counts);
    $top = reset($codigo_counts);
    $codigo_top_marca = ucfirst($top['marca']);
    $codigo_top_count = $top['count'];
}

// Header
$title = "Mis Leads | Código Amigo";
$description = "Usuarios interesados en tus códigos de referido";
get_header_modern($title, $description, '', '', '', true);
?>

<style>
/* === MIS LEADS - DARK MODE DESIGN === */
.leads-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 30px 20px 60px;
    background: #0f0f1e;
    color: #fff;
    box-shadow: 0 0 0 100vmax #0f0f1e;
    clip-path: inset(0 -100vmax);
    position: relative;
}

/* --- Header ---
   Ocupaba 71px con un título de 2rem y un subtítulo que explica lo evidente a
   partir de la segunda visita. Se reduce para que la lista de leads —que es lo
   que se viene a ver— entre antes en pantalla. */
.leads-header {
    margin-bottom: 16px;
}

.leads-title {
    font-size: 1.55rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.leads-title i {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.leads-subtitle {
    color: rgba(255,255,255,0.45);
    font-size: 0.85rem;
    margin: 0;
}

/* --- Resumen compacto --- */
.leads-resumen {
    display: flex;
    align-items: stretch;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 24px;
}
.lead-res-item {
    flex: 1 1 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    padding: 14px 8px;
    text-align: center;
    background: transparent;
    border: none;
    border-right: 1px solid rgba(255,255,255,0.07);
    font-family: inherit;
    min-width: 0;
}
.lead-res-item:last-child { border-right: none; }
.lead-res-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
    white-space: nowrap;
}
.lead-res-label {
    font-size: 0.72rem;
    color: rgba(255,255,255,0.45);
    text-transform: uppercase;
    letter-spacing: 0.4px;
    font-weight: 600;
    line-height: 1.3;
}
.lead-res-label em {
    display: block;
    font-style: normal;
    text-transform: none;
    letter-spacing: 0;
    font-weight: 500;
    font-size: 0.68rem;
    color: rgba(255,255,255,0.3);
    margin-top: 2px;
}
/* El único color de la tira es el de lo que pide acción, y es el único
   elemento pulsable: al tocarlo filtra la lista. */
.lead-res-accion { cursor: pointer; }
.lead-res-accion .lead-res-value { color: #ffd700; }
.lead-res-accion .lead-res-label { color: rgba(255,215,0,0.7); }
.lead-res-accion:hover { background: rgba(255,215,0,0.07); }
.lead-res-accion:active { background: rgba(255,215,0,0.12); }

@media (max-width: 380px) {
    .lead-res-value { font-size: 1.3rem; }
    .lead-res-item { padding: 12px 6px; }
}


/* --- Section Title --- */
.leads-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}

.leads-section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
}

.leads-section-title .count-badge {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}

.select-all-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.select-all-wrapper label {
    color: rgba(255,255,255,0.5);
    font-size: 0.85rem;
    cursor: pointer;
}

.select-all-wrapper input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #667eea;
}

/* --- Lead Cards --- */
.lead-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
}

.lead-card:hover {
    border-color: rgba(102, 126, 234, 0.3);
    background: rgba(255,255,255,0.05);
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.1);
}

.lead-card.is-new {
    border-left: 3px solid #4facfe;
}

.lead-card.is-completed {
    opacity: 0.75;
    border-left: 3px solid #10b981;
}

.lead-card.is-completed-hidden {
    display: none !important;
}

.completed-icon-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    color: #10b981;
    background: #1a1a2e;
    border-radius: 50%;
    font-size: 0.95rem;
    padding: 2px;
}

/* Tabs de filtro */
.leads-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.leads-tab {
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.7);
    border: 1px solid rgba(255,255,255,0.08);
    padding: 7px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.leads-tab:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}
.leads-tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-color: transparent;
}
.leads-tab .tab-count {
    background: rgba(0,0,0,0.25);
    padding: 1px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
}
.leads-tab.active .tab-count {
    background: rgba(255,255,255,0.25);
}
.leads-tab[data-tab="conseguidos"].active {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}
.leads-tab[data-tab="no_contactados"].active {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}
.lead-card.filtered-out { display: none !important; }

/* Panel Mi Suscripción (VIP) */
.sub-panel {
    background: linear-gradient(135deg, rgba(212,160,23,0.12) 0%, rgba(102,126,234,0.08) 100%);
    border: 1px solid rgba(255,215,0,0.25);
    border-radius: 14px;
    padding: 18px 22px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}
.sub-panel.pending { border-color: rgba(245,158,11,0.45); background: linear-gradient(135deg, rgba(245,158,11,0.15) 0%, rgba(220,38,38,0.08) 100%); }
.sub-panel-info { display: flex; flex-direction: column; gap: 4px; }
.sub-panel-title { color: #fff; font-weight: 700; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
.sub-panel-title i { color: #ffd700; }
.sub-panel-meta { color: rgba(255,255,255,0.6); font-size: 0.82rem; display: flex; gap: 14px; flex-wrap: wrap; }
.sub-panel-meta strong { color: rgba(255,255,255,0.9); font-weight: 600; }
.sub-panel-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.sub-btn {
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.85);
    border: 1px solid rgba(255,255,255,0.12);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    text-decoration: none;
}
.sub-btn:hover { background: rgba(255,255,255,0.14); color: #fff; }
.sub-btn.danger:hover { background: rgba(220,38,38,0.2); border-color: rgba(220,38,38,0.4); color: #fecaca; }
.sub-btn.primary { background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); color: #fff; border-color: transparent; }
.sub-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(227,6,19,0.3); color: #fff; }

/* FAQ accordion (upsell no-VIP) */
.upsell-faq { margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; }
.upsell-faq-item {
    border-bottom: 1px solid rgba(255,255,255,0.08);
    padding: 12px 0;
    cursor: pointer;
}
.upsell-faq-item:last-child { border-bottom: none; }
.upsell-faq-q {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    color: rgba(255,255,255,0.85);
    font-weight: 600;
    font-size: 0.92rem;
}
.upsell-faq-q i { transition: transform 0.2s; font-size: 0.75rem; }
.upsell-faq-item.open .upsell-faq-q i { transform: rotate(180deg); }
.upsell-faq-a {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.25s ease;
    color: rgba(255,255,255,0.6);
    font-size: 0.88rem;
    line-height: 1.55;
}
.upsell-faq-item.open .upsell-faq-a { max-height: 300px; margin-top: 10px; }

.lead-checkbox {
    flex-shrink: 0;
}

.lead-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #667eea;
}

.lead-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    font-weight: 700;
    flex-shrink: 0;
    position: relative;
}

.lead-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.new-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #4facfe;
    color: #fff;
    font-size: 0.55rem;
    font-weight: 800;
    padding: 2px 5px;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(79, 172, 254, 0.5);
}

.lead-info {
    flex: 1;
    min-width: 0;
}

.lead-name {
    font-weight: 700;
    color: #fff;
    font-size: 1rem;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.lead-meta {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.4);
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.lead-meta i { margin-right: 3px; }

.lead-meta .marca-tag {
    color: #667eea;
    font-weight: 600;
}

.lead-potential {
    text-align: right;
    flex-shrink: 0;
}

.lead-potential-amount {
    font-size: 1.3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.lead-potential-label {
    font-size: 0.72rem;
    color: rgba(255,255,255,0.35);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.lead-actions {
    flex-shrink: 0;
}

.btn-lead-contact {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 9px 18px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    white-space: nowrap;
}

.btn-lead-contact:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.btn-lead-contact.contacted {
    background: rgba(102, 126, 234, 0.15);
    color: #8c9eff;
    border: 1px solid rgba(102, 126, 234, 0.3);
    cursor: pointer;
}

.btn-lead-contact.contacted:hover {
    background: rgba(102, 126, 234, 0.25);
    color: #fff;
    transform: translateY(-2px);
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.btn-lead-vip-upsell {
    background: linear-gradient(135deg, #ffd700 0%, #E30613 100%);
    color: white;
    border: none;
    padding: 9px 18px;
    border-radius: 25px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    white-space: nowrap;
}

.btn-lead-vip-upsell:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(227, 6, 19, 0.4);
    color: white;
    text-decoration: none;
}

/* --- Mass Message Button --- */
.btn-mass-message {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 22px;
    border-radius: 25px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-mass-message:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-mass-message:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* --- VIP Upsell Block --- */
.leads-vip-upsell {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255, 215, 0, 0.15);
    border-radius: 20px;
    padding: 35px;
    text-align: center;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.leads-vip-upsell::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #ffd700, #E30613);
}

.leads-vip-upsell h3 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 10px;
}

.leads-vip-upsell h3 i { color: #ffd700; }

.leads-vip-upsell p {
    font-size: 1rem;
    color: rgba(255,255,255,0.6);
    max-width: 500px;
    margin: 0 auto 25px;
    line-height: 1.6;
}

.btn-upgrade-vip {
    background: linear-gradient(135deg, #ffd700 0%, #E30613 100%);
    color: white;
    border: none;
    padding: 14px 35px;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.btn-upgrade-vip:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(255, 140, 0, 0.4);
    color: white;
    text-decoration: none;
}

/* --- Empty State --- */
.leads-empty {
    text-align: center;
    padding: 60px 20px;
    background: rgba(255,255,255,0.02);
    border: 1px dashed rgba(255,255,255,0.08);
    border-radius: 20px;
}

.leads-empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.leads-empty-icon i {
    font-size: 32px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.leads-empty h3 {
    color: #fff;
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.leads-empty p {
    color: rgba(255,255,255,0.4);
    max-width: 400px;
    margin: 0 auto 25px;
    line-height: 1.6;
    font-size: 0.95rem;
}

.btn-share-codes {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-share-codes:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

/* --- Responsive --- */
@media (max-width: 768px) {
    .lead-card {
        flex-direction: column;
        text-align: center;
        gap: 12px;
        padding: 20px;
    }
    
    .lead-potential {
        text-align: center;
    }
    
    .lead-actions {
        width: 100%;
    }
    
    .lead-actions a,
    .lead-actions button {
        width: 100%;
        justify-content: center;
    }
    
    .lead-meta {
        justify-content: center;
    }
    
    .leads-section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .leads-title {
        font-size: 1.6rem;
    }
}

/* --- VIP Modal --- */
#modal-vip-upgrade .modal-content {
    background: #1a1a1a;
    color: white;
    border-radius: 15px;
    overflow: hidden;
    border: 1px solid #333;
}
#modal-vip-upgrade .modal-header {
    border-bottom: 1px solid #333;
    background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%);
    padding: 20px;
}
#modal-vip-upgrade .modal-title {
    color: #ffd700;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
}
.vip-feature-list {
    list-style: none;
    padding: 0;
    margin: 20px 0;
    text-align: left;
}
.vip-feature-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 15px;
    font-size: 15px;
}
.vip-feature-item i {
    color: #ffd700;
    margin-top: 4px;
}
.btn-upgrade-now {
    background: linear-gradient(135deg, #ffd700 0%, #E30613 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 30px;
    font-weight: bold;
    font-size: 18px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    margin-top: 10px;
    box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
}
.btn-upgrade-now:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(227, 6, 19, 0.5);
    color: white;
    text-decoration: none;
}

/* Blur preview para no-VIP */
.blur-vip {
    filter: blur(6px);
    user-select: none;
    pointer-events: none;
    transition: filter 0.2s ease;
}
.lead-card.locked {
    position: relative;
    cursor: pointer;
}
.lead-card.locked .lead-avatar {
    position: relative;
}
.lead-card.locked .lead-avatar::after {
    content: "\f023"; /* fa-lock */
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #ffd700;
    font-size: 1.1rem;
    text-shadow: 0 2px 6px rgba(0,0,0,0.6);
    pointer-events: none;
    z-index: 2;
}
.lead-card.locked:hover .blur-vip {
    filter: blur(7px);
}
.lead-card.locked:hover {
    outline: 1px solid rgba(255,215,0,0.35);
}
</style>

<div class="leads-page">
    <!-- Header -->
    <div class="leads-header">
        <h1 class="leads-title">
            <i class="fas fa-crosshairs"></i>
            Mis Leads
            <?php if ($is_vip): ?>
                <span class="vip-badge-gold"><i class="fas fa-crown"></i> VIP</span>
            <?php endif; ?>
        </h1>
        <p class="leads-subtitle">
            Usuarios registrados interesados en tus códigos de referido
        </p>
    </div>
    
    <!-- Resumen.
         Antes eran cuatro tarjetas en rejilla que ocupaban 292px de alto en
         móvil (el 67% de la primera pantalla junto con el título) para enseñar
         cuatro números que no llevaban a ninguna acción: leads totales, códigos
         vistos, potencial y código top. Encima tenían :hover con elevación y
         sombra, así que parecían pulsables sin serlo.
         Ahora es una tira de ~72px con lo que sí cambia lo que haces: cuántos
         quedan sin contactar (que además filtra la lista al tocarlo) y cuánto
         se ha cobrado de verdad. -->
    <div class="leads-resumen">
        <div class="lead-res-item">
            <span class="lead-res-value"><?php echo (int)$total_viewers; ?></span>
            <span class="lead-res-label">Leads</span>
        </div>

        <?php if ($count_no_contactados > 0): ?>
        <button type="button" class="lead-res-item lead-res-accion" onclick="filterLeads('no_contactados')"
                title="Ver solo los leads que aún no has contactado">
            <span class="lead-res-value"><?php echo (int)$count_no_contactados; ?></span>
            <span class="lead-res-label">Sin contactar</span>
        </button>
        <?php else: ?>
        <div class="lead-res-item">
            <span class="lead-res-value"><?php echo (int)$count_activos; ?></span>
            <span class="lead-res-label">Activos</span>
        </div>
        <?php endif; ?>

        <div class="lead-res-item">
            <span class="lead-res-value"><?php echo fmt_eur($total_ganado); ?></span>
            <span class="lead-res-label">
                Ganado
                <?php if ($total_en_juego > 0): ?>
                    <em><?php echo fmt_eur($total_en_juego); ?> en juego</em>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <?php if ($is_vip): ?>
    <!-- Panel Mi Suscripción (VIP) -->
    <div class="sub-panel <?php echo $vip_cancel_pending ? 'pending' : ''; ?>">
        <div class="sub-panel-info">
            <div class="sub-panel-title">
                <i class="fas fa-crown"></i>
                <?php if ($vip_cancel_pending): ?>
                    Suscripción VIP — Cancelación programada
                <?php else: ?>
                    Suscripción VIP activa
                <?php endif; ?>
            </div>
            <div class="sub-panel-meta">
                <?php if ($vip_cancel_pending): ?>
                    <span>Finaliza: <strong><?php echo $vip_expires_fmt ?: '—'; ?></strong></span>
                <?php else: ?>
                    <span>Próxima renovación: <strong><?php echo $vip_expires_fmt ?: '—'; ?></strong></span>
                <?php endif; ?>
                <span>Saldo: <strong><?php echo $saldo_fmt; ?>€</strong></span>
            </div>
        </div>
        <div class="sub-panel-actions">
            <?php if ($vip_cancel_pending): ?>
                <button class="sub-btn primary" id="btnReactivate"><i class="fas fa-undo"></i> Reactivar</button>
                <?php if (!$vip_retention_used): ?>
                <button class="sub-btn" id="btnRetention" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; border-color:transparent;"><i class="fas fa-gift"></i> Quedarme por 4,99€</button>
                <?php endif; ?>
            <?php else: ?>
                <button class="sub-btn danger" id="btnCancelVip"><i class="fas fa-times"></i> Cancelar</button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$is_vip): ?>
    <!-- VIP Upsell (visible también sin leads: antes exigía $total_viewers > 0
         y un usuario sin leads no tenía ninguna vía para suscribirse) -->
    <div class="leads-vip-upsell">
        <?php $leads_urgencia = $count_no_contactados > 0 ? $count_no_contactados : $total_viewers; ?>
        <?php if ($total_viewers > 0): ?>
        <h3><i class="fas fa-crown"></i> Tienes <?php echo $leads_urgencia; ?> <?php echo $count_no_contactados > 0 ? 'leads sin contactar' : 'leads esperando'; ?></h3>
        <?php else: ?>
        <h3><i class="fas fa-crown"></i> Saca el máximo partido a tus códigos</h3>
        <?php endif; ?>
        <p>
            <?php if ($total_potencial > 0): ?>
            Hasta <strong><?php echo number_format($total_potencial, 0); ?>€</strong> en beneficios potenciales esperándote.
            <?php endif; ?>
            <?php if ($total_viewers > 0): ?>
            Hazte VIP para escribir directamente a los usuarios que han visto tus códigos y ayudarles a completar el proceso. Ambos ganáis.
            <?php else: ?>
            Hazte VIP y podrás escribir directamente a los usuarios que vean tus códigos para ayudarles a completar el proceso. Ambos ganáis.
            <?php endif; ?>
        </p>
        <button class="btn-upgrade-vip" id="btnSubscribeVip">
            <i class="fas fa-bolt"></i>
            Primer mes 4,99€ <span style="opacity:.75; font-weight:400;">(luego 9,99€/mes)</span>
        </button>
        <?php if ($total_vips_activos >= 3): ?>
        <p style="margin-top:12px; font-size:.85rem; opacity:.8;">
            <i class="fas fa-users" style="color:#ffd700;"></i>
            Ya hay <strong><?php echo $total_vips_activos; ?></strong> publicadores VIP contactando a sus leads.
        </p>
        <?php endif; ?>

        <div class="upsell-faq">
            <div class="upsell-faq-item" onclick="this.classList.toggle('open')">
                <div class="upsell-faq-q"><span>¿Qué incluye la suscripción VIP?</span><i class="fas fa-chevron-down"></i></div>
                <div class="upsell-faq-a">Badge VIP dorado en tu perfil y códigos · Chat ilimitado con viewers · Mensajes masivos · +10€ de saldo gratis cada mes · Códigos destacados con borde dorado.</div>
            </div>
            <div class="upsell-faq-item" onclick="this.classList.toggle('open')">
                <div class="upsell-faq-q"><span>¿Puedo cancelar cuando quiera?</span><i class="fas fa-chevron-down"></i></div>
                <div class="upsell-faq-a">Sí. Cancelas desde esta misma página en cualquier momento. Mantienes los beneficios VIP hasta el final del periodo ya pagado.</div>
            </div>
            <div class="upsell-faq-item" onclick="this.classList.toggle('open')">
                <div class="upsell-faq-q"><span>¿Cuándo recibo los 10€ de saldo?</span><i class="fas fa-chevron-down"></i></div>
                <div class="upsell-faq-a">Automáticamente al activar la suscripción y cada mes al renovar. Úsalo para destacar tus códigos o cualquier promoción.</div>
            </div>
            <div class="upsell-faq-item" onclick="this.classList.toggle('open')">
                <div class="upsell-faq-q"><span>¿Cómo funcionan los leads?</span><i class="fas fa-chevron-down"></i></div>
                <div class="upsell-faq-a">Cada usuario registrado que ve tu código queda registrado como lead. Como VIP puedes contactarle directamente para ayudarle a completar el proceso — ambos ganáis el beneficio.</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Leads List -->
    <div class="leads-section-header" style="flex-direction:column; align-items:stretch; gap:15px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div class="leads-section-title">
                Leads
            </div>
            <?php if ($is_vip && $count_activos > 1): ?>
            <button class="btn-mass-message" onclick="openMassMessageModal()" id="btnMassMessage" disabled>
                <i class="fas fa-paper-plane"></i>
                Enviar a seleccionados
            </button>
            <?php endif; ?>
        </div>

        <?php if ($total_viewers > 0): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div class="leads-tabs" role="tablist">
                <button type="button" class="leads-tab active" data-tab="activos" onclick="filterLeads('activos')">
                    <i class="fas fa-bolt"></i> Activos <span class="tab-count"><?php echo $count_activos; ?></span>
                </button>
                <?php if ($count_no_contactados > 0): ?>
                <button type="button" class="leads-tab" data-tab="no_contactados" onclick="filterLeads('no_contactados')">
                    <i class="fas fa-user-clock"></i> Sin contactar <span class="tab-count"><?php echo $count_no_contactados; ?></span>
                </button>
                <?php endif; ?>
                <?php if ($count_conseguidos > 0): ?>
                <button type="button" class="leads-tab" data-tab="conseguidos" onclick="filterLeads('conseguidos')">
                    <i class="fas fa-check-circle"></i> Conseguidos <span class="tab-count"><?php echo $count_conseguidos; ?></span>
                </button>
                <?php endif; ?>
                <button type="button" class="leads-tab" data-tab="todos" onclick="filterLeads('todos')">
                    Todos <span class="tab-count"><?php echo $total_viewers; ?></span>
                </button>
            </div>

            <?php if ($is_vip && $count_activos > 0): ?>
            <div class="select-all-wrapper" id="selectAllWrapper">
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                <label for="selectAll">Seleccionar todos</label>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (empty($viewers)): ?>
    <!-- Empty State -->
    <div class="leads-empty">
        <div class="leads-empty-icon">
            <i class="fas fa-crosshairs"></i>
        </div>
        <h3>Aún no tienes leads</h3>
        <p>
            Cuando usuarios registrados vean tus códigos, aparecerán aquí como oportunidades de contacto. 
            ¡Comparte tus códigos para empezar!
        </p>
        <a href="/mis-anuncios" class="btn-share-codes">
            <i class="fas fa-share-alt"></i> Ir a mis códigos
        </a>
    </div>
    <?php else: ?>
        <?php foreach ($viewers as $viewer): ?>
        <?php
            $locked_click = '';
            if (!$is_vip) {
                $u_esc = htmlspecialchars(addslashes($viewer['viewer_username']), ENT_QUOTES);
                $m_esc = htmlspecialchars(addslashes($viewer['codigo_marca']), ENT_QUOTES);
                $b_esc = number_format($viewer['codigo_beneficio'], 0, ',', '.');
                $t_esc = htmlspecialchars(addslashes($viewer['tiempo_relativo'] ?? 'hace poco'), ENT_QUOTES);
                $locked_click = 'onclick="openVipModal(\'' . $u_esc . '\', \'' . $m_esc . '\', \'' . $b_esc . '\', \'' . $t_esc . '\')"';
            }
        ?>
        <div class="lead-card <?php echo $viewer['is_new'] && empty($viewer['completado']) ? 'is-new' : ''; ?> <?php echo !empty($viewer['completado']) ? 'is-completed' : ''; ?> <?php echo empty($viewer['contacted']) && empty($viewer['completado']) ? 'is-not-contacted' : ''; ?> <?php echo !$is_vip ? 'locked' : ''; ?>" <?php echo $locked_click; ?>>
            <?php if($is_vip): ?>
            <div class="lead-checkbox">
                <input type="checkbox" class="viewer-checkbox" value="<?php echo htmlspecialchars($viewer['viewer_id']); ?>" onchange="updateMassButton()">
            </div>
            <?php endif; ?>

            <div class="lead-avatar">
                <div class="<?php echo !$is_vip ? 'blur-vip' : ''; ?>" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:inherit;overflow:hidden;">
                    <?php if (!empty($viewer['viewer_img'])): ?>
                        <img src="<?php echo htmlspecialchars($viewer['viewer_img']); ?>" alt="Avatar">
                    <?php else: ?>
                        <?php echo strtoupper(substr($viewer['viewer_username'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <?php if ($viewer['is_new'] && empty($viewer['completado'])): ?>
                    <span class="new-badge">new</span>
                <?php endif; ?>
                <?php if (!empty($viewer['completado'])): ?>
                    <span class="completed-icon-badge" title="Conseguido"><i class="fas fa-check-circle"></i></span>
                <?php endif; ?>
            </div>

            <div class="lead-info">
                <div class="lead-name">
                    <span class="<?php echo !$is_vip ? 'blur-vip' : ''; ?>">
                        <?php echo htmlspecialchars(!$is_vip ? str_repeat('█', min(12, max(6, strlen($viewer['viewer_username'])))) : $viewer['viewer_username']); ?>
                    </span>
                </div>
                <div class="lead-meta">
                    <span><i class="fas fa-tag"></i> <span class="marca-tag"><?php echo htmlspecialchars($viewer['codigo_marca']); ?></span></span>
                    <?php if ($viewer['tiempo_relativo']): ?>
                    <span><i class="fas fa-clock"></i> <?php echo $viewer['tiempo_relativo']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="lead-potential">
                <div class="lead-potential-amount">+<?php echo number_format($viewer['codigo_beneficio'], 0); ?>€</div>
                <div class="lead-potential-label">beneficio</div>
            </div>
            
            <div class="lead-actions">
                <?php if ($is_vip): ?>
                    <?php if ($viewer['contacted']): ?>
                        <a href="/public/chat_usuario.php?open_chat=<?php echo urlencode($viewer['viewer_id']); ?>&codigo_id=<?php echo urlencode($viewer['codigo_id']); ?>" class="btn-lead-contact contacted">
                            <i class="fas fa-comments"></i> Abrir Chat
                        </a>
                    <?php else: ?>
                        <a href="/public/chat_usuario.php?open_chat=<?php echo urlencode($viewer['viewer_id']); ?>&codigo_id=<?php echo urlencode($viewer['codigo_id']); ?>&msg=<?php echo urlencode('¡Hola! Vi que te interesó mi código de ' . $viewer['codigo_marca'] . '. ¿Necesitas ayuda?'); ?>" class="btn-lead-contact">
                            <i class="fas fa-comment"></i> Contactar
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <?php
                    $no_vip_default_msg = '¡Hola! Vi que te interesó mi código de ' . $viewer['codigo_marca'] . '. ¿Necesitas ayuda?';
                    $no_vip_codigo_ctx = [
                        'codigoId' => $viewer['codigo_id'] ?? '',
                        'marcaSlug' => $viewer['codigo_marca_slug'] ?? '',
                        'marcaNombre' => $viewer['codigo_marca'] ?? '',
                        'beneficio' => (int)($viewer['codigo_beneficio'] ?? 0)
                    ];
                    ?>
                    <button class="btn-lead-contact" title="Enviar mensaje (responder requiere VIP)" onclick='openChatModal(<?php echo json_encode((string)$viewer['viewer_id']); ?>, <?php echo json_encode($viewer['viewer_username']); ?>, <?php echo json_encode($viewer['viewer_img'] ?? ''); ?>, <?php echo json_encode($no_vip_default_msg); ?>, <?php echo json_encode($no_vip_codigo_ctx); ?>)'>
                        <i class="fas fa-comment"></i> Chatear
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($is_vip && $total_viewers > 1): ?>
<!-- Mass Message Modal -->
<!-- Custom Mass Message Overlay (No dependencies) -->
<div id="massMessageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 1050; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
    <div style="background: #1a1a2e; border-radius: 20px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); width: 90%; max-width: 500px; box-shadow: 0 25px 80px rgba(0,0,0,0.5); animation: vipModalPop 0.3s ease; position: relative;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 18px 25px; position: relative;">
            <h5 style="margin: 0; color: white; font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-paper-plane"></i> Enviar a leads
            </h5>
            <button onclick="document.getElementById('massMessageModal').style.display='none'" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: white; font-size: 28px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <!-- Body -->
        <div style="padding: 25px;">
            <div style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.2); border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <div style="font-size: 22px; color: #667eea;"><i class="fas fa-info-circle"></i></div>
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.85); line-height: 1.4; text-align: left;">
                    Entregaremos este mensaje a <strong style="color: #fff;"><span id="selectedCount">0</span> leads</strong> en tu nombre.
                </div>
            </div>
            
            <div class="form-group">
                <label style="font-weight: 700; color: #fff; margin-bottom: 8px; display: block; text-align: left;">Tu mensaje directo</label>
                <textarea id="massMessageText" class="form-control" rows="5" placeholder="Hola! Vi que te interesa el código. Te puedo ayudar a completar el proceso para que ambos ganemos el beneficio. ¿Te animas?" style="border-radius: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; resize: vertical; margin-bottom: 20px; width: 100%; padding: 12px; font-family: inherit; font-size: 1rem;"></textarea>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 10px;">
                <button onclick="document.getElementById('massMessageModal').style.display='none'" style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.8); border: none; border-radius: 30px; padding: 12px 25px; font-weight: 600; cursor: pointer; transition: background 0.2s;">Cancelar</button>
                <button onclick="sendMassMessage()" style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 30px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: transform 0.2s;">
                    <i class="fas fa-paper-plane"></i> Enviar masivo
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; // fin del modal de mensaje masivo (solo VIP con más de un lead) ?>

<!-- Este script va FUERA del condicional de VIP a propósito.
     Estaba dentro de `if ($is_vip && $total_viewers > 1)`, así que a un usuario
     no VIP no se le definía filterLeads() y los filtros de leads ("Sin
     contactar", "Conseguidos", "Todos") no hacían nada: el onclick lanzaba un
     ReferenceError y la lista se quedaba igual. Justo los usuarios a los que
     hay que convencer para que se hagan VIP.
     Mismo fallo que tuvo el botón Cancelar con SweetAlert el 2026-07-30.
     Las funciones de envío masivo se quedan aquí pero solo se invocan desde
     botones que no existen sin VIP, y toleran que falten sus elementos. -->
<script>
function toggleSelectAll() {
    const selAll = document.getElementById('selectAll');
    if (!selAll) return;
    const isChecked = selAll.checked;
    // Only select visible ones
    const checkboxes = document.querySelectorAll('.lead-card:not(.filtered-out) .viewer-checkbox');
    checkboxes.forEach(cb => cb.checked = isChecked);
    updateMassButton();
}

function filterLeads(tab) {
    document.querySelectorAll('.leads-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
    document.querySelectorAll('.lead-card').forEach(card => {
        const isCompleted = card.classList.contains('is-completed');
        const isNotContacted = card.classList.contains('is-not-contacted');
        let show = true;
        if (tab === 'activos') show = !isCompleted;
        else if (tab === 'no_contactados') show = isNotContacted;
        else if (tab === 'conseguidos') show = isCompleted;
        card.classList.toggle('filtered-out', !show);
        if (!show) {
            const cb = card.querySelector('.viewer-checkbox');
            if (cb) cb.checked = false;
        }
    });
    // Ocultar "seleccionar todos" en tab conseguidos (acciones masivas no aplican)
    const selWrap = document.getElementById('selectAllWrapper');
    if (selWrap) selWrap.style.display = tab === 'conseguidos' ? 'none' : '';
    const selAll = document.getElementById('selectAll');
    if (selAll) selAll.checked = false;
    localStorage.setItem('leadsActiveTab', tab);
    updateMassButton();
}

document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('leadsActiveTab') || 'activos';
    const btn = document.querySelector('.leads-tab[data-tab="' + saved + '"]');
    if (btn) filterLeads(saved);
});

function updateMassButton() {
    const selected = document.querySelectorAll('.viewer-checkbox:checked').length;
    const btn = document.getElementById('btnMassMessage');
    const countSpan = document.getElementById('selectedCount');
    
    if (btn) {
        btn.disabled = selected === 0;
        btn.innerHTML = `<i class="fas fa-paper-plane"></i> Enviar a seleccionados (${selected})`;
    }
    if (countSpan) {
        countSpan.textContent = selected;
    }
}

function openMassMessageModal() {
    const selected = document.querySelectorAll('.viewer-checkbox:checked').length;
    if (selected === 0) return;
    document.getElementById('massMessageModal').style.display = 'flex';
}

async function sendMassMessage() {
    const message = document.getElementById('massMessageText').value.trim();
    if (!message) {
        Swal.fire('Error', 'Escribe un mensaje para enviar', 'warning');
        return;
    }
    
    const selectedIds = Array.from(document.querySelectorAll('.viewer-checkbox:checked')).map(cb => cb.value);
    
    try {
        Swal.fire({
            title: 'Enviando...',
            text: 'Por favor espera mientras enviamos los mensajes.',
            allowOutsideClick: false,
            onBeforeOpen: () => {
                Swal.showLoading()
            }
        });

        const formData = new FormData();
        formData.append('action', 'enviar_masivo');
        formData.append('mensaje', message);
        formData.append('destinatarios', JSON.stringify(selectedIds));
        
        const response = await fetch('/api/chat_api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('massMessageModal').style.display = 'none';
            Swal.fire('¡Enviado!', `Mensaje enviado a ${data.stats.enviados} leads (${data.stats.fallidos} fallidos)`, 'success')
                .then(() => location.reload());
        } else {
            Swal.fire('Error', data.error || 'Error al enviar mensajes', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'Error de conexión', 'error');
    }
}
</script>

<!-- SweetAlert2: lo usan los botones de gestión VIP (cancelar, reactivar,
     retención) y el upsell de cualquier usuario — debe cargarse siempre.
     Antes solo se cargaba dentro del bloque de mensaje masivo
     ($is_vip && $total_viewers > 1) y el botón Cancelar no hacía nada
     para VIPs con 0-1 leads (bug reportado 2026-07-30). -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom VIP Modal Overlay (No dependencies) -->
<div id="custom-vip-modal" class="vipm-overlay">
    <div class="vipm-card">
        <!-- Header -->
        <div class="vipm-head">
            <h5><i class="fas fa-crown"></i> Contacta con tu lead</h5>
            <button type="button" class="vipm-close" onclick="document.getElementById('custom-vip-modal').style.display='none'" aria-label="Cerrar">&times;</button>
        </div>

        <!-- Cuerpo: es lo único que hace scroll -->
        <div class="vipm-body">
            <p class="vipm-hook">
                Estás a un paso de <strong>+<span id="vip-modal-benefit"></span>€</strong>
            </p>
            <p class="vipm-sub">
                <strong id="vip-modal-username"></strong> vio tu código de
                <strong id="vip-modal-brand"></strong> <strong id="vip-modal-time"></strong>.
                Escríbele antes de que busque otra alternativa.
            </p>

            <ul class="vipm-list">
                <li><i class="fas fa-check-circle"></i> Chat ilimitado con tus leads</li>
                <li><i class="fas fa-check-circle"></i> Badge VIP: hasta <strong>+40%</strong> de clics</li>
                <li><i class="fas fa-check-circle"></i> IA ilimitada en tus descripciones</li>
                <li><i class="fas fa-check-circle"></i> <strong>10€</strong> de saldo cada mes</li>
            </ul>
        </div>

        <!-- Pie fijo: el CTA nunca queda fuera de pantalla -->
        <div class="vipm-foot">
            <button id="btnSubscribeMisLeads">
                <span class="btn-text">Hazte VIP · 4,99€ el primer mes</span>
                <span class="spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i></span>
            </button>
            <p class="vipm-legal">Luego 9,99€/mes. Cancelas cuando quieras.</p>
        </div>
    </div>
</div>

<style>
/* Modal VIP.
   Antes el cuerpo medía ~1.500px en móvil y el botón de alta caía por debajo
   del borde de la pantalla: había que adivinar que aún quedaba contenido y
   seguir haciendo scroll para verlo. Ahora la tarjeta es una columna con altura
   máxima; solo el cuerpo hace scroll y el CTA vive en un pie fijo, así que
   siempre se ve. Los textos se recortaron a una línea por ventaja. */
.vipm-overlay, .vipm-overlay * { box-sizing: border-box; }
.vipm-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.85);
    z-index: 1050; justify-content: center; align-items: center;
    backdrop-filter: blur(5px); padding: 16px;
}
.vipm-card {
    display: flex; flex-direction: column;
    background: #1a1a2e; border-radius: 20px; overflow: hidden;
    border: 1px solid rgba(255,255,255,.1);
    width: 100%; max-width: 440px; max-height: 88vh;
    box-shadow: 0 25px 80px rgba(0,0,0,.5); animation: vipModalPop .3s ease;
}
.vipm-head {
    flex: 0 0 auto; position: relative;
    background: linear-gradient(135deg, #ffd700 0%, #E30613 100%);
    padding: 15px 50px 15px 20px;
}
.vipm-head h5 {
    margin: 0; color: #fff; font-weight: 800; font-size: 1.1rem;
    display: flex; align-items: center; gap: 9px;
}
.vipm-close {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    background: transparent; border: none; color: #fff; font-size: 28px;
    cursor: pointer; line-height: 1; padding: 0 6px;
}
.vipm-body { flex: 1 1 auto; overflow-y: auto; padding: 22px 22px 6px; text-align: left; }
.vipm-hook {
    margin: 0 0 6px; text-align: center; font-size: 1.05rem;
    color: rgba(255,255,255,.9);
}
.vipm-hook strong { color: #4ade80; font-size: 1.6rem; display: block; margin-top: 2px; }
.vipm-sub {
    margin: 0 0 18px; text-align: center; font-size: .92rem; line-height: 1.5;
    color: rgba(255,255,255,.65);
}
.vipm-sub strong { color: #fff; }
.vipm-list { list-style: none; padding: 0; margin: 0; }
.vipm-list li {
    display: flex; align-items: center; gap: 10px; padding: 7px 0;
    color: rgba(255,255,255,.85); font-size: .94rem;
}
.vipm-list i { color: #ffd700; font-size: 1rem; flex: 0 0 auto; }
.vipm-list strong { color: #fff; }
.vipm-foot {
    flex: 0 0 auto; padding: 16px 22px 20px;
    border-top: 1px solid rgba(255,255,255,.08); background: #1a1a2e;
}
#btnSubscribeMisLeads {
    display: flex; justify-content: center; align-items: center; gap: 10px;
    width: 100%; padding: 15px 20px; border: none; border-radius: 30px;
    background: linear-gradient(135deg, #ffd700 0%, #E30613 100%);
    color: #fff; font-weight: 700; font-size: 1.05rem; cursor: pointer;
    box-shadow: 0 4px 15px rgba(227,6,19,.4); transition: transform .2s ease;
}
#btnSubscribeMisLeads:hover { transform: translateY(-1px); }
.vipm-legal {
    margin: 10px 0 0; text-align: center;
    color: rgba(255,255,255,.4); font-size: .8rem;
}
@media (max-width: 480px) {
    .vipm-card { max-height: 92vh; }
    .vipm-body { padding: 18px 18px 4px; }
    .vipm-hook strong { font-size: 1.45rem; }
}

@keyframes vipModalPop {
    0% { opacity: 0; transform: scale(0.95); }
    100% { opacity: 1; transform: scale(1); }
}

@keyframes pulseRed {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<script>
function openVipModal(username, brand, benefit, timeAgo) {
    document.getElementById('vip-modal-username').textContent = username;
    document.getElementById('vip-modal-brand').textContent = brand;
    document.getElementById('vip-modal-benefit').textContent = benefit;
    document.getElementById('vip-modal-time').textContent = timeAgo;
    document.getElementById('custom-vip-modal').style.display = 'flex';
}

// Cerrar modales HTML al hacer clic en el fondo oscuro
document.getElementById('custom-vip-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});
document.getElementById('massMessageModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});

// Botón de suscripción directa a Stripe
document.getElementById('btnSubscribeMisLeads')?.addEventListener('click', async function() {
    const btn = this;
    const btnText = btn.querySelector('.btn-text');
    const spinner = btn.querySelector('.spinner');
    
    // Activar estado de carga
    btn.disabled = true;
    btn.style.opacity = '0.8';
    btnText.style.display = 'none';
    spinner.style.display = 'inline-block';
    
    try {
        if (typeof gtag === 'function') {
            gtag('event', 'begin_checkout', { currency: 'EUR', value: 4.99, source: 'modal_mis_leads', items: [{ item_id: 'vip_subscription', item_name: 'Suscripción VIP', price: 4.99, quantity: 1 }] });
        }
        const response = await fetch('/crear_sesion_suscripcion_vip.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ source: 'modal_mis_leads' })
        });
        
        const data = await response.json();
        
        if (data.success && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            alert(data.error || 'No se pudo crear la sesión de pago. Inténtalo de nuevo.');
            // Restaurar botón
            btn.disabled = false;
            btn.style.opacity = '1';
            btnText.style.display = 'inline-block';
            spinner.style.display = 'none';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión. Inténtalo de nuevo.');
        // Restaurar botón
        btn.disabled = false;
        btn.style.opacity = '1';
        btnText.style.display = 'inline-block';
        spinner.style.display = 'none';
    }
});

// ═══ Upsell → checkout VIP (no-VIP) ═══
<?php if (!$is_vip): ?>
document.getElementById('btnSubscribeVip')?.addEventListener('click', async function() {
    const btn = this;
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    try {
        if (typeof gtag === 'function') {
            gtag('event', 'begin_checkout', { currency: 'EUR', value: 4.99, source: 'upsell_no_vip', items: [{ item_id: 'vip_subscription', item_name: 'Suscripción VIP', price: 4.99, quantity: 1 }] });
        }
        const response = await fetch('/crear_sesion_suscripcion_vip.php', { method: 'POST', headers: {'Content-Type':'application/json'} });
        const data = await response.json();
        if (data.success && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'No se pudo crear la sesión de pago' });
            btn.disabled = false; btn.innerHTML = original;
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión. Inténtalo de nuevo.' });
        btn.disabled = false; btn.innerHTML = original;
    }
});
<?php endif; ?>

</script>

<!-- ═══ Gestión VIP: cancelar / reactivar / retención ═══
     Script propio con sintaxis ES5 y fallback nativo (confirm/alert):
     antes estaba en el script principal (que usa optional chaining `?.`)
     y dependía de SweetAlert2 vía CDN. Si el CDN era bloqueado (adblock)
     o el navegador no soportaba `?.`, el listener nunca se registraba
     y el botón Cancelar "no hacía nada" (bug reportado 2026-07-31). -->
<script>
(function() {
    function hasSwal() { return typeof window.Swal !== 'undefined'; }
    function stripTags(html) { return String(html || '').replace(/<[^>]+>/g, ''); }
    function notify(icon, title, msg, reload) {
        if (hasSwal()) {
            Swal.fire({ icon: icon, title: title, html: msg }).then(function() { if (reload) location.reload(); });
        } else {
            alert(title + (msg ? '\n\n' + stripTags(msg) : ''));
            if (reload) location.reload();
        }
    }
    function postVip(url, loadingTitle, okTitle, okMsgFn) {
        if (hasSwal()) {
            Swal.fire({ title: loadingTitle, allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
        }
        fetch(url, { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d && d.success) {
                    notify('success', okTitle, okMsgFn ? okMsgFn(d) : (d.message || ''), true);
                } else {
                    notify('error', 'Error', (d && d.error) || 'No se pudo completar la acción', false);
                }
            })
            .catch(function() { notify('error', 'Error', 'Error de conexión', false); });
    }
    function cancelVipSubscription() {
        postVip('/ajax/cancelar_suscripcion_vip.php', 'Cancelando...', 'Suscripción cancelada',
            function(d) { return '<p>Mantendrás tus beneficios hasta el <strong>' + d.expires_at + '</strong>.</p>'; });
    }
    function applyRetentionOffer() {
        postVip('/ajax/oferta_retencion_vip.php', 'Aplicando oferta...', '¡Genial!',
            function(d) { return '<p>Tu próxima renovación será de <strong>' + d.next_amount + '</strong>.</p>'; });
    }

    <?php if ($is_vip && !$vip_cancel_pending): ?>
    var btnCancel = document.getElementById('btnCancelVip');
    if (btnCancel) btnCancel.addEventListener('click', function() {
        if (!hasSwal()) {
            // Fallback sin SweetAlert (CDN bloqueado, p.ej. adblock)
            if (!confirm('¿Cancelar VIP?\n\nPerderás estos beneficios al final del período ya pagado:\n- Badge VIP Verificado\n- Chat ilimitado con viewers\n- Mensajes masivos\n- 10€ de saldo mensual\n\nTu saldo actual se mantiene disponible.')) return;
            <?php if (!$vip_retention_used): ?>
            if (confirm('¡Espera! Tenemos algo para ti\n\n¿Quieres quedarte por solo 4,99€ el próximo mes (en vez de 9,99€)? Mismos beneficios, mitad de precio.\n\nAceptar = Quedarme por 4,99€\nCancelar = Cancelar VIP igualmente')) {
                applyRetentionOffer();
            } else {
                cancelVipSubscription();
            }
            <?php else: ?>
            cancelVipSubscription();
            <?php endif; ?>
            return;
        }
        Swal.fire({
            title: '¿Cancelar VIP?',
            html: '<div style="text-align:left; margin: 15px 0;">'
                + '<p style="color:#666; margin-bottom:15px;">Perderás estos beneficios al final del período:</p>'
                + '<div style="display:flex; align-items:center; gap:10px; padding:6px 0; color:#dc3545;"><i class="fas fa-times-circle"></i> Badge VIP Verificado</div>'
                + '<div style="display:flex; align-items:center; gap:10px; padding:6px 0; color:#dc3545;"><i class="fas fa-times-circle"></i> Chat ilimitado con viewers</div>'
                + '<div style="display:flex; align-items:center; gap:10px; padding:6px 0; color:#dc3545;"><i class="fas fa-times-circle"></i> Mensajes masivos</div>'
                + '<div style="display:flex; align-items:center; gap:10px; padding:6px 0; color:#dc3545;"><i class="fas fa-times-circle"></i> 10€ de saldo mensual</div>'
                + '<p style="color:#888; font-size:0.85rem; margin-top:15px;"><i class="fas fa-info-circle"></i> Tu saldo actual se mantiene disponible.</p>'
                + '</div>',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cancelar', cancelButtonText: 'Volver', reverseButtons: true
        }).then(function(r) {
            if (!r.isConfirmed) return;
            <?php if (!$vip_retention_used): ?>
            Swal.fire({
                title: '¡Espera! Tenemos algo para ti',
                html: '<div style="text-align:center;">'
                    + '<div style="font-size:3rem; margin:10px 0;">🎁</div>'
                    + '<p style="color:#333; font-size:1.1rem; font-weight:600; margin-bottom:5px;">¿Y si te quedas por solo 4,99€?</p>'
                    + '<p style="color:#666; font-size:0.95rem; margin-bottom:20px;">Mismos beneficios, mitad de precio el próximo mes.</p>'
                    + '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius:15px; padding:20px; color:white;">'
                    + '<div style="font-size:2.5rem; font-weight:800;">4,99€</div>'
                    + '<div style="font-size:0.9rem; opacity:0.9;">en vez de 9,99€/mes</div>'
                    + '</div></div>',
                showCancelButton: true, showDenyButton: true,
                confirmButtonText: '¡Acepto 4,99€!', denyButtonText: 'No, cancelar igualmente', cancelButtonText: 'Volver',
                confirmButtonColor: '#667eea', denyButtonColor: '#dc3545'
            }).then(function(rr) {
                if (rr.isConfirmed) applyRetentionOffer();
                else if (rr.isDenied) cancelVipSubscription();
            });
            <?php else: ?>
            cancelVipSubscription();
            <?php endif; ?>
        });
    });
    <?php endif; ?>

    <?php if ($is_vip && $vip_cancel_pending): ?>
    var btnReact = document.getElementById('btnReactivate');
    if (btnReact) btnReact.addEventListener('click', function() {
        function doReactivate() {
            postVip('/ajax/reactivar_suscripcion_vip.php', 'Reactivando...', '¡Reactivada!',
                function(d) { return d.message || ''; });
        }
        if (!hasSwal()) {
            if (confirm('¿Reactivar VIP?\n\nTu suscripción continuará renovándose automáticamente.')) doReactivate();
            return;
        }
        Swal.fire({
            title: '¿Reactivar VIP?', text: 'Tu suscripción continuará renovándose automáticamente.',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#ffd700', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, reactivar', cancelButtonText: 'Volver'
        }).then(function(r) { if (r.isConfirmed) doReactivate(); });
    });
    var btnRet = document.getElementById('btnRetention');
    if (btnRet) btnRet.addEventListener('click', function() {
        if (!hasSwal() && !confirm('¿Quedarte por 4,99€ el próximo mes (en vez de 9,99€)?')) return;
        applyRetentionOffer();
    });
    <?php endif; ?>
})();
</script>

<?php
include_once __DIR__ . '/../myphp/_footer.php';
?>
