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
    
    /**
     * Genera la URL SEO-friendly para la ficha individual de un código.
     * Formato: /codigo/{marca}-{shortId}
     * Ejemplo: /codigo/trading212-670a1b2c
     */
    if (!function_exists('link_ficha_codigo')) {
        function link_ficha_codigo($clave_marca, $codigo_id) {
            $website = isset($GLOBALS["website"]) ? $GLOBALS["website"] : "https://www.codigoamigo.com/";
            $id_str = (string)$codigo_id;
            // Usar los últimos 8 caracteres del ObjectId para un slug corto
            $short_id = substr($id_str, -8);
            return $website . "codigo/" . strtolower($clave_marca) . "-" . $short_id;
        }
    }
    
    /**
     * Parsea el slug de la URL /codigo/{slug} para extraer marca y ObjectId completo.
     * Busca el código en MongoDB por los últimos 8 chars del _id.
     */
    if (!function_exists('parse_ficha_codigo_slug')) {
        function parse_ficha_codigo_slug($slug) {
            // El slug tiene formato: {marca}-{8ultimos_chars_id}
            // Encontrar la última ocurrencia de '-' seguida de exactamente 8 chars hex
            if (preg_match('/^(.+)-([a-f0-9]{8})$/i', $slug, $matches)) {
                return [
                    'marca' => $matches[1],
                    'short_id' => $matches[2]
                ];
            }
            return null;
        }
    }
    
?>