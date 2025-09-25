<?php

header('Access-Control-Allow-Origin: *');
ini_set("display_errors", "on");
// use sessions
session_start();

// include google API client
require_once "vendor/autoload.php";



// set google client ID
$google_oauth_client_id = "298004995594-1qm1qpjok7lq4lo1kdd45406j9rarcqd.apps.googleusercontent.com";

// create google client object with client ID
$client = new Google_Client([
    'client_id' => $google_oauth_client_id
]);

// verify the token sent from AJAX
$id_token = $_POST["id_token"];

$payload = $client->verifyIdToken($id_token);

//print_r($payload);die;
if ($payload && $payload['aud'] == $google_oauth_client_id)
{
    // get user information from Google
    $user_google_id = $payload['sub'];
    
    $name = $payload["name"];
    $email = $payload["email"];
    $picture = $payload["picture"];
    
    
    
    $collection_usuarios = getCollectionUsuarios();
    $usuario = $collection_usuarios->findOne(
        [
            'mail' => $email
        ]);
    
    
    
    if(empty($usuario["username"])) {
        
        $array_return = array("estado"=>"noregistro");
        
        //echo "noregistro";
        // echo "<script>alert('Usuario y contraseña no coinciden.'); window.location='". $_SERVER["HTTP_REFERER"] ."';</script>";
    } else {
        
        $array_return = array("estado"=>"ok");
        
        if(strcmp($usuario["estado"], 0) == 0) {
            //echo "<script>alert('Recuerde que debe verificar su correo antes de poder iniciar sesión.'); window.location='". $GLOBALS["website"] ."';</script>";
        } else {
            $_SESSION["user_id"] = $usuario["_id"];
            $_SESSION["mail"] = $usuario["mail"];
            $_SESSION["username"] = $usuario["username"];
            
        }
    }
    
    $array_return= array_merge($array_return,$payload);
    
    $array_return = json_encode($array_return);
    
    echo json_encode($array_return);

    
    
    // login the user
    //$_SESSION["user"] = $user_google_id;
    
    // send the response back to client side
    //echo "Successfully logged in. " . $user_google_id . ", " . $name . ", " . $email . ", " . $picture;
}
else
{
    // token is not verified or expired
    echo "Failed to login.";
}





?>