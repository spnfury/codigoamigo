<?php 
    
    if(!empty($_POST)) {
        
        $resultat = "";
        $data = file_get_contents("provincias.json");
        $products = json_decode($data, true);
        
        $provincia = $_POST["provincia"];
        
        foreach ($products["data"] as $product) {
            if($product["PRO"] == $provincia) {
                $resultat = $product["CPRO"];
            }
        }
        echo $resultat;
    }
    
?>