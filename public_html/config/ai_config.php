<?php

/**
 * Configuración de APIs de Inteligencia Artificial
 * Para generar FAQs automáticamente
 */

// Configuración de Perplexity AI
define('PERPLEXITY_API_KEY', 'pplx-your-api-key-here');
define('PERPLEXITY_API_URL', 'https://api.perplexity.ai/chat/completions');
define('PERPLEXITY_MODEL', 'llama-3.1-sonar-small-128k-online');

// Configuración de Groq AI
define('GROQ_API_KEY', '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');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL', 'llama-3.1-8b-instant'); // Modelo actualizado (llama3-8b-8192 descontinuado)

// Configuración general de IA
define('AI_MAX_TOKENS', 2000);
define('AI_TEMPERATURE', 0.7);
define('AI_TIMEOUT', 30); // segundos

// Prompts para generar FAQs
define('FAQ_PROMPT_TEMPLATE', 'Genera 8-10 preguntas frecuentes sobre {MARCA_NOMBRE}, especialmente relacionadas con códigos de descuento, promociones, registro y uso de la plataforma. Responde en español y formato JSON con estructura: [{"pregunta": "...", "respuesta": "..."}]');

// Configuración de fallback
define('AI_FALLBACK_ENABLED', true);
define('AI_FALLBACK_PROVIDER', 'groq'); // 'perplexity' o 'groq'

// Configuración para Chollos
// Prompts para reescribir chollos
define('CHOLLO_PROMPT_TITULO', 'Reescribe este título de chollo de forma atractiva y SEO-friendly, manteniendo la información clave del producto y el descuento. Máximo 80 caracteres. Solo devuelve el título reescrito, sin explicaciones:');
define('CHOLLO_PROMPT_DESCRIPCION', 'Reescribe esta descripción de chollo de forma atractiva, destacando las ventajas y beneficios del producto. Usa un tono persuasivo pero honesto. Máximo 200 palabras. Solo devuelve la descripción reescrita, sin explicaciones:');
define('CHOLLO_PROMPT_GENERAL', 'Reescribe este texto de chollo de forma atractiva y profesional, manteniendo toda la información importante sobre el producto y la oferta. Solo devuelve el texto reescrito, sin explicaciones:');

// Configuración para Marcas
// Prompts para generar contenido de marcas
define('MARCA_PROMPT_DESCRIPCION', 'Genera una descripción corta y atractiva (máximo 150 palabras) para la marca {NOMBRE_MARCA} en la categoría {CATEGORIA}. Debe ser SEO-friendly y destacar los beneficios principales. Solo devuelve la descripción, sin explicaciones:');
define('MARCA_PROMPT_DESCRIPCION_LARGA', 'Genera una descripción larga y detallada (mínimo 500 palabras) para la marca {NOMBRE_MARCA} en la categoría {CATEGORIA}. Incluye información sobre la empresa, servicios, beneficios y por qué usar códigos de descuento. Solo devuelve la descripción, sin explicaciones:');
define('MARCA_PROMPT_VENTAJA', 'Genera una ventaja principal atractiva y concisa (máximo 20 palabras) para la marca {NOMBRE_MARCA} en la categoría {CATEGORIA}. Debe ser un beneficio específico y convincente. Solo devuelve la ventaja, sin explicaciones:');

// Configuración de Telegram para Chollos
define('TELEGRAM_BOT_TOKEN', '1208948207:AAF0O45V1zcsp7wjRgbwOrLQ7tNRUHlfnME'); // Token del bot existente
define('TELEGRAM_CHOLLOS_CHAT_ID_SALIDA', '-1003443178963'); // Chat ID del canal @cholloscodigoamigo donde se publicarán los chollos
define('TELEGRAM_CHOLLOS_CHAT_ID_ENTRADA', ''); // Chat ID del canal de donde se importarán los chollos (configurar)
define('TELEGRAM_ADMIN_CHAT_ID', '-1003443178963'); // ID del chat para notificaciones de errores (usando el mismo canal temporalmente)
define('TELEGRAM_SYNC_TOKEN', '1490761ae052abd261a6b7e0f776cb216ad3e00762ab259796b3be8fd6b030ee'); // Token secreto para sincronización con script Python

// ID de afiliado de Amazon (Associate Tag - el tag que aparece en tus enlaces de afiliado)
// Este es diferente del Access Key ID
if (!defined('AMAZON_AFFILIATE_ID')) {
    define('AMAZON_AFFILIATE_ID', 'spnfuryy-21');
}

// Credenciales de Amazon Product Advertising API (PA-API 5.0)
if (!defined('AMAZON_PAAPI_ACCESS_KEY')) {
    define('AMAZON_PAAPI_ACCESS_KEY', 'AKPADPLBNL1764290610'); // Access Key ID
}
if (!defined('AMAZON_PAAPI_SECRET_KEY')) {
    define('AMAZON_PAAPI_SECRET_KEY', 'coh5umXt8Eke0dUjQanq9vIscp2SV9tRLZjdSB'); // Secret Access Key
}
if (!defined('AMAZON_PAAPI_REGION')) {
    define('AMAZON_PAAPI_REGION', 'es'); // Región: es, com, co.uk, etc.
}
if (!defined('AMAZON_PAAPI_HOST')) {
    define('AMAZON_PAAPI_HOST', 'webservices.amazon.es'); // Host según región
}

// Configuración de YouTube Data API v3
// Obtener API key gratis en: https://console.cloud.google.com/apis/credentials
// Habilitar "YouTube Data API v3" en la consola
if (!defined('YOUTUBE_API_KEY')) {
    define('YOUTUBE_API_KEY', 'AIzaSyBO8kzIr4NtCVBxLxQSxGkq8Whw4kHgAqI');
}

// Puedes añadir aquí más keys para rotarlas automáticamente si una se agota
if (!defined('YOUTUBE_API_KEYS')) {
    define('YOUTUBE_API_KEYS', [
        'AIzaSyBO8kzIr4NtCVBxLxQSxGkq8Whw4kHgAqI',
        // 'OTRA_KEY_AQUI',
    ]);
}

?>
