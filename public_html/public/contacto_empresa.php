<?php get_header_new($title, $description); ?>



<?php echo "no";die; ?>


 <script src="https://www.google.com/recaptcha/api.js"></script>
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
        		
        		<button class="g-recaptcha" 
        data-sitekey="reCAPTCHA_site_key" 
        data-callback='onSubmit' 
        data-action='submit'>Submit</button>
        		
				<div class="text-center">
					<input class="btn btn_codigo_amigo" value="Enviar Mensaje" type="submit">
				</div>
            </form>
        </div>
    </div>
</div>

<?php get_footer(); ?>