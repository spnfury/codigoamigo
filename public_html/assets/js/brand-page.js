/**
 * Funcionalidad JavaScript para la página de marca
 * Incluye votación, animaciones y efectos interactivos
 */

class BrandPage {
    constructor() {
        this.init();
    }

    init() {
        this.setupVoting();
        this.setupAnimations();
        this.setupLazyLoading();
        this.setupTooltips();
        this.setupShareButtons();
    }

    /**
     * Configura el sistema de votación
     */
    setupVoting() {
        const voteButtons = document.querySelectorAll('.vote-btn');
        
        voteButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleVote(button);
            });
        });
    }

    /**
     * Maneja el proceso de votación
     */
    async handleVote(button) {
        const codigoId = button.getAttribute('data-codigo-id');
        const isPositive = button.classList.contains('positive');
        const voteCountElement = button.closest('.vote-section').querySelector('.vote-count');
        
        if (!codigoId) {
            console.error('ID de código no encontrado');
            return;
        }

        // Feedback visual inmediato
        this.animateVoteButton(button);
        
        try {
            // Deshabilitar botones durante la votación
            this.setVoteButtonsState(button, false);
            
            // Enviar voto al servidor
            const response = await this.sendVote(codigoId, isPositive);
            
            if (response.success) {
                // Actualizar contador
                this.updateVoteCount(voteCountElement, response.newCount);
                this.showVoteSuccess(button, isPositive);
            } else {
                this.showVoteError(button, response.message);
            }
        } catch (error) {
            console.error('Error en la votación:', error);
            this.showVoteError(button, 'Error de conexión');
        } finally {
            // Rehabilitar botones
            this.setVoteButtonsState(button, true);
        }
    }

    /**
     * Envía el voto al servidor
     */
    async sendVote(codigoId, isPositive) {
        const formData = new FormData();
        formData.append('action', 'vote_codigo');
        formData.append('codigo_id', codigoId);
        formData.append('tipo_voto', isPositive ? 'positivo' : 'negativo');
        
        const response = await fetch('/ajax_handler.php', {
            method: 'POST',
            body: formData
        });
        
        return await response.json();
    }

    /**
     * Anima el botón de votación
     */
    animateVoteButton(button) {
        button.style.transform = 'scale(0.9)';
        button.style.transition = 'transform 0.1s ease';
        
        setTimeout(() => {
            button.style.transform = 'scale(1.1)';
        }, 100);
        
        setTimeout(() => {
            button.style.transform = 'scale(1)';
        }, 200);
    }

    /**
     * Actualiza el contador de votos
     */
    updateVoteCount(element, newCount) {
        const currentCount = parseInt(element.textContent);
        const difference = newCount - currentCount;
        
        // Animación del contador
        element.style.transform = 'scale(1.2)';
        element.style.color = difference > 0 ? '#28a745' : '#dc3545';
        
        setTimeout(() => {
            element.textContent = newCount;
            element.style.transform = 'scale(1)';
            element.style.color = '';
        }, 150);
    }

    /**
     * Muestra mensaje de éxito en la votación
     */
    showVoteSuccess(button, isPositive) {
        const message = isPositive ? '¡Voto positivo enviado!' : '¡Voto negativo enviado!';
        this.showToast(message, 'success');
    }

    /**
     * Muestra error en la votación
     */
    showVoteError(button, message) {
        this.showToast(message || 'Error al enviar el voto', 'error');
    }

    /**
     * Controla el estado de los botones de votación
     */
    setVoteButtonsState(button, enabled) {
        const voteSection = button.closest('.vote-section');
        const allButtons = voteSection.querySelectorAll('.vote-btn');
        
        allButtons.forEach(btn => {
            btn.disabled = !enabled;
            btn.style.opacity = enabled ? '1' : '0.6';
        });
    }

    /**
     * Configura las animaciones de entrada
     */
    setupAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);
        
        // Observar elementos con animación
        document.querySelectorAll('.fade-in-up, .fade-in-left, .fade-in-right').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = el.classList.contains('fade-in-left') ? 'translateX(-30px)' : 
                               el.classList.contains('fade-in-right') ? 'translateX(30px)' : 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Añadir clase CSS para la animación
        const style = document.createElement('style');
        style.textContent = `
            .animate-in {
                opacity: 1 !important;
                transform: translateX(0) translateY(0) !important;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Configura la carga perezosa de imágenes
     */
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Configura tooltips para elementos interactivos
     */
    setupTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip);
            element.addEventListener('mouseleave', this.hideTooltip);
        });
    }

    /**
     * Muestra un tooltip
     */
    showTooltip(e) {
        const text = e.target.getAttribute('data-tooltip');
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
        
        setTimeout(() => {
            tooltip.style.opacity = '1';
        }, 10);
        
        e.target._tooltip = tooltip;
    }

    /**
     * Oculta un tooltip
     */
    hideTooltip(e) {
        if (e.target._tooltip) {
            e.target._tooltip.style.opacity = '0';
            setTimeout(() => {
                if (e.target._tooltip && e.target._tooltip.parentNode) {
                    e.target._tooltip.parentNode.removeChild(e.target._tooltip);
                }
            }, 300);
        }
    }

    /**
     * Configura botones de compartir
     */
    setupShareButtons() {
        const shareButtons = document.querySelectorAll('.share-btn');
        
        shareButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleShare(button);
            });
        });
    }

    /**
     * Maneja el compartir en redes sociales
     */
    handleShare(button) {
        const platform = button.getAttribute('data-platform');
        const url = window.location.href;
        const title = document.title;
        
        let shareUrl = '';
        
        switch (platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
                break;
            case 'telegram':
                shareUrl = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
                break;
            case 'copy':
                this.copyToClipboard(url);
                this.showToast('¡Enlace copiado al portapapeles!', 'success');
                return;
        }
        
        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    }

    /**
     * Copia texto al portapapeles
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
        } catch (err) {
            // Fallback para navegadores antiguos
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    }

    /**
     * Muestra un mensaje toast
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff'};
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Configura efectos de hover mejorados
     */
    setupHoverEffects() {
        const codeCards = document.querySelectorAll('.code-card');
        
        codeCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });
        });
    }

    /**
     * Configura el seguimiento de analytics
     */
    setupAnalytics() {
        // Seguimiento de interacciones
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-primary, .btn-secondary')) {
                this.trackEvent('button_click', {
                    button_text: e.target.textContent.trim(),
                    button_type: e.target.classList.contains('btn-primary') ? 'primary' : 'secondary'
                });
            }
        });
    }

    /**
     * Envía evento a analytics
     */
    trackEvent(eventName, parameters = {}) {
        // Aquí se integraría con Google Analytics, Facebook Pixel, etc.
        console.log('Event tracked:', eventName, parameters);
        
        // Ejemplo para Google Analytics 4
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, parameters);
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new BrandPage();
});

// Exportar para uso en otros módulos
window.BrandPage = BrandPage;



