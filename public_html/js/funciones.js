//callback function that will be called when the user is successfully logged-in with Google

// Definir variable url global
var url = window.location.href;

document.addEventListener("DOMContentLoaded", function() {
    var form = document.querySelector("[name='busqueda_marca2']");
    
    if (form) {
        form.addEventListener("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                // Obtener el valor actual del input
                var searchValue = document.querySelector("#busqueda_marca2").value;
                if (searchValue) {
                    // Redirigir a la URL de búsqueda
                    window.location.href = "/buscar/" + encodeURIComponent(searchValue);
                }
            }
        });
    }
});


function googleLoginEndpoint(googleUser) {
	    // get user information from Google
	    //console.log(googleUser);

	    // send an AJAX request to register the user in your website
	    var ajax = new XMLHttpRequest();

	    // path of server file
	    ajax.open("POST", "google_sign", true);

	    // callback when the status of AJAX is changed
	    ajax.onreadystatechange = function () {
	    	

        	console.log(this);

	        // when the request is completed
	        if (this.readyState == 4) {
	            // when the response is okay
	            if (this.status == 200) {

	            	var obj = JSON.parse(JSON.parse(this.responseText));
	            	
	                
	                if(obj.estado == 'ok'){
	                    
	                	//window.location=window.location='/';
	                	window.location.reload();
	                	
	                }else if(obj.estado == 'noregistro'){


	               	var nombre = obj.name;
					var correo = obj.email;
					var imagen = obj.picture;
	            		
	                	$.ajax({
	                		type: "POST",
	                		url: "/myphp/ajax_actions.php",
	                		data: {
	                			metodo: "registrar_usuario",
	                			nombre: nombre,
	                			img: imagen,
	                			correo: correo,
	                			origin: "googleonetap",
	                		}, 
	                		cache: false,
	                		success: function(data){

	                    		if(data == "trobat") { 
	                    			alert("Este correo ya existe. Prueba con otro !!!");
	                    			$("#correo").focus(); 
	                			} else {
	                				ga('send', 'event', 'Formulario registro usuario', 'nuevo_usuario', 'googleonetap');
	                    			window.location.href = "/bienvenido";
	                			}
	                		}
	                	});
	                    
	                }else{
	                	// Error en la respuesta del servidor
	                }
	                
	            }

	            // if there is any server error
	            if (this.status == 500) {
	                // Error 500 del servidor
	            }
	        }
	    };

	    // send google credentials in the AJAX request
	    var formData = new FormData();
	    formData.append("id_token", googleUser.credential);
	    ajax.send(formData);
	}

	document.addEventListener("DOMContentLoaded", function() {
        var form = document.querySelector("form[name='busqueda_marca2']");
        
        if (form) {
            form.addEventListener("keydown", function(event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                }
            });
        }
    });

	
$(document).ready(function() {
	
	



	/************************************************
	 * DATATABLE
	 **********************************************/

	if($(".table_datatable").length > 0){

		var table = $(".table_datatable").removeClass("hide").DataTable( {
		    "language": {
		        "lengthMenu": "Mostrar _MENU_  elementos por página",
		        "zeroRecords": "Ningún resultado encontrado",
		        "info": "Mostrando página _PAGE_ de _PAGES_",
		        "infoEmpty": "Ningún registro disponible",
		        "infoFiltered": "(Total de _MAX_ registros)",
		        "search": "Búsqueda:",
		        "paginate": {
		            "first":      "Primero",
		            "last":       "Último",
		            "next":       "Siguiente",
		            "previous":   "Anterior"
		        },
		    },
		    "order": [[ 1, "desc" ]]
		});

	}

	/************************************************
	 * USUARIO
	 **********************************************/




	$(document).on('click', '.text_inside', function(event) {


		$(this).next('.show_more').click();

	});

	$(document).on('click', '.pagina_marcas .show_more', function(event) {


		selector = $('#'+$(this).attr("data-show"));
		selector_actual = $(this);

		selector.each(function(index, elem){
			selector_actual.hide();
	        elem.style.height = elem.scrollHeight+'px';
	        reordena();
	    });

	});

	$(document).on('click', '.pagina_individual .show_more', function(event) {


		selector = $('#'+$(this).attr("data-show"));
		selector_actual = $(this);

		selector.each(function(index, elem){
			selector_actual.hide();
	        elem.style.height = elem.scrollHeight+'px';
	    });

	});





	$(document).on('click', '.a_link_us', function(event) {

		window.location.href = ($(this).attr("data-href"));

	});




	$(document).on('click', '#show_more', function(event) {


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
			success: function(data){
				$("#div_more_codes").html(data);
			}
		});


	});

	$(document).on('click', '.open_modal_compartir', function(event) {


		$(".block_menu_mobile").addClass("hide");

		$("#modal_compartir").html('');

		$.ajax({
			type: "POST",
			url: "/myphp/ajax_actions.php",
			data: {
				metodo: "last_codigo",
				marca:  $(this).attr("data-marca"),
				descuento:  $(this).attr("data-descuento"),
				data_codigo_url: $(this).attr("data-codigo-url"),
			},
			cache: false,
			success: function(data){
				$("#modal_compartir").html(data);
			}
		});

		$("#modal_compartir").modal();


	});


	$(document).on('click', '.open_modal_estadisticas', function(event) {


		$(".block_menu_mobile").addClass("hide");

		$("#modal_statistics").html('');

		$.ajax({
			type: "POST",
			url: "/myphp/ajax_actions.php",
			data: {
				metodo: "show_estatistics",
				marca:  $(this).attr("data-marca"),
				descuento:  $(this).attr("data-descuento"),
				data_codigo_id:  $(this).attr("data-codigo-id"),
				data_codigo_url: $(this).attr("data-codigo-url"),
			},
			cache: false,
			success: function(data){
				$("#modal_statistics").html(data);
			}
		});

		$("#modal_statistics").modal();


	});



$(document).on('click', '.envia_buzz', function(event) {


		//$(".block_menu_mobile").addClass("hide");

		//$("#modal_statistics").html('');

		var boton = $(this);

		$.ajax({
			type: "POST",
			url: "/myphp/ajax_actions.php",
			data: {
				metodo: "enviar_buzz_codigo",
				marca:  $(this).attr("data-marca"),
				descuento:  $(this).attr("data-descuento"),
				id_user:  $(this).attr("data-codigo-id-user"),
				data_codigo_id:  $(this).attr("data-codigo-id"),
				data_codigo_url: $(this).attr("data-codigo-url"),
			},
			cache: false,
			success: function(data){

				if(data >= 0){

				alert('zumbido enviado al usuario\nte quedan disponibles ' + data + ' zumbidos');
				boton.hide();

				}else{
					alert('Zumbido no enviado, no te queda saldo de zumbidos\n Puedes generar saldo haciendo login cada dia en codigoamigo.com\n 1 Zumbido por día');

				}
				//$("#modal_statistics").html(data);
			}
		});

		//$("#modal_statistics").modal();


	});







	$(document).on('click', '.desactivar_codigo_usuario', function(event) {

		if (confirm('Estás seguro de eliminar el código?')) {
			$.ajax({
				type: "POST",
				url: "/myphp/ajax_actions.php",
				data: {
					metodo: "desactivar_codigo_usuario",
					id_codigo: $(this).attr("data-id-codigo"),
				},
				cache: false,
				success: function(data){
					location.reload();
					/*$("#modal_compartir").html(data);*/
				}
			});
		} else {
		    // Do nothing!
		}

	});

	$(document).on('click', '.btn_a_restaurar', function(event) {

		if (confirm('Estás seguro de restaurar el código?')) {
			$.ajax({
				type: "POST",
				url: "/myphp/ajax_actions.php",
				data: {
					metodo: "restaurar_codigo_usuario",
					id_codigo: $(this).attr("data-id-codigo"),
				},
				cache: false,
				success: function(data){

					location.reload();
				}
			});
		} else {
		    // Do nothing!
		}

	});


	$("#modal_2").modal();
	
	$(document).on('click', '.dialogo', function(event) {
		$(".last_codigo_final").val($(this).attr("data-codigo"));

		$(".block_menu_mobile").addClass("hide");
		$("#modal_continuar").modal();
	});
	
	
	$(document).on('click', '.open_modal_login', function(event) {
		event.preventDefault();
		$(".last_codigo_final").val($(this).attr("data-codigo"));

		$(".block_menu_mobile").addClass("hide");
		
		// Cerrar cualquier modal abierto primero
		$('.modal').modal('hide');
		
		// Abrir el modal de login después de un pequeño delay
		setTimeout(function() {
			$("#modal_login").modal('show');
		}, 300);
	});


	$(document).on('click', '.open_modal_login_mobile', function(event) {
		event.preventDefault();
		$(".block_menu_mobile").addClass("hide");
		
		// Cerrar cualquier modal abierto primero
		$('.modal').modal('hide');
		
		// Abrir el modal de login después de un pequeño delay
		setTimeout(function() {
			$("#modal_login").modal('show');
		}, 300);
		
		$(".fondo_oscuro").addClass("hide");
	});

	$(document).on('click', '.destacar_gratis', function(event) {
		$(".block_menu_mobile").addClass("hide");
		$("#destacar_gratis").modal();
		$(".fondo_oscuro").addClass("hide");
	});

	$(document).on('click', '.destacar_gratis_plus', function(event) {
		$(".block_menu_mobile").addClass("hide");
		$("#destacar_gratis_plus").modal();
		$(".fondo_oscuro").addClass("hide");
	});





	// Variable para controlar si ya hay una petición en curso
	var loginInProgress = false;

	$("#login").submit(function(event) {

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
			success: function(data){
				try {
					var response = data.trim();
					console.log('Respuesta de la API:', response);

					if(response == "no_trobat") {
						alert("El usuario y contraseña introducidos no aparecen en nuestra base de datos");
					} else if(response == "no_verificado") {
						// Mostrar modal de activación en lugar de alert
						if (typeof showActivationModal === 'function') {
							showActivationModal();
						} else {
							alert("Es necesario que actives tu usuario desde el correo que has recibido al registrarte para poder acceder a tu cuenta.");
						}
					} else {
						location.reload();
					}
				} catch (e) {
					console.error('Error procesando respuesta:', e);
					alert('Error al procesar la respuesta del servidor. Inténtalo de nuevo.');
				}
			},
			error: function(xhr, status, error) {
				console.log('Error en AJAX:', error);
				alert('Error al procesar el login. Inténtalo de nuevo.');
			},
			complete: function() {
				// Rehabilitar el botón en cualquier caso
				$submitBtn.prop('disabled', false).text(originalText);
				loginInProgress = false;
			}
		});

	});


	$("#video_codigo_amigo").click(function(event) {

		$("#codigoamigo video")[0].load();
	});

	$(".votar").click(function(event) {

		event.preventDefault();
		event.stopPropagation();

		var actual = $(this);

		if($(this).hasClass('mas')){
			var votos_positivos = 1;
			var votos_negativos = 0;
		}else if($(this).hasClass('menos')){
			var votos_positivos = 0;
			var votos_negativos = 1;
		}

		$.ajax({
	        type: "POST",
	        url: "/ajax",
	        data: {
	       	 	metodo: "votar_codigo",
	       	    id_codigo: $(this).attr("data-codigo-id"),
	       	    votos_positivos: votos_positivos,
	       	    votos_negativos: votos_negativos
	        },
	        success: function(data) {
	        	actual.parent().html("votado");
	        }
		});


	});

	/************************************************
	 * MENU MOBILE
	 **********************************************/

	$("#btn_for_sidebar").click(function() {

		if($(".block_menu_mobile").hasClass("hide")) {
			$(".block_menu_mobile").removeClass("hide").addClass("animada");
			$(".fondo_oscuro").removeClass("hide");
		} else {
			$(".block_menu_mobile").addClass("hide").removeClass("animada");;
			$(".fondo_oscuro").addClass("hide");
		}

	});

	$(".fondo_oscuro").click(function() {
		$(".block_menu_mobile").addClass("hide").removeClass("animada");;
		$(".fondo_oscuro").addClass("hide");

	});



	/************************************************
	 * BUSCADOR MARCA
	 ************************************************/

	var url_d = "/datos.json?t="+ Date.now();

	//alert(url_d);

	var options_busqueda = {
		url: url_d,
		getValue: "nombre",
		template: {
			type: "custom",
			method: function(value, item) {

				var ruta = "/de-" + item.clave;

				var codes = "";

				if (item.codes == 1) {
					codes = "1 código";
				} else if (item.codes > 1){
					codes = item.codes + " códigos";
				} else{
					codes = "";
				}

				var element = "<a style='color: black;' href='" + item.url + "'>";
				element = element + "<div style='width: 80px;height: 40px;overflow: hidden;border-radius: 10px;display: inline-block;padding: 0px !important;'><img style='' src='" + item.imagen + "' /></div> ";
				element = element + " <div style='margin-left: 3px; font-size: 18px; margin-bottom: 10px;display:inline-block;'>" + value + " (" + codes + ")</div>";
				element = element + "</a>";
				return element;
			}
		},
		list: {
			match: {
				maxNumberOfElements: 20,
				enabled: true
			},
		}
	};

	$("#busqueda_marca,#busqueda_marca2").easyAutocomplete(options_busqueda);

	/************************************************
	 * FORMULARIO CONTACTO
	 ************************************************/

	$("#contacto_usuarios").submit(function(event) {

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
				origin: "Formulario contacto usuarios",
			},
			cache: false,
			success: function(data){
				alert("Mensaje enviado correctamente");
				location.reload();
			}
		});

	});

	$("#contacto_empresas").submit(function(event) {

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
			success: function(data){
				alert("Mensaje enviado correctamente");
				location.reload();
			}
		});

	});

	/************************************************
	 * AÑADIR NUEVO CÓDIGO
	 ************************************************/

	if(url.indexOf('nuevo_codigo') !== -1){

	$("#sin_fecha_caducidad").change(function() {

        if($(this).is(':checked')) { $("#fecha_caducidad").prop('disabled', true).val(""); }
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


	$(document).on("click", ".item_marca", function() {

		var marca = $(this).attr("data-nombre-marca");
		$("#marca_final").val(marca);

	});

	$("#publicar_codigo").submit(function(event) {

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
			success: function(data){
				alert("Mensaje enviado correctamente");
				location.reload();
			}
		});

	});



	/*********** INSERTAR NUEVO CODIGO ***********/

	/* Input de fecha */
	//$('#fecha').datepicker();

	/* Control de fecha caducidad código */
	$("#fechafinalvalid").click(function() {

        if($("#fechafinalvalid").is(':checked')) {
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

	var url_dd = "/datos.json?t="+ Date.now();



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
			onChooseEvent: function() {
				$('#creada').val(1)
            },
			match: {
				enabled: true
			},
		}
	};

	/* Asignar autocompletado a input de marcas */
	$("#marca").easyAutocomplete(options_marca);


	$(document).on("click","#cancelar", function() {

		$("#marca").show();
		$("#div_nueva_marca").hide();

	});

	/* Al escoger "Introducir nueva marca" mostrar div oculto con llamada a api de google imagenes */
	$(document).on("click","#nueva_marca", function() {
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
	        	q: busqueda+' logo',
	        	num: 10,
	        	searchType: "image",
	        	key: "AIzaSyBO8kzIr4NtCVBxLxQSxGkq8Whw4kHgAqI",
	        	cx: "011289846254342421780:ygm3rzpmf2a"
	        },
	        url : 'https://www.googleapis.com/customsearch/v1',
	        success : function (data){
	        	$.each(data["items"], function(index, item) {
	        		var img = $('<li><div class="item_imagen"><img data="' + item["link"] + '" src="' + item["link"] + '"/></div></li>');
	        		$('.inserta_imagenes').append(img);
	        	});
	        }
	    });
	});

	$(document).on("focusout","#marca", function() {


			if($(this).val() == ''){
				$('#creada').val('');
			}


				setTimeout(function(){
					if($('#creada').val()==''){
						$('#marca').val('');
					}
				},1000);

	});

	/* Permitimos al usuario seleccionar una imagen */
	$(document).on("click",".item_imagen", function(){

		//var inside = $(this+' img');
		$(".item_imagen").each(function () {
			$(this).removeClass("img-selected");
		});
		$(this).addClass("img-selected");
		$(".selectImagen").prop("checked", false);
		var name = $(this).children("img").attr('src');
		$("#url_imagen").val(name);
	});

	$("#marca").change(function(){
		$("#div_nueva_marca").addClass("hide");
	});

	$(".selectImagen").on( 'change', function() {
	    if( $(this).is(':checked') ) {
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

	$("#provincia").easyAutocomplete(options_provincia);

	$("#provincia").change(function(){

		var nombre_provincia = $("#provincia").val();
		$.ajax({
	        type: 'POST',
	        data: {
	        	'provincia' : nombre_provincia
	        },
	        url : '/cargar_localidades',
	        success : function (data){

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
	        	$("#localidad").easyAutocomplete(options_localidad);
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

    FB.login(function(response) {


        if(response.status == 'connected') {
            FB.api('/me',{fields: 'picture.type(large),id,name,email,first_name,last_name,gender'}, function(response_api) {


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
        	        success : function (data) {

        	        	// Respuesta del servidor recibida

        	        	ga('send', 'event', 'Formulario registro usuario', 'nuevo_usuario', 'facebook');

        	        	if($(".last_codigo_final").val()!=''){



        	        		if($(".last_codigo_final").val() == 'mis_codigos'){
        	        			document.location.href = 'https://www.codigoamigo.com/mis_codigos';
        	        		}else{
        	        			document.location.href = 'https://www.codigoamigo.com/'+$(".last_codigo_final").val();
        	        		}
        	        	}else{
        	        		location.reload();
        	        	}
        	        }
        	    });
            });
        } else if(response.status == 'not_authorized') { alert('Debes autorizar la app!');
        } else { alert('Debes ingresar a tu cuenta de Facebook!'); }

    },{scope:'email'});

}

/************************************************
 * Copiar código al portapapeles
 **********************************************/

function executeCopy(text, element) {

    var input = document.createElement('textarea');
    document.body.appendChild(input);
    input.value = text;
    input.select();
    document.execCommand('Copy');
    input.remove();

    ga('send', {
    	  hitType: 'event',
    	  eventCategory: 'Clicks',
    	  eventAction: 'enlace_copiado_al_portapapeles',
    	  eventLabel: ''
    	});

    alert("Código copiado en el portapapeles");

    return false;

}

/*
 * SCROLL
 */
/* ------------------------------------------------------------------------ */
/*  SCROLL TO TOP
 /* ------------------------------------------------------------------------ */
//Check to see if the window is top if not then display button
var scroll_top = $('.scrolltop-btn');
$(window).on("scroll",function(){
    if ($(this).scrollTop() > 100) {
        scroll_top.fadeIn(1000);
    } else {
        scroll_top.fadeOut(1000);
    }
});

$(".back-top").on('click',function() {
    $("html, body").animate({ scrollTop: 0 }, "slow");
    return false;
});

