<?php 
//PHP QUE REFRESCA EL JSON DE MARCAS


ini_set("display_errors", "on");
error_reporting(1);


include_once("../../inc/includes.php");


$collection_codigos = getCollectionCodigos();

$lista_codigos = $collection_codigos->find(
    [],
    ['sort' => ['id_usuario' => -1]]);

$array_usuarios["results"] = iterator_to_array($lista_codigos);



foreach($array_usuarios["results"] as $arr => $fields){
    
//     echo "<pre>";
//     print_r($fields);die;

    if(!$array_usuarios_final["".$fields["id_usuario"].""]["".$fields["marca"].""]){
        $array_usuarios_final["".$fields["id_usuario"].""]["".$fields["marca"].""] = $fields;
    }else{
        $arrayCodigosABorrar[] = $fields["_id"];
    }
}




foreach($arrayCodigosABorrar as $f => $fields){

    $data = ['_id' => $fields];
    $collection_codigos->deleteOne($data);
    
    
}

echo "a";die;
print_r($array_usuarios_final);

foreach ( $array_usuarios as $id => $marca )
{
    
    $num_codes = getNumCodes('marca', $marca["nombre_clave"], null);
    $elemento = array('nombre' => $marca["nombre"],
        'imagen' => $marca["imagen"],
        'codes' => $num_codes,
        'categoria' => $marca["categoria_clave"],
        'clave' => $marca["nombre_clave"]
    );
    array_push($listado, $elemento);
    
}

$listado_final = json_encode($listado);




?>