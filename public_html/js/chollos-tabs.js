/**
 * Manejo de tabs para widgets de chollos
 * Permite cambiar entre diferentes períodos de tiempo (24h/7d/30d)
 */

(function () {
    'use strict';

    /**
     * Inicializa los tabs de chollos
     */
    function initChollosTabs() {
        const tabContainers = document.querySelectorAll('.deals-column');

        tabContainers.forEach(container => {
            const tabs = container.querySelectorAll('.deal-tab');
            const contentArea = container.querySelector('.deals-content');
            const tipo = container.dataset.tipo; // 'calientes' o 'populares'
            const categoria = container.dataset.categoria || '';

            tabs.forEach(tab => {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();

                    // No hacer nada si ya está activo
                    if (this.classList.contains('active')) {
                        return;
                    }

                    const periodo = this.dataset.periodo;

                    // Actualizar tabs activos
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    // Cargar contenido
                    loadChollosContent(contentArea, tipo, periodo, categoria);
                });
            });
        });
    }

    /**
     * Carga el contenido de chollos vía AJAX
     */
    function loadChollosContent(contentArea, tipo, periodo, categoria) {
        // Mostrar loading
        contentArea.classList.add('loading');
        contentArea.innerHTML = '<div class="deals-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

        // Construir URL
        const params = new URLSearchParams({
            periodo: periodo,
            tipo: tipo,
            limite: 5
        });

        if (categoria) {
            params.append('categoria', categoria);
        }

        const url = `/api/get-chollos.php?${params.toString()}`;

        // Hacer petición
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.html) {
                    // Fade out
                    contentArea.style.opacity = '0';

                    setTimeout(() => {
                        contentArea.innerHTML = data.html;
                        contentArea.classList.remove('loading');

                        // Fade in
                        setTimeout(() => {
                            contentArea.style.opacity = '1';
                        }, 50);
                    }, 200);
                } else {
                    throw new Error('No se pudo cargar el contenido');
                }
            })
            .catch(error => {
                console.error('Error al cargar chollos:', error);
                contentArea.classList.remove('loading');
                contentArea.innerHTML = '<div class="deals-error"><i class="fas fa-exclamation-triangle"></i> Error al cargar los chollos</div>';
                contentArea.style.opacity = '1';
            });
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChollosTabs);
    } else {
        initChollosTabs();
    }

})();
