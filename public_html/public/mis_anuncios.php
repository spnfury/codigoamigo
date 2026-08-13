<?php
include_once __DIR__ . '/../inc/logger.php';
// Verificar que la sesión esté iniciada y que el usuario esté logueado
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || $_SESSION["user_id"] == "") {
    header("Location: https://www.codigoamigo.com/login");
    exit;
}

// Verificar que las variables de sesión necesarias existan
if (!isset($_SESSION["username"]) || empty($_SESSION["username"])) {
    if (isset($data_usuario) && isset($data_usuario["username"])) {
        $_SESSION["username"] = $data_usuario["username"];
    } else {
        try {
            $usuario_temp = getObjectUserWithSession('_id', new MongoDB\BSON\ObjectId($_SESSION["user_id"]));
            if ($usuario_temp && isset($usuario_temp["username"])) {
                $_SESSION["username"] = $usuario_temp["username"];
            } else {
                header("Location: https://www.codigoamigo.com/login");
                exit;
            }
        } catch (Exception $e) {
            header("Location: https://www.codigoamigo.com/login");
            exit;
        }
    }
}

// Desactivar AdSense para esta página
$anula_adsense = true;
$GLOBALS['anula_adsense'] = true; // Asegurar que esté disponible globalmente

// Stripe config (evitar notices si no está seteado previamente)
if (!isset($stripe_live_publishable_key)) {
    if (isset($_SESSION["user_id"]) && in_array($_SESSION["user_id"], [
        '639899bc6321ee0d0e4010d2', // admins con modo test
        '58bd851da54e295b8b52f702',
        '5db1af3a2f55c82b47342172'
    ])) {
        $stripe_live_publishable_key = "pk_test_yU61XXQMBvqVt4Ah9XD5uk6V";
    } else {
        $stripe_live_publishable_key = "pk_live_HvgqlImI22optTnSvHKFKDiG00VQ0EZdE9";
    }
}
if (!isset($sku_patrocinado_splash)) {
    // Precio de Stripe para destacar todos (splash)
    $sku_patrocinado_splash = 'sku_H6ViM4K361ELMH';
}

get_header_modern("Mis Códigos - CodigoAmigo.com", "Administra todos tus códigos descuento publicados. Gestiona tu visibilidad, estadísticas y saldo para destacar tus ofertas.");

// POTENCIAL DE GANANCIAS
include_once __DIR__ . '/../myphp/funciones_usuario.php';
$potencial_data = obtener_potencial_completo_usuario($_SESSION["user_id"]);
$total_potential = $potencial_data['total_potential'];
$total_viewers = $potencial_data['total_unique_viewers'];
$all_viewer_ids = $potencial_data['all_viewer_ids'];

// Comprobar si es VIP (para mass-message.js)
$is_vip_user = es_usuario_vip($_SESSION["user_id"]);

// Definir variables globales necesarias
if (!isset($GLOBALS['website'])) {
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
}
if (!isset($GLOBALS['actual_url'])) {
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// Fallback: si venimos de un pago por saldo/stripe y hay señal de éxito en la URL,
// aseguramos el disparo de notificaciones de destacado (idempotente con guardado en sesión)
try {
    if (isset($_GET['success']) && $_GET['success'] === 'destacado' && isset($_GET['codigo'])) {
        $codigo_id_qs = $_GET['codigo'];
        $tipo_qs = isset($_GET['tipo']) && in_array($_GET['tipo'], ['normal','super']) ? $_GET['tipo'] : 'normal';

        if (!isset($_SESSION['last_destacado_notify']) || $_SESSION['last_destacado_notify'] !== $codigo_id_qs) {
            include_once __DIR__ . '/../myphp/funciones.php';
            if (function_exists('destacar_codigo_moderno')) {
                destacar_codigo_moderno($codigo_id_qs, $tipo_qs);
            }
            $_SESSION['last_destacado_notify'] = $codigo_id_qs;
        }
    }
} catch (Exception $e) {
    // silencioso
}

// Los códigos del usuario ya están disponibles desde app_with_mongo.php
?>

<!-- Incluir archivos CSS y JavaScript externos -->
<link rel="stylesheet" href="/assets/css/mis-anuncios.css?v=<?php echo time(); ?>">
<script src="/assets/js/mis-anuncios.js?<?php echo time(); ?>" defer></script>
<script src="/assets/js/mis-anuncios-infinite.js?<?php echo time(); ?>" defer></script>
<script src="/js/mass-message.js?v=<?php echo time(); ?>" defer></script>
<!-- Define toggleAutoRenovar(): el interruptor de auto-renovación de cada
     tarjeta lo invoca, pero esta página no cargaba el JS que lo define. -->
<script src="/js/auto-renovar.js?v=<?php echo time(); ?>" defer></script>

<style>
/* ===========================================================================
   ma-row · diseño limpio mobile-first
   =========================================================================== */
.codes-grid {
    display:grid !important;
    grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
    gap:12px;
    max-width:1120px;
    margin:0 auto;
    align-items:start;
}
.codes-grid .ma-row { margin-bottom:0; }
@media (max-width: 720px) {
    .codes-grid { grid-template-columns: 1fr; max-width:760px; }
}

/* ===========================================================================
   ma-search-sticky · barra búsqueda fija al scroll
   =========================================================================== */
.ma-search-sticky {
    padding: 14px 16px;
    background: #fff;
    margin-bottom: 16px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: box-shadow 0.2s ease;
}
.ma-search-sticky.is-fixed {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 9998;
    margin: 0;
    border-radius: 0;
    padding: 12px 16px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.10);
}
.ma-search-placeholder { display: none; }
.ma-search-placeholder.is-active { display: block; }

.ma-row {
    background:#fff;
    border-radius:14px;
    padding:14px 16px;
    box-shadow:0 1px 2px rgba(0,0,0,0.05);
    border:1px solid #eef0f3;
    margin-bottom:10px;
    transition:border-color 0.15s ease, box-shadow 0.15s ease;
}
.ma-row:hover { border-color:#dfe3e8; box-shadow:0 2px 8px rgba(0,0,0,0.06); }

/* Header: logo + título + kebab */
.ma-row-head {
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:10px;
}
.ma-row-logo {
    flex-shrink:0;
    width:38px;
    height:38px;
    border-radius:10px;
    background:#f8f9fa;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    border:1px solid #eef0f3;
}
.ma-row-logo img { width:100%; height:100%; object-fit:contain; padding:4px; box-sizing:border-box; }
.ma-row-logo-fallback {
    width:100%; height:100%;
    display:flex; align-items:center; justify-content:center;
    background:#E30613;
    color:#fff; font-weight:800; font-size:1rem;
}
.ma-row-title { flex:1; min-width:0; display:flex; flex-direction:column; gap:3px; }
.ma-row-name {
    font-weight:800;
    color:#1a1a2e;
    text-decoration:none;
    font-size:0.98rem;
    line-height:1.2;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.ma-row-badges {
    display:flex;
    align-items:center;
    gap:5px;
    flex-wrap:wrap;
}

/* Pills */
.ma-pill {
    padding:2px 7px;
    border-radius:6px;
    font-size:0.68rem;
    font-weight:700;
    line-height:1.4;
    white-space:nowrap;
    background:#f3f4f6;
    color:#6b7280;
}
/* Posición: sutil, el valor es el número no el color */
.ma-pill-pos { background:#f3f4f6; color:#4b5563; }
/* Estado problema (caducado/desactivado/inactivo): ámbar discreto */
.ma-pill-estado { background:#fdf6ec; color:#b45309; }
.ma-pill-date { font-size:0.7rem; color:#9ca3af; }

/* Kebab */
.ma-kebab { position:relative; flex-shrink:0; }
.ma-kebab-btn {
    background:transparent;
    border:none;
    width:32px; height:32px;
    border-radius:8px;
    color:#6b7280;
    cursor:pointer;
    font-size:1rem;
    display:flex; align-items:center; justify-content:center;
    transition:background 0.15s ease;
}
.ma-kebab-btn:hover { background:#f3f4f6; color:#1a1a2e; }
.ma-kebab-menu {
    position:absolute;
    top:calc(100% + 4px);
    right:0;
    background:#fff;
    border-radius:10px;
    box-shadow:0 10px 25px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.06);
    border:1px solid #f0f0f0;
    min-width:170px;
    padding:6px;
    z-index:50;
    display:none;
    flex-direction:column;
    gap:1px;
}
.ma-kebab.is-open .ma-kebab-menu { display:flex; }
.ma-kebab-menu a,
.ma-kebab-menu button {
    background:transparent;
    border:none;
    text-align:left;
    padding:9px 12px;
    border-radius:7px;
    color:#374151;
    font-size:0.88rem;
    font-weight:600;
    text-decoration:none;
    cursor:pointer;
    display:flex; align-items:center; gap:9px;
    transition:background 0.12s ease;
}
.ma-kebab-menu a:hover,
.ma-kebab-menu button:hover { background:#f3f4f6; }
.ma-kebab-menu button.danger { color:#dc2626; }
.ma-kebab-menu button.danger:hover { background:#fef2f2; }
.ma-kebab-menu i { width:14px; text-align:center; opacity:0.7; }

/* Descripción */
.ma-row-desc {
    margin:0 0 10px 0;
    color:#4b5563;
    font-size:0.86rem;
    line-height:1.45;
}
.ma-row-desc a { color:#E30613; font-weight:700; text-decoration:none; }

/* Stats chips */
.ma-row-stats {
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    margin-bottom:10px;
}
.ma-chip {
    padding:3px 9px;
    border-radius:8px;
    font-size:0.74rem;
    font-weight:700;
    background:#f3f4f6;
    color:#4b5563;
    display:inline-flex;
    align-items:center;
    gap:5px;
    text-decoration:none;
    border:1px solid transparent;
}
/* Beneficio €: valor destacado pero plano (texto fuerte, sin fondo chillón) */
.ma-chip-benef { background:#f3f4f6; color:#1a1a2e; }
/* Potencial: ÚNICO acento de la card — dinero esperando, además es accionable */
.ma-chip-potencial { background:#fef2f2; color:#E30613; border-color:#fecaca; }
.ma-chip-potencial:hover { background:#fee2e2; }

/* CTA */
.ma-row-cta { display:flex; gap:8px; margin-bottom:8px; }
.ma-btn {
    flex:1;
    padding:9px 14px;
    border-radius:9px;
    font-weight:700;
    font-size:0.86rem;
    text-decoration:none;
    text-align:center;
    border:1px solid transparent;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    transition:background 0.15s ease, border-color 0.15s ease;
}
/* Ver detalle = secundario neutro (plano, sin color) */
.ma-btn-detalle {
    background:#fff;
    color:#374151 !important;
    border-color:#e5e7eb;
}
.ma-btn-detalle:hover { background:#f8f9fa; color:#1a1a2e !important; border-color:#d1d5db; }
/* Reactivar = acción única en códigos inactivos */
.ma-btn-reactivar { background:#1a1a2e; color:#fff; border-color:#1a1a2e; }
.ma-btn-reactivar:hover { background:#2d2d4a; }

/* Control segmentado de destacado: Normal | Super */
.ma-dest-control {
    display:flex;
    border:1px solid #e5e7eb;
    border-radius:9px;
    overflow:hidden;
    margin-bottom:8px;
}
.ma-dest-seg {
    flex:1;
    padding:9px 10px;
    font-size:0.82rem;
    font-weight:700;
    text-align:center;
    color:#6b7280;
    text-decoration:none;
    background:#fff;
    border:none;
    cursor:pointer;
    display:inline-flex; align-items:center; justify-content:center; gap:6px;
    transition:background 0.15s ease, color 0.15s ease;
}
.ma-dest-seg:hover { background:#f8f9fa; color:#1a1a2e; }
.ma-dest-seg + .ma-dest-seg { border-left:1px solid #e5e7eb; }
.ma-dest-seg i { opacity:0.85; }
/* Estado activo: Normal = acento rojo de marca; Super = oscuro premium */
.ma-dest-seg.is-active { color:#fff; }
.ma-dest-seg.is-active:hover { color:#fff; }
.ma-dest-normal.is-active { background:#E30613; }
.ma-dest-normal.is-active:hover { background:#c70511; }
.ma-dest-super.is-active { background:#1a1a2e; }
.ma-dest-super.is-active:hover { background:#2d2d4a; }
.ma-dest-label { font-size:0.7rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.4px; padding:0 2px 4px; }

/* ===========================================================================
   Métricas de rendimiento · tabla plana
   =========================================================================== */
.ma-metricas-toggle {
    width:100%;
    display:flex; align-items:center; justify-content:space-between;
    background:#fff; border:none; cursor:pointer;
    padding:16px 20px;
    font-size:0.98rem; font-weight:800; color:#1a1a2e;
    transition:background 0.15s ease;
}
.ma-metricas-toggle:hover { background:#f8f9fa; }
.ma-metricas-toggle i.fa-chart-line { color:#E30613; margin-right:8px; }
.ma-metricas-chevron { color:#9ca3af; transition:transform 0.2s ease; }
.ma-metricas-toggle.is-open .ma-metricas-chevron { transform:rotate(180deg); }

.ma-metricas-wrap { border-top:1px solid #eef0f3; }

/* KPIs resumen */
.ma-metricas-summary {
    display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));
    gap:1px; background:#eef0f3;
    border-bottom:1px solid #eef0f3;
}
.ma-metricas-kpi { background:#fff; padding:14px 18px; display:flex; flex-direction:column; gap:3px; }
.ma-metricas-kpi-label { font-size:0.72rem; color:#9ca3af; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; }
.ma-metricas-kpi-val { font-size:1.4rem; font-weight:800; color:#1a1a2e; line-height:1; }

/* Tabla */
.ma-metricas-scroll { max-height:520px; overflow-y:auto; }
.ma-metricas-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
.ma-metricas-table thead th {
    position:sticky; top:0; z-index:1;
    background:#f8f9fa; color:#6b7280;
    font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.3px;
    text-align:right; padding:10px 14px; white-space:nowrap;
    border-bottom:1px solid #eef0f3;
}
.ma-metricas-table thead th.col-prod { text-align:left; }
.ma-metricas-table thead th.col-trend, .ma-metricas-table thead th.col-spark { text-align:center; }
.ma-metricas-table tbody td { padding:10px 14px; text-align:right; border-bottom:1px solid #f3f4f6; color:#374151; white-space:nowrap; }
.ma-metricas-table tbody tr:hover { background:#fafbfc; }
.ma-metricas-table tbody td.col-prod { text-align:left; }
.ma-metricas-total { color:#9ca3af; }
.ma-row-inactiva { opacity:0.55; }

/* Producto (logo + texto) */
.ma-metricas-prod { display:flex; align-items:center; gap:10px; text-decoration:none; max-width:280px; }
.ma-metricas-logo {
    flex-shrink:0; width:34px; height:34px; border-radius:8px;
    background:#f3f4f6; border:1px solid #eef0f3;
    display:flex; align-items:center; justify-content:center; overflow:hidden;
    font-weight:800; color:#6b7280; font-size:0.85rem;
}
.ma-metricas-logo img { width:100%; height:100%; object-fit:contain; padding:3px; box-sizing:border-box; }
.ma-metricas-prod-txt { min-width:0; display:flex; flex-direction:column; gap:1px; }
.ma-metricas-marca { font-weight:800; color:#1a1a2e; font-size:0.86rem; display:flex; align-items:center; gap:5px; }
.ma-metricas-desc { font-size:0.76rem; color:#9ca3af; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:230px; }

/* Tendencia (único uso de verde/rojo: señal de rendimiento) */
.ma-trend { font-weight:700; font-size:0.8rem; display:inline-flex; align-items:center; gap:3px; }
.ma-trend-up { color:#16a34a; }
.ma-trend-down { color:#dc2626; }
.ma-trend-flat { color:#9ca3af; }
.col-trend { text-align:center !important; }

/* Sparkline 7 días */
.col-spark { text-align:center !important; }
.ma-spark { display:inline-flex; align-items:flex-end; gap:2px; height:28px; }
.ma-spark-bar { width:5px; background:#d1d5db; border-radius:2px; display:block; }
.ma-spark-bar.is-last { background:#1a1a2e; }

@media (max-width: 720px) {
    .ma-metricas-table thead th.col-spark, .ma-metricas-table tbody td.col-spark,
    .ma-metricas-table thead th:nth-child(4), .ma-metricas-table tbody td:nth-child(4) { display:none; }
    .ma-metricas-desc { display:none; }
}

/* Auto-renovación destacado */
.ma-row-autoren {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-top:10px;
    padding:8px 10px;
    background:#fafbfc;
    border:1px solid #eef0f3;
    border-radius:10px;
}
.ma-autoren-label { font-size:0.78rem; font-weight:700; color:#6b7280; display:flex; align-items:center; gap:6px; }
/* Switch estilo iOS */
.ma-autoren-switch {
    position:relative;
    width:46px; height:26px;
    border-radius:13px;
    border:none;
    background:#e4e6eb;
    cursor:pointer;
    padding:0;
    flex-shrink:0;
    transition:background 0.25s ease;
    -webkit-tap-highlight-color:transparent;
}
.ma-autoren-switch::after {
    content:'';
    position:absolute;
    top:2px; left:2px;
    width:22px; height:22px;
    border-radius:50%;
    background:#fff;
    box-shadow:0 1px 3px rgba(0,0,0,0.3);
    transition:transform 0.25s ease;
}
.ma-autoren-switch.is-on { background:#34c759; }
.ma-autoren-switch.is-on::after { transform:translateX(20px); }
.ma-autoren-switch.is-loading { opacity:0.55; pointer-events:none; }

/* Mobile */
@media (max-width: 600px) {
    .ma-row { padding:12px 14px; border-radius:14px; }
    .ma-row-logo { width:34px; height:34px; }
    .ma-row-name { font-size:0.92rem; }
    .ma-pill, .ma-pill-date { font-size:0.65rem; }
    .ma-chip { font-size:0.7rem; }
    .ma-btn { font-size:0.82rem; padding:9px 12px; }
    .ma-tab { padding:8px 12px !important; font-size:0.8rem !important; }
}
</style>

<script>
// Kebab dropdown: cerrar al click fuera
function maToggleKebab(btn) {
    var k = btn.closest('.ma-kebab');
    if (!k) return;
    var open = k.classList.contains('is-open');
    document.querySelectorAll('.ma-kebab.is-open').forEach(function (el) { el.classList.remove('is-open'); });
    if (!open) k.classList.add('is-open');
}
document.addEventListener('click', function (e) {
    if (!e.target.closest('.ma-kebab')) {
        document.querySelectorAll('.ma-kebab.is-open').forEach(function (el) { el.classList.remove('is-open'); });
    }
});
// Toggle tabla de métricas
function maToggleMetricas(btn) {
    var wrap = document.getElementById('ma-metricas-wrap');
    if (!wrap) return;
    var open = wrap.style.display !== 'none';
    wrap.style.display = open ? 'none' : 'block';
    btn.classList.toggle('is-open', !open);
}
</script>
<script>
window.userIsVip = <?php echo $is_vip_user ? 'true' : 'false'; ?>;
window.jsConfig = {
    userId: <?php echo json_encode($_SESSION["user_id"] ?? ""); ?>,
    skuPatrocinadoSplash: <?php echo json_encode($sku_patrocinado_splash ?? ""); ?>,
    website: <?php echo json_encode($GLOBALS["website"] ?? ""); ?>,
    actualUrl: <?php echo json_encode($GLOBALS["actual_url"] ?? ""); ?>
};

// Wrapper defensivo: asegura que exista la función global para los onclick inline
function filterByVisibility(visibility){
    if (window.filterByVisibility) { return window.filterByVisibility(visibility); }
    document.addEventListener('DOMContentLoaded', function(){
        if (window.filterByVisibility) window.filterByVisibility(visibility);
    });
}
</script>

<style>
/* INLINE CSS to bypass Cloudflare CDN cache */
.btn-modificar-neutral, .btn-stats-neutral, .btn-eliminar-neutral, .btn-compartir {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #b0b0b0 !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}
.btn-modificar-neutral:hover {
    background: #28a745 !important; color: white !important; border-color: #28a745 !important; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3) !important;
}
.btn-stats-neutral:hover {
    background: #17a2b8 !important; color: white !important; border-color: #17a2b8 !important; box-shadow: 0 4px 10px rgba(23, 162, 184, 0.3) !important;
}
.btn-eliminar-neutral:hover {
    background: #dc3545 !important; color: white !important; border-color: #dc3545 !important; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3) !important;
}
.btn-compartir:hover {
    background: #6f42c1 !important; color: white !important; border-color: #6f42c1 !important; box-shadow: 0 4px 10px rgba(111, 66, 193, 0.3) !important;
}
.btn-action.btn-reactivar {
    background: linear-gradient(135deg, #6f42c1, #59339d) !important;
    color: white !important;
    border: none !important;
}
.btn-action.btn-reactivar:hover {
    background: linear-gradient(135deg, #59339d, #45267c) !important;
    box-shadow: 0 4px 12px rgba(111, 66, 193, 0.35) !important;
}
</style>

            <?php if(isset($_SESSION['msg_error']) && $_SESSION['msg_error'] != ""): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        mostrarModalError('Error', '<?php echo addslashes($_SESSION['msg_error']); ?>');
                    });
                </script>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'password_updated'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        mostrarModalExito('¡Contraseña Actualizada!', 'Tu contraseña se ha actualizado correctamente.');
                    });
                </script>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'account_activated'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        mostrarModalExito('¡Cuenta Activada!', '¡Cuenta activada correctamente! Bienvenido a Código Amigo 🎉');
                    });
                </script>
            <?php endif; ?>
            
            <?php unset($_SESSION['msg_success']); ?>
            <?php unset($_SESSION['msg_error']); ?>
            <!-- Welcome Section -->
            <div style="background: #fff; color: #1a1a2e; padding: 24px 28px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-left: 4px solid #E30613;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="background: #f5f5f5; color: #E30613; padding: 10px; border-radius: 50%;">
                            <i class="fas fa-user" style="font-size: 1.3rem;"></i>
                        </div>
                        <div>
                            <h2 style="margin: 0 0 3px 0; font-size: 1.5rem; font-weight: 700; color: #1a1a2e;">
                                Hola <?php echo htmlspecialchars($data_usuario["username"] ?? "Usuario"); ?> 👋
                                <?php if (isset($is_vip_user) && $is_vip_user): ?>
                                    <span style="background: #FFF8E1; color: #F57C00; padding: 3px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #FFE0B2; margin-left: 8px; vertical-align: middle;">
                                        <i class="fas fa-crown"></i> VIP
                                    </span>
                                <?php endif; ?>
                            </h2>
                            <p style="margin: 0; color: #888; font-size: 0.9rem;">
                                Gestiona todos tus códigos de descuento
                                <?php if(isset($data_usuario["fecha_registro"])): ?>
                                    <?php
                                    $dias = null;
                                    $raw = $data_usuario["fecha_registro"]; 
                                    try {
                                        if ($raw instanceof MongoDB\BSON\UTCDateTime) {
                                            $fecha_registro_dt = $raw->toDateTime();
                                        } elseif (is_numeric($raw)) {
                                            $fecha_registro_dt = (new DateTime())->setTimestamp((int)$raw);
                                        } elseif (is_string($raw) && strtotime($raw)) {
                                            $fecha_registro_dt = new DateTime($raw);
                                        } else {
                                            $fecha_registro_dt = null;
                                        }
                                        if ($fecha_registro_dt) {
                                            $hoy = new DateTime();
                                            $dias = $hoy->diff($fecha_registro_dt)->days;
                                        }
                                    } catch (Throwable $e) {
                                        $dias = null;
                                    }
                                    ?>
                                    <?php if($dias !== null): ?>• <?php echo (int)$dias; ?> días en la plataforma<?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard de Potencial de Ganancias -->
            <div class="potential-summary-card" style="background: #fff; padding: 20px 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="background: #FFF5F5; color: #E30613; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: #999; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">Ganancias Potenciales</div>
                        <div style="display: flex; align-items: baseline; gap: 8px;">
                            <span style="font-size: 1.8rem; font-weight: 800; color: #1a1a2e; line-height: 1;"><?php echo number_format($total_potential, 2, ',', '.'); ?>€</span>
                            <span style="font-size: 0.85rem; color: #4CAF50; font-weight: 600;"><i class="fas fa-arrow-up"></i> <?php echo number_format($total_viewers, 0, ',', '.'); ?> interesados</span>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="text-align: right; max-width: 220px;">
                        <div style="font-size: 0.8rem; color: #999; line-height: 1.4;">Usuarios esperando tus códigos</div>
                    </div>
                    <button onclick='initMassMessageModal(<?php echo json_encode($all_viewer_ids); ?>, 0, <?php echo $total_potential; ?>)' 
                            style="background: #E30613; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; transition: all 0.2s ease; white-space: nowrap;">
                        <i class="fas fa-paper-plane"></i> Mensaje Masivo
                    </button>
                </div>
            </div>

            <!-- Compact Actions Bar -->
            <div style="background: #fff; padding: 16px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <div class="compact-actions-bar" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; align-items: center;">
                    
                    <!-- Mi Página Pública -->
                    <a href="<?php echo link_usuario($_SESSION["username"], $_SESSION["user_id"]); ?>" target="_blank" style="display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8f9fa; border-radius: 10px; text-decoration: none; cursor: pointer; transition: all 0.2s ease; border: 1px solid #eee;">
                        <div style="background: #EDE7F6; color: #7C4DFF; padding: 8px; border-radius: 8px;">
                            <i class="fas fa-external-link-alt" style="font-size: 1rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.85rem; color: #333;">Mi página pública</div>
                            <div style="font-size: 0.75rem; color: #999;">Comparte tu perfil</div>
                        </div>
                    </a>

                    <!-- Saldo -->
                    <div style="display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8f9fa; border-radius: 10px; cursor: pointer; transition: all 0.2s ease; border: 1px solid #eee;" onclick="document.getElementById('btn-recargar-saldo').click();">
                        <div style="background: #E8F5E9; color: #4CAF50; padding: 8px; border-radius: 8px;">
                            <i class="fas fa-piggy-bank" style="font-size: 1rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.85rem; color: #333;">Saldo</div>
                            <div style="font-size: 1rem; font-weight: 700; color: #4CAF50;">
                                <?php
                                $collection_usuarios = getCollectionUsuarios();
                                $usuario_actualizado = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION["user_id"])]);
                                $saldo_usuario = $usuario_actualizado['saldo'] ?? 0;
                                echo number_format($saldo_usuario, 2) . '€';
                                ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 6px;" onclick="event.stopPropagation();">
                            <button style="background: #E8F5E9; color: #4CAF50; padding: 6px 10px; border: none; border-radius: 6px; font-size: 0.7rem; font-weight: 600; cursor: pointer;" id="btn-recargar-saldo">
                                <i class="fas fa-plus" style="margin-right: 2px;"></i>Añadir
                            </button>
                            <a href="/public/historial_recargas.php" style="background: #f0f0f0; color: #666; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 0.7rem; font-weight: 600;">
                                <i class="fas fa-history" style="margin-right: 2px;"></i>Historial
                            </a>
                        </div>
                    </div>

                    <!-- Publicar -->
                    <a href="/nuevo_codigo" onclick="return confirmPublish();" style="display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8f9fa; border-radius: 10px; text-decoration: none; cursor: pointer; transition: all 0.2s ease; border: 1px solid #eee;">
                        <div style="background: #FFF3E0; color: #FF9800; padding: 8px; border-radius: 8px;">
                            <i class="fas fa-plus-circle" style="font-size: 1rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.85rem; color: #333;">Publicar código</div>
                            <div style="font-size: 0.75rem; color: #999;">Crea una nueva oferta</div>
                        </div>
                    </a>

                    <!-- Invitar amigos -->
                    <a href="/invitar-amigos" style="display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8f9fa; border-radius: 10px; text-decoration: none; cursor: pointer; transition: all 0.2s ease; border: 1px solid #eee;">
                        <div style="background: #FFEBEE; color: #E30613; padding: 8px; border-radius: 8px;">
                            <i class="fas fa-gift" style="font-size: 1rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.85rem; color: #333;">Invita amigos</div>
                            <div style="font-size: 0.75rem; color: #999;">Gana 5€ por cada uno</div>
                        </div>
                    </a>

                </div>
            </div>

            <!-- Métricas de rendimiento (tabla colapsable) -->
            <div style="background:#fff; border-radius:12px; margin-bottom:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden;">
                <button type="button" class="ma-metricas-toggle" onclick="maToggleMetricas(this)">
                    <span><i class="fas fa-chart-line"></i> Métricas de rendimiento</span>
                    <span style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:0.78rem; color:#9ca3af; font-weight:600;">Qué códigos funcionan mejor</span>
                        <i class="fas fa-chevron-down ma-metricas-chevron"></i>
                    </span>
                </button>
                <?php include __DIR__ . '/_mis_anuncios_metricas.php'; ?>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] === 'destacado'):
                // Obtener información del código destacado
                $codigo_id_modal = isset($_GET['codigo']) ? $_GET['codigo'] : '';
                $tipo_modal = isset($_GET['tipo']) && in_array($_GET['tipo'], ['normal','super']) ? $_GET['tipo'] : 'normal';
                
                $marca_nombre_modal = '';
                $marca_clave_modal = '';
                $enlaces_modal = '';
                
                try {
                    if ($codigo_id_modal) {
                        // Asegurar que las funciones estén disponibles
                        if (!function_exists('getObjectCodigo')) {
                            include_once __DIR__ . '/../myphp/funciones.php';
                        }
                        if (!function_exists('getObjectMarca')) {
                            include_once __DIR__ . '/../myphp/funciones.php';
                        }
                        
                        $codigo_modal = getObjectCodigo($codigo_id_modal);
                        
                        if ($codigo_modal && isset($codigo_modal['marca'])) {
                            $marca_clave_modal = $codigo_modal['marca'];
                            $marca_obj = getObjectMarca('nombre_clave', $marca_clave_modal);
                            
                            if ($marca_obj) {
                                $marca_nombre_modal = $marca_obj['nombre'] ?? $marca_clave_modal;
                                $marca_url_modal = '/de-' . $marca_clave_modal;
                                
                                if ($tipo_modal === 'normal') {
                                    $enlaces_modal = '<a href="' . htmlspecialchars($marca_url_modal) . '" style="color: #4CAF50; text-decoration: underline; font-weight: 600;">Ver en la página de ' . htmlspecialchars($marca_nombre_modal) . '</a>';
                                } else {
                                    $enlaces_modal = '<div style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px; align-items: center;">';
                                    $enlaces_modal .= '<a href="' . htmlspecialchars($marca_url_modal) . '" style="color: #4CAF50; text-decoration: underline; font-weight: 600;">Ver en la página de ' . htmlspecialchars($marca_nombre_modal) . '</a>';
                                    $enlaces_modal .= '<a href="/" style="color: #4CAF50; text-decoration: underline; font-weight: 600;">Ver en la página principal</a>';
                                    $enlaces_modal .= '</div>';
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Si hay error, usar valores por defecto
                    log_error("Error obteniendo información del código destacado: " . $e->getMessage());
                }
                
                // Mensaje por defecto si no se encontró la marca
                if (empty($marca_nombre_modal)) {
                    $mensaje_modal = 'Tu código ha sido destacado y aparecerá en primera posición con el badge "Destacado".';
                    if ($tipo_modal === 'super') {
                        $mensaje_modal = 'Tu código ha sido destacado y aparecerá en primera posición con badge dorado, en la página de la marca y en la página principal.';
                    }
                } else {
                    $mensaje_modal = 'Tu código de <strong>' . htmlspecialchars($marca_nombre_modal) . '</strong> ha sido destacado y aparecerá en primera posición con el badge "Destacado".';
                    if ($tipo_modal === 'super') {
                        $mensaje_modal = 'Tu código de <strong>' . htmlspecialchars($marca_nombre_modal) . '</strong> ha sido destacado y aparecerá en primera posición con badge dorado, en la página de ' . htmlspecialchars($marca_nombre_modal) . ' y en la página principal.';
                    }
                }
            ?>
                <script>
                    // Verificar si ya se mostró el modal para este código (usando localStorage)
                    var codigoDestacadoId = <?php echo json_encode($codigo_id_modal); ?>;
                    var modalKey = 'destacado_modal_' + codigoDestacadoId;
                    var yaMostrado = localStorage.getItem(modalKey);
                    
                    // Solo mostrar si no se ha mostrado antes o si han pasado más de 5 minutos
                    var mostrarModal = true;
                    if (yaMostrado) {
                        var timestamp = parseInt(yaMostrado);
                        var ahora = Date.now();
                        // Si pasaron menos de 5 minutos, no mostrar
                        if (ahora - timestamp < 300000) {
                            mostrarModal = false;
                        }
                    }
                    
                    if (mostrarModal) {
                        // Guardar timestamp en localStorage
                        localStorage.setItem(modalKey, Date.now().toString());
                        
                        // Guardar los datos en variables globales para usarlas después
                        window.destacadoModalData = {
                            titulo: '¡Código Destacado!',
                            mensaje: <?php echo json_encode($mensaje_modal); ?>,
                            enlaces: <?php echo json_encode($enlaces_modal); ?>
                        };
                        
                        // Función para mostrar el modal cuando esté listo
                        function mostrarModalDestacadoCuandoListo() {
                            if (typeof mostrarModalExitoDestacado === 'function') {
                                mostrarModalExitoDestacado(
                                    window.destacadoModalData.titulo,
                                    window.destacadoModalData.mensaje,
                                    window.destacadoModalData.enlaces
                                );
                                
                                // Limpiar la URL después de mostrar el modal (sin recargar)
                                if (window.history && window.history.replaceState) {
                                    var nuevaUrl = window.location.pathname;
                                    window.history.replaceState({}, document.title, nuevaUrl);
                                }
                            } else if (typeof mostrarModalExito === 'function') {
                                // Fallback a la función original
                                mostrarModalExito(
                                    window.destacadoModalData.titulo,
                                    window.destacadoModalData.mensaje.replace(/<[^>]*>/g, '')
                                );
                                
                                // Limpiar la URL después de mostrar el modal
                                if (window.history && window.history.replaceState) {
                                    var nuevaUrl = window.location.pathname;
                                    window.history.replaceState({}, document.title, nuevaUrl);
                                }
                            } else {
                                // Si aún no está disponible, intentar de nuevo después de un breve delay
                                setTimeout(mostrarModalDestacadoCuandoListo, 100);
                            }
                        }
                        
                        // Intentar mostrar el modal cuando el DOM esté listo
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', mostrarModalDestacadoCuandoListo);
                        } else {
                            // Si el DOM ya está cargado, esperar un poco más para que las funciones estén definidas
                            setTimeout(mostrarModalDestacadoCuandoListo, 500);
                        }
                    } else {
                        // Si ya se mostró, limpiar la URL de todos modos
                        if (window.history && window.history.replaceState) {
                            var nuevaUrl = window.location.pathname;
                            window.history.replaceState({}, document.title, nuevaUrl);
                        }
                    }
                </script>
            <?php endif; ?>

            <!-- Close dashboard cards container and start codes section -->
            <div style="clear: both;"></div>

            <style>
            /* Estilos para la barra compacta inspirada en Chollometro */
            .compact-actions-bar > div > div {
                transition: all 0.3s ease;
                cursor: pointer;
            }
            
            .compact-actions-bar > div > div:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
            }
            
            .compact-actions-bar a:hover {
                background: rgba(255, 255, 255, 0.3) !important;
                transform: scale(1.05);
            }
            
            .compact-actions-bar button:hover {
                background: rgba(255, 255, 255, 0.3) !important;
                transform: scale(1.05);
            }
            
            /* Responsive para móviles */
            @media (max-width: 768px) {
                .compact-actions-bar {
                    grid-template-columns: 1fr !important;
                }
                
                .compact-actions-bar > div > div {
                    padding: 12px !important;
                }
                
                .compact-actions-bar > div > div > div:first-child {
                    padding: 8px !important;
                }
                
                .compact-actions-bar > div > div > div:first-child i {
                    font-size: 1rem !important;
                }
            }
            
            @media (max-width: 480px) {
                .compact-actions-bar > div > div {
                    flex-direction: column;
                    text-align: center;
                    gap: 10px !important;
                }
                
                .compact-actions-bar > div > div > div:last-child {
                    flex-direction: row !important;
                    justify-content: center;
                }
            /* Animaciones para eliminación de códigos */
            .code-card.code-item {
                transition: all 0.3s ease;
            }
            
            .code-card.code-item.eliminando {
                opacity: 0;
                transform: translateX(-100%);
                pointer-events: none;
            }
            
            .no-codes-message {
                text-align: center;
                padding: 40px;
                color: #666;
                font-size: 1.2rem;
                background: #f8f9fa;
                border-radius: 10px;
                margin: 20px 0;
            }
            
            .no-codes-message a {
                color: #2196F3;
                text-decoration: none;
                font-weight: bold;
            }
            
            .no-codes-message a:hover {
                text-decoration: underline;
            }
            
            /* Mejorar la animación de fadeIn para códigos */
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .code-card.code-item {
                animation: fadeIn 0.3s ease-in;
            }
            </style>

            
            
        </div>
        
        
        <!-- Lista de códigos -->
        <div class="codes-section">
            <div class="codes-header">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
                    

                    
                </div>
                
                <?php if (!$mostrando_todos && $codigos_ocultos > 0): ?>
                <div class="alert alert-info" style="margin-top: 10px; padding: 10px; background: #e3f2fd; border: 1px solid #2196f3; border-radius: 4px; color: #1976d2;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Información:</strong> Tienes <?php echo number_format($total_codigos_usuario); ?> códigos en total. 
                    Se están mostrando los <?php echo number_format($num_codigos); ?> más recientes. 
                    <?php if ($codigos_ocultos > 0): ?>
                        <span style="color: #d32f2f;"><?php echo number_format($codigos_ocultos); ?> códigos más antiguos están ocultos.</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Placeholder evita layout shift cuando search pasa a fixed -->
            <div class="ma-search-placeholder" id="ma-search-placeholder"></div>
            <!-- Filtro de texto sticky (JS lo conmuta a position:fixed al hacer scroll) -->
            <div class="ma-search-sticky" id="ma-search-sticky">
                <div style="position: relative; max-width: 480px; margin: 0 auto;">
                    <input type="text" id="textFilter" placeholder="Buscar por marca, descripción o código..."
                           style="color: #555; width: 100%; padding: 12px 16px 12px 44px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 0.9rem; background: #f8f9fa; transition: all 0.2s ease; box-sizing: border-box;"
                           onkeyup="filterByText(this.value)">
                    <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 0.95rem;"></i>
                    <button id="clearTextFilter" onclick="clearTextFilter()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #aaa; cursor: pointer; font-size: 1rem; display: none;" title="Limpiar búsqueda">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Filtros de códigos -->
            <div style="background: #fff; padding: 20px 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">

                <!-- Tabs por ESTADO (server-side, paginadas) -->
                <?php
                $estado_tab_actual = isset($estado_tab) ? $estado_tab : 'todos';
                $tabs_estado = [
                    ['key' => 'todos',        'label' => 'Todos',        'icon' => 'fa-list',          'count' => $total_codigos_usuario, 'siempre' => true],
                    ['key' => 'activos',      'label' => '✅ Activos',     'icon' => 'fa-check-circle',  'count' => $num_activos,           'siempre' => true],
                    ['key' => 'destacados',   'label' => '🌟 Destacados',  'icon' => 'fa-star',          'count' => $num_destacados,        'siempre' => $num_destacados > 0],
                    ['key' => 'caducados',    'label' => '⏰ Caducados',   'icon' => 'fa-clock',         'count' => $num_caducados,         'siempre' => $num_caducados > 0],
                    ['key' => 'desactivados', 'label' => '🚫 Desactivados','icon' => 'fa-ban',           'count' => $num_desactivados,      'siempre' => $num_desactivados > 0],
                    ['key' => 'inactivos',    'label' => '⏸️ Inactivos',   'icon' => 'fa-pause-circle',  'count' => $num_inactivos,         'siempre' => $num_inactivos > 0],
                ];
                ?>
                <div style="margin-bottom: 8px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; border-bottom: 2px solid #f0f0f0; padding-bottom: 12px;">
                        <?php foreach ($tabs_estado as $tab): if (!$tab['siempre']) continue; ?>
                            <?php $activo = $tab['key'] === $estado_tab_actual; ?>
                            <a href="?estado=<?php echo $tab['key']; ?>"
                               class="ma-tab <?php echo $activo ? 'active' : ''; ?>"
                               style="<?php echo $activo
                                   ? 'background:#E30613;color:white;border:1px solid #E30613;'
                                   : 'background:#f8f9fa;color:#555;border:1px solid #e0e0e0;'; ?>
                                   padding:10px 18px;border-radius:10px;font-weight:600;font-size:0.9rem;
                                   text-decoration:none;display:inline-flex;align-items:center;gap:6px;
                                   transition:all 0.2s ease;">
                                <i class="fas <?php echo $tab['icon']; ?>"></i>
                                <?php echo $tab['label']; ?>
                                <span style="<?php echo $activo
                                    ? 'background:rgba(255,255,255,0.25);'
                                    : 'background:#eee;'; ?>
                                    padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700;">
                                    <?php echo number_format($tab['count'], 0, ',', '.'); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sub-filtros por visibilidad (sólo informativo, client-side dentro de la página actual) -->
                <?php if ($estado_tab_actual === 'activos' || $estado_tab_actual === 'todos'): ?>
                <div style="margin-top: 12px; display:flex; flex-wrap:wrap; gap:10px; font-size:0.8rem; color:#777;">
                    <span><i class="fas fa-star" style="color:#FFC107;"></i> Posición 1: <strong><?php echo $num_1_codes; ?></strong></span>
                    <span><i class="fas fa-eye" style="color:#2196F3;"></i> Posición 2: <strong><?php echo $num_2_codes; ?></strong></span>
                    <span><i class="fas fa-moon" style="color:#9E9E9E;"></i> Posición 3+: <strong><?php echo $num_3_codes; ?></strong></span>
                    <span style="opacity:0.6;">(en esta página)</span>
                </div>
                <?php endif; ?>

                <?php 
                // Calcular destacados caducados
                $destacados_caducados = [];
                if (isset($listado_codigos) && is_array($listado_codigos)) {
                    foreach ($listado_codigos as $c) {
                        if (isset($c['destacado']) && (int)$c['destacado'] > 0 && isset($c['fecha_fin_destacado'])) {
                            $fin = $c['fecha_fin_destacado'];
                            if ($fin instanceof MongoDB\BSON\UTCDateTime) {
                                $fin_ts = $fin->toDateTime()->getTimestamp();
                            } elseif (is_numeric($fin)) {
                                $fin_ts = (int)$fin;
                            } else {
                                $fin_ts = strtotime((string)$fin);
                            }
                            // Si ya pasó la fecha fin y el código sigue estando "activo" (estado=0)
                            if ($fin_ts < time() && (!isset($c['estado']) || (int)$c['estado'] === 0)) {
                                $destacados_caducados[] = (string)$c['_id'];
                            }
                        }
                    }
                }
                $num_destacados_caducados = count($destacados_caducados);
                ?>

                <?php if ($num_caducados > 0): ?>
                <div id="btn-reactivar-todos-container" style="display: none; margin-top: 12px; text-align: center;">
                    <button onclick="reactivarTodosLosCodigos()" style="background: #E30613; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; transition: all 0.2s ease;">
                        <i class="fas fa-sync-alt"></i> Reactivar todos los caducados (<?php echo $num_caducados; ?>)
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($num_destacados_caducados > 0): ?>
                <div style="margin-top: 15px; margin-bottom: 20px;">
                    <button onclick="renovarDestacadosMasivo(<?php echo htmlspecialchars(json_encode($destacados_caducados)); ?>)" style="width: 100%; background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 15px 20px; border: none; border-radius: 12px; font-weight: 700; font-size: 1.05rem; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3); border: 1px solid rgba(255,255,255,0.2);">
                        <i class="fas fa-bolt" style="margin-right: 8px; font-size: 1.2rem;"></i> 
                        Renovar <?php echo $num_destacados_caducados; ?> código(s) destacado(s) caducado(s) a la vez
                    </button>
                </div>
                <?php endif; ?>
                <?/*<br>

                <button onclick="destacarTodosCodigos()" style="width: 100%;background: linear-gradient(135deg, #FF9800, #F57C00); color: white; padding: 24px 30px; border: none; border-radius: 18px; font-weight: 700; font-size: 1.15rem; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 8px 25px rgba(255, 152, 0, 0.3); border: 2px solid transparent; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; left: -100%;  height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); transition: left 0.5s;"></div>
                    <div style="margin-bottom: 8px;">
                        <i class="fas fa-star" style="font-size: 2.2rem; color: rgba(255,255,255,0.9);"></i>
                    </div>
                    <div>
                        <span style="display: block; line-height: 1.2; font-size: 1.1rem;">⭐ Destacar todos mis códigos</span>
                        <span style="display: block; font-size: 0.9rem; opacity: 0.9; margin-top: 4px;">9,99€ - Ahorra vs individual</span>
                    </div>
                </button>


                <!-- Explicación de la función destacar -->
            <div style="background: linear-gradient(135deg, #FFF3E0, #FFE0B2); padding: 25px; border-radius: 18px; margin-bottom: 40px; border-left: 6px solid #FF9800; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.1);">
                <div style="display: flex; align-items: flex-start; gap: 18px;">
                    <div style="background: #FF9800; color: white; padding: 12px; border-radius: 50%; flex-shrink: 0;">
                        <i class="fas fa-lightbulb" style="font-size: 1.4rem;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0 0 12px 0; color: #E65100; font-size: 1.25rem; font-weight: 700; line-height: 1.3;">💡 ¿Qué hace "Destacar todos mis códigos"?</h3>
                        <p style="margin: 0 0 15px 0; color: #BF360C; font-size: 1.05rem; line-height: 1.6;">
                            Con un solo click, todos tus códigos aparecerán en las <strong style="color: #D84315;">primeras posiciones</strong> de cada marca. Así serán mucho más visibles para los usuarios que buscan códigos de descuento.
                        </p>
                        <p style="margin: 0; font-size: 0.95rem; color: #E65100; font-weight: 600; background: rgba(255, 152, 0, 0.1); padding: 8px 12px; border-radius: 8px; display: inline-block;">
                            💰 Costo: 9,99€ <span style="font-weight: 400; color: #2E7D32;">(¡Ahorra vs destacar individual!)</span>
                        </p>
                    </div>
                </div>
            </div>*/?>
              

                
            </div>
        
        <?php if($listado_codigos && count($listado_codigos) > 0): ?>
            <div class="sort-controls" style="display: flex; align-items: center; gap: 12px;">
                        <label for="sortSelect" style="color: #495057; font-weight: 600; font-size: 1rem; white-space: nowrap;">Ordenar por:</label>
                        <select id="sortSelect" onchange="sortCodes(this.value)" style="padding: 12px 16px; border: 2px solid #e9ecef; border-radius: 10px; background: white; color: #495057; font-weight: 500; cursor: pointer; transition: all 0.3s ease; min-width: 160px; font-size: 1rem;">
                            <option value="fecha_desc" selected>Más recientes</option>
                            <option value="fecha_asc">Más antiguos</option>
                            <option value="marca_asc">Marca A-Z</option>
                            <option value="marca_desc">Marca Z-A</option>
                            <option value="clicks_desc">Más visitados</option>
                            <option value="clicks_asc">Menos visitados</option>
                            <option value="beneficio_desc">Mayor beneficio</option>
                            <option value="beneficio_asc">Menor beneficio</option>
                        </select>
                    </div>
            <div class="codes-grid" id="ma-codes-grid" data-estado="<?php echo htmlspecialchars($estado_tab); ?>" data-pagina="1" data-total="<?php echo (int)$total_filtrado; ?>">
            <?php foreach($listado_codigos as $codigo): include __DIR__ . '/_mis_anuncios_card.php'; endforeach; ?>
            </div>

            <!-- Sentinel infinite scroll -->
            <div id="ma-sentinel" style="height:1px;"></div>
            <div id="ma-loader" style="text-align:center; padding:24px; color:#999; display:none;">
                <i class="fas fa-spinner fa-spin"></i> Cargando más códigos…
            </div>
            <div id="ma-end" style="text-align:center; color:#999; font-size:0.85rem; margin:20px 0 30px; display:none;">
                <span id="ma-end-text">No hay más códigos</span>
            </div>
            <div id="ma-counter" style="text-align:center; color:#999; font-size:0.85rem; margin:8px 0 24px;">
                <span id="ma-counter-text"><?php echo number_format($total_filtrado, 0, ',', '.'); ?> códigos en total</span>
            </div>

            <?php /* Bloque original eliminado por infinite scroll */ ?>
        <?php else: ?>
            <div style="background: white; border-radius: 20px; padding: 50px; text-align: center; box-shadow: 0 6px 20px rgba(0,0,0,0.1); margin: 40px 0;">
                <div style="font-size: 5rem; margin-bottom: 20px;">🎯</div>
                <h2 style="margin: 0 0 20px 0; color: #333; font-size: 2rem;">¡Aún no tienes códigos publicados!</h2>
                <p style="margin: 0 0 30px 0; color: #666; font-size: 1.2rem; line-height: 1.6;">
                    Comienza a publicar tus códigos de descuento para ayudar a otros usuarios a ahorrar dinero.
                    <br><br>
                    <strong>¿Por qué publicar códigos?</strong><br>
                    • Otras personas usarán tus códigos y te darán una comisión<br>
                    • Ayudas a la comunidad a encontrar las mejores ofertas<br>
                    • Ganas dinero por cada compra que generes
                </p>
                <a href="/nuevo_codigo" style="background: #4CAF50; color: white; padding: 15px 30px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 1.1rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);">
                    <i class="fas fa-plus-circle"></i>
                    Publicar mi primer código
                </a>

                <div style="margin-top: 30px; padding: 20px; background: #E8F5E8; border-radius: 10px; border-left: 5px solid #4CAF50;">
                    <p style="margin: 0; color: #2E7D32; font-size: 1rem;">
                        💡 <strong>Consejo:</strong> Empieza con códigos de tiendas que conozcas bien. ¡Es muy fácil y puedes empezar a ganar dinero desde el primer día!
                    </p>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>


<!-- Modal para recargar saldo - Diseño premium CodigoAmigo -->
<div class="modal fade" id="modal_recargar_saldo" tabindex="-1" role="dialog" aria-labelledby="modalRecargarSaldoLabel">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" id="modal_saldo_content">
            <!-- Header -->
            <div id="modal_saldo_header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; background: #E30613; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-wallet" style="font-size: 1rem; color: white;"></i>
                    </div>
                    <h4 class="modal-title" id="modalRecargarSaldoLabel" style="font-size: 1.15rem; margin: 0; font-weight: 700; color: #fff;">Recargar Saldo</h4>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="background: rgba(255,255,255,0.1); border: none; color: rgba(255,255,255,0.6); width: 32px; height: 32px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; padding: 0; margin: 0; position: static; opacity: 1; line-height: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Subtítulo -->
            <div style="padding: 16px 20px 0 20px; text-align: center; background: #1c1c2e;">
                <p style="margin: 0; color: #aaa; font-size: 0.85rem; font-weight: 400;">Selecciona un paquete. Cuanto más añadas, más recibes.</p>
            </div>

            <!-- Paquetes -->
            <div class="modal-body" id="modal_saldo_body">
                <!-- Paquete 20€ -->
                <div class="package-card saldo-pkg" data-package="20" data-amount="25">
                    <div class="saldo-pkg-left">
                        <div class="saldo-pkg-price">20€</div>
                        <div class="saldo-pkg-sub">Recibes <span>25€</span></div>
                    </div>
                    <div class="saldo-pkg-right">
                        <div class="saldo-pkg-bonus">+5€</div>
                        <div class="saldo-pkg-pct">+25%</div>
                    </div>
                </div>

                <!-- Paquete 40€ - Recomendado -->
                <div class="package-card saldo-pkg saldo-pkg-best" data-package="40" data-amount="50">
                    <div class="saldo-pkg-best-badge">RECOMENDADO</div>
                    <div class="saldo-pkg-left">
                        <div class="saldo-pkg-price">40€</div>
                        <div class="saldo-pkg-sub">Recibes <span>50€</span></div>
                    </div>
                    <div class="saldo-pkg-right">
                        <div class="saldo-pkg-bonus">+10€</div>
                        <div class="saldo-pkg-pct">+25%</div>
                    </div>
                </div>

                <!-- Paquete 100€ -->
                <div class="package-card saldo-pkg" data-package="100" data-amount="150">
                    <div class="saldo-pkg-left">
                        <div class="saldo-pkg-price">100€</div>
                        <div class="saldo-pkg-sub">Recibes <span>150€</span></div>
                    </div>
                    <div class="saldo-pkg-right">
                        <div class="saldo-pkg-bonus">+50€</div>
                        <div class="saldo-pkg-pct">+50%</div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div id="modal_saldo_footer">
                <i class="fas fa-lock" style="font-size: 0.7rem; opacity: 0.5;"></i>
                <span>Pago seguro con Stripe · Sin almacenar datos</span>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== Modal Recargar Saldo - Premium CodigoAmigo ===== */
#modal_saldo_content {
    border-radius: 16px !important;
    border: none !important;
    box-shadow: 0 25px 60px rgba(0,0,0,0.5) !important;
    overflow: hidden !important;
    max-width: 400px !important;
    margin: 0 auto !important;
    background: #1c1c2e !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

#modal_saldo_header {
    background: #1c1c2e !important;
    padding: 18px 20px 0 20px !important;
    border: none !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
}

#modal_saldo_header .close:hover {
    background: rgba(255,255,255,0.2) !important;
    color: #fff !important;
}

#modal_saldo_body {
    padding: 14px 16px 10px 16px !important;
    background: #1c1c2e !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 10px !important;
}

/* === Package cards === */
.saldo-pkg {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 16px 18px !important;
    border-radius: 12px !important;
    border: 1.5px solid rgba(255,255,255,0.08) !important;
    background: rgba(255,255,255,0.04) !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    position: relative !important;
    -webkit-tap-highlight-color: transparent;
}

.saldo-pkg:hover {
    border-color: #E30613 !important;
    background: rgba(227, 6, 19, 0.06) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 15px rgba(227, 6, 19, 0.1) !important;
}

.saldo-pkg:active {
    transform: scale(0.98) !important;
}

/* Best/recommended package */
.saldo-pkg-best {
    border-color: #E30613 !important;
    background: rgba(227, 6, 19, 0.08) !important;
    box-shadow: 0 0 0 1px rgba(227, 6, 19, 0.15) !important;
}

.saldo-pkg-best-badge {
    position: absolute;
    top: -9px;
    left: 50%;
    transform: translateX(-50%);
    background: #E30613;
    color: white;
    font-size: 0.6rem;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 4px;
    white-space: nowrap;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* Left side */
.saldo-pkg-left {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.saldo-pkg-price {
    font-size: 1.6rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
}

.saldo-pkg-sub {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.45);
    font-weight: 400;
}

.saldo-pkg-sub span {
    color: #E30613;
    font-weight: 700;
}

/* Right side */
.saldo-pkg-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 3px;
}

.saldo-pkg-bonus {
    font-size: 1rem;
    font-weight: 800;
    color: #E30613;
    line-height: 1;
}

.saldo-pkg-pct {
    font-size: 0.65rem;
    color: rgba(255,255,255,0.35);
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Footer */
#modal_saldo_footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px 20px 16px 20px;
    color: rgba(255,255,255,0.3);
    font-size: 0.7rem;
    background: #1c1c2e;
    border-radius: 0 0 16px 16px;
}

/* Selected state */
.saldo-pkg.selected {
    border-color: #E30613 !important;
    background: rgba(227, 6, 19, 0.12) !important;
    box-shadow: 0 0 0 2px rgba(227, 6, 19, 0.2) !important;
}
</style>

<!-- Modal para destacar todos (splash) -->
<div class="modal fade" id="modal_destacar_todos" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Destacar Todos los Códigos (Splash)</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres destacar todos tus códigos en posición 1 por 9,99€?</p>
                <p>Esta acción destacará todos tus códigos de una vez con máxima visibilidad.</p>
                <p><strong>Costo total: 9,99€</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmar_destacar_todos">Confirmar y Pagar</button>
            </div>
        </div>
    </div>
</div>

<!-- Cargar Stripe -->
<script src="https://js.stripe.com/v3/"></script>

<script type="text/javascript">
var stripe = Stripe('<?php echo $stripe_live_publishable_key; ?>');

// Función global para filtrar por texto
window.filterByText = function(searchText) {
    const textFilter = document.getElementById('textFilter');
    const clearButton = document.getElementById('clearTextFilter');
    
    // Mostrar/ocultar botón de limpiar
    if (searchText.length > 0) {
        clearButton.style.display = 'block';
    } else {
        clearButton.style.display = 'none';
    }
    
    // Obtener todas las tarjetas de código
    const codeItems = document.querySelectorAll('.code-item');
    let visibleCount = 0;
    
    codeItems.forEach(function(item) {
        const marca = item.getAttribute('data-marca') || '';
        const descElement = item.querySelector('p[id^="desc"]');
        const descripcion = descElement ? descElement.textContent : '';
        const codigoElement = item.querySelector('p[style*="monospace"]');
        const codigo = codigoElement ? codigoElement.textContent : '';
        
        const searchLower = searchText.toLowerCase();
        const marcaLower = marca.toLowerCase();
        const descripcionLower = descripcion.toLowerCase();
        const codigoLower = codigo.toLowerCase();
        
        const shouldShow = searchText === '' || 
                         marcaLower.includes(searchLower) || 
                         descripcionLower.includes(searchLower) || 
                         codigoLower.includes(searchLower);
        
        if (shouldShow) {
            item.style.display = 'block';
            item.style.animation = 'fadeIn 0.3s ease-in';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Actualizar contadores en los botones de filtro
    updateFilterCounts();
    
    // Mostrar mensaje si no hay resultados
    showNoResultsMessage(visibleCount === 0 && searchText.length > 0, 'No se encontraron códigos que coincidan con "' + searchText + '"');
};

// Función para limpiar el filtro de texto
window.clearTextFilter = function() {
    const textFilter = document.getElementById('textFilter');
    const clearButton = document.getElementById('clearTextFilter');
    
    textFilter.value = '';
    clearButton.style.display = 'none';
    
    // Mostrar todos los códigos
    const codeItems = document.querySelectorAll('.code-item');
    codeItems.forEach(function(item) {
        item.style.display = 'block';
    });
    
    updateFilterCounts();
    showNoResultsMessage(false);
};

// Función para actualizar contadores de filtros
function updateFilterCounts() {
    const codeItems = document.querySelectorAll('.code-item');
    const visibleItems = document.querySelectorAll('.code-item:not([style*="display: none"])');
    
    // Actualizar contador de "Todos"
    const allButton = document.querySelector('[data-visibility="all"]');
    if (allButton) {
        allButton.innerHTML = `<i class="fas fa-list" style="margin-right: 8px;"></i>📋 Todos mis códigos (${visibleItems.length})`;
    }
    
    // Contar por visibilidad solo de los elementos visibles
    let altaCount = 0, mediaCount = 0, bajaCount = 0;
    visibleItems.forEach(function(item) {
        const visibilidad = item.getAttribute('data-visibilidad');
        if (visibilidad === 'alta') altaCount++;
        else if (visibilidad === 'media') mediaCount++;
        else if (visibilidad === 'baja') bajaCount++;
    });
    
    // Actualizar botones de visibilidad
    const altaButton = document.querySelector('[data-visibility="alta"]');
    const mediaButton = document.querySelector('[data-visibility="media"]');
    const bajaButton = document.querySelector('[data-visibility="baja"]');
    
    if (altaButton) altaButton.innerHTML = `<i class="fas fa-star" style="margin-right: 8px;"></i>⭐ Más visibles (${altaCount})`;
    if (mediaButton) mediaButton.innerHTML = `<i class="fas fa-eye" style="margin-right: 8px;"></i>👁️ Visibles (${mediaCount})`;
    if (bajaButton) bajaButton.innerHTML = `<i class="fas fa-eye-slash" style="margin-right: 8px;"></i>😴 Poco visibles (${bajaCount})`;
}

// Función para mostrar mensaje de no resultados
function showNoResultsMessage(show, message = '') {
    let noResultsMessage = document.getElementById('no-results-message');
    
    if (show && !noResultsMessage) {
        noResultsMessage = document.createElement('div');
        noResultsMessage.id = 'no-results-message';
        noResultsMessage.className = 'no-results';
        noResultsMessage.innerHTML = `
            <i class="fas fa-search"></i>
            <h3>No se encontraron códigos</h3>
            <p>${message}</p>
        `;
        document.querySelector('.codes-section').appendChild(noResultsMessage);
    } else if (noResultsMessage) {
        noResultsMessage.style.display = show ? 'block' : 'none';
        if (show && message) {
            noResultsMessage.querySelector('p').textContent = message;
        }
    }
}

// Función global para filtrar por visibilidad
window.filterByVisibility = function(visibility) {
    // Actualizar botones activos
    document.querySelectorAll('.filter-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Marcar el botón seleccionado como activo
    const selectedButton = document.querySelector(`[data-visibility="${visibility}"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
    
    // Obtener todas las tarjetas de código
    const codeItems = document.querySelectorAll('.code-item');
    let visibleCount = 0;
    
    codeItems.forEach(function(item) {
        const itemVisibility = item.getAttribute('data-visibilidad');
        let shouldShow = false;
        
        if (visibility === 'all') {
            shouldShow = true;
        } else if (visibility === 'alta' && itemVisibility === 'alta') {
            shouldShow = true;
        } else if (visibility === 'media' && itemVisibility === 'media') {
            shouldShow = true;
        } else if (visibility === 'baja' && itemVisibility === 'baja') {
            shouldShow = true;
        }
        
        if (shouldShow) {
            item.style.display = 'block';
            item.style.animation = 'fadeIn 0.3s ease-in';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Actualizar contadores
    updateFilterCounts();
    
    // Mostrar mensaje si no hay resultados
    const visibilityNames = {
        'alta': 'alta visibilidad',
        'media': 'media visibilidad', 
        'baja': 'baja visibilidad'
    };
    const message = visibility !== 'all' ? `No hay códigos con ${visibilityNames[visibility] || visibility} visibilidad` : '';
    showNoResultsMessage(visibleCount === 0 && visibility !== 'all', message);
};

</script>
<style>
/* Estilos adicionales para el diseño mejorado */
.filter-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.code-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Animación suave para los filtros */
.filter-button {
    animation: fadeInUp 0.3s ease-out;
}

/* Mejorar accesibilidad */
.filter-button:focus,
.code-card button:focus,
.code-card a:focus {
    outline: 2px solid #2196F3;
    outline-offset: 2px;
}

/* Responsive mejorado */
@media (max-width: 768px) {
    .main-actions {
        grid-template-columns: 1fr;
    }

    .balance-section .d-flex {
        flex-direction: column;
    }

    .code-card {
        padding: 20px;
    }

    .code-card .main-actions {
        grid-template-columns: 1fr 1fr;
    }

    .package-card {
        padding: 20px 15px;
    }

    .package-card.popular {
        transform: none;
    }
}

/* Estilos para los paquetes de saldo */
.package-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.package-card:not(.popular):hover {
    border-color: #2196F3;
}

.package-card.popular:hover {
    transform: translateY(-5px) scale(1.05);
}

/* Animación para seleccionar paquete */
.package-card.selected {
    border-color: #4CAF50;
    background: #E8F5E8;
    animation: pulse 0.3s ease-in-out;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

/* Mejorar el header principal */
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    color: white;
    text-align: center;
}

/* Content wrapper styles moved to CSS file */
</style>


<!-- Footer con sección de ayuda -->
<footer class="footer-modern" style="background: #2c2c2c; padding: 40px 20px; margin-top: auto; text-align: center; position: relative; bottom: 0; left: 0; right: 0; width: 100%;">
    <div class="container" style="max-width: 1200px; margin: 0 auto;">
        <div class="help-section" style="background: linear-gradient(135deg, #E30613, #C40510); padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(227, 6, 19, 0.3);">
            <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 15px;">
                <i class="fa-brands fa-telegram" style="font-size: 2.5rem; color: white;"></i>
                <div>
                    <h3 style="color: white; margin: 0; font-size: 1.8rem; font-weight: bold;">¿Necesitas ayuda?</h3>
                    <p style="color: white; margin: 5px 0 0 0; font-size: 1.2rem; opacity: 0.9;">¡Escríbenos!</p>
                </div>
            </div>
            <a href="https://t.me/spnfury" target="_blank" style="display: inline-block; background: white; color: #E30613; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: bold; font-size: 1.1rem; transition: all 0.3s ease; box-shadow: 0 3px 10px rgba(0,0,0,0.2);">
                <i class="fa-brands fa-telegram" style="margin-right: 8px;"></i>
                Contactar por Telegram
            </a>
        </div>
        
        <div class="footer-links" style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px; flex-wrap: wrap;">
            <a href="/" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Inicio</a>
            <a href="/ultimos-codigos" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Todos los Códigos</a>
            <a href="/listado-marcas" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Marcas</a>
            <a href="/listado-categorias" style="color: #cccccc; text-decoration: none; font-size: 1rem; transition: color 0.3s ease;">Categorías</a>
        </div>
        
        <div class="footer-bottom" style="border-top: 1px solid #404040; padding-top: 20px; color: #888888; font-size: 0.9rem;">
            <p style="margin: 0;">© 2024 Código Amigo - Códigos verificados, gente real</p>
        </div>
    </div>
</footer>


<!-- Cerrar el HTML correctamente -->
</body>
</html>