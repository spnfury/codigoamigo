<?php get_header_new($title, $description); ?>

<div class="container">
    <div class="row" style="padding-bottom: 50px;">
        <div class="col-md-8 col-md-offset-2">            
            <div class="title text-center login-inner">
                <h1><span>BIENVENIDO A CÓDIGO AMIGO</span></h1>
            </div>
            <div class="ialert-box" id="registro_ok">
                <div class="alert alert-success fade in">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <strong>Usuario registrado correctamente.</strong> Por favor, confirme su correo electrónico.
                </div>
            </div><br>
            <p class="text-justify" style="font-size: 18px;">
            	Gracias por registrarte en Codigo Amigo. <b>Un correo de confirmación ha sido enviado a tu correo para que puedas activar tu usuario.</b><br>            	
        	</p><br>
        	<div class="text-center"><i>Revisa tu bandeja de entrada (y la de spam, por si acaso)</i></div>
        </div>            
    </div>
</div>

<?php get_footer(); ?>