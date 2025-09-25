<?php 
    // Incluir autoloader de Composer
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }
    
    // Incluir funciones de conexión
    include_once __DIR__ . '/../inc/conexion.php';
    
    // Incluir funciones de marcas
    include_once __DIR__ . '/../myphp/funciones_marca.php';
    
    $lista_marcas = getMarcas(null);
    $listado = array();

    if($lista_marcas){
        foreach ($lista_marcas as $marca)
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
    }

    /* Creamos json de marcas */
    $listado_final = json_encode($listado, JSON_UNESCAPED_UNICODE);
    $arxiu_json_marcas = fopen("marcas.json", 'w');
    fwrite($arxiu_json_marcas, $listado_final);
    fclose($arxiu_json_marcas);

?>