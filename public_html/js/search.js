// Funcionalidad de búsqueda AJAX para CodigoAmigo.com

$(document).ready(function() {
    // Configuración de búsqueda
    var searchTimeout;
    var minSearchLength = 2;
    var searchDelay = 300; // ms
    
    // Elementos de búsqueda
    var $searchHeader = $('.search-input-header');
    var $searchHero = $('.search-input-hero');
    var $btnSearchHeader = $('.search-header .btn-buscar');
    var $btnSearchHero = $('.btn-buscar');
    
    // Contenedor de resultados
    var $resultsContainer = $('.codes-grid');
    var $sectionTitle = $('.section-title');
    var $mainContent = $('.main-content');
    
    // Función para realizar búsqueda AJAX
    function performSearch(query) {
        if (!query || query.length < minSearchLength) {
            return;
        }
        
        // Mostrar indicador de carga
        showLoadingIndicator();
        
        // Realizar búsqueda AJAX
        $.ajax({
            url: '/api/search',
            method: 'GET',
            data: {
                q: query,
                page: 1
            },
            dataType: 'json',
            success: function(response) {
                displaySearchResults(response);
            },
            error: function(xhr, status, error) {
                console.error('Error en búsqueda:', error);
                showErrorMessage('Error al realizar la búsqueda. Inténtalo de nuevo.');
            }
        });
    }
    
    // Función para mostrar resultados de búsqueda
    function displaySearchResults(data) {
        var html = '';
        
        if (data.codes && data.codes.length > 0) {
            // Mostrar resultados
            $sectionTitle.text('Resultados de búsqueda (' + data.total + ' códigos encontrados)');
            
            html += '<div class="codes-grid">';
            data.codes.forEach(function(code) {
                html += generateCodeCard(code);
            });
            html += '</div>';
            
            // Agregar paginación si es necesario
            if (data.pagination) {
                html += generatePagination(data.pagination);
            }
        } else {
            // No hay resultados
            $sectionTitle.text('Resultados de búsqueda');
            html = '<div class="no-results">';
            html += '<i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; color: #FF6B35;"></i>';
            html += '<h3>No se encontraron códigos</h3>';
            html += '<p>Intenta con otros términos de búsqueda</p>';
            html += '</div>';
        }
        
        // Actualizar el contenido principal
        $mainContent.find('.codes-section').html('<h2 class="section-title">' + $sectionTitle.text() + '</h2>' + html);
    }
    
    // Función para generar tarjeta de código
    function generateCodeCard(code) {
        var html = '<div class="code-card" data-code-id="' + code._id + '">';
        html += '<div class="code-brand">' + escapeHtml(code.marca || 'Marca desconocida') + '</div>';
        html += '<div class="code-description">' + escapeHtml(code.descripcion || 'Descripción no disponible') + '</div>';
        
        // Información adicional
        if (code.num_beneficio > 0 || code.num_valoraciones > 0) {
            html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-size: 0.9rem; color: #ccc;">';
            
            if (code.num_beneficio > 0) {
                html += '<span><i class="fas fa-euro-sign"></i> ' + code.num_beneficio + ' beneficio</span>';
            }
            
            if (code.num_valoraciones > 0) {
                html += '<span><i class="fas fa-star"></i> ' + code.num_valoraciones + ' valoraciones</span>';
            }
            
            html += '</div>';
        }
        
        // Botón de acción
        html += '<button class="code-button" onclick="viewCode(\'' + code._id + '\')">';
        html += '<i class="fas fa-eye"></i> Ver Código';
        html += '</button>';
        
        html += '</div>';
        return html;
    }
    
    // Función para generar paginación
    function generatePagination(pagination) {
        var html = '<div class="pagination-modern">';
        html += '<div class="pagination-info">';
        html += 'Mostrando del ' + pagination.start + ' al ' + pagination.end + ' de un total de <strong>' + pagination.total + ' Códigos Amigo</strong>';
        html += '</div>';
        
        html += '<div class="pagination-controls">';
        
        // Botón anterior
        if (pagination.currentPage > 1) {
            html += '<a href="?page=' + (pagination.currentPage - 1) + '" class="pagination-btn"><i class="fas fa-chevron-left"></i> Anterior</a>';
        }
        
        // Números de página
        for (var i = pagination.startPage; i <= pagination.endPage; i++) {
            var activeClass = (i === pagination.currentPage) ? ' active' : '';
            html += '<a href="?page=' + i + '" class="pagination-btn' + activeClass + '">' + i + '</a>';
        }
        
        // Botón siguiente
        if (pagination.currentPage < pagination.totalPages) {
            html += '<a href="?page=' + (pagination.currentPage + 1) + '" class="pagination-btn">Siguiente <i class="fas fa-chevron-right"></i></a>';
        }
        
        html += '</div>';
        html += '</div>';
        
        return html;
    }
    
    // Función para mostrar indicador de carga
    function showLoadingIndicator() {
        $resultsContainer.html('<div class="loading-indicator"><i class="fas fa-spinner fa-spin"></i> Buscando códigos...</div>');
    }
    
    // Función para mostrar mensaje de error
    function showErrorMessage(message) {
        $resultsContainer.html('<div class="error-message"><i class="fas fa-exclamation-triangle"></i> ' + message + '</div>');
    }
    
    // Función para escapar HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Event listeners para búsqueda en tiempo real
    $searchHeader.on('input', function() {
        var query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length >= minSearchLength) {
            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, searchDelay);
        } else if (query.length === 0) {
            // Si el campo está vacío, cargar códigos por defecto
            loadDefaultCodes();
        }
    });
    
    $searchHero.on('input', function() {
        var query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length >= minSearchLength) {
            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, searchDelay);
        } else if (query.length === 0) {
            // Si el campo está vacío, cargar códigos por defecto
            loadDefaultCodes();
        }
    });
    
    // Event listeners para botones de búsqueda
    $btnSearchHeader.on('click', function(e) {
        e.preventDefault();
        var query = $searchHeader.val().trim();
        if (query) {
            performSearch(query);
        }
    });
    
    $btnSearchHero.on('click', function(e) {
        e.preventDefault();
        var query = $searchHero.val().trim();
        if (query) {
            performSearch(query);
        }
    });
    
    // Función para cargar códigos por defecto
    function loadDefaultCodes() {
        // Recargar la página para mostrar códigos por defecto
        window.location.reload();
    }
    
    // Función para ver código (llamada desde las tarjetas)
    window.viewCode = function(codeId) {
        // Implementar funcionalidad para ver código
        console.log('Ver código:', codeId);
        // Aquí puedes abrir un modal o redirigir a la página del código
        alert('Ver código: ' + codeId + '\n\nEsta funcionalidad se implementará próximamente.');
    };
    
    // Sincronizar búsquedas entre header y hero
    $searchHeader.on('input', function() {
        $searchHero.val($(this).val());
    });
    
    $searchHero.on('input', function() {
        $searchHeader.val($(this).val());
    });
});

// CSS adicional para indicadores de carga y mensajes
$('<style>')
    .prop('type', 'text/css')
    .html(`
        .loading-indicator {
            text-align: center;
            padding: 3rem;
            color: #ccc;
            font-size: 1.1rem;
        }
        
        .loading-indicator i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #FF6B35;
        }
        
        .error-message {
            text-align: center;
            padding: 3rem;
            color: #ff6b6b;
            font-size: 1.1rem;
        }
        
        .error-message i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            color: #ccc;
        }
        
        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #FF6B35;
        }
        
        .search-input-header:focus,
        .search-input-hero:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
    `)
    .appendTo('head');
