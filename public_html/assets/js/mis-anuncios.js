/**
 * JavaScript para la página de Mis Anuncios
 * Módulo principal para gestión de códigos de descuento
 */

// Variable global para rastrear si hay un modal abierto
let modalAbierto = false;

// Función global para cerrar todos los modales
function cerrarTodosLosModales() {
    // Resetear la variable de control
    modalAbierto = false;

    // Cerrar todos los modales (incluyendo el modal de estadísticas)
    // NOTA: No eliminar modales que están en el HTML del servidor (modal_recargar_saldo, modal_destacar_todos)
    const modales = document.querySelectorAll('[id*="Modal"], .modal, #estadisticasModal');
    modales.forEach(modal => {
        // No eliminar modales del servidor, solo ocultarlos
        if (modal.id === 'modal_recargar_saldo' || modal.id === 'modal_destacar_todos') {
            modal.style.display = 'none';
            modal.classList.remove('show');
            return;
        }
        if (modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
    });

    // También eliminar cualquier modal que pueda estar dentro de un iframe
    try {
        const iframes = document.querySelectorAll('iframe');
        iframes.forEach(iframe => {
            try {
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                const iframeModals = iframeDoc.querySelectorAll('[id*="Modal"], .modal');
                iframeModals.forEach(modal => {
                    if (modal.parentNode) {
                        modal.parentNode.removeChild(modal);
                    }
                });
            } catch (e) {
                // Ignorar errores de cross-origin
            }
        });
    } catch (e) {
        // Ignorar errores de cross-origin
    }

    document.body.style.overflow = 'auto';
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion-${tipo}`;
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${tipo === 'success' ? '#4CAF50' : tipo === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10001;
        font-weight: 500;
        animation: slideInRight 0.3s ease;
        max-width: 300px;
        word-wrap: break-word;
    `;

    notificacion.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <span>${mensaje}</span>
        </div>
    `;

    document.body.appendChild(notificacion);

    // Auto-remover después de 3 segundos
    setTimeout(() => {
        notificacion.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notificacion.parentNode) {
                notificacion.parentNode.removeChild(notificacion);
            }
        }, 300);
    }, 3000);
}

// Función para mostrar estadísticas en modal
function mostrarEstadisticas(codigoId, marca) {
    // Si ya hay un modal abierto, no abrir otro
    if (modalAbierto) {
        return;
    }

    // Verificar que el DOM esté listo
    if (document.readyState !== 'loading') {
        // Cerrar cualquier modal existente antes de abrir uno nuevo
        cerrarTodosLosModales();
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            cerrarTodosLosModales();
        });
    }

    // Verificar si ya existe un modal de estadísticas
    const modalExistente = document.getElementById('estadisticasModal');
    if (modalExistente) {
        modalExistente.remove();
    }

    // Marcar que hay un modal abierto
    modalAbierto = true;

    // Crear iframe para cargar las estadísticas
    const modal = document.createElement('div');
    modal.id = 'estadisticasModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    `;

    const iframe = document.createElement('iframe');
    iframe.src = `/estadisticas?codigo=${codigoId}`;
    iframe.style.cssText = `
        width: 100%;
        max-width: 800px;
        height: 90vh;
        border: none;
        border-radius: 12px;
        background: white;
    `;

    const closeButton = document.createElement('button');
    closeButton.innerHTML = '&times;';
    closeButton.style.cssText = `
        position: absolute;
        top: 20px;
        right: 20px;
        background: #E30613;
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
        cursor: pointer;
        z-index: 10001;
    `;

    closeButton.onclick = () => {
        modalAbierto = false;
        cerrarTodosLosModales();
    };

    modal.appendChild(iframe);
    modal.appendChild(closeButton);
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Cerrar modal al hacer click fuera del iframe
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modalAbierto = false;
            cerrarTodosLosModales();
        }
    });

    // Cerrar modal con tecla Escape
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            modalAbierto = false;
            cerrarTodosLosModales();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

// Función para copiar al portapapeles
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            mostrarNotificacion('Código copiado al portapapeles', 'success');
        }).catch(() => {
            fallbackCopyTextToClipboard(text);
        });
    } else {
        fallbackCopyTextToClipboard(text);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        mostrarNotificacion('Código copiado al portapapeles', 'success');
    } catch (err) {
        mostrarNotificacion('Error al copiar el código', 'error');
    }

    document.body.removeChild(textArea);
}

// Función para confirmar eliminación de código
function confirmarEliminarCodigo(codigoId, marcaNombre) {
    // Verificar que el DOM esté listo
    if (document.readyState !== 'loading') {
        crearModalEliminar();
    } else {
        document.addEventListener('DOMContentLoaded', crearModalEliminar);
    }

    function crearModalEliminar() {
        const modal = document.createElement('div');
        modal.id = 'modalEliminarCodigo';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        `;

        modal.innerHTML = `
            <div style="
                background: white;
                border-radius: 15px;
                padding: 30px;
                max-width: 500px;
                width: 100%;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            ">
                <div style="color: #dc3545; font-size: 3rem; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 style="color: #333; margin-bottom: 15px; font-size: 1.5rem;">
                    ¿Eliminar código?
                </h3>
                <p style="color: #666; margin-bottom: 25px; line-height: 1.5;">
                    ¿Estás seguro de que quieres eliminar el código de <strong>${marcaNombre}</strong>?<br>
                    <span style="color: #dc3545; font-weight: bold;">Esta acción no se puede deshacer.</span>
                </p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button id="cancelarEliminar" style="
                        background: #6c757d;
                        color: white;
                        border: none;
                        padding: 12px 25px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 600;
                        transition: all 0.3s ease;
                    ">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button id="confirmarEliminar" style="
                        background: #dc3545;
                        color: white;
                        border: none;
                        padding: 12px 25px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 600;
                        transition: all 0.3s ease;
                    ">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';

        // Event listeners
        document.getElementById('cancelarEliminar').onclick = () => {
            document.body.removeChild(modal);
            document.body.style.overflow = 'auto';
        };

        document.getElementById('confirmarEliminar').onclick = () => {
            eliminarCodigo(codigoId, marcaNombre);
            document.body.removeChild(modal);
            document.body.style.overflow = 'auto';
        };

        // Cerrar modal al hacer click fuera
        modal.onclick = (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
                document.body.style.overflow = 'auto';
            }
        };
    }
}

// Función para mostrar modal de carga
function mostrarModalCargando(mensaje) {
    const modal = document.createElement('div');
    modal.id = 'modalCargando';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    `;

    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 100%;
        ">
            <div style="color: #E30613; font-size: 3rem; margin-bottom: 20px; animation: spin 1s linear infinite;">
                <i class="fas fa-spinner"></i>
            </div>
            <h3 style="color: #333; margin: 0 0 10px 0; font-size: 1.3rem; font-weight: 600;">
                ${mensaje}
            </h3>
            <p style="color: #666; margin: 0; font-size: 1rem;">
                Por favor, espera un momento...
            </p>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    return modal;
}

// Función para eliminar el código via AJAX (sin recarga de página)
function eliminarCodigo(codigoId, marcaNombre) {
    var loadingModal = mostrarModalCargando('Eliminando código...');

    fetch('/ajax/eliminar_codigo.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'codigo_id=' + encodeURIComponent(codigoId)
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        return response.text();
    })
    .then(function(text) {
        var data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            console.error('Respuesta no JSON:', text.substring(0, 500));
            throw new Error('Respuesta no válida del servidor');
        }
        if (loadingModal && loadingModal.parentNode) {
            loadingModal.parentNode.removeChild(loadingModal);
        }
        document.body.style.overflow = 'auto';

        if (data.success) {
            // Animar y remover la tarjeta del DOM
            var card = document.querySelector('.code-item[data-codigo-id="' + codigoId + '"]');
            if (card) {
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-60px) scale(0.95)';
                card.style.maxHeight = card.offsetHeight + 'px';
                card.style.overflow = 'hidden';
                setTimeout(function() {
                    card.style.maxHeight = '0';
                    card.style.padding = '0';
                    card.style.margin = '0';
                    card.style.border = 'none';
                }, 300);
                setTimeout(function() {
                    if (card.parentNode) card.parentNode.removeChild(card);
                    actualizarContadoresFiltros();
                }, 600);
            }
            mostrarNotificacion('Código de ' + marcaNombre + ' eliminado', 'success');
        } else {
            mostrarModalError('Error', data.error || 'No se pudo eliminar el código.');
        }
    })
    .catch(function(err) {
        console.error('Error eliminando código:', err);
        if (loadingModal && loadingModal.parentNode) {
            loadingModal.parentNode.removeChild(loadingModal);
        }
        document.body.style.overflow = 'auto';
        mostrarModalError('Error', 'Error de conexión. Inténtalo de nuevo.');
    });
}

// Función para toggle de descripción
function toggleDescripcion(codigoId) {
    const descFull = document.getElementById('desc-' + codigoId);
    const descShort = document.getElementById('desc-short-' + codigoId);
    const button = descShort.parentNode.querySelector('button');

    if (descFull.style.display === 'none') {
        descFull.style.display = 'block';
        descShort.style.display = 'none';
        button.textContent = 'Ver menos';
    } else {
        descFull.style.display = 'none';
        descShort.style.display = 'block';
        button.textContent = 'Ver más';
    }
}

// Función para actualizar el contador de códigos
function actualizarContadorCodigos() {
    const codigosVisibles = document.querySelectorAll('.code-card.code-item:not([style*="display: none"])').length;
    const titulo = document.getElementById('codesTitle');
    if (titulo) {
        titulo.textContent = `Tus códigos (${codigosVisibles})`;
    }
}

// Función para actualizar contadores en los botones de filtro tras AJAX
function actualizarContadoresFiltros() {
    var allCards = document.querySelectorAll('.code-card.code-item');
    var counts = { all: 0, activos: 0, caducados: 0, desactivados: 0, inactivos: 0, alta: 0, media: 0, baja: 0, destacados: 0 };

    allCards.forEach(function(card) {
        counts.all++;
        var estado = parseInt(card.getAttribute('data-estado') || '0');
        var vis = card.getAttribute('data-visibilidad') || '';
        var dest = card.getAttribute('data-destacado') || 'no';

        if (estado === 0) {
            counts.activos++;
            if (vis === 'alta') counts.alta++;
            if (vis === 'media') counts.media++;
            if (vis === 'baja') counts.baja++;
            if (dest === 'si') counts.destacados++;
        } else if (estado === -3) { counts.caducados++; }
        else if (estado === -2) { counts.desactivados++; }
        else if (estado === -1) { counts.inactivos++; }
    });

    // Actualizar texto de cada botón de filtro
    var map = {
        'all': 'Todos (' + counts.all + ')',
        'activos': '✅ Activos (' + counts.activos + ')',
        'caducados': '⏰ Caducados (' + counts.caducados + ')',
        'desactivados': '🚫 Desactivados (' + counts.desactivados + ')',
        'inactivos': '⏸️ Inactivos (' + counts.inactivos + ')',
        'alta': '⭐ Más visibles (' + counts.alta + ')',
        'media': '👁️ Visibles (' + counts.media + ')',
        'baja': '😴 Poco visibles (' + counts.baja + ')',
        'destacados': '🌟 Destacados (' + counts.destacados + ')'
    };

    Object.keys(map).forEach(function(key) {
        var btn = document.querySelector('.filter-button[data-visibility="' + key + '"]');
        if (btn) {
            // Preserve the icon element inside the button
            var icon = btn.querySelector('i');
            var iconHtml = icon ? icon.outerHTML + ' ' : '';
            btn.innerHTML = iconHtml + map[key];
        }
    });

    actualizarContadorCodigos();
}

// Función para mostrar modal de éxito
function mostrarModalExito(titulo, mensaje, autoCerrar = true) {
    // Cerrar cualquier modal existente
    cerrarTodosLosModales();

    const modal = document.createElement('div');
    modal.id = 'modalExito';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
        animation: fadeIn 0.3s ease-out;
    `;

    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: slideInUp 0.4s ease-out;
            position: relative;
        ">
            <button id="cerrarModalExito" style="
                position: absolute;
                top: 15px;
                right: 15px;
                background: #f0f0f0;
                border: none;
                border-radius: 50%;
                width: 35px;
                height: 35px;
                font-size: 1.2rem;
                cursor: pointer;
                transition: background 0.3s ease;
            ">&times;</button>

            <div style="color: #4CAF50; font-size: 4rem; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i>
            </div>

            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 2rem; font-weight: bold;">
                ${titulo}
            </h2>

            <p style="color: #666; font-size: 1.2rem; line-height: 1.5; margin: 0 0 30px 0;">
                ${mensaje}
            </p>

            <button id="aceptarModalExito" style="
                background: linear-gradient(135deg, #4CAF50, #45a049);
                color: white;
                border: none;
                padding: 15px 30px;
                border-radius: 12px;
                font-weight: bold;
                font-size: 1.1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
            ">
                <i class="fas fa-check" style="margin-right: 8px;"></i>
                ¡Perfecto!
            </button>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Event listeners
    const cerrarBtn = document.getElementById('cerrarModalExito');
    const aceptarBtn = document.getElementById('aceptarModalExito');

    const cerrarModal = () => {
        if (modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
        document.body.style.overflow = 'auto';
    };

    cerrarBtn.onclick = cerrarModal;
    aceptarBtn.onclick = cerrarModal;

    // Cerrar modal al hacer click fuera
    modal.onclick = (e) => {
        if (e.target === modal) {
            cerrarModal();
        }
    };

    // Cerrar modal con tecla Escape
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            cerrarModal();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);

    // Auto-cerrar después de 5 segundos si está habilitado
    if (autoCerrar) {
        setTimeout(cerrarModal, 5000);
    }
}

// Función para mostrar modal de error
function mostrarModalError(titulo, mensaje, autoCerrar = false) {
    // Cerrar cualquier modal existente
    cerrarTodosLosModales();

    const modal = document.createElement('div');
    modal.id = 'modalError';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
        animation: fadeIn 0.3s ease-out;
    `;

    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: slideInUp 0.4s ease-out;
            position: relative;
        ">
            <button id="cerrarModalError" style="
                position: absolute;
                top: 15px;
                right: 15px;
                background: #f0f0f0;
                border: none;
                border-radius: 50%;
                width: 35px;
                height: 35px;
                font-size: 1.2rem;
                cursor: pointer;
                transition: background 0.3s ease;
            ">&times;</button>

            <div style="color: #f44336; font-size: 4rem; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 2rem; font-weight: bold;">
                ${titulo}
            </h2>

            <p style="color: #666; font-size: 1.2rem; line-height: 1.5; margin: 0 0 30px 0;">
                ${mensaje}
            </p>

            <button id="aceptarModalError" style="
                background: linear-gradient(135deg, #f44336, #d32f2f);
                color: white;
                border: none;
                padding: 15px 30px;
                border-radius: 12px;
                font-weight: bold;
                font-size: 1.1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
            ">
                <i class="fas fa-check" style="margin-right: 8px;"></i>
                Entendido
            </button>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Event listeners
    const cerrarBtn = document.getElementById('cerrarModalError');
    const aceptarBtn = document.getElementById('aceptarModalError');

    const cerrarModal = () => {
        if (modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
        document.body.style.overflow = 'auto';
    };

    cerrarBtn.onclick = cerrarModal;
    aceptarBtn.onclick = cerrarModal;

    // Cerrar modal al hacer click fuera
    modal.onclick = (e) => {
        if (e.target === modal) {
            cerrarModal();
        }
    };

    // Cerrar modal con tecla Escape
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            cerrarModal();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);

    // Auto-cerrar después de 7 segundos si está habilitado (errores no se auto-cierran por defecto)
    if (autoCerrar) {
        setTimeout(cerrarModal, 7000);
    }
}

// Sistema de filtros y ordenación
window.sortCodes = function (sortBy) {
    const container = document.querySelector('.codes-grid');
    if (!container) {
        return;
    }

    const items = Array.from(container.querySelectorAll('.code-card.code-item'));

    items.sort((a, b) => {
        let valueA, valueB;

        switch (sortBy) {
            case 'fecha_desc':
                // Ordenar por fecha descendente (más recientes primero)
                valueA = a.getAttribute('data-fecha') || '';
                valueB = b.getAttribute('data-fecha') || '';

                // Si no hay fecha, usar el timestamp del ObjectId del elemento
                if (!valueA) {
                    const idA = a.querySelector('[data-codigo-id]')?.getAttribute('data-codigo-id') || '';
                    valueA = idA ? new Date(parseInt(idA.substring(0, 8), 16) * 1000).toISOString() : '1970-01-01';
                }
                if (!valueB) {
                    const idB = b.querySelector('[data-codigo-id]')?.getAttribute('data-codigo-id') || '';
                    valueB = idB ? new Date(parseInt(idB.substring(0, 8), 16) * 1000).toISOString() : '1970-01-01';
                }

                return new Date(valueB) - new Date(valueA);

            case 'fecha_asc':
                // Ordenar por fecha ascendente (más antiguos primero)
                valueA = a.getAttribute('data-fecha') || '';
                valueB = b.getAttribute('data-fecha') || '';

                if (!valueA) {
                    const idA = a.querySelector('[data-codigo-id]')?.getAttribute('data-codigo-id') || '';
                    valueA = idA ? new Date(parseInt(idA.substring(0, 8), 16) * 1000).toISOString() : '1970-01-01';
                }
                if (!valueB) {
                    const idB = b.querySelector('[data-codigo-id]')?.getAttribute('data-codigo-id') || '';
                    valueB = idB ? new Date(parseInt(idB.substring(0, 8), 16) * 1000).toISOString() : '1970-01-01';
                }

                return new Date(valueA) - new Date(valueB);

            case 'marca_asc':
                // Ordenar por marca A-Z
                valueA = a.getAttribute('data-marca') || '';
                valueB = b.getAttribute('data-marca') || '';
                return valueA.localeCompare(valueB);
            case 'marca_desc':
                // Ordenar por marca Z-A
                valueA = a.getAttribute('data-marca') || '';
                valueB = b.getAttribute('data-marca') || '';
                return valueB.localeCompare(valueA);
            case 'clicks_desc':
                // Ordenar por visitas descendente
                valueA = parseInt(a.getAttribute('data-clicks') || '0');
                valueB = parseInt(b.getAttribute('data-clicks') || '0');
                return valueB - valueA;
            case 'clicks_asc':
                // Ordenar por visitas ascendente
                valueA = parseInt(a.getAttribute('data-clicks') || '0');
                valueB = parseInt(b.getAttribute('data-clicks') || '0');
                return valueA - valueB;
            case 'beneficio_desc':
                // Ordenar por beneficio descendente
                valueA = parseFloat(a.getAttribute('data-beneficio') || '0');
                valueB = parseFloat(b.getAttribute('data-beneficio') || '0');
                return valueB - valueA;
            case 'beneficio_asc':
                // Ordenar por beneficio ascendente
                valueA = parseFloat(a.getAttribute('data-beneficio') || '0');
                valueB = parseFloat(b.getAttribute('data-beneficio') || '0');
                return valueA - valueB;
            default:
                return 0;
        }
    });

    // Reorganizar los elementos en el DOM
    items.forEach(item => container.appendChild(item));

    // Actualizar contador
    actualizarContadorCodigos();
};

// Función para mostrar modal de recargar saldo
function mostrarRecargarSaldo() {
    mostrarModalVanilla();
}

function cerrarModalSaldo() {
    const modal = document.getElementById('modal_recargar_saldo');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        // Restaurar estilos originales
        const dialog = modal.querySelector('.modal-dialog');
        if (dialog) {
            dialog.style.cssText = '';
        }
        const content = modal.querySelector('.modal-content');
        if (content) {
            content.style.cssText = '';
        }
    }
    document.body.style.overflow = 'auto';
    // Eliminar backdrop si existe
    const backdrop = document.getElementById('modal_saldo_backdrop');
    if (backdrop && backdrop.parentNode) {
        backdrop.parentNode.removeChild(backdrop);
    }
}

function mostrarModalVanilla() {
    const modal = document.getElementById('modal_recargar_saldo');
    if (!modal) return;

    // Crear backdrop oscuro
    let backdrop = document.getElementById('modal_saldo_backdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'modal_saldo_backdrop';
    }
    backdrop.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 10049;
    `;
    document.body.appendChild(backdrop);

    // Aplicar estilos inline al modal para sobreescribir Bootstrap
    modal.style.cssText = `
        display: flex !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        z-index: 10050 !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 20px !important;
        box-sizing: border-box !important;
        overflow-y: auto !important;
        background: transparent !important;
    `;
    modal.classList.add('show');

    // Estilizar el dialogo
    const dialog = modal.querySelector('.modal-dialog');
    if (dialog) {
        dialog.style.cssText = `
            position: relative;
            margin: auto;
            max-width: 440px;
            width: 100%;
            z-index: 10051;
            transform: none !important;
            opacity: 1 !important;
        `;
    }

    // Estilizar el contenido del modal
    const content = modal.querySelector('.modal-content');
    if (content) {
        content.style.cssText = `
            background: #1c1c2e !important;
            color: #fff !important;
            border-radius: 16px !important;
            border: none !important;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5) !important;
            max-width: 400px !important;
            margin: 0 auto !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            overflow: hidden !important;
        `;
    }

    document.body.style.overflow = 'hidden';

    // Cerrar con botón X
    const closeBtn = modal.querySelector('.close');
    if (closeBtn) {
        closeBtn.onclick = function (e) {
            e.preventDefault();
            cerrarModalSaldo();
        };
    }

    // Cerrar con botón Cancelar
    const cancelBtns = modal.querySelectorAll('[data-dismiss="modal"]');
    cancelBtns.forEach(function (btn) {
        btn.onclick = function (e) {
            e.preventDefault();
            cerrarModalSaldo();
        };
    });

    // Cerrar al hacer click en el backdrop
    backdrop.onclick = function () {
        cerrarModalSaldo();
    };

    // Cerrar al hacer click fuera del contenido
    modal.onclick = function (e) {
        if (e.target === modal) {
            cerrarModalSaldo();
        }
    };

    // Cerrar con tecla Escape
    document.addEventListener('keydown', function handleEsc(e) {
        if (e.key === 'Escape') {
            cerrarModalSaldo();
            document.removeEventListener('keydown', handleEsc);
        }
    });
}

// Variables globales para el sistema de saldo
var paqueteSeleccionado = null;

// Función para destacar todos los códigos (splash) - Lleva directamente al pago
function destacarTodosCodigos() {
    // Redirigir directamente al pago de Stripe para destacar todos los códigos
    stripe.redirectToCheckout({
        lineItems: [{ price: window.jsConfig.skuPatrocinadoSplash, quantity: 1 }],
        mode: 'payment',
        clientReferenceId: window.jsConfig.userId,
        billingAddressCollection: 'auto',
        successUrl: window.jsConfig.website + 'felicidades_destacar_todos?session_id={CHECKOUT_SESSION_ID}',
        cancelUrl: window.jsConfig.actualUrl,
    }).then(function (result) {
        if (result.error) {
            var displayError = document.getElementById('error-message');
            if (displayError) displayError.textContent = result.error.message;
        }
    });
}

// Seleccionar paquete de saldo y proceder directamente al pago
$(document).on('click', '.package-card', function () {
    $('.package-card').removeClass('selected');
    $(this).addClass('selected');
    paqueteSeleccionado = $(this).data('package');

    // Proceder directamente al pago
    if (paqueteSeleccionado) {
        var precio = paqueteSeleccionado;
        var saldo = $(this).data('amount');

        // Mostrar loading
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Procesando...');

        // Redirigir directamente a crear sesión de Stripe
        var url = '/public/crear_sesion_recarga.php?' +
            'paquete=' + encodeURIComponent(paqueteSeleccionado) +
            '&precio=' + encodeURIComponent(precio) +
            '&saldo=' + encodeURIComponent(saldo) +
            '&usuario_id=' + encodeURIComponent(window.jsConfig.userId);

        window.location.href = url;
    }
});

// Event listener para el botón "Destacar todos mis códigos"
$('#destacar_todos').click(function () {
    stripe.redirectToCheckout({
        lineItems: [{ price: window.jsConfig.skuPatrocinadoSplash, quantity: 1 }],
        mode: 'payment',
        clientReferenceId: window.jsConfig.userId,
        billingAddressCollection: 'auto',
        successUrl: window.jsConfig.website + 'felicidades_splash?session_id={CHECKOUT_SESSION_ID}&todos=1',
        cancelUrl: window.jsConfig.actualUrl,
    }).then(function (result) {
        if (result.error) {
            var displayError = document.getElementById('error-message');
            if (displayError) displayError.textContent = result.error.message;
        }
    });
});

// Confirmar destacar todos (splash)
$('#confirmar_destacar_todos').click(function () {
    // Redirigir a destacar todos con saldo
    window.location.href = '/destacar_con_saldo?tipo=splash';
});

// Función para obtener el ID de precio según el paquete
function getPriceId(paquete) {
    switch (paquete) {
        case '20': return 'price_20euros_2000'; // 20€ = 2000 céntimos
        case '40': return 'price_40euros_4000'; // 40€ = 4000 céntimos
        case '100': return 'price_100euros_10000'; // 100€ = 10000 céntimos
        default: return 'price_20euros_2000';
    }
}

// Función global para filtrar por visibilidad y estado
window.filterByVisibility = function (visibility) {
    // Actualizar URL sin recargar la página
    const url = new URL(window.location);
    url.searchParams.set('tab', visibility);
    window.history.replaceState({}, '', url);

    // Ocultar/Mostrar el botón de Reactivar Todos
    const reactivarBtn = document.getElementById('btn-reactivar-todos-container');
    if (reactivarBtn) {
        reactivarBtn.style.display = (visibility === 'caducados') ? 'block' : 'none';
    }

    // Actualizar botones activos
    document.querySelectorAll('.filter-button').forEach(button => {
        button.classList.remove('active');
    });

    // Marcar el botón seleccionado como activo
    const selectedBtn = document.querySelector(`[data-visibility="${visibility}"]`);
    if (selectedBtn) selectedBtn.classList.add('active');

    // Obtener todas las tarjetas de código
    var codeItems = document.querySelectorAll('.code-card.code-item');
    var visibleCount = 0;

    codeItems.forEach(function (item) {
        var itemVisibility = item.getAttribute('data-visibilidad');
        var itemDestacado = item.getAttribute('data-destacado');
        var itemEstado = parseInt(item.getAttribute('data-estado') || '0');
        var shouldShow = false;

        if (visibility === 'all') {
            shouldShow = true;
        }
        // Filtros por ESTADO
        else if (visibility === 'activos' && (itemEstado === 0)) {
            shouldShow = true;
        } else if (visibility === 'caducados' && itemEstado === -3) {
            shouldShow = true;
        } else if (visibility === 'desactivados' && itemEstado === -2) {
            shouldShow = true;
        } else if (visibility === 'inactivos' && itemEstado === -1) {
            shouldShow = true;
        }
        // Filtros por VISIBILIDAD (solo activos)
        else if (visibility === 'alta' && itemVisibility === 'alta' && itemEstado === 0) {
            shouldShow = true;
        } else if (visibility === 'media' && itemVisibility === 'media' && itemEstado === 0) {
            shouldShow = true;
        } else if (visibility === 'baja' && itemVisibility === 'baja' && itemEstado === 0) {
            shouldShow = true;
        } else if (visibility === 'destacados' && itemDestacado === 'si' && itemEstado === 0) {
            shouldShow = true;
        } else if (visibility === 'no_destacados' && itemDestacado === 'no' && itemEstado === 0) {
            shouldShow = true;
        }

        if (shouldShow) {
            item.style.display = 'block';
            item.style.animation = 'fadeIn 0.3s ease-in';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // Actualizar el título con el número de códigos visibles
    var titleElement = document.getElementById('codesTitle');
    if (titleElement) {
        if (visibility === 'all') {
            titleElement.textContent = 'Tus códigos (' + codeItems.length + ')';
        } else {
            titleElement.textContent = 'Tus códigos (' + visibleCount + ')';
        }
    }

    // Mostrar mensaje si no hay resultados
    var noResultsMessage = document.getElementById('no-results-message');
    if (visibleCount === 0 && visibility !== 'all') {
        if (!noResultsMessage) {
            noResultsMessage = document.createElement('div');
            noResultsMessage.id = 'no-results-message';
            noResultsMessage.className = 'no-results';

            var messageText = '';
            if (visibility === 'destacados') {
                messageText = 'No tienes códigos destacados';
            } else if (visibility === 'no_destacados') {
                messageText = 'Todos tus códigos están destacados';
            } else if (visibility === 'caducados') {
                messageText = 'No tienes códigos caducados';
            } else if (visibility === 'desactivados') {
                messageText = 'No tienes códigos desactivados';
            } else if (visibility === 'inactivos') {
                messageText = 'No tienes códigos inactivos';
            } else if (visibility === 'activos') {
                messageText = 'No tienes códigos activos';
            } else {
                messageText = `No hay códigos con ${visibility} visibilidad`;
            }

            noResultsMessage.innerHTML = `
                <i class="fas fa-search"></i>
                <h3>${messageText}</h3>
                <p>Intenta con otro filtro o publica más códigos.</p>
            `;
            document.querySelector('.codes-section').appendChild(noResultsMessage);
        }
        noResultsMessage.style.display = 'block';
    } else if (noResultsMessage) {
        noResultsMessage.style.display = 'none';
    }
};

// Función para reactivar un código caducado/desactivado (sin recarga)
function reactivarCodigo(codigoId, marcaNombre) {
    if (!confirm('¿Quieres reactivar el código de ' + marcaNombre + '? El código volverá a estar visible públicamente.')) {
        return;
    }

    var loadingModal = mostrarModalCargando('Reactivando código...');

    fetch('/ajax/reactivar_codigo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'codigo_id=' + encodeURIComponent(codigoId)
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (loadingModal && loadingModal.parentNode) {
            loadingModal.parentNode.removeChild(loadingModal);
        }
        document.body.style.overflow = 'auto';

        if (data.success) {
            // Actualizar la tarjeta en el DOM sin recargar
            var card = document.querySelector('.code-card[data-codigo-id="' + codigoId + '"]');
            if (card) {
                card.setAttribute('data-estado', '0');
                card.classList.remove('estado-caducado', 'estado-desactivado', 'estado-inactivo');
                // Quitar badge de estado
                var estadoBadge = card.querySelector('.estado-badge');
                if (estadoBadge) estadoBadge.remove();
                // Flash de confirmación visual
                card.style.transition = 'all 0.4s ease';
                card.style.boxShadow = '0 0 0 3px #4CAF50';
                setTimeout(function() { card.style.boxShadow = ''; }, 1500);
            }
            actualizarContadoresFiltros();
            mostrarNotificacion('Código de ' + marcaNombre + ' reactivado', 'success');
        } else {
            mostrarModalError('Error', data.error || 'No se pudo reactivar el código. Inténtalo de nuevo.');
        }
    })
    .catch(function(err) {
        if (loadingModal && loadingModal.parentNode) {
            loadingModal.parentNode.removeChild(loadingModal);
        }
        document.body.style.overflow = 'auto';
        mostrarModalError('Error', 'Error de conexión. Inténtalo de nuevo.');
    });
}

// Función para mostrar todos los códigos
function showAllCodes() {
    // Remover clase active de todas las pestañas
    $('.tab-button').removeClass('active');

    // Activar pestaña "TODOS"
    $('.tab-button[data-visibility="all"]').addClass('active');

    // Mostrar todos los códigos
    $('.code-card.code-item').show();

    // Actualizar contador de códigos visibles
    actualizarContadorCodigos();
}

// Función para filtrar por texto (marca, descripción, código)
window.filterByText = function (searchText) {
    const codeItems = document.querySelectorAll('.code-card.code-item');
    let visibleCount = 0;

    // Mostrar/ocultar botón de limpiar
    const clearButton = document.getElementById('clearTextFilter');
    if (clearButton) {
        clearButton.style.display = searchText.length > 0 ? 'block' : 'none';
    }

    codeItems.forEach(function (item) {
        const marca = item.getAttribute('data-marca') || '';
        const categoria = item.getAttribute('data-categoria') || '';

        // Buscar en el contenido visible de la tarjeta
        const descripcion = item.querySelector('p[id^="desc"]')?.textContent || '';
        const codigoText = item.querySelector('.codigo-text')?.textContent || '';

        // También buscar en el código completo usando el atributo title
        const codigoCompleto = item.querySelector('.codigo-text')?.getAttribute('title') || '';

        // Crear texto de búsqueda combinado
        const searchableText = (marca + ' ' + categoria + ' ' + descripcion + ' ' + codigoText + ' ' + codigoCompleto).toLowerCase();
        const searchLower = searchText.toLowerCase();

        const shouldShow = searchableText.includes(searchLower);

        if (shouldShow) {
            item.style.display = 'block';
            item.style.animation = 'fadeIn 0.3s ease-in';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // Actualizar contador
    const titleElement = document.getElementById('codesTitle');
    if (titleElement) {
        titleElement.textContent = 'Tus códigos (' + visibleCount + ')';
    }

    // Mostrar mensaje si no hay resultados
    const noResultsMessage = document.getElementById('no-results-message');
    if (visibleCount === 0 && searchText.length > 0) {
        if (!noResultsMessage) {
            const noResultsDiv = document.createElement('div');
            noResultsDiv.id = 'no-results-message';
            noResultsDiv.className = 'no-results';
            noResultsDiv.innerHTML = `
                <div style="text-align: center; padding: 40px; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin: 20px 0;">
                    <i class="fas fa-search" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                    <h3 style="color: #666; margin-bottom: 10px;">No se encontraron resultados</h3>
                    <p style="color: #999; margin: 0;">No hay códigos que coincidan con "${searchText}"</p>
                </div>
            `;
            document.querySelector('.codes-section').appendChild(noResultsDiv);
        }
        var createdMsg = document.getElementById('no-results-message');
        if (createdMsg) createdMsg.style.display = 'block';
    } else if (noResultsMessage) {
        noResultsMessage.style.display = 'none';
    }

    // Actualizar contador
    actualizarContadorCodigos();
};

// Función para limpiar el filtro de texto
window.clearTextFilter = function () {
    const textInput = document.getElementById('textFilter');
    if (textInput) {
        textInput.value = '';
        filterByText('');
    }
};

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    // Aplicar ordenamiento por defecto (más recientes)
    setTimeout(function () {
        sortCodes('fecha_desc');
    }, 100);

    // Actualizar contador inicial
    actualizarContadorCodigos();

    // Configurar filtros
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(button => {
        button.addEventListener('click', function () {
            const visibility = this.getAttribute('data-visibility');
            filterByVisibility(visibility);
        });
    });

    // Leer el fragmento de la URL y aplicar filtro si existe
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        setTimeout(function() { filterByVisibility(tab); }, 150);
    }

    // Configurar filtro de texto
    const textFilter = document.getElementById('textFilter');
    if (textFilter) {
        // Búsqueda en tiempo real mientras se escribe
        textFilter.addEventListener('input', function () {
            const searchText = this.value.trim();
            filterByText(searchText);
        });

        // Búsqueda al presionar Enter
        textFilter.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const searchText = this.value.trim();
                filterByText(searchText);
            }
        });
    }

    // Configurar botón de recargar saldo
    const btnRecargar = document.getElementById('btn-recargar-saldo');
    if (btnRecargar) {
        btnRecargar.addEventListener('click', function (e) {
            mostrarRecargarSaldo();
        });
    }
});

// Función para reactivar todos los códigos caducados (sin recarga)
function reactivarTodosLosCodigos() {
    if (!confirm('¿Estás seguro de que quieres reactivar TODOS tus códigos caducados? Volverán a estar visibles públicamente.')) {
        return;
    }

    var loadingModal = mostrarModalCargando('Reactivando todos los códigos...');

    fetch('/ajax/reactivar_todos_codigos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (loadingModal && loadingModal.parentNode) {
            loadingModal.parentNode.removeChild(loadingModal);
        }
        document.body.style.overflow = 'auto';

        if (data.success) {
            // Actualizar todas las tarjetas caducadas en el DOM
            var caducadas = document.querySelectorAll('.code-card[data-estado="-3"]');
            caducadas.forEach(function(card) {
                card.setAttribute('data-estado', '0');
                card.classList.remove('estado-caducado');
                var badge = card.querySelector('.estado-badge');
                if (badge) badge.remove();
                card.style.transition = 'all 0.4s ease';
                card.style.boxShadow = '0 0 0 3px #4CAF50';
                setTimeout(function() { card.style.boxShadow = ''; }, 1500);
            });
            actualizarContadoresFiltros();
            // Ocultar botón de reactivar todos
            var btnContainer = document.getElementById('btn-reactivar-todos-container');
            if (btnContainer) btnContainer.style.display = 'none';
            mostrarNotificacion('Se han reactivado ' + data.reactivados + ' códigos', 'success');
        } else {
            mostrarModalError('Error', data.error || 'No se pudieron reactivar los códigos. Inténtalo de nuevo.');
        }
    })
    .catch(function(err) {
        if (loadingModal && loadingModal.parentNode) {
            loadingModal.parentNode.removeChild(loadingModal);
        }
        document.body.style.overflow = 'auto';
        mostrarModalError('Error', 'Error de conexión. Inténtalo de nuevo.');
    });
}

// Función para renovar masivamente códigos destacados caducados
function renovarDestacadosMasivo(codigosIds) {
    if (!codigosIds || codigosIds.length === 0) return;
    
    if (!confirm('¿Quieres renovar ' + codigosIds.length + ' código(s) destacado(s) caducado(s)? Se descontará el importe total correspondiente de tu saldo.')) {
        return;
    }

    var loadingModal = mostrarModalCargando('Renovando códigos destacados...');

    fetch('/ajax/renovar_destacados_masivo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ codigos: codigosIds })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (loadingModal && loadingModal.parentNode) {
            loadingModal.parentNode.removeChild(loadingModal);
        }
        document.body.style.overflow = 'auto';

        if (data.success) {
            mostrarModalExito('¡Completado!', data.message || ('Se han renovado ' + data.renovados + ' códigos destacados.'), true);
            setTimeout(function() {
                window.location.reload();
            }, 2000);
        } else {
            // Si falta saldo, redirigir al checkout con un flag de recarga
            if (data.error_codigo === 'saldo_insuficiente') {
                mostrarModalError('Saldo Insuficiente', data.error + ' Por favor, recarga saldo antes de continuar.', true);
                setTimeout(function() {
                    if (typeof mostrarRecargarSaldo === 'function') {
                        mostrarRecargarSaldo();
                    } else {
                        window.location.href = data.redirect_url || '/public/destaca.php';
                    }
                }, 2000);
            } else {
                mostrarModalError('Error', data.error || 'No se pudieron renovar los códigos. Inténtalo de nuevo.');
            }
        }
    })
    .catch(function(err) {
        if (loadingModal && loadingModal.parentNode) {
            loadingModal.parentNode.removeChild(loadingModal);
        }
        document.body.style.overflow = 'auto';
        mostrarModalError('Error', 'Error de conexión. Inténtalo de nuevo.');
    });
}
