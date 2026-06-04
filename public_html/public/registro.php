<?php
    get_header_new($title, $description);

    // Verificar si hay un código de referido
    $codigo_referido = isset($_GET['ref']) ? $_GET['ref'] : '';
    $nombre_referidor = '';
    $foto_referidor = 'https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg';

    if (!empty($codigo_referido)) {
        // Buscar el nombre y foto del usuario que hizo la invitación
        $collection_usuarios = getCollectionUsuarios();
        $referidor = $collection_usuarios->findOne(['codigo_referido' => $codigo_referido]);
        if ($referidor) {
            $nombre_referidor = $referidor['username'];
            if (!empty($referidor['img'])) {
                $foto_referidor = $referidor['img'];
            }
        }
    }

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

<style>
    .registro-invitacion {
        background: linear-gradient(135deg, #E30613 0%, #FF4D4D 100%);
        color: white;
        padding: 40px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .registro-invitacion::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
        animation: float 20s infinite linear;
    }

    @keyframes float {
        0% { transform: translateX(-100px) rotate(0deg); }
        100% { transform: translateX(100px) rotate(360deg); }
    }

    .invitacion-header h1 {
        font-size: 2.2em;
        font-weight: 800;
        margin-bottom: 15px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    .inviter-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid rgba(255,255,255,0.3);
        margin: 0 auto 20px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        background: white;
    }

    .inviter-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .invitacion-header p {
        font-size: 1.25em;
        margin-bottom: 30px;
        opacity: 0.9;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.4;
    }

    .ventajas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin: 25px 0;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }

    .ventaja-card {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 20px 15px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .ventaja-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }

    .ventaja-icon {
        font-size: 3em;
        margin-bottom: 20px;
        display: block;
    }

    .ventaja-card h3 {
        font-size: 1.3em;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .ventaja-card p {
        opacity: 0.9;
        line-height: 1.5;
    }

    .registro-form-container {
        background: white;
        border-radius: 25px;
        padding: 35px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        margin-top: -30px;
        position: relative;
        z-index: 2;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .form-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .form-header h2 {
        color: #333;
        font-size: 2em;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .form-header p {
        color: #666;
        font-size: 1.1em;
    }

    .registro-rapido {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
    }

    .registro-rapido-btn {
        max-width: 200px;
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 12px;
        font-size: 1.1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .registro-google {
        background: #4285F4;
        color: white;
    }

    .registro-google:hover {
        background: #357AE8;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(66, 133, 244, 0.3);
    }


    .registro-normal {
        border-top: 1px solid #eee;
        padding-top: 30px;
        margin-top: 30px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
    }

    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1em;
        transition: border-color 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: #E30613;
        box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
    }

    .referral-info {
        background: #FFF8E1;
        border: 2px solid #FFB74D;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .inviter-name {
        background: #E8F5E8;
        border: 1px solid #C3E6C3;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 10px;
        color: #2D5A2D;
        font-size: 0.95em;
        font-weight: 600;
    }

    .referral-code {
        color: #E65100;
        font-weight: 600;
    }

    .referral-code strong {
        color: #D84315;
        font-size: 1.05em;
    }

    .referral-code small {
        color: #BF360C;
        font-weight: 500;
    }


    .submit-btn {
        background: linear-gradient(135deg, #E30613, #FF4D4D);
        color: white;
        border: none;
        padding: 15px 40px;
        font-size: 1.1em;
        font-weight: 600;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(227, 6, 19, 0.3);
    }

    .login-link {
        text-align: center;
        margin-top: 20px;
        color: #666;
    }

    .login-link a {
        color: #E30613;
        text-decoration: none;
        font-weight: 600;
    }

    .login-link a:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .ventajas-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .registro-rapido {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .registro-invitacion {
            padding: 40px 0;
        }

        .invitacion-header h1 {
            font-size: 2.2em;
        }

        .ventaja-card {
            padding: 20px 15px;
        }

        .registro-form-container {
            padding: 25px;
            margin-top: -30px;
        }

        .form-header h2 {
            font-size: 1.7em;
        }
    }

    /* Animaciones para hacer la página más atractiva */
    .ventaja-card {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .ventaja-card:nth-child(1) { animation-delay: 0.1s; }
    .ventaja-card:nth-child(2) { animation-delay: 0.2s; }
    .ventaja-card:nth-child(3) { animation-delay: 0.3s; }
    .ventaja-card:nth-child(4) { animation-delay: 0.4s; }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .registro-form-container {
        animation: slideUp 0.8s ease-out 0.5s both;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<?php if (!empty($codigo_referido) && !empty($nombre_referidor)): ?>
<!-- Página especial para registro por invitación -->
<div class="registro-invitacion">
    <div class="container">
        <div class="invitacion-header">
            <div class="inviter-avatar">
                <img src="<?php echo htmlspecialchars($foto_referidor); ?>" alt="<?php echo htmlspecialchars($nombre_referidor); ?>">
            </div>
            <h1>🎉 ¡Te han invitado a Código Amigo!</h1>
            <p><strong><?php echo htmlspecialchars($nombre_referidor); ?></strong> te ha enviado una invitación exclusiva con <strong>5€ de regalo</strong> para que empieces a ahorrar</p>
        </div>

        <div class="ventajas-grid">
            <div class="ventaja-card" style="background: rgba(255, 255, 255, 0.25); border: 2px solid rgba(255, 255, 255, 0.4);">
                <span class="ventaja-icon">💰</span>
                <h3>5€ de Regalo</h3>
                <p>Recibe 5€ automáticos en tu cuenta al completar tu registro y verificar tu perfil</p>
            </div>
            <div class="ventaja-card">
                <span class="ventaja-icon">🎁</span>
                <h3>Códigos exclusivos</h3>
                <p>Accede a códigos de descuento verificados y actualizados diariamente</p>
            </div>
            <div class="ventaja-card">
                <span class="ventaja-icon">⚡</span>
                <h3>Registro rápido</h3>
                <p>Regístrate en segundos con Google. ¡Es completamente gratis!</p>
            </div>
            <div class="ventaja-card">
                <span class="ventaja-icon">🏆</span>
                <h3>Sin límites</h3>
                <p>Publica todos los códigos que quieras. No hay restricciones ni costos ocultos</p>
            </div>
        </div>
    </div>
</div>

<div class="registro-form-container">
    <div class="form-header">
        <h2>🚀 ¡Únete ahora!</h2>
        <p>Crea tu cuenta gratis y empieza a ahorrar dinero</p>
    </div>

    <div class="registro-rapido">
        <a href="#" class="registro-rapido-btn registro-google" onclick="iniciarRegistroGoogle()">
            <i class="fab fa-google"></i>
            Google
        </a>
    </div>

    <div class="registro-normal">
        <form id="registrar_usuario" action="" method="POST">
            <div class="referral-info">
                <?php if (!empty($nombre_referidor)): ?>
                <div class="inviter-name">
                    <strong>👤 Te invitó:</strong> <?php echo htmlspecialchars($nombre_referidor); ?>
                </div>
                <?php endif; ?>
                <div class="referral-code">
                    <strong>🎯 Código de referido aplicado:</strong> <?php echo htmlspecialchars($codigo_referido); ?>
                    <br>
                    <small>Se aplicará tu regalo de 5€ automáticamente tras la verificación</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Tu nombre completo">
                </div>
                <div class="form-group">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" required placeholder="tu@email.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="Mínimo 8 caracteres">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repite tu contraseña">
                </div>
            </div>

            <div class="form-group">
                <input type="hidden" id="codigo_referido" name="codigo_referido" value="<?php echo htmlspecialchars($codigo_referido); ?>">
                <label style="font-size: 0.9em; color: #666;">
                    <input type="checkbox" required style="margin-right: 8px;">
                    Acepto la <a href="/politica-de-privacidad" target="_blank" style="color: #E30613;">política de privacidad</a> y protección de datos de Código Amigo
                </label>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-rocket"></i> Crear cuenta gratis
            </button>

            <div class="login-link">
                ¿Ya tienes cuenta? <a href="/registro?zona=login">Inicia sesión aquí</a>
            </div>
        </form>
    </div>
</div>

<!-- Google One Tap -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

<script>
function iniciarRegistroGoogle() {
    // Inicializar Google One Tap
    google.accounts.id.initialize({
        client_id: '298004995594-1qm1qpjok7lq4lo1kdd45406j9rarcqd.apps.googleusercontent.com',
        callback: handleGoogleRegistrationResponse
    });
    
    // Mostrar el prompt de Google One Tap
    google.accounts.id.prompt();
}

function handleGoogleRegistrationResponse(response) {
    // Obtener el código de referido de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const codigoReferido = urlParams.get('ref') || '';
    
    // Enviar datos a la API de registro
    $.ajax({
        type: "POST",
        url: "/api/login.php",
        data: {
            metodo: "google_login",
            credential: response.credential,
            codigo_referido: codigoReferido
        },
        cache: false,
        success: function(data) {
            console.log('Respuesta Google registro:', data);
            
            if (data.success) {
                if (data.registered) {
                    // Usuario ya registrado - iniciar sesión
                    if (data.verified) {
                        window.location.href = "/bienvenido";
                    } else {
                        alert("Tu cuenta no está verificada. Revisa tu email.");
                    }
                } else {
                    // Usuario nuevo - registrar
                    registrarUsuarioGoogle(data.user, codigoReferido);
                }
            } else {
                alert("Error en el registro con Google: " + (data.error || "Error desconocido"));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en AJAX de Google:', error);
            alert('Error al procesar el registro con Google. Inténtalo de nuevo.');
        }
    });
}

function registrarUsuarioGoogle(userData, codigoReferido) {
    $.ajax({
        type: "POST",
        url: "/myphp/ajax_actions.php",
        data: {
            metodo: "registrar_usuario",
            nombre: userData.name,
            correo: userData.email,
            img: userData.picture,
            origin: "googleonetap",
            codigo_referido: codigoReferido
        },
        cache: false,
        success: function(data) {
            try {
                var regResponse;
                if (typeof data === 'string') {
                    regResponse = JSON.parse(data.trim());
                } else {
                    regResponse = data;
                }
                
                if (regResponse.success === false) {
                    if (regResponse.error == "trobat") {
                        alert("Este correo ya existe. Prueba con otro.");
                    } else {
                        alert("Error en registro: " + regResponse.error);
                    }
                } else {
                    // Registro exitoso
                    window.location.href = "/bienvenido";
                }
            } catch(e) {
                console.error('Error procesando respuesta de registro:', e);
                alert('Error al procesar el registro. Inténtalo de nuevo.');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en AJAX de registro:', error);
            alert('Error al procesar el registro. Inténtalo de nuevo.');
        }
    });
}


$(document).ready(function() {
    $("#registrar_usuario").submit(function(event) {
        event.preventDefault();
        event.stopPropagation();

        var nombre = $("#nombre").val();
        var correo = $("#correo").val();
        var password = $("#password").val();
        var confirm_password = $("#confirm_password").val();
        var codigo_referido = $("#codigo_referido").val();

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
                    codigo_referido: codigo_referido,
                    origin: "web",
                },
                cache: false,
                success: function(data){
                    if(data == "trobat") {
                        alert("Este correo ya existe. Prueba con otro.");
                        $("#correo").focus();
                    } else {
                        // Verificación por código: abrir modal con temporizador
                        mostrarModalVerificacion(correo);
                    }
                },
                error: function() {
                    alert("Error en el registro. Inténtalo de nuevo.");
                }
            });
        }
    });
});
</script>

<div id="registro-success" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; text-align: center; padding-top: 200px; color: white;">
    <div style="background: #28a745; padding: 40px; border-radius: 20px; display: inline-block; max-width: 400px;">
        <h2>¡Registro exitoso! 🎉</h2>
        <p>Te hemos enviado un email de activación. Revisa tu bandeja de entrada.</p>
    </div>
</div>

<?php else: ?>
<!-- Página normal de registro/login -->
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
                        <a title="Recuperar contraseña" class="enlace" href="cambiar_password">Recuperar contraseña</a> |
                        <a title="Solicitar nuevo email de activación" class="enlace" href="#" onclick="solicitarActivacion()">Solicitar activación</a>
                    </p>
                    <br>
                                <button type="submit" class="btn btn_codigo_amigo btn-blue" onsubmit="">Iniciar sesión</button>
                            </div>

                        </form>
                        <div style="padding-top: 20px;">
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
                                <?php if (isset($_GET['ref']) && !empty($_GET['ref'])): ?>
                                <label class="referral">
                                    <input type="text" placeholder="Código de referido (opcional)" id="codigo_referido" value="<?php echo htmlspecialchars($_GET['ref']); ?>" readonly style="background-color: #f0f0f0;">
                                    <small style="color: #666; font-size: 12px;">Código de referido detectado automáticamente</small>
                                </label>
                                <?php else: ?>
                                <label class="referral">
                                    <input type="text" placeholder="Código de referido (opcional)" id="codigo_referido">
                                    <small style="color: #666; font-size: 12px;">Si tienes un código de referido, introdúcelo aquí</small>
                                </label>
                                <?php endif; ?>
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
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<?php endif; ?>

<script>

$(document).ready(function() {
	
    $("#registrar_usuario").submit(function(event) {
    	
    	event.preventDefault();
    	event.stopPropagation();

    	var nombre = $("#nombre").val();
    	var correo = $("#correo").val();
    	var password = $("#password").val();
    	var confirm_password = $("#confirm_password").val();
    	var codigo_referido = $("#codigo_referido").val();

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
        			codigo_referido: codigo_referido,
        			origin: "web",
        		}, 
        		cache: false,
        		success: function(data){

            		if(data == "trobat") {
            			alert("Este correo ya existe. Prueba con otro !!!");
            			$("#correo").focus();
        			} else {
        				if (typeof ga === 'function') { ga('send', 'event', 'Formulario registro usuario', 'nuevo_usuario', 'web'); }
            			// Verificación por código: abrir modal con temporizador
            			mostrarModalVerificacion(correo);
        			}
        		}
        	});
    		
    	}
    
    });

});


</script>

<!-- ════════════ MODAL VERIFICACIÓN POR CÓDIGO ════════════ -->
<style>
#modalVerif{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;
  background:rgba(18,20,28,.78);backdrop-filter:blur(6px);padding:18px;}
#modalVerif.activo{display:flex;}
#modalVerif .verif-card{background:#fff;border-radius:22px;max-width:420px;width:100%;padding:34px 30px;
  box-shadow:0 24px 70px rgba(0,0,0,.4);text-align:center;animation:verifIn .35s cubic-bezier(.2,.8,.2,1);}
@keyframes verifIn{from{opacity:0;transform:translateY(18px) scale(.97)}to{opacity:1;transform:none}}
#modalVerif .verif-emoji{font-size:42px;line-height:1;margin-bottom:8px;}
#modalVerif h2{font-size:22px;font-weight:800;margin:0 0 6px;color:#161a23;}
#modalVerif .verif-sub{font-size:14px;color:#5b6472;line-height:1.5;margin:0 0 4px;}
#modalVerif .verif-mail{font-weight:700;color:#2d5bff;word-break:break-all;}
#modalVerif .verif-timer{display:inline-flex;align-items:center;gap:6px;margin:16px 0 4px;font-size:14px;
  font-weight:700;color:#2d5bff;background:#eef2ff;padding:6px 14px;border-radius:999px;}
#modalVerif .verif-timer.warning{color:#e6502e;background:#fdecea;}
#modalVerif .verif-inputs{display:flex;gap:12px;justify-content:center;margin:22px 0 6px;}
#modalVerif .verif-inputs input{width:58px;height:68px;text-align:center;font-size:30px;font-weight:800;
  border:2px solid #dfe3ec;border-radius:14px;color:#161a23;outline:none;transition:.15s;background:#fafbff;}
#modalVerif .verif-inputs input:focus{border-color:#2d5bff;box-shadow:0 0 0 4px rgba(45,91,255,.14);background:#fff;}
#modalVerif .verif-inputs.error input{border-color:#e6502e;animation:verifShake .4s;}
@keyframes verifShake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-7px)}40%,80%{transform:translateX(7px)}}
#modalVerif .verif-msg{font-size:13px;min-height:18px;margin:6px 0 0;color:#e6502e;font-weight:600;}
#modalVerif .verif-msg.ok{color:#1ca15a;}
#modalVerif .verif-btn{width:100%;margin-top:18px;border:none;border-radius:13px;padding:15px;font-size:16px;
  font-weight:700;color:#fff;background:linear-gradient(135deg,#2d5bff,#5b8cff);cursor:pointer;transition:.15s;}
#modalVerif .verif-btn:hover{filter:brightness(1.05);}
#modalVerif .verif-btn:disabled{opacity:.5;cursor:not-allowed;}
#modalVerif .verif-resend{margin-top:16px;font-size:13px;color:#5b6472;}
#modalVerif .verif-resend a{color:#2d5bff;font-weight:700;text-decoration:none;cursor:pointer;}
#modalVerif .verif-resend a.disabled{color:#aab1bd;pointer-events:none;}
#modalVerif .verif-ok-anim{font-size:60px;color:#1ca15a;margin:10px 0;animation:verifPop .5s cubic-bezier(.2,1.4,.4,1);}
@keyframes verifPop{from{transform:scale(0)}to{transform:scale(1)}}
</style>

<div id="modalVerif">
  <div class="verif-card">
    <div id="verifBody">
      <div class="verif-emoji">🎯</div>
      <h2>¡Ya casi estás dentro!</h2>
      <p class="verif-sub">Te hemos enviado un código de 4 dígitos a</p>
      <p class="verif-sub"><span class="verif-mail" id="verifMail"></span></p>

      <div class="verif-timer" id="verifTimer"><i class="fas fa-clock"></i> <span id="verifTimerTxt">10:00</span></div>

      <div class="verif-inputs" id="verifInputs">
        <input type="tel" inputmode="numeric" maxlength="1" autocomplete="one-time-code" data-i="0">
        <input type="tel" inputmode="numeric" maxlength="1" data-i="1">
        <input type="tel" inputmode="numeric" maxlength="1" data-i="2">
        <input type="tel" inputmode="numeric" maxlength="1" data-i="3">
      </div>
      <p class="verif-msg" id="verifMsg"></p>

      <button class="verif-btn" id="verifBtn" disabled>Verificar y entrar</button>

      <p class="verif-resend">¿No te ha llegado? <a id="verifResend">Reenviar código</a></p>
    </div>
  </div>
</div>

<script>
(function(){
  var correoVerif = '', timerInt = null, restante = 0, bloqueado = false;
  var $modal = document.getElementById('modalVerif');
  var inputs = Array.prototype.slice.call(document.querySelectorAll('#verifInputs input'));
  var $msg = document.getElementById('verifMsg');
  var $btn = document.getElementById('verifBtn');
  var $timerTxt = document.getElementById('verifTimerTxt');
  var $timer = document.getElementById('verifTimer');
  var $resend = document.getElementById('verifResend');

  window.mostrarModalVerificacion = function(correo){
    correoVerif = correo;
    document.getElementById('verifMail').textContent = correo;
    $modal.classList.add('activo');
    resetInputs();
    iniciarTimer(600); // 10 min
    setTimeout(function(){ inputs[0].focus(); }, 200);
  };

  // Usuario no verificado al hacer login → abrir modal y enviar código nuevo
  function abrirVerificacionDesdeLogin(){
    var el = document.getElementById('mail_login');
    var correo = el ? (el.value || '').trim() : '';
    if (!correo) { correo = prompt('Introduce tu correo para reenviarte el código de verificación:') || ''; }
    if (!correo) return;
    window.mostrarModalVerificacion(correo);
    // Pedir un código nuevo automáticamente
    fetch('/myphp/ajax_actions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'metodo=reenviar_codigo&correo='+encodeURIComponent(correo)})
      .then(function(r){return r.json();})
      .then(function(res){
        if(res && res.success){ $msg.classList.add('ok'); $msg.textContent='Te hemos enviado un código nuevo.'; }
      }).catch(function(){});
  }
  window.showActivationModal = abrirVerificacionDesdeLogin;
  window.solicitarActivacion = abrirVerificacionDesdeLogin;

  function resetInputs(){
    inputs.forEach(function(i){ i.value=''; i.disabled=false; });
    document.getElementById('verifInputs').classList.remove('error');
    $msg.textContent=''; $msg.classList.remove('ok');
    $btn.disabled=true; $btn.textContent='Verificar y entrar';
  }

  function iniciarTimer(seg){
    clearInterval(timerInt); restante = seg;
    pintarTimer();
    timerInt = setInterval(function(){
      restante--; pintarTimer();
      if(restante<=0){
        clearInterval(timerInt);
        $msg.textContent='El código ha caducado. Pide uno nuevo.';
        inputs.forEach(function(i){ i.disabled=true; });
        $btn.disabled=true;
      }
    },1000);
  }
  function pintarTimer(){
    var m=Math.floor(restante/60), s=restante%60;
    $timerTxt.textContent = m+':'+(s<10?'0':'')+s;
    $timer.classList.toggle('warning', restante<=60);
  }

  // Manejo de inputs: auto-avance, borrado, pegado
  inputs.forEach(function(inp, idx){
    inp.addEventListener('input', function(e){
      this.value = this.value.replace(/\D/g,'');
      if(this.value && idx<inputs.length-1) inputs[idx+1].focus();
      checkComplete();
    });
    inp.addEventListener('keydown', function(e){
      if(e.key==='Backspace' && !this.value && idx>0) inputs[idx-1].focus();
      if(e.key==='Enter') verificar();
    });
    inp.addEventListener('paste', function(e){
      e.preventDefault();
      var d=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,4);
      for(var k=0;k<d.length && k<inputs.length;k++) inputs[k].value=d[k];
      if(d.length) inputs[Math.min(d.length,inputs.length)-1].focus();
      checkComplete();
    });
  });

  function leerCodigo(){ return inputs.map(function(i){return i.value;}).join(''); }
  function checkComplete(){
    var c=leerCodigo();
    $btn.disabled = c.length!==4;
    if(c.length===4) verificar();
  }

  function verificar(){
    if(bloqueado) return;
    var codigo=leerCodigo();
    if(codigo.length!==4) return;
    bloqueado=true; $btn.disabled=true; $btn.textContent='Verificando…';
    $msg.textContent=''; $msg.classList.remove('ok');
    document.getElementById('verifInputs').classList.remove('error');
    fetch('/myphp/ajax_actions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'metodo=verificar_codigo&correo='+encodeURIComponent(correoVerif)+'&codigo='+encodeURIComponent(codigo)})
    .then(function(r){return r.json();})
    .then(function(res){
      bloqueado=false;
      if(res.success){
        clearInterval(timerInt);
        $msg.classList.add('ok'); $msg.textContent='';
        document.getElementById('verifBody').innerHTML =
          '<div class="verif-ok-anim">✓</div><h2>¡Cuenta verificada!</h2>'+
          '<p class="verif-sub">Entrando…</p>';
        setTimeout(function(){ window.location.href = res.redirect || '/'; }, 1200);
      } else {
        $btn.textContent='Verificar y entrar';
        document.getElementById('verifInputs').classList.add('error');
        $msg.textContent = res.mensaje || 'Código incorrecto.';
        resetTimeout();
        if(res.error==='caducado' || res.error==='no_codigo' || res.error==='max_intentos'){
          inputs.forEach(function(i){ i.disabled=true; });
        } else {
          inputs.forEach(function(i){ i.value=''; }); inputs[0].focus(); $btn.disabled=true;
        }
      }
    })
    .catch(function(){ bloqueado=false; $btn.textContent='Verificar y entrar'; $btn.disabled=false; $msg.textContent='Error de conexión. Inténtalo de nuevo.'; });
  }
  function resetTimeout(){ setTimeout(function(){ document.getElementById('verifInputs').classList.remove('error'); }, 500); }

  $btn.addEventListener('click', verificar);

  // Reenviar código con cooldown
  $resend.addEventListener('click', function(){
    if($resend.classList.contains('disabled')) return;
    $msg.classList.remove('ok'); $msg.textContent='Enviando nuevo código…';
    fetch('/myphp/ajax_actions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'metodo=reenviar_codigo&correo='+encodeURIComponent(correoVerif)})
    .then(function(r){return r.json();})
    .then(function(res){
      if(res.success){
        $msg.classList.add('ok'); $msg.textContent='¡Nuevo código enviado!';
        resetInputs(); iniciarTimer(res.expira_en || 600); inputs[0].focus();
        cooldownResend(60);
      } else if(res.error==='cooldown'){
        $msg.classList.remove('ok'); $msg.textContent='Espera '+(res.espera||30)+'s para reenviar.';
        cooldownResend(res.espera||30);
      } else {
        $msg.classList.remove('ok'); $msg.textContent=res.mensaje||'No se pudo reenviar.';
      }
    })
    .catch(function(){ $msg.textContent='Error de conexión.'; });
  });
  function cooldownResend(seg){
    $resend.classList.add('disabled');
    var orig=$resend.textContent, c=seg;
    var it=setInterval(function(){
      $resend.textContent='Reenviar código ('+c+'s)'; c--;
      if(c<0){ clearInterval(it); $resend.classList.remove('disabled'); $resend.textContent='Reenviar código'; }
    },1000);
  }
})();
</script>