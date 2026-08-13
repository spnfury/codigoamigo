<?php
/**
 * Librería de Detección de Bots
 * Identifica si un visitante es un bot/crawler basado en su User-Agent
 */

function isBot($userAgent = null) {
    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    // Lista de bots comunes (Google, Bing, Yahoo, Redes Sociales, SEO Tools, etc.)
    $bots = [
        'googlebot', 'bingbot', 'yandex', 'baiduspider', 'facebookexternalhit', 
        'twitterbot', 'rogerbot', 'linkedinbot', 'embedly', 'quora link preview', 
        'showyoubot', 'outbrain', 'pinterest/0.', 'developers.google.com/+/web/snippet', 
        'slackbot', 'vkShare', 'W3C_Validator', 'redditbot', 'applebot', 
        'whatsapp', 'flipboard', 'tumblr', 'bitlybot', 'skypeuripreview', 
        'nuzzel', 'discordbot', 'google page speed', 'qwantify', 'pinterest', 
        'instagram', 'telegrambot', 'mj12bot', 'ahrefsbot', 'semrushbot', 
        'dotbot', 'petalbot', 'serpstatbot', 'geedoshinfinder', 'geedoshopproductfinder', 
        'amazonbot', 'gptbot', 'claudebot', 'anthropic-ai', 'cohere-ai', 
        'bytespider', 'ccbot', 'diffbot', 'duckduckbot', 'ia_archiver', 
        'linkdexbot', 'ltx71', 'meanpathbot', 'megaindex', 'netestate ne crawler', 
        'screaming frog', 'seo-rus', 'seokicks', 'serpstatbot', 'spbot', 
        'uptimerobot', 'zoominfobot', 'adidxbot', 'blexbot', 'ezooms', 
        'mauibot', 'panscient', 'seznambot', 'turnitbot', 'voilabot', 
        'searchmetricsbot', 'proximic', 'sogou', 'exabot', 'facebot',
        'yandexbot', 'adsbot-google', 'mediapartners-google', 'apis-google',
        'curl', 'wget', 'python-requests', 'scrapy', 'http_get', 'lwp-trivial'
    ];

    $userAgentLower = strtolower($userAgent);
    
    foreach ($bots as $bot) {
        if (strpos($userAgentLower, $bot) !== false) {
            return true;
        }
    }
    
    // Detección genérica de "bot", "crawl", "spider" (Cuidado con falsos positivos, pero seguro para redirects)
    if (strpos($userAgentLower, 'bot') !== false && strpos($userAgentLower, 'cubot') === false) { // Excluir cubot (marca de móviles) si existe
        return true;
    }
    if (strpos($userAgentLower, 'spider') !== false) {
        return true;
    }
    if (strpos($userAgentLower, 'crawl') !== false) {
        return true;
    }
    
    return false;
}

/**
 * Verifica si es un crawler de redes sociales (útil para previews)
 */
function isSocialCrawler($userAgent = null) {
    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    $socialBots = [
        'facebookexternalhit', 'twitterbot', 'linkedinbot', 'whatsapp', 
        'telegrambot', 'discordbot', 'slackbot', 'pinterest', 'instagram'
    ];
    
    $userAgentLower = strtolower($userAgent);
    foreach ($socialBots as $bot) {
        if (strpos($userAgentLower, $bot) !== false) {
            return true;
        }
    }
    
    return false;
}
?>
