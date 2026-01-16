<?php 
$title = "Contacto Empresas - CodigoAmigo.com";
$description = "Formulario de contacto para empresas interesadas en colaborar con CodigoAmigo.com";
get_header_new($title, $description); 
?>

<style>
/* Asegurar que el widget de reCAPTCHA sea visible */
.g-recaptcha {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    margin: 20px auto !important;
    text-align: center !important;
}

/* Estilos adicionales para el contenedor de reCAPTCHA */
.recaptcha-container {
    margin: 20px 0;
    padding: 15px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    text-align: center;
}
</style>

<div class="container container-top container-bottom">
    <div class="row">    
    	<div class="col-md-12 col-xs-12 text-center"><h1 class="title_page">Formulario de contacto para empresas</h1></div>        
        <div class="col-md-8 col-md-offset-2">                                
        	<form id="contacto_empresas" class="formulario"> 
        		<div class="row">
        			<div class="col-md-2"><label>Nombre: </label></div>
        			<div class="col-md-8">
        				<input type="text" class="form-control" name="nombre" id="nombre" placeholder="Empresa SL" required>
        			</div>
        		</div><br>      
        		<div class="row">
        			<div class="col-md-2"><label>Correo: </label></div>
        			<div class="col-md-8">
        				<input type="email" class="form-control" name="correo" id="correo" placeholder="alguien@alguien.com" required>
        			</div>
        		</div><br>  	
        		<div class="row">
        			<div class="col-md-2"><label>Teléfono: </label></div>
        			<div class="col-md-8">
        				<input type="number" class="form-control" name="telefono" id="telefono" placeholder="Introduce tu teléfono (opcional)">
        			</div>
        		</div><br>
        		<div class="row">
        			<div class="col-md-2"><label>Mensaje: </label></div>
        			<div class="col-md-8">
        				<textarea class="form-control" name="mensaje" id="mensaje" rows="4" required placeholder="Hablanos sobre tu marca y que propuesta quieres hacernos llegar. Te responderemos lo más pronto posible."></textarea>
        			</div>
        		</div><br>
        		<div class="row">
        			<div class="col-md-12">
        				<div class="recaptcha-container">
        					<p><strong>Verificación de seguridad:</strong></p>
        					<div class="g-recaptcha" data-sitekey="6LfyTegrAAAAAEGfm7q5Huhcej7EQFEIM9yCU8JS"></div>
        					<p><small>Por favor, completa la verificación reCAPTCHA para enviar el formulario.</small></p>
        				</div>
        			</div>
        		</div><br>
				<div class="text-center">
					<input class="btn btn_codigo_amigo" value="Enviar Mensaje" type="submit">
				</div>
            </form>
        </div>
    </div>
</div>

<?php get_footer(); ?>