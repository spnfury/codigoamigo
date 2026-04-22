
<?php
session_start();

get_header_new($title, $description);

$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com'); die();
}

    $lista_codigos_total = get_all_codigos_panel_control(); 
    
    
    /*echo "<pre>";
    print_r($lista_codigos_total);*/
?>

<div class="container-fluid container-top container-bottom page_panel_control text-center"> 

	<?php bloque_titulo_pagina("PANEL DE CONTROL DE CÓDIGOS"); ?>
	<div class="bloque_subtitulo_pagina">
		<p style="font-size: 20px; font-weight: bold;">Hay <?php echo count($lista_codigos_total); ?> códigos en el sistema</p>
	</div><br>
	<div class="row text-left">
		<a href="panel-de-control-marcas">Ir al panel de marcas</a><br>
		<a href="panel-de-control-usuarios">Ir al panel de usuarios</a>
	</div>	

	<div class="row">	
		<table class="table table-hover table_datatable hide" style="background: white;">
			<thead style="background: #3466ff !important; color: white; font-size: 14px;">
				<tr><td>Fecha</td><td>Destacado</td><td>Data Código</td><td>ID Usuario</td><td>Clave marca</td><td>Código</td><td>Descripción</td><td>Acciones</td></tr>
			</thead>
			<tbody>
				 <?php foreach ($lista_codigos_total as $item) { ?>
					<tr>
						<td><?php echo $item["fecha"]; ?></td>
						<td>
							<?php if($item["destacado"] != 0){ echo $item["destacado"]." - destacado"; } ?>														
						</td>
						<td>
							<p><b>ID: </b><?php echo $item["id"]; ?></p>
							<span><b>Estado: </b></span><span class="label <?php echo $item["estado_class"]; ?>"><?php echo $item["estado_string"]; ?></span><br>
							<p><b>Vistas: </b> <?php echo $item["totalclicks"]; ?></p>
						</td>	
						<td>
							<p><a href=""><?php echo $item["id_usuario"]; ?></a></p>										
						</td>
						<td>
							<p class="label <?php echo $item["marca_class"]; ?>"><?php echo $item["marca_string"]; ?></p>
							<textarea class="form-control" ><?php echo $item["marca"]; ?></textarea>
						</td>
						<td><textarea class="form-control"><?php echo $item["codigo"]; ?></textarea></td>
						<td>
							<p class="label <?php echo $item["validacion_class"]; ?>"><?php echo $item["validacion_string"]; ?></p>			
							<textarea class="form-control" id="" cols=110 rows=6><?php echo $item["descripcion"]; ?></textarea>
						</td>
						<td style="width:5%">
							<?php /*?><button class="btn btn-warning actualizar_codigo_listado" data-id-codigo="<?php echo $item["id"]; ?>">Actualizar</button><? */ ?>
							<button class="btn btn-primary editar_codigo" data-destacado="<?php echo $item["destacado"]; ?>" data-id-codigo="<?php echo $item["id"]; ?>" data-marca="<?php echo $item["marca"]; ?>" data-descripcion="<?php echo $item["descripcion"]; ?>" data-estado="<?php echo $item["estado"]; ?>">Editar</button>
							
							<button class="btn btn-warning desactivar_codigo" data-id-codigo="<?php echo $item["id"]; ?>">Desactivar</button>
							<button class="btn btn-danger borrar_codigo" data-id-codigo="<?php echo $item["id"]; ?>">Eliminar</button>	
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

	<div id="modal_editar_codigo" class="modal fade" role="dialog">
  		<div class="modal-dialog">
    		<div class="modal-content">
      			<div class="modal-header">
        			<button type="button" class="close" data-dismiss="modal">&times;</button>
        			<h4 class="modal-title">Editar código</h4>
      			</div>
      			<div class="modal-body container" style="padding: 50px;">  
      				<div class="row">
        				<div class="col-md-2"><b>ID Codigo: </b></div>
        				<div class="col-md-4">
        					<span id="txt_id_codigo"></span>
        				</div>
        			</div><br>     			
        			<div class="row">
        				<div class="col-md-2"><b>Estado: </b></div>
        				<div class="col-md-4">
        					<select class="form-control" id="estado_codigo">
                				<option value="0">Activo</option>
                				<option value="-1">Desactivado por administrador</option>
                				<option value="-2">Desactivado por usuario</option>
                				<option value="-3">Desactivado por antigüedad</option>
                			</select>
        				</div>
        			</div><br>
        			<?php /*?><div class="row">
        				<div class="col-md-2"><b>Destacado: </b></div>
        				<div class="col-md-4">
        					<select class="form-control" id="destacado">
                				<option value="0">Sin Destacar</option>
                				<option value="1">Destacado</option>
                			</select>
        				</div>
        			</div><br>*/?>
        			<div class="row">
        				<div class="col-md-2"><b>Marca: </b></div>
        				<div class="col-md-4">
        					<input type="text" class="form-control" id="marca_codigo" />
        				</div>
        			</div><br>
        			<div class="row">
        				<div class="col-md-2"><b>Descripción: </b></div>
        				<div class="col-md-4">
        					<textarea  cols="" class="form-control" id="descripcion_codigo" rows="15" cols="200"></textarea>
        				</div>
        			</div><br>
        			<div class="row text-center">
        				<div class="col-md-6">
        					<button class="btn btn-primary actualizar_codigo">Editar código</button>
        				</div>        				
        			</div>        			
      			</div>
  				<div class="modal-footer">
        			<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      			</div>
			</div>
  		</div>
	</div>
	
</div>

<?php get_footer(); ?>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.js"></script>

<script>

jQuery(document).ready(function($) {

	$(document).on('click', '.actualizar_codigo_listado', function() {

		var id_codigo = $(this).attr("data-id-codigo");
		
		
		var nombre = $("#nombre_" + id_codigo).val();
		var nombre_clave = $("#nombre_clave_" + id_codigo).val();		
		var categoria = $("#categoria_" + id_codigo).val();
		var categoria_clave = $("#categoria_clave_" + id_codigo).val();

		
		alert($("#descripcion_codigo").val());
		$.ajax({
	        type: "POST",
	        url: "/myphp/ajax_actions.php",
	        data: {
	       	 	metodo: "actualizar_codigo",
		       	 id_codigo: $(this).attr("data-id-codigo"),  
		            estado: $("#estado_codigo").val(),
		            /*destacado: $("#destacado").val(),*/
		            marca: $("#marca_codigo").val(),
		            descripcion: $("#descripcion_codigo").val(),
	        }, 
	        cache: false,
	        success: function(data){
		        alert("Marca actualizada correctamente");
 		        //location.reload();     
	        }
		});

	});
	
		$(document).on('click', '.actualizar_codigo', function() {

		$.ajax({
	        type: "POST",
	        url: "/myphp/ajax_actions.php",
	        data: {
	       	 	metodo: "actualizar_codigo",
	            id_codigo: $(this).attr("data-id-codigo"),  
	            estado: $("#estado_codigo").val(),
	            destacado: $("#destacado").val(),
	            marca: $("#marca_codigo").val(),
	            descripcion: $("#descripcion_codigo").val(),
	        }, 
	        cache: false,
	        success: function(data){
		        alert("Código actualizado correctamente");    
	        }
		});
		
	});

	/*$(document).on('click', '.actualizar_codigo', function() {

		$.ajax({
	        type: "POST",
	        url: "/myphp/ajax_actions.php",
	        data: {
	       	 	metodo: "actualizar_codigo",
	            id_codigo: $(this).attr("data-id-codigo"),  
	            estado: $("#estado_codigo").val(),
	            destacado: $("#destacado").val(),
	            marca: $("#marca_codigo").val(),
	            descripcion: $("#descripcion_codigo").val(),
	        }, 
	        cache: false,
	        success: function(data){
		        alert("Código actualizado correctamente");    
	        }
		});
		
	});*/

	$(document).on('click', '.editar_codigo', function() {

		var id_codigo = $(this).attr("data-id-codigo");
		var estado = $(this).attr("data-estado");
		var destacado = $(this).attr("data-destacado");	
		var marca = $(this).attr("data-marca");
		var descripcion = $(this).attr("data-descripcion");

		$("#txt_id_codigo").html(id_codigo);
		document.getElementById('estado_codigo').value = estado;		
		$("#marca_codigo").val(marca);
		$("#destacado").val(destacado);
		$("#descripcion_codigo").val(descripcion);
		$(".actualizar_codigo").attr("data-id-codigo", id_codigo);
		
		$("#modal_editar_codigo").modal();
		
	});

	$(document).on('click', '.desactivar_codigo', function() {
		
		var r = confirm("¿Deseas desactivar este código?");		
		if (r == true) {

	 		$.ajax({
    	        type: "POST",
    	        url: "/myphp/ajax_actions.php",
    	        data: {
    	       	 	metodo: "desactivar_codigo",
    	            id_codigo: $(this).attr("data-id-codigo"),  
    	        }, 
    	        cache: false,
    	        success: function(data){
        	        alert("Código desactivado");
    	        }
    		});
						
		}
		
	});

	$(document).on('click', '.borrar_codigo', function() {
		
		var r = confirm("¿Deseas eliminar este código?");		
		if (r == true) {

			$(this).parent().parent().addClass("hide");
	 		$.ajax({
    	        type: "POST",
    	        url: "/myphp/ajax_actions.php",
    	        data: {
    	       	 	metodo: "borrar_codigo",
    	            id_codigo: $(this).attr("data-id-codigo"),  
    	        }, 
    	        cache: false,
    	        success: function(data){
        	        alert("Código eliminado");    	        	
    	        }
    		});
						
		}
		
	});
	
});

</script>