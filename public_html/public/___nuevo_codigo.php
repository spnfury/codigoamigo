<?php 
// Usar header personalizado sin footer CSS para evitar puntos de fuga
get_header_new($title, $description, $image_src, $author); 

// Agregar CSS para ocultar cualquier elemento de footer que pueda aparecer
?>
<style>
/* Ocultar footer y elementos relacionados en la página de nuevo código */
.footer, 
.footer-modern, 
.footer-main, 
.footer-links, 
.telegram-float-new,
.footer-top,
.footer-bottom {
    display: none !important;
}

/* Asegurar que el contenido principal ocupe toda la pantalla */
body {
    min-height: 100vh;
}

.container-top {
    min-height: calc(100vh - 200px);
}
</style>

<?php if($_GET["ayuda"] == "") { ?>
<p>Estamos en mantenimiento. Volveremos en breve</p>
<?php } else { ?>

<style>

.easy-autocomplete-container li {
	/* height: 60px; */
}

</style>

<div class="container container-top container-bottom">
    <div class="row">    
    	<div class="col-md-12 col-xs-12 text-center"><h1 class="title_page">Publicar código</h1></div>
    	<div class="col-md-12 col-xs-12 text-center">
    		<p class="info_publicar_codigo">
        		Asegurate de introducir correctamente los siguientes datos, luego no podrán ser modificados !!!<br>
        		Si tienes algún problema, no dudes en ponerte en contacto con nosotros en <b>info@codigoamigo.com</b>
    		</p>
    	</div>       
        <div class="col-md-8 col-md-offset-2">                                
        	<form id="publicar_codigo" class="formulario"> 
        		<div class="row">
        			<div class="col-md-4 text-center"><label>Marca o Servicio: </label></div>
        			<div class="col-md-8">
        				<input type="text" class="form-control" name="marca" id="marca" placeholder="" required>
        				<p class="text-center">
                			¿No encuentras tu marca? <b>Escríbenos a info@codigoamigo.com y la añadimos !</b>
            			</p><br>
            			<span>Marca seleccionada</span>            			
            			<input type="text" class="form-control" name="marca_final" id="marca_final" readonly required>
            			<i>(Para seleccionarla, haz click en el desplegable de arriba con la opción que prefieras)</i>
        			</div>
        		</div><br>           		  
        		<div class="row">
        			<div class="col-md-4 text-center"><label>Beneficio económico: </label></div>
        			<div class="col-md-4 col-xs-6">
        				<input type="number" class="form-control" name="numero_beneficio" id="numero_beneficio" required>
    				</div>
    				<div class="col-md-4 col-xs-6">
        				<select id="texto_beneficio" name="texto_beneficio" class="form-control" required>
                            <option selected value="euros">euros</option> 
                            <option value="% de descuento">% de descuento</option>  
                            <option value="minutos gratis">minutos gratis</option>
                            <option value="horas gratis">horas gratis</option>
                            <option value="días gratis">días gratis</option>   
                            <option value="semanas gratis">semanas gratis</option>   
                            <option value="meses gratis">meses gratis</option>
                            <option value="euros para cheque regalo">euros para cheque regalo</option>
                        </select>
        			</div>
        		</div><br>  	
        		<div class="row" style="border: 3px solid #3466ff !important; padding: 15px; border-radius: 10px;">
        			<div class="col-md-4 text-center"><label>Código Promocional: </label></div>
        			<div class="col-md-8">
        				<input type="text" class="form-control" name="codigo" id="codigo" required placeholder="Introduce tu código promocional o url para compartir">
        			</div>
        		</div><br>
        		<div class="row">
        			<div class="col-md-4"><label>Descripción: </label></div>
        			<div class="col-md-8">
        				<textarea class="form-control" name="descripcion" id="descripcion" rows="4" required placeholder="Cuéntanos de que va tu código, que beneficios tienes tú y el nuevo usuario que lo usa ... etc. De esta manera, incentivarás a la comunidad a usar tu código amigo."></textarea>
        				<i style="color: red;">Te pedimos que no coloques el código amigo en la descripción. Va contra las normas y puedes ser baneado !!!</i>
        			</div>
        		</div><br>
        		<div class="row">
        			<div class="col-md-4 text-center"><label>Provincia: </label></div>
        			<div class="col-md-8">
        				<input type="text" class="form-control" name="provincia" id="provincia" required>
        			</div>
        		</div><br>
        		<div class="row">
        			<div class="col-md-4 text-center"><label>Localidad: </label></div>
        			<div class="col-md-8">
        				<input type="text" class="form-control" name="localidad" id="localidad" required>
        			</div>
        		</div><br>
        		<div class="row">
        			<div class="col-md-4 text-center"><label>Fecha de caducidad: </label></div>
        			<div class="col-md-8">
        				<input disabled type="date" class="form-control" name="fecha_caducidad" id="fecha_caducidad" />
        				<input type="checkbox" id="sin_fecha_caducidad" checked /> Sin fecha de caducidad
        			</div>
        		</div><br><hr>
				<div class="text-center">
					<input class="btn btn_codigo_amigo" value="Publicar código" type="submit">
				</div>
            </form>
        </div>
    </div>
</div>

<?php } ?>