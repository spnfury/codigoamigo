<?php
namespace App\Middlewares;
use \Psr\Http\Message\ResponseInterface;

class validIpMiddle 
{
 
    public function __invoke($request, $response, $next)
    {
        // $response->getBody()->write('BEFORE');
        



  
	    $newResponse = $next($request, $response);
	    return $newResponse;



        // $response->getBody()->write('AFTER');

       
    }


}