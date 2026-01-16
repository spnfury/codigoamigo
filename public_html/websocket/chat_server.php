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
$host = '0.0.0.0';
$port = 2096;
$allowed_origins = ['codigoamigo.com', 'www.codigoamigo.com', 'localhost'];

// Crear aplicación Ratchet
// NOTA: El 4to argumento es el Loop, no los allowed_origins.
$app = new App($host, $port, '0.0.0.0');

// Registrar el handler de chat
$app->route('/chat', new ChatHandler, $allowed_origins);

echo "Servidor WebSocket iniciado en ws://{$host}:{$port}/chat\n";
echo "Presiona Ctrl+C para detener el servidor\n";

// Ejecutar el servidor
$app->run();

