<?php
namespace App\Middlewares;

use App\Utiles\Mongoo;

class Auth {


	private $errors;
	private $success;



	public function __invoke($request, $response, $next){
		
		$_SESSION['UrlRedirect'] = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

		if ($_GET['public'] == true) {
			$newResponse = $next($request, $response);
			return $newResponse;
		}

		if (isset($_SESSION['login_user']) && $_SESSION['login_user']) {
			  
			$newResponse = $next($request, $response);
			return $newResponse;

		}else{

			return $response = $response->withRedirect("/auth/login", 202);

		}


	}
	/**
	 * Posibles mensajes de error
	 * @return [type] [description]
	 */
	public function error_messages(){

		$this->errors[0] = "Error contraseña las contraseñas no coinciden";
		$this->errors[1] = "Nombre de usuario no disponible";
		$this->errors[2] = "Email no disponible";
		$this->errors[3] = "El nombre de usuario o contraseña son incorrectos";

		return $this->errors;
	}
	/**
	 * [success_messages description]
	 * @return [type] [description]
	 */
	public function success_messages(){
		$this->success[0] = "Felicidades! Usuario creado correctamente, hemos enviado un email de confirmación a su dirección de correo electrónico";

		return $this->success;
	}
	/**
	 * Comprobamos la session activa
	 * @return [type] [description]
	 */
	public function keeper(){
		if (isset($_SESSION['login_user']) && $_SESSION['login_user']) {
			return $_SESSION['login_user'];
		}else{
			return false;
		}
	}
	/**
	 * Comprobamos que el usuario sea correcto
	 * @param  [type] $email    [description]
	 * @param  [type] $password [description]
	 * @return [type]           [description]
	 */
	public function getUser($email){
	    
	    $Mongoo = new Mongoo("hello_users");
		$cursor = $Mongoo->Collection->findOne(array('email'=>"$email"));
 

	    if(count($cursor)>0){

	      return $cursor;

	    }else{

	      return false;
	    }
	}
	/**
	 * register registra un nuevo usaurio
	 * @param  [type] $username [description]
	 * @param  [type] $email    [description]
	 * @param  [type] $password [description]
	 * @param  [type] $level    [description]
	 * @return [type]           [description]
	 */
	public function register($username,$email,$password, $level){

		$Mongoo = new Mongoo("hello_users");
		$cursor = $Mongoo->Collection->findOne(array('email'=>"$email"));

		if (count($cursor)>0) {
			//el email ya existe
			return false;
		}


		$data = array(
			'id'=>uniqid(),
			'email'=>$email,
			'username'=>$username,
			'password_hash'=>$password,
			'level'=>array('condition'=>'$lte', 'level'=>'0')
			);


		   $insert = $Mongoo->Collection->insert($data);

		   if (isset($insert)) {
		   	//Insertado correctamente
		   	return true;
		   }else{
		   	//No insertado
		   	return false;
		   }

	}
}

?>