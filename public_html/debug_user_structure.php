<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
$query = new MongoDB\Driver\Query([], ['limit' => 1]);
$cursor = $manager->executeQuery('codigoamigo.usuarios', $query);

foreach ($cursor as $doc) {
    print_r($doc);
}
?>
