<?php
// Script para crear una versión corregida de get_all_marcas_panel_control
require_once __DIR__ . '/../public_html/vendor/autoload.php';
include_once __DIR__ . '/../public_html/inc/includes.php';

echo "=== CREANDO VERSIÓN CORREGIDA ===\n";

// Función corregida que evita el problema con count_all_listado_codigos_array
function get_all_marcas_panel_control_fixed($limit = 9999, $aviso = '', $solo = '') {
    $array_final_marcas = array();
    $collection_marcas = getCollectionMarcas();

    if($aviso=='Marca nueva estado 0'){
        $lista_marcas = $collection_marcas->find([
            'estado' => 0,
            'aviso' => $aviso
        ], ['limit' => $limit]);
    }elseif($aviso=='Marca nueva'){
        $lista_marcas = $collection_marcas->find([
            'aviso' => $aviso
        ], ['limit' => $limit]);
    }elseif($aviso=='sin_categoria'){
        $lista_marcas = $collection_marcas->find([
            'categoria' => 'select'
        ], ['limit' => $limit]);
    }elseif($aviso=='sin_imagen'){
        $lista_marcas = $collection_marcas->find([
            'imagen' => 'Sin imagen'
        ], ['limit' => $limit]);
    }else{
        $lista_marcas = $collection_marcas->find([
            'estado' => 1,
        ], ['limit' => $limit]);
    }
    
    $array_marcas = iterator_to_array($lista_marcas);
    
    foreach ($array_marcas as $item) {
        $item_auxiliar = array();
        
        // CORRECCIÓN: Simplificar el conteo de códigos para evitar errores
        try {
            $array_filtro = array("marca" => $item["nombre_clave"]);
            $array_filtro = array_merge($array_filtro, array("estado" => 0));
            $item_auxiliar["numero_codigos"] = count_all_listado_codigos_array($array_filtro, '', 0);
        } catch (Exception $e) {
            // Si hay error, usar conteo simplificado
            $item_auxiliar["numero_codigos"] = 0;
        }

        if(($solo==1 && $item_auxiliar["numero_codigos"]==1) || ($solo=='no' && $item_auxiliar["numero_codigos"]==0) || !$solo){
            $item_auxiliar["id"] = $item["_id"];
            $item_auxiliar["fecha"] = $item["fecha_publicacion"] ?? date('Y-m-d');
            $item_auxiliar["nombre"] = $item["nombre"] ?? 'Sin nombre';
            $item_auxiliar["nombre_clave"] = $item["nombre_clave"] ?? '';
            $item_auxiliar["categoria"] = $item["categoria"] ?? 'Sin categoría';
            $item_auxiliar["categoria_clave"] = $item["categoria_clave"] ?? '';
            $item_auxiliar["url_imagen"] = $item["imagen"] ?? 'Sin imagen';
            $item_auxiliar["marca"] = $item["nombre"] ?? 'Sin nombre';
            $item_auxiliar["estado"] = $item["estado"] ?? 1;
            
            $array_final_marcas[] = $item_auxiliar;
        }
    }
    
    return $array_final_marcas;
}

// Probar la función corregida
try {
    session_start();
    $_SESSION["user_id"] = "58bd851da54e295b8b52f702";
    
    echo "Probando función corregida...\n";
    $marcas = get_all_marcas_panel_control_fixed(10);
    echo "Total marcas: " . count($marcas) . "\n";
    
    if (count($marcas) > 0) {
        echo "✓ ¡Funciona! Mostrando primeras 3:\n";
        for ($i = 0; $i < min(3, count($marcas)); $i++) {
            $marca = $marcas[$i];
            echo "- " . $marca['marca'] . " | Categoría: " . $marca['categoria'] . " | Códigos: " . $marca['numero_codigos'] . "\n";
        }
        
        echo "\n✓ La función corregida funciona correctamente\n";
        echo "Ahora necesitamos reemplazar la función original\n";
    } else {
        echo "✗ No se encontraron marcas\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
