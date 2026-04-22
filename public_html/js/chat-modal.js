// Chat Modal - Sistema de chat rápido con estilo Custom Overlay (estilo Login)
let chatModalInitialized = false;
let currentChatUserId = null;
let currentChatUserName = null;
let currentChatConversationId = null;
let chatPollingInterval = null;
let lastChatMessageId = null;

// Variables de contexto del código
let currentChatCodigoContexto = null; // {codigoId, marcaSlug, beneficio, marcaNombre}

// Inicializar modal de chat
function initChatModal(userId, userName, userImg, defaultMessage = null, codigoContexto = null) {
    try {
        console.log('[Chat] initChatModal start', { userId, userName, codigoContexto });
    } catch (logError) { }
    currentChatUserId = userId;
    currentChatUserName = userName;
    currentChatConversationId = null;
    lastChatMessageId = null;
    currentChatCodigoContexto = codigoContexto;

    // Crear modal si no existe
    if (!document.getElementById('modal_chat_overlay')) {
        createChatModal();
    }

    // Configurar UI
    const modal = document.getElementById('modal_chat_overlay');
    document.getElementById('chatModalUserName').textContent = userName;

    const avatarImg = document.getElementById('chatModalUserAvatar');
    const placeholder = document.getElementById('chatModalUserAvatarPlaceholder');

    if (userImg && userImg.trim() !== '') {
        avatarImg.src = userImg;
        avatarImg.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';

        avatarImg.onerror = function () {
            this.style.display = 'none';
            if (placeholder) {
                placeholder.style.display = 'flex';
                placeholder.textContent = userName ? userName.substring(0, 2).toUpperCase() : 'U';
            }
        };
    } else {
        avatarImg.style.display = 'none';
        if (placeholder) {
            placeholder.style.display = 'flex';
            placeholder.textContent = userName ? userName.substring(0, 2).toUpperCase() : 'U';
        }
    }

    // Configurar banner de contexto
    const banner = document.getElementById('chatCodigoContextoBanner');
    const bannerText = document.getElementById('chatCodigoContextoText');
    if (banner && bannerText) {
        if (codigoContexto && codigoContexto.codigoId) {
            banner.style.background = 'linear-gradient(135deg, #00c853, #b2ff59)'; // Verde éxito
            banner.style.color = '#000';
            banner.style.fontWeight = '700';
            bannerText.innerHTML = `<i class="fas fa-gift"></i> ¡Asegura tus <strong>${codigoContexto.beneficio || 0}€</strong> de ${codigoContexto.marcaNombre}! Chatea ahora.`;
            banner.style.display = 'block';

            // Efecto de brillo suave en el banner
            banner.style.boxShadow = 'inset 0 0 15px rgba(255,255,255,0.5)';
        } else {
            banner.style.display = 'none';
        }
    }

    // Cargar conversación
    loadOrCreateConversation(userId);

    // Mostrar modal con animación
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevenir scroll del body
    setTimeout(() => {
        modal.classList.add('active');

        // Pre-llenar mensaje si se proporciona
        if (defaultMessage) {
            const input = document.getElementById('chatModalInput');
            if (input) {
                input.value = defaultMessage;
                input.focus();
            }
        }
    }, 10);

    // Iniciar polling
    startChatPolling();

    // Event handling
    const closeBtn = modal.querySelector('.login-modal-close');
    const closeHandler = function () {
        closeChatModal();
    };

    // Limpiar listeners anteriores para evitar duplicados
    const newCloseBtn = closeBtn.cloneNode(true);
    closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
    newCloseBtn.addEventListener('click', closeHandler);

    // Cerrar al hacer click fuera
    modal.onclick = function (e) {
        if (e.target === modal) {
            closeChatModal();
        }
    };
}

function closeChatModal() {
    const modal = document.getElementById('modal_chat_overlay');
    if (!modal) return;

    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        stopChatPolling();
        currentChatUserId = null;
        currentChatUserName = null;
        currentChatConversationId = null;
        currentChatCodigoContexto = null;
    }, 300);
}

// Crear estructura del modal (Estilo Login)
function createChatModal() {
    const modalHTML = `
        <div class="login-modal-overlay" id="modal_chat_overlay" style="z-index: 99999;">
            <div class="login-modal-content" style="max-width: 600px; height: 80vh; display: flex; flex-direction: column;">
                <div class="login-modal-header">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="position: relative;">
                            <img id="chatModalUserAvatar" src="" alt="" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; display: none; border: 2px solid rgba(255,255,255,0.5);">
                            <div id="chatModalUserAvatarPlaceholder" style="width: 40px; height: 40px; border-radius: 50%; background: white; color: #E30613; display: none; align-items: center; justify-content: center; font-weight: bold; border: 2px solid rgba(255,255,255,0.5);">
                            </div>
                        </div>
                        <div>
                            <div class="chat-modal-title" style="margin: 0; font-size: 1.1rem; font-weight: bold;" id="chatModalUserName">Usuario</div>
                            <small style="color: rgba(255,255,255,0.9); font-weight: normal; font-size: 0.8rem;">En línea</small>
                        </div>
                    </div>
                    <button class="login-modal-close">&times;</button>
                </div>
                
                <!-- Banner de contexto del código -->
                <div id="chatCodigoContextoBanner" style="display: none; background: linear-gradient(135deg, #ff7a18, #ff4f0f); padding: 10px 15px; color: white; font-size: 0.85rem; cursor: pointer;" onclick="window.open('/de-' + (currentChatCodigoContexto?.marcaSlug || ''), '_blank')">
                    <i class="fas fa-tag"></i> 
                    <span id="chatCodigoContextoText">Código vinculado</span>
                    <i class="fas fa-external-link-alt" style="float: right; opacity: 0.8; margin-top: 2px;"></i>
                </div>
                
                <div class="modal-body" style="flex: 1; overflow-y: auto; padding: 20px; background: #f8f9fa;" id="chatModalMessages">
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-spinner fa-spin"></i> Cargando mensajes...
                    </div>
                </div>
                
                <div class="modal-footer" style="padding: 15px; border-top: 1px solid #dee2e6; background: white;">
                    <form style="width: 100%; display: flex; gap: 10px; margin: 0;" id="chatModalForm" onsubmit="sendChatMessage(event)">
                        <input type="text" class="form-control" id="chatModalInput" placeholder="Escribe un mensaje..." autocomplete="off" style="border-radius: 25px; padding: 10px 20px; border: 2px solid #e9ecef;">
                        <button type="submit" class="btn btn-primary" id="chatModalSendBtn" style="border-radius: 50%; width: 45px; height: 45px; padding: 0; display: flex; align-items: center; justify-content: center; background: #E30613; border: none; flex-shrink: 0;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Añadir estilos específicos si es necesario (reutilizando clases del login)
    if (!document.getElementById('chatOverlayStyles')) {
        const styles = document.createElement('style');
        styles.id = 'chatOverlayStyles';
        styles.textContent = `
            #chatModalMessages {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            
            .chat-message-item {
                display: flex;
                width: 100%;
                animation: fadeIn 0.3s;
            }
            
            .chat-message-item.sent {
                justify-content: flex-end;
            }
            
            .chat-message-item.received {
                justify-content: flex-start;
            }
            
            .chat-message-item > div {
                display: flex;
                flex-direction: column;
                max-width: 75%;
            }
            
            .chat-message-item.sent > div {
                align-items: flex-end;
            }
            
            .chat-message-item.received > div {
                align-items: flex-start;
            }
            
            .chat-message-bubble {
                padding: 12px 16px;
                border-radius: 18px;
                word-wrap: break-word;
                word-break: break-word;
                white-space: pre-wrap;
                line-height: 1.5;
                display: inline-block;
                font-size: 0.95rem;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            
            .chat-message-item.sent .chat-message-bubble {
                background: linear-gradient(135deg, #E30613, #FF4D4D);
                color: white;
                border-bottom-right-radius: 4px;
            }
            
            .chat-message-item.received .chat-message-bubble {
                background: white;
                color: #212529;
                border: 1px solid #e9ecef;
                border-bottom-left-radius: 4px;
            }
            
            .chat-message-time {
                font-size: 0.7rem;
                color: #999;
                margin-top: 4px;
                padding: 0 8px;
            }
            
            @media (max-width: 768px) {
                #modal_chat_overlay .login-modal-content {
                    height: 100%;
                    max-width: 100%;
                    border-radius: 0;
                }
                #modal_chat_overlay {
                    padding: 0;
                }
            }
        `;
        document.head.appendChild(styles);
    }
}

function getChatFallbackUrl(userId) {
    return '/chat' + (userId ? ('?usuario=' + encodeURIComponent(userId)) : '');
}

function isChatUserLoggedIn() {
    return typeof window.codigoAmigoChatLoggedIn !== 'undefined' ? !!window.codigoAmigoChatLoggedIn : false;
}

function openChatModal(userId, userName, userImg, defaultMessage = null, codigoContexto = null) {
    // Si no hay usuario logueado, usar el sistema de login existente
    if (!isChatUserLoggedIn()) {
        if (typeof showLoginModal === 'function') {
            showLoginModal();
        } else {
            window.location.href = '/?login=1';
        }
        return;
    }

    if (!userId) {
        window.location.href = getChatFallbackUrl('');
        return;
    }

    // Inicializar y mostrar con contexto del código
    initChatModal(userId, userName, userImg, defaultMessage, codigoContexto);
}

window.openChatModal = openChatModal;

// Cargar o crear conversación
function loadOrCreateConversation(userId) {
    const currentUserId = typeof currentUserIdVar !== 'undefined' ? currentUserIdVar : '';
    const messagesContainer = document.getElementById('chatModalMessages');

    if (!currentUserId) {
        messagesContainer.innerHTML = '<div class="text-center text-danger p-4">Error: No se pudo identificar al usuario</div>';
        return;
    }

    messagesContainer.innerHTML = '<div class="text-center text-muted p-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

    // Buscar conversación existente (temp ID logic, incluir codigo_id si hay contexto)
    const codigoIdForConv = currentChatCodigoContexto && currentChatCodigoContexto.codigoId ? currentChatCodigoContexto.codigoId : null;
    const conversacionIdTemp = crearConversacionIdTemp(currentUserId, userId, codigoIdForConv);
    currentChatConversationId = conversacionIdTemp; // Establecer ID temporalmente

    // Intentar cargar mensajes existentes
    $.ajax({
        url: '/api/chat_api.php',
        method: 'GET',
        data: {
            action: 'get_mensajes',
            conversacion_id: conversacionIdTemp
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                if (response.mensajes && response.mensajes.length > 0) {
                    renderChatMessages(response.mensajes);
                    lastChatMessageId = response.mensajes[response.mensajes.length - 1]._id;
                } else {
                    messagesContainer.innerHTML = '<div class="text-center text-muted p-4" style="margin-top:20px;">' +
                        '<div style="background: #e9ecef; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #adb5bd; font-size: 24px;"><i class="fas fa-comments"></i></div>' +
                        '<strong>Nueva conversación</strong><br>' +
                        '<small>Escribe tu primer mensaje para ' + escapeHtml(currentChatUserName) + '</small></div>';
                }
            } else {
                messagesContainer.innerHTML = '<div class="text-center text-muted p-4" style="margin-top:20px;">' +
                    '<div style="background: #e9ecef; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #adb5bd; font-size: 24px;"><i class="fas fa-comments"></i></div>' +
                    '<strong>Nueva conversación</strong><br>' +
                    '<small>Escribe tu primer mensaje para ' + escapeHtml(currentChatUserName) + '</small></div>';
            }
        },
        error: function () {
            messagesContainer.innerHTML = '<div class="text-center text-danger p-4">Error al cargar mensajes</div>';
        }
    });
}

// Renderizar mensajes en el modal
function renderChatMessages(mensajes) {
    const messagesContainer = document.getElementById('chatModalMessages');
    const currentUserId = typeof currentUserIdVar !== 'undefined' ? currentUserIdVar : '';

    if (!mensajes || mensajes.length === 0) {
        return;
    }

    let html = '';
    mensajes.forEach(function (msg) {
        const fecha = msg.fecha ? new Date(msg.fecha) : new Date();
        const timeStr = formatChatDateTime(fecha);
        const isSent = msg.de_usuario_id === currentUserId;
        const messageClass = isSent ? 'sent' : 'received';

        html += `
            <div class="chat-message-item ${messageClass}" data-message-id="${msg._id || ''}">
                <div>
                    <div class="chat-message-bubble">${escapeHtml(msg.mensaje)}</div>
                    <div class="chat-message-time">${timeStr}</div>
                </div>
            </div>
        `;
    });

    messagesContainer.innerHTML = html;
    setTimeout(() => scrollChatToBottom(), 10);
}

// Enviar mensaje desde el modal
function sendChatMessage(event) {
    event.preventDefault();

    const input = document.getElementById('chatModalInput');
    const messageText = input.value.trim();

    if (!messageText || !currentChatUserId) return;

    const sendBtn = document.getElementById('chatModalSendBtn');
    sendBtn.disabled = true;
    input.value = ''; // Limpiar inmediatamente para UX

    // UI Optimista
    const messagesContainer = document.getElementById('chatModalMessages');
    const noMessagesDiv = messagesContainer.querySelector('.text-center.text-muted');
    if (noMessagesDiv) noMessagesDiv.remove();

    const fecha = new Date();
    const timeStr = formatChatDateTime(fecha);
    const tempMessageId = 'temp-' + Date.now();

    const messageHTML = `
        <div class="chat-message-item sent" data-message-id="${tempMessageId}" style="opacity: 0.7;">
            <div>
                <div class="chat-message-bubble">${escapeHtml(messageText)}</div>
                <div class="chat-message-time">${timeStr} <i class="fas fa-clock"></i></div>
            </div>
        </div>
    `;
    messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
    scrollChatToBottom();

    const ajaxData = {
        action: 'enviar_mensaje',
        para_usuario_id: currentChatUserId,
        mensaje: messageText
    };

    // Añadir contexto del código si existe
    if (currentChatCodigoContexto && currentChatCodigoContexto.codigoId) {
        ajaxData.codigo_id = currentChatCodigoContexto.codigoId;
        ajaxData.marca_slug = currentChatCodigoContexto.marcaSlug;
        ajaxData.beneficio = currentChatCodigoContexto.beneficio;
    }

    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: ajaxData,
        dataType: 'json',
        success: function (response) {
            sendBtn.disabled = false;

            if (response.success) {
                // Actualizar conversation ID real
                if (response.conversacion_id) {
                    currentChatConversationId = response.conversacion_id;
                }

                // Actualizar mensaje temporal
                const tempMsg = messagesContainer.querySelector('[data-message-id="' + tempMessageId + '"]');
                if (tempMsg) {
                    tempMsg.style.opacity = '1';
                    tempMsg.setAttribute('data-message-id', response.mensaje_id);
                    const timeEl = tempMsg.querySelector('.chat-message-time');
                    if (timeEl) timeEl.innerHTML = timeStr; // Quitar reloj
                }

                if (response.mensaje_id) {
                    lastChatMessageId = response.mensaje_id;
                }
            } else {
                // Error: mostrar visualmente
                const tempMsg = messagesContainer.querySelector('[data-message-id="' + tempMessageId + '"]');
                if (tempMsg) {
                    tempMsg.querySelector('.chat-message-bubble').style.background = '#dc3545';
                    tempMsg.querySelector('.chat-message-bubble').title = 'Error al enviar';
                }
                alert('Error: ' + (response.error || 'No se pudo enviar'));
            }
        },
        error: function () {
            sendBtn.disabled = false;
            alert('Error de conexión al enviar mensaje');
        }
    });
}

// Polling simplificado
function startChatPolling() {
    stopChatPolling();

    chatPollingInterval = setInterval(function () {
        if (!currentChatConversationId || !lastChatMessageId) return;

        $.ajax({
            url: '/api/chat_api.php',
            method: 'GET',
            data: {
                action: 'get_nuevos_mensajes',
                conversacion_id: currentChatConversationId,
                ultimo_id: lastChatMessageId
            },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.mensajes && response.mensajes.length > 0) {
                    const messagesContainer = document.getElementById('chatModalMessages');
                    if (!messagesContainer) return;

                    const currentUserId = typeof currentUserIdVar !== 'undefined' ? currentUserIdVar : '';
                    let shouldScroll = (messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 50);

                    response.mensajes.forEach(function (msg) {
                        if (messagesContainer.querySelector('[data-message-id="' + msg._id + '"]')) return;

                        const fecha = msg.fecha ? new Date(msg.fecha) : new Date();
                        const timeStr = formatChatDateTime(fecha);
                        const isSent = msg.de_usuario_id === currentUserId;
                        const messageClass = isSent ? 'sent' : 'received';

                        const messageHTML = `
                            <div class="chat-message-item ${messageClass}" data-message-id="${msg._id}">
                                <div>
                                    <div class="chat-message-bubble">${escapeHtml(msg.mensaje)}</div>
                                    <div class="chat-message-time">${timeStr}</div>
                                </div>
                            </div>
                        `;
                        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
                        lastChatMessageId = msg._id;
                    });

                    if (shouldScroll) scrollChatToBottom();
                }
            }
        });
    }, 3000);
}

function stopChatPolling() {
    if (chatPollingInterval) {
        clearInterval(chatPollingInterval);
        chatPollingInterval = null;
    }
}

function scrollChatToBottom() {
    const messagesContainer = document.getElementById('chatModalMessages');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}

// Utilidades
function formatChatDateTime(date) {
    return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
}

function crearConversacionIdTemp(user1Id, user2Id, codigoId) {
    const ids = [user1Id, user2Id];
    ids.sort();
    let convId = ids[0] + '-' + ids[1];
    if (codigoId) {
        convId += '-' + codigoId;
    }
    return convId;
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Init vars global
let currentUserIdVar = '';
if (typeof currentUserId !== 'undefined') {
    currentUserIdVar = currentUserId;
}

