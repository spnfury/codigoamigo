<?php

global $marca;



?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>PHP - jquery ajax crop image before upload using croppie plugins</title>
  <script src="/js/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.4/croppie.min.js"></script>



<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.4/croppie.min.css">
</head>
<body>

<?php
if($_REQUEST["submit"]==1){//SUBMIT


    $collection_marcas = getCollectionMarcas();

    $updateResult = $collection_marcas->updateOne(
        ['_id' => new \MongoDB\BSON\ObjectId($_REQUEST["id_marca"]) ],
        ['$set' => [
            'descripción' => $_REQUEST["descripcion_marca_corta"],
            'descripción_larga' => $_REQUEST["descripcion_marca_larga"],
            'video' => $_REQUEST["video_marca"]
        ]]
    );

    // Después de actualizar, redirigir a la misma página con el ID de la marca
    header("Location: /panel-de-control-marcas-edita-marcas?id_marca=" . $_REQUEST["id_marca"]);
    exit();
}
?>


<select name="marca_nombre_migrada" id="marca_nombre_migrada">

<?php foreach($lista_marcas_total as $total){?>

	<option value="<?php echo $total["nombre_clave"]; ?>" ><?php echo $total["nombre"]; ?></option>

<?php } ?>

</select>


<div class="container" style="position:relative;">
	<div class="panel panel-default">

	<form id="edita_marca" action="/panel-de-control-marcas-edita-marcas" method="post">
	<input type="hidden" id="submit" name="submit" value="1">

<div id="modal_editar_marca" class=" ">
  		<div class="">
		<div class="modal-content">

  			<div class="modal-header">
    			<button type="button" class="close" data-dismiss="modal">&times;</button>
    			<h4 class="modal-title">Editar Textos Marca</h4>
  			</div>

  			<div class="modal-body container" style="padding: 50px;">



  				<div class="row">
    				<div class="col-md-2"><b>ID Codigo: </b></div>
    				<div class="col-md-4">
    					<span id="txt_id_codigo"><input type="text" name="id_marca" value="<?php echo $marca["_id"]; ?>" readonly="readonly"></span>
    				</div>
    			</div><br>

    			<?php /*?>
    			<div class="row">
    				<div class="col-md-2"><b>Estado: </b></div>
    				<div class="col-md-4">
    					<select class="form-control" id="estado_codigo">
            				<option value="0">Activo</option>
            				<option value="-1">Pendiente</option>
            				<option value="-1">Desactivado por administrador</option>
            				<option value="-2">Desactivado por usuario</option>
            			</select>
    				</div>
    			</div><br>
    			</ */?>

    			<div class="row">
    				<div class="col-md-12"><h1>Descripción Corta: </h1></div>
    				<div class="col-md-12">
    					<textarea rows="1" cols="" class="form-control" id="descripcion_marca_corta" name="descripcion_marca_corta"><?php echo $marca["descripción"]; ?></textarea>
    				</div>
    			</div><br>

    			<div class="row">
    				<div class="col-md-12"><h1>Descripción Larga: </h1></div>
    				<div class="col-md-12">
    					<textarea rows="1" cols="" class="form-control" id="descripcion_marca_larga" name="descripcion_marca_larga"><?php echo $marca["descripción_larga"]; ?></textarea>
    				</div>
    			</div><br>

    			<div class="row">
    				<div class="col-md-12"><h1>Video: </h1></div>
    				<div class="col-md-12">
    					<textarea rows="1" cols="" class="form-control" id="video_marca" name="video_marca"><?php echo $marca["video"]; ?></textarea>
    				</div>
    			</div><br>

    			<div class="row text-center">
    				<div class="col-md-6">
    					<button type="submit" class="btn btn-primary actualizar_marca">Editar código</button>
    				</div>
    			</div>
    			</form>
  			</div>
		</div>
	</div>
</div>


	</div>
</div>

<script src="https://cdn.tiny.cloud/1/bmvmvgpnrz01vi0w81jmm87kvypms43aj5lxo0hg28mmxaow/tinymce/5/tinymce.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.js"></script>

<script>
jQuery(document).ready(function($) {
    tinyMCE.init({
        selector: "textarea",
        height: 200,
        menubar: false,
        plugins: [
            'code advlist autolink lists link image charmap print preview anchor textcolor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ],
        toolbar: 'code | undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        content_css: [
            '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
            '//www.tiny.cloud/css/codepen.min.css'
        ]
    });
});

$(document).on('click', '.editar_marca', function() {


		var id_marca = $(this).attr("data-id-marca");
		var estado = $(this).attr("data-estado");
		var destacado = $(this).attr("data-destacado");
		var marca = $(this).attr("data-marca");

		var descripcion = $(this).attr("data-descripcion_corta");
		var descripcion_larga = $(this).attr("data-descripcion_larga");

		$("#txt_id_codigo").html(id_marca);
		document.getElementById('estado_codigo').value = estado;
		$("#marca_codigo").val(marca);
		$("#destacado").val(destacado);
		$("#descripcion_marca_corta").val(descripcion);
		$("#descripcion_marca_larga").val(descripcion_larga);
		$(".actualizar_marca").attr("data-id-marca", id_marca);

		$("#modal_editar_marca").modal();

	});
</script>

</body>
</html>