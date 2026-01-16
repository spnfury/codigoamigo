let currentConversationId = null;
let lastMessageId = null;
let pollingInterval = null;
let conversations = [];

// Inicializar
$(document).ready(function() {
    loadConversations();
    startPolling();
    
    // Buscar conversaciones
    $('#searchConversations').on('input', function() {
        filterConversations($(this).val());
    });
    
    // Enviar mensaje
    $('#sendMessageBtn').on('click', sendMessage);
    $('#messageInput').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
});

// Cargar conversaciones
function loadConversations() {
    $.ajax({
        url: '/api/chat_api.php',
        method: 'GET',
        data: { action: 'get_conversaciones' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                conversations = response.conversaciones;
                renderConversations(conversations);
                $('#totalConversations').text(conversations.length);
            }
        },
        error: function() {
            $('#conversationsItems').html('<div class="text-center text-danger p-4">Error al cargar conversaciones</div>');
        }
    });
}

// Renderizar conversaciones
function renderConversations(convs) {
    if (convs.length === 0) {
        $('#conversationsItems').html('<div class="text-center text-muted p-4">No hay conversaciones</div>');
        return;
    }
    
    let html = '';
    convs.forEach(function(conv) {
        // conv.ultimo_mensaje_fecha ahora es un timestamp en milisegundos
        const fecha = conv.ultimo_mensaje_fecha ? new Date(conv.ultimo_mensaje_fecha) : new Date();
        const timeStr = formatTime(fecha);
        const preview = conv.ultimo_mensaje.length > 40 ? conv.ultimo_mensaje.substring(0, 40) + '...' : conv.ultimo_mensaje;
        const imgUrl = conv.usuario_img || 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';
        const activeClass = currentConversationId === conv.conversacion_id ? 'active' : '';
        const badgeHtml = conv.no_leidos > 0 ? `<span class="conversation-badge">${conv.no_leidos}</span>` : '';
        
        html += `
            <div class="conversation-item ${activeClass}" data-conversation-id="${conv.conversacion_id}" data-user-id="${conv.usuario_id}">
                <img src="${imgUrl}" alt="${conv.usuario_nombre}" class="conversation-avatar" onerror="this.src='https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg'">
                <div class="conversation-info">
                    <div class="conversation-name">${escapeHtml(conv.usuario_nombre)}</div>
                    <div class="conversation-preview">${escapeHtml(preview)}</div>
                </div>
                <div class="conversation-meta">
                    <div class="conversation-time">${timeStr}</div>
                    ${badgeHtml}
                </div>
            </div>
        `;
    });
    
    $('#conversationsItems').html(html);
    
    // Click en conversación
    $('.conversation-item').on('click', function() {
        const convId = $(this).data('conversation-id');
        const userId = $(this).data('user-id');
        selectConversation(convId, userId);
    });
}

// Filtrar conversaciones
function filterConversations(searchTerm) {
    if (!searchTerm) {
        renderConversations(conversations);
        return;
    }
    
    const filtered = conversations.filter(function(conv) {
        const search = searchTerm.toLowerCase();
        return conv.usuario_nombre.toLowerCase().includes(search) ||
               conv.usuario_email.toLowerCase().includes(search) ||
               conv.ultimo_mensaje.toLowerCase().includes(search);
    });
    
    renderConversations(filtered);
}

// Seleccionar conversación
function selectConversation(conversationId, userId) {
    currentConversationId = conversationId;
    lastMessageId = null;
    
    // Actualizar UI
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-conversation-id="${conversationId}"]`).addClass('active');
    
    // Obtener info del usuario
    const conv = conversations.find(c => c.conversacion_id === conversationId);
    if (conv) {
        showMessagesArea(conv);
        loadMessages(conversationId);
    }
}

// Mostrar área de mensajes
function showMessagesArea(conv) {
    const imgUrl = conv.usuario_img || 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';
    
    const html = `
        <div class="messages-header">
            <img src="${imgUrl}" alt="${conv.usuario_nombre}" class="messages-header-avatar" onerror="this.src='https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg'">
            <div class="messages-header-info">
                <h6>${escapeHtml(conv.usuario_nombre)}</h6>
                <small>${escapeHtml(conv.usuario_email)}</small>
            </div>
        </div>
        <div class="messages-list" id="messagesList">
            <div class="text-center text-muted p-4">
                <i class="fas fa-spinner fa-spin"></i> Cargando mensajes...
            </div>
        </div>
        <div class="messages-input">
            <form class="messages-input-form" id="messageForm">
                <input type="text" id="messageInput" placeholder="Escribe un mensaje..." autocomplete="off">
                <button type="submit" id="sendMessageBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    `;
    
    $('#messagesArea').html(html);
    
    // Scroll al final
    setTimeout(() => {
        scrollToBottom();
    }, 100);
}

// Cargar mensajes
function loadMessages(conversationId, lastId = null) {
    $.ajax({
        url: '/api/chat_api.php',
        method: 'GET',
        data: {
            action: lastId ? 'get_nuevos_mensajes' : 'get_mensajes',
            conversacion_id: conversationId,
            ultimo_id: lastId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.mensajes) {
                if (response.mensajes.length > 0) {
                    renderMessages(response.mensajes, !lastId);
                    if (!lastId) {
                        // Marcar como leídos
                        $.ajax({
                            url: '/api/chat_api.php',
                            method: 'POST',
                            data: {
                                action: 'marcar_leido',
                                conversacion_id: conversationId
                            }
                        });
                    }
                } else if (!lastId) {
                    $('#messagesList').html('<div class="text-center text-muted p-4">No hay mensajes. Comienza la conversación.</div>');
                }
            }
        },
        error: function() {
            $('#messagesList').html('<div class="text-center text-danger p-4">Error al cargar mensajes</div>');
        }
    });
}

// Renderizar mensajes
function renderMessages(mensajes, replace = false) {
    if (replace) {
        $('#messagesList').html('');
        lastMessageId = null;
    }
    
    let html = '';
    mensajes.forEach(function(msg) {
        // msg.fecha ahora es un timestamp en milisegundos
        const fecha = msg.fecha ? new Date(msg.fecha) : new Date();
        const timeStr = formatDateTime(fecha);
        const isAdmin = msg.es_admin;
        const messageClass = isAdmin ? 'admin' : 'user';
        
        html += `
            <div class="message-item ${messageClass}">
                <div>
                    <div class="message-bubble">${escapeHtml(msg.mensaje)}</div>
                    <div class="message-time">${timeStr}</div>
                </div>
            </div>
        `;
        
        if (!lastMessageId || msg._id > lastMessageId) {
            lastMessageId = msg._id;
        }
    });
    
    $('#messagesList').append(html);
    scrollToBottom();
}

// Enviar mensaje
function sendMessage() {
    const messageText = $('#messageInput').val().trim();
    if (!messageText || !currentConversationId) {
        return;
    }
    
    // Obtener user_id de la conversación activa
    const conv = conversations.find(c => c.conversacion_id === currentConversationId);
    if (!conv) {
        return;
    }
    
    $('#sendMessageBtn').prop('disabled', true);
    
    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'enviar_mensaje',
            para_usuario_id: conv.usuario_id,
            mensaje: messageText
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#messageInput').val('');
                // Recargar mensajes
                loadMessages(currentConversationId);
                // Recargar conversaciones para actualizar preview
                loadConversations();
            } else {
                alert('Error: ' + (response.error || 'No se pudo enviar el mensaje'));
            }
            $('#sendMessageBtn').prop('disabled', false);
        },
        error: function() {
            alert('Error al enviar mensaje');
            $('#sendMessageBtn').prop('disabled', false);
        }
    });
}

// Polling para nuevos mensajes
function startPolling() {
    pollingInterval = setInterval(function() {
        if (currentConversationId && lastMessageId) {
            // Obtener solo nuevos mensajes
            loadMessages(currentConversationId, lastMessageId);
        }
        // Actualizar lista de conversaciones
        loadConversations();
    }, 3000); // Cada 3 segundos
}

// Scroll al final
function scrollToBottom() {
    const messagesList = $('#messagesList');
    if (messagesList.length) {
        messagesList.scrollTop(messagesList[0].scrollHeight);
    }
}

// Utilidades
function formatTime(date) {
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    
    if (minutes < 1) return 'Ahora';
    if (minutes < 60) return `${minutes}m`;
    if (minutes < 1440) return `${Math.floor(minutes / 60)}h`;
    return `${Math.floor(minutes / 1440)}d`;
}

function formatDateTime(date) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const msgDate = new Date(date);
    const msgToday = new Date(msgDate.getFullYear(), msgDate.getMonth(), msgDate.getDate());
    
    if (msgToday.getTime() === today.getTime()) {
        return msgDate.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    } else {
        return msgDate.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: '2-digit' }) + ' ' +
               msgDate.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Limpiar interval al salir
window.addEventListener('beforeunload', function() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

