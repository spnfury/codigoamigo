(function () {
    'use strict';

    // Search bar fija al scroll (position:fixed con placeholder anti-shift)
    const searchBar = document.getElementById('ma-search-sticky');
    const placeholder = document.getElementById('ma-search-placeholder');
    if (searchBar && placeholder) {
        let triggerY = 0;
        const recompute = () => {
            const wasFixed = searchBar.classList.contains('is-fixed');
            if (wasFixed) {
                triggerY = placeholder.getBoundingClientRect().top + window.scrollY;
            } else {
                triggerY = searchBar.getBoundingClientRect().top + window.scrollY;
            }
            placeholder.style.height = searchBar.offsetHeight + 'px';
        };
        const onScrollSticky = () => {
            if (window.scrollY >= triggerY) {
                if (!searchBar.classList.contains('is-fixed')) {
                    placeholder.classList.add('is-active');
                    searchBar.classList.add('is-fixed');
                }
            } else {
                if (searchBar.classList.contains('is-fixed')) {
                    searchBar.classList.remove('is-fixed');
                    placeholder.classList.remove('is-active');
                }
            }
        };
        recompute();
        window.addEventListener('resize', recompute, { passive: true });
        window.addEventListener('scroll', onScrollSticky, { passive: true });
        onScrollSticky();
    }

    const grid = document.getElementById('ma-codes-grid');
    const sentinel = document.getElementById('ma-sentinel');
    const loader = document.getElementById('ma-loader');
    const endBox = document.getElementById('ma-end');
    const endText = document.getElementById('ma-end-text');
    const counterText = document.getElementById('ma-counter-text');
    const searchInput = document.getElementById('textFilter');
    const clearBtn = document.getElementById('clearTextFilter');

    if (!grid) return;

    const estado = grid.dataset.estado || 'todos';
    let total = parseInt(grid.dataset.total || '0', 10);
    let pagina = parseInt(grid.dataset.pagina || '1', 10);
    let cargados = grid.querySelectorAll('.code-item').length;
    let loading = false;
    let hasMore = cargados < total;
    let controller = null;
    let currentQ = '';
    let observer = null;

    function showEnd(message) {
        if (loader) loader.style.display = 'none';
        if (endBox) endBox.style.display = 'block';
        if (endText && message) endText.textContent = message;
        if (observer) observer.disconnect();
    }
    function hideEnd() {
        if (endBox) endBox.style.display = 'none';
    }

    function updateCounter() {
        if (!counterText) return;
        const fmt = new Intl.NumberFormat('es-ES').format(total);
        const fmtCargados = new Intl.NumberFormat('es-ES').format(cargados);
        if (total === 0) {
            counterText.textContent = currentQ
                ? `Sin resultados para "${currentQ}"`
                : `0 códigos`;
        } else {
            counterText.textContent = cargados >= total
                ? `${fmt} ${currentQ ? 'resultados' : 'códigos en total'}`
                : `Mostrando ${fmtCargados} de ${fmt}`;
        }
    }

    function buildUrl(page) {
        const params = new URLSearchParams();
        params.set('estado', estado);
        params.set('p', String(page));
        if (currentQ) params.set('q', currentQ);
        return `/ajax/mis_anuncios_pagina.php?${params.toString()}`;
    }

    async function fetchPage(page) {
        if (controller) controller.abort();
        controller = new AbortController();
        const res = await fetch(buildUrl(page), {
            signal: controller.signal,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Error');
        return data;
    }

    async function loadNext() {
        if (loading || !hasMore) return;
        loading = true;
        if (loader) {
            loader.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando más códigos…';
            loader.style.display = 'block';
        }
        try {
            const data = await fetchPage(pagina + 1);
            const tmp = document.createElement('div');
            tmp.innerHTML = data.html;
            const frag = document.createDocumentFragment();
            while (tmp.firstChild) frag.appendChild(tmp.firstChild);
            grid.appendChild(frag);

            pagina = data.page;
            cargados = data.cargados;
            total = data.total;
            hasMore = data.has_more;
            updateCounter();
            if (!hasMore) showEnd(currentQ ? 'Fin de resultados' : 'No hay más códigos');
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[mis-anuncios] loadNext', err);
            if (loader) loader.innerHTML = '<span style="color:#c00;">Error al cargar. <a href="javascript:;" id="ma-retry">Reintentar</a></span>';
            const retry = document.getElementById('ma-retry');
            if (retry) retry.addEventListener('click', () => { loading = false; loadNext(); });
            return;
        } finally {
            if (hasMore && loader) loader.style.display = 'none';
            loading = false;
        }
    }

    async function runSearch(q) {
        currentQ = q.trim();
        if (loader) {
            loader.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando…';
            loader.style.display = 'block';
        }
        hideEnd();
        try {
            const data = await fetchPage(1);
            grid.innerHTML = data.html;
            pagina = data.page;
            cargados = data.cargados;
            total = data.total;
            hasMore = data.has_more;
            updateCounter();
            if (!hasMore) showEnd(currentQ ? 'Fin de resultados' : 'No hay más códigos');
            else if (observer) {
                observer.disconnect();
                observer.observe(sentinel);
            }
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[mis-anuncios] search', err);
            if (loader) loader.innerHTML = '<span style="color:#c00;">Error al buscar</span>';
            return;
        } finally {
            if (hasMore && loader) loader.style.display = 'none';
        }
    }

    // Debounced input
    if (searchInput) {
        // Desactiva filtro DOM legacy: sobreescribe filterByText con server-side
        let debounceTimer = null;
        const onInput = (val) => {
            if (clearBtn) clearBtn.style.display = val.length > 0 ? 'block' : 'none';
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => runSearch(val), 250);
        };
        // Quitar el onkeyup legacy (filterByText) — usamos input listener nuevo
        searchInput.removeAttribute('onkeyup');
        searchInput.addEventListener('input', (e) => onInput(e.target.value));
        // Reemplaza global
        window.filterByText = function (val) { onInput(val); };
        window.clearTextFilter = function () {
            searchInput.value = '';
            onInput('');
        };
    }

    if (sentinel) {
        observer = new IntersectionObserver((entries) => {
            for (const entry of entries) if (entry.isIntersecting) loadNext();
        }, { rootMargin: '800px 0px' });
        if (hasMore) observer.observe(sentinel);
    }

    if (!hasMore) showEnd('No hay más códigos');
    updateCounter();
})();
