<?php 

    /* Links con Middleware */

    global $url_pagina_middleware;
    
    $url_pagina_middleware = "https://www.codigoamigo.com/";

    if (!function_exists('enlace_usuario')) {
        function enlace_usuario ($nombre, $id) {
            return "https://www.codigoamigo.com/usuario_".strtolower(urlencode($nombre))."_".$id;
        }
    }

    if (!function_exists('link_usuario')) {
        function link_usuario($nombre, $id) {
            return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/")."usuario_".strtolower(urlencode($nombre))."_".$id;
        }
    }
    
    if (!function_exists('link_usuario_nuevas_marcas')) {
        function link_usuario_nuevas_marcas($nombre, $id) {
            return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/")."usuario_marcas_".strtolower(urlencode($nombre))."_".$id;
        }
    }
    
    
    if (!function_exists('link_categoria')) {
        function link_categoria($clave_categoria) {
            return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/").$clave_categoria."-comparte-y-gana";
        }
    }
    
    if (!function_exists('link_blog_marca')) {
        function link_blog_marca($clave_marca) {
            return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/")."que-es-".$clave_marca;
        }
    }
    
    if (!function_exists('link_codigos_filtro_localizacion')) {
        function link_codigos_filtro_localizacion($clave_marca, $localizacion) {
            return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/")."codigos-de-".$clave_marca."-en-".$localizacion;
        }
    }
    
    if (!function_exists('link_codigos_filtro')) {
        function link_codigos_filtro($clave_marca, $mes, $año) {
            return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/")."codigos-de-".$clave_marca."-de-".$mes."-del-".$año;
        }
    }
    
    if (!function_exists('link_listado_categorias')) {
        function link_listado_categorias () {
            return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/")."listado-categorias";
        }
    }
    
    if (!function_exists('link_listado_marcas')) {
        function link_listado_marcas () {
            return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/")."listado-marcas";
        }
    }
    
    if (!function_exists('link_marca')) {
        function link_marca($clave_marca) {
            return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/")."de-".$clave_marca;
        }
    }
    
    if (!function_exists('link_nuevo_codigo')) {
        function link_nuevo_codigo() {
            $website = isset($GLOBALS["website"]) ? $GLOBALS["website"] : 'https://www.codigoamigo.com/';
            if(!empty($_SESSION["user_id"])) { return $website."nuevo_codigo"; } 
            else { return $website."registro"; }
        }
    }
    
    if (!function_exists('link_codigo_sitemap')) {
        function link_codigo_sitemap($id_codigo, $clave_marca) {
            return "https://www.codigoamigo.com/de-".$clave_marca."?codigo=".$id_codigo;
        }
    }
    
    if (!function_exists('link_codigo')) {
        function link_codigo($id_codigo, $clave_marca,$destacado='') {
            if ($destacado) {
                return "/destacar_codigo?codigo=".$id_codigo;
            } else {
                $website = isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/";
                return $website."de-".$clave_marca."?codigo=".$id_codigo;
            }
        }
    }
    
?>