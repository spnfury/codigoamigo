<?php 
    get_header_new($title, $description);
    
    $active_tab_login = $active_tab_register = $class_login = $class_register = "";
    if(isset($_GET["zona"]) && $_GET["zona"] == "login") { 
        $class_register = "hide"; 
        $active_tab_login = "active_tab"; 
    }
    else { 
        $class_login = "hide"; 
        $active_tab_register = "active_tab"; 
    } 
    
?> 
        
<div class="container container-top container-bottom">
    
    <div class="login-area">
        <div class="login-header">
        	<div class="row" style="margin: 0px;">
        		<div class="col-md-6 col-xs-6 text-center tab_registro <?php echo $active_tab_login; ?>"><a href='?zona=login'>Iniciar sesión</a></div>
        		<div class="col-md-6 col-xs-6 text-center tab_registro <?php echo $active_tab_register; ?>"><a href='/registro'>Registrarse</a></div>
        	</div>
    	</div>
	</div>    
    <div class="row">    
    	<div class="content" style="margin-top: 40px;">    		
    		<div id="login" class="<?php echo $class_login; ?>">
                <div class="login-inner">
                    <div class="title"><h1><span>Login</span></h1></div>
                    <div class="login-form">
                        <form role="form" name="login" action="login" method="POST">
                            <div class="form-details">
                                <label class="user"><input type="text" name="mail_login" placeholder="Correo" id="mail_login"></label>
                                <label class="pass"><input type="password" name="pass_login" placeholder="Contraseña" id="pass_login"></label>
                                
                                
                                <p class="text">
                        <a title="Recuperar contraseña" class="enlace" href="cambiar_password">Recuperar contraseña</a>
                    </p>
                    <br>
                                <button type="submit" class="btn btn_codigo_amigo btn-blue" onsubmit="">Iniciar sesión</button>
                            </div>
                            
                        </form>
                        <div style="padding-top: 20px;">
                        	<fb:login-button scope="public_profile,email" size="xlarge" onlogin="login_user_facebook();">Login con facebook</fb:login-button>
                    	</div>
                    </div>
                </div>
            </div>    		
			<div id="register" class="<?php echo $class_register; ?>">
               <div class="login-inner">
                    <div class="title"><h1><span>Bienvenido</span></h1></div>
                    <div class="login-form">
                        <form id="registrar_usuario" action="" method="POST">
                            <div class="form-details">
                                <label class="user">
                                    <input type="text" placeholder="Nombre completo" id="nombre" required>
                                </label>
                                <label class="mail">
                                    <input type="email" placeholder="Dirección de correo" id="correo" required>
                                </label>
                                <label class="pass">
                                    <input type="password" placeholder="Introduce tu contraseña" id="password" required>
                                </label>
                                <label class="pass">
                                    <input type="password" placeholder="Confirma tu contraseña" id="confirm_password" required>
                                </label>                                                        
                                <p class="help-block hide" id="text_ayuda">La contraseña debe tener un mínimo de 8 caracteres</p>                                
                                <div class="text-center">
                                	<input style="width: 20px;" type="checkbox" required /> <span style="font-size: 14px;"><span>Acepto</span> <a href='politica-de-privacidad'>la política de privacidad y protección de datos de <b>Código Amigo</b></a></span>
                                </div>                                                        
                            </div> 
                             
                        	<button type="submit" class="btn btn_codigo_amigo btn-blue" />Registrar usuario</button>
                        	
                        	<hr>
                        	<div class="text-center">
                        		<p style="font-size: 20px;"> O ... </p>
                        	</div>
                            <div class="tapa"></div>
                            <div class="zona_facebook" style="margin-top: 10px;">
                            	<fb:login-button readonly scope="public_profile,email" size="xlarge" onlogin="login_user_facebook();">Registro con facebook</fb:login-button>
                        	</div>
                        </form>
                    </div>
                </div>
            </div>                        
    	</div>
    </div>
    
</div>

<?php get_footer(); ?>

<script>

$(document).ready(function() {
	
    $("#registrar_usuario").submit(function(event) {
    	
    	event.preventDefault();
    	event.stopPropagation();

    	var nombre = $("#nombre").val();
    	var correo = $("#correo").val();
    	var password = $("#password").val();
    	var confirm_password = $("#confirm_password").val();

    	var tamaño_password = password.length;

    	if(tamaño_password < 8) {
        	
        	alert("La contraseña debe tener un mínimo de 8 caracteres");
        	$("#password").focus();
        	
    	} else if(password != confirm_password) {
        	
    		alert("Las contraseñas deben ser iguales");
        	$("#password").focus();
        	
    	} else {

        	$.ajax({
        		type: "POST",
        		url: "/myphp/ajax_actions.php",
        		data: {
        			metodo: "registrar_usuario",
        			nombre: nombre,
        			correo: correo,
        			password: password,
        			origin: "web",
        		}, 
        		cache: false,
        		success: function(data){
            		
            		if(data == "trobat") { 
            			alert("Este correo ya existe. Prueba con otro !!!");
            			$("#correo").focus(); 
        			} else {
        				ga('send', 'event', 'Formulario registro usuario', 'nuevo_usuario', 'web');
            			window.location.href = "/bienvenido";
        			}
        		}
        	});
    		
    	}
    
    });

});


</script>