<?php
session_start();

get_header_new($title, $description);

$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://codigoamigo.com'); die();
}
    
  
    
    $lista_usuarios = get_all_users_panel_control();
?>

<div class="container-fluid container-top container-bottom page_panel_control text-center">  

	<?php bloque_titulo_pagina("PANEL DE CONTROL DE USUARIOS"); ?>
	<div class="bloque_subtitulo_pagina">
		<p style="font-size: 20px; font-weight: bold;">Hay <?php echo count($lista_usuarios); ?> usuarios en el sistema</p>
	</div><br>
	<div class="row text-left">
		<a href="panel-de-control-marcas">Ir al panel de marcas</a><br>
		<a href="panel-de-control-codigos">Ir al panel de codigos</a>
	</div>
	 
	<div class="row">	
		<table class="table table-hover table_datatable hide" style="background: white;">
			<thead style="background: #3466ff !important; color: white; font-size: 14px;">
				<tr><td>Fecha</td><td>Usuario</td><td>Nº de códigos</td><td>Nombre</td><td>Correo</td><td>Pass</td><td>Url Imagen</td><td>Acciones</td></tr>
			</thead>
			<tbody>
				<?php foreach ($lista_usuarios as $item) { ?>
					<tr>
						<td><?php echo $item["fecha"]; ?></td>
						<td>
							<p><?php echo $item["id"]; ?></p>
							<p><span>Origin_user: </span><span class="label label-primary"><?php echo $item["type"]; ?></span></p>
							<p><span>Estado_user: </span><span class="label <?php echo $item["estado_class"]; ?>"><?php echo $item["estado_string"]; ?></span></p>
						</td>
						<td><?php echo $item["numero_codigos"]; ?></td>
						<td>
							<textarea class="form-control"><?php echo $item["nombre"]; ?></textarea>							
						</td>
						<td><textarea class="form-control"><?php echo $item["correo"]; ?></textarea></td>
						<td><textarea class="form-control"><?php echo $item["contraseña"]; ?></textarea></td>
						<td><textarea class="form-control"><?php echo $item["img"]; ?></textarea></td>
						<td>
							<a class="btn btn-default" href="<?php echo enlace_usuario($item["nombre"], $item["id"]); ?>" target="_blank">Ir a página de códigos</a><br>
							<?php if($item["estado"] == "-1" || $item["estado"] == "-2") { ?>
								<button class="btn btn-success desbanear_usuario" data-id-usuario="<?php echo $item["id"]; ?>">Quitar baneo</button>
							<?php } else { ?>
								<?php if($item["estado"] != "-1") { ?>
									<button class="btn btn-warning baneo_temporal" data-id-usuario="<?php echo $item["id"]; ?>">Baneo temporal</button>
								<?php } ?>
								<?php if($item["estado"] != "-2") { ?>
									<button class="btn btn-danger baneo_definitivo" data-id-usuario="<?php echo $item["id"]; ?>">Baneo definitivo</button>
								<?php } ?>														
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	
</div>

<?php get_footer(); ?>

<script>

jQuery(document).ready(function($) {

	$(document).on('click', '.desbanear_usuario', function() {

		$.ajax({
	        type: "POST",
	        url: "/myphp/ajax_actions.php",
	        data: {
	       	 	metodo: "desbanear_usuario",
	            id_usuario: $(this).attr("data-id-usuario"),  
	        }, 
	        cache: false,
	        success: function(data){
		        alert("Usuario baneado temporalmente");   
	        }
		});

	});

	$(document).on('click', '.baneo_temporal', function() {

		$.ajax({
	        type: "POST",
	        url: "/myphp/ajax_actions.php",
	        data: {
	       	 	metodo: "baneo_temporal",
	            id_usuario: $(this).attr("data-id-usuario"),  
	        }, 
	        cache: false,
	        success: function(data){
		        alert("Usuario baneado temporalmente");   
	        }
		});

	});

	$(document).on('click', '.baneo_definitivo', function() {

		$.ajax({
	        type: "POST",
	        url: "/myphp/ajax_actions.php",
	        data: {
	       	 	metodo: "baneo_definitivo",
	            id_usuario: $(this).attr("data-id-usuario"),  
	        }, 
	        cache: false,
	        success: function(data){
		        alert("Usuario baneado definitivamente");   
	        }
		});

	});
	
});

</script>