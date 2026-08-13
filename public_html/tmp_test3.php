<?php
require_once "inc/includes.php";
require_once "myphp/funciones_usuario.php";
$collection = getCollectionCodeViewers();
print_r(iterator_to_array($collection->listIndexes()));
