<?php 

	//////////////////////////////////////
	// RUTAS
	//////////////////////////////////////


// HOME
$app->get("/",  'App\Controllers\HomeController:index');	

//HORARIOS TRENES
$app->get("/horarios/{origen}/{destino}/{ano}/{mes}/{dia}",  'App\Controllers\HomeController:horarioTrenes');	

$app->get("/{ciudad}",  'App\Controllers\HomeController:ciudad');