/**
 * Sistema de favoritos para códigos
 */

(function () {
    'use strict';

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function () {
        initFavoritos();
    });

    // También escuchar cuando se añade un botón dinámicamente
    document.addEventListener('favoriteButtonAdded', function (event) {
        if (event.detail && event.detail.button) {
            event.detail.button.addEventListener('click', handleFavoriteClick);
        }
    });

    // Función para re-inicializar (útil cuando se añaden botones dinámicamente)
    function initFavoritos() {
        // Añadir event listeners a todos los botones de favoritos
        const favoriteButtons = document.querySelectorAll('.favorite-btn');

        favoriteButtons.forEach(button => {
            // Remover listeners anteriores para evitar duplicados
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            newButton.addEventListener('click', handleFavoriteClick);
        });
    }

    // Hacer la función disponible globalmente para que pueda ser llamada desde otros scripts
    window.initFavoritos = initFavoritos;

    async function handleFavoriteClick(event) {
        event.preventDefault();
        event.stopPropagation();

        const button = event.currentTarget;
        const codigoId = button.getAttribute('data-codigo-id');

        if (!codigoId) {
            console.error('ID de código no encontrado');
            return;
        }

        const isActive = button.classList.contains('active');
        const action = isActive ? 'eliminar_favorito' : 'añadir_favorito';
        const tipo = button.getAttribute('data-tipo') || 'codigo';

        // Feedback visual inmediato
        button.style.opacity = '0.6';
        button.disabled = true;

        try {
            const formData = new FormData();
            formData.append('metodo', action);
            formData.append('codigo_id', codigoId);
            formData.append('tipo', tipo);

            const response = await fetch('/ajax_actions', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Respuesta no es JSON:', text.substring(0, 200));
                throw new Error('El servidor devolvió una respuesta no válida. Por favor, recarga la página e inténtalo de nuevo.');
            }

            const result = await response.json();

            if (result.success) {
                // Actualizar estado visual
                if (action === 'añadir_favorito') {
                    button.classList.add('active');
                    // Asegurar que siempre use 'fas' (solid)
                    const icon = button.querySelector('i');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    button.setAttribute('title', 'Quitar de favoritos');

                    // Mostrar notificación
                    showNotification('Código añadido a favoritos', 'success');
                } else {
                    button.classList.remove('active');
                    // Asegurar que siempre use 'fas' (solid)
                    const icon = button.querySelector('i');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    button.setAttribute('title', 'Añadir a favoritos');

                    // Mostrar notificación
                    showNotification('Código eliminado de favoritos', 'info');
                }
            } else {
                // Mostrar error
                showNotification(result.message || 'Error al procesar la acción', 'error');

                // Revertir estado visual
                if (isActive) {
                    button.classList.add('active');
                } else {
                    button.classList.remove('active');
                }
            }
        } catch (error) {
            console.error('Error en favoritos:', error);
            const errorMessage = error.message || 'Error de conexión. Inténtalo de nuevo.';
            showNotification(errorMessage, 'error');

            // Revertir estado visual
            if (isActive) {
                button.classList.add('active');
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                }
            } else {
                button.classList.remove('active');
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.remove('fas');
                    icon.classList.add('fas');
                }
            }
        } finally {
            button.style.opacity = '1';
            button.disabled = false;
        }
    }

    function showNotification(message, type = 'info') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `favorite-notification favorite-notification-${type}`;
        notification.textContent = message;

        // Estilos inline
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            max-width: 300px;
        `;

        document.body.appendChild(notification);

        // Remover después de 3 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Añadir animaciones CSS si no existen
    if (!document.getElementById('favoritos-animations')) {
        const style = document.createElement('style');
        style.id = 'favoritos-animations';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            .favorite-btn {
                background: transparent !important;
                border: 2px solid #bbb !important;
                border-radius: 50% !important;
                width: 40px !important;
                height: 40px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                cursor: pointer !important;
                transition: all 0.3s ease !important;
                color: #555 !important;
                padding: 0 !important;
                position: relative !important;
                flex-shrink: 0 !important;
                margin: 0 !important;
            }
            .favorite-btn i {
                font-size: 18px !important;
                transition: all 0.3s ease !important;
                display: inline-block !important;
                line-height: 1 !important;
                color: inherit !important;
                font-weight: 900 !important;
            }
            .favorite-btn:hover {
                border-color: #E30613 !important;
                color: #E30613 !important;
                transform: scale(1.1) !important;
                background: rgba(227, 6, 19, 0.1) !important;
            }
            .favorite-btn:hover i {
                color: #E30613 !important;
            }
            .favorite-btn.active {
                background: #E30613 !important;
                border-color: #E30613 !important;
                color: white !important;
            }
            .favorite-btn.active i {
                color: white !important;
            }
            .favorite-btn.active:hover {
                background: #C40510 !important;
                border-color: #C40510 !important;
            }
            .favorite-btn.active:hover i {
                color: white !important;
            }
            .code-actions {
                display: flex !important;
                gap: 10px !important;
                align-items: center !important;
            }
        `;
        document.head.appendChild(style);
    }
})();

