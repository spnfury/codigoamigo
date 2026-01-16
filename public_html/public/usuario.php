<?php 
    get_header_modern($title, $description);
    $GLOBALS['header_modern_used'] = true; 
    global $data_usuario;
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="user-profile-container">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="profile-header mb-5 text-center">
                    <h1 class="profile-title">Mi Perfil</h1>
                    <p class="profile-subtitle">Gestiona tu información personal y preferencias</p>
                </div>

                <div class="row">
                    <!-- Columna Izquierda: Foto -->
                    <div class="col-md-4 mb-4">
                        <div class="profile-card text-center p-4 h-100">
                            <div class="avatar-container mb-3 position-relative mx-auto">
                                <img src="<?php echo $data_usuario['img']; ?>" alt="Foto de perfil" class="profile-img">
                                
                                <div class="avatar-overlay">
                                    <div class="dropdown">
                                        <button class="btn btn-icon btn-light rounded-circle shadow-sm" type="button" data-toggle="dropdown">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                        <div class="dropdown-menu shadow">
                                            <form id="form_cambiar_foto" enctype="multipart/form-data" action="/cambiar_foto_usuario" method="POST">
                                                <label for="uploadedfile" class="dropdown-item mb-0" style="cursor: pointer;">
                                                    <i class="fas fa-upload mr-2 text-primary"></i> Subir nueva foto
                                                </label>
                                                <input type="file" name="uploadedfile" id="uploadedfile" class="d-none" accept="image/jpeg,image/png,image/gif">
                                            </form>
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item text-danger" type="button" id="eliminar_foto">
                                                <i class="fas fa-trash-alt mr-2"></i> Eliminar foto
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="username mb-1"><?php echo $data_usuario['username']; ?></h3>
                            <p class="email-text mb-3"><?php echo $data_usuario['mail']; ?></p>
                            
                            <?php 
                                if (session_status() === PHP_SESSION_NONE) session_start();
                                if(isset($_SESSION["msg"]) && $_SESSION["msg"] != "") {
                                    echo '<div class="alert alert-info small rounded-pill py-2 px-3 mt-3">' . htmlspecialchars($_SESSION["msg"]) . '</div>';
                                    unset($_SESSION["msg"]);
                                } 
                            ?> 
                        </div>
                    </div>

                    <!-- Columna Derecha: Formulario -->
                    <div class="col-md-8">
                        <div class="profile-card p-4 p-md-5">
                            <form id="editar_perfil">
                                <h4 class="mb-4 form-section-title"><i class="fas fa-user-edit mr-2"></i> Información Personal</h4>
                                
                                <div class="form-group">
                                    <label>Nombre de usuario</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" name="nombre" id="nombre" required class="form-control modern-input" value="<?php echo $data_usuario['username']; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Correo electrónico</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="text" disabled class="form-control modern-input disabled" value="<?php echo $data_usuario['mail']; ?>">
                                        <input type="hidden" name="correo" id="correo" value="<?php echo $data_usuario['mail']; ?>">
                                    </div>
                                    <small class="form-text text-muted pl-1">Para cambiar tu correo, contacta con soporte.</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Teléfono</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                </div>
                                                <input type="tel" name="telefono" id="telefono" class="form-control modern-input" value="<?php echo $data_usuario['telefono']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>WhatsApp</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                                                </div>
                                                <input type="tel" name="whatsapp" id="whatsapp" class="form-control modern-input" value="<?php echo $data_usuario['whatsapp']; ?>" placeholder="+34...">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Contraseña</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        </div>
                                        <input type="password" name="pass" id="pass" required class="form-control modern-input" value="<?php echo $data_usuario['pass']; ?>">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4 border-secondary">

                                <h4 class="mb-4 form-section-title"><i class="fas fa-bell mr-2"></i> Preferencias</h4>

                                <div class="preference-item d-flex justify-content-between align-items-center mb-4 p-3 rounded">
                                    <div>
                                        <h6 class="mb-1 text-white">Notificaciones Push</h6>
                                        <small class="text-muted">Recibir alertas sobre nuevos chollos y ofertas.</small>
                                    </div>
                                    <label class="switch mb-0">
                                        <input type="checkbox" name="notis" id="notis" <?php if($data_usuario['notis'] == 1) echo "checked"; ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>

                                <div class="preference-item d-flex justify-content-between align-items-center mb-4 p-3 rounded">
                                    <div>
                                        <h6 class="mb-1 text-white">Comunicaciones por Email</h6>
                                        <small class="text-muted">Recibir boletines y promociones exclusivas.</small>
                                    </div>
                                    <label class="switch mb-0">
                                        <input type="checkbox" name="email_comm" id="email_comm" <?php if($data_usuario['email_comm'] == 1) echo "checked"; ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>

                                <div class="text-center mt-5">
                                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-lg btn-save" id="modificar_usuario">
                                        <i class="fas fa-save mr-2"></i> Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
            
<?php get_footer(); ?>

<style>
/* Modern Dark Theme Styles */
:root {
    --bg-dark: #2C2C2C;
    --bg-card: #383838; /* Lighter than background */
    --input-bg: #444444;
    --primary: #E30613;
    --text-white: #ffffff;
    --text-muted: #aaaaaa;
    --border-color: #555555;
}

body {
    background-color: var(--bg-dark);
    color: var(--text-white);
}

.user-profile-container {
    min-height: 80vh;
    padding-bottom: 3rem;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}

.profile-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-white);
    margin-bottom: 0.5rem;
}

.profile-subtitle {
    color: var(--text-muted);
    font-size: 1.1rem;
}

/* Cards */
.profile-card {
    background-color: var(--bg-card);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.05);
}

/* Avatar */
.avatar-container {
    width: 150px;
    height: 150px;
}

.profile-img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid var(--bg-dark);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.avatar-overlay {
    position: absolute;
    bottom: 5px;
    right: 5px;
}

.username {
    font-weight: 700;
    color: var(--text-white);
}

.email-text {
    color: var(--text-muted);
}

/* Forms */
.form-section-title {
    color: var(--primary);
    font-weight: 600;
    font-size: 1.2rem;
    border-bottom: 1px solid rgba(255,107,53,0.3);
    padding-bottom: 10px;
}

.form-group label {
    color: var(--text-white);
    font-weight: 500;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.modern-input {
    background-color: var(--input-bg) !important;
    border: 1px solid var(--border-color);
    color: var(--text-white) !important;
    height: 50px;
    border-radius: 0 8px 8px 0;
    font-size: 1rem;
}

.modern-input:focus {
    background-color: #4a4a4a !important;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(255,107,53,0.2);
}

.modern-input.disabled {
    background-color: #333333 !important;
    color: #888888 !important;
    cursor: not-allowed;
}

.input-group-text {
    background-color: #333333;
    border: 1px solid var(--border-color);
    border-right: none;
    color: var(--text-muted);
    border-radius: 8px 0 0 8px;
    min-width: 45px;
    justify-content: center;
}

.input-group:focus-within .input-group-text {
    border-color: var(--primary);
    color: var(--primary);
}

/* Toggles */
.preference-item {
    background-color: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.05);
    transition: background-color 0.2s;
}

.preference-item:hover {
    background-color: rgba(255,255,255,0.06);
}

.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 28px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #555;
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: var(--primary);
}

input:checked + .slider:before {
    transform: translateX(22px);
}

/* Button */
.btn-save {
    background: linear-gradient(45deg, var(--primary), #FF8C5A);
    border: none;
    border-radius: 50px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    font-size: 1rem;
    padding: 15px 40px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255,107,53,0.4) !important;
    background: linear-gradient(45deg, #ff5722, #E30613);
}

/* Utilities */
.text-muted {
    color: #999 !important;
}

.border-secondary {
    border-color: rgba(255,255,255,0.1) !important;
}

/* Responsive */
@media (max-width: 768px) {
    .profile-title { font-size: 2rem; }
    .profile-card { padding: 1.5rem !important; }
}
</style>

<script>
    $(document).ready(function() {
        // Toggle password visibility
        $('.toggle-password').click(function() {
            var input = $('#pass');
            var icon = $(this).find('i');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

    	$("#eliminar_foto").click(function() {  
    	    if(confirm("¿Estás seguro de eliminar tu foto de perfil?")) {
        		$.post("/remove_photo_user", { 'mail' : $('#correo').val()});
        		location.reload();
    	    }
    	});

    	$("#editar_perfil").submit(function(event) {
    		event.preventDefault();
    		
    		var btn = $("#modificar_usuario");
    		var originalText = btn.html();
    		btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...');

    		var notis_val = $("#notis:checked").val() != undefined ? '1' : '0';
    		var email_comm_val = $("#email_comm:checked").val() != undefined ? '1' : '0';

    		$.ajax({
    			type: "POST",
    			url: "/myphp/ajax_actions.php",
    			data: {
    				metodo: "editar_perfil",
    				notis: notis_val,
    				email_comm: email_comm_val,
    				nombre: $("#nombre").val(),
    				correo: $("#correo").val(),
    				password: $("#pass").val(),
    				telefono: $("#telefono").val(),
    				whatsapp: $("#whatsapp").val(),
    			}, 
    			cache: false,
    			dataType: 'json',
    			success: function(data){
    			    btn.prop('disabled', false).html(originalText);
    				if (data && data.success) {
    					// Mostrar toast o alerta bonita
    					alert(data.message || "Usuario modificado correctamente");
    					location.reload();
    				} else {
    					alert(data.message || "Error al modificar el usuario");
    				}
    			},
    			error: function(xhr, status, error) {
    			    btn.prop('disabled', false).html(originalText);
    				try {
    					var response = JSON.parse(xhr.responseText);
    					alert(response.message || "Error al modificar");
    				} catch(e) {
    					alert("Error de conexión. Inténtelo de nuevo.");
    				}
    			}
    		});
    	});
    	
        $("#uploadedfile").change(function() {
            if (this.files && this.files[0]) {
                if (this.files[0].size > 2000000) {
                    alert("El archivo es demasiado grande. Máximo 2MB.");
                    $(this).val('');
                    return;
                }
                var fileType = this.files[0].type;
                if (!fileType.match('image.*')) {
                    alert("Solo se permiten imágenes (JPG, PNG).");
                    $(this).val('');
                    return;
                }
                $('.dropdown-menu').removeClass('show');
                $("#form_cambiar_foto").submit();
            }
        });
    });
</script>