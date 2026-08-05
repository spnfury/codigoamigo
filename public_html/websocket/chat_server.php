<?php
/**
 * Servidor WebSocket para Chat en Tiempo Real
 * 
 * Para ejecutar: php chat_server.php
 * O con supervisor/systemd para mantenerlo corriendo
 */

use Ratchet\App;
use CodigoAmigo\WebSocket\ChatHandler; // Keep existing use statement for clarity

require __DIR__ . '/../vendor/autoload.php';

// Incluir el handler manualmente ya que no está en el autoloader principal
require_once __DIR__ . '/ChatHandler.php';

// Configuración
// $http_host: hostname público que los navegadores usan en new WebSocket(...).
// Ratchet lo usa para el enrutado por Host (Symfony Router) - si no coincide
// EXACTO con el Host: que manda el navegador, el handshake da 404/400 y el
// chat nunca conecta por WebSocket (cae siempre al fallback de polling).
// Antes aquí ponía '0.0.0.0' -> nunca coincidía con el Host real del navegador.
$http_host = 'www.codigoamigo.com';
$port = 2096;
$bind_address = '0.0.0.0'; // escuchar en todas las interfaces, no confundir con $http_host
$allowed_origins = ['codigoamigo.com', 'www.codigoamigo.com', 'localhost'];

// Crear aplicación Ratchet
// NOTA: El 4to argumento es el Loop, no los allowed_origins.
$app = new App($http_host, $port, $bind_address);

// Registrar el handler de chat
$app->route('/chat', new ChatHandler, $allowed_origins);

// fwrite(STDERR,...) en vez de echo: echo cuenta como "output ya enviado" para
// el motor de sesiones PHP y rompe session_start() en cada conexión (bug real
// visto en journalctl: "Session cannot be started after headers have already
// been sent" en CADA conexión, porque este echo ya había "enviado output").
fwrite(STDERR, "Servidor WebSocket iniciado en ws://{$http_host}:{$port}/chat (escuchando en {$bind_address}:{$port})\n");
fwrite(STDERR, "Presiona Ctrl+C para detener el servidor\n");

// Ejecutar el servidor
$app->run();

