<?php
/**
 * Tabla de métricas de rendimiento por código (estilo panel Wallapop).
 * Muestra clicks por período (hoy / 7 días / 30 días / total) + tendencia y
 * mini-gráfico de los últimos 7 días, para ver de un vistazo qué funciona.
 *
 * Requiere en scope: $_SESSION['user_id'].
 * Helpers: getCollectionCodigos(), getCollectionMarcas().
 */

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) return;

/**
 * Suma clicks de stats_diarias por períodos + serie de 7 días.
 */
if (!function_exists('ma_metricas_periodo')) {
    function ma_metricas_periodo($stats_diarias) {
        $stats = is_object($stats_diarias) ? (array)$stats_diarias : (is_array($stats_diarias) ? $stats_diarias : []);
        $hoy = 0; $d7 = 0; $d30 = 0; $prev7 = 0; $serie = [];
        for ($i = 0; $i < 30; $i++) {
            $f = date('Y-m-d', strtotime("-$i days"));
            $c = 0;
            if (isset($stats[$f])) {
                $sd = is_object($stats[$f]) ? (array)$stats[$f] : $stats[$f];
                $c = isset($sd['clicks']) ? (int)$sd['clicks'] : 0;
            }
            if ($i === 0) $hoy = $c;
            if ($i < 7) { $d7 += $c; $serie[] = $c; }
            if ($i >= 7 && $i < 14) $prev7 += $c;
            $d30 += $c;
        }
        return [
            'hoy'   => $hoy,
            'd7'    => $d7,
            'd30'   => $d30,
            'prev7' => $prev7,
            'serie' => array_reverse($serie), // antiguo → reciente
        ];
    }
}

// ── Cargar códigos activos del usuario (proyección ligera) ──────────────────
if (!function_exists('getCollectionCodigos')) return;
try {
    $coll_codigos = getCollectionCodigos();
    $user_oid = new MongoDB\BSON\ObjectId($_SESSION['user_id']);
    $cursor = $coll_codigos->find(
        ['id_usuario' => $user_oid, '$or' => [['estado' => 0], ['estado' => ['$exists' => false]]]],
        ['projection' => [
            'marca' => 1, 'descripcion' => 1, 'fecha_publicacion' => 1,
            'totalclicks' => 1, 'stats_diarias' => 1, 'num_beneficio' => 1,
            'destacado' => 1, 'tipo_destacado' => 1,
        ]]
    );
    $codigos_metricas = iterator_to_array($cursor);
} catch (Exception $e) {
    $codigos_metricas = [];
}

if (empty($codigos_metricas)) return;

// ── Batch de marcas (1 sola query) ──────────────────────────────────────────
$slugs = [];
foreach ($codigos_metricas as $c) {
    if (!empty($c['marca'])) $slugs[strtolower($c['marca'])] = true;
}
$marca_map = [];
if (!empty($slugs) && function_exists('getCollectionMarcas')) {
    try {
        $coll_marcas = getCollectionMarcas();
        $mc = $coll_marcas->find(['nombre_clave' => ['$in' => array_keys($slugs)]],
            ['projection' => ['nombre_clave' => 1, 'nombre' => 1, 'url_imagen' => 1, 'imagen' => 1, 'logo' => 1]]);
        foreach ($mc as $m) {
            $marca_map[strtolower($m['nombre_clave'] ?? '')] = $m;
        }
    } catch (Exception $e) { /* fallback sin marcas */ }
}

// ── Calcular métricas y ordenar por rendimiento 7 días ──────────────────────
$filas = [];
$tot_hoy = 0; $tot_d7 = 0; $tot_prev7 = 0;
foreach ($codigos_metricas as $c) {
    $m = ma_metricas_periodo($c['stats_diarias'] ?? []);
    $tot_hoy += $m['hoy']; $tot_d7 += $m['d7']; $tot_prev7 += $m['prev7'];
    $slug = strtolower($c['marca'] ?? '');
    $marca = $marca_map[$slug] ?? null;
    $filas[] = [
        'id'        => (string)$c['_id'],
        'slug'      => $slug,
        'marca'     => $marca['nombre'] ?? ($c['marca'] ?? '—'),
        'desc'      => $c['descripcion'] ?? '',
        'fecha'     => $c['fecha_publicacion'] ?? '',
        'total'     => (int)($c['totalclicks'] ?? 0),
        'is_super'  => (isset($c['tipo_destacado']) && $c['tipo_destacado'] === 'super'),
        'is_dest'   => (isset($c['destacado']) && (int)$c['destacado'] > 0),
        'img'       => $marca['url_imagen'] ?? ($marca['imagen'] ?? ($marca['logo'] ?? '')),
        'm'         => $m,
    ];
}
// Orden: más clicks en 7 días primero; empate → total desc
usort($filas, function ($a, $b) {
    if ($b['m']['d7'] !== $a['m']['d7']) return $b['m']['d7'] - $a['m']['d7'];
    return $b['total'] - $a['total'];
});

// Tendencia global
$tend_global = $tot_prev7 > 0 ? round(($tot_d7 - $tot_prev7) / $tot_prev7 * 100) : ($tot_d7 > 0 ? 100 : 0);

// Helper: normalizar fecha a d/m/Y
$fmt_fecha = function ($raw) {
    if (empty($raw)) return '—';
    if ($raw instanceof MongoDB\BSON\UTCDateTime) return $raw->toDateTime()->format('d/m/Y');
    $ts = is_numeric($raw) ? (int)$raw : strtotime((string)$raw);
    return $ts ? date('d/m/Y', $ts) : '—';
};
?>

<div class="ma-metricas-wrap" id="ma-metricas-wrap" style="display:none;">
    <!-- Resumen -->
    <div class="ma-metricas-summary">
        <div class="ma-metricas-kpi">
            <span class="ma-metricas-kpi-label">Clicks hoy</span>
            <span class="ma-metricas-kpi-val"><?php echo number_format($tot_hoy, 0, ',', '.'); ?></span>
        </div>
        <div class="ma-metricas-kpi">
            <span class="ma-metricas-kpi-label">Últimos 7 días</span>
            <span class="ma-metricas-kpi-val"><?php echo number_format($tot_d7, 0, ',', '.'); ?></span>
        </div>
        <div class="ma-metricas-kpi">
            <span class="ma-metricas-kpi-label">Tendencia 7d</span>
            <?php
            $tcls = $tend_global > 0 ? 'up' : ($tend_global < 0 ? 'down' : 'flat');
            $tico = $tend_global > 0 ? 'arrow-up' : ($tend_global < 0 ? 'arrow-down' : 'minus');
            ?>
            <span class="ma-metricas-kpi-val ma-trend ma-trend-<?php echo $tcls; ?>">
                <i class="fas fa-<?php echo $tico; ?>"></i> <?php echo abs($tend_global); ?>%
            </span>
        </div>
        <div class="ma-metricas-kpi">
            <span class="ma-metricas-kpi-label">Códigos activos</span>
            <span class="ma-metricas-kpi-val"><?php echo count($filas); ?></span>
        </div>
    </div>

    <div class="ma-metricas-scroll">
        <table class="ma-metricas-table">
            <thead>
                <tr>
                    <th class="col-prod">Código</th>
                    <th class="col-num">Hoy</th>
                    <th class="col-num">7 días</th>
                    <th class="col-num">30 días</th>
                    <th class="col-num">Total</th>
                    <th class="col-trend">Tendencia</th>
                    <th class="col-spark">Últimos 7 días</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $f):
                    $mm = $f['m'];
                    $tend = $mm['prev7'] > 0 ? round(($mm['d7'] - $mm['prev7']) / $mm['prev7'] * 100) : ($mm['d7'] > 0 ? 100 : 0);
                    $tcls = $tend > 0 ? 'up' : ($tend < 0 ? 'down' : 'flat');
                    $tico = $tend > 0 ? 'arrow-up' : ($tend < 0 ? 'arrow-down' : 'minus');
                    $max_serie = max(1, max($mm['serie']));
                    $desc_corta = mb_strlen($f['desc']) > 48 ? mb_substr($f['desc'], 0, 48) . '…' : $f['desc'];
                    $sin_act = $mm['d30'] === 0;
                ?>
                <tr class="<?php echo $sin_act ? 'ma-row-inactiva' : ''; ?>">
                    <td class="col-prod">
                        <a href="/destacar_codigo?codigo=<?php echo $f['id']; ?>" class="ma-metricas-prod" title="<?php echo htmlspecialchars($f['marca']); ?>">
                            <span class="ma-metricas-logo">
                                <?php if (!empty($f['img'])): ?>
                                    <img loading="lazy" src="<?php echo htmlspecialchars($f['img']); ?>" alt="">
                                <?php else: ?>
                                    <?php echo strtoupper(mb_substr($f['marca'], 0, 1)); ?>
                                <?php endif; ?>
                            </span>
                            <span class="ma-metricas-prod-txt">
                                <span class="ma-metricas-marca">
                                    <?php echo htmlspecialchars($f['marca']); ?>
                                    <?php if ($f['is_super']): ?><i class="fas fa-trophy" title="Super" style="color:#1a1a2e;font-size:0.7rem;"></i>
                                    <?php elseif ($f['is_dest']): ?><i class="fas fa-star" title="Destacado" style="color:#E30613;font-size:0.7rem;"></i><?php endif; ?>
                                </span>
                                <span class="ma-metricas-desc"><?php echo htmlspecialchars($desc_corta); ?></span>
                            </span>
                        </a>
                    </td>
                    <td class="col-num"><?php echo number_format($mm['hoy'], 0, ',', '.'); ?></td>
                    <td class="col-num"><strong><?php echo number_format($mm['d7'], 0, ',', '.'); ?></strong></td>
                    <td class="col-num"><?php echo number_format($mm['d30'], 0, ',', '.'); ?></td>
                    <td class="col-num ma-metricas-total"><?php echo number_format($f['total'], 0, ',', '.'); ?></td>
                    <td class="col-trend">
                        <span class="ma-trend ma-trend-<?php echo $tcls; ?>">
                            <i class="fas fa-<?php echo $tico; ?>"></i> <?php echo abs($tend); ?>%
                        </span>
                    </td>
                    <td class="col-spark">
                        <span class="ma-spark">
                            <?php foreach ($mm['serie'] as $idx => $v):
                                $h = max(3, round($v / $max_serie * 26));
                                $is_last = ($idx === count($mm['serie']) - 1);
                            ?>
                                <span class="ma-spark-bar <?php echo $is_last ? 'is-last' : ''; ?>" style="height:<?php echo $h; ?>px;" title="<?php echo $v; ?> clicks"></span>
                            <?php endforeach; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
