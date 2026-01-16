#!/bin/bash
# Script para iniciar el servidor WebSocket del chat

cd /home/admin/web/codigoamigo.com/public_html/websocket

echo "Iniciando servidor WebSocket del chat..."
echo "Presiona Ctrl+C para detener"
echo ""

php chat_server.php

