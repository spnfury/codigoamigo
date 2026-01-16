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

// Obtener datos del usuario
$data_usuario = get_object_user('_id', new MongoDB\BSON\ObjectId($user_id));

if (!$data_usuario) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$username = $data_usuario['username'] ?? 'Usuario';
$title = "Chat - $username";
$description = "Sistema de mensajería de CodigoAmigo";

// Incluir header moderno
include_once __DIR__ . '/../myphp/_header_modern.php';
$GLOBALS['header_modern_used'] = true;

// Renderizar header
get_header_modern($title, $description, '', '', '', true);
?>

<link href="/css/chat-modern-v2.css?v=<?php echo time(); ?>" rel="stylesheet">
<link href="/css/usuario-chat.css?v=<?php echo time(); ?>" rel="stylesheet">
<style>
    .chat-connection-status {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        z-index: 1000;
    }
</style>
<div class="chat-connection-status offline" id="connectionStatus"></div>

<div class="chat-usuario-container">
    <?php if ($es_admin_chat) { ?>
        <div class="chat-quick-actions">
            <a href="/admin_chat" class="btn btn-sm btn-warning">
                <i class="fas fa-toolbox me-1"></i>Panel admin
            </a>
        </div>
    <?php } ?>

    <div class="chat-layout">
        <aside class="chat-sidebar">
            <div class="chat-sidebar-top">
                <div class="sidebar-title">
                    <div>
                        <span class="sidebar-label">Bandeja de entrada</span>
                        <p class="sidebar-helper">Mensajes que recibes de la comunidad</p>
                    </div>
                    <button class="btn btn-sm btn-primary" id="newConversationBtn" title="Nueva conversación" style="display: block;">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="chat-tabs">
                    <button type="button" class="chat-tab active" data-tab="inbox">Mensajes</button>
                    <button type="button" class="chat-tab" data-tab="requests">
                        Solicitudes ocultas
                        <span class="chat-tab-badge" id="requestsBadge" hidden>0</span>
                    </button>
                </div>
                <div class="chat-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchConversations" placeholder="Buscar conversación o usuario...">
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
                <div class="profile-avatar-wrapper">
                    <img id="profileAvatar" src="" alt="Avatar" class="profile-avatar" hidden>
                    <div class="profile-initials" id="profileInitials" hidden></div>
                </div>
                <h2 id="profileName"></h2>
                <p id="profileEmail"></p>
                <div class="profile-actions">
                    <a href="#" target="_blank" rel="noopener" id="profileLink" class="btn btn-outline-light">
                        <i class="fas fa-external-link-alt me-1"></i>Ver perfil público
                    </a>
                </div>
                <div class="profile-meta" id="profileMeta"></div>
            </div>
        </aside>
    </div>

    <!-- Modal para buscar usuarios -->
    <div class="modal fade" id="newConversationModal" tabindex="-1" role="dialog" aria-labelledby="newConversationModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="newConversationModalLabel">Nueva conversación</h4>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-3" id="searchUsersInput" placeholder="Buscar usuario por nombre o email...">
                    <div id="usersSearchResults" style="max-height: 300px; overflow-y: auto;">
                        <p class="text-muted text-center">Escribe para buscar usuarios...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para búsqueda de mensajes -->
    <div class="modal fade" id="searchMessagesModal" tabindex="-1" role="dialog" aria-labelledby="searchMessagesModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="searchMessagesModalLabel">Buscar en conversación</h4>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-3" id="searchMessagesInput" placeholder="Buscar mensajes...">
                    <div id="searchMessagesResults" style="max-height: 400px; overflow-y: auto;">
                        <p class="text-muted text-center">Escribe para buscar mensajes...</p>
                    </div>
                </div>
            </div>
        </div>
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

