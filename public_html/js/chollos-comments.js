/**
 * Sistema de Comentarios de Chollos
 * Maneja comentarios con respuestas anidadas y votación
 */

(function () {
    'use strict';

    // Verificar si el usuario está autenticado
    const isAuthenticated = typeof currentUserId !== 'undefined' && currentUserId;
    let currentCholloId = null;
    let currentSortOrder = 'nuevos';

    /**
     * Inicializa el sistema de comentarios
     */
    function initComments(cholloId) {
        currentCholloId = cholloId;

        if (!currentCholloId) {
            console.error('[Comments] No se proporcionó chollo ID');
            return;
        }

        // Configurar event listeners
        setupEventListeners();

        // Cargar comentarios
        loadComments();

        // Verificar si hay un comentario pendiente de publicar (después de login)
        checkPendingComment();

        console.log('[Comments] Sistema de comentarios inicializado para chollo:', cholloId);
    }

    /**
     * Verifica si hay un comentario guardado en localStorage para este chollo
     */
    function checkPendingComment() {
        console.log('[Comments] Checking pending comment. Auth:', isAuthenticated, 'Chollo:', currentCholloId);
        if (!isAuthenticated) return;

        const pending = localStorage.getItem('pending_comment');
        if (pending) {
            try {
                const data = JSON.parse(pending);
                console.log('[Comments] Found pending:', data);
                // Usar == para evitar problemas de tipos (string vs int)
                if (data.chollo_id == currentCholloId && data.text) {
                    console.log('[Comments] Detectado comentario pendiente, intentando publicar...');

                    // Pequeño delay para asegurar que todo esté listo
                    setTimeout(() => {
                        autoSubmitComment(data.text);
                        localStorage.removeItem('pending_comment');
                    }, 1000); // Aumentado a 1000ms para asegurar carga total
                } else {
                    console.log('[Comments] ID mismatch or missing text:', data.chollo_id, currentCholloId);
                }
            } catch (e) {
                console.error('[Comments] Error al parsear comentario pendiente:', e);
                localStorage.removeItem('pending_comment');
            }
        } else {
            console.log('[Comments] No pending comment found in localStorage');
        }
    }

    /**
     * Publica automáticamente un comentario guardado
     */
    function autoSubmitComment(text) {
        const formData = new FormData();
        formData.append('action', 'crear');
        formData.append('chollo_id', currentCholloId);
        formData.append('comentario', text);

        fetch('/api/chollos_comentarios.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('¡Tu comentario ha sido publicado!', 'success');
                    loadComments();
                }
            })
            .catch(error => console.error('[Comments] Error en auto-submit:', error));
    }

    /**
     * Configura los event listeners
     */
    function setupEventListeners() {
        // Formulario principal de comentarios
        const mainForm = document.getElementById('comment-form');
        if (mainForm) {
            mainForm.addEventListener('submit', handleCommentSubmit);

            const textarea = mainForm.querySelector('textarea');
            const container = document.getElementById('comment-form-wrapper');
            const submitBtn = mainForm.querySelector('.comment-btn-submit');

            // Expandir al hacer focus
            textarea.addEventListener('focus', () => {
                container.classList.remove('collapsed');
                container.classList.add('expanded');
            });

            // Controlar el botón de enviar
            textarea.addEventListener('input', function () {
                const text = this.value.trim();
                const hasContent = text.length > 0;

                submitBtn.disabled = !hasContent;
                if (hasContent) {
                    submitBtn.classList.add('active');
                } else {
                    submitBtn.classList.remove('active');
                }
            });

            // Enviar con Enter (sin Shift)
            textarea.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (!submitBtn.disabled) {
                        mainForm.dispatchEvent(new Event('submit', { cancelable: true }));
                    }
                }
            });

            // Click fuera para colapsar si está vacío
            document.addEventListener('click', (e) => {
                if (!container.contains(e.target) && !textarea.value.trim()) {
                    container.classList.remove('expanded');
                    container.classList.add('collapsed');
                }
            });
        }

        // Selector de ordenamiento
        const sortSelect = document.getElementById('comments-sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                currentSortOrder = e.target.value;
                loadComments();
            });
        }
    }

    /**
     * Carga los comentarios del servidor
     */
    function loadComments() {
        const container = document.getElementById('comments-list');
        if (!container) return;

        // Mostrar loading
        container.innerHTML = '<div class="comments-loading"><i class="fas fa-spinner fa-spin"></i></div>';

        fetch(`/api/chollos_comentarios.php?action=listar&chollo_id=${currentCholloId}&orden=${currentSortOrder}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderComments(container, data.comentarios);
                    updateCommentsCount(data.total);
                } else {
                    container.innerHTML = '<div class="comments-empty"><p>Error al cargar comentarios</p></div>';
                }
            })
            .catch(error => {
                console.error('[Comments] Error cargando comentarios:', error);
                container.innerHTML = '<div class="comments-empty"><p>Error de conexión</p></div>';
            });
    }

    /**
     * Renderiza los comentarios
     */
    function renderComments(container, comentarios) {
        if (comentarios.length === 0) {
            container.innerHTML = `
                <div class="comments-empty">
                    <i class="fas fa-comments"></i>
                    <p>No hay comentarios todavía</p>
                    <p style="font-size: 0.9em; color: #bbb;">Sé el primero en comentar</p>
                </div>
            `;
            return;
        }

        const html = comentarios.map(comment => renderComment(comment)).join('');
        container.innerHTML = html;

        // Agregar event listeners a los botones
        attachCommentEventListeners();

        // Auto-scroll si hay hash en la URL
        if (window.location.hash) {
            const hashId = window.location.hash.substring(1);
            const target = document.getElementById(hashId);
            if (target) {
                setTimeout(() => {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    target.classList.add('highlight-pulse');
                }, 500);
            }
        }
    }

    /**
     * Renderiza un comentario individual
     */
    function renderComment(comment, isReply = false) {
        const avatar = comment.usuario_img
            ? `<img src="${comment.usuario_img}" alt="${escapeHtml(comment.usuario_nombre)}">`
            : `<div class="comment-avatar-placeholder">${getInitials(comment.usuario_nombre)}</div>`;

        const timeAgo = formatTimeAgo(comment.fecha);
        const editedText = comment.editado ? `<span class="comment-edited">(editado)</span>` : '';

        const isOwn = isAuthenticated && currentUserId === comment.usuario_id;

        let html = `
            <div class="comment-item" id="comment-${comment.id}" data-comment-id="${comment.id}">
                <div class="comment-avatar">
                    ${avatar}
                </div>
                <div class="comment-content">
                    <div class="comment-header">
                        <span class="comment-author">${escapeHtml(comment.usuario_nombre)}</span>
                        <span class="comment-time">${timeAgo}</span>
                        ${editedText}
                    </div>
                    <div class="comment-text">${escapeHtml(comment.comentario)}</div>
                    <div class="comment-actions">
                        <div class="comment-vote">
                            <button class="comment-vote-btn vote-up-comment" data-comment-id="${comment.id}" data-tipo="positivo">
                                <i class="fas fa-thumbs-up"></i>
                            </button>
                            <span class="comment-vote-count">${comment.votos_positivos - comment.votos_negativos}</span>
                            <button class="comment-vote-btn vote-down-comment" data-comment-id="${comment.id}" data-tipo="negativo">
                                <i class="fas fa-thumbs-down"></i>
                            </button>
                        </div>
                        ${isAuthenticated ? `
                            <button class="comment-action-btn reply-btn" data-comment-id="${comment.id}">
                                <i class="fas fa-reply"></i> Responder
                            </button>
                        ` : ''}
                        ${isOwn ? `
                            <button class="comment-action-btn edit-btn" data-comment-id="${comment.id}">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="comment-action-btn delete-btn" data-comment-id="${comment.id}">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        ` : ''}
                    </div>
                    ${comment.respuestas && comment.respuestas.length > 0 ? `
                        <div class="comment-replies">
                            ${comment.respuestas.map(reply => renderComment(reply, true)).join('')}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;

        return html;
    }

    /**
     * Adjunta event listeners a los botones de comentarios
     */
    function attachCommentEventListeners() {
        // Botones de voto
        document.querySelectorAll('.vote-up-comment, .vote-down-comment').forEach(btn => {
            btn.addEventListener('click', handleCommentVote);
        });

        // Botones de responder
        document.querySelectorAll('.reply-btn').forEach(btn => {
            btn.addEventListener('click', handleReplyClick);
        });

        // Botones de editar
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', handleEditClick);
        });

        // Botones de eliminar
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', handleDeleteClick);
        });
    }

    /**
     * Maneja el envío del formulario de comentario
     */
    function handleCommentSubmit(e) {
        e.preventDefault();

        if (!isAuthenticated) {
            const textarea = e.target.querySelector('textarea');
            const comentario = textarea.value.trim();

            if (comentario) {
                localStorage.setItem('pending_comment', JSON.stringify({
                    chollo_id: currentCholloId,
                    text: comentario
                }));
            }

            showLoginPrompt();
            return;
        }

        const form = e.target;
        const textarea = form.querySelector('textarea');
        const comentario = textarea.value.trim();
        const padreId = form.dataset.padreId || null;

        if (!comentario) {
            showNotification('Escribe un comentario', 'error');
            return;
        }

        const submitBtn = form.querySelector('.comment-btn-submit') || form.querySelector('.comment-btn-primary');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        const formData = new FormData();
        formData.append('action', 'crear');
        formData.append('chollo_id', currentCholloId);
        formData.append('comentario', comentario);
        if (padreId) {
            formData.append('padre_id', padreId);
        }

        fetch('/api/chollos_comentarios.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    textarea.value = '';
                    localStorage.removeItem('pending_comment'); // Limpiar por si acaso

                    // Resetear estado del formulario
                    const container = document.getElementById('comment-form-wrapper');
                    const submitBtn = form.querySelector('.comment-btn-submit');
                    if (container) {
                        container.classList.remove('expanded');
                        container.classList.add('collapsed');
                    }
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.remove('active');
                    }

                    showNotification('Comentario publicado', 'success');
                    loadComments(); // Recargar comentarios

                    // Si es una respuesta, cerrar el formulario
                    if (padreId) {
                        form.remove();
                    }
                } else {
                    showNotification(data.error || 'Error al publicar comentario', 'error');
                }
            })
            .catch(error => {
                console.error('[Comments] Error:', error);
                showNotification('Error de conexión', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> ' + (padreId ? 'Responder' : 'Comentar');

                // Re-verificar estado para el botón principal
                if (!padreId) {
                    const hasContent = textarea.value.trim().length > 0;
                    submitBtn.disabled = !hasContent;
                    if (!hasContent) submitBtn.classList.remove('active');
                }
            });
    }

    /**
     * Maneja el voto de un comentario
     */
    function handleCommentVote(e) {
        e.preventDefault();

        if (!isAuthenticated) {
            showLoginPrompt();
            return;
        }

        const btn = e.currentTarget;
        const comentarioId = btn.dataset.commentId;
        const tipo = btn.dataset.tipo;

        fetch('/api/chollos_comentarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=votar&comentario_id=${comentarioId}&tipo=${tipo}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar contador de votos
                    const commentItem = btn.closest('.comment-item');
                    const voteCount = commentItem.querySelector('.comment-vote-count');
                    if (voteCount) {
                        voteCount.textContent = data.votos.positivos - data.votos.negativos;
                    }

                    // Toggle active state
                    const voteButtons = commentItem.querySelectorAll('.comment-vote-btn');
                    voteButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.toggle('active');
                }
            })
            .catch(error => console.error('[Comments] Error votando:', error));
    }

    /**
     * Maneja el click en responder
     */
    function handleReplyClick(e) {
        const btn = e.currentTarget;
        const commentId = btn.dataset.commentId;
        const commentItem = btn.closest('.comment-item');

        // Verificar si ya existe un formulario de respuesta
        if (commentItem.querySelector('.comment-reply-form')) {
            return;
        }

        // Crear formulario de respuesta
        const replyForm = createReplyForm(commentId);
        commentItem.querySelector('.comment-content').appendChild(replyForm);
    }

    /**
     * Crea un formulario de respuesta
     */
    function createReplyForm(padreId) {
        const div = document.createElement('div');
        div.className = 'comment-reply-form';
        div.innerHTML = `
            <form class="comment-form" data-padre-id="${padreId}">
                <textarea class="comment-textarea" placeholder="Escribe tu respuesta..." required></textarea>
                <div class="comment-form-actions">
                    <button type="button" class="comment-btn comment-btn-secondary cancel-reply-btn">Cancelar</button>
                    <button type="submit" class="comment-btn comment-btn-primary">
                        <i class="fas fa-paper-plane"></i> Responder
                    </button>
                </div>
            </form>
        `;

        const form = div.querySelector('form');
        form.addEventListener('submit', handleCommentSubmit);

        const cancelBtn = div.querySelector('.cancel-reply-btn');
        cancelBtn.addEventListener('click', () => div.remove());

        return div;
    }

    /**
     * Maneja el click en editar
     */
    function handleEditClick(e) {
        const btn = e.currentTarget;
        const commentId = btn.dataset.commentId;
        const commentItem = btn.closest('.comment-item');
        const textDiv = commentItem.querySelector('.comment-text');
        const currentText = textDiv.textContent;

        // Crear formulario de edición
        const editForm = document.createElement('div');
        editForm.className = 'comment-edit-form';
        editForm.innerHTML = `
            <textarea class="comment-textarea">${currentText}</textarea>
            <div class="comment-form-actions">
                <button type="button" class="comment-btn comment-btn-secondary cancel-edit-btn">Cancelar</button>
                <button type="button" class="comment-btn comment-btn-primary save-edit-btn">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        `;

        textDiv.style.display = 'none';
        textDiv.after(editForm);

        const cancelBtn = editForm.querySelector('.cancel-edit-btn');
        cancelBtn.addEventListener('click', () => {
            editForm.remove();
            textDiv.style.display = 'block';
        });

        const saveBtn = editForm.querySelector('.save-edit-btn');
        saveBtn.addEventListener('click', () => {
            const newText = editForm.querySelector('textarea').value.trim();
            if (!newText) return;

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            fetch('/api/chollos_comentarios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=editar&comentario_id=${commentId}&comentario=${encodeURIComponent(newText)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        textDiv.textContent = newText;
                        editForm.remove();
                        textDiv.style.display = 'block';

                        // Agregar indicador de editado si no existe
                        const header = commentItem.querySelector('.comment-header');
                        if (!header.querySelector('.comment-edited')) {
                            const editedSpan = document.createElement('span');
                            editedSpan.className = 'comment-edited';
                            editedSpan.textContent = '(editado)';
                            header.appendChild(editedSpan);
                        }

                        showNotification('Comentario actualizado', 'success');
                    } else {
                        showNotification('Error al editar', 'error');
                    }
                })
                .catch(error => {
                    console.error('[Comments] Error editando:', error);
                    showNotification('Error de conexión', 'error');
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> Guardar';
                });
        });
    }

    /**
     * Maneja el click en eliminar
     */
    function handleDeleteClick(e) {
        const btn = e.currentTarget;
        const commentId = btn.dataset.commentId;

        if (!confirm('¿Estás seguro de que quieres eliminar este comentario?')) {
            return;
        }

        fetch('/api/chollos_comentarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=eliminar&comentario_id=${commentId}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Comentario eliminado', 'success');
                    loadComments();
                } else {
                    showNotification('Error al eliminar', 'error');
                }
            })
            .catch(error => {
                console.error('[Comments] Error eliminando:', error);
                showNotification('Error de conexión', 'error');
            });
    }

    /**
     * Actualiza el contador de comentarios
     */
    function updateCommentsCount(count) {
        const countElement = document.querySelector('.comments-count');
        if (countElement) {
            countElement.textContent = `${count} comentario${count !== 1 ? 's' : ''}`;
        }
    }

    /**
     * Formatea el tiempo transcurrido
     */
    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'hace un momento';
        if (seconds < 3600) return `hace ${Math.floor(seconds / 60)} min`;
        if (seconds < 86400) return `hace ${Math.floor(seconds / 3600)} h`;
        if (seconds < 604800) return `hace ${Math.floor(seconds / 86400)} d`;

        return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
    }

    /**
     * Obtiene las iniciales de un nombre
     */
    function getInitials(name) {
        if (!name) return 'U';
        const parts = name.split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }

    /**
     * Escapa HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Muestra prompt de login
     */
    function showLoginPrompt() {
        // Guardar URL actual para volver después del login
        try {
            localStorage.setItem('redirectAfterLogin', window.location.href);
        } catch (e) {
            console.error('[Comments] Error saving redirect url:', e);
        }

        if (typeof openLoginModalWithRedirect === 'function') {
            openLoginModalWithRedirect(window.location.href);
        } else if (typeof showLoginModal === 'function') {
            showLoginModal();
        } else {
            showNotification('Debes iniciar sesión para comentar', 'info');
            setTimeout(() => {
                window.location.href = '/?login=1';
            }, 1500);
        }
    }

    /**
     * Muestra una notificación
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `comment-notification comment-notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#3b82f6'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Exponer funciones globalmente
    window.ChollosComments = {
        init: initComments,
        reload: loadComments
    };

})();
