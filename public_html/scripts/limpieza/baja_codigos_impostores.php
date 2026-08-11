<?php
/**
 * Da de baja (estado = -1) los códigos cuyo enlace lleva a una marca distinta
 * de aquella bajo la que están publicados.
 *
 * Sale de la revisión a mano de cron/detectar_codigos_impostores.php: de los
 * 287 que ese script marca como sospechosos, la mayoría son falsos positivos
 * (los programas de referidos usan dominios de terceros por diseño: Repsol
 * reparte por aklam.io, Airbnb por abnb.me, El Tenedor por tfk.io). Lo que sí
 * es real y se limpia aquí es otra cosa: unos pocos usuarios sembraron SU
 * MISMO enlace de referido en decenas de fichas de marcas que no tienen nada
 * que ver.
 *
 *   letyshops  ww=10508077 / r=7823414  → 25 fichas (Amazon, Adidas, eBay...)
 *   vivid.money angel46W y compañía     → 15 fichas (BBVA, N26, Trading212...)
 *   preferredby.me (berubi)             →  8 fichas (Asos, Simyo, Yoigo...)
 *   morpher.com/invite/alexish818       →  5 fichas (Freecash, Betfair...)
 *   compartir.eniplenitude.es/elena...  →  8 fichas de energía (Endesa, Repsol,
 *                                          Iberdrola, Octopus... todas menos
 *                                          la suya)
 *
 * Quien busca "código Amazon" y se encuentra un enlace de LetyShops se va, y
 * además ensucia el dato de clics salientes por marca, que es el argumento con
 * el que hay que negociar las altas de afiliación.
 *
 * NO se tocan:
 *   - Las fichas que no representan una marca real, sino un invento del propio
 *     usuario para colgar su referido (criptomoney, dinerofacil, mastercoin,
 *     invitationcodes...). Ahí el enlace ES el contenido; no engañan a nadie
 *     que buscara otra cosa, y no reciben tráfico de Google.
 *   - Los dominios de red de afiliación legítimos (aklam.io, abnb.me, tfk.io,
 *     hnst.app, ibkr.com, xkr.ma...). Ver el comentario del detector.
 *
 * Uso: php scripts/limpieza/baja_codigos_impostores.php [--apply]
 * Sin --apply solo enseña lo que haría. Guarda el documento entero de cada
 * código en logs/backup_impostores_<fecha>.json para poder revertir.
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../../inc/includes.php';

$apply = in_array('--apply', $argv, true);
$db = createConnection();
$codigos = $db->selectCollection('codigos');

// Enlaces sembrados en fichas de marcas ajenas. Se listan tal cual aparecen en
// el campo `codigo` para no tener que adivinar por dominio.
$ENLACES_SEMBRADOS = [
    'letyshops.com',
    'vivid.money',
    'preferredby.me',
    'morpher.com/invite/alexish818',
    'compartir.eniplenitude.es',
    'attapoll.app',
    'cryptotabbrowser.com',
    'gamee.com',
    'ferisbiz.com',
    'nube5g.com',
    'venusbbw.net',
    'descargar-dinero.web.app',
    'income.spider.dev',
    'promosapp.page.link',
    'btcswt.com',
    'hype.games',
    'n26.com/r/',
    // Enlaces que no salen del sitio: uno apunta al propio codigoamigo.com y
    // otro al redirector de campañas de email, que fuera del correo no lleva
    // a ningún sitio útil.
    'codigoamigo.com/bienvenido_de_nuevo',
    'url9572.codigoamigo.com',
    'http://www.codigoamigo.com',
];

// Fichas que no son una marca real, sino un contenedor que el propio usuario
// creó para su referido. Quitarles el enlace las deja vacías sin arreglar nada.
$MARCAS_NO_REALES = [
    'criptomoney', 'dineromoneycripto', 'dinerofacil', 'bancaycriptomonedas',
    'mastercoin', 'bitcoinblack', 'invitationcodes', 'cashbackdeals',
    'bitcoin', 'paypay', 'promosapp',
    // Estas describen la categoría del propio enlace, no una empresa: la ficha
    // 'navegadorcrypto' es literalmente CryptoTab, 'promos' es PromosApp.
    'navegadorcrypto', 'dinerorapido', 'promos',
];

// Excepción: si el enlace es de la marca bajo la que está publicado, es
// legítimo aunque el dominio esté en la lista. Se compara contra el dominio
// registrable, no contra la URL entera: n26.com/r/xxx en la ficha de N26 es su
// propio programa de referidos, mientras que venusbbw.net/n26 no lo es aunque
// lleve "n26" en la ruta. La igualdad exacta va sin mínimo de longitud porque
// hay marcas de tres caracteres ('n26', 'voi', 'dia').
$coincide_con_marca = function (string $slug, string $url): bool {
    $slug_n = preg_replace('/[^a-z0-9]/', '', strtolower($slug));
    if ($slug_n === '') return false;

    // Dominio registrable: la etiqueta anterior al TLD, no el subdominio. Sin
    // esto 'compartir.eniplenitude.es' se leía como "compartir" y los códigos
    // que Plenitude reparte en su propia ficha salían marcados como ajenos.
    $host = preg_replace('/^www\./i', '', strtolower((string)parse_url($url, PHP_URL_HOST)));
    $partes = explode('.', $host);
    $n = count($partes);
    if ($n >= 3 && in_array($partes[$n - 2], ['co', 'com', 'org', 'net'], true)) {
        $dominio = $partes[$n - 3];   // marca.co.uk, marca.com.es
    } elseif ($n >= 2) {
        $dominio = $partes[$n - 2];
    } else {
        $dominio = $partes[0] ?? '';
    }
    $dominio_n = preg_replace('/[^a-z0-9]/', '', $dominio);
    if ($dominio_n === '') return false;

    if ($slug_n === $dominio_n) return true;
    return strlen($slug_n) >= 4 && (str_contains($dominio_n, $slug_n) || str_contains($slug_n, $dominio_n));
};

$a_dar_de_baja = [];
$excluidos = ['marca_no_real' => 0, 'coincide' => 0];

$cursor = $codigos->find(['estado' => 0, 'codigo' => ['$regex' => '^https?://']]);
foreach ($cursor as $c) {
    $url  = (string)($c['codigo'] ?? '');
    $slug = (string)($c['marca'] ?? '');
    $url_l = strtolower($url);

    $sembrado = null;
    foreach ($ENLACES_SEMBRADOS as $patron) {
        if (str_contains($url_l, $patron)) { $sembrado = $patron; break; }
    }
    if ($sembrado === null) continue;

    if (in_array($slug, $MARCAS_NO_REALES, true)) { $excluidos['marca_no_real']++; continue; }
    if ($coincide_con_marca($slug, $url))         { $excluidos['coincide']++;      continue; }

    $a_dar_de_baja[] = [
        'doc'      => $c,
        'id'       => (string)$c['_id'],
        'marca'    => $slug,
        'url'      => mb_substr($url, 0, 70),
        'sembrado' => $sembrado,
        'clicks'   => (int)($c['totalclicks'] ?? 0),
    ];
}

usort($a_dar_de_baja, fn($a, $b) => [$a['sembrado'], $a['marca']] <=> [$b['sembrado'], $b['marca']]);

printf("Códigos a dar de baja: %d\n", count($a_dar_de_baja));
printf("Excluidos: %d en fichas que no son marca real, %d cuyo enlace sí es de su marca\n\n",
    $excluidos['marca_no_real'], $excluidos['coincide']);

printf("%-24s %-22s %8s  %s\n", 'MARCA', 'ENLACE SEMBRADO', 'CLICKS', 'URL');
echo str_repeat('-', 110) . "\n";
foreach ($a_dar_de_baja as $s) {
    printf("%-24s %-22s %8d  %s\n",
        mb_substr($s['marca'], 0, 23), mb_substr($s['sembrado'], 0, 21), $s['clicks'], $s['url']);
}

if (!$apply) {
    echo "\nSimulación. Añade --apply para aplicarlo.\n";
    exit(0);
}

// Copia de seguridad del documento entero antes de tocar nada.
$backup = __DIR__ . '/../../logs/backup_impostores_' . date('Y-m-d') . '.json';
file_put_contents($backup, json_encode([
    'fecha'   => date('c'),
    'motivo'  => 'baja de códigos cuyo enlace lleva a una marca ajena',
    'codigos' => array_map(fn($s) => json_decode(json_encode($s['doc']), true), $a_dar_de_baja),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "\nCopia de seguridad: $backup\n";

$ok = 0;
foreach ($a_dar_de_baja as $s) {
    try {
        $r = $codigos->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($s['id'])],
            ['$set' => [
                'estado'          => -1,
                'motivo_baja'     => 'enlace a marca ajena (' . $s['sembrado'] . ')',
                'fecha_baja'      => date('Y-m-d H:i:s'),
            ]]
        );
        $ok += $r->getModifiedCount();
    } catch (\Throwable $e) {
        log_error('[impostores] no se pudo dar de baja ' . $s['id'] . ': ' . $e->getMessage());
    }
}

printf("Dados de baja: %d de %d\n", $ok, count($a_dar_de_baja));
log_info('[impostores] bajas aplicadas', ['total' => $ok, 'backup' => $backup]);
