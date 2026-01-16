/**
 * Sistema de Votación de Chollos
 * Maneja la votación de chollos con actualización en tiempo real
 */

(function () {
    'use strict';

    // Verificar si el usuario está autenticado
    const isAuthenticated = typeof currentUserId !== 'undefined' && currentUserId;

    /**
     * Inicializa el sistema de votación
     */
    /**
     * Inicializa el sistema de votación
     */
    function initVoting() {
        // Agregar event listeners a todos los botones de voto (standard y premium)
        const voteButtons = document.querySelectorAll('.vote-btn, .vote-btn-premium');
        console.log('[Voting] Inicializando sistema de votación...');
        console.log('[Voting] Botones de voto encontrados:', voteButtons.length);

        voteButtons.forEach(btn => {
            // Remover listeners previos para evitar duplicados si se llama initVoting múltiples veces
            btn.removeEventListener('click', handleVoteClick);
            btn.addEventListener('click', handleVoteClick);
        });

        // Cargar estado de votos del usuario si está autenticado
        if (isAuthenticated) {
            console.log('[Voting] Usuario autenticado, cargando votos...');
            loadUserVotes();
        } else {
            console.log('[Voting] Usuario no autenticado');
        }

        console.log('[Voting] Sistema de votación inicializado correctamente');
    }

    /**
     * Maneja el click en un botón de voto
     */
    function handleVoteClick(e) {
        e.preventDefault();
        e.stopPropagation();

        console.log('[Voting] Click en botón de voto detectado');

        const btn = e.currentTarget;
        console.log('[Voting] Botón clickeado:', btn);

        // Buscar contenedor (standard o premium)
        const container = btn.closest('.chollo-voting, .chollo-voting-premium');
        console.log('[Voting] Contenedor encontrado:', container);

        if (!container) {
            console.error('[Voting] ERROR: No se encontró el contenedor de votación');
            return;
        }

        const cholloId = container.dataset.cholloId;
        console.log('[Voting] Chollo ID:', cholloId);

        if (!cholloId) {
            console.error('[Voting] ERROR: No se encontró data-chollo-id en el contenedor');
            console.log('[Voting] Dataset del contenedor:', container.dataset);
            return;
        }

        // Determinar tipo de voto basado en clases
        let tipo = '';
        if (btn.classList.contains('vote-up') || btn.classList.contains('vote-up-premium')) {
            tipo = 'positivo';
        } else if (btn.classList.contains('vote-down') || btn.classList.contains('vote-down-premium')) {
            tipo = 'negativo';
        }

        console.log('[Voting] Tipo de voto:', tipo);

        if (!isAuthenticated) {
            console.log('[Voting] Usuario no autenticado, mostrando prompt de login');
            showLoginPrompt();
            return;
        }

        if (btn.classList.contains('loading')) {
            console.log('[Voting] Botón ya está procesando, ignorando click');
            return; // Ya está procesando
        }

        console.log('[Voting] Enviando voto al servidor...');
        votarChollo(cholloId, tipo, container);
    }

    /**
     * Envía el voto al servidor
     */
    function votarChollo(cholloId, tipo, container) {
        // Selectores compatibles con ambos estilos
        const voteUpBtn = container.querySelector('.vote-up, .vote-up-premium');
        const voteDownBtn = container.querySelector('.vote-down, .vote-down-premium');

        // Marcar como loading
        if (voteUpBtn) voteUpBtn.classList.add('loading');
        if (voteDownBtn) voteDownBtn.classList.add('loading');

        fetch('/api/chollos_votos.php?action=votar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `chollo_id=${cholloId}&tipo=${tipo}`
        })
            .then(response => response.json())
            .then(data => {
                console.log('[Voting] Respuesta del servidor:', data);
                if (data.success) {
                    // Actualizar UI
                    updateVotingUI(container, data.voto_actual, data.temperatura);

                    // Animación de éxito
                    animateVote(container, tipo);
                } else {
                    console.error('[Voting] Error:', data.error);
                    showNotification('Error al votar: ' + (data.error || 'Desconocido'), 'error');
                }
            })
            .catch(error => {
                console.error('[Voting] Error de red:', error);
                showNotification('Error de conexión', 'error');
            })
            .finally(() => {
                if (voteUpBtn) voteUpBtn.classList.remove('loading');
                if (voteDownBtn) voteDownBtn.classList.remove('loading');
            });
    }

    /**
     * Actualiza la UI de votación
     */
    function updateVotingUI(container, votoActual, temperatura) {
        const voteUpBtn = container.querySelector('.vote-up, .vote-up-premium');
        const voteDownBtn = container.querySelector('.vote-down, .vote-down-premium');

        // Buscar display de temperatura (soporta standard y premium)
        const tempDisplay = container.querySelector('.temperature, .temperature-display, .temp-premium');

        // Remover estados activos
        if (voteUpBtn) voteUpBtn.classList.remove('active');
        if (voteDownBtn) voteDownBtn.classList.remove('active');

        // Aplicar nuevo estado
        if (votoActual === 'positivo' && voteUpBtn) {
            voteUpBtn.classList.add('active');
        } else if (votoActual === 'negativo' && voteDownBtn) {
            voteDownBtn.classList.add('active');
        }

        // Actualizar temperatura
        if (tempDisplay) {
            tempDisplay.textContent = temperatura + '°';

            // Lógica específica para estilo premium
            if (tempDisplay.classList.contains('temp-premium')) {
                // Actualizar color inline
                if (temperatura >= 50) {
                    tempDisplay.style.color = '#ff5252';
                } else if (temperatura < 0) {
                    tempDisplay.style.color = '#81d4fa';
                } else {
                    tempDisplay.style.color = '#ffffff'; // Blanco/Gris claro para neutral
                }
            } else {
                // Lógica para estilo standard (usando clases)
                tempDisplay.classList.remove('hot', 'cold', 'very-hot');
                if (temperatura >= 100) {
                    tempDisplay.classList.add('very-hot');
                } else if (temperatura >= 50) {
                    tempDisplay.classList.add('hot');
                } else if (temperatura < 0) {
                    tempDisplay.classList.add('cold');
                }

                // Actualizar color inline si existe
                if (tempDisplay.style.color !== undefined) {
                    if (temperatura >= 50) {
                        tempDisplay.style.color = '#E30613';
                    } else if (temperatura < 0) {
                        tempDisplay.style.color = '#64b5f6';
                    } else {
                        tempDisplay.style.color = '#666';
                    }
                }
            }
        }
    }

    /**
     * Anima el voto
     */
    function animateVote(container, tipo) {
        const isPremium = container.classList.contains('chollo-voting-premium');
        const selector = tipo === 'positivo'
            ? (isPremium ? '.vote-up-premium' : '.vote-up')
            : (isPremium ? '.vote-down-premium' : '.vote-down');

        const btn = container.querySelector(selector);

        if (!btn) return;

        // Crear partícula de animación
        const particle = document.createElement('div');
        particle.className = 'vote-particle';
        particle.innerHTML = tipo === 'positivo' ? '<i class="fas fa-fire"></i>' : '<i class="fas fa-snowflake"></i>';

        // Color según tipo
        const color = tipo === 'positivo' ? '#E30613' : '#64b5f6';
        const colorPremium = tipo === 'positivo' ? '#ff5252' : '#81d4fa';

        particle.style.cssText = `
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-size: 20px;
            color: ${isPremium ? colorPremium : color};
            pointer-events: none;
            animation: voteParticle 0.6s ease-out forwards;
            z-index: 1000;
        `;

        btn.style.position = 'relative';
        btn.appendChild(particle);

        setTimeout(() => particle.remove(), 600);
    }

    /**
     * Carga los votos del usuario
     */
    function loadUserVotes() {
        document.querySelectorAll('.chollo-voting').forEach(container => {
            const cholloId = container.dataset.cholloId;
            if (!cholloId) return;

            fetch(`/api/chollos_votos.php?action=get_voto&chollo_id=${cholloId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.voto) {
                        const voteUpBtn = container.querySelector('.vote-up');
                        const voteDownBtn = container.querySelector('.vote-down');

                        if (data.voto === 'positivo') {
                            voteUpBtn.classList.add('active');
                        } else if (data.voto === 'negativo') {
                            voteDownBtn.classList.add('active');
                        }
                    }
                })
                .catch(error => console.error('[Voting] Error cargando voto:', error));
        });
    }

    /**
     * Muestra prompt de login
     */
    function showLoginPrompt() {
        // Guardar URL actual para volver después del login
        try {
            localStorage.setItem('redirectAfterLogin', window.location.href);
        } catch (e) {
            console.error('[Voting] Error saving redirect url:', e);
        }

        if (typeof openLoginModalWithRedirect === 'function') {
            openLoginModalWithRedirect(window.location.href);
        } else if (typeof showLoginModal === 'function') {
            showLoginModal();
        } else {
            showNotification('Debes iniciar sesión para votar', 'info');
            setTimeout(() => {
                window.location.href = '/?login=1';
            }, 1500);
        }
    }

    /**
     * Muestra una notificación
     */
    function showNotification(message, type = 'info') {
        // Crear notificación simple
        const notification = document.createElement('div');
        notification.className = `vote-notification vote-notification-${type}`;
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

    /**
     * Carga el widget de chollos más calientes
     */
    function loadHotDealsWidget() {
        const widget = document.getElementById('hot-deals-widget');
        if (!widget) return;

        fetch('/api/chollos_votos.php?action=get_mas_calientes&limite=5')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.chollos.length > 0) {
                    renderHotDeals(widget, data.chollos);
                } else {
                    widget.innerHTML = '<p style="text-align: center; color: #999;">No hay chollos calientes disponibles</p>';
                }
            })
            .catch(error => {
                console.error('[Voting] Error cargando chollos calientes:', error);
                widget.innerHTML = '<p style="text-align: center; color: #999;">Error al cargar chollos</p>';
            });
    }

    /**
     * Renderiza los chollos más calientes
     */
    function renderHotDeals(container, chollos) {
        const html = chollos.map(chollo => {
            const categoria = Array.isArray(chollo.categoria) ? chollo.categoria[0] : chollo.categoria;
            const url = `/chollos/${categoria}/${chollo.id}`;
            const imagen = chollo.imagen || 'https://via.placeholder.com/80x80?text=Chollo';
            const precio = chollo.precio_descuento ? `${parseFloat(chollo.precio_descuento).toFixed(2)}€` : '';

            return `
                <a href="${url}" class="hot-deal-item">
                    <div class="hot-deal-image">
                        <img src="${imagen}" alt="${escapeHtml(chollo.titulo)}">
                    </div>
                    <div class="hot-deal-content">
                        <div class="hot-deal-title">${escapeHtml(chollo.titulo)}</div>
                        <div class="hot-deal-meta">
                            ${precio ? `<span class="hot-deal-price">${precio}</span>` : ''}
                            <span class="hot-deal-temperature">
                                <i class="fas fa-fire"></i>
                                ${chollo.temperatura}°
                            </span>
                        </div>
                    </div>
                </a>
            `;
        }).join('');

        container.innerHTML = html;
    }

    /**
     * Escapa HTML para prevenir XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Agregar estilos de animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes voteParticle {
            0% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -150%) scale(1.5);
            }
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
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
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initVoting();
            loadHotDealsWidget();
        });
    } else {
        initVoting();
        loadHotDealsWidget();
    }

    // Exponer funciones globalmente si es necesario
    window.ChollosVoting = {
        init: initVoting,
        loadHotDeals: loadHotDealsWidget
    };

})();
