let currentConversationId = null;
let lastMessageId = null;
let pollingInterval = null;
let conversations = [];
const userId = typeof currentUserId !== 'undefined' ? currentUserId : '';
let targetUserHandled = false;
let targetUserIdParam = '';
let currentTab = 'inbox';
const cachedUserProfiles = {};

try {
    const params = new URLSearchParams(window.location.search);
    targetUserIdParam = params.get('usuario') || '';
} catch (paramError) {
    console.warn('No se pudo leer el parámetro de usuario objetivo para el chat:', paramError);
    targetUserIdParam = '';
}

// Inicializar
$(document).ready(function () {
    if (!userId) {
        console.error('Error: currentUserId no está definido');
        $('#conversationsItems').html('<div class="text-center text-danger p-4">Error: No se pudo identificar al usuario. Por favor, recarga la página.</div>');
        return;
    }

    loadConversations();
    startPolling();

    $('.chat-tab').on('click', function () {
        const tab = $(this).data('tab');
        if (!tab || tab === currentTab) {
            return;
        }
        switchTab(tab);
    });

    $('#searchConversations').on('input', function () {
        filterConversations($(this).val());
    });

    $('#messageForm').on('submit', function (e) {
        e.preventDefault();
        sendMessage();
    });
    $('#sendMessageBtn').on('click', function (e) {
        e.preventDefault();
        sendMessage();
    });
    $('#messageInput').on('keypress', function (e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Nueva conversación
    $('#newConversationBtn').on('click', function () {
        $('#newConversationModal').modal('show');
        $('#searchUsersInput').val('');
        $('#usersSearchResults').html('<p class="text-muted text-center">Escribe para buscar usuarios...</p>');
    });

    // Buscar usuarios
    let searchTimeout = null;
    $('#searchUsersInput').on('input', function () {
        const searchTerm = $(this).val().trim();

        clearTimeout(searchTimeout);

        if (searchTerm.length < 2) {
            $('#usersSearchResults').html('<p class="text-muted text-center">Escribe al menos 2 caracteres...</p>');
            return;
        }

        searchTimeout = setTimeout(function () {
            searchUsers(searchTerm);
        }, 300);
    });
});

// Cargar conversaciones
function loadConversations() {
    if (!userId) {
        $('#conversationsItems').html('<div class="text-center text-danger p-4">Error: No se pudo identificar al usuario</div>');
        return;
    }

    $.ajax({
        url: '/api/chat_api.php',
        method: 'GET',
        data: { action: 'get_conversaciones_usuario' },
        dataType: 'json',
        success: function (response) {
            console.log('[Chat] Respuesta completa de get_conversaciones_usuario:', response);
            if (response.success) {
                conversations = response.conversaciones || [];
                console.log('[Chat] Conversaciones recibidas del servidor:', conversations.length);

                // Si no hay conversaciones pero hay debug info que indica que hay mensajes, intentar migración
                if (conversations.length === 0 && response.debug) {
                    const debug = response.debug;
                    console.log('[Chat] Debug info:', debug);

                    // Si hay mensajes pero no conversaciones, intentar migración automática
                    if (debug.count_directo_mensajes > 0 && debug.count_sin_conversacion_id > 0) {
                        console.log('[Chat] Detectados mensajes sin conversacion_id, intentando migración automática...');
                        // Reintentar con migración
                        $.ajax({
                            url: '/api/chat_api.php',
                            method: 'GET',
                            data: { action: 'get_conversaciones_usuario', migrate: '1' },
                            dataType: 'json',
                            success: function (migrateResponse) {
                                if (migrateResponse.success) {
                                    conversations = migrateResponse.conversaciones || [];
                                    console.log('[Chat] Después de migración, conversaciones:', conversations.length);
                                    updateRequestsBadge();
                                    renderConversations(getFilteredConversations());
                                    handleTargetUser();
                                } else {
                                    renderConversations([]);
                                }
                            },
                            error: function () {
                                renderConversations([]);
                            }
                        });
                        return;
                    }
                }

                if (conversations.length > 0) {
                    console.log('[Chat] Primera conversación del servidor:', conversations[0]);
                } else {
                    console.warn('[Chat] El servidor devolvió 0 conversaciones');
                    if (response.debug) {
                        console.log('[Chat] Debug info:', response.debug);
                    }
                }
                updateRequestsBadge();
                renderConversations(getFilteredConversations());
                handleTargetUser();
            } else {
                console.error('[Chat] Error en respuesta:', response.error);
                $('#conversationsItems').html('<div class="text-center text-danger p-4">Error: ' + (response.error || 'No se pudieron cargar las conversaciones') + '</div>');
            }
        },
        error: function (xhr, status, error) {
            console.error('[Chat] Error AJAX al cargar conversaciones:', status, error, xhr.responseText);
            $('#conversationsItems').html('<div class="text-center text-danger p-4">Error al cargar conversaciones. Por favor, recarga la página.</div>');
        }
    });
}

function getFilteredConversations() {
    const base = Array.isArray(conversations) ? conversations : [];
    if (currentTab === 'requests') {
        return base.filter(isRequestConversation);
    }
    return base.filter(conv => !isRequestConversation(conv));
}

function isRequestConversation(conv) {
    if (!conv) {
        return false;
    }
    if (typeof conv.es_solicitud !== 'undefined') {
        return !!conv.es_solicitud;
    }
    if (typeof conv.tipo !== 'undefined' && String(conv.tipo).toLowerCase().includes('solicitud')) {
        return true;
    }
    if (typeof conv.estado !== 'undefined' && String(conv.estado).toLowerCase().includes('pendiente')) {
        return true;
    }
    return false;
}

function updateRequestsBadge() {
    const badge = $('#requestsBadge');
    if (!badge.length) {
        return;
    }
    const count = conversations.filter(isRequestConversation).length;
    if (count > 0) {
        badge.text(count).removeAttr('hidden');
    } else {
        badge.attr('hidden', true);
    }
}

function switchTab(tabName) {
    currentTab = tabName;
    $('.chat-tab').removeClass('active');
    $(`.chat-tab[data-tab="${tabName}"]`).addClass('active');
    const searchTerm = $('#searchConversations').val();
    if (searchTerm && searchTerm.trim().length > 0) {
        filterConversations(searchTerm.trim());
    } else {
        renderConversations(getFilteredConversations());
    }
}

// Renderizar conversaciones
function renderConversations(convs) {
    if (!Array.isArray(convs) || convs.length === 0) {
        resetMainChatView();
        if (currentTab === 'requests') {
            $('#conversationsItems').html(`
                <div class="chat-sidebar-empty">
                    <i class="fas fa-inbox"></i>
                    <p>No tienes solicitudes ocultas.</p>
                    <small>Cuando recibas mensajes de usuarios que no sigues aparecerán aquí.</small>
                </div>
            `);
        } else {
            $('#conversationsItems').html(`
                <div class="chat-sidebar-empty">
                    <i class="fas fa-comments"></i>
                    <p>Aún no hay conversaciones.</p>
                    <small>Busca a un usuario y envíale un mensaje para comenzar.</small>
                </div>
            `);
        }
        return;
    }

    // Filtrar conversaciones válidas: deben tener conversacion_id válido
    const validConvs = convs.filter(function (conv) {
        if (!conv) {
            console.warn('[Chat] Conversación nula omitida');
            return false;
        }
        // Validar que tenga conversacion_id válido (esto es lo más importante)
        const convId = conv.conversacion_id;
        if (!convId || convId === null || convId === '' || convId === 'null' || convId === 'undefined') {
            console.warn('[Chat] Omitiendo conversación sin ID válido:', conv);
            return false;
        }
        // Validar que tenga otro_usuario_id
        if (!conv.otro_usuario_id || conv.otro_usuario_id === null || conv.otro_usuario_id === '') {
            console.warn('[Chat] Omitiendo conversación sin otro_usuario_id:', conv);
            return false;
        }
        // El mensaje puede estar vacío, pero la conversación es válida si tiene ID y otro_usuario_id
        return true;
    });

    console.log('[Chat] Total conversaciones recibidas:', convs.length);
    console.log('[Chat] Conversaciones válidas después de filtro:', validConvs.length);
    if (validConvs.length > 0) {
        console.log('[Chat] Primera conversación válida:', validConvs[0]);
    }

    // Si después del filtrado no hay conversaciones válidas, mostrar mensaje vacío
    if (validConvs.length === 0) {
        resetMainChatView();
        if (currentTab === 'requests') {
            $('#conversationsItems').html(`
                <div class="chat-sidebar-empty">
                    <i class="fas fa-inbox"></i>
                    <p>No tienes solicitudes ocultas.</p>
                    <small>Cuando recibas mensajes de usuarios que no sigues aparecerán aquí.</small>
                </div>
            `);
        } else {
            $('#conversationsItems').html(`
                <div class="chat-sidebar-empty">
                    <i class="fas fa-comments"></i>
                    <p>Aún no hay conversaciones.</p>
                    <small>Busca a un usuario y envíale un mensaje para comenzar.</small>
                </div>
            `);
        }
        return;
    }

    let html = '';
    validConvs.forEach(function (conv) {
        const fechaBruta = conv.ultimo_mensaje_fecha;
        // fechaBruta ahora es un timestamp en milisegundos
        const fecha = fechaBruta ? new Date(fechaBruta) : null;
        const timeStr = fecha ? formatTime(fecha) : '';
        const rawPreview = conv.ultimo_mensaje && conv.ultimo_mensaje.length > 0 ? conv.ultimo_mensaje : 'Sin mensajes todavía';
        const preview = rawPreview.length > 60 ? `${rawPreview.substring(0, 60)}…` : rawPreview;
        const imgUrl = conv.img_otro || 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';
        const activeClass = currentConversationId && conv.conversacion_id === currentConversationId ? 'active' : '';
        const badgeHtml = conv.no_leidos > 0 ? `<span class="conversation-badge-usuario">${conv.no_leidos}</span>` : '';
        const adminTag = conv.es_con_admin ? '<span class="conversation-admin-tag"><i class="fas fa-shield-alt"></i>Admin</span>' : '';

        html += `
            <div class="conversation-item-usuario ${activeClass}" data-conversation-id="${conv.conversacion_id || ''}" data-otro-user-id="${conv.otro_usuario_id}">
                <img src="${imgUrl}" alt="${escapeHtml(conv.nombre_otro)}" class="conversation-avatar-usuario" onerror="this.src='https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg'">
                <div class="conversation-info-usuario">
                    <div class="conversation-name-usuario">${escapeHtml(conv.nombre_otro)} ${adminTag}</div>
                    <div class="conversation-preview-usuario">${escapeHtml(preview)}</div>
                </div>
                <div class="conversation-meta-usuario">
                    <div class="conversation-time-usuario">${timeStr}</div>
                    ${badgeHtml}
                </div>
            </div>
        `;
    });

    $('#conversationsItems').html(html);

    $('.conversation-item-usuario').on('click', function () {
        const convId = $(this).data('conversation-id');
        const otroUserId = $(this).data('otro-user-id');
        selectConversation(convId, otroUserId);
    });

    const activeExists = validConvs.some(c => c.conversacion_id === currentConversationId);
    if (!activeExists) {
        currentConversationId = null;
        lastMessageId = null;
        resetMainChatView();
    }

    handleTargetUser();
}

function handleTargetUser() {
    if (!targetUserIdParam || targetUserHandled) {
        return;
    }

    if (!Array.isArray(conversations)) {
        return;
    }

    const existingConversation = conversations.find(function (conv) {
        return conv.otro_usuario_id === targetUserIdParam;
    });

    if (existingConversation) {
        targetUserHandled = true;
        selectConversation(existingConversation.conversacion_id, existingConversation.otro_usuario_id);
        return;
    }

    fetchTargetUserData(targetUserIdParam);
}

function fetchTargetUserData(userIdTarget) {
    if (!userIdTarget || targetUserHandled) {
        return;
    }

    $.ajax({
        url: '/api/chat_api.php',
        method: 'GET',
        data: {
            action: 'get_usuario_chat',
            usuario_id: userIdTarget
        },
        dataType: 'json',
        success: function (response) {
            if (response && response.success && response.usuario) {
                targetUserHandled = true;
                const usuario = response.usuario;
                const convPlaceholder = {
                    conversacion_id: null,
                    otro_usuario_id: usuario._id,
                    nombre_otro: usuario.username,
                    email_otro: usuario.mail || '',
                    img_otro: usuario.img || '',
                    es_con_admin: !!usuario.es_admin,
                    ultimo_mensaje: '',
                    ultimo_mensaje_fecha: new Date(),
                    perfil_otro: usuario.profile_url || ''
                };

                currentConversationId = null;
                lastMessageId = null;

                showMessagesArea(convPlaceholder);
                $('#chatMainContent').data('otro-user-id', usuario._id);
                $('#messagesList').html('<div class="text-center text-muted p-4">Escribe un mensaje para comenzar la conversación.</div>');
                $('#messageInput').trigger('focus');
            } else if (response && response.error) {
                console.warn('Chat: usuario objetivo no disponible -', response.error);
            }
        },
        error: function () {
            console.warn('Chat: error al cargar información del usuario objetivo');
        }
    });
}

// Filtrar conversaciones
function filterConversations(searchTerm) {
    if (!searchTerm) {
        renderConversations(getFilteredConversations());
        return;
    }

    const baseList = getFilteredConversations();
    const filtered = baseList.filter(function (conv) {
        const search = searchTerm.toLowerCase();
        return conv.nombre_otro.toLowerCase().includes(search) ||
            conv.email_otro.toLowerCase().includes(search) ||
            conv.ultimo_mensaje.toLowerCase().includes(search);
    });

    renderConversations(filtered);
}

// Seleccionar conversación
function selectConversation(conversationId, otroUserId) {
    currentConversationId = conversationId;
    lastMessageId = null;

    // Actualizar UI
    $('.conversation-item-usuario').removeClass('active');
    $(`.conversation-item-usuario[data-conversation-id="${conversationId}"]`).addClass('active');

    // Obtener info de la conversación
    const conv = conversations.find(c => c.conversacion_id === conversationId);
    if (conv) {
        showMessagesArea(conv);
        loadMessages(conversationId);
    }
}

// Mostrar área de mensajes
function showMessagesArea(conv) {
    const imgUrl = conv.img_otro || 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';
    const adminBadge = conv.es_con_admin ? '<span class="conversation-admin-tag"><i class="fas fa-shield-alt"></i>Admin</span>' : '';
    const secondaryLabel = conv.es_con_admin ? 'Equipo Código Amigo' : (conv.email_otro ? escapeHtml(conv.email_otro) : 'Usuario de Código Amigo');

    cachedUserProfiles[conv.otro_usuario_id] = Object.assign({}, conv);

    $('#chatMainPlaceholder').hide();
    $('#chatMainContent').show();

    // Mobile: slide to chat view
    if (window.innerWidth <= 768) {
        $('.chat-layout').addClass('chat-open');
    }

    $('#chatMainHeader').html(`
        <button class="mobile-back-btn" onclick="closeMobileChat()" style="display:none;"><i class="fas fa-arrow-left"></i></button>
        <div class="chat-main-user">
            <img src="${imgUrl}" alt="${escapeHtml(conv.nombre_otro)}" class="chat-main-user-avatar" onerror="this.src='https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg'">
            <div class="chat-main-user-info">
                <h2>${escapeHtml(conv.nombre_otro)} ${adminBadge}</h2>
                <span style="color: var(--success, #38ef7d); font-size: 0.8rem; display: flex; align-items: center; gap: 5px;"><span style="width:7px;height:7px;background:currentColor;border-radius:50%;display:inline-block;"></span> En línea</span>
            </div>
        </div>
        <div class="chat-main-actions">
            <button type="button" class="btn btn-outline-light" id="openProfileButton">
                <i class="fas fa-user-circle me-1"></i>Ver perfil
            </button>
        </div>
    `);

    $('#messagesList').html('<div class="text-center text-muted p-4"><i class="fas fa-spinner fa-spin"></i> Cargando mensajes...</div>');
    $('#chatMainContent').data('otro-user-id', conv.otro_usuario_id);
    $('#chatMainContent').data('conversation-id', conv.conversacion_id || '');
    $('#chatMainContent').data('profile-url', conv.perfil_otro || '');

    updateProfilePanel(conv);

    $('#openProfileButton').on('click', function () {
        const profileUrl = conv.perfil_otro || $('#profileLink').attr('href');
        if (profileUrl && profileUrl !== '#') {
            window.open(profileUrl, '_blank');
        }
    });

    $('#messageInput').val('').prop('disabled', false).focus();
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
        success: function (response) {
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
        error: function () {
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
    mensajes.forEach(function (msg) {
        // msg.fecha ahora es un timestamp en milisegundos
        const fecha = msg.fecha ? new Date(msg.fecha) : new Date();
        const timeStr = formatDateTime(fecha);
        const isSent = String(msg.de_usuario_id) === userId;
        const messageClass = isSent ? 'sent' : 'received';

        html += `
            <div class="message-item-usuario ${messageClass}">
                <div>
                    <div class="message-bubble-usuario">${escapeHtml(msg.mensaje)}</div>
                    <div class="message-time-usuario">${timeStr}</div>
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
    if (!messageText) {
        return;
    }

    // Obtener otro_user_id de la conversación activa
    const conv = conversations.find(c => c.conversacion_id === currentConversationId);
    if (!conv) {
        // Si no hay conversación, necesitamos otro_usuario_id del mensaje area
        const otroUserId = $('#chatMainContent').data('otro-user-id');
        if (!otroUserId) {
            alert('Error: No se puede enviar el mensaje. Por favor, selecciona una conversación.');
            return;
        }

        // Enviar mensaje y crear conversación nueva
        sendMessageToUser(otroUserId, messageText);
        return;
    }

    $('#sendMessageBtn').prop('disabled', true);

    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'enviar_mensaje',
            para_usuario_id: conv.otro_usuario_id,
            mensaje: messageText,
            codigo_id: (() => { const p = (currentConversationId || '').split('-'); return p.length >= 3 ? p[p.length - 1] : ''; })()
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                $('#messageInput').val('');

                // Si es una conversación nueva, actualizar el ID
                if (response.conversacion_id && currentConversationId !== response.conversacion_id) {
                    currentConversationId = response.conversacion_id;
                }

                // Recargar mensajes
                loadMessages(currentConversationId);
                // Recargar conversaciones para actualizar preview
                setTimeout(() => {
                    loadConversations();
                }, 500);
            } else {
                alert('Error: ' + (response.error || 'No se pudo enviar el mensaje'));
            }
            $('#sendMessageBtn').prop('disabled', false);
        },
        error: function () {
            alert('Error al enviar mensaje');
            $('#sendMessageBtn').prop('disabled', false);
        }
    });
}

// Enviar mensaje a un usuario específico (nueva conversación)
function sendMessageToUser(otroUserId, messageText) {
    $('#sendMessageBtn').prop('disabled', true);

    $.ajax({
        url: '/api/chat_api.php',
        method: 'POST',
        data: {
            action: 'enviar_mensaje',
            para_usuario_id: otroUserId,
            mensaje: messageText,
            codigo_id: (() => { const p = (currentConversationId || '').split('-'); return p.length >= 3 ? p[p.length - 1] : ''; })()
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                $('#messageInput').val('');

                // Actualizar conversación con el ID real
                currentConversationId = response.conversacion_id;

                // Recargar conversaciones y luego mensajes
                loadConversations();

                setTimeout(() => {
                    // Buscar la conversación en la lista actualizada
                    const conv = conversations.find(c => c.conversacion_id === response.conversacion_id);
                    if (conv) {
                        selectConversation(response.conversacion_id, otroUserId);
                    } else {
                        // Si no está, cargar mensajes directamente
                        loadMessages(response.conversacion_id);
                    }
                }, 500);
            } else {
                alert('Error: ' + (response.error || 'No se pudo enviar el mensaje'));
            }
            $('#sendMessageBtn').prop('disabled', false);
        },
        error: function () {
            alert('Error al enviar mensaje');
            $('#sendMessageBtn').prop('disabled', false);
        }
    });
}

// Polling para nuevos mensajes
function startPolling() {
    pollingInterval = setInterval(function () {
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

function updateProfilePanel(conv) {
    const profileContainer = $('#chatProfilePanel');
    if (!profileContainer.length) {
        return;
    }

    const placeholder = $('#profilePlaceholder');
    const content = $('#profileContent');

    if (!conv) {
        content.hide();
        placeholder.show();
        return;
    }

    placeholder.hide();
    content.show();

    const avatar = $('#profileAvatar');
    const initials = $('#profileInitials');
    if (conv.img_otro) {
        avatar.attr('src', conv.img_otro).show();
        initials.hide();
    } else {
        avatar.hide();
        initials.text(getInitials(conv.nombre_otro || conv.email_otro || 'Usuario')).show();
    }

    $('#profileName').text(conv.nombre_otro || 'Usuario');
    $('#profileEmail').addClass('profile-email-hidden');

    const profileUrl = conv.perfil_otro || '';
    if (profileUrl) {
        $('#profileLinkAvatar, #profileLinkName').attr('href', profileUrl).removeClass('disabled-link');
    } else {
        $('#profileLinkAvatar, #profileLinkName').attr('href', '#').addClass('disabled-link');
    }

    // Show loading state for meta while we fetch user details
    const metaContainer = $('#profileMeta');
    metaContainer.html(`
        <div class="profile-stat-item">
            <span class="profile-stat-label"><i class="fas fa-spinner fa-spin"></i> Cargando...</span>
        </div>
    `);

    // Fetch detailed user info
    fetchUserProfileDetails(conv.otro_usuario_id, metaContainer);

    // Load code interactions (with timeout protection)
    loadCodeInteractions(conv.otro_usuario_id);
}

// Fetch user profile details from API
function fetchUserProfileDetails(otroUserId, metaContainer) {
    // Check cache first
    if (cachedUserProfiles[otroUserId] && cachedUserProfiles[otroUserId]._detailsFetched) {
        const cached = cachedUserProfiles[otroUserId];
        renderProfileMeta(cached, metaContainer);
        return;
    }

    $.ajax({
        url: '/api/chat_api.php',
        method: 'GET',
        data: {
            action: 'get_usuario_chat',
            usuario_id: otroUserId
        },
        dataType: 'json',
        timeout: 8000,
        success: function (response) {
            if (response.success && response.usuario) {
                const u = response.usuario;
                // Cache the detailed info
                cachedUserProfiles[otroUserId] = Object.assign(cachedUserProfiles[otroUserId] || {}, {
                    _detailsFetched: true,
                    tiempo_miembro: u.tiempo_miembro || '',
                    fecha_registro: u.fecha_registro || '',
                    codigos_count: u.codigos_count || 0,
                    perfil_otro: u.profile_url || ''
                });
                renderProfileMeta(cachedUserProfiles[otroUserId], metaContainer);

                // Update profile link if we got it
                if (u.profile_url) {
                    $('#profileLinkAvatar, #profileLinkName').attr('href', u.profile_url).removeClass('disabled-link');
                }
            } else {
                metaContainer.html('');
            }
        },
        error: function () {
            metaContainer.html('');
        }
    });
}

// Render profile meta stats
function renderProfileMeta(data, metaContainer) {
    let html = '';

    if (data.tiempo_miembro) {
        html += `
            <div class="profile-stat-item">
                <span class="profile-stat-label"><i class="fas fa-clock"></i> Miembro</span>
                <span class="profile-stat-value">${escapeHtml(data.tiempo_miembro)}</span>
            </div>
        `;
    }

    if (data.fecha_registro) {
        html += `
            <div class="profile-stat-item">
                <span class="profile-stat-label"><i class="fas fa-calendar-alt"></i> Registro</span>
                <span class="profile-stat-value">${escapeHtml(data.fecha_registro)}</span>
            </div>
        `;
    }

    if (typeof data.codigos_count !== 'undefined') {
        html += `
            <div class="profile-stat-item">
                <span class="profile-stat-label"><i class="fas fa-tag"></i> Códigos</span>
                <span class="profile-stat-value">${data.codigos_count}</span>
            </div>
        `;
    }

    metaContainer.html(html);
}

// Cargar contexto de la conversación con el otro usuario
function loadCodeInteractions(otroUserId) {
    let interaccionesContainer = $('#profileInteracciones');

    // Si no existe el contenedor, crearlo después de profileMeta
    if (!interaccionesContainer.length) {
        $('#profileMeta').after(`
            <div id="profileInteracciones" class="profile-interacciones">
                <div class="interacciones-loading">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </div>
            </div>
        `);
        interaccionesContainer = $('#profileInteracciones');
    }

    // Mostrar cargando
    interaccionesContainer.html(`
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
        timeout: 10000,
        success: function (response) {
            if (response.success) {
                renderCodeInteractions(response);
            } else {
                interaccionesContainer.html('');
            }
        },
        error: function () {
            interaccionesContainer.html('');
        }
    });
}

// Renderizar contexto de la conversación - REDISEÑO CONTEXTUAL
function renderCodeInteractions(data) {
    const container = $('#profileInteracciones');
    const otroUserId = $('#chatMainContent').data('otro-user-id');

    // Get cached user info for name
    const cachedUser = cachedUserProfiles[otroUserId] || {};
    const otroNombre = cachedUser.nombre_otro || cachedUser.username || 'Usuario';

    const contexto = data.contexto || 'directo';
    const codigoInfo = data.codigo_info || null;
    const soyOwner = data.soy_owner_codigo || false;
    const codigoCompletado = data.codigo_completado || false;

    // === CASO 1: Conversación directa sin código ===
    if (contexto === 'directo' || !codigoInfo) {
        container.html(`
            <div class="interacciones-section">
                <div class="interacciones-header">
                    <i class="fas fa-comment-dots"></i>
                    <span>Contexto</span>
                </div>
                <div class="interacciones-empty">
                    <i class="fas fa-comments"></i>
                    <p>Conversación directa sin código asociado.</p>
                </div>
            </div>
        `);
        return;
    }

    // === CASO 2: Hay código asociado ===
    const marca = escapeHtml(codigoInfo.marca);
    const beneficio = codigoInfo.beneficio || 0;

    let textoContexto = '';
    if (contexto === 'me_contactaron') {
        if (soyOwner) {
            textoContexto = `<strong>${escapeHtml(otroNombre)}</strong> te contactó por tu código de <strong>${marca}</strong>`;
        } else {
            textoContexto = `<strong>${escapeHtml(otroNombre)}</strong> te contactó por su código de <strong>${marca}</strong>`;
        }
    } else if (contexto === 'yo_contacte') {
        if (soyOwner) {
            textoContexto = `Contactaste a <strong>${escapeHtml(otroNombre)}</strong> por tu código de <strong>${marca}</strong>`;
        } else {
            textoContexto = `Contactaste a <strong>${escapeHtml(otroNombre)}</strong> por su código de <strong>${marca}</strong>`;
        }
    }

    const strCodigo = codigoInfo.str_codigo ? escapeHtml(codigoInfo.str_codigo) : '';
    const codigoUrl = codigoInfo.url ? escapeHtml(codigoInfo.url) : '';
    const linkHref = codigoUrl ? '/codigo/' + codigoUrl : '#';

    let html = `
        <div class="interacciones-section">
            <div class="interacciones-header">
                <i class="fas fa-link"></i>
                <span>Contexto</span>
            </div>
            <div style="padding: 1rem;">
                <p style="font-size: 0.85rem; color: var(--text-secondary, #adb5bd); margin: 0 0 1rem 0; line-height: 1.5;">${textoContexto}</p>
                <a href="${linkHref}" target="_blank" style="text-decoration: none; color: inherit; display: block;" class="contexto-card-link">
                    <div class="interaccion-item" data-codigo-id="${codigoInfo.codigo_id}" data-beneficio="${beneficio}" style="display: flex; flex-direction: column; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; transition: all 0.2s;">
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: ${strCodigo ? '0.85rem' : '0'};">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 38px; height: 38px; border-radius: 8px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.05)); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-tag" style="font-size: 0.9rem; color: #f59e0b;"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 1rem; color: white;">${marca}</div>
                                    <div style="font-size: 0.8rem; color: #9ca3af;">Beneficio: <span style="color: #f59e0b; font-weight: 600;">${beneficio}€</span></div>
                                </div>
                            </div>
    `;

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
                            <i class="fas fa-external-link-alt" style="color: #6b7280; font-size: 0.85rem;"></i>
        `;
    }

    html += `
                        </div>
                        
                        ${strCodigo ? `
                        <div style="background: rgba(0,0,0,0.3); border: 1px dashed rgba(255, 255, 255, 0.2); border-radius: 8px; padding: 0.85rem; text-align: center; margin-top: 0.25rem;">
                            <span style="font-family: monospace; font-size: 1.25rem; font-weight: 700; color: #ff3366; letter-spacing: 2px;">${strCodigo}</span>
                        </div>
                        ` : ''}
                        
                    </div>
                </a>
    `;

    // Mensaje contextual
    if (soyOwner) {
        if (codigoCompletado) {
            html += `<p style="margin-top: 0.75rem; font-size: 0.8rem; color: #10b981; padding: 0.5rem 0.75rem; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); border-radius: 8px;"><i class="fas fa-check-circle"></i> Marcado como completado · +${beneficio}€</p>`;
        } else {
            html += `<p style="margin-top: 0.75rem; font-size: 0.8rem; color: #9ca3af; padding: 0.5rem 0.75rem; background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.15); border-radius: 8px;"><i class="fas fa-lightbulb" style="color: #f59e0b;"></i> Si ya ha usado tu código, activa el toggle.</p>`;
        }
    } else if (contexto === 'yo_contacte') {
        html += `<p style="margin-top: 0.75rem; font-size: 0.8rem; color: #9ca3af; padding: 0.5rem 0.75rem; background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.15); border-radius: 8px;"><i class="fas fa-info-circle" style="color: #6366f1;"></i> Si usas su código, ambos ganaréis <strong style="color: #f59e0b;">${beneficio}€</strong>.</p>`;
    }

    html += `</div></div>`;
    container.html(html);

    // Eventos toggle
    if (soyOwner) {
        container.find('.interaccion-toggle').on('change', function () {
            const isChecked = $(this).is(':checked');
            const codigoId = $(this).closest('.interaccion-item').data('codigo-id');
            const beneficioVal = parseFloat($(this).closest('.interaccion-item').data('beneficio') || 0);

            const completedKey = `completed_codes_${currentUserId}_${otroUserId}`;
            let completedCodes = {};
            try { completedCodes = JSON.parse(localStorage.getItem(completedKey) || '{}'); } catch (e) { completedCodes = {}; }

            if (isChecked) {
                completedCodes[codigoId] = true;
            } else {
                delete completedCodes[codigoId];
            }
            localStorage.setItem(completedKey, JSON.stringify(completedCodes));

            renderCodeInteractions(Object.assign({}, data, { codigo_completado: isChecked }));

            $.ajax({
                url: '/api/chat_api.php',
                method: 'POST',
                data: {
                    action: 'toggle_codigo_completado',
                    codigo_id: codigoId,
                    usuario_referido_id: otroUserId,
                    completado: isChecked,
                    beneficio: beneficioVal
                }
            });
        });
    }
}

function resetMainChatView() {
    $('#chatMainPlaceholder').show();
    $('#chatMainContent').hide();
    $('#chatMainContent').data('otro-user-id', '');
    $('#chatMainContent').data('conversation-id', '');
    updateProfilePanel(null);
}

function closeMobileChat() {
    $('.chat-layout').removeClass('chat-open');
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
    if (!text) {
        return '';
    }
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function getInitials(text) {
    if (!text || typeof text !== 'string') return 'CA';
    const parts = text.trim().split(/\s+/).filter(part => part.length > 0);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    if (parts.length === 1) {
        return parts[0].substring(0, 2).toUpperCase();
    }
    return 'CA';
}

// Buscar usuarios
function searchUsers(searchTerm) {
    $('#usersSearchResults').html('<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>');

    $.ajax({
        url: '/api/chat_api.php',
        method: 'GET',
        data: {
            action: 'buscar_usuarios',
            busqueda: searchTerm
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                renderUserSearchResults(response.usuarios);
            } else {
                $('#usersSearchResults').html('<div class="text-danger p-3">Error al buscar usuarios</div>');
            }
        },
        error: function () {
            $('#usersSearchResults').html('<div class="text-danger p-3">Error al buscar usuarios</div>');
        }
    });
}

// Renderizar resultados de búsqueda de usuarios
function renderUserSearchResults(usuarios) {
    if (usuarios.length === 0) {
        $('#usersSearchResults').html('<p class="text-muted text-center p-3">No se encontraron usuarios</p>');
        return;
    }

    let html = '';
    usuarios.forEach(function (usuario) {
        const imgUrl = usuario.img || 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';
        html += `
            <div class="user-search-item p-3 border-bottom cursor-pointer" style="cursor: pointer;" data-user-id="${usuario._id}" data-user-name="${escapeHtml(usuario.username)}">
                <div class="d-flex align-items-center gap-3">
                    <img src="${imgUrl}" alt="${escapeHtml(usuario.username)}" 
                         class="rounded-circle" style="width: 45px; height: 45px; object-fit: cover;"
                         onerror="this.src='https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg'">
                    <div class="flex-grow-1">
                        <div class="fw-bold">${escapeHtml(usuario.username)}</div>
                        <small class="text-muted">${escapeHtml(usuario.mail)}</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </div>
            </div>
        `;
    });

    $('#usersSearchResults').html(html);

    // Click en usuario
    $('.user-search-item').on('click', function () {
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');
        startNewConversation(userId, userName);
    });
}

// Iniciar nueva conversación
function startNewConversation(otroUserId, otroUserName) {
    // Cerrar modal
    $('#newConversationModal').modal('hide');

    // Verificar si ya existe una conversación con este usuario
    const existingConv = conversations.find(function (conv) {
        return conv.otro_usuario_id === otroUserId;
    });

    if (existingConv) {
        // Si existe, seleccionarla
        selectConversation(existingConv.conversacion_id, otroUserId);
        return;
    }

    // Crear conversación simulada para mostrar
    const conversacionId = crearConversacionIdTemp(userId, otroUserId);

    const nuevaConversacion = {
        conversacion_id: conversacionId,
        otro_usuario_id: otroUserId,
        es_con_admin: false,
        nombre_otro: otroUserName,
        email_otro: '',
        img_otro: '',
        ultimo_mensaje: '',
        ultimo_mensaje_fecha: new Date(),
        no_leidos: 0,
        es_admin_ultimo: false,
        perfil_otro: ''
    };

    // Añadir a la lista de conversaciones temporalmente
    conversations.unshift(nuevaConversacion);
    updateRequestsBadge();
    renderConversations(getFilteredConversations());

    // Establecer conversación actual
    currentConversationId = conversacionId;
    lastMessageId = null;

    // Mostrar área de mensajes vacía
    showMessagesArea(nuevaConversacion);

    // Mostrar mensaje indicando que es nueva conversación
    $('#messagesList').html('<div class="text-center text-muted p-4">Nueva conversación con ' + escapeHtml(otroUserName) + '<br><small>Escribe un mensaje para comenzar</small></div>');
}

// Crear ID temporal de conversación
function crearConversacionIdTemp(user1Id, user2Id, codigoId) {
    const ids = [user1Id, user2Id];
    ids.sort();
    let convId = ids[0] + '-' + ids[1];
    if (codigoId) {
        convId += '-' + codigoId;
    }
    return convId;
}

// Limpiar interval al salir
window.addEventListener('beforeunload', function () {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

