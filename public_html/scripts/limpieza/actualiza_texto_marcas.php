<?php 

ini_set("display_errors", "on");
error_reporting(1);


include_once("../../inc/includes.php");

$db = createConnection();
$collection = $db->selectCollection('marcas');

$cursor = $collection->find(
    ['descripción' => ''], 
    [
        'skip' => 0,
        'sort' => ['_id' => -1]
    ]);

$count = $collection->count(
    ['descripción' => ''],
    [
        'skip' => 0,
        'sort' => ['_id' => -1]
    ]);

echo $count." marcas sin texto   por actualizar";

$array_marcas = iterator_to_array($cursor);


foreach ( $array_marcas as $id => $marca )
{
    
    /* PATROCINADOS */
    $array_filtro = array("marca"=>$marca["nombre_clave"]);
    $array_filtro = array_merge($array_filtro, array("estado"=>0));
    
    $array_skip = array("limit"=>0);
    $array_skip = array_merge($array_skip, array("skip"=>$skip_patrocinados));
    
    //TOMO LOS CODIGOS
    $lista_codigos_patrocinados_pre = get_all_listado_codigos_array($array_filtro, $array_skip);
    if(!$lista_codigos_patrocinados_pre["results"][0]){
        $noentra = 1;
    }
    
    $marca["descripción"] = recorta_texto_pos($lista_codigos_patrocinados_pre["results"][0]["descripcion"],120,"...");

    

    $updateResult = $collection->updateOne(
        ['_id' => $marca["_id"] ],
        ['$set' => ['descripción' => $marca["descripción"], 'descripción_larga' => $marca["descripción"]]]
        );
    

    
    $collectionArray[] = $codigo;
}
