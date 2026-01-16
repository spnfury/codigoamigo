/**
 * Sistema de Chat Moderno con WebSocket
 * Reemplaza el sistema de polling por WebSocket en tiempo real
 */

let chatWebSocket = null;
let currentConversationId = null;
let lastMessageId = null;
let conversations = [];
let cachedUserProfiles = {};
let currentTab = 'inbox';
let pollingFallback = null;
let usePollingFallback = false;
let userId = null; // Se inicializará desde currentUserId
let messageParam = null; // Mensaje predeterminado de la URL

// Inicializar cuando el DOM esté listo
$(document).ready(function () {
    console.log('[Chat] Inicializando sistema de chat...');

    // Usar currentUserId que viene del PHP
    userId = typeof currentUserId !== 'undefined' ? currentUserId : null;

    console.log('[Chat] userId:', userId);

    if (!userId) {
        console.error('[Chat] Error: currentUserId no está definido');
        $('#conversationsItems').html('<div class="text-center text-danger p-4">Error: No se pudo identificar al usuario. Por favor, recarga la página.</div>');
        return;
    }

    // Cargar conversaciones primero (funciona sin WebSocket)
    console.log('[Chat] Cargando conversaciones...');
    loadConversations();

    // Configurar eventos
    console.log('[Chat] Configurando event handlers...');
    setupEventHandlers();

    // Inicializar búsqueda
    console.log('[Chat] Inicializando búsqueda...');
    initSearch();

    // Intentar inicializar WebSocket (puede fallar si el servidor no está corriendo)
    console.log('[Chat] Intentando conectar WebSocket...');
    try {
        initWebSocket();
    } catch (error) {
        console.warn('[Chat] No se pudo inicializar WebSocket, usando modo polling:', error);
        usePollingFallback = true;
        startPollingFallback();
    }

    // Actualizar badge inicial
    setTimeout(updateUnreadBadge, 1000);

    // Verificar si hay parametro open_chat en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const targetUserId = urlParams.get('open_chat');
    messageParam = urlParams.get('msg') || urlParams.get('mensaje');

    if (targetUserId) {
        console.log('[Chat] Abrir chat con usuario:', targetUserId);

        // Esperamos a que carguen las conversaciones antes de abrir el chat
        setTimeout(function () {
            // Crear el ID de conversación directamente
            const conversationId = crearConversacionId(userId, targetUserId);
            console.log('[Chat] Intentando abrir conversación:', conversationId);

            // Intentar abrir la conversación
            openConversation(conversationId, targetUserId);

            // Limpiar el parámetro de la URL para evitar que se abra nuevamente si se recarga
            if (window.history && window.history.replaceState) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        }, 1000); // Aumentamos el delay para asegurar que las conversaciones se hayan cargado
    }

    console.log('[Chat] Inicialización completa');
});

/**
 * Inicializa la conexión WebSocket
 */
function initWebSocket() {
    if (!userId) {
        console.error('No se puede inicializar WebSocket: userId no definido');
        return;
    }

    // La URL del WebSocket se obtendrá del token
    chatWebSocket = new ChatWebSocket(userId, {
        maxReconnectAttempts: 10,
        reconnectDelay: 3000
    });

    // Handlers de eventos WebSocket
    chatWebSocket.on('connect', function () {
        console.log('[Chat] WebSocket conectado');
        updateConnectionStatus(true);
        usePollingFallback = false;
    });

    chatWebSocket.on('disconnect', function () {
        console.log('[Chat] WebSocket desconectado');
        updateConnectionStatus(false);
    });

    chatWebSocket.on('new_message', function (data) {
        handleNewMessage(data);
    });

    chatWebSocket.on('typing', function (data) {
        showTypingIndicator(data.de_usuario_id, data.para_usuario_id);
    });

    chatWebSocket.on('stop_typing', function (data) {
        hideTypingIndicator(data.de_usuario_id);
    });

    chatWebSocket.on('messages_read', function (data) {
        markMessagesAsReadUI(data.conversacion_id);
    });

    chatWebSocket.on('user_status', function (data) {
        updateUserStatus(data.usuario_id, data.status);
    });

    chatWebSocket.on('fallback_to_polling', function () {
        console.log('[Chat] Cambiando a modo polling');
        usePollingFallback = true;
        startPollingFallback();
        showFallbackNotification();
    });

    chatWebSocket.on('max_reconnect_attempts', function () {
        console.error('[Chat] Máximo de intentos alcanzado, usando polling');
        usePollingFallback = true;
        startPollingFallback();
        showFallbackNotification();
    });

    // Detectar si WebSocket no está disponible desde el inicio
    setTimeout(function () {
        if (!chatWebSocket.isConnected && !usePollingFallback) {
            console.log('[Chat] WebSocket no disponible, usando polling');
            usePollingFallback = true;
            startPollingFallback();
        }
    }, 5000);

    // Conectar
    chatWebSocket.connect();
}

/**
 * Inicia el fallback de polling
 */
function startPollingFallback() {
    if (pollingFallback) {
        return;
    }

    console.log('[Chat] Iniciando polling fallback');
    pollingFallback = setInterval(function () {
        if (currentConversationId) {
            loadNewMessages();
        }
        loadConversations();
    }, 3000); // Polling cada 3 segundos
}

/**
 * Detiene el fallback de polling
 */
function stopPollingFallback() {
    if (pollingFallback) {
        clearInterval(pollingFallback);
        pollingFallback = null;
    }
}

/**
 * Configura los event handlers
 */
function setupEventHandlers() {
    // Tabs
    $('.chat-tab').on('click', function () {
        const tab = $(this).data('tab');
        if (!tab || tab === currentTab) {
            return;
        }
        switchTab(tab);
    });

    // Búsqueda de conversaciones
    $('#searchConversations').on('input', function () {
        filterConversations($(this).val());
    });

    // Envío de mensajes
    $('#messageForm').on('submit', function (e) {
        e.preventDefault();
        sendMessage();
    });

    $('#sendMessageBtn').on('click', function (e) {
        e.preventDefault();
        sendMessage();
    });

    // Indicador de escritura
    let typingTimeout = null;
    $('#messageInput').on('input', function () {
        if (currentConversationId && chatWebSocket && !usePollingFallback) {
            const otherUserId = getOtherUserIdFromConversation(currentConversationId);
            if (otherUserId) {
                chatWebSocket.sendTyping(otherUserId);
            }
        }

        // Limpiar timeout anterior
        if (typingTimeout) {
            clearTimeout(typingTimeout);
        }

        // Auto-stop typing después de 3 segundos sin escribir
        typingTimeout = setTimeout(function () {
            if (currentConversationId && chatWebSocket && !usePollingFallback) {
                const otherUserId = getOtherUserIdFromConversation(currentConversationId);
                if (otherUserId) {
                    chatWebSocket.sendStopTyping(otherUserId);
                }
            }
        }, 3000);
    });

    // Nueva conversación
    $('#newConversationBtn').on('click', function () {
        $('#newConversationModal').simpleModal('show');
        $('#searchUsersInput').val('');
        $('#usersSearchResults').html('<p class="text-muted text-center">Escribe para buscar usuarios...</p>');
    });

    // Buscar usuarios
    let searchTimeout = null;
    $('#searchUsersInput').on('input', function () {
        const query = $(this).val().trim();

        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        if (query.length < 2) {
            $('#usersSearchResults').html('<p class="text-muted text-center">Escribe para buscar usuarios...</p>');
            return;
        }

        searchTimeout = setTimeout(function () {
            searchUsers(query);
        }, 300);
    });
}

/**
 * Carga las conversaciones
 */
function loadConversations() {
    console.log('[Chat] Cargando conversaciones...');
    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'get_conversaciones_usuario'
        },
        dataType: 'json',
        success: function (response) {
            console.log('[Chat] Respuesta de conversaciones:', response);
            if (response.success) {
                conversations = response.conversaciones || [];
                console.log('[Chat] Conversaciones cargadas:', conversations.length);
                renderConversations();
                updateUnreadBadge();
            } else {
                console.error('[Chat] Error cargando conversaciones:', response.error);
                $('#conversationsItems').html('<div class="text-center text-danger p-4">Error: ' + (response.error || 'Error desconocido') + '</div>');
            }
        },
        error: function (xhr, status, error) {
            console.error('[Chat] Error AJAX cargando conversaciones:', error, xhr);
            $('#conversationsItems').html('<div class="text-center text-danger p-4">Error de conexión. Por favor, recarga la página.</div>');
        }
    });
}

/**
 * Renderiza la lista de conversaciones
 */
function renderConversations(filteredConvs = null) {
    const container = $('#conversationsItems');
    const listToRender = filteredConvs || conversations;

    console.log('[Chat] Renderizando conversaciones:', listToRender.length);

    if (listToRender.length === 0) {
        container.html(`
            <div class="chat-sidebar-empty">
                <i class="fas fa-inbox"></i>
                <p>${filteredConvs ? 'No se encontraron resultados' : 'No tienes conversaciones'}</p>
                ${filteredConvs ? '' : '<p class="text-muted" style="font-size: 12px; margin-top: 10px;">Haz clic en el botón "+" para iniciar una nueva conversación</p>'}
            </div>
        `);
        return;
    }

    let html = '';
    listToRender.forEach(function (conv) {
        // Usar los nombres correctos de campos que devuelve la API
        const otroUsuarioId = conv.otro_usuario_id || conv.usuario_id || '';
        const nombreOtro = conv.nombre_otro || conv.usuario_nombre || 'Usuario';
        const imgOtro = conv.img_otro || conv.usuario_img || '';

        const unreadBadge = conv.no_leidos > 0 ? `<span class="chat-unread-badge">${conv.no_leidos}</span>` : '';
        const lastMessagePreview = conv.ultimo_mensaje ? (conv.ultimo_mensaje.length > 50 ? conv.ultimo_mensaje.substring(0, 50) + '...' : conv.ultimo_mensaje) : '';
        const lastMessageDate = conv.ultimo_mensaje_fecha ? formatMessageDate(conv.ultimo_mensaje_fecha) : '';

        html += `
            <div class="chat-conversation-item" data-conversation-id="${conv.conversacion_id}" data-user-id="${otroUsuarioId}">
                <div class="conversation-avatar">
                    ${imgOtro ? `<img src="${imgOtro}" alt="${nombreOtro}">` : `<div class="avatar-placeholder">${getInitials(nombreOtro)}</div>`}
                </div>
                <div class="conversation-content">
                    <div class="conversation-header">
                        <span class="conversation-name">${escapeHtml(nombreOtro)}</span>
                        <span class="conversation-date">${lastMessageDate}</span>
                    </div>
                    <div class="conversation-preview">
                        <span class="preview-text">${escapeHtml(lastMessagePreview)}</span>
                        ${unreadBadge}
                    </div>
                </div>
            </div>
        `;
    });

    container.html(html);

    // Event handlers para items de conversación
    $('.chat-conversation-item').on('click', function () {
        const conversationId = $(this).data('conversation-id');
        const userId = $(this).data('user-id');
        openConversation(conversationId, userId);
    });
}

/**
 * Abre una conversación
 */
function openConversation(conversationId, otherUserId) {
    currentConversationId = conversationId;

    // Actualizar UI
    $('.chat-conversation-item').removeClass('active');
    $(`.chat-conversation-item[data-conversation-id="${conversationId}"]`).addClass('active');

    // Mostrar panel de chat
    $('#chatMainPlaceholder').hide();
    $('#chatMainContent').show();

    // Actualizar Header del chat
    const conv = conversations.find(c => c.conversacion_id === conversationId);
    if (conv) {
        const nombreChat = conv.nombre_otro || conv.usuario_nombre || 'Chat';
        $('#chatMainHeader').html(`
            <div class="chat-header-info">
                <div class="chat-header-name">${escapeHtml(nombreChat)}</div>
                <div class="chat-header-status"><span id="connectionStatus" class="status-dot online"></span> En línea</div>
            </div>
            <div class="chat-header-actions">
                <button class="header-action-btn" id="searchMessagesBtn" title="Buscar en esta conversación">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        `);

        // Re-vincular evento de búsqueda
        $('#searchMessagesBtn').on('click', function () {
            $('#searchMessagesModal').simpleModal('show');
            $('#searchMessagesInput').val('').focus();
        });
    }

    // Cargar mensajes
    loadMessages(conversationId);

    // Cargar perfil del usuario
    loadUserProfile(otherUserId);

    // Pre-llenar mensaje si viene de la URL
    if (messageParam) {
        const input = $('#messageInput');
        if (input.length) {
            input.val(messageParam).focus();
            messageParam = null; // Limpiar para que no se repita
        }
    }

    // Marcar como leído
    if (chatWebSocket && !usePollingFallback) {
        chatWebSocket.markAsRead(conversationId);
    } else {
        markAsReadAPI(conversationId);
    }
}

/**
 * Carga los mensajes de una conversación
 */
function loadMessages(conversationId) {
    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'get_mensajes',
            conversacion_id: conversationId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                renderMessages(response.mensajes);
                if (response.mensajes.length > 0) {
                    lastMessageId = response.mensajes[response.mensajes.length - 1]._id;
                }
            }
        }
    });
}

/**
 * Carga nuevos mensajes (para polling fallback)
 */
function loadNewMessages() {
    if (!currentConversationId || !lastMessageId) {
        return;
    }

    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'get_nuevos_mensajes',
            conversacion_id: currentConversationId,
            ultimo_id: lastMessageId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.mensajes.length > 0) {
                appendMessages(response.mensajes);
                if (response.mensajes.length > 0) {
                    lastMessageId = response.mensajes[response.mensajes.length - 1]._id;
                }
            }
        }
    });
}

/**
 * Renderiza mensajes
 */
function renderMessages(mensajes) {
    const container = $('#messagesList');
    container.empty();

    mensajes.forEach(function (msg) {
        appendMessage(msg);
    });

    scrollToBottom();
}

/**
 * Añade mensajes al final
 */
function appendMessages(mensajes) {
    mensajes.forEach(function (msg) {
        appendMessage(msg);
    });
    scrollToBottom();
}

/**
 * Añade un mensaje
 */
function appendMessage(msg) {
    const isOwn = msg.de_usuario_id === userId;
    const messageClass = isOwn ? 'message-own' : 'message-other';
    const date = formatMessageDate(msg.fecha);

    const html = `
        <div class="chat-message ${messageClass}" data-message-id="${msg._id}">
            <div class="message-content">
                <div class="message-text">${escapeHtml(msg.mensaje)}</div>
                <div class="message-time">${date}</div>
            </div>
        </div>
    `;

    $('#messagesList').append(html);
}

/**
 * Maneja un nuevo mensaje recibido por WebSocket
 */
function handleNewMessage(data) {
    // Si es la conversación actual, añadir el mensaje
    const conversationId = crearConversacionId(data.de_usuario_id, data.para_usuario_id);
    if (conversationId === currentConversationId) {
        appendMessage({
            _id: 'temp_' + Date.now(),
            de_usuario_id: data.de_usuario_id,
            para_usuario_id: data.para_usuario_id,
            mensaje: data.mensaje,
            fecha: data.timestamp,
            leido: false
        });
    }

    // Actualizar lista de conversaciones
    loadConversations();

    // Actualizar badge de no leídos
    updateUnreadBadge();

    // Mostrar notificación si no es la conversación actual
    if (conversationId !== currentConversationId) {
        // Obtener nombre del usuario para la notificación
        const usuarioNombre = conversations.find(c => c.conversacion_id === conversationId)?.usuario_nombre || 'Usuario';
        showNotification({
            ...data,
            de_usuario_nombre: usuarioNombre,
            conversacion_id: conversationId
        });
    }
}

/**
 * Envía un mensaje
 */
function sendMessage() {
    const messageText = $('#messageInput').val().trim();
    if (!messageText || !currentConversationId) {
        return;
    }

    const otherUserId = getOtherUserIdFromConversation(currentConversationId);
    if (!otherUserId) {
        return;
    }

    // Enviar por WebSocket si está disponible
    if (chatWebSocket && !usePollingFallback && chatWebSocket.isConnected) {
        chatWebSocket.sendMessage(otherUserId, messageText);

        // Añadir optimísticamente a la UI
        appendMessage({
            _id: 'temp_' + Date.now(),
            de_usuario_id: userId,
            para_usuario_id: otherUserId,
            mensaje: messageText,
            fecha: Date.now(),
            leido: false
        });

        $('#messageInput').val('');
    } else {
        // Fallback a API REST
        $.ajax({
            url: '/api/chat_api.php',
            method: 'POST',
            data: {
                action: 'enviar_mensaje',
                para_usuario_id: otherUserId,
                mensaje: messageText
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#messageInput').val('');
                    loadMessages(currentConversationId);
                    loadConversations();
                }
            }
        });
    }
}

/**
 * Muestra indicador de "escribiendo..."
 */
function showTypingIndicator(deUsuarioId, paraUsuarioId) {
    if (paraUsuarioId !== userId) {
        return; // No es para nosotros
    }

    const conversationId = crearConversacionId(deUsuarioId, paraUsuarioId);
    if (conversationId !== currentConversationId) {
        return; // No es la conversación actual
    }

    // Mostrar indicador
    let indicator = $('#typingIndicator');
    if (indicator.length === 0) {
        indicator = $('<div id="typingIndicator" class="typing-indicator"><span></span><span></span><span></span></div>');
        $('#messagesList').append(indicator);
        scrollToBottom();
    }

    // Ocultar después de 5 segundos
    setTimeout(function () {
        hideTypingIndicator(deUsuarioId);
    }, 5000);
}

/**
 * Oculta indicador de "escribiendo..."
 */
function hideTypingIndicator(deUsuarioId) {
    $('#typingIndicator').remove();
}

/**
 * Actualiza el estado de conexión en la UI
 */
function updateConnectionStatus(connected) {
    const status = connected ? 'online' : 'offline';
    $('#connectionStatus').removeClass('online offline').addClass(status);
}

/**
 * Funciones auxiliares
 */
function crearConversacionId(user1, user2) {
    const ids = [user1, user2].sort();
    return ids[0] + '-' + ids[1];
}

function getOtherUserIdFromConversation(conversationId) {
    const parts = conversationId.split('-');
    return parts[0] === userId ? parts[1] : parts[0];
}

function formatMessageDate(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) {
        return 'Ahora';
    } else if (diff < 3600000) {
        return Math.floor(diff / 60000) + 'm';
    } else if (diff < 86400000) {
        return Math.floor(diff / 3600000) + 'h';
    } else {
        return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getInitials(name) {
    if (!name || typeof name !== 'string') return 'U';
    const parts = name.trim().split(/\s+/).filter(part => part.length > 0);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    if (parts.length === 1) {
        return parts[0].substring(0, 2).toUpperCase();
    }
    return 'U';
}

function scrollToBottom() {
    const container = $('#messagesList');
    container.scrollTop(container[0].scrollHeight);
}

function markAsReadAPI(conversationId) {
    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'marcar_leido',
            conversacion_id: conversationId
        }
    });
}

function markMessagesAsReadUI(conversationId) {
    if (conversationId === currentConversationId) {
        $('.chat-message').addClass('read');
    }
}

function updateUserStatus(userId, status) {
    // Actualizar indicador de estado en la UI
    $(`.chat-conversation-item[data-user-id="${userId}"] .user-status`).removeClass('online offline').addClass(status);
}

function switchTab(tab) {
    currentTab = tab;
    $('.chat-tab').removeClass('active');
    $(`.chat-tab[data-tab="${tab}"]`).addClass('active');
    loadConversations();
}

function filterConversations(query) {
    if (!query) {
        renderConversations();
        return;
    }

    const filtered = conversations.filter(conv => {
        const nombre = (conv.nombre_otro || conv.usuario_nombre || '').toLowerCase();
        const ultimoMsg = (conv.ultimo_mensaje || '').toLowerCase();
        return nombre.includes(query.toLowerCase()) || ultimoMsg.includes(query.toLowerCase());
    });

    renderConversations(filtered);
}

function searchUsers(query) {
    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'buscar_usuarios',
            busqueda: query
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                renderUserSearchResults(response.usuarios);
            }
        }
    });
}

function renderUserSearchResults(usuarios) {
    const container = $('#usersSearchResults');

    if (usuarios.length === 0) {
        container.html('<p class="text-muted text-center">No se encontraron usuarios</p>');
        return;
    }

    let html = '<div class="user-search-results">';
    usuarios.forEach(function (usuario) {
        html += `
            <div class="user-search-item" data-user-id="${usuario._id}">
                <div class="user-avatar">
                    ${usuario.img ? `<img src="${usuario.img}" alt="${usuario.username}">` : `<div class="avatar-placeholder">${getInitials(usuario.username)}</div>`}
                </div>
                <div class="user-info">
                    <div class="user-name">${escapeHtml(usuario.username)}</div>
                    <div class="user-email">${escapeHtml(usuario.mail)}</div>
                </div>
            </div>
        `;
    });
    html += '</div>';

    container.html(html);

    $('.user-search-item').on('click', function () {
        const userId = $(this).data('user-id');
        startConversation(userId);
        $('#newConversationModal').simpleModal('hide');
    });
}

function startConversation(otherUserId) {
    // Crear conversación con el usuario
    const conversationId = crearConversacionId(otherUserId, userId);
    openConversation(conversationId, otherUserId);
}

function loadUserProfile(userId) {
    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'get_usuario_chat',
            usuario_id: userId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                renderUserProfile(response.usuario);
            }
        }
    });
}

function renderUserProfile(usuario) {
    $('#profileName').text(usuario.username);
    $('#profileEmail').text(usuario.mail);
    $('#profileLink').attr('href', usuario.profile_url);

    if (usuario.img) {
        $('#profileAvatar').attr('src', usuario.img).show();
        $('#profileInitials').hide();
    } else {
        $('#profileAvatar').hide();
        $('#profileInitials').text(getInitials(usuario.username)).show();
    }

    // Mostrar información adicional del usuario
    let metaHtml = '';

    if (usuario.tiempo_miembro) {
        metaHtml += `
            <div class="profile-stat-item">
                <div class="profile-stat-label">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Miembro</span>
                </div>
                <div class="profile-stat-value">${usuario.tiempo_miembro}</div>
            </div>`;
    }

    if (usuario.fecha_registro) {
        metaHtml += `
            <div class="profile-stat-item">
                <div class="profile-stat-label">
                    <i class="fas fa-user-plus"></i>
                    <span>Registro</span>
                </div>
                <div class="profile-stat-value">${usuario.fecha_registro}</div>
            </div>`;
    }

    if (usuario.codigos_count !== undefined) {
        metaHtml += `
            <div class="profile-stat-item">
                <div class="profile-stat-label">
                    <i class="fas fa-tags"></i>
                    <span>Códigos</span>
                </div>
                <div class="profile-stat-value">${usuario.codigos_count}</div>
            </div>`;
    }

    if (usuario.es_admin) {
        metaHtml += `
            <div class="profile-stat-item" style="background: rgba(46, 204, 113, 0.1);">
                <div class="profile-stat-label">
                    <i class="fas fa-shield-alt" style="color: #2ecc71;"></i>
                    <span style="color: #2ecc71; font-weight: 600;">Rol</span>
                </div>
                <div class="profile-stat-badge">
                    <i class="fas fa-check-circle"></i> Admin
                </div>
            </div>`;
    }

    $('#profileMeta').html(metaHtml);

    $('#profilePlaceholder').hide();
    $('#profileContent').show();
}

// Solicitar permiso para notificaciones al cargar
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

function showNotification(data) {
    // Solo mostrar si la página no está visible
    if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
        const notification = new Notification('Nuevo mensaje de ' + (data.de_usuario_nombre || 'Usuario'), {
            body: data.mensaje.length > 100 ? data.mensaje.substring(0, 100) + '...' : data.mensaje,
            icon: '/favicon.ico',
            tag: data.conversacion_id,
            badge: '/favicon.ico'
        });

        notification.onclick = function () {
            window.focus();
            const conversationId = crearConversacionId(data.de_usuario_id, data.para_usuario_id);
            openConversation(conversationId, data.de_usuario_id);
            notification.close();
        };

        // Cerrar automáticamente después de 5 segundos
        setTimeout(function () {
            notification.close();
        }, 5000);
    }

    // Actualizar badge de no leídos
    updateUnreadBadge();
}

function updateUnreadBadge() {
    let totalUnread = 0;
    conversations.forEach(function (conv) {
        totalUnread += conv.no_leidos || 0;
    });

    // Actualizar badge en el header
    const badge = $('.chat-unread-count');
    if (totalUnread > 0) {
        badge.text(totalUnread).show();
    } else {
        badge.hide();
    }

    // Actualizar título de la página
    if (totalUnread > 0) {
        document.title = `(${totalUnread}) Mensajes - CodigoAmigo`;
    } else {
        document.title = 'Mensajes - CodigoAmigo';
    }
}

function initSearch() {
    // Botón de búsqueda (inicialmente desde el input, ahora desde el header dinámico)
    $(document).on('click', '#searchMessagesBtn', function () {
        if (!currentConversationId) {
            return;
        }
        $('#searchMessagesModal').simpleModal('show');
        $('#searchMessagesInput').val('').focus();
    });

    // Búsqueda de mensajes
    let searchTimeout = null;
    $('#searchMessagesInput').on('input', function () {
        const query = $(this).val().trim();

        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        if (query.length < 2) {
            $('#searchMessagesResults').html('<p class="text-muted text-center">Escribe para buscar mensajes...</p>');
            return;
        }

        searchTimeout = setTimeout(function () {
            searchMessagesInConversation(query);
        }, 300);
    });
}

function searchMessagesInConversation(query) {
    if (!currentConversationId) {
        return;
    }

    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'search_mensajes',
            conversacion_id: currentConversationId,
            query: query
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                renderSearchResults(response.resultados, query);
            }
        }
    });
}

function renderSearchResults(mensajes, query) {
    const container = $('#searchMessagesResults');

    if (mensajes.length === 0) {
        container.html('<p class="text-muted text-center">No se encontraron mensajes</p>');
        return;
    }

    let html = '<div class="search-results-list">';
    mensajes.forEach(function (msg) {
        const highlightedMessage = highlightText(msg.mensaje, query);
        const date = formatMessageDate(msg.fecha);
        const isOwn = msg.de_usuario_id === userId;

        html += `
            <div class="search-result-item ${isOwn ? 'own' : 'other'}" data-message-id="${msg._id}">
                <div class="result-message">${highlightedMessage}</div>
                <div class="result-date">${date}</div>
            </div>
        `;
    });
    html += '</div>';

    container.html(html);

    // Click en resultado para ir al mensaje
    $('.search-result-item').on('click', function () {
        const messageId = $(this).data('message-id');
        scrollToMessage(messageId);
        $('#searchMessagesModal').simpleModal('hide');
    });
}

function highlightText(text, query) {
    const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
    return escapeHtml(text).replace(regex, '<mark>$1</mark>');
}

function escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function scrollToMessage(messageId) {
    const messageElement = $(`.chat-message[data-message-id="${messageId}"]`);
    if (messageElement.length > 0) {
        const container = $('#messagesList');
        const scrollTop = messageElement.offset().top - container.offset().top + container.scrollTop() - 50;
        container.animate({ scrollTop: scrollTop }, 500);
        messageElement.addClass('highlight');
        setTimeout(function () {
            messageElement.removeClass('highlight');
        }, 2000);
    }
}

function showFallbackNotification() {
    // Mostrar notificación visual de que se está usando polling
    const notification = $('<div class="chat-fallback-notification">Modo offline: usando actualización periódica</div>');
    $('body').append(notification);
    setTimeout(function () {
        notification.fadeOut(function () {
            $(this).remove();
        });
    }, 5000);
}

