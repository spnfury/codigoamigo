<?php 
//PHP QUE REFRESCA EL JSON DE MARCAS

/*************** DETECTAR EL PATH ********************/
$actual_path = dirname(__FILE__);
$actual_path_tmp = explode ("/", $actual_path);
$chivato = 0; $n = 0;
$control = count ($actual_path_tmp);
$actual_path = "";
while ($chivato != 1 && $n < $control){
    $actual_path .= $actual_path_tmp[$n]."/";
    //$str_info.= $actual_path_tmp[$n]."/e";
    
    if ( strstr($actual_path_tmp[$n], "dev")){
        $ext_tmp = explode (".", $actual_path_tmp[$n]);
        $ext = $ext_tmp[1];
        $chivato = 1;
    }elseif ( strstr($actual_path_tmp[$n], "codigoamigo.com")){
        $ext_tmp = explode (".", $actual_path_tmp[$n]);
        $ext = $ext_tmp[1];
    }
        
    if ($actual_path_tmp[$n] == "httpdocs"){
        $chivato = 1;
    }
    
    $n++;
}

$_SERVER['DOCUMENT_ROOT'] = $actual_path;


/*******************************************************/



ini_set("display_errors", "on");
error_reporting(E_ALL);


include_once($_SERVER["DOCUMENT_ROOT"]."/inc/includes.php");

$db = createConnection();
$collection = $db->selectCollection('marcas');
$cursor = $collection->find(
    ['estado' => 1],
    [
        'skip' => 0,
        'sort' => ['_id' => -1]
    ]);

$array_codigos = iterator_to_array($cursor);
//print_r($array_codigos);die;
//echo count($array_codigos);die;




$listado = array();
//echo count($array_codigos);die;
foreach ( $array_codigos as $id => $marca )
{
    
    $num_codes = getNumCodes('marca', $marca["nombre_clave"], null);
    
    //echo $num_codes."\n";
    
    //SOLO PONGO LAS MARCAS CON MAS DE 1 CODIGO
    if($num_codes>=1){
        
        $elemento = array(
            'nombre' => $marca["nombre"],
            'nombre_clave' => $marca["nombre_clave"],
            'categoria' => $marca["categoria"],
            'categoria_clave' => $marca["categoria_clave"],
            'imagen' => $marca["imagen"],
            'codes' => $num_codes,
            'url' => "https://www.codigoamigo.com/de-".$marca["nombre_clave"]
        );
    
    
        array_push($listado, $elemento);
    
    }
    
}


$json_path = $_SERVER["DOCUMENT_ROOT"] . "/datos.json";
$json_codigos = json_encode($listado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

if ($json_codigos === false) {
    throw new Exception("Error al codificar JSON: " . json_last_error_msg());
}

if (file_put_contents($json_path, $json_codigos) === false) {
    throw new Exception("Error al escribir el archivo JSON");
}

// foreach ( $array_codigos as $id => $marca )
// {
    
//     print_r($marca);
//     die;
    
// }


?>