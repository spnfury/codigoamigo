<?php


?><html lang="en">
<head>
  <title>PHP - jquery ajax crop image before upload using croppie plugins</title>
  <script src="/js/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.4/croppie.min.js"></script>



<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.4/croppie.min.css">
</head>



<body>


<div class="container">
	<div class="panel panel-default">
	  <div class="panel-heading">Image UPload</div>
	  <div class="panel-body">


<input style="width:100%;" id="txt_imagen" type="text" value="<?php echo $_GET["txt_imagen"]; ?>" />
	  	<input type="buttoon" id="recarga">

	  	<div class="row">
	  		<div class="col-md-4 text-center">
				<div id="upload-demo" style="width:350px"></div>
	  		</div>
	  		<div class="col-md-4" style="padding-top:30px;">
				<strong>Select Image:</strong>
				<br/>
				<input type="file" id="upload">
				<br/>
				<button class="btn btn-success upload-result">Upload Image</button>
	  		</div>
	  		<div class="col-md-4" style="">
				<div id="upload-demo-i" style="background:#e1e1e1;width:300px;padding:30px;height:300px;margin-top:30px"></div>
	  		</div>
	  	</div>


	  </div>
	</div>
</div>


<script type="text/javascript">

$uploadCrop = $('#upload-demo').croppie({
	 url: '<?php echo $_GET["txt_imagen"]; ?>',
    enableExif: true,
    viewport: {
        width: 200,
        height: 200,
    },
    boundary: {
        width: 400,
        height: 400
    }

});

<?php

/*
?>$('#recarga').on('click', function (ev) {
	$uploadCrop.croppie('bind', {
		url: $('#recarga').val();
	);
});
*/
?>


$('#upload').on('change', function () {
	var reader = new FileReader();
    reader.onload = function (e) {
    	$uploadCrop.croppie('bind', {
    		url: e.target.result
    	}).then(function(){
    		console.log('jQuery bind complete');
    	});

    }
    reader.readAsDataURL(this.files[0]);
});


$('.upload-result').on('click', function (ev) {
	$uploadCrop.croppie('result', {
		type: 'canvas',
		size: 'viewport'
	}).then(function (resp) {

		$.ajax({
			url: "/myphp/ajax_actions.php",
			type: "POST",
			data: {
				"metodo": "sube_imagen_marca",
				"id_marca": "<?php echo $_GET["id_marca"]?>",
				"image":resp
				},
			success: function (data) {
				html = '<img src="' + resp + '" />';
				$("#upload-demo-i").html(html);
			}
		});
	});
});


</script>


</body>
</html>