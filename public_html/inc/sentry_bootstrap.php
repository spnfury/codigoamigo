<?php

use Sentry\SentrySdk;

if (!function_exists('codigoamigo_init_sentry')) {
    function codigoamigo_init_sentry(): void
    {
        static $initialized = false;

        if ($initialized) {
            return;
        }

        $configPath = __DIR__ . '/../config/sentry.php';

        if (!file_exists($configPath)) {
            return;
        }

        $config = require $configPath;
        $dsn = $config['dsn'] ?? null;

        if (empty($dsn) || !class_exists(\Sentry\ClientBuilder::class)) {
            return;
        }

        $options = $config['options'] ?? [];
        $options['dsn'] = $dsn;

        try {
            \Sentry\init($options);
            $initialized = true;
        } catch (\Throwable $e) {
            if (function_exists('debug_log')) {
                debug_log('Sentry initialization failed', $e);
            }
        }
    }
}

if (!function_exists('codigoamigo_sentry_capture_exception')) {
    /**
     * Envía una excepción a Sentry si el cliente está inicializado.
     */
    function codigoamigo_sentry_capture_exception($exception): void
    {
        if (!class_exists(SentrySdk::class)) {
            return;
        }

        try {
            $hub = SentrySdk::getCurrentHub();

            if ($hub && $hub->getClient()) {
                $hub->captureException($exception);
            }
        } catch (\Throwable $e) {
            if (function_exists('debug_log')) {
                debug_log('Sentry capture exception failed', $e);
            }
        }
    }
}

if (!function_exists('codigoamigo_sentry_capture_last_error')) {
    /**
     * Convierte el último error fatal en excepción y lo envía a Sentry.
     */
    function codigoamigo_sentry_capture_last_error(): void
    {
        $error = error_get_last();

        if (!$error) {
            return;
        }

        $fatalTypes = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR,
        ];

        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        $exception = new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );

        codigoamigo_sentry_capture_exception($exception);
    }
}

if (!function_exists('codigoamigo_get_sentry_browser_snippet')) {
    function codigoamigo_get_sentry_browser_snippet(): string
    {
        static $rendered = false;
        static $cachedSnippet = null;

        if ($rendered) {
            return '';
        }

        $configPath = __DIR__ . '/../config/sentry.php';

        if (!file_exists($configPath)) {
            $cachedSnippet = '';
            $rendered = true;
            return $cachedSnippet;
        }

        $config = require $configPath;
        $dsn = $config['dsn'] ?? '';

        if (empty($dsn)) {
            $cachedSnippet = '';
            $rendered = true;
            return $cachedSnippet;
        }

        $options = $config['options'] ?? [];
        $environment = $options['environment'] ?? (getenv('APP_ENV') ?: 'production');
        $release = $options['release'] ?? null;

        $configJson = [
            'dsn' => $dsn,
            'environment' => $environment,
            'release' => $release ?: null,
            'autoSessionTracking' => true,
            'sessionSampleRate' => 1.0,
            'tracesSampleRate' => 0.0,
            'replaysSessionSampleRate' => 0.0,
            'replaysOnErrorSampleRate' => 1.0,
            'ignoreErrors' => [
                'adsbygoogle.push() error',
                'All \'ins\' elements in the DOM with class=adsbygoogle already have ads in them',
                'enable_page_level_ads',
                'Only one \'enable_page_level_ads\' allowed per page',
                'Unexpected non-whitespace character after JSON',
                'Accessing domItems after disposal',
            ],
        ];

        if ($configJson['release'] === null) {
            unset($configJson['release']);
        }

        // Función JavaScript para filtrar errores de Google Ads
        $beforeSendJs = <<<'JS'
function(event, hint) {
    var message = event.message || "";
    var exception = event.exception;
    
    // Filtrar errores de JSON.parse que no son críticos (a menudo vienen de respuestas malformadas)
    if (message && (
        message.indexOf("Unexpected non-whitespace character after JSON") !== -1 ||
        (message.indexOf("Unexpected token") !== -1 && message.indexOf("JSON") !== -1)
    )) {
        return null; // No enviar a Sentry
    }
    
    // Filtrar errores de adsbygoogle.push() y otros errores de Google Ads
    if (message && (
        message.indexOf("adsbygoogle.push() error") !== -1 ||
        message.indexOf("All 'ins' elements in the DOM with class=adsbygoogle already have ads") !== -1 ||
        (message.indexOf("adsbygoogle") !== -1 && message.indexOf("already have ads") !== -1) ||
        message.indexOf("enable_page_level_ads") !== -1 ||
        message.indexOf("Only one 'enable_page_level_ads' allowed per page") !== -1 ||
        message.indexOf("Accessing domItems after disposal") !== -1 ||
        message.indexOf("domItems after disposal") !== -1
    )) {
        return null; // No enviar a Sentry
    }
    
    // Filtrar desde excepciones
    if (exception && exception.values) {
        for (var i = 0; i < exception.values.length; i++) {
            var exc = exception.values[i];
            var excValue = exc.value || "";
            var excType = exc.type || "";
            
            // Filtrar errores de JSON.parse
            if (excType === "SyntaxError" && excValue && (
                excValue.indexOf("Unexpected non-whitespace character after JSON") !== -1 ||
                (excValue.indexOf("Unexpected token") !== -1 && excValue.indexOf("JSON") !== -1)
            )) {
                return null; // No enviar a Sentry
            }
            
            if (excType === "TagError" && (
                excValue.indexOf("adsbygoogle") !== -1 ||
                excValue.indexOf("enable_page_level_ads") !== -1
            )) {
                return null; // No enviar a Sentry
            }
            
            if (excValue && (
                excValue.indexOf("adsbygoogle.push() error") !== -1 ||
                excValue.indexOf("All 'ins' elements in the DOM with class=adsbygoogle already have ads") !== -1 ||
                (excValue.indexOf("adsbygoogle") !== -1 && excValue.indexOf("already have ads") !== -1) ||
                excValue.indexOf("enable_page_level_ads") !== -1 ||
                excValue.indexOf("Only one 'enable_page_level_ads' allowed per page") !== -1 ||
                excValue.indexOf("Accessing domItems after disposal") !== -1 ||
                excValue.indexOf("domItems after disposal") !== -1
            )) {
                return null; // No enviar a Sentry
            }
        }
    }
    
    return event;
}
JS;

        $cachedSnippet = '<script src="https://browser.sentry-cdn.com/7.120.0/bundle.tracing.replay.min.js" crossorigin="anonymous"></script>' . "\n";
        $cachedSnippet .= '<script>' . "\n";
        $cachedSnippet .= 'if (window.Sentry) {' . "\n";
        $cachedSnippet .= '  var sentryConfig = ' . json_encode($configJson, JSON_UNESCAPED_SLASHES) . ';' . "\n";
        $cachedSnippet .= '  sentryConfig.beforeSend = ' . $beforeSendJs . ';' . "\n";
        $cachedSnippet .= '  window.Sentry.init(sentryConfig);' . "\n";
        $cachedSnippet .= '}' . "\n";
        $cachedSnippet .= '</script>' . "\n";

        $rendered = true;

        return $cachedSnippet;
    }
}

