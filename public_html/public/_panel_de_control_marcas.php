<?php
session_start();

get_header_new($title, $description);

$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://codigoamigo.com'); die();
}


    if($_GET["nuevas_0"]){
        $lista_marcas_total = get_all_marcas_panel_control(9999,'Marca nueva estado 0');
    }elseif($_GET["nuevas"]){
        $lista_marcas_total = get_all_marcas_panel_control(9999,'Marca nueva');
    }else if($_GET["sin_categoria"]){
        $lista_marcas_total = get_all_marcas_panel_control(9999,'sin_categoria');
    }else if($_GET["solo"]==1){
        $lista_marcas_total = get_all_marcas_panel_control(9999,'','1');
    }else if($_GET["sin_imagen"]==1){
        $lista_marcas_total = get_all_marcas_panel_control(9999,'sin_imagen');
    }else{
        $lista_marcas_total = get_all_marcas_panel_control(9999);
    }


    $lista_categorias = getCategorias();
    $lista_categorias = iterator_to_array($lista_categorias);


?>


<style>
table.dataTable tbody th, table.dataTable tbody td{
	padding: 8px 10px;
    max-width: 221px !important;
}
</style>



<div class="container-fluid container-top container-bottom page_panel_control text-center">


	<?php bloque_titulo_pagina("PANEL DE CONTROL DE MARCAS"); ?>
	<div class="bloque_subtitulo_pagina">
		<p style="font-size: 20px; font-weight: bold;">Hay <?php echo count($lista_marcas_total); ?> marcas en el sistema</p>
	</div><br>


	<div class="row text-left">
        <a href="panel-de-control-marcas?nuevas=1">Marcas Nuevas</a> |
        <a href="panel-de-control-marcas?nuevas_0=1">Marcas Nuevas Con Estado 0 (sin validar)</a> |
        <a href="panel-de-control-marcas?solo=0">Marcas sin códigos</a> |
        <a href="panel-de-control-marcas?solo=1">Marcas con un sólo código</a> |
        <a href="panel-de-control-marcas?sin_categoria=1">Marcas sin categoria</a> |
        <a href="panel-de-control-marcas?sin_imagen=1">Marcas sin imagen</a> |
	</div>

	<div class="row text-right">
		<a href="https://mda.codigoamigo.com/category/index">Ir al panel de categorias</a><br>
		<a href="panel-de-control-codigos">Ir al panel de codigoss</a><br>
		<a href="panel-de-control-usuarios">Ir al panel de usuarios</a>
	</div>

	<div class="row">
		<table class="table table-hover table_datatable hide" style="background: white;" id="table_marca">
			<thead style="background: #3466ff !important; color: white; font-size: 14px;">
				<tr><td>Fecha</td><td>ID Marca</td><td>Nº de códigos</td><td>Nombre</td><td>Categoría</td>
				<td style="width:30%;">Url Imagen</td>

				<td>Acciones</td></tr>
			</thead>
			<tbody>
				<?php foreach ($lista_marcas_total as $item) { ?>
					<tr>
						<td><?php echo $item["fecha"]; ?></td>
						<td><?php echo $item["id"]; ?>

						<?php if ($_SESSION["user_id"]=="58bd851da54e295b8b52f702"){?>

    						<br><br><a class="fusionar_marca" data-id-fusion="" data-id-marca="<?php echo $item["id"]; ?>">- Mover todos los códigos a (eliminará esta marca):</a>
							<br><input type="text" id="prefusion<?php echo $item["id"]; ?>">
						<?php } ?>
						</td>
						<td><?php echo $item["numero_codigos"]; ?></td>
						<td>
							<?php echo $item["nombre"]; ?>
							<input id="nombre_<?php echo $item["id"]; ?>" class="form-control" value="<?php echo $item["nombre"]; ?>" />
							<input id="nombre_clave_<?php echo $item["id"]; ?>" class="form-control" value="<?php echo $item["nombre_clave"]; ?>" />
						</td>
						<td>

							<select class="categoria_seleccionada" data-id_marca="<?php echo $item["id"]; ?>">
								<option value="-" style="margin: 40px;
    background: rgba(0, 0, 0, 0.3);
    color: #fff;
    text-shadow: 0 1px 0 rgba(0, 0, 0, 0.4);">sin categoria</option>
								<?php foreach($lista_categorias as $categoria){ ?>
									<option value="<?php echo $categoria["nombre_clave"];?>" <?php if($categoria["nombre_clave"] == $item["categoria_clave"]){ echo "selected=selected"; }?>><?php echo $categoria["nombre"];?></option>
								<?php } ?>
							</select>

							<br><?php echo $item["categoria"];?> - <?php echo $item["categoria_clave"];?>

						</td>
						<td>
						<input id="url_imagen_<?php echo $item["id"]; ?>" class="form-control" value="<?php echo $item["url_imagen"]; ?>" />

						<a target="_blank" href="https://www.codigoamigo.com/panel-de-control-marcas-edita-foto?id_marca=<?php echo $item["id"]; ?>&txt_imagen=<?php echo $item["url_imagen"]; ?>">Editar Foto</a>
						<br><br>
						<?php echo $item["url_imagen"]; ?>
						</td>

						<td>
							<a target="_blank" class="btn btn-success" href="/de-<?php echo $item["nombre_clave"];?>">Ver</a><br>
							<a data-id-marca="<?php echo $item["id"]; ?>" class="btn btn-success editar_marca" data-destacado="<?php echo $item["destacado"]; ?>" data-id-codigo="<?php echo $item["id"]; ?>" data-marca="<?php echo $item["marca"]; ?>" data-descripcion="<?php echo $item["descripcion"]; ?>" data-estado="<?php echo $item["estado"]; ?>" href="/panel-de-control-marcas-edita-marcas?id_marca=<?php echo $item["id"]; ?>" target="_blank">Editar Marca</a>
							<button data-id-marca="<?php echo $item["id"]; ?>" class="btn btn-success actualizar_marca">Guardar cambios</button>
							<button data-id-marca="<?php echo $item["id"]; ?>" class="btn btn-danger borrar_marca">Borrar</button>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>



</div>

<?php get_footer(); ?>

<script src="https://cdn.tiny.cloud/1/bmvmvgpnrz01vi0w81jmm87kvypms43aj5lxo0hg28mmxaow/tinymce/5/tinymce.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.js"></script>

<script>

jQuery(document).ready(function($) {
	//destruyo la tabla para crearla con opciones personalizadas (ya que no encuentro donde se crea inicialmente)

	table = $('#table_marca').DataTable();
	table.destroy();

	//añado la sopciones personalizadas de la tabla
	table = $('#table_marca').DataTable({
		"iDisplayLength": 100,
		paging: false,
		});


	$(document).on('change', '.categoria_seleccionada', function() {
    	$.ajax({
            type: "POST",
            url: "/myphp/ajax_actions.php",
            data: {
           	 	metodo: "update_marca",
	       	    id_marca: $(this).attr("data-id_marca"),
		       	marca_sel: $(this).find('option:selected').val(),
		       	marca_sel_txt: $(this).find('option:selected').text()
            },
            cache: false,
            success: function(data){
    	        alert("Marca actualizada correctamente");
    		        //location.reload();
            }
    	});

	});


	$(document).on('click', '.actualizar_marca', function() {

		tinyMCE.triggerSave();

		var id_marca = $(this).attr("data-id-marca");
		var nombre = $("#nombre_" + id_marca).val();
		var nombre_clave = $("#nombre_clave_" + id_marca).val();
		var categoria = $("#categoria_" + id_marca).val();
		var categoria_clave = $("#categoria_clave_" + id_marca).val();
		var url_imagen = $("#url_imagen_" + id_marca).val();
		var descripcion_e = $("#descripcion_marca_corta").val();
		var descripcion_e_larga = $("#descripcion_marca_larga").val();


		$.ajax({
	        type: "POST",
	        url: "/myphp/ajax_actions.php",
	        data: {
	       	 	metodo: "actualizar_marca",
	            id_marca: id_marca,
	            descripcion: descripcion_e,
	            descripcion_larga: descripcion_e_larga
	            /*nombre_clave: nombre_clave,
	            categoria: categoria,
	            categoria_clave: categoria_clave,
	            url_imagen: url_imagen,	*/
	        },
	        cache: false,
	        success: function(data){
		        alert("Marca actualizada correctamente");
 		        //location.reload();
	        }
		});

	});



	$(document).on('click', '.fusionar_marca', function() {


		var data_marca = $(this).attr("data-id-marca");

		$.ajax({
	        type: "POST",
	        url: "/myphp/ajax_actions.php",
	        data: {
	       	 	metodo: "fusiona_marcas",
	            id_marca: $(this).attr("data-id-marca"),
	            id_marca_fusiona: $('#prefusion'+data_marca).val()
	        },
	        cache: false,
	        success: function(data){
		        alert(data);
 		        /*location.reload();*/
	        }
		});




	});

	$(document).on('click', '.borrar_marca', function() {

		var actual = $(this);

		$.ajax({
	        type: "POST",
	        url: "/myphp/ajax_actions.php",
	        data: {
	       	 	metodo: "borrar_marca",
	            id_marca: $(this).attr("data-id-marca"),
	        },
	        cache: false,
	        success: function(data){

	        	actual.parent().hide();
	        	actual.parent().parent().hide();

		        //alert("Marca eliminada correctamente");
 		        /*location.reload();*/
	        }
		});

	});

});

</script>