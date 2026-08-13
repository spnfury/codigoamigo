//callback function that will be called when the user is successfully logged-in with Google

// Definir variable url global
var url = window.location.href;

// ── Non-blocking Toast Notification (INP optimisation) ──
(function () {
	var toastContainer = null;
	function ensureContainer() {
		if (toastContainer) return toastContainer;
		toastContainer = document.createElement('div');
		toastContainer.id = 'ca-toast-container';
		toastContainer.style.cssText = 'position:fixed;top:20px;right:20px;z-index:999999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
		document.body.appendChild(toastContainer);
		return toastContainer;
	}
	window.showToast = function (message, type) {
		var container = ensureContainer();
		var toast = document.createElement('div');
		var bg = type === 'error' ? '#dc3545' : type === 'warning' ? '#fd7e14' : '#28a745';
		toast.style.cssText = 'pointer-events:auto;max-width:340px;padding:14px 20px;border-radius:10px;color:#fff;font-size:14px;font-family:inherit;box-shadow:0 4px 20px rgba(0,0,0,.25);opacity:0;transform:translateX(40px);transition:opacity .3s ease,transform .3s ease;background:' + bg + ';';
		toast.textContent = message;
		container.appendChild(toast);
		requestAnimationFrame(function () {
			toast.style.opacity = '1';
			toast.style.transform = 'translateX(0)';
		});
		setTimeout(function () {
			toast.style.opacity = '0';
			toast.style.transform = 'translateX(40px)';
			setTimeout(function () { toast.remove(); }, 350);
		}, 3500);
	};
})();

// Definir función global openLoginModalWithRedirect si no existe (para evitar errores)
if (typeof window.openLoginModalWithRedirect !== 'function') {
	window.openLoginModalWithRedirect = function (targetUrl) {
		var redirectUrl = targetUrl || window.location.href;
		try {
			localStorage.setItem('redirectAfterLogin', redirectUrl);
		} catch (storageError) {
			console.warn('[LoginModal] No se pudo guardar redirectAfterLogin', storageError);
		}

		if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined' && typeof $.fn.modal === 'function') {
			$('.modal.in').not('#modal_login').each(function () {
				try {
					$(this).modal('hide');
				} catch (hideError) {
					console.warn('[LoginModal] Error al cerrar modal previo', hideError);
				}
			});
		} else {
			var modals = document.querySelectorAll('.modal:not(#modal_login)');
			modals.forEach(function (modal) {
				modal.classList.remove('in', 'show');
				modal.style.display = 'none';
				modal.setAttribute('aria-hidden', 'true');
			});
			var backdrops = document.querySelectorAll('.modal-backdrop');
			backdrops.forEach(function (backdrop) {
				backdrop.remove();
			});
			document.body.classList.remove('modal-open');
		}

		if (typeof window.showLoginModal === 'function') {
			// Limpiar backdrops de Bootstrap antes de mostrar el modal custom
			if (typeof $ !== 'undefined') {
				$('.modal-backdrop').remove();
				$('body').removeClass('modal-open');
			} else {
				document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.remove(); });
				document.body.classList.remove('modal-open');
			}
			window.showLoginModal();
			return;
		}

		var modal = document.getElementById('modal_login');
		if (modal) {
			if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined' && typeof $.fn.modal === 'function') {
				$(modal).modal({
					backdrop: true,
					keyboard: true,
					show: true
				});
			} else {
				modal.classList.add('in', 'show');
				modal.style.display = 'block';
				modal.setAttribute('aria-hidden', 'false');
				document.body.classList.add('modal-open');
				if (!document.querySelector('.modal-backdrop')) {
					var backdrop = document.createElement('div');
					backdrop.className = 'modal-backdrop fade in';
					document.body.appendChild(backdrop);
				}
			}
			return;
		}

		window.location.href = '/login.php';
	};
}

document.addEventListener("DOMContentLoaded", function () {
	var form = document.querySelector("[name='busqueda_marca2']");

	if (form) {
		form.addEventListener("keydown", function (event) {
			if (event.key === "Enter") {
				event.preventDefault();
				// Obtener el valor actual del input
				var searchValue = document.querySelector("#busqueda_marca2").value;
				if (searchValue) {
					// Redirigir a la URL de búsqueda
					window.location.href = "/ofertas/" + encodeURIComponent(searchValue);
				}
			}
		});
	}
});


function googleLoginEndpoint(googleUser) {
	// get user information from Google
	console.log('[GoogleLogin] googleLoginEndpoint triggered', googleUser);

	// Helper function to parse response
	function parseLoginResponse(data) {
		if (data === null || typeof data === 'undefined') {
			return null;
		}

		if (typeof data === 'string') {
			var trimmed = data.trim();
			if (!trimmed) {
				return null;
			}
			try {
				return JSON.parse(trimmed);
			} catch (e) {
				return null;
			}
		}

		return data;
	}

	function showLoginError(message) {
		var finalMessage = message || 'Error al procesar el inicio de sesión. Inténtalo de nuevo.';
		showToast(finalMessage, 'error');
	}

	// Use jQuery AJAX to call /api/login.php (same as _header_modern.php)
	$.ajax({
		type: "POST",
		url: "/api/login.php",
		data: {
			metodo: "google_login",
			credential: googleUser.credential
		},
		cache: false
	}).done(function (data) {
		console.log('[GoogleLogin] /api/login.php response raw:', data);
		var responseData = parseLoginResponse(data);
		console.log('[GoogleLogin] Parsed response:', responseData);

		if (!responseData) {
			showLoginError('Respuesta inválida del servidor de Google.');
			return;
		}

		if (responseData.success && responseData.user) {
			console.log('[GoogleLogin] Success, applying user session');

			// Apply user session if function exists
			if (typeof window.applyUserSession === 'function') {
				window.applyUserSession(responseData.user);
			} else if (typeof showUserMenu === 'function') {
				showUserMenu(responseData.user);
			}

			// Update menu session if function exists
			if (typeof updateMenuSession === 'function') {
				updateMenuSession();
			}

			// Show success message if function exists
			if (responseData.message && typeof showSuccessToast === 'function') {
				setTimeout(function () {
					showSuccessToast(responseData.message);
				}, 100);
			}

			// Hide login modal if it exists
			if (typeof window.closeLoginModal === 'function') {
				window.closeLoginModal();
			} else if ($("#modal_login").length) {
				// Fallback for pages that might still use bootstrap modal
				try { $("#modal_login").modal('hide'); } catch (e) { }
			}

			// Handle redirect - use localStorage redirectAfterLogin or reload
			var redirectUrl = localStorage.getItem('redirectAfterLogin');
			if (redirectUrl && redirectUrl !== window.location.href) {
				console.log('[GoogleLogin] Redirigiendo a:', redirectUrl);
				localStorage.removeItem('redirectAfterLogin');
				window.location.href = redirectUrl;
			} else {
				console.log('[GoogleLogin] Recargando página');
				location.reload();
			}
		} else if (responseData.error) {
			console.warn('[GoogleLogin] Error payload recibido:', responseData.error);
			if (responseData.error === 'no_verificado' && typeof showActivationModal === 'function') {
				showActivationModal();
			} else {
				showLoginError('Error en el login con Google: ' + responseData.error);
			}
		} else {
			console.error('[GoogleLogin] Respuesta inesperada', responseData);
			showLoginError('No se pudo completar el login con Google.');
		}
	}).fail(function (jqXHR, textStatus, errorThrown) {
		console.error('[GoogleLogin] AJAX fail', textStatus, errorThrown, jqXHR);
		showLoginError('Error al procesar el login con Google. Inténtalo de nuevo.');
	});
}

// Toggle auto-renovar destacado
function toggleAutoRenovar(el) {
    var codigoId = el.getAttribute('data-codigo-id');
    if (el.classList.contains('is-loading')) return;
    el.classList.add('is-loading');
    $.ajax({
        url: '/myphp/ajax_actions.php',
        method: 'POST',
        data: { metodo: 'toggle_auto_renovar', codigo_id: codigoId },
        success: function(resp) {
            el.classList.remove('is-loading');
            let jsonResp = typeof resp === 'string' ? JSON.parse(resp) : resp;
            if (jsonResp.success) {
                if (jsonResp.auto_renovar) {
                    el.classList.add('is-on');
                    el.setAttribute('aria-checked', 'true');
                    el.title = 'Auto-renovación activada: se renovará desde tu saldo al expirar el destacado';
                } else {
                    el.classList.remove('is-on');
                    el.setAttribute('aria-checked', 'false');
                    el.title = 'Activa la auto-renovación para renovar el destacado automáticamente desde tu saldo';
                }
            }
        },
        error: function() { el.classList.remove('is-loading'); }
    });
}


document.addEventListener("DOMContentLoaded", function () {
	var form = document.querySelector("form[name='busqueda_marca2']");

	if (form) {
		form.addEventListener("keydown", function (event) {
			if (event.key === "Enter") {
				event.preventDefault();
			}
		});
	}
});


$(document).ready(function () {





	/************************************************
	 * DATATABLE
	 **********************************************/

	if ($(".table_datatable").length > 0) {

		var table = $(".table_datatable").removeClass("hide").DataTable({
			"language": {
				"lengthMenu": "Mostrar _MENU_  elementos por página",
				"zeroRecords": "Ningún resultado encontrado",
				"info": "Mostrando página _PAGE_ de _PAGES_",
				"infoEmpty": "Ningún registro disponible",
				"infoFiltered": "(Total de _MAX_ registros)",
				"search": "Búsqueda:",
				"paginate": {
					"first": "Primero",
					"last": "Último",
					"next": "Siguiente",
					"previous": "Anterior"
				},
			},
			"order": [[1, "desc"]]
		});

	}

	/************************************************
	 * USUARIO
	 **********************************************/




	$(document).on('click', '.text_inside', function (event) {


		$(this).next('.show_more').click();

	});

	$(document).on('click', '.pagina_marcas .show_more', function (event) {


		selector = $('#' + $(this).attr("data-show"));
		selector_actual = $(this);

		selector.each(function (index, elem) {
			selector_actual.hide();
			elem.style.height = elem.scrollHeight + 'px';
			reordena();
		});

	});

	$(document).on('click', '.pagina_individual .show_more', function (event) {


		selector = $('#' + $(this).attr("data-show"));
		selector_actual = $(this);

		selector.each(function (index, elem) {
			selector_actual.hide();
			elem.style.height = elem.scrollHeight + 'px';
		});

	});





	$(document).on('click', '.a_link_us', function (event) {

		window.location.href = ($(this).attr("data-href"));

	});




	$(document).on('click', '#show_more', function (event) {


		$.ajax({
			type: "POST",
			url: "/myphp/ajax_actions.php",
			data: {
				metodo: "more_codes",
				nombre_clave: "n26",
				skip: "2",
				data_codigo_url: $(this).attr("data-codigo-url"),
			},
			cache: false,
			success: function (data) {
				$("#div_more_codes").html(data);
			}
		});


	});

	$(document).on('click', '.open_modal_compartir', function (event) {


		$(".block_menu_mobile").addClass("hide");

		$("#modal_compartir").html('');

		$.ajax({
			type: "POST",
			url: "/myphp/ajax_actions.php",
			data: {
				metodo: "last_codigo",
				marca: $(this).attr("data-marca"),
				descuento: $(this).attr("data-descuento"),
				data_codigo_url: $(this).attr("data-codigo-url"),
			},
			cache: false,
			success: function (data) {
				$("#modal_compartir").html(data);
			}
		});

		$("#modal_compartir").modal();


	});


	$(document).on('click', '.open_modal_estadisticas', function (event) {


		$(".block_menu_mobile").addClass("hide");

		$("#modal_statistics").html('');

		$.ajax({
			type: "POST",
			url: "/myphp/ajax_actions.php",
			data: {
				metodo: "show_estatistics",
				marca: $(this).attr("data-marca"),
				descuento: $(this).attr("data-descuento"),
				data_codigo_id: $(this).attr("data-codigo-id"),
				data_codigo_url: $(this).attr("data-codigo-url"),
			},
			cache: false,
			success: function (data) {
				$("#modal_statistics").html(data);
			}
		});

		$("#modal_statistics").modal();


	});










	$(document).on('click', '.desactivar_codigo_usuario', function (event) {

		if (confirm('Estás seguro de eliminar el código?')) {
			$.ajax({
				type: "POST",
				url: "/myphp/ajax_actions.php",
				data: {
					metodo: "desactivar_codigo_usuario",
					id_codigo: $(this).attr("data-id-codigo"),
				},
				cache: false,
				success: function (data) {
					location.reload();
					/*$("#modal_compartir").html(data);*/
				}
			});
		} else {
			// Do nothing!
		}

	});

	$(document).on('click', '.btn_a_restaurar, .restaurar_codigo_usuario', function (event) {

		if (confirm('Estás seguro de restaurar el código?')) {
			$.ajax({
				type: "POST",
				url: "/myphp/ajax_actions.php",
				data: {
					metodo: "restaurar_codigo_usuario",
					id_codigo: $(this).attr("data-id-codigo"),
				},
				cache: false,
				success: function (data) {

					location.reload();
				}
			});
		} else {
			// Do nothing!
		}

	});



	if ($.fn.modal && $("#modal_2").length > 0) {
		$("#modal_2").modal();
	}


	$(document).on('click', '.dialogo', function (event) {
		$(".last_codigo_final").val($(this).attr("data-codigo"));

		$(".block_menu_mobile").addClass("hide");
		$("#modal_continuar").modal();
	});

	$(document).on('click', '.open_modal_login', function (event) {
		event.preventDefault();

		var $trigger = $(this);
		var codigoDestino = $trigger.attr("data-codigo");
		if (codigoDestino) {
			$(".last_codigo_final").val(codigoDestino);
		}

		$(".block_menu_mobile").addClass("hide");

		// Cerrar otros modales activos, pero dejar libre el login
		if ($.fn.modal) {
			$('.modal').not('#modal_login').modal('hide');
		} else {
			$('.modal').not('#modal_login').removeClass('in show').hide();
		}

		var redirectUrl = $trigger.data('redirect-url');
		if (!redirectUrl) {
			var href = $trigger.attr('href');
			if (href && href !== '#') {
				redirectUrl = href;
			}
		}

		if (typeof window.openLoginModalWithRedirect === 'function') {
			window.openLoginModalWithRedirect(redirectUrl);
		} else {
			setTimeout(function () {
				if ($.fn.modal) {
					$("#modal_login").modal('show');
				} else {
					$("#modal_login").addClass('in show').show().attr('aria-hidden', 'false');
					$('body').addClass('modal-open');
					if (!$('.modal-backdrop').length) {
						$('<div class="modal-backdrop fade in"></div>').appendTo(document.body);
					}
				}
			}, 100);
		}
	});


	$(document).on('click', '.open_modal_login_mobile', function (event) {
		event.preventDefault();

		$(".block_menu_mobile").addClass("hide");

		if ($.fn.modal) {
			$('.modal').not('#modal_login').modal('hide');
		} else {
			$('.modal').not('#modal_login').removeClass('in show').hide();
		}

		var redirectUrl = $(this).data('redirect-url') || null;

		if (typeof window.openLoginModalWithRedirect === 'function') {
			window.openLoginModalWithRedirect(redirectUrl);
		} else {
			setTimeout(function () {
				if ($.fn.modal) {
					$("#modal_login").modal('show');
				} else {
					$("#modal_login").addClass('in show').show().attr('aria-hidden', 'false');
					$('body').addClass('modal-open');
					if (!$('.modal-backdrop').length) {
						$('<div class="modal-backdrop fade in"></div>').appendTo(document.body);
					}
				}
			}, 100);
		}

		$(".fondo_oscuro").addClass("hide");
	});

	$(document).on('click', '.destacar_gratis', function (event) {
		$(".block_menu_mobile").addClass("hide");
		$("#destacar_gratis").modal();
		$(".fondo_oscuro").addClass("hide");
	});

	$(document).on('click', '.destacar_gratis_plus', function (event) {
		$(".block_menu_mobile").addClass("hide");
		$("#destacar_gratis_plus").modal();
		$(".fondo_oscuro").addClass("hide");
	});





	// Variable para controlar si ya hay una petición en curso
	var loginInProgress = false;

	$("#login").submit(function (event) {

		event.preventDefault();
		event.stopPropagation();

		// Prevenir múltiples envíos simultáneos
		if (loginInProgress) {
			console.log('Login ya en progreso, ignorando envío');
			return false;
		}

		loginInProgress = true;

		// Deshabilitar el botón para evitar múltiples envíos
		var $submitBtn = $(this).find('button[type="submit"]');
		var originalText = $submitBtn.text();
		$submitBtn.prop('disabled', true).text('Iniciando sesión...');

		$.ajax({
			type: "POST",
			url: "/myphp/ajax_actions.php",
			data: {
				metodo: "login_user",
				username: $("#mail_login").val(),
				password: $("#pass_login").val(),
			},
			cache: false,
			timeout: 10000, // 10 segundos de timeout
			success: function (data) {
				try {
					var response;
					if (typeof data === 'string') {
						response = JSON.parse(data.trim());
					} else {
						response = data;
					}
					console.log('Respuesta de la API:', response);

					if (response.success === false) {
						if (response.error == "no_trobat") {
							showToast('El usuario y contraseña introducidos no aparecen en nuestra base de datos', 'error');
						} else if (response.error == "no_verificado") {
							// Mostrar modal de activación en lugar de alert
							if (typeof showActivationModal === 'function') {
								showActivationModal();
							} else {
								showToast('Es necesario que actives tu usuario desde el correo que has recibido al registrarte para poder acceder a tu cuenta.', 'warning');
							}
						} else {
							showToast('Error: ' + response.error, 'error');
						}
					} else if (response.success === true) {
						var redirectUrl = localStorage.getItem('redirectAfterLogin');
						if (redirectUrl && redirectUrl !== window.location.href) {
							console.log('[Login] Redirigiendo a:', redirectUrl);
							localStorage.removeItem('redirectAfterLogin');
							window.location.href = redirectUrl;
						} else {
							console.log('[Login] Recargando página');
							location.reload();
						}
					} else {
						// Respuesta inesperada
						console.error('Respuesta inesperada:', response);
						location.reload();
					}
				} catch (e) {
					console.error('Error procesando respuesta:', e);
					console.log('Data received:', data);
					showToast('Error al procesar la respuesta del servidor. Inténtalo de nuevo.', 'error');
				}
			},
			error: function (xhr, status, error) {
				console.log('Error en AJAX:', error);
				showToast('Error al procesar el login. Inténtalo de nuevo.', 'error');
			},
			complete: function () {
				// Rehabilitar el botón en cualquier caso
				$submitBtn.prop('disabled', false).text(originalText);
				loginInProgress = false;
			}
		});

	});


	$("#video_codigo_amigo").click(function (event) {

		$("#codigoamigo video")[0].load();
	});

	$(".votar").click(function (event) {

		event.preventDefault();
		event.stopPropagation();

		var actual = $(this);
		var codigoId = $(this).attr("data-codigo-id");
		var voteSection = actual.closest('.vote-section');

		if ($(this).hasClass('mas')) {
			var votos_positivos = 1;
			var votos_negativos = 0;
		} else if ($(this).hasClass('menos')) {
			var votos_positivos = 0;
			var votos_negativos = 1;
		}

		// Deshabilitar botones durante la petición
		actual.prop('disabled', true);

		$.ajax({
			type: "POST",
			url: "/ajax",
			data: {
				metodo: "votar_codigo",
				id_codigo: codigoId,
				votos_positivos: votos_positivos,
				votos_negativos: votos_negativos
			},
			dataType: "json",
			success: function (response) {
				if (response.success) {
					// Actualizar contador de votos
					if (voteSection) {
						var voteCountElement = voteSection.find('.vote-count');
						if (voteCountElement.length) {
							voteCountElement.text(response.total + '°');
						}
					}

					// Deshabilitar botones de votación
					voteSection.find('.votar').prop('disabled', true).css('opacity', '0.6');

					// Mostrar mensaje de éxito
					actual.parent().html('<span style="color: green;">✓ Votado</span>');
				} else {
					showToast(response.message || 'Error al votar', 'error');
					actual.prop('disabled', false);
				}
			},
			error: function () {
				showToast('Error de conexión. Inténtalo de nuevo.', 'error');
				actual.prop('disabled', false);
			}
		});


	});

	/************************************************
	 * MENU MOBILE
	 **********************************************/

	$("#btn_for_sidebar").click(function () {

		if ($(".block_menu_mobile").hasClass("hide")) {
			$(".block_menu_mobile").removeClass("hide").addClass("animada");
			$(".fondo_oscuro").removeClass("hide");
		} else {
			$(".block_menu_mobile").addClass("hide").removeClass("animada");;
			$(".fondo_oscuro").addClass("hide");
		}

	});

	$(".fondo_oscuro").click(function () {
		$(".block_menu_mobile").addClass("hide").removeClass("animada");;
		$(".fondo_oscuro").addClass("hide");

	});



	/************************************************
	 * BUSCADOR MARCA
	 ************************************************/

	var url_d = "/datos.json?t=" + Date.now();

	//alert(url_d);

	var options_busqueda = {
		url: url_d,
		getValue: "nombre",
		template: {
			type: "custom",
			method: function (value, item) {

				var ruta = "/de-" + item.clave;

				var codes = "";

				if (item.codes == 1) {
					codes = "1 código";
				} else if (item.codes > 1) {
					codes = item.codes + " códigos";
				} else {
					codes = "";
				}

				// Procesar URL de imagen: convertir CDN a URL directa del servidor
				var imagenUrl = item.imagen || '/img/no_image.png';
				try {
					if (imagenUrl && typeof imagenUrl === 'string' && imagenUrl.indexOf('cdn.codigoamigo.com') !== -1) {
						// Extraer el path de la URL del CDN
						var urlObj = new URL(imagenUrl);
						var path = urlObj.pathname;
						// Convertir a URL directa del servidor
						// Si es panel_marcas, necesita /img/ antes
						if (path.indexOf('/panel_marcas/') !== -1) {
							imagenUrl = 'https://www.codigoamigo.com/img' + path;
						} else {
							imagenUrl = 'https://www.codigoamigo.com' + path;
						}
					}
					// Convertir http a https
					if (imagenUrl && typeof imagenUrl === 'string' && imagenUrl.indexOf('http://') !== -1) {
						imagenUrl = imagenUrl.replace('http://', 'https://');
					}
				} catch (e) {
					// Si hay error al procesar la URL, usar imagen por defecto
					imagenUrl = '/img/no_image.png';
				}

				var element = "<a style='color: black;' href='" + item.url + "'>";
				element = element + "<div style='width: 80px;height: 40px;overflow: hidden;border-radius: 10px;display: inline-block;padding: 0px !important;'><img style='' src='" + imagenUrl + "' onerror=\"this.src='/img/no_image.png'\" /></div> ";
				element = element + " <div style='margin-left: 3px; font-size: 18px; margin-bottom: 10px;display:inline-block;'>" + value + " (" + codes + ")</div>";
				element = element + "</a>";
				return element;
			}
		},
		list: {
			maxNumberOfElements: 20,
			match: {
				enabled: true
			},
		},
	};

	/* Aplicar autocompletado a campos de búsqueda - Solo si Select2 no está presente 
	   COMENTADO PARA EVITAR CONFLICTO CON header_base.php QUE TIENE UNA CONFIGURACIÓN MÁS COMPLETA
	if (typeof $.fn.select2 === 'undefined') {
		$("#busqueda_marca,#busqueda_marca2").easyAutocomplete(options_busqueda);
		console.log("EasyAutocomplete aplicado a campos de búsqueda (Select2 no disponible)");
	} else {
		console.log("Select2 está disponible, omitiendo EasyAutocomplete para campos de búsqueda");
	}
	*/

	/************************************************
	 * FORMULARIO CONTACTO
	 ************************************************/

	var contactoUsuariosEnProgreso = false;

	$("#contacto_usuarios").submit(function (event) {

		event.preventDefault();
		event.stopPropagation();

		if (contactoUsuariosEnProgreso) { return false; }
		contactoUsuariosEnProgreso = true;
		var $form = $(this);
		var $btn = $form.find('input[type="submit"], button[type="submit"]');
		if ($btn.length) { $btn.prop('disabled', true).val('Enviando...'); }

		// Detectar si hay reCAPTCHA v3 cargado (grecaptcha.execute disponible)
		if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.execute === 'function') {
			// Obtener sitekey desde el script de carga (?render=SITEKEY) si es posible
			var recaptchaScript = document.querySelector('script[src*="recaptcha/api.js?render="]');
			var siteKey = null;
			try { siteKey = recaptchaScript ? new URL(recaptchaScript.src).searchParams.get('render') : null; } catch (e) { siteKey = null; }
			if (!siteKey) { siteKey = '6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS'; }

			grecaptcha.execute(siteKey, { action: 'contact' }).then(function (token) {
				$.ajax({
					type: "POST",
					url: "/myphp/ajax_actions.php",
					data: {
						metodo: "formulario_contacto",
						nombre: $("#nombre").val(),
						correo: $("#correo").val(),
						telefono: $("#telefono").val(),
						mensaje: $("#mensaje").val(),
						origin: "Formulario contacto usuarios (v3)",
						recaptcha_response: token
					},
					cache: false,
					success: function (data) {
						if (data.trim() === "success") {
							showToast('Mensaje enviado correctamente');
							$("#contacto_usuarios")[0].reset();
							contactoUsuariosEnProgreso = false;
							if ($btn.length) { $btn.prop('disabled', false).val('Enviar Mensaje'); }
						} else if (data.trim() === "recaptcha_error") {
							showToast('Error de verificación reCAPTCHA. Por favor, inténtalo de nuevo.', 'error');
							contactoUsuariosEnProgreso = false;
							if ($btn.length) { $btn.prop('disabled', false).val('Enviar Mensaje'); }
						} else {
							showToast('Error al enviar el mensaje. Por favor, inténtalo de nuevo.', 'error');
							contactoUsuariosEnProgreso = false;
							if ($btn.length) { $btn.prop('disabled', false).val('Enviar Mensaje'); }
						}
					},
					error: function (xhr, status, error) {
						showToast('Error al enviar el mensaje. Por favor, inténtalo de nuevo.', 'error');
						console.error('Error en AJAX:', error);
						contactoUsuariosEnProgreso = false;
						if ($btn.length) { $btn.prop('disabled', false).val('Enviar Mensaje'); }
					}
				});
			});
			return false;
		}

		// Fallback v2 (checkbox) si execute no está disponible
		if (typeof grecaptcha === 'undefined') {
			showToast('El sistema de verificación no está disponible. Por favor, contacta directamente a info@codigoamigo.com', 'error');
			return false;
		}

		var recaptchaWidget = document.querySelector('.g-recaptcha');
		if (!recaptchaWidget || !recaptchaWidget.hasAttribute('data-widget-id')) {
			showToast('El widget de verificación no está disponible. Por favor, contacta directamente a info@codigoamigo.com', 'error');
			return false;
		}

		var recaptchaResponse = grecaptcha.getResponse();
		if (recaptchaResponse.length === 0) {
			showToast('Por favor, completa el reCAPTCHA antes de enviar el formulario.', 'warning');
			return false;
		}

		$.ajax({
			type: "POST",
			url: "/myphp/ajax_actions.php",
			data: {
				metodo: "formulario_contacto",
				nombre: $("#nombre").val(),
				correo: $("#correo").val(),
				telefono: $("#telefono").val(),
				mensaje: $("#mensaje").val(),
				origin: "Formulario contacto usuarios",
				recaptcha_response: recaptchaResponse
			},
			cache: false,
			success: function (data) {
				if (data.trim() === "success") {
					showToast('Mensaje enviado correctamente');
					grecaptcha.reset();
					contactoUsuariosEnProgreso = false;
					if ($btn.length) { $btn.prop('disabled', false).val('Enviar Mensaje'); }
					location.reload();
				} else if (data.trim() === "recaptcha_error") {
					showToast('Error de verificación reCAPTCHA. Por favor, inténtalo de nuevo.', 'error');
					grecaptcha.reset();
					contactoUsuariosEnProgreso = false;
					if ($btn.length) { $btn.prop('disabled', false).val('Enviar Mensaje'); }
				} else {
					showToast('Error al enviar el mensaje. Por favor, inténtalo de nuevo.', 'error');
					grecaptcha.reset();
					contactoUsuariosEnProgreso = false;
					if ($btn.length) { $btn.prop('disabled', false).val('Enviar Mensaje'); }
				}
			},
			error: function (xhr, status, error) {
				showToast('Error al enviar el mensaje. Por favor, inténtalo de nuevo.', 'error');
				grecaptcha.reset();
				console.error('Error en AJAX:', error);
				contactoUsuariosEnProgreso = false;
				if ($btn.length) { $btn.prop('disabled', false).val('Enviar Mensaje'); }
			}
		});

	});

	var contactoEmpresasEnProgreso = false;

	$("#contacto_empresas").submit(function (event) {

		event.preventDefault();
		event.stopPropagation();

		if (contactoEmpresasEnProgreso) { return false; }
		contactoEmpresasEnProgreso = true;
		var $formE = $(this);
		var $btnE = $formE.find('input[type="submit"], button[type="submit"]');
		if ($btnE.length) { $btnE.prop('disabled', true).val('Enviando...'); }

		if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.execute === 'function') {
			var recaptchaScript = document.querySelector('script[src*="recaptcha/api.js?render="]');
			var siteKey = null;
			try { siteKey = recaptchaScript ? new URL(recaptchaScript.src).searchParams.get('render') : null; } catch (e) { siteKey = null; }
			if (!siteKey) { siteKey = '6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS'; }

			grecaptcha.execute(siteKey, { action: 'contact_business' }).then(function (token) {
				$.ajax({
					type: "POST",
					url: "/myphp/ajax_actions.php",
					data: {
						metodo: "formulario_contacto",
						nombre: $("#nombre").val(),
						correo: $("#correo").val(),
						telefono: $("#telefono").val(),
						mensaje: $("#mensaje").val(),
						origin: "Formulario contacto empresas (v3)",
						recaptcha_response: token
					},
					cache: false,
					success: function (data) {
						if (data.trim() === "success") {
							showToast('Mensaje enviado correctamente');
							$("#contacto_empresas")[0].reset();
							contactoEmpresasEnProgreso = false;
							if ($btnE.length) { $btnE.prop('disabled', false).val('Enviar Mensaje'); }
						} else if (data.trim() === "recaptcha_error") {
							showToast('Error de verificación reCAPTCHA. Por favor, inténtalo de nuevo.', 'error');
							contactoEmpresasEnProgreso = false;
							if ($btnE.length) { $btnE.prop('disabled', false).val('Enviar Mensaje'); }
						} else {
							showToast('Error al enviar el mensaje. Por favor, inténtalo de nuevo.', 'error');
							contactoEmpresasEnProgreso = false;
							if ($btnE.length) { $btnE.prop('disabled', false).val('Enviar Mensaje'); }
						}
					},
					error: function (xhr, status, error) {
						showToast('Error al enviar el mensaje. Por favor, inténtalo de nuevo.', 'error');
						console.error('Error en AJAX:', error);
						contactoEmpresasEnProgreso = false;
						if ($btnE.length) { $btnE.prop('disabled', false).val('Enviar Mensaje'); }
					}
				});
			});
			return false;
		}

		// Fallback v2
		if (typeof grecaptcha === 'undefined') {
			showToast('El sistema de verificación no está disponible. Por favor, contacta directamente a info@codigoamigo.com', 'error');
			return false;
		}

		var recaptchaWidget = document.querySelector('.g-recaptcha');
		if (!recaptchaWidget || !recaptchaWidget.hasAttribute('data-widget-id')) {
			showToast('El widget de verificación no está disponible. Por favor, contacta directamente a info@codigoamigo.com', 'error');
			return false;
		}

		var recaptchaResponse = grecaptcha.getResponse();
		if (recaptchaResponse.length === 0) {
			showToast('Por favor, completa el reCAPTCHA antes de enviar el formulario.', 'warning');
			return false;
		}

		$.ajax({
			type: "POST",
			url: "/myphp/ajax_actions.php",
			data: {
				metodo: "formulario_contacto",
				nombre: $("#nombre").val(),
				correo: $("#correo").val(),
				telefono: $("#telefono").val(),
				mensaje: $("#mensaje").val(),
				origin: "Formulario contacto empresas",
				recaptcha_response: recaptchaResponse
			},
			cache: false,
			success: function (data) {
				if (data.trim() === "success") {
					showToast('Mensaje enviado correctamente');
					grecaptcha.reset();
					contactoEmpresasEnProgreso = false;
					if ($btnE.length) { $btnE.prop('disabled', false).val('Enviar Mensaje'); }
					location.reload();
				} else if (data.trim() === "recaptcha_error") {
					showToast('Error de verificación reCAPTCHA. Por favor, inténtalo de nuevo.', 'error');
					grecaptcha.reset();
					contactoEmpresasEnProgreso = false;
					if ($btnE.length) { $btnE.prop('disabled', false).val('Enviar Mensaje'); }
				} else {
					showToast('Error al enviar el mensaje. Por favor, inténtalo de nuevo.', 'error');
					grecaptcha.reset();
					contactoEmpresasEnProgreso = false;
					if ($btnE.length) { $btnE.prop('disabled', false).val('Enviar Mensaje'); }
				}
			},
			error: function (xhr, status, error) {
				showToast('Error al enviar el mensaje. Por favor, inténtalo de nuevo.', 'error');
				grecaptcha.reset();
				console.error('Error en AJAX:', error);
				contactoEmpresasEnProgreso = false;
				if ($btnE.length) { $btnE.prop('disabled', false).val('Enviar Mensaje'); }
			}
		});

	});

	/************************************************
	 * AÑADIR NUEVO CÓDIGO
	 ************************************************/

	if (url.indexOf('nuevo_codigo') !== -1) {

		$("#sin_fecha_caducidad").change(function () {

			if ($(this).is(':checked')) { $("#fecha_caducidad").prop('disabled', true).val(""); }
			else { $("#fecha_caducidad").prop('disabled', false); }

		});

		//	var options2 = {
		//			url: "/datos.json",
		//			getValue: "nombre",
		//			template: {
		//				type: "custom",
		//				method: function(value, item) {
		//
		//					var ruta = "/de-" + item.clave;
		//
		//					var codes = "";
		//					if (item.codes == 1) { codes = "1 código"; }  else { codes = item.codes + " códigos"; }
		//
		//					var element = "";
		//					element = element + "<div class='item_marca' data-nombre-marca='" + item.nombre + "'>";
		//					element = element + "<img style='width: 60px;' src='" + item.imagen + "' />";
		//					element = element + "<span style='font-size: 16px; font-weight: 500; position: relative; bottom: 10px; left: 10px;'>" + value + "</span>";
		//					element = element + "</div>";
		//					return element;
		//
		//				}
		//			},
		//			list: {
		//				match: {
		//					enabled: true
		//				},
		//			}
		//		};
		//
		//	$("#marca").easyAutocomplete(options2);


		$(document).on("click", ".item_marca", function () {

			var marca = $(this).attr("data-nombre-marca");
			$("#marca_final").val(marca);

		});

		$("#publicar_codigo").submit(function (event) {

			event.preventDefault();
			event.stopPropagation();

			$.ajax({
				type: "POST",
				url: "/myphp/ajax_actions.php",
				data: {
					metodo: "formulario_contacto",
					nombre: $("#nombre").val(),
					correo: $("#correo").val(),
					telefono: $("#telefono").val(),
					mensaje: $("#mensaje").val(),
					origin: "Formulario contacto empresas",
				},
				cache: false,
				success: function (data) {
					showToast('Mensaje enviado correctamente');
					location.reload();
				}
			});

		});



		/*********** INSERTAR NUEVO CODIGO ***********/

		/* Input de fecha */
		//$('#fecha').datepicker();

		/* Control de fecha caducidad código */
		$("#fechafinalvalid").click(function () {

			if ($("#fechafinalvalid").is(':checked')) {
				$("#fecha").css("opacity", "0.4");
				$("#helpfecha").css("opacity", "0.4");
				$("#fecha").prop('disabled', true);
			} else {
				$("#fecha").css("opacity", "1.0");
				$("#helpfecha").css("opacity", "1.0");
				$("#fecha").prop('disabled', false);
			}
		});

		/* Este php refresca los datos de provincias en la BD y los printa en un .json */
		$.post("/list_elements_bd");

		var url_dd = "/datos.json?t=" + Date.now();



		var options_marca = {
			url: url_dd,
			getValue: "nombre",
			template: {
				type: "iconLeft",
				fields: {
					iconSrc: "imagen"
				}
			},
			noResults: "<a id='nueva_marca'>(No encontrado)\n + Añadir como nueva Marca</a>",
			list: {
				maxNumberOfElements: 10,
				onChooseEvent: function () {
					$('#creada').val(1)
				},
				match: {
					enabled: true
				},
			}
		};

		/* Asignar autocompletado a input de marcas - Solo si Select2 no está presente */
		if (typeof $.fn.select2 === 'undefined') {
			$("#marca").easyAutocomplete(options_marca);
			console.log("EasyAutocomplete aplicado a #marca (Select2 no disponible)");
		} else {
			console.log("Select2 está disponible, omitiendo EasyAutocomplete para evitar conflictos");
		}


		$(document).on("click", "#cancelar", function () {

			$("#marca").show();
			$("#div_nueva_marca").hide();

		});

		/* Al escoger "Introducir nueva marca" mostrar div oculto con llamada a api de google imagenes */
		$(document).on("click", "#nueva_marca", function () {
			$('#creada').val(1);
			$("#marca").hide();

			$("#div_nueva_marca").show();
			$("#div_nueva_marca").removeClass("hide");
			$("#categoria").focus();
			$(".nombre_nuevo").html($("#marca").val());
			$(".inserta_imagenes").empty();
			var busqueda = $("#marca").val() + " logo";

			$.ajax({
				type: 'GET',
				data: {
					q: busqueda + ' logo',
					num: 10,
					searchType: "image",
					key: "AIzaSyBO8kzIr4NtCVBxLxQSxGkq8Whw4kHgAqI",
					cx: "011289846254342421780:ygm3rzpmf2a"
				},
				url: 'https://www.googleapis.com/customsearch/v1',
				success: function (data) {
					$.each(data["items"], function (index, item) {
						var img = $('<li><div class="item_imagen"><img data="' + item["link"] + '" src="' + item["link"] + '"/></div></li>');
						$('.inserta_imagenes').append(img);
					});
				}
			});
		});

		$(document).on("focusout", "#marca", function () {


			if ($(this).val() == '') {
				$('#creada').val('');
			}


			setTimeout(function () {
				if ($('#creada').val() == '') {
					$('#marca').val('');
				}
			}, 1000);

		});

		/* Permitimos al usuario seleccionar una imagen */
		$(document).on("click", ".item_imagen", function () {

			//var inside = $(this+' img');
			$(".item_imagen").each(function () {
				$(this).removeClass("img-selected");
			});
			$(this).addClass("img-selected");
			$(".selectImagen").prop("checked", false);
			var name = $(this).children("img").attr('src');
			$("#url_imagen").val(name);
		});

		$("#marca").change(function () {
			$("#div_nueva_marca").addClass("hide");
		});

		$(".selectImagen").on('change', function () {
			if ($(this).is(':checked')) {
				$("#url_imagen").val("Sin imagen");
				$(".item_imagen").each(function () {
					$(this).removeClass("img-selected");
				});
			} else {
				$("#url_imagen").val("");
			}
		});

		var options_provincia = {
			url: "/provincias.json",
			listLocation: "data",
			getValue: "PRO",
			noResults: "",
			list: {
				maxNumberOfElements: 5,
				match: {
					enabled: true
				}
			}
		};

		/* Aplicar autocompletado a provincia - Solo si Select2 no está presente */
		if (typeof $.fn.select2 === 'undefined') {
			$("#provincia").easyAutocomplete(options_provincia);
			console.log("EasyAutocomplete aplicado a #provincia (Select2 no disponible)");
		} else {
			console.log("Select2 está disponible, omitiendo EasyAutocomplete para provincia");
		}

		$("#provincia").change(function () {

			var nombre_provincia = $("#provincia").val();
			$.ajax({
				type: 'POST',
				data: {
					'provincia': nombre_provincia
				},
				url: '/cargar_localidades',
				success: function (data) {

					var options_localidad = {
						url: "http://apiv1.geoapi.es/municipios?CPRO=" + data + "&type=JSON&key=89c905162d7eac6cd9aa4720feb636912c4c77677330c33a6a91861d68bd0f59",
						listLocation: "data",
						getValue: "DMUN50",
						noResults: "",
						list: {
							maxNumberOfElements: 7,
							match: {
								enabled: true
							}
						}
					};
					/* Aplicar autocompletado a localidad - Solo si Select2 no está presente */
					if (typeof $.fn.select2 === 'undefined') {
						$("#localidad").easyAutocomplete(options_localidad);
						console.log("EasyAutocomplete aplicado a #localidad (Select2 no disponible)");
					} else {
						console.log("Select2 está disponible, omitiendo EasyAutocomplete para localidad");
					}
				}
			});
		});

	}
	//alert(document.getElementsByTagName('*').length);
});










/************************************************
 * LOGIN WITH FACEBOOK
 **********************************************/

function login_user_facebook() {

	FB.login(function (response) {


		if (response.status == 'connected') {
			FB.api('/me', { fields: 'picture.type(large),id,name,email,first_name,last_name,gender' }, function (response_api) {


				$.ajax({
					type: 'POST',
					url: "https://www.codigoamigo.com/myphp/ajax_actions.php",
					data: {
						metodo: "login_user_facebook",
						id_user: response_api.id,
						username: response_api.name,
						email: response_api.email,
						gender: response_api.gender,
						img_user: response_api.picture["data"]["url"]
					},
					success: function (data) {

						// Respuesta del servidor recibida

						ga('send', 'event', 'Formulario registro usuario', 'nuevo_usuario', 'facebook');

						if ($(".last_codigo_final").val() != '') {



							if ($(".last_codigo_final").val() == 'mis_codigos') {
								document.location.href = 'https://www.codigoamigo.com/mis_codigos';
							} else {
								document.location.href = 'https://www.codigoamigo.com/' + $(".last_codigo_final").val();
							}
						} else {
							location.reload();
						}
					}
				});
			});
		} else if (response.status == 'not_authorized') {
			showToast('Debes autorizar la app!', 'warning');
		} else { showToast('Debes ingresar a tu cuenta de Facebook!', 'warning'); }

	}, { scope: 'email' });

}

/************************************************
 * Copiar código al portapapeles
 **********************************************/

function executeCopy(text, element) {

	function afterCopy() {
		try {
			ga('send', {
				hitType: 'event',
				eventCategory: 'Clicks',
				eventAction: 'enlace_copiado_al_portapapeles',
				eventLabel: ''
			});
		} catch (e) {}
		showToast('Código copiado en el portapapeles ✓');
	}

	if (navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(text).then(afterCopy).catch(function () {
			// Fallback para contextos sin permisos
			var input = document.createElement('textarea');
			document.body.appendChild(input);
			input.value = text;
			input.select();
			document.execCommand('Copy');
			input.remove();
			afterCopy();
		});
	} else {
		var input = document.createElement('textarea');
		document.body.appendChild(input);
		input.value = text;
		input.select();
		document.execCommand('Copy');
		input.remove();
		afterCopy();
	}

	return false;

}

/*
 * SCROLL
 */
/* ------------------------------------------------------------------------ */
/*  SCROLL TO TOP
 /* ------------------------------------------------------------------------ */
// ── Scroll-to-top with rAF throttle (INP optimisation) ──
(function () {
	var scrollBtn = document.querySelector('.scrolltop-btn');
	if (!scrollBtn) return;
	var ticking = false;
	var isVisible = false;
	scrollBtn.style.transition = 'opacity .4s ease, visibility .4s ease';
	scrollBtn.style.opacity = '0';
	scrollBtn.style.visibility = 'hidden';
	window.addEventListener('scroll', function () {
		if (ticking) return;
		ticking = true;
		requestAnimationFrame(function () {
			var shouldShow = window.scrollY > 100;
			if (shouldShow !== isVisible) {
				isVisible = shouldShow;
				scrollBtn.style.opacity = shouldShow ? '1' : '0';
				scrollBtn.style.visibility = shouldShow ? 'visible' : 'hidden';
			}
			ticking = false;
		});
	}, { passive: true });

	var backTopBtn = document.querySelector('.back-top');
	if (backTopBtn) {
		backTopBtn.addEventListener('click', function (e) {
			e.preventDefault();
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	}
})();

