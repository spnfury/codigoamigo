<?php
namespace CodigoAmigo\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use MongoDB\Client;
include_once __DIR__ . '/../inc/logger.php';

class ChatHandler implements MessageComponentInterface {
    protected $clients;
    protected $users; // user_id => ConnectionInterface[]
    protected $conversations; // conversacion_id => [user_id1, user_id2]
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        $this->conversations = [];
    }
    
    /**
     * Cuando un cliente se conecta
     */
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        
        // Obtener user_id de la query string
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $params);
        
        $user_id = $params['user_id'] ?? null;
        $token = $params['token'] ?? null;
        
        // Validar autenticación
        if (!$this->validateAuth($user_id, $token, $conn)) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Autenticación fallida'
            ]));
            $conn->close();
            return;
        }
        
        // Asociar conexión con usuario
        $conn->user_id = $user_id;
        
        if (!isset($this->users[$user_id])) {
            $this->users[$user_id] = [];
        }
        $this->users[$user_id][] = $conn;
        
        // Notificar que el usuario está online
        $this->broadcastUserStatus($user_id, 'online');
        
        // Enviar confirmación de conexión
        $conn->send(json_encode([
            'type' => 'connected',
            'user_id' => $user_id
        ]));
    }
    
    /**
     * Cuando un cliente envía un mensaje
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            return;
        }
        
        $user_id = $from->user_id ?? null;
        if (!$user_id) {
            return;
        }
        
        switch ($data['type']) {
            case 'message':
                $this->handleMessage($from, $data);
                break;
                
            case 'typing':
                $this->handleTyping($from, $data);
                break;
                
            case 'stop_typing':
                $this->handleStopTyping($from, $data);
                break;
                
            case 'read':
                $this->handleRead($from, $data);
                break;
        }
    }
    
    /**
     * Cuando un cliente se desconecta
     */
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        $user_id = $conn->user_id ?? null;
        if ($user_id && isset($this->users[$user_id])) {
            $key = array_search($conn, $this->users[$user_id], true);
            if ($key !== false) {
                unset($this->users[$user_id][$key]);
                $this->users[$user_id] = array_values($this->users[$user_id]);
            }
            
            // Si no hay más conexiones para este usuario, está offline
            if (empty($this->users[$user_id])) {
                unset($this->users[$user_id]);
                $this->broadcastUserStatus($user_id, 'offline');
            }
        }
    }
    
    /**
     * Cuando hay un error
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        log_error("WebSocket Error: " . $e->getMessage());
        $conn->close();
    }
    
    /**
     * Valida la autenticación del usuario
     */
    protected function validateAuth($user_id, $token, $conn = null) {
        if (!$user_id || !$token) {
            return false;
        }

        // Validar formato de user_id (ObjectId de MongoDB)
        if (!preg_match('/^[a-f\d]{24}$/i', $user_id)) {
            return false;
        }

        // Validar token contra sesión PHP
        try {
            // Obtener el ID de sesión de la cookie si existe. $conn no se pasaba
            // antes (bug: variable inexistente en este scope) -> session_start()
            // arrancaba siempre una sesión vacía nueva y ws_tokens nunca se
            // encontraba, fallando la autenticación de TODA conexión websocket.
            // Nota: httpRequest es un GuzzleHttp\Psr7\Request (petición cliente,
            // no ServerRequest), que NO tiene getCookieParams() -> hay que
            // parsear la cabecera Cookie a mano.
            $session_id = null;
            if ($conn && isset($conn->httpRequest)) {
                $cookie_header = $conn->httpRequest->getHeaderLine('Cookie');
                if ($cookie_header && preg_match('/PHPSESSID=([^;]+)/', $cookie_header, $m)) {
                    $session_id = $m[1];
                }
            }

            if ($session_id) {
                // El pool FPM de codigoamigo.com usa session.save_path=/home/admin/tmp
                // (override, ver /etc/php/8.3/fpm/pool.d/codigoamigo.com.conf). Este
                // proceso CLI usa por defecto /var/lib/php/sessions -> sin esto,
                // session_start() nunca encuentra el fichero de sesión real.
                if (session_status() === PHP_SESSION_NONE) {
                    session_save_path('/home/admin/tmp');
                }
                session_id($session_id);
            }

            // Iniciar sesión para validar token
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Validar token usando función de validación
            require_once __DIR__ . '/../inc/includes.php';
            require_once __DIR__ . '/../myphp/funciones_usuario.php';
            
            $validated_user_id = validarTokenWebSocket($token);

            // Cerrar la sesión inmediatamente: este proceso es de larga duración
            // y atiende conexiones de MUCHOS usuarios distintos. Sin cerrarla,
            // la sesión del primer usuario que conecta queda abierta para
            // siempre y session_id() no puede cambiar en las siguientes
            // conexiones (falla el auth de todos los usuarios excepto el primero).
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            if ($validated_user_id && $validated_user_id === $user_id) {
                // Verificar que el usuario existe
                $usuario = get_object_user('_id', new \MongoDB\BSON\ObjectId($user_id));
                return $usuario !== null;
            }
            
            return false;
        } catch (\Exception $e) {
            log_error("Error validando auth: " . $e->getMessage());
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            return false;
        }
    }
    
    /**
     * Maneja el envío de mensajes
     */
    protected function handleMessage(ConnectionInterface $from, $data) {
        $de_usuario_id = $from->user_id;
        $para_usuario_id = $data['para_usuario_id'] ?? null;
        $mensaje = $data['mensaje'] ?? '';
        $codigo_id = $data['codigo_id'] ?? null;
        
        if (!$para_usuario_id || !$mensaje) {
            return;
        }
        
        // Guardar mensaje en MongoDB y enviar notificación por email
        if (!function_exists('enviarMensaje')) {
            require_once __DIR__ . '/../inc/includes.php';
            require_once __DIR__ . '/../myphp/funciones_usuario.php';
        }
        
        $contexto = [];
        if (!empty($codigo_id)) {
            $contexto['codigo_id'] = $codigo_id;
        }
        
        $mensaje_id = enviarMensaje($de_usuario_id, $para_usuario_id, $mensaje, false, $contexto);
        
        $conversacion_id = $this->crearConversacionId($de_usuario_id, $para_usuario_id, $codigo_id);
        
        // Enviar a destinatario
        $this->sendToUser($para_usuario_id, [
            'type' => 'new_message',
            'mensaje_id' => (string)$mensaje_id,
            'de_usuario_id' => $de_usuario_id,
            'para_usuario_id' => $para_usuario_id,
            'mensaje' => $mensaje,
            'conversacion_id' => $conversacion_id,
            'timestamp' => time() * 1000
        ]);
        
        // Confirmar al remitente
        $from->send(json_encode([
            'type' => 'message_sent',
            'para_usuario_id' => $para_usuario_id,
            'mensaje' => $mensaje,
            'conversacion_id' => $conversacion_id
        ]));
        
        // Notificar a ambos que la lista de conversaciones ha cambiado
        $update_event = [
            'type' => 'conversation_updated',
            'conversacion_id' => $conversacion_id
        ];
        $this->sendToUser($para_usuario_id, $update_event);
        $this->sendToUser($de_usuario_id, $update_event);
    }
    
    /**
     * Maneja el indicador de "escribiendo..."
     */
    protected function handleTyping(ConnectionInterface $from, $data) {
        $de_usuario_id = $from->user_id;
        $para_usuario_id = $data['para_usuario_id'] ?? null;
        
        if (!$para_usuario_id) {
            return;
        }
        
        $this->sendToUser($para_usuario_id, [
            'type' => 'typing',
            'de_usuario_id' => $de_usuario_id,
            'para_usuario_id' => $para_usuario_id
        ]);
    }
    
    /**
     * Maneja el stop de "escribiendo..."
     */
    protected function handleStopTyping(ConnectionInterface $from, $data) {
        $de_usuario_id = $from->user_id;
        $para_usuario_id = $data['para_usuario_id'] ?? null;
        
        if (!$para_usuario_id) {
            return;
        }
        
        $this->sendToUser($para_usuario_id, [
            'type' => 'stop_typing',
            'de_usuario_id' => $de_usuario_id,
            'para_usuario_id' => $para_usuario_id
        ]);
    }
    
    /**
     * Maneja la marca de mensaje como leído
     */
    protected function handleRead(ConnectionInterface $from, $data) {
        $usuario_id = $from->user_id;
        $conversacion_id = $data['conversacion_id'] ?? null;
        
        if (!$conversacion_id) {
            return;
        }
        
        // Notificar al otro usuario que sus mensajes fueron leídos
        // El conversacion_id puede tener formato: userA-userB o userA-userB-codigoId
        $parts = explode('-', $conversacion_id);
        $other_user_id = ($parts[0] === $usuario_id) ? ($parts[1] ?? '') : $parts[0];
        
        if (!empty($other_user_id)) {
            $this->sendToUser($other_user_id, [
                'type' => 'messages_read',
                'conversacion_id' => $conversacion_id,
                'usuario_id' => $usuario_id
            ]);
        }
    }
    
    /**
     * Envía un mensaje a todas las conexiones de un usuario
     */
    protected function sendToUser($user_id, $data) {
        if (!isset($this->users[$user_id])) {
            return;
        }
        
        $message = json_encode($data);
        foreach ($this->users[$user_id] as $conn) {
            $conn->send($message);
        }
    }
    
    /**
     * Broadcast del estado de usuario (online/offline)
     */
    protected function broadcastUserStatus($user_id, $status) {
        // Obtener todas las conversaciones de este usuario
        try {
            require_once __DIR__ . '/../inc/includes.php';
            $conversaciones = obtenerConversacionesUsuario($user_id);
            
            foreach ($conversaciones as $conv) {
                $other_user_id = $conv['otro_usuario_id'];
                $this->sendToUser($other_user_id, [
                    'type' => 'user_status',
                    'usuario_id' => $user_id,
                    'status' => $status
                ]);
            }
        } catch (\Exception $e) {
            log_error("Error broadcasting user status: " . $e->getMessage());
        }
    }
    
    /**
     * Crea un ID de conversación, opcionalmente vinculada a un código
     */
    protected function crearConversacionId($user1_id, $user2_id, $codigo_id = null) {
        $ids = [$user1_id, $user2_id];
        sort($ids);
        $conv_id = $ids[0] . '-' . $ids[1];
        if (!empty($codigo_id)) {
            $conv_id .= '-' . $codigo_id;
        }
        return $conv_id;
    }
}

