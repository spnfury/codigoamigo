<?php

$environment = getenv('SENTRY_ENVIRONMENT') ?: (defined('ENVIRONMENT') ? ENVIRONMENT : (getenv('APP_ENV') ?: 'production'));

$release = getenv('SENTRY_RELEASE') ?: (defined('APP_VERSION') ? APP_VERSION : null);

if (!$release) {
    $revisionFile = __DIR__ . '/../REVISION';

    if (is_readable($revisionFile)) {
        $candidate = trim((string) file_get_contents($revisionFile));
        if ($candidate !== '') {
            $release = $candidate;
        }
    }
}

if (!$release) {
    $release = 'codigoamigo@unknown';
}

// DSN y token viven en env / private/api_secrets.php (fuera de git)
$sentry_secrets_path = dirname(__DIR__, 2) . '/private/api_secrets.php';
if (is_readable($sentry_secrets_path)) {
    require_once $sentry_secrets_path;
}
$sentry_env = function ($key) {
    if (!empty($_ENV[$key])) return $_ENV[$key];
    $v = getenv($key);
    return ($v !== false && $v !== '') ? $v : '';
};

return [
    'dsn' => $sentry_env('SENTRY_DSN'),
    'auth_token' => $sentry_env('SENTRY_AUTH_TOKEN'),
    'org_slug' => 'sergi-rodriguez',
    'options' => [
        'environment' => $environment,
        'release' => $release,
        'traces_sample_rate' => 0.0,
        'profiles_sample_rate' => 0.0,
        'send_default_pii' => false,
        // Filtrar errores de Google Ads que no son críticos
        'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
            // Filtrar errores de adsbygoogle.push()
            $message = $event->getMessage();
            $exceptions = $event->getExceptions();
            
            // Filtrar errores de JSON.parse que no son críticos (a menudo vienen de respuestas malformadas)
            if ($message && (
                strpos($message, 'Unexpected non-whitespace character after JSON') !== false ||
                strpos($message, 'Unexpected token') !== false && strpos($message, 'JSON') !== false
            )) {
                return null; // No enviar a Sentry
            }
            
            // Filtrar errores de Google Ads que no son críticos
            if ($message && (
                strpos($message, 'adsbygoogle.push() error') !== false ||
                strpos($message, 'All \'ins\' elements in the DOM with class=adsbygoogle already have ads') !== false ||
                strpos($message, 'adsbygoogle') !== false && strpos($message, 'already have ads') !== false ||
                strpos($message, 'enable_page_level_ads') !== false ||
                strpos($message, 'Only one \'enable_page_level_ads\' allowed per page') !== false ||
                strpos($message, 'Accessing domItems after disposal') !== false ||
                strpos($message, 'domItems after disposal') !== false
            )) {
                return null; // No enviar a Sentry
            }
            
            // Filtrar desde excepciones
            foreach ($exceptions as $exception) {
                $exceptionValue = $exception->getValue();
                $exceptionType = $exception->getType();
                
                // Filtrar errores de JSON.parse
                if ($exceptionType === 'SyntaxError' && $exceptionValue && (
                    strpos($exceptionValue, 'Unexpected non-whitespace character after JSON') !== false ||
                    strpos($exceptionValue, 'Unexpected token') !== false && strpos($exceptionValue, 'JSON') !== false
                )) {
                    return null; // No enviar a Sentry
                }
                
                if ($exceptionValue && (
                    strpos($exceptionValue, 'adsbygoogle.push() error') !== false ||
                    strpos($exceptionValue, 'All \'ins\' elements in the DOM with class=adsbygoogle already have ads') !== false ||
                    strpos($exceptionValue, 'adsbygoogle') !== false && strpos($exceptionValue, 'already have ads') !== false ||
                    strpos($exceptionValue, 'enable_page_level_ads') !== false ||
                    strpos($exceptionValue, 'Only one \'enable_page_level_ads\' allowed per page') !== false ||
                    strpos($exceptionValue, 'Accessing domItems after disposal') !== false ||
                    strpos($exceptionValue, 'domItems after disposal') !== false
                )) {
                    return null; // No enviar a Sentry
                }
                
                // Ya asignado arriba
                if ($exceptionType === 'TagError' && $exceptionValue && (
                    strpos($exceptionValue, 'adsbygoogle') !== false ||
                    strpos($exceptionValue, 'enable_page_level_ads') !== false
                )) {
                    return null; // No enviar a Sentry
                }
            }
            
            return $event;
        },
    ],
];

