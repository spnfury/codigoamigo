<?php
/**
 * Auditoría de la lista de correo antes de reactivarla.
 *
 * Contexto: hay 133.124 usuarios verificados y la lista no se ha usado nunca de
 * verdad. El único envío masivo (newsletter 692c7dad, 2025-11-30) mandó 3.000
 * correos, obtuvo 6 aperturas (0,2%) y 0 clics, y se canceló el 2025-12-09.
 * Ese 0,2% es el patrón de una lista fría que cae directa a spam.
 *
 * El 85% de los registros son de 2018-2021 y no hay ninguna señal de engagement
 * guardada (last_login existe en 1 usuario de 169.959; email_comm en 93), así
 * que no se puede segmentar por actividad reciente. Lo único medible es:
 * validez del correo, salud del dominio, mercado y si el usuario llegó a
 * publicar algún código alguna vez.
 *
 * Este script NO envía nada. Solo determina qué parte de la lista es enviable y
 * en qué orden, para que la reactivación empiece por lo más caliente.
 *
 * Uso:
 *   php cron/auditar_lista_email.php [--marcar] [--sin-mx] [--json]
 *
 *   --marcar  escribe el veredicto en usuarios.email_auditoria (lo consume el
 *             cron de envío). Sin este flag no toca la base de datos.
 *   --sin-mx  salta la comprobación DNS (rápido, pero no descarta dominios muertos)
 */

define('CRON_MODE', true);
require_once __DIR__ . '/../inc/includes.php';

$opts     = getopt('', ['marcar', 'sin-mx', 'json']);
$marcar   = isset($opts['marcar']);
$sin_mx   = isset($opts['sin-mx']);
$como_json = isset($opts['json']);

// Dominios de correo temporal: registros que nunca fueron una persona alcanzable.
$DESECHABLES = [
    'yopmail.com','mailinator.com','guerrillamail.com','10minutemail.com','tempmail.com',
    'trashmail.com','sharklasers.com','getnada.com','temp-mail.org','throwawaymail.com',
    'maildrop.cc','fakeinbox.com','mytemp.email','dispostable.com','mailnesia.com',
];

// TLD fuera del mercado del portal (cupones de comercios españoles). No se
// borran: se dejan al final de la cola, no merecen gastar reputación al principio.
$TLD_FUERA = ['ar','mx','cl','co','pe','ve','uy','ec','bo','py','br','us','fr','it','de','pt'];

$db  = createConnection();
$col = $db->selectCollection('usuarios');

// ─── 1. Usuarios que han publicado algún código: la señal de engagement real ───

echo "Cargando publicadores...\n";
$publicadores = [];
foreach ($db->selectCollection('codigos')->distinct('id_usuario') as $uid) {
    $publicadores[(string)$uid] = true;
}
echo "  " . count($publicadores) . " usuarios han publicado alguna vez\n";

// ─── 2. Recorrido de la lista ───

$cursor = $col->find(
    ['estado' => 1, 'mail' => ['$type' => 'string', '$ne' => '']],
    ['projection' => ['mail' => 1, 'fecha_registro' => 1]]
);

$vistos     = [];  // email normalizado => true, para deduplicar
$mx_cache   = [];  // dominio => bool
$por_dominio = [];
$segmentos  = ['publicadores' => 0, 'recientes' => 0, 'frios' => 0];
$descartes  = ['sintaxis' => 0, 'duplicado' => 0, 'desechable' => 0, 'dominio_muerto' => 0];
$fuera_mercado = 0;
$marcas_bulk = [];
$total = 0;

foreach ($cursor as $u) {
    $total++;
    $mail = strtolower(trim((string)$u['mail']));
    $uid  = (string)$u['_id'];

    $veredicto = null;

    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $descartes['sintaxis']++;
        $veredicto = 'sintaxis_invalida';
    } elseif (isset($vistos[$mail])) {
        $descartes['duplicado']++;
        $veredicto = 'duplicado';
    } else {
        $vistos[$mail] = true;
        $dominio = substr($mail, strpos($mail, '@') + 1);

        if (in_array($dominio, $DESECHABLES, true)) {
            $descartes['desechable']++;
            $veredicto = 'desechable';
        } else {
            // MX cacheado por dominio: 1.980 dominios distintos, no 133.000 consultas.
            if (!$sin_mx) {
                if (!isset($mx_cache[$dominio])) {
                    $mx_cache[$dominio] = checkdnsrr($dominio, 'MX') || checkdnsrr($dominio, 'A');
                }
                if (!$mx_cache[$dominio]) {
                    $descartes['dominio_muerto']++;
                    $veredicto = 'dominio_muerto';
                }
            }
        }

        if ($veredicto === null) {
            // Enviable. Ahora la prioridad.
            if (isset($publicadores[$uid])) {
                $seg = 'publicadores';
            } else {
                $anio = 0;
                $fr = (string)($u['fecha_registro'] ?? '');
                // Formato habitual dd-mm-YYYY HH:mm
                if (preg_match('/(\d{4})/', $fr, $m)) $anio = (int)$m[1];
                $seg = ($anio >= 2022) ? 'recientes' : 'frios';
            }
            $segmentos[$seg]++;
            $veredicto = $seg;

            $tld = substr($dominio, strrpos($dominio, '.') + 1);
            if (in_array($tld, $TLD_FUERA, true)) {
                $fuera_mercado++;
                $veredicto .= '_fuera_mercado';
            }

            $por_dominio[$dominio] = ($por_dominio[$dominio] ?? 0) + 1;
        }
    }

    if ($marcar) {
        $marcas_bulk[] = [
            'updateOne' => [
                ['_id' => $u['_id']],
                ['$set' => ['email_auditoria' => $veredicto, 'email_auditoria_fecha' => date('Y-m-d')]],
            ],
        ];
        if (count($marcas_bulk) >= 1000) {
            $col->bulkWrite($marcas_bulk);
            $marcas_bulk = [];
        }
    }

    if ($total % 20000 === 0) echo "  procesados $total...\n";
}

if ($marcar && $marcas_bulk) $col->bulkWrite($marcas_bulk);

$enviables = array_sum($segmentos);

// ─── 3. Salida ───

arsort($por_dominio);
$resultado = [
    'fecha'          => date('c'),
    'total_verificados' => $total,
    'enviables'      => $enviables,
    'segmentos'      => $segmentos,
    'descartes'      => $descartes,
    'fuera_mercado'  => $fuera_mercado,
    'top_dominios'   => array_slice($por_dominio, 0, 25, true),
    'mx_comprobado'  => !$sin_mx,
];

$destino = __DIR__ . '/../logs/auditoria_email_' . date('Y-m-d') . '.json';
@file_put_contents($destino, json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

if ($como_json) {
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

echo "\n=== AUDITORÍA DE LISTA ===\n";
printf("Verificados analizados : %d\n", $total);
printf("Enviables              : %d (%.1f%%)\n", $enviables, $total ? 100 * $enviables / $total : 0);
echo "\nSegmentos (orden de envío recomendado):\n";
printf("  1. publicadores : %6d  (han publicado código, la señal más caliente)\n", $segmentos['publicadores']);
printf("  2. recientes    : %6d  (alta desde 2022)\n", $segmentos['recientes']);
printf("  3. fríos        : %6d  (alta anterior a 2022)\n", $segmentos['frios']);
printf("     de los cuales fuera de mercado ES: %d\n", $fuera_mercado);
echo "\nDescartados:\n";
foreach ($descartes as $k => $v) printf("  %-16s %6d\n", $k, $v);
printf("\nDominios distintos: %d\n", count($por_dominio));
echo "Informe guardado en $destino\n";
if (!$marcar) echo "\n(no se ha escrito nada en BD; usa --marcar para guardar el veredicto)\n";

log_info('[auditoria_email] completada', [
    'enviables' => $enviables,
    'descartes' => array_sum($descartes),
    'marcado'   => $marcar,
]);
