<?php 

ini_set("display_errors", "on");
error_reporting(1);


include_once("../../inc/includes.php");

$db = createConnection();
$collection = $db->selectCollection('codigos')


$cursor = $collection->find(
    ['estado' => 0, 'clave_categoria' => '-'], 
    [
        'skip' => 0,
        'sort' => ['_id' => -1]
    ]);

$count = $collection->count(
    ['estado' => 0, 'clave_categoria' => '-'],
    [
        'skip' => 0,
        'sort' => ['_id' => -1]
    ]);

echo $count." códigos sin categoria por actualizar";



$array_codigos = iterator_to_array($cursor);


$collection_marcas = getCollectionMarcas();

foreach ( $array_codigos as $id => $codigo )
{
 
    
    $marca = $collection_marcas->findOne(["nombre_clave" => $codigo["marca"]]);   
    
    
    $updateResult = $collection->updateOne(
        ['_id' => new \MongoDB\BSON\ObjectId($codigo["_id"]) ],
        ['$set' => ['clave_categoria' => $marca["categoria_clave"]]]
        );

    
    $collectionArray[] = $codigo;
}
