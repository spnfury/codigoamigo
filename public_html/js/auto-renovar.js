/**
 * Interruptor de auto-renovación del destacado.
 *
 * La función vivía solo en /js/funciones.js, que NO se carga en
 * public/mis_anuncios.php. Ahí el interruptor existe en el HTML pero al
 * pulsarlo saltaba "toggleAutoRenovar is not defined" y no pasaba nada: un
 * usuario reportó el 2026-07-28 que no podía desactivar la renovación.
 *
 * Se define aquí, en un archivo pequeño y sin dependencias (fetch en vez de
 * jQuery), para poder cargarlo en esa página sin arrastrar las 1.500 líneas
 * de funciones.js. Si ambos se cargan, gana esta definición y el
 * comportamiento es el mismo.
 */
(function () {
    'use strict';

    var TITULO_ON  = 'Auto-renovación activada: se renovará desde tu saldo al expirar el destacado';
    var TITULO_OFF = 'Activa la auto-renovación para renovar el destacado automáticamente desde tu saldo';

    function pintar(el, activada) {
        el.classList.toggle('is-on', activada);
        el.setAttribute('aria-checked', activada ? 'true' : 'false');
        el.title = activada ? TITULO_ON : TITULO_OFF;
    }

    window.toggleAutoRenovar = function (el) {
        var codigoId = el.getAttribute('data-codigo-id');
        if (!codigoId || el.classList.contains('is-loading')) return;

        el.classList.add('is-loading');

        fetch('/myphp/ajax_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: 'metodo=toggle_auto_renovar&codigo_id=' + encodeURIComponent(codigoId)
        })
            .then(function (r) { return r.text(); })
            .then(function (texto) {
                el.classList.remove('is-loading');
                var resp;
                try { resp = JSON.parse(texto); } catch (e) { resp = null; }

                if (resp && resp.success) {
                    pintar(el, !!resp.auto_renovar);
                } else {
                    // Sin feedback el usuario vuelve a pulsar pensando que no
                    // se ha enterado, que es justo lo que pasaba antes.
                    alert((resp && resp.message) ? resp.message : 'No se ha podido cambiar la auto-renovación. Inténtalo de nuevo.');
                }
            })
            .catch(function () {
                el.classList.remove('is-loading');
                alert('No se ha podido cambiar la auto-renovación. Revisa tu conexión.');
            });
    };
})();
