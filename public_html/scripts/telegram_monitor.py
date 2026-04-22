#!/usr/bin/env python3
"""
Script para monitorear canales de Telegram y sincronizar mensajes con la API PHP
Requiere Telethon para conectarse a Telegram
"""

import os
import sys
import json
import logging
import asyncio
import requests
import hashlib
from datetime import datetime
from pathlib import Path
from typing import List, Dict, Optional
from telethon import TelegramClient
from telethon.tl.types import Message, Channel
from telethon.errors import SessionPasswordNeededError, FloodWaitError

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('telegram_monitor.log'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

# Cargar configuración
try:
    from config import (
        API_ID, API_HASH, PHONE_NUMBER,
        API_URL, API_TOKEN,
        SESSION_FILE, TELEGRAM_BOT_TOKEN
    )
except ImportError:
    logger.error("Error: No se encontró el archivo config.py. Por favor, crea config.py basándote en config.example.py")
    sys.exit(1)
except NameError:
    # Si TELEGRAM_BOT_TOKEN no está definido, usar None
    TELEGRAM_BOT_TOKEN = None


class TelegramMonitor:
    """Clase para monitorear canales de Telegram"""
    
    def __init__(self):
        self.client = None
        self.api_url = API_URL
        self.api_token = API_TOKEN
        self.bot_token = TELEGRAM_BOT_TOKEN
        
        # Detectar si estamos en el mismo servidor (usar IP directa para bypass de Cloudflare)
        self.direct_ip = '78.46.100.91'
        if 'www.codigoamigo.com' in self.api_url or 'codigoamigo.com' in self.api_url:
            # Usar HTTPS y la IP directa para el bypass de Cloudflare
            self.api_url_localhost = self.api_url.replace('https://www.codigoamigo.com', f'https://{self.direct_ip}').replace('https://codigoamigo.com', f'https://{self.direct_ip}')
        else:
            self.api_url_localhost = None
        
        # Directorio para guardar imágenes localmente
        script_dir = Path(__file__).parent.absolute()
        self.images_dir = script_dir.parent / 'images' / 'chollos'
        self.images_dir.mkdir(parents=True, exist_ok=True)
        # URL base para las imágenes (ajustar según tu dominio)
        self.images_base_url = 'https://www.codigoamigo.com/images/chollos/'
        
    async def connect(self):
        """Conecta al cliente de Telegram"""
        try:
            self.client = TelegramClient(SESSION_FILE, API_ID, API_HASH)
            
            # Conectar al cliente
            await self.client.connect()
            
            # Si no está autorizado, solicitar código
            if not await self.client.is_user_authorized():
                logger.info(f"Enviando código de verificación a {PHONE_NUMBER}...")
                logger.info("NOTA: Si tienes Telegram abierto en otro dispositivo, el código aparecerá allí.")
                logger.info("Si no recibes el código por SMS, verifica que el número sea correcto.")
                
                await self.client.send_code_request(PHONE_NUMBER)
                
                # Solicitar código al usuario
                code = input("Por favor, ingresa el código que recibiste (por SMS o en la app de Telegram): ").strip()
                
                try:
                    await self.client.sign_in(PHONE_NUMBER, code)
                except SessionPasswordNeededError:
                    # Si requiere contraseña 2FA
                    logger.warning("=" * 60)
                    logger.warning("SE REQUIERE CONTRASEÑA DE 2FA (Autenticación de Dos Factores)")
                    logger.warning("=" * 60)
                    logger.warning("Esta es la contraseña que configuraste cuando activaste")
                    logger.warning("la autenticación de dos factores en Telegram.")
                    logger.warning("")
                    logger.warning("Si no la recuerdas, puedes:")
                    logger.warning("1. Ir a Telegram > Configuración > Privacidad y Seguridad")
                    logger.warning("2. Buscar 'Autenticación de dos pasos'")
                    logger.warning("3. Ver o cambiar la contraseña")
                    logger.warning("")
                    logger.warning("O usar una cuenta de Telegram sin 2FA para este bot.")
                    logger.warning("=" * 60)
                    password = input("\nIngresa tu contraseña de 2FA (o presiona Enter para cancelar): ").strip()
                    
                    if not password:
                        logger.error("Autenticación cancelada.")
                        sys.exit(1)
                    
                    await self.client.sign_in(password=password)
                    logger.info("✓ Autenticación 2FA completada exitosamente.")
            
            logger.info("Conectado a Telegram exitosamente")
            return True
        except SessionPasswordNeededError:
            logger.error("Se requiere contraseña de 2FA. Por favor, ejecuta el script manualmente para ingresarla.")
            sys.exit(1)
        except Exception as e:
            logger.error(f"Error al conectar: {e}")
            return False
    
    async def get_channel_messages(self, channel_username: str, limit: int = 500, 
                                   since_id: Optional[int] = None) -> List[Dict]:
        """
        Obtiene mensajes de un canal de Telegram
        
        Args:
            channel_username: Username del canal (ej: 'canalwolfvvi')
            limit: Número máximo de mensajes a obtener
            since_id: ID del último mensaje procesado (solo obtener mensajes más nuevos)
        
        Returns:
            Lista de diccionarios con información de los mensajes
        """
        try:
            # Obtener entidad del canal
            entity = await self.client.get_entity(channel_username)
            
            if not isinstance(entity, Channel):
                logger.error(f"{channel_username} no es un canal válido")
                return []
            
            messages = []
            min_id = since_id if since_id else 0
            
            async for message in self.client.iter_messages(
                entity,
                limit=limit,
                min_id=min_id,
                reverse=False  # Más antiguos primero
            ):
                if not isinstance(message, Message):
                    continue
                
                # Extraer texto del mensaje
                texto = message.text or message.raw_text or ""
                
                # Obtener imagen si existe
                imagen_url = None
                if message.photo:
                    try:
                        # Descargar la imagen y guardarla localmente
                        imagen_url = await self._descargar_y_guardar_imagen(message, channel_username)
                    except Exception as e:
                        logger.warning(f"Error al descargar imagen del mensaje {message.id}: {e}")
                
                # Formatear fecha
                fecha = message.date.strftime('%Y-%m-%d %H:%M:%S') if message.date else datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                
                messages.append({
                    'id': message.id,
                    'texto': texto,
                    'imagen': imagen_url,
                    'fecha': fecha
                })
            
            logger.info(f"Obtenidos {len(messages)} mensajes del canal {channel_username}")
            return messages
            
        except FloodWaitError as e:
            logger.warning(f"Rate limit alcanzado. Esperando {e.seconds} segundos...")
            await asyncio.sleep(e.seconds)
            return await self.get_channel_messages(channel_username, limit, since_id)
        except Exception as e:
            logger.error(f"Error al obtener mensajes del canal {channel_username}: {e}")
            return []
    
    def get_active_sources(self, tipo: str = 'telegram') -> List[Dict]:
        """
        Obtiene fuentes activas desde la API PHP
        Intenta primero con el dominio normal, y si Cloudflare bloquea, usa la IP directa
        
        Args:
            tipo: Tipo de fuente a obtener (por defecto 'telegram')
        
        Returns:
            Lista de fuentes activas
        """
        # IP directa del servidor como fallback
        DIRECT_IP = '78.46.100.91'
        
        # Construir URLs (dominio normal e IP directa)
        base_url = self.api_url.replace('/telegram-sync.php', '')
        sources_url = f"{base_url}/get-sources.php?tipo={tipo}"
        
        # Headers realistas para evitar bloqueos de Cloudflare
        headers = {
            'X-Telegram-Sync-Token': self.api_token,
            'X-API-Internal': 'true',
            'X-Requested-With': 'TelegramSyncScript',
            'Content-Type': 'application/json',
            'Accept': 'application/json, text/plain, */*',
            'Accept-Language': 'es-ES,es;q=0.9,en;q=0.8',
            'Accept-Encoding': 'gzip, deflate, br',
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer': base_url,
            'Origin': base_url,
            'Connection': 'keep-alive',
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
        
        # Intentar primero con el dominio normal
        try:
            response = requests.get(
                sources_url,
                headers=headers,
                timeout=30,
                allow_redirects=True
            )
            
            # Detectar si la respuesta es HTML (posible challenge de Cloudflare)
            content_type = response.headers.get('Content-Type', '').lower()
            is_html_response = (
                'text/html' in content_type or 
                response.text.strip().startswith('<!DOCTYPE') or 
                response.text.strip().startswith('<html') or
                response.text.strip().startswith('<')
            )
            
            if is_html_response:
                logger.warning("Cloudflare está bloqueando la petición (respuesta HTML detectada)")
                logger.info("Intentando con IP directa (bypass de Cloudflare)...")
                
                # Intentar con la IP directa usando HTTPS (bypass de Cloudflare)
                # El header Host es crucial para que Nginx use el virtual host correcto
                localhost_url = f"https://{self.direct_ip}/api/get-sources.php?tipo={tipo}"
                localhost_headers = headers.copy()
                localhost_headers['Host'] = 'www.codigoamigo.com'
                try:
                    response = requests.get(
                        localhost_url,
                        headers=localhost_headers,
                        timeout=30,
                        allow_redirects=True,
                        verify=False # Importante: ignorar error de certificado al usar IP
                    )
                    logger.info("✓ Conexión exitosa usando IP directa")
                except requests.exceptions.RequestException as e:
                    logger.error(f"Error al conectar con IP directa: {e}")
                    return []
            else:
                logger.info("✓ Conexión exitosa usando dominio normal")
            
            if response.status_code == 200:
                try:
                    result = response.json()
                    if result.get('success'):
                        logger.info(f"Obtenidas {len(result.get('sources', []))} fuentes activas")
                        return result.get('sources', [])
                    else:
                        logger.error(f"Error en API al obtener fuentes: {result.get('error')}")
                        return []
                except (ValueError, json.JSONDecodeError) as json_error:
                    logger.error(f"Error al parsear JSON de la respuesta: {json_error}")
                    logger.error(f"Respuesta recibida (primeros 500 caracteres): {response.text[:500]}")
                    return []
            else:
                logger.error(f"Error HTTP al obtener fuentes: {response.status_code}")
                logger.error(f"Headers de respuesta: {dict(response.headers)}")
                logger.error(f"Respuesta recibida (primeros 500 caracteres): {response.text[:500]}")
                return []
                
        except requests.exceptions.RequestException as e:
            logger.error(f"Error al obtener fuentes desde API: {e}")
            # Intentar con IP directa como último recurso (bypass de Cloudflare)
            logger.info("Intentando con IP directa como fallback (bypass de Cloudflare)...")
            try:
                localhost_url = f"https://{self.direct_ip}/api/get-sources.php?tipo={tipo}"
                localhost_headers = headers.copy()
                localhost_headers['Host'] = 'www.codigoamigo.com'
                response = requests.get(
                    localhost_url,
                    headers=localhost_headers,
                    timeout=30,
                    allow_redirects=True,
                    verify=False
                )
                if response.status_code == 200:
                    try:
                        result = response.json()
                        if result.get('success'):
                            logger.info(f"✓ Obtenidas {len(result.get('sources', []))} fuentes activas usando IP directa")
                            return result.get('sources', [])
                    except (ValueError, json.JSONDecodeError):
                        pass
            except Exception as localhost_error:
                logger.error(f"Error al conectar con localhost: {localhost_error}")
            return []
    
    def send_to_api(self, fuente_id: str, mensajes: List[Dict], 
                   categoria: Optional[str] = None,
                   estado_por_defecto: int = 0,
                   reescribir_automatico: bool = False,
                   eliminar_anteriores: bool = False) -> Dict:
        """
        Envía mensajes a la API PHP para procesamiento
        
        Args:
            fuente_id: ID de la fuente en MongoDB
            mensajes: Lista de mensajes a procesar
            categoria: Categoría por defecto para los chollos
            estado_por_defecto: Estado por defecto (0 = inactivo, 1 = activo)
            reescribir_automatico: Si reescribir automáticamente con Groq
        
        Returns:
            Respuesta de la API
        """
        if not mensajes:
            logger.warning("No hay mensajes para enviar")
            return {'success': False, 'error': 'No hay mensajes'}
        
        payload = {
            'token': self.api_token,
            'fuente_id': fuente_id,
            'mensajes': mensajes,
            'categoria': categoria,
            'estado_por_defecto': estado_por_defecto,
            'reescribir_automatico': reescribir_automatico,
            'eliminar_anteriores': eliminar_anteriores
        }
        
        # Headers para la petición
        headers = {
            'Content-Type': 'application/json',
            'X-API-Internal': 'true',
            'X-Requested-With': 'TelegramSyncScript'
        }
        
        # Si tenemos URL de localhost, usar SIEMPRE localhost (bypass de Cloudflare)
        # Esto evita que Cloudflare bloquee las peticiones cuando se ejecuta desde el servidor
        try:
            if self.api_url_localhost:
                localhost_headers = headers.copy()
                localhost_headers['Host'] = 'www.codigoamigo.com'
                logger.debug("Usando localhost para bypass de Cloudflare...")
                
                response = requests.post(
                    self.api_url_localhost,
                    json=payload,
                    timeout=300,
                    headers=localhost_headers,
                    verify=False # Ignorar errores de certificado al usar IP directa
                )
                logger.debug("✓ Petición enviada usando IP directa")
            else:
                # Si no hay URL de localhost, usar el dominio normal
                response = requests.post(
                    self.api_url,
                    json=payload,
                    timeout=300,
                    headers=headers
                )
                
                # Detectar si Cloudflare bloquea
                content_type = response.headers.get('Content-Type', '').lower()
                is_html_response = (
                    'text/html' in content_type or 
                    response.text.strip().startswith('<!DOCTYPE') or 
                    response.text.strip().startswith('<html') or
                    response.text.strip().startswith('<')
                )
                
                if is_html_response or response.status_code == 403:
                    logger.error("Cloudflare está bloqueando la petición y no hay localhost disponible")
                    return {'success': False, 'error': 'Cloudflare bloqueando y sin acceso localhost'}
            
            # Procesar la respuesta (tanto de localhost como del dominio normal)
            if response.status_code == 200:
                try:
                    result = response.json()
                    # Validar que sea un diccionario
                    if not isinstance(result, dict):
                        logger.error(f"API devolvió un formato inesperado: {type(result)}")
                        logger.error(f"Contenido: {result}")
                        return {'success': False, 'error': 'Formato de respuesta inválido'}
                    
                    logger.info(f"API respondió: {result.get('resultados', {})}")
                    return result
                except (ValueError, json.JSONDecodeError) as e:
                    logger.error(f"Error al parsear JSON de la API: {e}")
                    logger.error(f"Respuesta recibida: {response.text[:500]}")
                    return {'success': False, 'error': 'Error al parsear respuesta JSON'}
            else:
                logger.error(f"Error en API: {response.status_code} - {response.text[:500]}")
                return {'success': False, 'error': f'HTTP {response.status_code}'}
                
        except requests.exceptions.RequestException as e:
            logger.error(f"Error al enviar a API: {e}")
            return {'success': False, 'error': str(e)}
    
    async def sync_channel(self, fuente_id: str, channel_username: str, 
                         ultimo_mensaje_id: Optional[int] = None,
                         limit: int = 50,
                         configuracion: Optional[Dict] = None,
                         eliminar_anteriores: bool = False) -> Dict:
        """
        Sincroniza un canal completo
        
        Args:
            fuente_id: ID de la fuente en MongoDB
            channel_username: Username del canal
            ultimo_mensaje_id: ID del último mensaje procesado
            limit: Límite de mensajes a procesar por ejecución
            configuracion: Configuración de la fuente (opcional)
            eliminar_anteriores: Si eliminar chollos anteriores antes de procesar
        
        Returns:
            Resultado de la sincronización
        """
        logger.info(f"Iniciando sincronización de {channel_username} (fuente_id: {fuente_id})")
        
        # Obtener mensajes nuevos
        mensajes = await self.get_channel_messages(
            channel_username,
            limit=limit,
            since_id=ultimo_mensaje_id
        )
        
        if not mensajes:
            logger.info("No hay mensajes nuevos")
            return {
                'success': True,
                'mensajes_procesados': 0,
                'mensajes_creados': 0
            }
        
        # Obtener configuración por defecto
        if configuracion is None:
            configuracion = {}
        elif isinstance(configuracion, list):
            # Si viene como lista, convertir a diccionario vacío (probablemente es un array vacío de MongoDB)
            if len(configuracion) == 0:
                configuracion = {}
            else:
                # Si tiene elementos, intentar convertir el primero a diccionario
                logger.warning(f"Configuración en formato lista con {len(configuracion)} elementos, usando valores por defecto")
                configuracion = {}
        elif not isinstance(configuracion, dict):
            # Si configuracion no es un diccionario, convertir o usar valores por defecto
            logger.warning(f"Configuración en formato inesperado: {type(configuracion)}, usando valores por defecto")
            configuracion = {}
        
        categoria = configuracion.get('categoria_defecto', 'general') if isinstance(configuracion, dict) else 'general'
        estado_por_defecto = configuracion.get('estado_defecto', 1) if isinstance(configuracion, dict) else 1  # Por defecto activo
        reescribir_automatico = configuracion.get('reescribir_automatico', True) if isinstance(configuracion, dict) else True  # Por defecto activado para usar Groq
        
        # Procesar mensajes en lotes para evitar timeouts
        # Procesar en lotes de 5 mensajes (reducido para evitar timeouts con Groq)
        BATCH_SIZE = 5
        resultados_totales = {
            'procesados': 0,
            'creados': 0,
            'duplicados': 0,
            'errores': 0,
            'eliminados_anteriores': 0,
            'errores_detalle': []
        }
        
        # Si eliminamos anteriores, hacerlo solo en el primer lote
        eliminar_anteriores_lote = eliminar_anteriores
        nombre_fuente = ''
        
        for i in range(0, len(mensajes), BATCH_SIZE):
            lote = mensajes[i:i + BATCH_SIZE]
            logger.info(f"Procesando lote {i//BATCH_SIZE + 1} de {(len(mensajes) + BATCH_SIZE - 1)//BATCH_SIZE} ({len(lote)} mensajes)")
            
            # Enviar lote a API
            resultado = self.send_to_api(
                fuente_id=fuente_id,
                mensajes=lote,
                categoria=categoria,
                estado_por_defecto=estado_por_defecto,
                reescribir_automatico=reescribir_automatico,
                eliminar_anteriores=eliminar_anteriores_lote  # Solo eliminar en el primer lote
            )
            
            # Después del primer lote, no eliminar anteriores
            eliminar_anteriores_lote = False
            
            # Guardar nombre de fuente del primer resultado exitoso
            if resultado.get('success') and not nombre_fuente:
                nombre_fuente = resultado.get('fuente', '')
            
            # Acumular resultados
            if resultado.get('success'):
                resultados_api = resultado.get('resultados', {})
                if isinstance(resultados_api, dict):
                    resultados_totales['procesados'] += resultados_api.get('procesados', 0)
                    resultados_totales['creados'] += resultados_api.get('creados', 0)
                    resultados_totales['duplicados'] += resultados_api.get('duplicados', 0)
                    resultados_totales['errores'] += resultados_api.get('errores', 0)
                    if 'errores_detalle' in resultados_api:
                        resultados_totales['errores_detalle'].extend(resultados_api['errores_detalle'])
                    if 'eliminados_anteriores' in resultados_api:
                        resultados_totales['eliminados_anteriores'] = resultados_api['eliminados_anteriores']
            else:
                logger.error(f"Error en lote {i//BATCH_SIZE + 1}: {resultado.get('error', 'Error desconocido')}")
                resultados_totales['errores'] += len(lote)
        
        # Crear resultado combinado
        resultado = {
            'success': True,
            'resultados': resultados_totales,
            'fuente': nombre_fuente
        }
        
        # Validar que resultado sea un diccionario
        if not isinstance(resultado, dict):
            logger.error(f"Error: send_to_api devolvió un tipo inesperado: {type(resultado)}")
            logger.error(f"Contenido: {resultado}")
            return {'success': False, 'error': 'Error en respuesta de API'}
        
        if resultado.get('success'):
            resultados = resultado.get('resultados', {})
            if isinstance(resultados, dict):
                logger.info(f"Sincronización completada: {resultados.get('creados', 0)} creados, "
                           f"{resultados.get('duplicados', 0)} duplicados, "
                           f"{resultados.get('errores', 0)} errores")
            else:
                logger.warning(f"Resultados en formato inesperado: {type(resultados)}")
        else:
            logger.error(f"Error en sincronización: {resultado.get('error', 'Error desconocido')}")
        
        return resultado
    
    async def _descargar_y_guardar_imagen(self, message, channel_username):
        """
        Descarga una imagen de Telegram y la guarda localmente
        Retorna la URL pública de la imagen guardada
        """
        try:
            if not message.photo:
                return None
            
            # Generar nombre único para la imagen basado en el ID del mensaje y un hash
            message_id_str = str(message.id)
            # Crear un hash del ID del mensaje para evitar colisiones
            hash_id = hashlib.md5(f"{message_id_str}_{channel_username}".encode()).hexdigest()[:8]
            # Nombre del archivo: mensaje_id_hash.jpg
            filename = f"{message_id_str}_{hash_id}.jpg"
            filepath = self.images_dir / filename
            
            # Si la imagen ya existe, retornar su URL
            if filepath.exists():
                imagen_url = f"{self.images_base_url}{filename}"
                logger.debug(f"Imagen ya existe para mensaje {message.id}: {imagen_url}")
                return imagen_url
            
            # Descargar la imagen usando Telethon
            try:
                # Descargar la imagen en bytes
                image_data = await self.client.download_media(message.photo, file=bytes)
                
                if image_data:
                    # Guardar la imagen en el directorio local
                    with open(filepath, 'wb') as f:
                        f.write(image_data)
                    
                    # Generar URL pública
                    imagen_url = f"{self.images_base_url}{filename}"
                    logger.info(f"Imagen descargada y guardada para mensaje {message.id}: {imagen_url}")
                    return imagen_url
                else:
                    logger.warning(f"No se pudo descargar la imagen para mensaje {message.id}")
                    return None
                    
            except Exception as download_error:
                logger.warning(f"Error al descargar imagen para mensaje {message.id}: {download_error}")
                return None
                
        except Exception as e:
            logger.error(f"Error al procesar imagen para mensaje {message.id}: {e}")
            return None
    
    async def _obtener_url_publica_foto(self, message, channel_username):
        """
        Obtiene la URL pública de una foto descargándola temporalmente y usando la Bot API
        Método alternativo: descargar la imagen y obtener su file_id desde la Bot API
        """
        try:
            if not self.bot_token or not message.photo:
                return None
            
            from telethon.tl.types import Photo, PhotoSize
            
            try:
                # Obtener el objeto Photo
                photo_obj = message.photo
                if not isinstance(photo_obj, Photo):
                    return None
                
                # Intentar obtener el PhotoSize más grande que tenga location
                largest_size = None
                if hasattr(photo_obj, 'sizes') and photo_obj.sizes:
                    # Buscar el PhotoSize más grande que tenga location
                    for size in reversed(photo_obj.sizes):  # Reversed para obtener el más grande primero
                        if isinstance(size, PhotoSize) and hasattr(size, 'location'):
                            largest_size = size
                            break
                
                if not largest_size or not hasattr(largest_size, 'location'):
                    logger.debug(f"No se encontró PhotoSize con location para mensaje {message.id}")
                    return None
                
                # Usar el método de Telethon para obtener el file_id
                # Necesitamos usar el método download_file para obtener el file_id de Bot API
                # Alternativa: usar el método de obtener el file_id directamente desde el Photo
                from telethon.utils import pack_bot_file_id
                
                # Intentar empaquetar el Photo completo
                try:
                    file_id = pack_bot_file_id(photo_obj)
                    if file_id:
                        # Obtener la URL pública usando la Bot API
                        bot_api_url = f"https://api.telegram.org/bot{self.bot_token}/getFile"
                        params = {'file_id': file_id}
                        
                        response = requests.get(bot_api_url, params=params, timeout=10)
                        if response.status_code == 200:
                            result = response.json()
                            if result.get('ok') and 'result' in result:
                                file_path = result['result'].get('file_path')
                                if file_path:
                                    # Construir la URL pública
                                    public_url = f"https://api.telegram.org/file/bot{self.bot_token}/{file_path}"
                                    logger.info(f"URL pública obtenida para mensaje {message.id}: {public_url}")
                                    return public_url
                except Exception as pack_error:
                    logger.debug(f"Error al empaquetar file_id para mensaje {message.id}: {pack_error}")
                    # Si falla, intentar método alternativo: descargar y usar la Bot API
                    # Por ahora, retornamos None ya que el método de pack_bot_file_id no funciona bien
                    return None
                    
            except Exception as convert_error:
                logger.debug(f"Error al procesar photo para mensaje {message.id}: {convert_error}")
                return None
            
            return None
        except Exception as e:
            logger.debug(f"Error al obtener URL pública de foto para mensaje {message.id}: {e}")
            return None
    
    async def disconnect(self):
        """Desconecta el cliente"""
        if self.client:
            await self.client.disconnect()
            logger.info("Desconectado de Telegram")


async def main():
    """Función principal"""
    
    monitor = TelegramMonitor()
    
    # Obtener fuente_id desde argumentos o variable de entorno
    fuente_id = os.getenv('FUENTE_ID') or (sys.argv[1] if len(sys.argv) > 1 else None)
    channel_username = os.getenv('CHANNEL_USERNAME') or (sys.argv[2] if len(sys.argv) > 2 else None)
    ultimo_mensaje_id = os.getenv('ULTIMO_MENSAJE_ID')
    if ultimo_mensaje_id:
        ultimo_mensaje_id = int(ultimo_mensaje_id)
    else:
        ultimo_mensaje_id = None
    
    # Si no se proporcionan argumentos, obtener fuentes automáticamente
    if not fuente_id or not channel_username:
        logger.info("No se proporcionaron argumentos, obteniendo fuentes activas desde la API...")
        sources = monitor.get_active_sources('telegram')
        
        if not sources:
            logger.error("No se encontraron fuentes activas de Telegram")
            logger.error("Uso alternativo: python telegram_monitor.py <fuente_id> <channel_username> [ultimo_mensaje_id]")
            logger.error("O configura las variables de entorno: FUENTE_ID, CHANNEL_USERNAME, ULTIMO_MENSAJE_ID")
            sys.exit(1)
        
        # Procesar todas las fuentes activas
        try:
            # Conectar
            if not await monitor.connect():
                sys.exit(1)
            
            resultados_totales = {
                'fuentes_procesadas': 0,
                'fuentes_exitosas': 0,
                'fuentes_fallidas': 0
            }
            
            for source in sources:
                fuente_id = source['id']
                channel_username = source['url']  # En Telegram, la URL es el username del canal
                ultimo_mensaje_id = source.get('ultimo_mensaje_id')
                configuracion = source.get('configuracion', {})
                
                logger.info(f"Procesando fuente: {source['nombre']} ({channel_username})")
                
                # NO eliminar chollos anteriores para evitar duplicados
                # El sistema de detección de duplicados evitará crear chollos que ya existen
                # Solo procesar mensajes nuevos usando ultimo_mensaje_id
                eliminar_anteriores = False
                
                resultado = await monitor.sync_channel(
                    fuente_id=fuente_id,
                    channel_username=channel_username,
                    ultimo_mensaje_id=ultimo_mensaje_id,  # Usar ultimo_mensaje_id para solo procesar mensajes nuevos
                    limit=500,  # Aumentado para obtener más mensajes
                    configuracion=configuracion,
                    eliminar_anteriores=eliminar_anteriores
                )
                
                resultados_totales['fuentes_procesadas'] += 1
                if resultado.get('success'):
                    resultados_totales['fuentes_exitosas'] += 1
                else:
                    resultados_totales['fuentes_fallidas'] += 1
            
            logger.info(f"Sincronización completada: {resultados_totales['fuentes_exitosas']}/{resultados_totales['fuentes_procesadas']} fuentes exitosas")
            sys.exit(0 if resultados_totales['fuentes_fallidas'] == 0 else 1)
            
        except KeyboardInterrupt:
            logger.info("Interrumpido por el usuario")
        except Exception as e:
            logger.error(f"Error inesperado: {e}", exc_info=True)
            sys.exit(1)
        finally:
            await monitor.disconnect()
    else:
        # Modo manual: sincronizar una fuente específica
        try:
            # Conectar
            if not await monitor.connect():
                sys.exit(1)
            
            # Sincronizar canal
            resultado = await monitor.sync_channel(
                fuente_id=fuente_id,
                channel_username=channel_username,
                ultimo_mensaje_id=ultimo_mensaje_id,
                limit=500  # Aumentado para obtener más mensajes
            )
            
            if resultado.get('success'):
                logger.info("Sincronización completada exitosamente")
                sys.exit(0)
            else:
                logger.error(f"Error en sincronización: {resultado.get('error')}")
                sys.exit(1)
                
        except KeyboardInterrupt:
            logger.info("Interrumpido por el usuario")
        except Exception as e:
            logger.error(f"Error inesperado: {e}", exc_info=True)
            sys.exit(1)
        finally:
            await monitor.disconnect()


if __name__ == '__main__':
    import asyncio
    asyncio.run(main())

