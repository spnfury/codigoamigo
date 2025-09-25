<?php get_header_modern($title, $description); ?>

<div class="container-fluid main_entremedio">
	<form role="form" name="nuevo_password" action="actualizar_usuario" method="POST">
		<div class="row" style="padding: 30px;">
			<div class="row text-center" style="padding: 20px;">
					<h1>Nueva contraseña</h1>
			</div>
        	<div class="col-md-6 col-md-offset-3 text-center">
        		<?php if (isset($cadena_desencriptada) && !empty($cadena_desencriptada)): ?>
        		<div class="row" style="padding: 5px;">
    				<div class="input-group input-group-md">
              			<span class="input-group-addon">@</span>
              			<input readonly type="email" class="form-control" name="mail" id="mail" value="<?php echo htmlspecialchars($cadena_desencriptada); ?>">
            		</div>
        		</div>
        		<?php else: ?>
        		<div class="alert alert-danger">
        			<strong>Error:</strong> Código de recuperación inválido o expirado.
        		</div>
        		<?php endif; ?>
        		<div class="row" style="padding: 5px;">
            		<div class="input-group input-group-md">
            			<span class="input-group-addon">
                        	<span class="glyphicon glyphicon-lock"></span>
                      	</span>
                      	<input required class="form-control" name="nueva_password" id="nueva_password" type="password" placeholder="Introduzca su nueva contraseña">
            		</div>
        		</div>
        		<div class="row" style="padding: 5px;">
            		<div class="input-group input-group-md">
            			<span class="input-group-addon">
                        	<span class="glyphicon glyphicon-lock"></span>
                      	</span>
                      	<input required class="form-control" name="nueva_confirm_password" id="nueva_confirm_password" type="password" placeholder="Vuelva a introducir su nueva contraseña">
            		</div>
        		</div>
        		<p class="help-block hide" id="text_ayuda"></p>
    			<button id="" class="btn-custom btn-small pull-right" type="submit">Actualizar usuario</button>
    		</div>
        </div>
    </form>
</div>
    	       
<?php get_footer(); ?>