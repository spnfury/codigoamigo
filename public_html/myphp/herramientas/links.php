<?php 

    /* Links con Middleware */

    global $url_pagina_middleware;
    
    $url_pagina_middleware = "https://www.codigoamigo.com/";

    function enlace_usuario ($nombre, $id) {
        $nombre = $nombre ?? '';
        $id = $id ?? '';
        return "https://www.codigoamigo.com/usuario_".strtolower(urlencode($nombre))."_".$id;
    }

    function link_usuario($nombre, $id) {
        $nombre = $nombre ?? '';
        $id = $id ?? '';
        return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : 'https://www.codigoamigo.com/')."usuario_".strtolower(urlencode($nombre))."_".$id;
    }
    
    function link_usuario_nuevas_marcas($nombre, $id) {
        $nombre = $nombre ?? '';
        $id = $id ?? '';
        return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : 'https://www.codigoamigo.com/')."usuario_marcas_".strtolower(urlencode($nombre))."_".$id;
    }
    
    
    
    function link_categoria($clave_categoria) {
        return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : 'https://www.codigoamigo.com/').$clave_categoria."-comparte-y-gana";
    }
    
    function link_blog_marca($clave_marca) {
        return $GLOBALS["website"]."que-es-".$clave_marca;
    }
    
    function link_codigos_filtro_localizacion($clave_marca, $localizacion) {
        return $GLOBALS["website"]."codigos-de-".$clave_marca."-en-".$localizacion;
    }
    
    function link_codigos_filtro($clave_marca, $mes, $año) {
        return $GLOBALS["website"]."codigos-de-".$clave_marca."-de-".$mes."-del-".$año;
        //return $GLOBALS["website"]."codigos-de-".$clave_marca."-de-".$mes."-del-".$año;
    }
    
    function link_listado_categorias () {
        return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : 'https://www.codigoamigo.com/')."listado-categorias";
    }
    
    function link_listado_marcas () {
        return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : 'https://www.codigoamigo.com/')."listado-marcas";
    }
    
    function link_marca($clave_marca) {
        return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : 'https://www.codigoamigo.com/')."de-".$clave_marca;
    }
    
    function link_nuevo_codigo() {
        $website = isset($GLOBALS["website"]) ? $GLOBALS["website"] : 'https://www.codigoamigo.com/';
        if(!empty($_SESSION["user_id"])) { return $website."nuevo_codigo"; } 
        else { return $website."registro"; }
        
    }
    
    function link_codigo_sitemap($id_codigo, $clave_marca) {
        return "https://www.codigoamigo.com/de-".$clave_marca."?codigo=".$id_codigo;
    }
    
    function link_codigo($id_codigo, $clave_marca,$destacado='') {

        if ($destacado) {
            return "/destaca?codigo=".$id_codigo;
        /*}else if (!$_SESSION['user_id'] && $clave_marca!='bookingcom') {
            return $GLOBALS["website"]."registro";*/
        } else {
            $website = isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/";
            return $website."de-".$clave_marca."?codigo=".$id_codigo;
        }

    }
    
?>