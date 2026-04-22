'use strict';

(function () {
    var sliderStates = new Map();

    function toFloat(value, fallback) {
        var parsed = parseFloat(value);
        return Number.isNaN(parsed) ? fallback : parsed;
    }

    function getSlidesPerView(root) {
        var mobile = toFloat(root.getAttribute('data-slides-mobile'), 1);
        var tablet = toFloat(root.getAttribute('data-slides-tablet'), mobile);
        var desktop = toFloat(root.getAttribute('data-slides-desktop'), tablet);
        var width = window.innerWidth || document.documentElement.clientWidth;

        if (width <= 768) {
            return Math.max(1, mobile);
        }
        if (width <= 1024) {
            return Math.max(1, tablet);
        }
        return Math.max(1, desktop);
    }

    var SLIDE_TRANSITION = 'transform 0.35s ease';
    var scrollLockState = {
        count: 0,
        previousOverflow: '',
        lock: function () {
            if (this.count === 0) {
                this.previousOverflow = document.body.style.overflow || '';
                document.body.style.overflow = 'hidden';
            }
            this.count += 1;
        },
        unlock: function () {
            if (this.count === 0) {
                return;
            }
            this.count -= 1;
            if (this.count === 0) {
                document.body.style.overflow = this.previousOverflow;
            }
        }
    };

    function getMaxGroups(state) {
        return Math.max(1, Math.ceil(state.items.length / state.slidesPerView));
    }

    function updateDots(root, state) {
        var dots = root.querySelectorAll('[data-slider-dot]');
        dots.forEach(function (dot) {
            var dotIndex = parseInt(dot.getAttribute('data-slider-dot'), 10) || 0;
            dot.classList.toggle('active', dotIndex === state.index);
        });
    }

    function getBaseTranslate(state) {
        return -(state.index * (100 / state.slidesPerView));
    }

    function applySliderPosition(root, state) {
        var translate = getBaseTranslate(state);
        state.track.style.transform = 'translateX(' + translate + '%)';
        updateDots(root, state);
    }

    function moveSlider(root, direction) {
        var state = sliderStates.get(root);
        if (!state) {
            return;
        }

        var maxGroups = getMaxGroups(state);
        state.index = (state.index + direction) % maxGroups;
        if (state.index < 0) {
            state.index = maxGroups - 1;
        }

        state.track.style.transition = SLIDE_TRANSITION;
        applySliderPosition(root, state);
    }

    function goToSlider(root, index) {
        var state = sliderStates.get(root);
        if (!state) {
            return;
        }

        var maxGroups = getMaxGroups(state);
        state.index = Math.min(Math.max(index, 0), maxGroups - 1);
        state.track.style.transition = SLIDE_TRANSITION;
        applySliderPosition(root, state);
    }

    function initSlider(root) {
        if (sliderStates.has(root)) {
            return;
        }

        var track = root.querySelector('[data-slider-track]');
        if (!track) {
            return;
        }

        var items = Array.from(track.querySelectorAll('[data-slider-item]'));
        if (!items.length) {
            return;
        }

        var state = {
            track: track,
            items: items,
            index: 0,
            slidesPerView: getSlidesPerView(root)
        };

        sliderStates.set(root, state);
        state.track.style.transition = SLIDE_TRANSITION;
        state.track.style.touchAction = 'pan-y'; // Allow vertical scroll, handle horizontal
        state.track.style.display = 'flex'; // Ensure flex

        // Set initial widths
        items.forEach(function (item) {
            item.style.flex = '0 0 ' + (100 / state.slidesPerView) + '%';
            item.style.width = (100 / state.slidesPerView) + '%';
        });

        applySliderPosition(root, state);

        // Prevent accidental clicks when dragging
        // Prevent accidental clicks when dragging
        track.addEventListener('click', function (event) {
            // Permitir explícitamente navegación en marcas, incluso si hubo un ligero arrastre
            if (event.target.closest('.brand-link') ||
                event.target.closest('.brand-name-badge a') ||
                event.target.closest('.featured-button')) {
                return;
            }

            if (dragState.isDragging) {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);

        var controls = root.querySelectorAll('[data-slider-action]');
        controls.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                var action = button.getAttribute('data-slider-action');
                if (action === 'next') {
                    moveSlider(root, 1);
                } else {
                    moveSlider(root, -1);
                }
            });
        });

        var dots = root.querySelectorAll('[data-slider-dot]');
        dots.forEach(function (dot) {
            dot.addEventListener('click', function (event) {
                event.preventDefault();
                var targetIndex = parseInt(dot.getAttribute('data-slider-dot'), 10) || 0;
                goToSlider(root, targetIndex);
            });
        });

        var DRAG_THRESHOLD_PX = 20; // Reduced for better mobile response
        var AXIS_LOCK_THRESHOLD_PX = 8;
        var DRAG_ACTIVATE_PX = 12;
        var dragState = {
            active: false,
            pointerId: null,
            startX: 0,
            startY: 0,
            deltaX: 0,
            lockedAxis: null,
            baseTranslate: 0,
            rootWidth: 1,
            scrollLocked: false,
            isDragging: false
        };

        // Removed: Allow drag to start anywhere - threshold prevents accidental clicks

        function releasePointerCaptureIfNeeded() {
            if (!window.PointerEvent) {
                return;
            }
            if (dragState.pointerId === null) {
                return;
            }
            if (typeof track.releasePointerCapture === 'function') {
                try {
                    track.releasePointerCapture(dragState.pointerId);
                } catch (error) {
                    // ignore release errors
                }
            }
        }

        function resetDragState() {
            dragState.active = false;
            dragState.pointerId = null;
            dragState.startX = 0;
            dragState.startY = 0;
            dragState.deltaX = 0;
            dragState.lockedAxis = null;
            dragState.baseTranslate = 0;
            dragState.rootWidth = 1;
            if (dragState.scrollLocked) {
                scrollLockState.unlock();
                dragState.scrollLocked = false;
            }
            dragState.isDragging = false;
        }

        function startDrag(x, y, pointerId) {
            dragState.active = true;
            dragState.pointerId = pointerId || null;
            dragState.startX = x;
            dragState.startY = y;
            dragState.deltaX = 0;
            dragState.lockedAxis = null;
            dragState.baseTranslate = getBaseTranslate(state);
            dragState.isDragging = false;

            var bounds = root.getBoundingClientRect();
            dragState.rootWidth = Math.max(1, bounds.width || root.offsetWidth || 1);

            state.track.style.transition = 'none';
            state.track.style.transform = 'translateX(' + dragState.baseTranslate + '%)';
        }

        function updateDrag(x, y, originalEvent) {
            if (!dragState.active) {
                return;
            }

            var deltaX = x - dragState.startX;
            var deltaY = y - dragState.startY;

            if (!dragState.lockedAxis) {
                if (Math.abs(deltaX) >= AXIS_LOCK_THRESHOLD_PX || Math.abs(deltaY) >= AXIS_LOCK_THRESHOLD_PX) {
                    dragState.lockedAxis = Math.abs(deltaX) >= Math.abs(deltaY) ? 'x' : 'y';
                    if (dragState.lockedAxis === 'x' && !dragState.scrollLocked) {
                        scrollLockState.lock();
                        dragState.scrollLocked = true;
                    }
                }
            }

            if (dragState.lockedAxis === 'y') {
                releasePointerCaptureIfNeeded();
                state.track.style.transition = SLIDE_TRANSITION;
                state.track.style.transform = 'translateX(' + dragState.baseTranslate + '%)';
                resetDragState();
                return;
            }

            if (dragState.lockedAxis === 'x') {
                dragState.deltaX = deltaX;
                var deltaPercent = (dragState.deltaX / dragState.rootWidth) * 100;
                var translate = dragState.baseTranslate + deltaPercent;
                state.track.style.transform = 'translateX(' + translate + '%)';
                if (!dragState.isDragging && Math.abs(dragState.deltaX) >= DRAG_ACTIVATE_PX) {
                    dragState.isDragging = true;
                }
                if (dragState.isDragging && originalEvent && typeof originalEvent.preventDefault === 'function') {
                    originalEvent.preventDefault();
                }
            }
        }

        function finishDrag() {
            if (!dragState.active) {
                return;
            }

            var shouldSwipe = dragState.lockedAxis === 'x' && Math.abs(dragState.deltaX) >= DRAG_THRESHOLD_PX;
            var direction = dragState.deltaX < 0 ? 1 : -1;

            releasePointerCaptureIfNeeded();
            state.track.style.transition = SLIDE_TRANSITION;
            resetDragState();

            if (shouldSwipe) {
                moveSlider(root, direction);
            } else {
                applySliderPosition(root, state);
            }
        }

        if (window.PointerEvent) {
            track.addEventListener('pointerdown', function (event) {
                // Disable drag for mouse (desktop) completely
                if (event.pointerType === 'mouse') {
                    return;
                }
                if (event.button !== 0) {
                    return;
                }
                // Allow drag from anywhere - threshold prevents accidental activation
                startDrag(event.clientX, event.clientY, event.pointerId);
                if (typeof track.setPointerCapture === 'function') {
                    try {
                        track.setPointerCapture(event.pointerId);
                    } catch (error) {
                        // Ignore capture errors
                    }
                }
            });

            track.addEventListener('pointermove', function (event) {
                if (!dragState.active || dragState.pointerId !== event.pointerId) {
                    return;
                }
                updateDrag(event.clientX, event.clientY, event);
            });

            var handlePointerEnd = function (event) {
                if (!dragState.active || dragState.pointerId !== event.pointerId) {
                    return;
                }
                finishDrag();
            };

            track.addEventListener('pointerup', handlePointerEnd);
            track.addEventListener('pointercancel', handlePointerEnd);
            track.addEventListener('pointerleave', function (event) {
                if (!dragState.active || dragState.pointerId !== event.pointerId) {
                    return;
                }
                finishDrag();
            });
        } else {
            track.addEventListener('touchstart', function (event) {
                var touch = event.changedTouches && event.changedTouches[0];
                if (!touch) {
                    return;
                }
                if (isInteractiveTarget(event.target)) {
                    return;
                }
                startDrag(touch.clientX, touch.clientY);
            }, { passive: true });

            track.addEventListener('touchmove', function (event) {
                var touch = event.changedTouches && event.changedTouches[0];
                if (!touch) {
                    return;
                }
                updateDrag(touch.clientX, touch.clientY, event);
            }, { passive: false });

            var handleTouchEnd = function (event) {
                finishDrag();
            };

            track.addEventListener('touchend', handleTouchEnd, { passive: true });
            track.addEventListener('touchcancel', handleTouchEnd, { passive: true });

            /* Mouse events for dragging disabled on desktop to prevent click interference
            track.addEventListener('mousedown', function (event) {
                if (event.button !== 0) {
                    return;
                }
                if (isInteractiveTarget(event.target)) {
                    return;
                }
                startDrag(event.clientX, event.clientY);

                var handleMouseMove = function (moveEvent) {
                    updateDrag(moveEvent.clientX, moveEvent.clientY, moveEvent);
                };

                var handleMouseUp = function () {
                    document.removeEventListener('mousemove', handleMouseMove);
                    document.removeEventListener('mouseup', handleMouseUp);
                    finishDrag();
                };

                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
            });
            */
        }
    }

    function isInteractiveTarget(target) {
        return target.closest('a, button, input, select, textarea, label, .clickable');
    }

    function debounce(fn, delay) {
        var timeoutId;
        return function () {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(fn, delay);
        };
    }

    var handleResize = debounce(function () {
        sliderStates.forEach(function (state, root) {
            var newSlides = getSlidesPerView(root);
            if (newSlides !== state.slidesPerView) {
                state.slidesPerView = newSlides;
                if (state.index >= getMaxGroups(state)) {
                    state.index = 0;
                }
                // Update widths on resize
                state.items.forEach(function (item) {
                    item.style.flex = '0 0 ' + (100 / state.slidesPerView) + '%';
                    item.style.width = (100 / state.slidesPerView) + '%';
                });
                applySliderPosition(root, state);
            }
        });
    }, 150);

    window.addEventListener('resize', handleResize);

    function ensureModalElement() {
        var fallback = document.getElementById('loginModal');
        if (fallback) {
            return fallback;
        }

        var markup = '' +
            '<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">' +
            '  <div class="modal-dialog">' +
            '    <div class="modal-content">' +
            '      <div class="modal-header">' +
            '        <h5 class="modal-title" id="loginModalLabel">Iniciar Sesion / Registrarse</h5>' +
            '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
            '      </div>' +
            '      <div class="modal-body">' +
            '        <p>Para publicar codigos y ganar dinero, necesitas estar registrado.</p>' +
            '        <div class="d-grid gap-2">' +
            '          <a href="/registro.php" class="btn btn-primary">Crear Cuenta</a>' +
            '          <a href="/login.php" class="btn btn-outline-primary">Ya tengo cuenta</a>' +
            '        </div>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        document.body.insertAdjacentHTML('beforeend', markup);
        return document.getElementById('loginModal');
    }

    function showLoginModal() {
        var modalLogin = document.getElementById('modal_login');

        if (modalLogin) {
            // Intentar con jQuery modal primero
            if (window.jQuery && typeof window.jQuery(modalLogin).modal === 'function') {
                try {
                    window.jQuery(modalLogin).modal({
                        backdrop: 'static',
                        keyboard: true,
                        show: true
                    });
                    return;
                } catch (error) {
                    console.error('[showLoginModal] Error con jQuery modal:', error);
                }
            }

            // Intentar con Bootstrap 5 modal
            if (window.bootstrap && window.bootstrap.Modal) {
                try {
                    new window.bootstrap.Modal(modalLogin).show();
                    return;
                } catch (error) {
                    console.error('[showLoginModal] Error con Bootstrap 5 modal:', error);
                }
            }

            // Método manual robusto
            modalLogin.classList.add('show', 'in');
            modalLogin.style.cssText = 'display: block !important; opacity: 1 !important; z-index: 99999 !important; position: fixed !important;';
            modalLogin.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');

            var existingBackdrop = document.querySelector('.modal-backdrop');
            if (!existingBackdrop) {
                var backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade in show';
                backdrop.style.cssText = 'display: block !important; opacity: 0.5 !important; z-index: 99998 !important; position: fixed !important; top: 0; left: 0; width: 100%; height: 100%; background-color: #000;';
                document.body.appendChild(backdrop);

                backdrop.addEventListener('click', function () {
                    modalLogin.style.display = 'none';
                    modalLogin.classList.remove('show', 'in');
                    document.body.classList.remove('modal-open');
                    backdrop.remove();
                });
            }
            return;
        }

        var fallback = ensureModalElement();
        if (!fallback) {
            window.location.href = '/login.php';
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            new window.bootstrap.Modal(fallback).show();
            return;
        }

        fallback.classList.add('show');
        fallback.style.display = 'block';
        document.body.classList.add('modal-open');
    }

    // Solo definir showLoginModal si no existe (dar prioridad a la del header)
    if (typeof window.showLoginModal !== 'function') {
        window.showLoginModal = showLoginModal;
    }

    function closeMobileMenus() {
        var profileMenu = document.getElementById('mobile-profile-menu');
        var hamburgerMenu = document.getElementById('mobile-hamburger-menu');

        if (profileMenu) {
            profileMenu.classList.remove('show');
        }
        if (hamburgerMenu) {
            hamburgerMenu.classList.remove('show');
        }
        document.body.style.overflow = '';
    }

    window.closeMobileMenus = closeMobileMenus;

    function initMobileSearch() {
        // La búsqueda ahora es permanente en el header móvil, no necesita toggle de visibilidad
        var searchInput = document.getElementById('mobile-search-input-header');
        if (searchInput) {
            // Lógica adicional para el campo de búsqueda permanente si fuera necesaria
        }
    }

    function initMobileMenu() {
        var profileToggle = document.getElementById('profile-toggle');
        var profileMenu = document.getElementById('mobile-profile-menu');

        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', function (event) {
                event.preventDefault();

                var serverData = window.serverUserData || null;
                var isLoggedIn = Boolean(serverData && serverData.id);

                if (!isLoggedIn) {
                    var desktopLoginBtn = document.getElementById('btn-login');
                    if (desktopLoginBtn) {
                        desktopLoginBtn.click();
                        return;
                    }
                    showLoginModal();
                    return;
                }

                profileMenu.classList.add('show');
                document.body.style.overflow = 'hidden';
            });

            profileMenu.addEventListener('click', function (event) {
                if (event.target === profileMenu) {
                    profileMenu.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        }

        var menuToggle = document.getElementById('menu-toggle');
        var hamburgerMenu = document.getElementById('mobile-hamburger-menu');

        if (menuToggle && hamburgerMenu) {
            menuToggle.addEventListener('click', function (event) {
                event.preventDefault();
                hamburgerMenu.classList.add('show');
                document.body.style.overflow = 'hidden';
            });

            hamburgerMenu.addEventListener('click', function (event) {
                if (event.target === hamburgerMenu) {
                    hamburgerMenu.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        }
    }

    function buildCodeUrl(codeId, brandSlug) {
        if (!codeId) {
            return null;
        }

        var safeSlug = '';
        if (typeof brandSlug === 'string') {
            safeSlug = brandSlug.trim().toLowerCase();
        }

        if (!safeSlug) {
            safeSlug = 'codigo';
        }

        safeSlug = safeSlug
            .replace(/[^a-z0-9-]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');

        if (!safeSlug) {
            safeSlug = 'codigo';
        }

        return '/de-' + safeSlug + '?codigo=' + encodeURIComponent(codeId);
    }

    if (typeof window.viewCode !== 'function') {
        window.viewCode = function (codeId, brandSlug) {
            var targetUrl = buildCodeUrl(codeId, brandSlug);
            if (!targetUrl) {
                return;
            }
            window.location.href = targetUrl;
        };
    }

    function attachActionHandlers() {
        document.addEventListener('click', function (event) {
            var actionButton = event.target.closest('[data-action-button]');
            if (actionButton) {
                event.stopPropagation();
            }

            var actionTarget = event.target.closest('[data-action]');
            if (!actionTarget) {
                return;
            }

            var scope = actionTarget.getAttribute('data-action-scope');
            if (scope === 'card') {
                var interactiveChild = event.target.closest('a, button, input, textarea, select, [data-action-button]');
                if (interactiveChild && interactiveChild !== actionTarget) {
                    return;
                }
            }

            var actionType = actionTarget.getAttribute('data-action');
            var targetUrl = actionTarget.getAttribute('data-target');

            if (actionType === 'navigate' && targetUrl) {
                event.preventDefault();
                window.location.href = targetUrl;
                return;
            }

            if (actionType === 'require-login') {
                event.preventDefault();
                showLoginModal();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-slider-root]').forEach(initSlider);
        attachActionHandlers();
        initMobileMenu();
        initMobileSearch();
    });
})();

