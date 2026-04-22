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
foreach ($viewers as $v) {
    if (!empty($v['completado'])) {
        $count_conseguidos++;
    } else {
        $count_activos++;
        if (empty($v['contacted'])) {
            $count_no_contactados++;
        }
    }
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
}

/* --- Header --- */
.leads-header {
    margin-bottom: 35px;
}

.leads-title {
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.leads-title i {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.leads-subtitle {
    color: rgba(255,255,255,0.5);
    font-size: 1rem;
}

/* --- Stats Grid --- */
.leads-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 35px;
}

@media (max-width: 768px) {
    .leads-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 420px) {
    .leads-stats-grid {
        grid-template-columns: 1fr;
    }
}

.lead-stat-card {
    background: rgba(255,255,255,0.04);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    padding: 22px 18px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.lead-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: 16px 16px 0 0;
}

.lead-stat-card:nth-child(1)::before { background: linear-gradient(90deg, #667eea, #764ba2); }
.lead-stat-card:nth-child(2)::before { background: linear-gradient(90deg, #f093fb, #f5576c); }
.lead-stat-card:nth-child(3)::before { background: linear-gradient(90deg, #4facfe, #00f2fe); }
.lead-stat-card:nth-child(4)::before { background: linear-gradient(90deg, #ffd700, #E30613); }

.lead-stat-card:hover {
    border-color: rgba(255,255,255,0.15);
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.lead-stat-icon {
    font-size: 1.4rem;
    margin-bottom: 10px;
    opacity: 0.7;
}

.lead-stat-card:nth-child(1) .lead-stat-icon { color: #667eea; }
.lead-stat-card:nth-child(2) .lead-stat-icon { color: #f093fb; }
.lead-stat-card:nth-child(3) .lead-stat-icon { color: #4facfe; }
.lead-stat-card:nth-child(4) .lead-stat-icon { color: #ffd700; }

.lead-stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
    margin-bottom: 6px;
}

.lead-stat-label {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.45);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
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
    
    <!-- Stats Grid -->
    <div class="leads-stats-grid">
        <div class="lead-stat-card">
            <div class="lead-stat-icon"><i class="fas fa-crosshairs"></i></div>
            <div class="lead-stat-value"><?php echo $total_viewers; ?></div>
            <div class="lead-stat-label">Leads totales</div>
        </div>
        <div class="lead-stat-card">
            <div class="lead-stat-icon"><i class="fas fa-tags"></i></div>
            <div class="lead-stat-value"><?php echo $total_codigos_vistos; ?></div>
            <div class="lead-stat-label">Códigos vistos</div>
        </div>
        <div class="lead-stat-card">
            <div class="lead-stat-icon"><i class="fas fa-coins"></i></div>
            <div class="lead-stat-value"><?php echo number_format($total_potencial, 0); ?>€</div>
            <div class="lead-stat-label">Potencial</div>
        </div>
        <div class="lead-stat-card">
            <div class="lead-stat-icon"><i class="fas fa-trophy"></i></div>
            <div class="lead-stat-value" style="font-size: <?php echo strlen($codigo_top_marca) > 10 ? '1.1rem' : '1.5rem'; ?>"><?php echo htmlspecialchars($codigo_top_marca); ?></div>
            <div class="lead-stat-label">Código top<?php if ($codigo_top_count > 0) echo " ($codigo_top_count leads)"; ?></div>
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

    <?php if (!$is_vip && $total_viewers > 0): ?>
    <!-- VIP Upsell -->
    <div class="leads-vip-upsell">
        <h3><i class="fas fa-crown"></i> ¡Tienes <?php echo $total_viewers; ?> leads esperando!</h3>
        <p>
            Hazte VIP para contactar directamente con los usuarios que han visto tus códigos.
            Ayúdales a completar el proceso y ambos ganáis. Win-win.
        </p>
        <button class="btn-upgrade-vip" id="btnSubscribeVip">
            <i class="fas fa-bolt"></i>
            Desbloquear por 9,99€/mes
        </button>

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
                    <button class="btn-lead-contact" title="Hazte VIP para contactar" onclick="openVipModal('<?php echo htmlspecialchars(addslashes($viewer['viewer_username'])); ?>', '<?php echo htmlspecialchars(addslashes($viewer['codigo_marca'])); ?>', '<?php echo number_format($viewer['codigo_beneficio'], 0, ',', '.'); ?>', '<?php echo htmlspecialchars(addslashes($viewer['tiempo_relativo'] ?? 'hace poco')); ?>')">
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function toggleSelectAll() {
    const isChecked = document.getElementById('selectAll').checked;
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
<?php endif; ?>

<!-- Custom VIP Modal Overlay (No dependencies) -->
<div id="custom-vip-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 1050; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
    <div style="background: #1a1a2e; border-radius: 20px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); width: 90%; max-width: 500px; box-shadow: 0 25px 80px rgba(0,0,0,0.5); animation: vipModalPop 0.3s ease; position: relative;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); padding: 18px 25px; position: relative;">
            <h5 style="margin: 0; color: white; font-weight: 800; font-size: 1.25rem; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-crown"></i> VENTAJAS VIP
            </h5>
            <button onclick="document.getElementById('custom-vip-modal').style.display='none'" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: white; font-size: 28px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <!-- Body -->
        <div style="padding: 30px; text-align: left;">
            
            <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.2); border-radius: 12px; padding: 20px 15px; margin-bottom: 20px; text-align: center;">
                <p style="margin: 0; font-size: 1.1rem; color: rgba(255,255,255,0.95); line-height: 1.6;">
                    ¡Estás a un paso de conseguir <strong style="color: #4ade80; font-size: 1.3rem;">+<span id="vip-modal-benefit"></span>€</strong> de beneficio!<br><br>
                    Contacta con <strong style="color: #fff;" id="vip-modal-username"></strong> para ayudarle con tu código de <strong style="color: #fff;" id="vip-modal-brand"></strong> y asegurar tu referido.<br><br>
                    <span style="font-size: 0.95rem; color: rgba(255,255,255,0.6);">Hablar directamente con los leads es una función VIP.</span><br>
                    <strong style="color: #ffd700; font-size: 1.15rem; margin-top: 5px; display: block;">¡Hazte VIP y contacta sin límites!</strong>
                </p>
            </div>
            
            <div style="background: rgba(255, 69, 58, 0.1); border: 1px solid rgba(255, 69, 58, 0.3); border-radius: 10px; padding: 12px 15px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                <div style="font-size: 24px; color: #ff453a; animation: pulseRed 2s infinite;"><i class="fas fa-hourglass-half"></i></div>
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.85); line-height: 1.4; text-align: left;">
                    <strong style="color: #ff453a;">La probabilidad de referido baja cada minuto:</strong> El usuario vio tu código <strong style="color: #fff;" id="vip-modal-time"></strong>. ¡Actúa rápido antes de que busque otra alternativa en internet!
                </div>
            </div>
            
            <ul style="list-style: none; padding: 0; margin: 0 0 25px 0;">
                <li style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; color: rgba(255,255,255,0.85); font-size: 0.95rem;">
                    <i class="fas fa-check-circle" style="color: #ffd700; margin-top: 3px; font-size: 1.1rem;"></i>
                    <span><strong style="color: #fff;">Chat Ilimitado:</strong> Contacta y ayuda a los usuarios que ven tus códigos para asegurar tus referidos.</span>
                </li>
                <li style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; color: rgba(255,255,255,0.85); font-size: 0.95rem;">
                    <i class="fas fa-check-circle" style="color: #ffd700; margin-top: 3px; font-size: 1.1rem;"></i>
                    <span><strong style="color: #fff;">Badge VIP Verificado:</strong> Gana confianza y obtén hasta un 40% más de clics en tus códigos.</span>
                </li>
                <li style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; color: rgba(255,255,255,0.85); font-size: 0.95rem;">
                    <i class="fas fa-check-circle" style="color: #ffd700; margin-top: 3px; font-size: 1.1rem;"></i>
                    <span><strong style="color: #fff;">IA Ilimitada:</strong> Completa todas las descripciones de tus códigos con inteligencia artificial profesional.</span>
                </li>
                <li style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 0; color: rgba(255,255,255,0.85); font-size: 0.95rem;">
                    <i class="fas fa-check-circle" style="color: #ffd700; margin-top: 3px; font-size: 1.1rem;"></i>
                    <span><strong style="color: #fff;">10€ de Saldo Mensual:</strong> Recibe 10€ cada mes para destacar tus códigos totalmente gratis.</span>
                </li>
            </ul>

            <div style="text-align: center;">
                <button id="btnSubscribeMisLeads" style="display: inline-flex; justify-content: center; align-items: center; gap: 10px; background: linear-gradient(135deg, #ffd700 0%, #E30613 100%); color: white; border: none; padding: 14px 35px; border-radius: 30px; font-weight: bold; font-size: 1.1rem; text-decoration: none; cursor: pointer; box-shadow: 0 4px 15px rgba(227, 6, 19, 0.4); width: 100%; transition: transform 0.2s ease;">
                    <span class="btn-text">QUIERO SER VIP POR 9,99€</span>
                    <span class="spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
                <p style="margin-top: 15px; margin-bottom: 0; color: rgba(255,255,255,0.4); font-size: 0.85rem;">
                    Cancela en cualquier momento con un solo clic.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
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

// ═══ Gestión VIP: cancelar / reactivar / retención ═══
<?php if ($is_vip && !$vip_cancel_pending): ?>
document.getElementById('btnCancelVip')?.addEventListener('click', function() {
    Swal.fire({
        title: '¿Cancelar VIP?',
        html: `
            <div style="text-align:left; margin: 15px 0;">
                <p style="color:#666; margin-bottom:15px;">Perderás estos beneficios al final del período:</p>
                <div style="display:flex; align-items:center; gap:10px; padding:6px 0; color:#dc3545;"><i class="fas fa-times-circle"></i> Badge VIP Verificado</div>
                <div style="display:flex; align-items:center; gap:10px; padding:6px 0; color:#dc3545;"><i class="fas fa-times-circle"></i> Chat ilimitado con viewers</div>
                <div style="display:flex; align-items:center; gap:10px; padding:6px 0; color:#dc3545;"><i class="fas fa-times-circle"></i> Mensajes masivos</div>
                <div style="display:flex; align-items:center; gap:10px; padding:6px 0; color:#dc3545;"><i class="fas fa-times-circle"></i> 10€ de saldo mensual</div>
                <p style="color:#888; font-size:0.85rem; margin-top:15px;"><i class="fas fa-info-circle"></i> Tu saldo actual se mantiene disponible.</p>
            </div>`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, cancelar', cancelButtonText: 'Volver', reverseButtons: true
    }).then((r) => {
        if (!r.isConfirmed) return;
        <?php if (!$vip_retention_used): ?>
        Swal.fire({
            title: '¡Espera! Tenemos algo para ti',
            html: `
                <div style="text-align:center;">
                    <div style="font-size:3rem; margin:10px 0;">🎁</div>
                    <p style="color:#333; font-size:1.1rem; font-weight:600; margin-bottom:5px;">¿Y si te quedas por solo 4,99€?</p>
                    <p style="color:#666; font-size:0.95rem; margin-bottom:20px;">Mismos beneficios, mitad de precio el próximo mes.</p>
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius:15px; padding:20px; color:white;">
                        <div style="font-size:2.5rem; font-weight:800;">4,99€</div>
                        <div style="font-size:0.9rem; opacity:0.9;">en vez de 9,99€/mes</div>
                    </div>
                </div>`,
            showCancelButton: true, showDenyButton: true,
            confirmButtonText: '¡Acepto 4,99€!', denyButtonText: 'No, cancelar igualmente', cancelButtonText: 'Volver',
            confirmButtonColor: '#667eea', denyButtonColor: '#dc3545'
        }).then((rr) => {
            if (rr.isConfirmed) applyRetentionOffer();
            else if (rr.isDenied) cancelVipSubscription();
        });
        <?php else: ?>
        cancelVipSubscription();
        <?php endif; ?>
    });
});
async function cancelVipSubscription() {
    Swal.fire({ title: 'Cancelando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const r = await fetch('/ajax/cancelar_suscripcion_vip.php', { method: 'POST' });
        const d = await r.json();
        if (d.success) {
            Swal.fire({ icon: 'info', title: 'Suscripción cancelada', html: `<p>Mantendrás tus beneficios hasta el <strong>${d.expires_at}</strong>.</p>`, confirmButtonColor: '#6c757d' }).then(() => location.reload());
        } else Swal.fire({ icon: 'error', title: 'Error', text: d.error || 'No se pudo cancelar' });
    } catch(e) { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' }); }
}
async function applyRetentionOffer() {
    Swal.fire({ title: 'Aplicando oferta...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const r = await fetch('/ajax/oferta_retencion_vip.php', { method: 'POST' });
        const d = await r.json();
        if (d.success) {
            Swal.fire({ icon: 'success', title: '¡Genial!', html: `<p>Tu próxima renovación será de <strong>${d.next_amount}</strong>.</p>`, confirmButtonColor: '#667eea' }).then(() => location.reload());
        } else Swal.fire({ icon: 'error', title: 'Error', text: d.error || 'No se pudo aplicar la oferta' });
    } catch(e) { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' }); }
}
<?php endif; ?>

<?php if ($is_vip && $vip_cancel_pending): ?>
document.getElementById('btnReactivate')?.addEventListener('click', async function() {
    const r = await Swal.fire({
        title: '¿Reactivar VIP?', text: 'Tu suscripción continuará renovándose automáticamente.',
        icon: 'question', showCancelButton: true,
        confirmButtonColor: '#ffd700', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, reactivar', cancelButtonText: 'Volver'
    });
    if (!r.isConfirmed) return;
    Swal.fire({ title: 'Reactivando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const res = await fetch('/ajax/reactivar_suscripcion_vip.php', { method: 'POST' });
        const d = await res.json();
        if (d.success) Swal.fire({ icon: 'success', title: '¡Reactivada!', text: d.message, confirmButtonColor: '#ffd700' }).then(() => location.reload());
        else Swal.fire({ icon: 'error', title: 'Error', text: d.error });
    } catch(e) { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' }); }
});
document.getElementById('btnRetention')?.addEventListener('click', async function() {
    Swal.fire({ title: 'Aplicando oferta...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const r = await fetch('/ajax/oferta_retencion_vip.php', { method: 'POST' });
        const d = await r.json();
        if (d.success) Swal.fire({ icon: 'success', title: '¡Genial!', html: `<p>Tu próxima renovación será de <strong>${d.next_amount}</strong>.</p>`, confirmButtonColor: '#667eea' }).then(() => location.reload());
        else Swal.fire({ icon: 'error', title: 'Error', text: d.error || 'No se pudo aplicar la oferta' });
    } catch(e) { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' }); }
});
<?php endif; ?>
</script>

<?php
include_once __DIR__ . '/../myphp/_footer.php';
?>
