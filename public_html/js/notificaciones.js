$(document).ready(function () {
    // Solo ejecutar si el usuario está logueado
    if (typeof window.currentUserId === 'undefined' || !window.currentUserId) {
        return;
    }

    const NOTIFICATION_POLL_INTERVAL = 30000; // 30 segundos
    let notificationCount = 0;

    // Inicializar
    checkNotifications();
    setInterval(checkNotifications, NOTIFICATION_POLL_INTERVAL);

    // Toggle dropdown
    $('#notification-btn').click(function (e) {
        e.stopPropagation();
        $('#notification-dropdown').toggleClass('active');
        if ($('#notification-dropdown').hasClass('active')) {
            loadNotifications();
        }
    });

    // Cerrar al hacer click fuera
    $(document).click(function (e) {
        if (!$(e.target).closest('.notification-container').length) {
            $('#notification-dropdown').removeClass('active');
        }
    });

    // Marcar todas como leídas
    $('#mark-all-read').click(function (e) {
        e.stopPropagation();
        $.post('/api/notificaciones.php', { action: 'marcar_todas' }, function (response) {
            if (response.success) {
                updateBadge(0);
                $('.notification-item').removeClass('unread');
            }
        });
    });

    // Función para comprobar nuevas notificaciones
    function checkNotifications() {
        $.getJSON('/api/notificaciones.php', { action: 'contar' }, function (response) {
            if (response.success) {
                updateBadge(response.count);
            }
        });
    }

    // Actualizar badge
    function updateBadge(count) {
        notificationCount = count;
        const badge = $('#notification-badge');
        if (count > 0) {
            badge.text(count > 99 ? '99+' : count).show();
        } else {
            badge.hide();
        }
    }

    // Cargar lista de notificaciones
    function loadNotifications() {
        const list = $('#notification-list');
        list.html('<div class="notification-empty"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');

        $.getJSON('/api/notificaciones.php', { action: 'listar', limit: 10 }, function (response) {
            if (response.success && response.notificaciones.length > 0) {
                list.empty();
                response.notificaciones.forEach(n => {
                    const item = $(`
                        <a href="${n.enlace}" class="notification-item ${n.leido ? '' : 'unread'}" data-id="${n.id}">
                            <div class="notification-icon"><i class="${n.icono}"></i></div>
                            <div class="notification-content">
                                <span class="notification-text">${n.mensaje}</span>
                                <span class="notification-time">${n.fecha_relativa}</span>
                            </div>
                        </a>
                    `);

                    // Marcar como leída al hacer click
                    item.click(function () {
                        if (!n.leido) {
                            $.post('/api/notificaciones.php', {
                                action: 'marcar_leida',
                                id: n.id
                            });
                        }
                    });

                    list.append(item);
                });
            } else {
                list.html('<div class="notification-empty">No tienes notificaciones recientes</div>');
            }
        });
    }
});
