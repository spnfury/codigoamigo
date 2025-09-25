<?php get_header_new($title, $description);  ?>


<div class="container container-top container-bottom">
    <div class="row">    
    	<div class="col-md-12 col-xs-12 text-center"><h1 class="title_page">Formulario de contacto</h1></div>        
        <div class="col-md-8 col-md-offset-2">                                
        	<form id="contacto_usuarios" class="formulario"> 
        		<div class="row">
        			<div class="col-md-2"><label>Nombre: </label></div>
        			<div class="col-md-8">
        				<input type="text" class="form-control" name="nombre" id="nombre" placeholder="Ana Gándara" required>
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
        				<textarea class="form-control" name="mensaje" id="mensaje" rows="4" required placeholder="Explícanos tus dudas, consultas o sugerencias. Te responderemos lo más pronto posible."></textarea>
        			</div>
        		</div><br>
				<div class="text-center">
					<input class="btn btn_codigo_amigo" value="Enviar Mensaje" type="submit">
				</div>
				
				<p class="text-justify">
                        		Si deseas ponerte en contacto con nosotros de forma más directa, puedes escribirnos a <b class="enlace">info@codigoamigo.com</b>
                        		donde estaremos encantados de atenderte.
                    		</p><br> 
            </form>
        </div>
    </div>
</div>

<?php get_footer(); ?>