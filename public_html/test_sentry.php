<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/inc/sentry_bootstrap.php';

codigoamigo_init_sentry();

try {
    throw new \RuntimeException('Evento de prueba desde CodigoAmigo');
} catch (\Throwable $exception) {
    \Sentry\captureException($exception);
}

echo "Evento de prueba enviado a Sentry.\n";

