<?php
// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';

// Validar sesión de usuario
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
    header('Location: /?login=1');
    die();
}

$user_id = $_SESSION["user_id"];

// Verificar si es admin para mostrar acceso rápido
$array_admins = [
    "58bd851da54e295b8b52f702",
    "5e78170e6b68e6519b7c5df2",
    "639899bc6321ee0d0e4010d2",
    "5c8a10ce2f55c86d6e707d82"
];
$es_admin_chat = in_array($user_id, $array_admins);
$es_vip_chat = es_usuario_vip($user_id);

// Obtener datos del usuario
$data_usuario = get_object_user('_id', new MongoDB\BSON\ObjectId($user_id));

if (!$data_usuario) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$username = $data_usuario['username'] ?? 'Usuario';
$title = "Chat - $username";
$description = "Sistema de mensajería de CodigoAmigo";

// Paywall VIP: chat exclusivo para VIPs / admins
if (!$es_vip_chat && !$es_admin_chat) {
    // Contar mensajes pendientes para teaser
    $mensajes_pendientes = 0;
    try {
        if (function_exists('getCollectionMensajes')) {
            $coll_msg = getCollectionMensajes();
            if ($coll_msg) {
                $mensajes_pendientes = $coll_msg->countDocuments([
                    'para_usuario_id' => ['$in' => [$user_id, new MongoDB\BSON\ObjectId($user_id)]],
                    'leido' => false
                ]);
            }
        }
    } catch (Exception $e) {
        $mensajes_pendientes = 0;
    }

    include_once __DIR__ . '/../myphp/_header_modern.php';
    $GLOBALS['header_modern_used'] = true;
    $GLOBALS['anula_adsense'] = true; // Sin publicidad en el chat, molesta y distrae de conversar
    get_header_modern("Chat VIP - CódigoAmigo", "Mensajería directa exclusiva para usuarios VIP", '', '', '', false);
    ?>
    <div class="vip-chat-paywall">
        <div class="paywall-card">
            <div class="paywall-crown">
                <i class="fas fa-crown"></i>
            </div>
            <h1>Mensajería directa solo para VIP</h1>
            <?php if ($mensajes_pendientes > 0): ?>
                <p class="paywall-pending">
                    <i class="fas fa-envelope"></i>
                    Tienes <strong><?php echo (int)$mensajes_pendientes; ?></strong>
                    <?php echo $mensajes_pendientes === 1 ? 'mensaje sin leer' : 'mensajes sin leer'; ?>
                </p>
            <?php endif; ?>
            <p class="paywall-desc">
                Habla directamente con publicadores y usuarios interesados en tus códigos.
                Cierra acuerdos sin intermediarios y maximiza tus beneficios.
            </p>
            <ul class="paywall-features">
                <li><i class="fas fa-check-circle"></i> Lee y responde mensajes ilimitados</li>
                <li><i class="fas fa-check-circle"></i> Contacta a quien ve tus códigos</li>
                <li><i class="fas fa-check-circle"></i> Badge dorado en tu perfil</li>
                <li><i class="fas fa-check-circle"></i> 10€/mes de saldo de regalo</li>
            </ul>
            <a href="/suscripciones_y_creditos" class="paywall-cta">
                <i class="fas fa-crown"></i> Hazte VIP — 9,99€/mes
            </a>
            <p class="paywall-small">Cancela cuando quieras. Sin permanencia.</p>
        </div>
    </div>
    <style>
    .vip-chat-paywall {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        background: linear-gradient(135deg, #fff8e6 0%, #fff 60%);
    }
    .paywall-card {
        max-width: 560px;
        width: 100%;
        background: white;
        border-radius: 24px;
        padding: 40px 36px;
        text-align: center;
        box-shadow: 0 25px 60px rgba(227, 6, 19, 0.15);
        border: 1px solid #fde8a8;
    }
    .paywall-crown {
        width: 80px;
        height: 80px;
        margin: 0 auto 18px;
        background: linear-gradient(135deg, #ffd700, #f9a825);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 12px 30px rgba(249, 168, 37, 0.35);
    }
    .paywall-crown i { color: white; font-size: 36px; }
    .paywall-card h1 {
        font-size: 1.7rem;
        font-weight: 800;
        color: #1a1a1a;
        margin: 0 0 14px;
    }
    .paywall-pending {
        background: linear-gradient(135deg, #fff3cd, #ffe69c);
        color: #7a5400;
        font-weight: 700;
        padding: 12px 18px;
        border-radius: 12px;
        margin: 0 0 18px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .paywall-desc {
        color: #555;
        font-size: 1rem;
        line-height: 1.5;
        margin: 0 0 22px;
    }
    .paywall-features {
        list-style: none;
        padding: 0;
        margin: 0 0 26px;
        text-align: left;
        display: inline-block;
    }
    .paywall-features li {
        padding: 6px 0;
        color: #333;
        font-size: 0.95rem;
        font-weight: 500;
    }
    .paywall-features li i {
        color: #28a745;
        margin-right: 8px;
    }
    .paywall-cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: linear-gradient(135deg, #E30613, #FF4D4D);
        color: white !important;
        text-decoration: none !important;
        padding: 16px 36px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 1.05rem;
        box-shadow: 0 12px 30px rgba(227, 6, 19, 0.35);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .paywall-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 18px 40px rgba(227, 6, 19, 0.45);
    }
    .paywall-small {
        margin: 14px 0 0;
        color: #888;
        font-size: 0.8rem;
    }
    </style>
    <?php
    if (function_exists('get_footer_modern')) { get_footer_modern(); }
    die();
}

// Incluir header moderno
include_once __DIR__ . '/../myphp/_header_modern.php';
$GLOBALS['header_modern_used'] = true;
$GLOBALS['anula_adsense'] = true; // Sin publicidad en el chat, molesta y distrae de conversar

// Renderizar header
get_header_modern($title, $description, '', '', '', false);
?>

<link href="/css/chat-modern-v2.css?v=<?php echo time(); ?>" rel="stylesheet">
<link href="/css/usuario-chat.css?v=<?php echo time(); ?>" rel="stylesheet">
<style>
    body {
        /* Remove full-screen forced overrides so standard header shows */
    }
</style>

<div class="chat-usuario-container">
    <div class="chat-layout">
        <aside class="chat-sidebar">
            <div class="chat-sidebar-top">
                <div class="sidebar-title">
                    <div>
                        <span class="sidebar-label">Bandeja de entrada</span>
                        <p class="sidebar-helper">Mensajes que recibes de la comunidad</p>
                    </div>
                </div>
                <div class="chat-tabs">
                    <button type="button" class="chat-tab active" data-tab="inbox">Mensajes</button>
                    <button type="button" class="chat-tab" data-tab="archived">
                        <i class="fas fa-archive" style="font-size: 0.75rem;"></i> Archivados
                        <span class="chat-tab-badge" id="archivedBadge" hidden>0</span>
                    </button>
                    <button type="button" class="chat-tab" data-tab="requests">
                        Solicitudes ocultas
                        <span class="chat-tab-badge" id="requestsBadge" hidden>0</span>
                    </button>
                </div>
            </div>

            <div class="chat-sidebar-list" id="conversationsItems">
                <div class="chat-sidebar-empty">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando conversaciones...</p>
                </div>
            </div>
        </aside>

        <main class="chat-main" id="chatMain">
            <div class="chat-main-placeholder" id="chatMainPlaceholder">
                <i class="fas fa-comments"></i>
                <h2>Selecciona una conversación</h2>
                <p>Elige un chat para ver tus mensajes y continuar la conversación.</p>
            </div>
            <div class="chat-main-content" id="chatMainContent" style="display: none;">
                <div class="chat-main-header" id="chatMainHeader"></div>
                <div class="chat-main-messages" id="messagesList"></div>
                <div id="typingIndicatorContainer"></div>
                <div class="chat-main-input">
                    <form class="chat-input-form" id="messageForm">
                        <button type="button" id="emojiPickerBtn" class="emoji-btn" title="Emojis">
                            <i class="fas fa-smile"></i>
                        </button>
                        <input type="text" id="messageInput" placeholder="Escribe un mensaje..." autocomplete="off">

                        <button type="submit" id="sendMessageBtn" title="Enviar mensaje">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </main>

        <aside class="chat-profile" id="chatProfilePanel">
            <div class="profile-placeholder" id="profilePlaceholder">
                <i class="fas fa-user-circle"></i>
                <h3>Sin conversación seleccionada</h3>
                <p>Elige un chat para ver los detalles del usuario y acceder rápidamente a su perfil público.</p>
            </div>
            <div class="profile-content" id="profileContent" style="display: none;">
                <a href="#" target="_blank" rel="noopener" id="profileLinkAvatar" class="profile-link-wrapper">
                    <div class="profile-avatar-wrapper">
                        <img id="profileAvatar" src="" alt="Avatar" class="profile-avatar" hidden onerror="this.style.display='none'; document.getElementById('profileInitials').style.display='flex'; document.getElementById('profileInitials').innerText=document.getElementById('profileInitials').innerText || 'U';">
                        <div class="profile-initials" id="profileInitials" hidden></div>
                    </div>
                </a>
                <a href="#" target="_blank" rel="noopener" id="profileLinkName" class="profile-link-wrapper">
                    <h2 id="profileName"></h2>
                </a>
                <p id="profileEmail"></p>
                
                <div class="profile-meta" id="profileMeta"></div>
            </div>
        </aside>
    </div>

</div>

<!-- Menú contextual para conversaciones -->
<div id="chatContextMenu" class="chat-context-menu" style="display: none;">
    <div class="context-menu-item" data-action="pin">
        <i class="fas fa-thumbtack"></i> <span>Fijar conversación</span>
    </div>
    <div class="context-menu-item" data-action="archive">
        <i class="fas fa-archive"></i> <span>Archivar conversación</span>
    </div>
    <div class="context-menu-divider"></div>
    <div class="context-menu-item context-menu-danger" data-action="delete">
        <i class="fas fa-trash-alt"></i> <span>Eliminar conversación</span>
    </div>
</div>

<!-- Emoji Picker -->
<div id="emojiPickerContainer" class="emoji-picker-container" style="display: none;"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const currentUserId = '<?php echo $user_id; ?>';
</script>
<script src="/js/simple-modal.js?v=<?php echo time(); ?>"></script>
<script src="/js/chat-websocket.js?v=<?php echo time(); ?>"></script>
<script src="/js/chat-modern.js?v=<?php echo time(); ?>"></script>
<script src="/js/chat-emoji-picker.js?v=<?php echo time(); ?>"></script>

<?php
include_once __DIR__ . '/../myphp/_footer.php';
?>

