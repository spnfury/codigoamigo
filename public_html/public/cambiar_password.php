<?php get_header_modern($title, $description); ?>

<div class="container">
	<div class="row" style="padding: 20px;">
		<?php if(isset($_REQUEST["msg"]) && $_REQUEST["msg"]){
            echo '<div class="alert-box"><div class="alert alert-success fade in">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
                <strong>Mensaje enviado correctamente!</strong> 
            </div></div>';
	      } elseif (isset($_REQUEST["msg_error"]) && $_REQUEST["msg_error"]){
              $error_message = "El correo introducido no aparece en nuestra base de datos";
              if ($_REQUEST["msg_error"] == "invalid_code") {
                  $error_message = "Código de recuperación inválido o expirado";
              }
              echo '<div class="alert-box"><div class="alert alert-danger fade in">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
                <strong>' . $error_message . '</strong> 
	          </div></div>'; } ?>
    	 <form role="form" name="" action="cambio_password" method="POST">
    		<div class="row">
    			<div class="col-md-8 col-md-offset-2 text-center">
    				<h1>Recuperar contraseña</h1>
    				<p style="font-size: 20px;">Introduzca la dirección de correo asociada a su cuenta.</p>
    				<div class="input-group input-group-md" style="padding: 20px;">
                  		<span class="input-group-addon">@</span>
                  		<input type="email" required class="form-control" name="mail" id="mail" placeholder="Introduce tu mail">
                	</div>
                	<button id="recuperar_pass" class="btn-custom btn-small" type="submit">Recuperar contraseña</button><br><br>
            		<span>Si ha dejado de utilizar la dirección de correo electrónico asociado a su cuenta, puede contactar con nosotros a través de nuestro <a href="contacto" class="enlace">formulario de contacto.</a></span>
        		</div>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>