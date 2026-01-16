<?php
namespace CodigoAmigo\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use MongoDB\Client;

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
        if (!$this->validateAuth($user_id, $token)) {
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
        error_log("WebSocket Error: " . $e->getMessage());
        $conn->close();
    }
    
    /**
     * Valida la autenticación del usuario
     */
    protected function validateAuth($user_id, $token) {
        if (!$user_id || !$token) {
            return false;
        }
        
        // Validar formato de user_id (ObjectId de MongoDB)
        if (!preg_match('/^[a-f\d]{24}$/i', $user_id)) {
            return false;
        }
        
        // Validar token contra sesión PHP
        try {
            // Obtener el ID de sesión de la cookie si existe
            $session_id = null;
            if (isset($conn->httpRequest->getCookieParams()['PHPSESSID'])) {
                $session_id = $conn->httpRequest->getCookieParams()['PHPSESSID'];
            }
            
            if ($session_id) {
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
            
            if ($validated_user_id && $validated_user_id === $user_id) {
                // Verificar que el usuario existe
                $usuario = get_object_user('_id', new \MongoDB\BSON\ObjectId($user_id));
                return $usuario !== null;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Error validando auth: " . $e->getMessage());
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
        
        if (!$para_usuario_id || !$mensaje) {
            return;
        }
        
        // Guardar mensaje en MongoDB y enviar notificación por email
        if (!function_exists('enviarMensaje')) {
            require_once __DIR__ . '/../inc/includes.php';
            require_once __DIR__ . '/../myphp/funciones_usuario.php';
        }
        
        $mensaje_id = enviarMensaje($de_usuario_id, $para_usuario_id, $mensaje);
        
        // Enviar a destinatario
        $this->sendToUser($para_usuario_id, [
            'type' => 'new_message',
            'mensaje_id' => (string)$mensaje_id,
            'de_usuario_id' => $de_usuario_id,
            'para_usuario_id' => $para_usuario_id,
            'mensaje' => $mensaje,
            'conversacion_id' => $this->crearConversacionId($de_usuario_id, $para_usuario_id),
            'timestamp' => time() * 1000
        ]);
        
        // Confirmar al remitente
        $from->send(json_encode([
            'type' => 'message_sent',
            'para_usuario_id' => $para_usuario_id,
            'mensaje' => $mensaje
        ]));
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
        $parts = explode('-', $conversacion_id);
        $other_user_id = ($parts[0] === $usuario_id) ? $parts[1] : $parts[0];
        
        $this->sendToUser($other_user_id, [
            'type' => 'messages_read',
            'conversacion_id' => $conversacion_id,
            'usuario_id' => $usuario_id
        ]);
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
            error_log("Error broadcasting user status: " . $e->getMessage());
        }
    }
    
    /**
     * Crea un ID de conversación
     */
    protected function crearConversacionId($user1_id, $user2_id) {
        $ids = [$user1_id, $user2_id];
        sort($ids);
        return $ids[0] . '-' . $ids[1];
    }
}

