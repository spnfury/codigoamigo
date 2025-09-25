<?php

namespace App\Utiles;

Class NoImages{

	protected $url;
	protected $url_noImgen = "no-imagen.jpg";


	static function img($url_base){

		$Path = $_SERVER['DOCUMENT_ROOT']."/public/logotipos";

			if( file_exists($Path."/".$url_base) ){
				return "/logotipos/".$url_base;
			}else{
				return "/logotipos/no-imagen.jpg";
			}
	
	}


}



?>