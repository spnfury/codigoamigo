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

    // Setup mobile back via hardware/browser back button
    if (isMobileChat()) {
        window.addEventListener('popstate', function() {
            if ($('.chat-layout').hasClass('chat-open')) {
                closeMobileChat();
            }
        });
    }

    // Verificar si hay parametro open_chat en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const targetUserId = urlParams.get('open_chat');
    const targetCodigoId = urlParams.get('codigo_id') || null;
    messageParam = urlParams.get('msg') || urlParams.get('mensaje');

    if (targetUserId) {
        console.log('[Chat] Abrir chat con usuario:', targetUserId, 'codigo:', targetCodigoId);

        // Esperamos a que carguen las conversaciones antes de abrir el chat
        setTimeout(function () {
            // Crear el ID de conversación directamente (con codigo_id si viene de un lead)
            const conversationId = crearConversacionId(userId, targetUserId, targetCodigoId);
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
        stopPollingFallback();
        // Cargar conversaciones frescas al reconectar
        loadConversations();
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

    chatWebSocket.on('conversation_updated', function (data) {
        // Refrescar lista de conversaciones sin recargar todo
        loadConversations();
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
    }, 30000); // Polling cada 30 segundos (fallback, WebSocket es el primario)
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

    $('#messageInput').on('keydown', function (e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
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

    // Context menu para conversaciones
    setupContextMenu();
}

/**
 * Carga las conversaciones
 */
function loadConversations(tab) {
    const requestTab = tab || currentTab || 'inbox';
    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'get_conversaciones_usuario',
            tab: requestTab
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                conversations = response.conversaciones || [];
                renderConversations();
                updateUnreadBadge();
            } else {
                console.error('[Chat] Error cargando conversaciones:', response.error);
                $('#conversationsItems').html('<div class="text-center text-danger p-4">Error: ' + (response.error || 'Error desconocido') + '</div>');
            }
        },
        error: function (xhr, status, error) {
            console.error('[Chat] Error AJAX cargando conversaciones:', error, xhr);
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
        const codigoInfo = conv.codigo_info || null;
        const isPinned = conv.is_pinned || false;

        const unreadBadge = conv.no_leidos > 0 ? `<span class="chat-unread-badge">${conv.no_leidos}</span>` : '';
        const lastMessagePreview = conv.ultimo_mensaje ? (conv.ultimo_mensaje.length > 50 ? conv.ultimo_mensaje.substring(0, 50) + '...' : conv.ultimo_mensaje) : '';
        const lastMessageDate = conv.ultimo_mensaje_fecha ? formatMessageDate(conv.ultimo_mensaje_fecha) : '';
        const marcaTag = codigoInfo ? `<span class="conversation-marca-tag" style="background: rgba(102,126,234,0.2); color: #667eea; padding: 1px 6px; border-radius: 8px; font-size: 10px; font-weight: 600; margin-left: 6px;">${escapeHtml(codigoInfo.marca)}</span>` : '';
        const pinBadge = isPinned ? '<i class="fas fa-thumbtack conversation-pin-badge" title="Fijada"></i>' : '';
        const pinnedClass = isPinned ? ' is-pinned' : '';

        html += `
            <div class="chat-conversation-item${pinnedClass}" data-conversation-id="${conv.conversacion_id}" data-user-id="${otroUsuarioId}" data-pinned="${isPinned}">
                <div class="conversation-avatar">
                    ${imgOtro ? `<img src="${imgOtro}" alt="${nombreOtro}">` : `<div class="avatar-placeholder">${getInitials(nombreOtro)}</div>`}
                </div>
                <div class="conversation-content">
                    <div class="conversation-header">
                        <span class="conversation-name">${escapeHtml(nombreOtro)}${pinBadge}${marcaTag}</span>
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
    $('.chat-conversation-item').on('click', function (e) {
        // No abrir si fue click derecho
        if (e.which === 3) return;
        const conversationId = $(this).data('conversation-id');
        const uid = $(this).data('user-id');
        openConversation(conversationId, uid);
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

    // Mobile: slide to chat view
    if (isMobileChat()) {
        $('.chat-layout').addClass('chat-open');
    }

    // Actualizar Header del chat
    const conv = conversations.find(c => c.conversacion_id === conversationId);
    if (conv) {
        const nombreChat = conv.nombre_otro || conv.usuario_nombre || 'Chat';
        $('#chatMainHeader').html(`
            <button class="mobile-back-btn" onclick="closeMobileChat()" style="display:none;"><i class="fas fa-arrow-left"></i></button>
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

    // Inyectar burbuja de contexto de código de promo si existe
    const conv = conversations.find(c => c.conversacion_id === currentConversationId);
    if (conv && conv.codigo_info) {
        renderContextBubble(container, conv.codigo_info);
    }

    if (!mensajes || mensajes.length === 0) {
        container.append(`
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-tertiary); text-align: center; height: 100%; padding-top: 40px;">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class="fas fa-paper-plane" style="font-size: 2rem; opacity: 0.5;"></i>
                </div>
                <h3 style="color: var(--text-secondary); margin: 0 0 0.5rem 0; font-size: 1.1rem;">Aún no hay mensajes</h3>
                <p style="font-size: 0.9rem; max-width: 250px; margin: 0;">Escribe un mensaje abajo para iniciar la conversación.</p>
            </div>
        `);
        return;
    }

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
            <div class="message-bubble">
                <div class="message-text">${escapeHtml(msg.mensaje)}</div>
                <div class="message-time">${date}</div>
            </div>
        </div>
    `;

    $('#messagesList').append(html);
}

/**
 * Renderiza la burbuja estática de contexto al inicio del chat
 */
function renderContextBubble(container, codigoInfo) {
    const marca = codigoInfo.marca || 'la marca';
    const marcaLower = typeof marca === 'string' ? marca.toLowerCase().replace(/\s+/g, '-') : 'desconocida';
    let beneficioStr = '';
    const bf = parseFloat(codigoInfo.beneficio) || 0;
    if (bf > 0) {
        beneficioStr = `ahorra ${bf}€`;
    }
    
    let shortId = '';
    if (codigoInfo.codigo_id && typeof codigoInfo.codigo_id === 'string') {
        shortId = codigoInfo.codigo_id.slice(-8);
    }
    const link = `https://www.codigoamigo.com/codigo/${marcaLower}${shortId ? '-' + shortId : ''}`;
    
    const html = `
        <div class="chat-context-bubble-wrapper">
            <div class="ccb-link-header">
                <a href="${link}" target="_blank">${link}</a> (ficha publicatoria)
            </div>
            <div class="chat-context-bubble">
                <div class="ccb-header">
                    <span class="ccb-brand-name">Codigoamigo</span>
                </div>
                <div class="ccb-title">Cupones descuento ${marca} ${beneficioStr ? `【 ${beneficioStr} 】` : ''}</div>
                <div class="ccb-desc">
                    ${beneficioStr ? `【 ${beneficioStr} 】` : ''} con el código amigo ${marca}. Códigos y cupones válidos ✅ - ¡Aprovecha el descuento!
                </div>
                <div class="ccb-preview">
                    <!-- Preview Image estancada por ahora o usar logo genérico -->
                    <div class="ccb-img-placeholder">
                        <span class="ccb-logo-text">CodigoAmigo<small>.com</small></span>
                    </div>
                </div>
                <a href="${link}" target="_blank" class="ccb-link-overlay"></a>
            </div>
            
            <div class="ccb-system-msg">
                Aquí comienza la conversación por el código de <strong>${marca}</strong>. Usa los mensajes para pedir información o si eres el propietario para ofrecer ayuda.
            </div>
        </div>
    `;
    container.append(html);
}

/**
 * Maneja un nuevo mensaje recibido por WebSocket
 */
function handleNewMessage(data) {
    // Si es la conversación actual, añadir el mensaje
    const conversationId = data.conversacion_id || crearConversacionId(data.de_usuario_id, data.para_usuario_id);
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
        // Extraer codigo_id del conversationId si existe (formato: userA-userB-codigoId)
        const convParts = currentConversationId.split('-');
        const codigoIdFromConv = convParts.length >= 3 ? convParts[convParts.length - 1] : '';
        
        $.ajax({
            url: '/api/chat_api.php',
            method: 'POST',
            data: {
                action: 'enviar_mensaje',
                para_usuario_id: otherUserId,
                mensaje: messageText,
                codigo_id: codigoIdFromConv
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
function crearConversacionId(user1, user2, codigoId) {
    const ids = [user1, user2].sort();
    let convId = ids[0] + '-' + ids[1];
    if (codigoId) {
        convId += '-' + codigoId;
    }
    return convId;
}

function getOtherUserIdFromConversation(conversationId) {
    const parts = conversationId.split('-');
    // Formato: userA(24chars)-userB(24chars) o userA(24chars)-userB(24chars)-codigoId(24chars)
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

/**
 * Mobile chat helpers
 */
function isMobileChat() {
    return window.innerWidth <= 768;
}

function closeMobileChat() {
    $('.chat-layout').removeClass('chat-open');
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
    loadConversations(tab);
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
                    ${usuario.img ? `<img src="${usuario.img}" alt="${escapeHtml(usuario.username)}" onerror="this.onerror=null; this.src='https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';">` : `<div class="avatar-placeholder">${getInitials(usuario.username)}</div>`}
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
    $('#profileEmail').text('').hide(); // Force empty and hide
    $('#profileLinkAvatar, #profileLinkName').attr('href', usuario.profile_url);

    // Save user data for other renders
    window.currentChatUser = usuario;

    if (usuario.img) {
        $('#profileInitials').text(getInitials(usuario.username)).hide();
        $('#profileAvatar').attr('src', "").attr('src', usuario.img).show();
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

    // Cargar interacciones con códigos del usuario
    loadCodeInteractions(usuario._id);
}

// Cargar contexto de la conversación con el otro usuario
function loadCodeInteractions(otroUserId) {
    // Si no existe el contenedor, crearlo después de profileMeta
    if ($('#profileInteracciones').length === 0) {
        $('#profileMeta').after(`
            <div id="profileInteracciones" class="profile-interacciones">
                <div class="interacciones-loading">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </div>
            </div>
        `);
    }

    // Mostrar cargando
    $('#profileInteracciones').html(`
        <div class="interacciones-loading">
            <i class="fas fa-spinner fa-spin"></i> Cargando contexto...
        </div>
    `);

    $.ajax({
        url: '/api/chat_api.php',
        method: 'GET',
        data: {
            action: 'get_interacciones_codigo',
            usuario_id: otroUserId,
            conversacion_id: currentConversationId || ''
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                renderCodeInteractions(response);
            } else {
                $('#profileInteracciones').html('');
            }
        },
        error: function () {
            $('#profileInteracciones').html('');
        }
    });

}

// Renderizar contexto de la conversación - REDISEÑO CONTEXTUAL
function renderCodeInteractions(data) {
    const container = $('#profileInteracciones');
    const otroUserId = data.usuario_id || getOtherUserIdFromConversation(currentConversationId);
    const user = window.currentChatUser || {};
    const otroNombre = user.username || 'Usuario';

    const contexto = data.contexto || 'directo'; // directo, yo_contacte, me_contactaron
    const codigoInfo = data.codigo_info || null;
    const soyOwner = data.soy_owner_codigo || false;
    const codigoCompletado = data.codigo_completado || false;

    container.show();

    // === CASO 1: Conversación directa sin código ===
    if (contexto === 'directo' || !codigoInfo) {
        container.html(`
            <div class="interacciones-section" style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--glass-border); border-radius: var(--radius-md); overflow: hidden;">
                <div style="padding: 1rem; background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--glass-border); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-comment-dots" style="color: var(--accent-color);"></i>
                    <span style="font-weight: 600; font-size: 0.9rem;">Contexto</span>
                </div>
                <div style="padding: 1.5rem; text-align: center;">
                    <i class="fas fa-comments" style="font-size: 1.8rem; color: var(--text-tertiary); margin-bottom: 0.75rem; opacity: 0.6;"></i>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0 0 0.25rem 0; font-weight: 500;">Conversación directa</p>
                    <p style="color: var(--text-tertiary); font-size: 0.8rem; margin: 0;">Sin código asociado a esta conversación.</p>
                </div>
            </div>
        `);
        return;
    }

    // === CASO 2: Hay código asociado ===
    const marca = escapeHtml(codigoInfo.marca);
    const beneficio = codigoInfo.beneficio || 0;
    // Generar slug de marca para enlace a la página de marca
    const marcaSlug = (codigoInfo.marca || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const marcaPageUrl = '/de-' + marcaSlug;

    // Determinar texto de contexto
    let textoContexto = '';
    let iconoContexto = '';
    let colorContexto = '';

    if (contexto === 'me_contactaron') {
        if (soyOwner) {
            textoContexto = `<strong>${escapeHtml(otroNombre)}</strong> te contactó por tu código de <a href="${marcaPageUrl}" target="_blank" style="color: var(--accent-color); text-decoration: none; font-weight: 700;">${marca}</a>`;
            iconoContexto = 'fas fa-arrow-left';
            colorContexto = 'var(--success-color)';
        } else {
            textoContexto = `<strong>${escapeHtml(otroNombre)}</strong> te contactó por su código de <a href="${marcaPageUrl}" target="_blank" style="color: var(--accent-color); text-decoration: none; font-weight: 700;">${marca}</a>`;
            iconoContexto = 'fas fa-arrow-left';
            colorContexto = 'var(--accent-color)';
        }
    } else if (contexto === 'yo_contacte') {
        if (soyOwner) {
            textoContexto = `Contactaste a <strong>${escapeHtml(otroNombre)}</strong> por tu código de <a href="${marcaPageUrl}" target="_blank" style="color: var(--accent-color); text-decoration: none; font-weight: 700;">${marca}</a>`;
            iconoContexto = 'fas fa-arrow-right';
            colorContexto = 'var(--accent-color)';
        } else {
            textoContexto = `Contactaste a <strong>${escapeHtml(otroNombre)}</strong> por su código de <a href="${marcaPageUrl}" target="_blank" style="color: var(--accent-color); text-decoration: none; font-weight: 700;">${marca}</a>`;
            iconoContexto = 'fas fa-arrow-right';
            colorContexto = 'var(--warning-color)';
        }
    }

    const strCodigo = codigoInfo.str_codigo ? escapeHtml(codigoInfo.str_codigo) : '';
    const codigoUrl = codigoInfo.url ? escapeHtml(codigoInfo.url) : '';
    const linkHref = codigoUrl ? '/codigo/' + codigoUrl : marcaPageUrl;

    let html = `
        <div class="interacciones-section" style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--glass-border); border-radius: var(--radius-md); overflow: hidden;">
            <div style="padding: 1rem; background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--glass-border); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-link" style="color: var(--accent-color);"></i>
                <span style="font-weight: 600; font-size: 0.9rem;">Contexto</span>
            </div>

            <!-- Texto de contexto -->
            <div style="padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                    <i class="${iconoContexto}" style="color: ${colorContexto}; margin-top: 0.15rem; font-size: 0.9rem;"></i>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin: 0; line-height: 1.5;">${textoContexto}</p>
                </div>
            </div>

            <!-- Tarjeta del código (Clickable) -->
            <div style="padding: 1rem;">
                <a href="${linkHref}" target="_blank" style="text-decoration: none; color: inherit; display: block;" class="contexto-card-link">
                    <div class="interaccion-item" data-codigo-id="${codigoInfo.codigo_id}" data-beneficio="${beneficio}" style="display: flex; flex-direction: column; padding: 1rem; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: 10px; transition: all 0.2s;">
                        
                        <!-- Cabecera de la Marca -->
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: ${strCodigo ? '0.85rem' : '0'};">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 38px; height: 38px; border-radius: 8px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.05)); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-tag" style="font-size: 0.9rem; color: var(--warning-color);"></i>
                                </div>
                                <div>
                                    <a href="${marcaPageUrl}" target="_blank" onclick="event.stopPropagation();" style="font-weight: 600; font-size: 1rem; color: white; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--accent-color)'" onmouseout="this.style.color='white'">${marca}</a>
                                    <div style="font-size: 0.8rem; color: var(--text-tertiary);">Beneficio: <span style="color: var(--warning-color); font-weight: 600;">${beneficio}€</span></div>
                                </div>
                            </div>
    `;

    // Toggle solo si SOY el dueño del código (alguien me contactó por mi código)
    if (soyOwner) {
        html += `
                            <div onclick="event.preventDefault(); event.stopPropagation();" style="display: flex; align-items: center;">
                                <label class="apple-switch" title="${codigoCompletado ? 'Desmarcar' : '¿Ya usó tu código?'}" style="margin: 0;">
                                    <input type="checkbox" class="interaccion-toggle" ${codigoCompletado ? 'checked' : ''}>
                                    <span class="apple-slider"></span>
                                </label>
                            </div>
        `;
    } else {
        html += `
                            <i class="fas fa-external-link-alt" style="color: var(--text-tertiary); font-size: 0.85rem;"></i>
        `;
    }

    html += `
                        </div>
                        
                        <!-- Código Destacado -->
                        ${strCodigo ? `
                        <div style="background: rgba(0,0,0,0.3); border: 1px dashed rgba(255, 255, 255, 0.2); border-radius: 8px; padding: 0.85rem; text-align: center; margin-top: 0.25rem;">
                            <span style="font-family: monospace; font-size: 1.25rem; font-weight: 700; color: var(--accent-color); letter-spacing: 2px;">${strCodigo}</span>
                        </div>
                        ` : ''}

                    </div>
                </a>
    `;

    // Mensaje de ayuda contextual
    if (soyOwner) {
        if (codigoCompletado) {
            html += `
                <div style="margin-top: 0.75rem; padding: 0.65rem 0.85rem; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 8px; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-check-circle" style="color: var(--success-color); font-size: 0.85rem;"></i>
                    <span style="color: var(--success-color); font-size: 0.8rem; font-weight: 500;">Marcado como completado · +${beneficio}€</span>
                </div>
            `;
        } else {
            html += `
                <div style="margin-top: 0.75rem; padding: 0.65rem 0.85rem; background: rgba(245, 158, 11, 0.06); border: 1px solid rgba(245, 158, 11, 0.15); border-radius: 8px;">
                    <p style="color: var(--text-tertiary); font-size: 0.8rem; margin: 0; line-height: 1.4;">
                        <i class="fas fa-lightbulb" style="color: var(--warning-color); margin-right: 0.25rem;"></i>
                        Si ya ha usado tu código, activa el toggle para registrar la ganancia.
                    </p>
                </div>
            `;
        }
    } else if (contexto === 'yo_contacte') {
        html += `
            <div style="margin-top: 0.75rem; padding: 0.65rem 0.85rem; background: rgba(99, 102, 241, 0.06); border: 1px solid rgba(99, 102, 241, 0.15); border-radius: 8px;">
                <p style="color: var(--text-tertiary); font-size: 0.8rem; margin: 0; line-height: 1.4;">
                    <i class="fas fa-info-circle" style="color: var(--accent-color); margin-right: 0.25rem;"></i>
                    Si usas su código, ambos ganaréis <strong style="color: var(--warning-color);">${beneficio}€</strong>.
                </p>
            </div>
        `;
    }

    html += `
            </div>
        </div>
    `;

    container.html(html);

    // Eventos para el toggle (solo si soy owner)
    if (soyOwner) {
        container.find('.interaccion-toggle').on('change', function (e) {
            const checkbox = $(this);
            const item = checkbox.closest('.interaccion-item');
            const codigoId = item.data('codigo-id');
            const beneficioVal = parseFloat(item.data('beneficio') || 0);
            const isChecked = checkbox.is(':checked');

            // Actualizar localStorage
            const completedKey = `completed_codes_${userId}_${otroUserId}`;
            let completedCodes = {};
            try { completedCodes = JSON.parse(localStorage.getItem(completedKey) || '{}'); } catch (e) { completedCodes = {}; }

            if (isChecked) {
                completedCodes[codigoId] = true;
            } else {
                delete completedCodes[codigoId];
            }
            localStorage.setItem(completedKey, JSON.stringify(completedCodes));

            // Recargar el panel para reflejar el nuevo estado visual
            renderCodeInteractions(Object.assign({}, data, { codigo_completado: isChecked }));

            // Persistencia en Servidor (API)
            $.ajax({
                url: '/api/chat_api.php',
                method: 'POST',
                data: {
                    action: 'toggle_codigo_completado',
                    codigo_id: codigoId,
                    usuario_referido_id: otroUserId,
                    completado: isChecked,
                    beneficio: beneficioVal
                },
                success: function (response) {
                    if (!response.success) {
                        console.error('Error persistiendo estado en servidor:', response.error);
                    }
                }
            });
        });
    }
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
    showToast('Modo offline: usando actualización periódica', 'fas fa-wifi');
}

/**
 * Context Menu para gestión de conversaciones
 */
function setupContextMenu() {
    const menu = $('#chatContextMenu');
    let contextConvId = null;
    let contextIsPinned = false;
    let contextIsArchived = false;

    // Click derecho en conversación
    $(document).on('contextmenu', '.chat-conversation-item', function (e) {
        e.preventDefault();
        const $item = $(this);
        contextConvId = $item.data('conversation-id');
        contextIsPinned = $item.data('pinned') === true || $item.data('pinned') === 'true';
        contextIsArchived = currentTab === 'archived';

        // Actualizar texto según estado
        const pinItem = menu.find('[data-action="pin"]');
        if (contextIsPinned) {
            pinItem.find('span').text('Desfijar conversación');
            pinItem.find('i').removeClass('fa-thumbtack').addClass('fa-thumbtack').css('transform', 'rotate(45deg)');
        } else {
            pinItem.find('span').text('Fijar conversación');
            pinItem.find('i').css('transform', 'none');
        }

        const archiveItem = menu.find('[data-action="archive"]');
        if (contextIsArchived) {
            archiveItem.find('span').text('Mover a bandeja de entrada');
            archiveItem.find('i').removeClass('fa-archive').addClass('fa-inbox');
        } else {
            archiveItem.find('span').text('Archivar conversación');
            archiveItem.find('i').removeClass('fa-inbox').addClass('fa-archive');
        }

        // Posicionar menú
        let top = e.clientY;
        let left = e.clientX;
        const menuH = 140;
        const menuW = 220;
        if (top + menuH > window.innerHeight) top = window.innerHeight - menuH - 10;
        if (left + menuW > window.innerWidth) left = window.innerWidth - menuW - 10;

        menu.css({ top: top + 'px', left: left + 'px' }).show();
    });

    // Long press para móvil
    let longPressTimer = null;
    $(document).on('touchstart', '.chat-conversation-item', function (e) {
        const $item = $(this);
        longPressTimer = setTimeout(function () {
            const touch = e.originalEvent.touches[0];
            $item.trigger({
                type: 'contextmenu',
                clientX: touch.clientX,
                clientY: touch.clientY,
                preventDefault: function () { }
            });
        }, 600);
    });
    $(document).on('touchend touchmove', '.chat-conversation-item', function () {
        clearTimeout(longPressTimer);
    });

    // Cerrar menú al hacer click fuera
    $(document).on('click', function () {
        menu.hide();
    });

    // Acciones del menú
    menu.on('click', '.context-menu-item', function (e) {
        e.stopPropagation();
        const action = $(this).data('action');
        menu.hide();

        if (!contextConvId) return;

        switch (action) {
            case 'pin':
                togglePinConversation(contextConvId, contextIsPinned);
                break;
            case 'archive':
                toggleArchiveConversation(contextConvId, contextIsArchived);
                break;
            case 'delete':
                deleteConversation(contextConvId);
                break;
        }
    });
}

function togglePinConversation(convId, isPinned) {
    const action = isPinned ? 'unpin_conversation' : 'pin_conversation';
    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: { action: action, conversacion_id: convId },
        dataType: 'json',
        success: function (res) {
            if (res.success) {
                showToast(isPinned ? 'Conversación desfijada' : 'Conversación fijada', 'fas fa-thumbtack');
                loadConversations();
            } else {
                showToast(res.error || 'Error al fijar', 'fas fa-exclamation-circle');
            }
        }
    });
}

function toggleArchiveConversation(convId, isArchived) {
    const action = isArchived ? 'unarchive_conversation' : 'archive_conversation';
    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: { action: action, conversacion_id: convId },
        dataType: 'json',
        success: function (res) {
            if (res.success) {
                showToast(isArchived ? 'Movida a bandeja de entrada' : 'Conversación archivada', 'fas fa-archive');
                // Si estamos en la conversación archivada/desarchivada, limpiar vista
                if (currentConversationId === convId) {
                    currentConversationId = null;
                    lastMessageId = null;
                    $('#chatMainPlaceholder').show();
                    $('#chatMainContent').hide();
                    $('#profilePlaceholder').show();
                    $('#profileContent').hide();
                }
                loadConversations();
            } else {
                showToast(res.error || 'Error', 'fas fa-exclamation-circle');
            }
        }
    });
}

function deleteConversation(convId) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta conversación? Podrás seguir recibiendo mensajes de este usuario.')) {
        return;
    }
    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: { action: 'delete_conversation', conversacion_id: convId },
        dataType: 'json',
        success: function (res) {
            if (res.success) {
                showToast('Conversación eliminada', 'fas fa-trash-alt');
                if (currentConversationId === convId) {
                    currentConversationId = null;
                    lastMessageId = null;
                    $('#chatMainPlaceholder').show();
                    $('#chatMainContent').hide();
                    $('#profilePlaceholder').show();
                    $('#profileContent').hide();
                }
                loadConversations();
            } else {
                showToast(res.error || 'Error al eliminar', 'fas fa-exclamation-circle');
            }
        }
    });
}

/**
 * Muestra un toast notification
 */
function showToast(message, iconClass) {
    // Eliminar toasts anteriores
    $('.chat-toast').remove();
    const icon = iconClass || 'fas fa-check-circle';
    const toast = $(`<div class="chat-toast"><i class="${icon}"></i> ${escapeHtml(message)}</div>`);
    $('body').append(toast);
    setTimeout(function () {
        toast.addClass('toast-out');
        setTimeout(function () { toast.remove(); }, 300);
    }, 2500);
}

