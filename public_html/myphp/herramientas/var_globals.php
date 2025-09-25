<?php 

    /**************************************************
     * GENERAL
     *************************************************/

    global $detect_device;
    $detect_device = new Mobile_Detect();
    
    global $author_web, $web, $url_usuario_sin_foto, $url_logo_web, $img_compartir_pagina, $ubicacion_actual;
    
    $web = "https://codigoamigo.com";
    $author_web = "CODIGOAMIGO.COM";
    $GLOBALS["author"] = "CODIGOAMIGO.COM";
    $url_usuario_sin_foto = "https://www.codigoamigo.com/img/utilidades/usuario_sin_foto.jpg";
    $url_logo_web = "https://www.codigoamigo.com/img/_importantes/logo.png";
    $img_compartir_pagina = "https://www.codigoamigo.com/img/_importantes/compartir.jpg";

    $ubicacion_actual = "";
    
    /**************************************************
     * RICH SNIPPET
     *************************************************/
    
    global $rating_count_rs, $rating_value_rs, $url_logo_rs, $url_marca_rs, $title_marca_rs;
    
    $rating_count_rs = 0;
    $rating_value_rs = 0;
    $url_logo_rs = 0;
    $url_marca_rs = 0;
    $title_marca_rs = 0;
    
    /**************************************************
     * PUBLICAR CÓDIGO
     *************************************************/
    
    global $array_descuentos;
    
    $array_descuentos = array("euros", "% de descuento", "minutos gratis", "horas gratis", "días gratis", "semanas gratis", "meses gratis", "euros para cheque regalo");
    
    /**************************************************
     * LISTADO CÓDIGOS
     *************************************************/
    
    global $array_mes;
    
    $array_mes[] = array("id" => "01", "name" => "enero");
    $array_mes[] = array("id" => "02", "name" => "febrero");
    $array_mes[] = array("id" => "03", "name" => "marzo");
    $array_mes[] = array("id" => "04", "name" => "abril");
    $array_mes[] = array("id" => "05", "name" => "mayo");
    $array_mes[] = array("id" => "06", "name" => "junio");
    $array_mes[] = array("id" => "07", "name" => "julio");
    $array_mes[] = array("id" => "08", "name" => "agosto");
    $array_mes[] = array("id" => "09", "name" => "septiembre");
    $array_mes[] = array("id" => "10", "name" => "octubre");
    $array_mes[] = array("id" => "11", "name" => "noviembre");
    $array_mes[] = array("id" => "12", "name" => "diciembre");
    
    global $array_mes2;
    
    $array_mes2["01"] = "enero";
    $array_mes2["02"] = "febrero";
    $array_mes2["03"] = "marzo";
    $array_mes2["04"] = "abril";
    $array_mes2["05"] = "mayo";
    $array_mes2["06"] = "junio";
    $array_mes2["07"] = "julio";
    $array_mes2["08"] = "agosto";
    $array_mes2["09"] = "septiembre";
    $array_mes2["10"] = "octubre";
    $array_mes2["11"] = "noviembre";
    $array_mes2["12"] = "diciembre";

?>