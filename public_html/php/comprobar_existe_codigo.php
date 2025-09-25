<?php 

    $trobat = checkCodeExists($_POST["thecodigo"]);
    
    if ($trobat) {
        echo "trobat";
    } else {
        echo "no";
    }

?>