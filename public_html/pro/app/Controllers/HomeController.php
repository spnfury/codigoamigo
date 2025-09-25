<?php 

namespace App\Controllers;


use App\Models\User;
use App\Controllers\Controller;
use Slim\Views\Twig as View;
use Psr\Log\LoggerInterface;
use App\Utiles\Mongoo;

class HomeController extends Controller
{

	/**
	 * [index description]
	 * @param  [type] $request  [description]
	 * @param  [type] $response [description]
	 * @return [type]           [description]
	 */
	public function index($request, $response, $arg=[])
	{
	
	return $this->view->render($response, 'index.twig'); 

	}
	
}