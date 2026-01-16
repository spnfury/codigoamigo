/**
 * Cliente WebSocket para Chat en Tiempo Real
 * Maneja conexión, reconexión automática y eventos
 */

class ChatWebSocket {
    constructor(userId, options = {}) {
        this.userId = userId;
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = options.maxReconnectAttempts || 10;
        this.reconnectDelay = options.reconnectDelay || 3000;
        this.isConnected = false;
        this.isConnecting = false;
        this.token = null;
        this.wsUrl = options.wsUrl || 'ws://localhost:2096/chat';
        this.eventHandlers = {};
        this.pendingMessages = [];
        this.typingTimeout = null;
        this.typingUsers = {}; // conversacion_id => { usuario_id, timeout }

        // Callbacks
        this.onConnectCallback = null;
        this.onDisconnectCallback = null;
        this.onErrorCallback = null;
    }

    /**
     * Obtiene token de autenticación y conecta
     */
    async connect() {
        if (this.isConnecting || this.isConnected) {
            return;
        }

        this.isConnecting = true;

        try {
            // Obtener token de autenticación
            const response = await $.ajax({
                url: '/api/chat_api.php',
                method: 'POST',
                data: {
                    action: 'get_websocket_token'
                },
                dataType: 'json'
            });

            if (!response.success || !response.token) {
                throw new Error('No se pudo obtener token de autenticación');
            }

            this.token = response.token;
            const wsUrl = response.ws_url || this.wsUrl;

            // Actualizar URL del WebSocket si viene en la respuesta
            if (response.ws_url) {
                this.wsUrl = response.ws_url;
            }

            // Conectar WebSocket
            this._connect(wsUrl);

        } catch (error) {
            console.error('Error obteniendo token WebSocket:', error);
            this.isConnecting = false;
            this._handleError(error);

            // Fallback a polling después de varios intentos
            if (this.reconnectAttempts >= 3) {
                this.trigger('fallback_to_polling');
            }
        }
    }

    /**
     * Conecta al servidor WebSocket
     */
    _connect(wsUrl) {
        try {
            const url = `${wsUrl}?user_id=${this.userId}&token=${this.token}`;
            this.ws = new WebSocket(url);

            this.ws.onopen = (event) => {
                this.isConnected = true;
                this.isConnecting = false;
                this.reconnectAttempts = 0;
                console.log('[WebSocket] Conectado');

                // Enviar mensajes pendientes
                this._sendPendingMessages();

                if (this.onConnectCallback) {
                    this.onConnectCallback();
                }
                this.trigger('connect', event);
            };

            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this._handleMessage(data);
                } catch (error) {
                    console.error('[WebSocket] Error parseando mensaje:', error);
                }
            };

            this.ws.onerror = (error) => {
                console.error('[WebSocket] Error:', error);
                this._handleError(error);
            };

            this.ws.onclose = (event) => {
                this.isConnected = false;
                this.isConnecting = false;
                console.log('[WebSocket] Desconectado', event.code, event.reason);

                if (this.onDisconnectCallback) {
                    this.onDisconnectCallback(event);
                }
                this.trigger('disconnect', event);

                // Intentar reconectar si no fue un cierre intencional
                if (event.code !== 1000) {
                    this._reconnect();
                }
            };

        } catch (error) {
            console.error('[WebSocket] Error de conexión:', error);
            this.isConnecting = false;
            this._handleError(error);
            this._reconnect();
        }
    }

    /**
     * Maneja mensajes recibidos
     */
    _handleMessage(data) {
        switch (data.type) {
            case 'connected':
                console.log('[WebSocket] Autenticado correctamente');
                break;

            case 'new_message':
                this.trigger('new_message', data);
                break;

            case 'message_sent':
                this.trigger('message_sent', data);
                break;

            case 'typing':
                this.trigger('typing', data);
                break;

            case 'stop_typing':
                this.trigger('stop_typing', data);
                break;

            case 'messages_read':
                this.trigger('messages_read', data);
                break;

            case 'user_status':
                this.trigger('user_status', data);
                break;

            case 'error':
                console.error('[WebSocket] Error del servidor:', data.message);
                this.trigger('error', data);
                break;

            default:
                console.warn('[WebSocket] Tipo de mensaje desconocido:', data.type);
        }
    }

    /**
     * Envía un mensaje
     */
    sendMessage(paraUsuarioId, mensaje) {
        const data = {
            type: 'message',
            para_usuario_id: paraUsuarioId,
            mensaje: mensaje
        };

        this._send(data);
    }

    /**
     * Indica que el usuario está escribiendo
     */
    sendTyping(paraUsuarioId) {
        // Limpiar timeout anterior
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }

        const data = {
            type: 'typing',
            para_usuario_id: paraUsuarioId
        };

        this._send(data);

        // Auto-enviar stop_typing después de 3 segundos
        this.typingTimeout = setTimeout(() => {
            this.sendStopTyping(paraUsuarioId);
        }, 3000);
    }

    /**
     * Indica que el usuario dejó de escribir
     */
    sendStopTyping(paraUsuarioId) {
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
            this.typingTimeout = null;
        }

        const data = {
            type: 'stop_typing',
            para_usuario_id: paraUsuarioId
        };

        this._send(data);
    }

    /**
     * Marca mensajes como leídos
     */
    markAsRead(conversacionId) {
        const data = {
            type: 'read',
            conversacion_id: conversacionId
        };

        this._send(data);
    }

    /**
     * Envía datos al servidor
     */
    _send(data) {
        if (this.isConnected && this.ws && this.ws.readyState === WebSocket.OPEN) {
            try {
                this.ws.send(JSON.stringify(data));
            } catch (error) {
                console.error('[WebSocket] Error enviando mensaje:', error);
                this.pendingMessages.push(data);
            }
        } else {
            // Guardar para enviar cuando se conecte
            this.pendingMessages.push(data);

            // Intentar reconectar si no está conectando
            if (!this.isConnecting) {
                this.connect();
            }
        }
    }

    /**
     * Envía mensajes pendientes
     */
    _sendPendingMessages() {
        while (this.pendingMessages.length > 0) {
            const msg = this.pendingMessages.shift();
            this._send(msg);
        }
    }

    /**
     * Reconecta automáticamente
     */
    _reconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('[WebSocket] Máximo de intentos de reconexión alcanzado');
            this.trigger('max_reconnect_attempts');
            return;
        }

        this.reconnectAttempts++;
        const delay = this.reconnectDelay * this.reconnectAttempts;

        console.log(`[WebSocket] Reintentando conexión en ${delay}ms (intento ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);

        setTimeout(() => {
            if (!this.isConnected && !this.isConnecting) {
                this.connect();
            }
        }, delay);
    }

    /**
     * Maneja errores
     */
    _handleError(error) {
        if (this.onErrorCallback) {
            this.onErrorCallback(error);
        }
        this.trigger('error', error);
    }

    /**
     * Registra un handler de eventos
     */
    on(event, handler) {
        if (!this.eventHandlers[event]) {
            this.eventHandlers[event] = [];
        }
        this.eventHandlers[event].push(handler);
    }

    /**
     * Elimina un handler de eventos
     */
    off(event, handler) {
        if (this.eventHandlers[event]) {
            const index = this.eventHandlers[event].indexOf(handler);
            if (index > -1) {
                this.eventHandlers[event].splice(index, 1);
            }
        }
    }

    /**
     * Dispara un evento
     */
    trigger(event, data) {
        if (this.eventHandlers[event]) {
            this.eventHandlers[event].forEach(handler => {
                try {
                    handler(data);
                } catch (error) {
                    console.error(`[WebSocket] Error en handler de ${event}:`, error);
                }
            });
        }
    }

    /**
     * Desconecta
     */
    disconnect() {
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
            this.typingTimeout = null;
        }

        if (this.ws) {
            this.ws.close(1000, 'Desconexión intencional');
            this.ws = null;
        }

        this.isConnected = false;
        this.isConnecting = false;
    }

    /**
     * Obtiene el estado de conexión
     */
    getStatus() {
        return {
            connected: this.isConnected,
            connecting: this.isConnecting,
            reconnectAttempts: this.reconnectAttempts
        };
    }
}

