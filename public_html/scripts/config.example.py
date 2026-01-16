# Configuración para telegram_monitor.py
# Copia este archivo a config.py y completa los valores

# Credenciales de Telegram API
# Obtén estas credenciales desde https://my.telegram.org/apps
API_ID = 12345678  # Tu API ID
API_HASH = 'tu_api_hash_aqui'  # Tu API Hash

# Número de teléfono (con código de país, ej: +34612345678)
PHONE_NUMBER = '+34612345678'

# Archivo de sesión (se creará automáticamente)
SESSION_FILE = 'telegram_session.session'

# URL de la API PHP
API_URL = 'https://www.codigoamigo.com/api/telegram-sync.php'

# Token secreto para autenticación con la API
# Debe coincidir con TELEGRAM_SYNC_TOKEN en config/ai_config.php
API_TOKEN = 'cambiar_token_secreto_aqui'

# Token del bot de Telegram para obtener URLs públicas de imágenes
# Debe coincidir con TELEGRAM_BOT_TOKEN en config/ai_config.php
TELEGRAM_BOT_TOKEN = 'tu_bot_token_aqui'




