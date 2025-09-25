/**
 * JavaScript para filtros móviles - CodigoAmigo.com
 * Maneja el menú desplegable de filtros y búsqueda
 */

// Función global para toggle del brand sidebar
function toggleBrandSidebar() {
    console.log('toggleBrandSidebar llamada');
    
    const filtersSection = document.querySelector('.brand-sidebar .filters-section');
    const toggleBtn = document.querySelector('.brand-sidebar .filter-toggle-btn');
    
    console.log('filtersSection:', filtersSection);
    console.log('toggleBtn:', toggleBtn);
    
    if (filtersSection && toggleBtn) {
        console.log('Elementos encontrados, cambiando estado...');
        
        if (filtersSection.classList.contains('show')) {
            console.log('Cerrando sidebar...');
            filtersSection.classList.remove('show');
            toggleBtn.classList.remove('open');
        } else {
            console.log('Abriendo sidebar...');
            filtersSection.classList.add('show');
            toggleBtn.classList.add('open');
        }
        
        console.log('Estado actual - show:', filtersSection.classList.contains('show'));
        console.log('Estado actual - open:', toggleBtn.classList.contains('open'));
    } else {
        console.error('No se encontraron los elementos necesarios');
        console.log('filtersSection:', filtersSection);
        console.log('toggleBtn:', toggleBtn);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar filtros móviles
    initMobileFilters();
});

function initMobileFilters() {
    // Botón "Más" para filtros
    const filterMoreBtn = document.getElementById('filter-more-btn');
    const filterPanel = document.getElementById('filter-panel');
    
    // Botón de búsqueda
    const filterSearchBtn = document.getElementById('filter-search-btn');
    const searchPanel = document.getElementById('search-panel');
    
    // Botones de tipo de filtro
    const filterTypeBtns = document.querySelectorAll('.filter-type-btn');
    
    if (filterMoreBtn && filterPanel) {
        filterMoreBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleFilterPanel();
        });
    }
    
    if (filterSearchBtn && searchPanel) {
        filterSearchBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSearchPanel();
        });
    }
    
    // Manejar botones de tipo de filtro
    filterTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remover clase active de todos los botones
            filterTypeBtns.forEach(b => b.classList.remove('active'));
            // Agregar clase active al botón clickeado
            this.classList.add('active');
            
            // Obtener el tipo de filtro
            const filterType = this.getAttribute('data-type');
            
            // Aplicar filtro inmediatamente
            applyFilterType(filterType);
        });
    });
    
    // Cerrar paneles al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (filterPanel && !filterPanel.contains(e.target) && !filterMoreBtn.contains(e.target)) {
            closeFilterPanel();
        }
        
        if (searchPanel && !searchPanel.contains(e.target) && !filterSearchBtn.contains(e.target)) {
            closeSearchPanel();
        }
    });
    
    // Manejar formulario de búsqueda
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
}

function toggleFilterPanel() {
    const filterPanel = document.getElementById('filter-panel');
    const searchPanel = document.getElementById('search-panel');
    
    if (filterPanel) {
        // Cerrar panel de búsqueda si está abierto
        if (searchPanel) {
            closeSearchPanel();
        }
        
        if (filterPanel.classList.contains('show')) {
            closeFilterPanel();
        } else {
            openFilterPanel();
        }
    }
}

function toggleSearchPanel() {
    const filterPanel = document.getElementById('filter-panel');
    const searchPanel = document.getElementById('search-panel');
    
    if (searchPanel) {
        // Cerrar panel de filtros si está abierto
        if (filterPanel) {
            closeFilterPanel();
        }
        
        if (searchPanel.classList.contains('show')) {
            closeSearchPanel();
        } else {
            openSearchPanel();
        }
    }
}

function openFilterPanel() {
    const filterPanel = document.getElementById('filter-panel');
    if (filterPanel) {
        filterPanel.classList.add('show');
        // Prevenir scroll del body
        document.body.style.overflow = 'hidden';
    }
}

function closeFilterPanel() {
    const filterPanel = document.getElementById('filter-panel');
    if (filterPanel) {
        filterPanel.classList.remove('show');
        // Restaurar scroll del body
        document.body.style.overflow = 'auto';
    }
}

function openSearchPanel() {
    const searchPanel = document.getElementById('search-panel');
    if (searchPanel) {
        searchPanel.classList.add('show');
        // Prevenir scroll del body
        document.body.style.overflow = 'hidden';
        
        // Enfocar el input de búsqueda
        const searchInput = searchPanel.querySelector('.search-input');
        if (searchInput) {
            setTimeout(() => searchInput.focus(), 100);
        }
    }
}

function closeSearchPanel() {
    const searchPanel = document.getElementById('search-panel');
    if (searchPanel) {
        searchPanel.classList.remove('show');
        // Restaurar scroll del body
        document.body.style.overflow = 'auto';
    }
}

function applyFilterType(filterType) {
    // Aquí puedes implementar la lógica para aplicar el filtro
    console.log('Aplicando filtro tipo:', filterType);
    
    // Ejemplo: actualizar la URL o hacer una petición AJAX
    const url = new URL(window.location);
    url.searchParams.set('tipo', filterType);
    window.location.href = url.toString();
}

function performSearch() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        const query = searchInput.value.trim();
        if (query) {
            console.log('Buscando:', query);
            
            // Ejemplo: redirigir a página de búsqueda
            const url = new URL('/busqueda', window.location.origin);
            url.searchParams.set('q', query);
            window.location.href = url.toString();
        }
    }
}

// Funciones globales para compatibilidad
function applyFilters() {
    const form = document.querySelector('.filter-panel-content');
    if (form) {
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Recopilar datos del formulario
        for (let [key, value] of formData.entries()) {
            params.set(key, value);
        }
        
        // Aplicar filtros
        const url = new URL(window.location);
        for (let [key, value] of params.entries()) {
            url.searchParams.set(key, value);
        }
        
        window.location.href = url.toString();
    }
}

function clearFilters() {
    // Limpiar todos los filtros
    const url = new URL(window.location);
    url.searchParams.delete('fecha');
    url.searchParams.delete('tipo');
    url.searchParams.delete('q');
    
    window.location.href = url.toString();
}

// Manejar el menú hamburguesa del header
function toggleMenu() {
    const menu = document.getElementById("menu");
    const hamburger = document.querySelector(".hamburger");
    
    if (menu && hamburger) {
        if (menu.style.display === "flex") {
            menu.style.display = "none";
            hamburger.classList.remove("open");
            document.body.style.overflow = "auto";
        } else {
            menu.style.display = "flex";
            hamburger.classList.add("open");
            document.body.style.overflow = "hidden";
        }
    }
}


// Cerrar menú al hacer clic fuera
document.addEventListener('click', function(event) {
    const menu = document.getElementById("menu");
    const hamburger = document.querySelector(".hamburger");
    
    if (menu && hamburger && 
        !menu.contains(event.target) && 
        !hamburger.contains(event.target)) {
        menu.style.display = "none";
        hamburger.classList.remove("open");
        document.body.style.overflow = "auto";
    }
});
