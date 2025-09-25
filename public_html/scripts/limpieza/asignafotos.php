<?php 

ini_set("display_errors", "on");



include_once("../../inc/includes.php");

$db = createConnection();
$collection = $db->selectCollection('codigos');

$cursor = $collection->find();
foreach ( $cursor as $id => $value )
{
    
    
    $collectionArray[] = $value;
}




?>