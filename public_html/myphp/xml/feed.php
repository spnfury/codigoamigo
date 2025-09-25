<?php

/*************** DETECTAR EL PATH ********************/
$actual_path = dirname(__FILE__);
$actual_path_tmp = explode ("/", $actual_path);
$chivato = 0; $n = 0;
$control = count ($actual_path_tmp);
$actual_path = "";
while ($chivato != 1 && $n < $control){
    $actual_path .= $actual_path_tmp[$n]."/";
    //$str_info.= $actual_path_tmp[$n]."/e";
    if ( strstr($actual_path_tmp[$n], "codigoamigo")){
        $ext_tmp = explode (".", $actual_path_tmp[$n]);
        $ext = $ext_tmp[1];
    }
    if ($actual_path_tmp[$n] == "httpdocs" ||  strstr($actual_path_tmp[$n], "dev.codigoamigo.com")){
        $chivato = 1;
    }
    $n++;
}

$_SERVER['DOCUMENT_ROOT'] = $actual_path;

/*******************************************************/


//     echo $_SERVER['DOCUMENT_ROOT'].'/inc/includes.php';die;
    include_once $_SERVER['DOCUMENT_ROOT'].'/inc/includes.php';

    $hoy = date("Y-m-d");

    /**********************************************
     * XML GENERAL
     * *******************************************/

    $lista_codigos = get_publish_codes();
    $xml_ = new DOMDocument("1.0", "UTF-8");

    $container_ = $xml_->createElement("sitemapindex");
    $container_ = $xml_->appendChild($container_);
    $container_->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");

    /*$url_ = $xml_->createElement("sitemap");
    $url_ = $container_->appendChild($url_);
    $loc_ = $xml_->createElement("loc", "https://www.codigoamigo.com/myphp/xml/sitemap_codigos.xml");
    $loc_ = $url_->appendChild($loc_);
    $lastmod_ = $xml_->createElement("lastmod", $hoy);
    $lastmod_ = $url_->appendChild($lastmod_);*/

    $url_ = $xml_->createElement("sitemap");
    $url_ = $container_->appendChild($url_);
    $loc_ = $xml_->createElement("loc", "https://www.codigoamigo.com/myphp/xml/sitemap_marcas.xml");
    $loc_ = $url_->appendChild($loc_);
    $lastmod_ = $xml_->createElement("lastmod", $hoy);
    $lastmod_ = $url_->appendChild($lastmod_);

    $url_ = $xml_->createElement("sitemap");
    $url_ = $container_->appendChild($url_);
    $loc_ = $xml_->createElement("loc", "https://www.codigoamigo.com/myphp/xml/sitemap_categorias.xml");
    $loc_ = $url_->appendChild($loc_);
    $lastmod_ = $xml_->createElement("lastmod", $hoy);
    $lastmod_ = $url_->appendChild($lastmod_);

    $xml_->FormatOutput = true;
    $string_value = $xml_->saveXML();
    $xml_->save("/var/www/vhosts/codigoamigo.com/httpdocs/sitemap.xml") or die("Error, unable to create XML File");

    /**********************************************
     * XML CODIGOS
     * *******************************************/

//     $lista_codigos = get_publish_codes();
//     $xml = new DOMDocument("1.0", "UTF-8");

//     $container = $xml->createElement("urlset");
//     $container = $xml->appendChild($container);
//     $container->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");

//     foreach ($lista_codigos as $codigo) {

//         $url_codigo = link_codigo_sitemap($codigo["_id"], $codigo["marca"]);

//         $url = $xml->createElement("url");
//         $url = $container->appendChild($url);

//         $loc = $xml->createElement("loc", $url_codigo);
//         $loc = $url->appendChild($loc);
//         $lastmod = $xml->createElement("lastmod", $hoy);
//         $lastmod = $url->appendChild($lastmod);
//         $changefreq = $xml->createElement("changefreq", "daily");
//         $changefreq = $url->appendChild($changefreq);
//         $priority = $xml->createElement("loc", "0.9");
//         $priority = $url->appendChild($priority);

//     }

//     $xml->FormatOutput = true;
//     $string_value = $xml->saveXML();
//     $xml->save("sitemap_codigos.xml") or die("Error, unable to create XML File");

    /**********************************************
     * XML MARCAS
     * *******************************************/

    $lista_marcas = getMarcas(9999);
    $xml2 = new DOMDocument("1.0", "UTF-8");

    $container2 = $xml2->createElement("urlset");
    $container2 = $xml2->appendChild($container2);
    $container2->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
    
//     echo "<pre>";
//     print_r($lista_marcas);die;

    foreach ($lista_marcas as $marca) {

        $url_marca = "https://www.codigoamigo.com/".link_marca($marca["nombre_clave"]);

        $url2 = $xml2->createElement("url");
        $url2 = $container2->appendChild($url2);

        $loc2 = $xml2->createElement("loc", $url_marca);
        $loc2 = $url2->appendChild($loc2);
        $lastmod2 = $xml2->createElement("lastmod", $hoy);
        $lastmod2 = $url2->appendChild($lastmod2);
        $changefreq2 = $xml2->createElement("changefreq", "daily");
        $changefreq2 = $url2->appendChild($changefreq2);
        $priority2 = $xml2->createElement("loc", "0.9");
        $priority2 = $url2->appendChild($priority2);

    }

    $xml2->FormatOutput = true;
    $string_value = $xml2->saveXML();
    $xml2->save("/var/www/vhosts/codigoamigo.com/httpdocs/myphp/xml/sitemap_marcas.xml") or die("Error, unable to create XML File");

    /**********************************************
     * XML CATEGORIAS
     * *******************************************/

    $listacategorias = getCategorias();
    $xml3 = new DOMDocument("1.0", "UTF-8");

    $container3 = $xml3->createElement("urlset");
    $container3 = $xml3->appendChild($container3);
    $container3->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");

    foreach ($listacategorias as $categoria) {

        $url_categoria = "https://www.codigoamigo.com/".link_categoria($categoria["nombre_clave"]);

        $url3 = $xml3->createElement("url");
        $url3 = $container3->appendChild($url3);

        $loc3 = $xml3->createElement("loc", $url_categoria);
        $loc3 = $url3->appendChild($loc3);
        $lastmod3 = $xml3->createElement("lastmod", $hoy);
        $lastmod3 = $url3->appendChild($lastmod3);
        $changefreq3 = $xml3->createElement("changefreq", "daily");
        $changefreq3 = $url3->appendChild($changefreq3);
        $priority3 = $xml3->createElement("loc", "0.9");
        $priority3 = $url3->appendChild($priority3);

    }

    $xml3->FormatOutput = true;
    $string_value = $xml3->saveXML();
    $xml3->save("/var/www/vhosts/codigoamigo.com/httpdocs/myphp/xml/sitemap_categorias.xml") or die("Error, unable to create XML File");


?>