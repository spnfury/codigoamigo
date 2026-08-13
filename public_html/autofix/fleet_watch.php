<?php
/**
 * autofix/fleet_watch.php — vigía de flota con AUTO-DESCUBRIMIENTO.
 *
 * Escanea los roots de fleet.php, descubre cualquier *.log activo (menos ruido
 * y dependencias), lee lo NUEVO de cada uno desde el último run (offset de bytes),
 * clasifica cada error en 'ops' (token/quota/DB -> acción tuya) o 'código' (bug),
 * deduplica y manda digest a Telegram.
 *
 * Baseline en 1ª vista: un log nuevo se marca a su tamaño actual y NO se lee el
 * histórico (evita OOM y re-alertar lo viejo). Los sitios nuevos se cubren solos.
 *
 * NO edita código. Uso:  php fleet_watch.php [--print]  (--print = no notifica)
 */

$dir   = __DIR__;
$cfg   = require $dir . '/fleet.php';
$STATE = $dir . '/state';
$offFile = $STATE . '/fleet_offsets.json';
$sigFile = $STATE . '/fleet_seen.json';
$DRY = in_array('--print', $argv, true);

@mkdir($STATE, 0775, true);
$offsets = is_file($offFile) ? (json_decode(file_get_contents($offFile), true) ?: []) : [];
$seen    = is_file($sigFile) ? (json_decode(file_get_contents($sigFile), true) ?: []) : [];

$ops_patterns = $cfg['_ops_patterns'];
$code_pattern = $cfg['_code_patterns'];
$maxEdad = ($cfg['max_edad_dias'] ?? 7) * 86400;
$maxLeer = $cfg['max_leer_bytes'] ?? 5242880;
$opsCooldown = ($cfg['ops_cooldown_h'] ?? 24) * 3600;
$retFirmas   = ($cfg['retencion_firmas_dias'] ?? 30) * 86400;

/** Deriva el nombre de portal desde la ruta del log. */
function fleet_portal(string $path): string {
    // Logs de dominio Apache/nginx: el "portal" es el dominio del fichero.
    if (preg_match('#^/var/log/(?:apache2/domains|nginx)/([^/]+)\.error\.log$#', $path, $m)) {
        $d = $m[1];
        // Flota casinuevo: todos los subdominios (incl. homesya y variantes
        // tipográficas como casinnuevo) comparten el mismo public_html, así
        // que agruparlos evita que el digest se llene de "portales" sueltos.
        if (stripos($d, 'casinuevo') !== false || stripos($d, 'casinovios') !== false || stripos($d, 'homesya') !== false) {
            return 'casinuevo';
        }
        return $d;
    }
    if (preg_match('#^/home/admin/web/([^/]+)#', $path, $m)) return $m[1];
    if (preg_match('#^/var/www/([^/]+)#', $path, $m)) return $m[1];
    if (preg_match('#^/home/([^/]+)#', $path, $m)) return $m[1];
    return basename(dirname($path));
}

/** Auto-descubre logs activos bajo los roots, aplicando exclusiones. */
function fleet_descubrir(array $cfg): array {
    $roots = [];
    foreach ($cfg['roots'] as $g) {
        foreach (glob($g, GLOB_ONLYDIR) ?: [] as $d) $roots[] = $d;
        if (is_dir($g)) $roots[] = $g;   // root sin glob
    }
    $roots = array_unique($roots);
    $maxEdad = ($cfg['max_edad_dias'] ?? 7) * 86400;
    $out = [];

    foreach ($roots as $root) {
        $skip = false;
        foreach ($cfg['exclude_root'] as $x) { if (stripos($root, $x) !== false) { $skip = true; break; } }
        if ($skip) continue;

        $rdi = @new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
        if (!$rdi) continue;
        // Poda: no descender en dependencias/VCS/cachés (rápido, no recorre node_modules).
        $filtered = new RecursiveCallbackFilterIterator($rdi, function ($current) use ($cfg) {
            if ($current->isDir()) {
                foreach ($cfg['exclude_path'] as $x) {
                    if (stripos($current->getPathname(), $x) !== false) return false;
                }
            }
            return true;
        });
        $it = new RecursiveIteratorIterator($filtered, RecursiveIteratorIterator::LEAVES_ONLY);
        $it->setMaxDepth(3);

        foreach ($it as $file) {
            $p = $file->getPathname();
            if (substr($p, -4) !== '.log') continue;

            // Logs de dominio Apache/nginx: solo *.error.log. El access.log y el
            // detailed.log son ruido y gigantes (los globales error.log/access.log
            // de nginx no terminan en ".error.log" -> se descartan solos).
            if ((strpos($p, '/var/log/apache2/domains/') === 0 || strpos($p, '/var/log/nginx/') === 0)
                && substr($p, -10) !== '.error.log') continue;

            $bad = false;
            foreach ($cfg['exclude_path'] as $x) { if (stripos($p, $x) !== false) { $bad = true; break; } }
            if ($bad) continue;
            foreach ($cfg['ignorar_log'] as $x) { if (stripos(basename($p), $x) !== false) { $bad = true; break; } }
            if ($bad) continue;

            $mt = @filemtime($p);
            if ($mt === false || $mt < time() - $maxEdad) continue;
            if (@filesize($p) <= 0) continue;
            $out[$p] = fleet_portal($p);
        }
    }
    return $out;
}

/** Lee bytes nuevos desde el offset guardado (baseline en 1ª vista). */
function fleet_leer_nuevo(string $f, array &$offsets, int $maxLeer): string {
    $size = @filesize($f);
    if ($size === false) return '';
    if (!array_key_exists($f, $offsets)) { $offsets[$f] = $size; return ''; }  // baseline
    $prev = $offsets[$f];
    if ($prev > $size) $prev = 0;                          // rotó -> desde 0
    if ($size - $prev > $maxLeer) $prev = $size - $maxLeer; // tope de lectura
    $data = '';
    if ($size > $prev) {
        $fh = @fopen($f, 'r');
        if ($fh) { fseek($fh, $prev); $data = stream_get_contents($fh); fclose($fh); }
    }
    $offsets[$f] = $size;
    return $data;
}

// ---- escaneo ----
$logs = fleet_descubrir($cfg);
$digest_ops = [];
$digest_code = [];
$total_new = 0;

foreach ($logs as $f => $portal) {
    $data = fleet_leer_nuevo($f, $offsets, $maxLeer);
    if ($data === '') continue;

    foreach (explode("\n", $data) as $linea) {
        if (strlen($linea) < 12) continue;

        // Supresión de ruido esperado/manejado (falsas alarmas).
        $ignorar = false;
        foreach ($cfg['ignorar_patrones'] ?? [] as $rx) {
            if (preg_match($rx, $linea)) { $ignorar = true; break; }
        }
        if ($ignorar) continue;

        $es_ops = null;
        foreach ($ops_patterns as $accion => $rx) {
            if (preg_match($rx, $linea)) { $es_ops = $accion; break; }
        }
        $es_code = ($es_ops === null) && preg_match($code_pattern, $linea);
        if (!$es_ops && !$es_code) continue;

        // El mensaje real empieza tras "PHP message:" en logs envueltos por
        // Apache/nginx (AH01071, FastCGI stderr) — el prefijo (timestamp, pid,
        // tid, client IP) tiene longitud variable y desplaza una ventana fija
        // de 120 chars antes de llegar al contenido, rompiendo la dedup (dos
        // ocurrencias del MISMO error generaban firmas distintas). Si no hay
        // "PHP message:" (formato ya nativo), se usa la línea desde el inicio.
        if ($es_ops) {
            // Los errores OPS ("quota agotada", "clave inválida") no son bugs
            // distintos: son UNA condición operativa que escupe mensajes con
            // texto variable (endpoint, id, timestamps). Firmar por el cuerpo
            // generaba una firma nueva por mensaje -> decenas de avisos del
            // mismo problema. Se agrupa por portal+clase y se re-avisa como
            // mucho una vez cada `ops_cooldown_h`.
            $firma = substr(md5($portal . '|ops|' . $es_ops), 0, 16);
        } else {
            $cuerpo = $linea;
            $pos = strpos($linea, 'PHP message:');
            if ($pos !== false) { $cuerpo = substr($linea, $pos); }
            $norm  = preg_replace('/\d+/', 'N', substr($cuerpo, 0, 120));
            $firma = substr(md5($portal . '|code|' . $norm), 0, 16);
        }

        if (isset($seen[$firma])) {
            $edad = time() - strtotime($seen[$firma]['ts'] ?? 'now');
            $seen[$firma]['count'] = ($seen[$firma]['count'] ?? 0) + 1;
            $seen[$firma]['ts'] = date('Y-m-d H:i:s');
            // Solo los OPS reviven tras el cooldown (la condición puede haber
            // vuelto). Un bug de código ya avisado no se repite.
            if (!$es_ops || $edad < $opsCooldown) continue;
        } else {
            $seen[$firma] = ['portal' => $portal, 'ts' => date('Y-m-d H:i:s'), 'count' => 1];
        }
        $total_new++;

        if ($es_ops) {
            $digest_ops[$portal][$es_ops] = ($digest_ops[$portal][$es_ops] ?? 0) + 1;
        } else {
            $digest_code[$portal][] = trim(substr($linea, 0, 140));
        }
    }
}

// Poda: sin esto fleet_seen.json crece sin techo (se lee y reescribe entero
// cada 30 min). Una firma sin reaparecer en semanas ya no aporta dedup.
$corte = time() - $retFirmas;
foreach ($seen as $f => $r) {
    if (strtotime($r['ts'] ?? 'now') < $corte) unset($seen[$f]);
}

if (!$DRY) {
    file_put_contents($offFile, json_encode($offsets, JSON_PRETTY_PRINT));
    file_put_contents($sigFile, json_encode($seen, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

if (!$digest_ops && !$digest_code) {
    echo "fleet_watch: sin novedades · " . count($logs) . " logs vigilados · " . count($seen) . " firmas\n";
    exit(0);
}

$msg = "🌐 Vigía de flota — errores NUEVOS\n";
if ($digest_ops) {
    $msg .= "\n🔧 OPS (acción tuya):\n";
    foreach ($digest_ops as $portal => $acc) {
        foreach ($acc as $a => $n) $msg .= "• {$portal}: {$a} ×{$n}\n";
    }
}
if ($digest_code) {
    $msg .= "\n🐛 CÓDIGO (bug):\n";
    foreach ($digest_code as $portal => $ej) {
        $msg .= "• {$portal}: " . count($ej) . " tipo(s)\n";
        foreach (array_slice(array_unique($ej), 0, 2) as $e) $msg .= "   {$e}\n";
    }
}
$msg .= "\n{$total_new} firmas nuevas · " . count($logs) . " logs vigilados.";

echo $msg . "\n";
if (!$DRY) {
    $p = popen('php ' . escapeshellarg($dir . '/notify.php') . ' 2>/dev/null', 'w');
    if ($p) { fwrite($p, $msg); pclose($p); }
}
