<?php
// Archivo de carga intermedia para CodigoAmigo.com
// Este archivo se incluye en el template principal

// Inicializar variables globales si no están definidas
if (!isset($GLOBALS['website'])) {
    $GLOBALS['website'] = 'https://www.codigoamigo.com/';
}

if (!isset($GLOBALS['actual_url'])) {
    $GLOBALS['actual_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// Variables de paginación por defecto
if (!isset($num_inicio)) {
    $num_inicio = 1;
}

if (!isset($num_fin)) {
    $num_fin = 20;
}

if (!isset($codigos_restantes)) {
    $codigos_restantes = 0;
}

if (!isset($lista_codigos)) {
    $lista_codigos = array();
}

if (!isset($skip_patrocinados)) {
    $skip_patrocinados = 0;
}

if (!isset($num_destacados)) {
    $num_destacados = 0;
}

if (!isset($num_codes)) {
    $num_codes = 0;
}

// Inicializar variable de sesión si no existe
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = '';
}

// Inicializar variable de errores si no existe
if (!isset($manda)) {
    $manda = '';
}

if (!isset($_ERRORS)) {
    $_ERRORS = array();
}
?>