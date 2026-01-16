'use strict';

(function () {
    // Configuration
    const CONFIG = {
        SCROLL_THRESHOLD: 6, // Number of chollos to scroll past
        TIME_THRESHOLD: 120000, // 2 minutes in milliseconds
        STORAGE_KEY: 'telegram_modal_shown',
        TELEGRAM_URL: 'https://t.me/codigoamigo'
    };

    // State
    let visibleChollosCount = 0;
    let timeOnPage = 0;
    let timeInterval = null;
    let modalShown = false;

    // Check if modal was already shown
    function wasModalShown() {
        return localStorage.getItem(CONFIG.STORAGE_KEY) === 'true';
    }

    // Mark modal as shown
    function markModalAsShown() {
        localStorage.setItem(CONFIG.STORAGE_KEY, 'true');
        modalShown = true;
    }

    // Show modal
    function showTelegramModal() {
        if (modalShown || wasModalShown()) {
            return;
        }

        const overlay = document.getElementById('telegram-modal-overlay');
        if (!overlay) {
            return;
        }

        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        markModalAsShown();

        // Stop tracking when modal is shown
        if (timeInterval) {
            clearInterval(timeInterval);
        }
    }

    // Hide modal
    function hideTelegramModal() {
        const overlay = document.getElementById('telegram-modal-overlay');
        if (!overlay) {
            return;
        }

        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    // Track time on page
    function startTimeTracking() {
        timeInterval = setInterval(function () {
            timeOnPage += 1000; // Increment by 1 second

            if (timeOnPage >= CONFIG.TIME_THRESHOLD) {
                showTelegramModal();
            }
        }, 1000);
    }

    // Track scroll using IntersectionObserver
    function setupScrollTracking() {
        const cholloCards = document.querySelectorAll('.chollo-card');
        if (!cholloCards || cholloCards.length === 0) {
            return;
        }

        const seenCards = new Set();

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && !seenCards.has(entry.target)) {
                    seenCards.add(entry.target);
                    visibleChollosCount = seenCards.size;

                    if (visibleChollosCount >= CONFIG.SCROLL_THRESHOLD) {
                        showTelegramModal();
                    }
                }
            });
        }, {
            threshold: 0.5 // Card must be 50% visible
        });

        cholloCards.forEach(function (card) {
            observer.observe(card);
        });
    }

    // Setup event listeners
    function setupEventListeners() {
        const closeBtn = document.getElementById('telegram-modal-close');
        const dismissBtn = document.getElementById('telegram-modal-dismiss');
        const overlay = document.getElementById('telegram-modal-overlay');
        const joinBtn = document.getElementById('telegram-modal-btn');

        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                hideTelegramModal();
            });
        }

        if (dismissBtn) {
            dismissBtn.addEventListener('click', function (e) {
                e.preventDefault();
                hideTelegramModal();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    hideTelegramModal();
                }
            });
        }

        if (joinBtn) {
            joinBtn.addEventListener('click', function () {
                // Analytics or tracking can be added here
                hideTelegramModal();
            });
        }
    }

    // Initialize
    function init() {
        // Don't initialize if modal was already shown
        if (wasModalShown()) {
            return;
        }

        setupEventListeners();
        setupScrollTracking();
        startTimeTracking();
    }

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
