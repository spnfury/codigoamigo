<?php
/**
 * Recolector de estado de las apps móviles.
 *
 * Recorre las apps declaradas en config/apps_moviles.json, inspecciona cada
 * proyecto (versión, identificadores, git, dependencias, requisitos de tienda)
 * y vuelca el resultado en myphp/data/apps_estado.json.
 *
 * ¿Por qué un recolector y no leerlo desde el panel? El pool PHP-FPM de
 * codigoamigo tiene open_basedir limitado a su docroot, así que la web NO
 * puede leer /home/planazosbcn, /var/www/radargas.com ni siquiera
 * ../mobile-app. Este script corre por CLI (sin esa restricción) y deja un
 * JSON que el panel sí puede leer.
 *
 * Uso: php cron/recolectar_estado_apps.php
 * Cron sugerido: cada hora.
 */

define('CRON_MODE', true);

$base       = dirname(__DIR__);
$manifiesto = $base . '/config/apps_moviles.json';
$destino    = $base . '/myphp/data/apps_estado.json';

if (!is_file($manifiesto)) {
    fwrite(STDERR, "No existe el manifiesto: $manifiesto\n");
    exit(1);
}

$cfg = json_decode(file_get_contents($manifiesto), true);
if (!is_array($cfg) || empty($cfg['apps'])) {
    fwrite(STDERR, "Manifiesto ilegible o sin apps\n");
    exit(1);
}

/** Ejecuta un comando dentro de una ruta y devuelve la salida recortada. */
function cmd(string $ruta, string $comando): string {
    if (!is_dir($ruta)) return '';
    $out = @shell_exec('cd ' . escapeshellarg($ruta) . ' && ' . $comando . ' 2>/dev/null');
    return trim((string)$out);
}

/** Lee un JSON de disco devolviendo array vacío si no se puede. */
function leer_json(string $ruta): array {
    if (!is_file($ruta)) return [];
    $d = json_decode((string)file_get_contents($ruta), true);
    return is_array($d) ? $d : [];
}

/** Dimensiones de un PNG sin depender de extensiones externas. */
function dimensiones(string $f): ?string {
    if (!is_file($f)) return null;
    $s = @getimagesize($f);
    return $s ? ($s[0] . 'x' . $s[1]) : null;
}

$resultado = [
    'generado'       => date('c'),
    'generado_human' => date('Y-m-d H:i'),
    'apps'           => [],
];

foreach ($cfg['apps'] as $app) {
    $ruta   = $app['ruta'] ?? '';
    $existe = is_dir($ruta);

    $info = [
        'id'               => $app['id'] ?? '?',
        'nombre'           => $app['nombre'] ?? ($app['id'] ?? '?'),
        'tipo'             => $app['tipo'] ?? 'desconocido',
        'ruta'             => $ruta,
        'web'              => $app['web'] ?? '',
        'estado_objetivo'  => $app['estado_objetivo'] ?? '',
        'notas'            => $app['notas'] ?? '',
        'existe'           => $existe,
        'version'          => null,
        'android_package'  => $app['tiendas']['android'] ?? null,
        'ios_bundle'       => $app['tiendas']['ios'] ?? null,
        'git'              => ['ultimo_commit' => null, 'fecha' => null, 'sin_commitear' => null, 'remote' => null],
        'checklist'        => [],
    ];

    if (!$existe) {
        $info['checklist'][] = ['ok' => false, 'texto' => 'La ruta del proyecto no existe en este servidor'];
        $resultado['apps'][] = $info;
        continue;
    }

    // ─── Git (común a todos los tipos) ───
    $info['git']['ultimo_commit'] = cmd($ruta, 'git log -1 --format=%h') ?: null;
    $info['git']['fecha']         = cmd($ruta, 'git log -1 --format=%cd --date=short') ?: null;
    $sin = cmd($ruta, 'git status --porcelain | wc -l');
    $info['git']['sin_commitear'] = $sin === '' ? null : (int)$sin;
    $info['git']['remote']        = cmd($ruta, 'git remote get-url origin') ?: null;

    if (($app['tipo'] ?? '') === 'expo') {
        $appJson = leer_json($ruta . '/app.json');
        $expo    = $appJson['expo'] ?? [];
        $pkg     = leer_json($ruta . '/package.json');
        $easJson = leer_json($ruta . '/eas.json');

        $info['version']         = $expo['version'] ?? ($pkg['version'] ?? null);
        $info['android_package'] = $expo['android']['package'] ?? $info['android_package'];
        $info['ios_bundle']      = $expo['ios']['bundleIdentifier'] ?? $info['ios_bundle'];
        $info['sdk']             = $pkg['dependencies']['expo'] ?? null;
        $info['eas_project_id']  = $expo['extra']['eas']['projectId'] ?? '';

        $deps = $pkg['dependencies'] ?? [];

        $info['checklist'] = [
            ['ok' => !empty($expo['name']),                 'texto' => 'Nombre de app definido'],
            ['ok' => !empty($info['android_package']),      'texto' => 'android.package definido'],
            ['ok' => !empty($info['ios_bundle']),           'texto' => 'ios.bundleIdentifier definido'],
            ['ok' => !empty($info['eas_project_id']),       'texto' => 'Proyecto vinculado a EAS (eas init)'],
            ['ok' => !empty($easJson['build']['production']), 'texto' => 'Perfil de build "production" en eas.json'],
            ['ok' => isset($deps['expo-updates']),          'texto' => 'expo-updates instalado (necesario por "channel", da OTA)'],
            ['ok' => isset($deps['expo-constants']),        'texto' => 'expo-constants instalado (lo exige expo-router)'],
            ['ok' => is_dir($ruta . '/node_modules'),       'texto' => 'Dependencias instaladas'],
            ['ok' => dimensiones($ruta . '/assets/icon.png') === '1024x1024', 'texto' => 'Icono 1024x1024'],
            ['ok' => is_file($ruta . '/private/play-service-account.json'), 'texto' => 'Service account de Google Play (para eas submit)'],
            ['ok' => !empty($easJson['submit']['production']['ios']['ascAppId']), 'texto' => 'ascAppId de App Store Connect'],
            ['ok' => is_dir($ruta . '/store-assets'),       'texto' => 'Recursos de ficha de tienda (capturas y gráfico destacado)'],
            ['ok' => !empty($info['git']['remote']),        'texto' => 'Repositorio con remoto (copia fuera del servidor)'],
        ];
    } elseif (($app['tipo'] ?? '') === 'capacitor') {
        $pkg = leer_json($ruta . '/package.json');
        $info['version'] = $pkg['version'] ?? null;
        $info['checklist'] = [
            ['ok' => is_dir($ruta . '/android'), 'texto' => 'Proyecto Android presente en el servidor'],
            ['ok' => is_dir($ruta . '/ios'),     'texto' => 'Proyecto iOS presente en el servidor'],
            ['ok' => !empty($info['git']['remote']), 'texto' => 'Repositorio con remoto'],
        ];
    }

    // Resumen: cuántos puntos del checklist están cumplidos
    $tot = count($info['checklist']);
    $ok  = count(array_filter($info['checklist'], fn($c) => !empty($c['ok'])));
    $info['listos'] = $ok;
    $info['total']  = $tot;
    $info['pct']    = $tot ? (int)round(100 * $ok / $tot) : 0;

    $resultado['apps'][] = $info;
}

if (!is_dir(dirname($destino))) {
    mkdir(dirname($destino), 0755, true);
}
file_put_contents($destino, json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "Estado recolectado: " . count($resultado['apps']) . " apps -> $destino\n";
foreach ($resultado['apps'] as $a) {
    printf("  %-14s %s  %d/%d requisitos\n", $a['id'], str_pad($a['version'] ?? '-', 8), $a['listos'], $a['total']);
}
