<?php
require_once __DIR__ . '/public_html/vendor/autoload.php';
require_once __DIR__ . '/public_html/inc/includes.php';
require_once __DIR__ . '/public_html/myphp/funciones.php';
require_once __DIR__ . '/public_html/myphp/funciones_destacados_email.php';

$usuario = ['mail' => 'blackhole@codigoamigo.com', 'username' => 'Test', '_id' => '111111111111111111111111'];
$codigo = ['tipo_destacado' => 'normal', '_id' => '222222222222222222222222'];

$res = enviarEmailDestacadoExpiraPronto($usuario, $codigo, "Spliiitcom", 2);
print_r($res);
