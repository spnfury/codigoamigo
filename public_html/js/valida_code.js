with(document.nuevocodigo)
{
	onsubmit = function(e){
		e.preventDefault();
		ok = true;
		ok_loading = false;
		
		if ($("#div_nueva_marca").hasClass('hide')){
			ok = true;
		} else {
			var categoria = $('select[name=categoria]').val();
			if (ok && categoria == "select") {
				ok = false;
				$('select[name=categoria]').focus();
				alert("Por favor, seleccione una categoria");
			}
			
			var ruta_imagen = $("#url_imagen").val();
			if (ok && ruta_imagen == "") {
				ok = false;
				$('select[name=categoria]').focus();
				alert("Por favor, seleccione una imagen");
			}
		}
		
		if(ok){
			
			var code = $("#codigo").val();
			$.ajax({
		        type: 'POST',
		        data: {
		        	'thecodigo' : code
		        },
		        url : '/comprobar_existe_codigo',
		        success : function (data){
		        	//alert(data);
		        	var resultat = $.trim(data);
		        	//alert(resultat);
		        	if(resultat == "trobat") {
		        		$("#msg").removeClass("hide");
		        		$("#msg").text("Este código ya ha sido introducido. Por favor, prueba con otro código. Si crees que se trata de un error, ponte en contacto con nosotros.")
		        		$("#codigo").focus();
		        	} else {
		        		$(".loading").removeClass("hide");
						setTimeout(function() {
					        submit();
					    },3000);
		        	}
		        }	       
		    });		
		}
	}
}
