<?php 
    get_header_modern($title, $description);
    global $data_usuario;
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="container">
	<div class="row" >
      	<div class="title text-center login-inner">
        	<h1><span>Edición de perfil</span></h1>
        </div>                
        <div class="col-md-4 text-center">
            <div class="dropdown">
                <button class="btn btn-light btn-sm rounded-circle shadow-sm menu-dots" type="button" id="photoOptionsMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow" aria-labelledby="photoOptionsMenu">
                    <form enctype="multipart/form-data" action="cambiar_foto_usuario" method="POST">
                        <label for="uploadedfile" class="dropdown-item d-flex align-items-center">
                            <i class="fas fa-camera text-primary mr-2"></i>
                            <span>Cambiar foto</span>
                        </label>
                        <input type="file" name="uploadedfile" id="uploadedfile" class="d-none" required accept="image/*">
                    </form>
                    <div class="dropdown-divider"></div>
                    <button class="dropdown-item d-flex align-items-center" type="button" id="eliminar_foto">
                        <i class="fas fa-trash-alt text-danger mr-2"></i>
                        <span>Eliminar foto</span>
                    </button>
                </div>
            </div>

            <?php 
                if(isset($_SESSION["msg"]) && $_SESSION["msg"] != "") {
                    echo '<div class="alert-box"><div class="alert alert-danger fade in">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
                        <strong>Error al subir la nueva foto, debe ser un archivo imagen menor de 2MB.</strong> 
                      </div></div>';
                } 
            ?> 
            
            <img style="width: 100%;" src="<?php echo $data_usuario['img']; ?>">
            <p id="mensaje"><?php echo isset($msg) ? $msg : ''; ?></p>
        </div>
        <div class="col-md-6">
            <form id="editar_perfil">
                <div class="form-group row align-items-center">
                    <div class="col-md-3 text-right">
                        <label class="mb-0">Nombre:</label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" name="nombre" id="nombre" required class="form-control" value="<?php echo $data_usuario['username']; ?>"> 
                    </div>
                </div>
                
                <div class="form-group row align-items-center">
                    <div class="col-md-3 text-right">
                        <label class="mb-0">Correo:</label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" name="correo" id="correo" disabled class="form-control" value="<?php echo $data_usuario['mail']; ?>"> 
                        <small class="form-text text-muted">El correo no se puede modificar</small>
                    </div>
                </div>
                
                <div class="form-group row align-items-center">
                    <div class="col-md-3 text-right">
                        <label class="mb-0">Teléfono:</label>
                    </div>
                    <div class="col-md-8">
                        <input type="tel" name="telefono" id="telefono" class="form-control" value="<?php echo $data_usuario['telefono']; ?>">
                    </div>
                </div>

                <div class="form-group row align-items-center">
                    <div class="col-md-3 text-right">
                        <label class="mb-0">WhatsApp:</label>
                    </div>
                    <div class="col-md-8">
                        <input type="tel" name="whatsapp" id="whatsapp" class="form-control" value="<?php echo $data_usuario['whatsapp']; ?>">
                        <small class="form-text text-muted">Incluir código de país (ej: +34)</small>
                    </div>
                </div>
                
                <div class="form-group row align-items-center">
                    <div class="col-md-3 text-right">
                        <label class="mb-0">Contraseña:</label>
                    </div>
                    <div class="col-md-8">
                        <input type="password" name="pass" id="pass" required class="form-control" value="<?php echo $data_usuario['pass']; ?>">  
                    </div>
                </div>
                
                <div class="form-group row align-items-center">
                    <div class="col-md-3 text-right">
                        <label class="mb-0">Notificaciones:</label>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <label class="switch mb-0">
                                <input type="checkbox" name="notis" id="notis" <?php if($data_usuario['notis'] == 1) echo "checked"; ?>>
                                <span class="slider round"></span>
                            </label>
                            <span class="toggle-label">Recibir notificaciones</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group row align-items-center">
                    <div class="col-md-3 text-right">
                        <label class="mb-0">Comunicaciones:</label>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <label class="switch mb-0">
                                <input type="checkbox" name="email_comm" id="email_comm" <?php if($data_usuario['email_comm'] == 1) echo "checked"; ?>>
                                <span class="slider round"></span>
                            </label>
                            <span class="toggle-label">Recibir comunicaciones por email</span>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="modificar_usuario">
                        Guardar información
                    </button>
                </div>  
            </form>          
        </div>
	</div>
</div>
            
<?php get_footer(); ?>

<style>
/* iOS style toggle switch */
.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
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
    background-color: #ccc;
    transition: .4s;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
}

input:checked + .slider {
    background-color: #2196F3;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.slider.round {
    border-radius: 34px;
}

.slider.round:before {
    border-radius: 50%;
}

.toggle-label {
    margin-left: 10px;
    vertical-align: super;
}

/* Form improvements */
.form-group {
    margin-bottom: 1.5rem;
}

.form-control {
    height: 45px;
    font-size: 16px;
    border-radius: 6px;
}

.btn-lg {
    padding: 12px 30px;
    font-size: 18px;
    border-radius: 6px;
}

label {
    font-weight: 500;
    color: #495057;
}

.text-right {
    text-align: right;
}

.toggle-label {
    margin-left: 12px;
    font-size: 15px;
    color: #495057;
}

small.form-text {
    color: #6c757d !important;
    font-size: 13px;
    margin-top: 4px;
}

.align-items-center {
    display: flex;
    align-items: center;
}

hr {
    border-color: #dee2e6;
}

/* Improved Dropdown menu styles */
.dropdown {
    position: absolute;
    right: 15px;
    top: 15px;
    z-index: 1;
}

.dropdown-toggle {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.dropdown-toggle:hover {
    background-color: #f8f9fa;
    transform: scale(1.1);
}

.dropdown-toggle:focus {
    box-shadow: none;
}

.dropdown-menu {
    min-width: 200px;
    padding: 0.5rem 0;
    margin-top: 0.5rem;
    border: none;
    border-radius: 8px;
}

.dropdown-item {
    padding: 0.75rem 1rem;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item label {
    cursor: pointer;
    margin: 0;
    display: flex;
    align-items: center;
    width: 100%;
}

.dropdown-divider {
    margin: 0.25rem 0;
}

/* Ensure the image container has proper spacing */
.col-md-4 {
    position: relative;
    margin-bottom: 2rem;
}

.col-md-4 img {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<script>

    $(document).ready(function() {

    	$("#eliminar_foto").click(function() {  
    		$.post("/remove_photo_user", { 'mail' : $('#correo').val()});
    		alert("Foto de perfil eliminada correctamente.");
    		location.reload();
    	});

    	$("#editar_perfil").submit(function(event) {

    		event.preventDefault();
    		event.stopPropagation();

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
    			success: function(data){
					alert("Usuario modificado correctamente");
					location.reload();
    			}
    		});

    	});
    	
        $("#uploadedfile").change(function() {
            if (this.files && this.files[0]) {
                $(this).closest('form').submit();
            }
        });
    });

</script>