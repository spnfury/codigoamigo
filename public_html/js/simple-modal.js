/**
 * Simple Modal System - Sin dependencias de Bootstrap
 * Sistema ligero de modales compatible con jQuery
 */

(function ($) {
    'use strict';

    // Objeto para almacenar modales activos
    const activeModals = [];

    /**
     * Muestra un modal
     * @param {string} modalId - ID del modal a mostrar
     */
    function showModal(modalId) {
        const $modal = $('#' + modalId);

        if ($modal.length === 0) {
            console.error('[SimpleModal] Modal no encontrado:', modalId);
            return;
        }

        // Crear backdrop si no existe
        let $backdrop = $('.simple-modal-backdrop');
        if ($backdrop.length === 0) {
            $backdrop = $('<div class="simple-modal-backdrop"></div>');
            $('body').append($backdrop);
        }

        // Mostrar backdrop
        $backdrop.addClass('show');

        // Mostrar modal
        $modal.addClass('show').css('display', 'block');

        // Agregar a la lista de modales activos
        if (activeModals.indexOf(modalId) === -1) {
            activeModals.push(modalId);
        }

        // Prevenir scroll del body
        $('body').addClass('modal-open');

        // Trigger evento personalizado
        $modal.trigger('modal:shown');
    }

    /**
     * Oculta un modal
     * @param {string} modalId - ID del modal a ocultar
     */
    function hideModal(modalId) {
        const $modal = $('#' + modalId);

        if ($modal.length === 0) {
            return;
        }

        // Ocultar modal
        $modal.removeClass('show').css('display', 'none');

        // Remover de la lista de modales activos
        const index = activeModals.indexOf(modalId);
        if (index > -1) {
            activeModals.splice(index, 1);
        }

        // Si no hay más modales activos, ocultar backdrop y restaurar scroll
        if (activeModals.length === 0) {
            $('.simple-modal-backdrop').removeClass('show');
            $('body').removeClass('modal-open');
        }

        // Trigger evento personalizado
        $modal.trigger('modal:hidden');
    }

    /**
     * Alterna un modal (muestra si está oculto, oculta si está visible)
     * @param {string} modalId - ID del modal a alternar
     */
    function toggleModal(modalId) {
        const $modal = $('#' + modalId);

        if ($modal.hasClass('show')) {
            hideModal(modalId);
        } else {
            showModal(modalId);
        }
    }

    // Extender jQuery con métodos de modal
    $.fn.simpleModal = function (action) {
        const modalId = this.attr('id');

        if (!modalId) {
            console.error('[SimpleModal] El elemento debe tener un ID');
            return this;
        }

        switch (action) {
            case 'show':
                showModal(modalId);
                break;
            case 'hide':
                hideModal(modalId);
                break;
            case 'toggle':
                toggleModal(modalId);
                break;
            default:
                console.error('[SimpleModal] Acción no válida:', action);
        }

        return this;
    };

    // Configurar event handlers cuando el DOM esté listo
    $(document).ready(function () {
        // Click en backdrop para cerrar
        $(document).on('click', '.simple-modal-backdrop', function () {
            if (activeModals.length > 0) {
                hideModal(activeModals[activeModals.length - 1]);
            }
        });

        // Click en botones de cerrar
        $(document).on('click', '[data-dismiss="modal"]', function (e) {
            e.preventDefault();
            const $modal = $(this).closest('.modal');
            if ($modal.length > 0) {
                hideModal($modal.attr('id'));
            }
        });

        // Tecla ESC para cerrar
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && activeModals.length > 0) {
                hideModal(activeModals[activeModals.length - 1]);
            }
        });

        // Prevenir que clicks dentro del modal lo cierren
        $(document).on('click', '.modal-dialog', function (e) {
            e.stopPropagation();
        });
    });

    // Exponer funciones globalmente
    window.SimpleModal = {
        show: showModal,
        hide: hideModal,
        toggle: toggleModal
    };

})(jQuery);
