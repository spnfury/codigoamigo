<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Ordenación</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="/assets/css/mis-anuncios.css">
</head>
<body>
    <div style="max-width: 800px; margin: 50px auto; padding: 20px;">
        <h1>Test Ordenación de Códigos</h1>

        <div style="margin-bottom: 30px;">
            <label for="sortSelect">Ordenar por:</label>
            <select id="sortSelect" onchange="sortCodes(this.value)" style="margin-left: 10px; padding: 8px;">
                <option value="fecha_desc" selected>Más recientes</option>
                <option value="fecha_asc">Más antiguos</option>
                <option value="marca_asc">Marca A-Z</option>
                <option value="marca_desc">Marca Z-A</option>
                <option value="clicks_desc">Más visitados</option>
                <option value="clicks_asc">Menos visitados</option>
                <option value="beneficio_desc">Mayor beneficio</option>
                <option value="beneficio_asc">Menor beneficio</option>
            </select>
        </div>

        <div id="codes-container" style="border: 1px solid #ccc; padding: 20px; min-height: 200px;">
            <!-- Tarjetas de prueba -->
            <div class="code-card code-item" data-visibilidad="alta" data-marca="Amazon" data-clicks="150" data-beneficio="20" data-fecha="2024-01-15" style="background: white; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;">
                <h3>Amazon</h3>
                <p>20€ de beneficio</p>
                <p>150 visitas</p>
                <p>Fecha: 15/01/2024</p>
            </div>

            <div class="code-card code-item" data-visibilidad="media" data-marca="Booking" data-clicks="80" data-beneficio="15" data-fecha="2024-01-10" style="background: white; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;">
                <h3>Booking</h3>
                <p>15€ de beneficio</p>
                <p>80 visitas</p>
                <p>Fecha: 10/01/2024</p>
            </div>

            <div class="code-card code-item" data-visibilidad="baja" data-marca="Zara" data-clicks="30" data-beneficio="10" data-fecha="2024-01-05" style="background: white; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;">
                <h3>Zara</h3>
                <p>10€ de beneficio</p>
                <p>30 visitas</p>
                <p>Fecha: 05/01/2024</p>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <h3>Debug Info:</h3>
            <div id="debug-info" style="background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;"></div>
        </div>
    </div>

    <script>
        // Función global para filtrar por visibilidad
        window.filterByVisibility = function(visibility) {
            console.log('Filtrando por visibilidad:', visibility);

            // Actualizar botones activos
            document.querySelectorAll('.filter-button').forEach(button => {
                button.classList.remove('active');
            });

            // Marcar el botón seleccionado como activo
            document.querySelector(`[data-visibility="${visibility}"]`).classList.add('active');

            // Obtener todas las tarjetas de código
            var codeItems = document.querySelectorAll('.code-card.code-item');
            var visibleCount = 0;

            codeItems.forEach(function(item) {
                var itemVisibility = item.getAttribute('data-visibilidad');
                var shouldShow = false;

                if (visibility === 'all') {
                    shouldShow = true;
                } else if (visibility === 'alta' && itemVisibility === 'alta') {
                    shouldShow = true;
                } else if (visibility === 'media' && itemVisibility === 'media') {
                    shouldShow = true;
                } else if (visibility === 'baja' && itemVisibility === 'baja') {
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

            console.log('Códigos visibles después del filtrado:', visibleCount);
        };

        // Sistema de filtros y ordenación
        window.sortCodes = function(sortBy) {
            console.log('Ordenando códigos por:', sortBy);

            const container = document.querySelector('#codes-container');
            if (!container) {
                console.error('Contenedor #codes-container no encontrado');
                return;
            }

            const items = Array.from(container.querySelectorAll('.code-card.code-item'));

            items.sort((a, b) => {
                let valueA, valueB;

                switch(sortBy) {
                    case 'fecha_desc':
                        // Ordenar por fecha descendente (más recientes primero)
                        valueA = new Date(a.getAttribute('data-fecha') || '1970-01-01');
                        valueB = new Date(b.getAttribute('data-fecha') || '1970-01-01');
                        return valueB - valueA;
                    case 'fecha_asc':
                        // Ordenar por fecha ascendente (más antiguos primero)
                        valueA = new Date(a.getAttribute('data-fecha') || '1970-01-01');
                        valueB = new Date(b.getAttribute('data-fecha') || '1970-01-01');
                        return valueA - valueB;
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

            // Debug info
            const debugInfo = document.getElementById('debug-info');
            debugInfo.innerHTML = `
                <p>Ordenado por: ${sortBy}</p>
                <p>Total elementos: ${items.length}</p>
                <p>Primer elemento: ${items[0]?.querySelector('h3')?.textContent || 'N/A'}</p>
                <p>Último elemento: ${items[items.length-1]?.querySelector('h3')?.textContent || 'N/A'}</p>
            `;

            console.log('Códigos ordenados por:', sortBy, '- Total:', items.length);
        };

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Test de ordenación cargado');
            sortCodes('fecha_desc'); // Ordenar por defecto
        });
    </script>
</body>
</html>
