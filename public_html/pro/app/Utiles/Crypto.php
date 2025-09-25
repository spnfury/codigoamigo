<?php

namespace App\Utiles;
/**
 * GIANNI
 */

Class Crypto{
	
 

	 public function crypt($string, $key="HJDFSHOVWOWE8EHHCQ08W8EH0Q0E8EYF0Q0E7FEFEQ00E8DDE0") {
		   $result = '';
		   for($i=0; $i<strlen($string); $i++) {
		      $char = substr($string, $i, 1);
		      $keychar = substr($key, ($i % strlen($key))-1, 1);
		      $char = chr(ord($char)+ord($keychar));
		      $result.=$char;
		   }
		   return base64_encode($result);
	}


	public function decrypt($string, $key="HJDFSHOVWOWE8EHHCQ08W8EH0Q0E8EYF0Q0E7FEFEQ00E8DDE0") {
	   $result = '';
	   $string = base64_decode($string);
	   for($i=0; $i<strlen($string); $i++) {
	      $char = substr($string, $i, 1);
	      $keychar = substr($key, ($i % strlen($key))-1, 1);
	      $char = chr(ord($char)-ord($keychar));
	      $result.=$char;
	   }
	   return $result;
	}


}