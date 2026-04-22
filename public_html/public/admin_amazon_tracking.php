<?php
// Dashboard de monitoreo de clicks de Amazon (admin_amazon_tracking.php)
// SOLO ACCESIBLE POR ADMINISTRADORES

session_start();

include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../myphp/funciones_chollos.php';
include_once __DIR__ . '/../myphp/funciones_chollos_helpers.php';

// Verificar permisos de administrador (misma lista que admin_dashboard.php)
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

// --- FUNCIONES HELPER GLOBALES PARA ESTE DASHBOARD ---
// Se definen arriba para usarlas en Estadísticas, Gráfica y Tabla consistentemente
function esClickAmazon($click) {
    // Dominios conocidos de Amazon y acortadores que suelen llevar a Amazon
    $dominios_amazon = ['amazon', 'amzn', 'amz.tf', 'chollo.biz', 'ganga.ad'];
    
    // 1. Chequeo base de datos (flag explícito)
    if (isset($click['es_amazon']) && $click['es_amazon']) return true;
    
    // 2. Chequeo URL final (si ya existe y es Amazon, es Amazon)
    if (!empty($click['enlace_final'])) {
        foreach ($dominios_amazon as $dominio) {
            if (stripos($click['enlace_final'], $dominio) !== false) return true;
        }
    }

    // 3. Chequeo URL original (solo si es un acortador específico de Amazon)
    $url_original = $click['enlace_original'] ?? '';
    $acortadores_amazon = ['amzn', 'amz.tf', 'chollo.biz', 'ganga.ad'];
    foreach ($acortadores_amazon as $acortador) {
        if (stripos($url_original, $acortador) !== false) return true;
    }

    return false;
}

function tieneTagAfiliado($click) {
    // 1. Chequeo base de datos
    if (isset($click["tiene_tag_afiliado"]) && $click["tiene_tag_afiliado"]) return true;

    // 2. Chequeo manual URL (busca el tag específico)
    $url = $click["enlace_final"] ?? ($click["enlace_original"] ?? "");
    return (strpos($url, "spnfury") !== false);
}

function esEnlaceBusqueda($click) {
    $url = $click["enlace_final"] ?? ($click["enlace_original"] ?? "");
    // Patrones típicos de páginas de búsqueda de Amazon
    return (strpos($url, "/s?k=") !== false || strpos($url, "keywords=") !== false || strpos($url, "/s/ref=") !== false);
}

$filtro_fecha = $_GET['fecha'] ?? 'hoy';
$filtro_amazon = isset($_GET['solo_amazon']) && $_GET['solo_amazon'] === '1';
$filtro_problemas = isset($_GET['solo_problemas']) && $_GET['solo_problemas'] === '1';

// Calcular rango de fechas
$fecha_inicio = new DateTime();
$fecha_fin = new DateTime();
$custom_date_inputs = false;

switch ($filtro_fecha) {
    case 'ayer':
        $fecha_inicio->modify('-1 day')->setTime(0, 0, 0);
        $fecha_fin->modify('-1 day')->setTime(23, 59, 59);
        break;
    case 'ultima_semana':
        $fecha_inicio->modify('-7 days')->setTime(0, 0, 0);
        $fecha_fin->setTime(23, 59, 59);
        break;
    case 'ultimo_mes':
        $fecha_inicio->modify('-30 days')->setTime(0, 0, 0);
        $fecha_fin->setTime(23, 59, 59);
        break;
    case 'custom':
        $custom_date_inputs = true;
        // Obtener fechas del input si existen, sino usar hoy por defecto
        $start_date = $_GET['start_date'] ?? date('Y-m-d');
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        
        $fecha_inicio = DateTime::createFromFormat('Y-m-d', $start_date);
        $fecha_inicio->setTime(0, 0, 0);
        
        $fecha_fin = DateTime::createFromFormat('Y-m-d', $end_date);
        $fecha_fin->setTime(23, 59, 59);
        break;
    case 'hoy':
    default:
        $filtro_fecha = 'hoy'; // Asegurar valor por defecto
        $fecha_inicio->setTime(0, 0, 0);
        $fecha_fin->setTime(23, 59, 59);
        break;
}

// Obtener clicks de la BD
$collection = getCollectionHistorialChollos();
$filtro_bd = [
    'fecha' => [
        '$gte' => new MongoDB\BSON\UTCDateTime($fecha_inicio->getTimestamp() * 1000),
        '$lte' => new MongoDB\BSON\UTCDateTime($fecha_fin->getTimestamp() * 1000)
    ]
];

// Nota: No filtramos por 'es_amazon' en la query de Mongo si queremos detectar cosas como chollo.biz 
// que Mongo quizás no marcó como Amazon. Filtramos en memoria.
// Eliminamos el límite severo para permitir "Últimos 30 días" completo, pero procesamos con cursor.
$clicks_cursor = $collection->find($filtro_bd, [
    'sort' => ['fecha' => -1],
    'limit' => 50000 // Límite de seguridad muy alto, pero no infinito
]);
// No usamos toArray() para no explotar la memoria


// --- PROCESAMIENTO DE DATOS ---

// Estadísticas Resumidas
$total_clicks = 0;
$clicks_amazon = 0;
$clicks_con_tag = 0;
$clicks_sin_tag = 0;
$asins_unicos = [];

// Datos para la Gráfica
$agrupacion_por_hora = ($filtro_fecha === 'hoy' || $filtro_fecha === 'ayer');
$clicks_grafica = [];

// Inicializar array base para gráfica
if ($agrupacion_por_hora) {
    $max_hora = 23;
    
    // Si es hoy, limitamos hasta la hora actual - 1 (última hora completa)
    // Ejemplo: Si son las 11:10, max_hora = 10, mostramos hasta 10:00 (datos de 10:00 a 10:59)
    if ($filtro_fecha === 'hoy') {
        $now_madrid = new DateTime('now', new DateTimeZone('Europe/Madrid'));
        $max_hora = (int)$now_madrid->format('H') - 1;
        // Si es 00:xx, max_hora será -1, no mostrará nada aún, correcto.
    }

    for ($i = 0; $i <= $max_hora; $i++) {
        $hora = sprintf('%02d:00', $i);
        $clicks_grafica[$hora] = ['total' => 0, 'amazon' => 0, 'con_tag' => 0, 'sin_tag' => 0];
    }
}

// Array final filtrado para mostrar en tabla
$clicks_filtrados = [];

foreach ($clicks_cursor as $click) {
    // Aplicar filtro de "Solo Amazon" en memoria si está activo
    $es_amazon = esClickAmazon($click);
    
    if ($filtro_amazon && !$es_amazon) {
        continue; // Saltar si el usuario solo quiere ver Amazon
    }
    
    // Filtro SOLO PROBLEMAS (Sin Tag)
    if ($filtro_problemas) {
        // Para tener problema debe ser Amazon y NO tener tag
        if (!$es_amazon) continue; 
        if (tieneTagAfiliado($click)) continue; 
    }
    
    // Guardar para tabla (Solo los primeros 500 para no matar el navegador)
    if (count($clicks_filtrados) < 500) {
        $clicks_filtrados[] = $click;
    }
    
    $total_clicks++;
    
    if ($es_amazon) {
        $clicks_amazon++;
        
        if (isset($click['asin'])) {
            $asins_unicos[$click['asin']] = ($asins_unicos[$click['asin']] ?? 0) + 1;
        }
        
        $tiene_tag = tieneTagAfiliado($click);
        if ($tiene_tag) {
            $clicks_con_tag++;
        } else {
            $clicks_sin_tag++;
        }
    }
    
    // Proceso Gráfica
    $fecha_obj = $click['fecha']->toDateTime()->setTimezone(new DateTimeZone('Europe/Madrid'));
    if ($agrupacion_por_hora) {
        $clave = $fecha_obj->format('H:00');
        // IMPORTANTE: Si estamos filtrando por horas (hoy/ayer) y la hora no está inicializada 
        // (porque es una hora futura o la hora actual incompleta), SALTAMOS este punto en la gráfica.
        if (!isset($clicks_grafica[$clave])) {
            continue; 
        }
    } else {
        $clave = $fecha_obj->format('Y-m-d');
        if (!isset($clicks_grafica[$clave])) {
            $clicks_grafica[$clave] = ['total' => 0, 'amazon' => 0, 'con_tag' => 0, 'sin_tag' => 0];
        }
    }
    
    $clicks_grafica[$clave]['total']++;
    if ($es_amazon) {
        $clicks_grafica[$clave]['amazon']++;
        if (tieneTagAfiliado($click)) {
            $clicks_grafica[$clave]['con_tag']++;
        } else {
            $clicks_grafica[$clave]['sin_tag']++;
        }
    }
}

ksort($clicks_grafica);

// Preparar datos Chart.js
$labels_grafica = array_keys($clicks_grafica);
$datos_total = array_column($clicks_grafica, 'total');
$datos_amazon = array_column($clicks_grafica, 'amazon');
$datos_con_tag = array_column($clicks_grafica, 'con_tag');
$datos_sin_tag = array_column($clicks_grafica, 'sin_tag');

// Tasa de éxito
$tasa_exito = $clicks_amazon > 0 ? round(($clicks_con_tag / $clicks_amazon) * 100, 2) : 0;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Amazon - Clicks y Tags de Afiliado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }
        
        .sidebar {
            min-width: 250px;
            max-width: 250px;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s;
            position: relative;
        }
        
        .sidebar.active {
            margin-left: -250px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        /* Botón de toggle en el borde */
        #sidebarCollapse {
            position: absolute;
            top: 50%;
            right: -20px;
            transform: translateY(-50%);
            width: 20px;
            height: 40px;
            background: #764ba2;
            color: white;
            border: none;
            border-radius: 0 10px 10px 0;
            cursor: pointer;
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        #sidebarCollapse:hover {
            background: #667eea;
            width: 25px;
        }

        .main-content {
            width: 100%;
            background-color: #f8f9fa;
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
        }
        .stat-card.success { border-left-color: #27ae60; }
        .stat-card.warning { border-left-color: #f39c12; }
        .stat-card.danger { border-left-color: #e74c3c; }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 2.5em;
            font-weight: 700;
            color: #2c3e50;
        }
        .stat-detail {
            margin-top: 10px;
            font-size: 0.85em;
            color: #95a5a6;
        }
        
        /* ESTILOS DE TABLA - RESALTADO ROJO INTENSO */
        tr.sin-tag-afiliado,
        tr.sin-tag-afiliado td {
            background-color: #f8d7da !important; /* Rojo Bootstrap type danger */
            color: #721c24 !important;
        }
        
        tr.sin-tag-afiliado {
            border-left: 5px solid #dc3545 !important;
        }

        tr.sin-tag-afiliado:hover,
        tr.sin-tag-afiliado:hover td {
            background-color: #f1b0b7 !important; /* Rojo más oscuro al pasar el mouse */
        }
        
        tr.sin-tag-afiliado td {
            font-weight: bold !important;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .url-cell {
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            word-break: break-all;
        }
        
        /* Anchos de columnas de la tabla */
        .tracking-table th:nth-child(1), .tracking-table td:nth-child(1) { width: 130px; min-width: 130px; } /* Fecha */
        .tracking-table th:nth-child(2), .tracking-table td:nth-child(2) { width: 320px; min-width: 250px; max-width: 400px; } /* Chollo + Referer */
        .tracking-table th:nth-child(3), .tracking-table td:nth-child(3) { width: 120px; min-width: 110px; } /* Estado Tag */
        .tracking-table th:nth-child(4), .tracking-table td:nth-child(4) { width: 140px; min-width: 120px; } /* IP */
        .tracking-table th:nth-child(5), .tracking-table td:nth-child(5) { min-width: 350px; } /* URL Destino */
        
        .tracking-table {
            table-layout: fixed;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <button type="button" id="sidebarCollapse">
                <i class="fas fa-chevron-left"></i>
            </button>
            <?php 
            include_once __DIR__ . '/admin_sidebar_menu.php';
            echo get_admin_sidebar_menu('admin_amazon_tracking.php'); 
            ?>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
                <div class="container-fluid">
                    <h5 class="mb-0 ms-2"><i class="fas fa-amazon me-2 text-warning"></i>Amazon Tracking</h5>
                    <div class="d-flex align-items-center">
                        <span class="text-muted me-3 d-none d-md-block">Bienvenido, <?php echo $_SESSION["username"] ?? 'Admin'; ?></span>
                        <a href="https://www.codigoamigo.com/logout" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-sign-out-alt me-1"></i>Salir
                        </a>
                    </div>
                </div>
            </nav>

            <div class="p-4">
                <!-- Filtros -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <form id="filterForm" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Período</label>
                                <select name="fecha" id="filtro_fecha" class="form-select" onchange="toggleCustomDate()">
                                    <option value="hoy" <?php echo $filtro_fecha === 'hoy' ? 'selected' : ''; ?>>Hoy</option>
                                    <option value="ayer" <?php echo $filtro_fecha === 'ayer' ? 'selected' : ''; ?>>Ayer</option>
                                    <option value="ultima_semana" <?php echo $filtro_fecha === 'ultima_semana' ? 'selected' : ''; ?>>Últimos 7 días</option>
                                    <option value="ultimo_mes" <?php echo $filtro_fecha === 'ultimo_mes' ? 'selected' : ''; ?>>Últimos 30 días</option>
                                    <option value="custom" <?php echo $filtro_fecha === 'custom' ? 'selected' : ''; ?>>Personalizado...</option>
                                </select>
                            </div>
                            
                            <!-- Inputs de fecha personalizados (ocultos por defecto) -->
                            <div class="col-md-2 custom-date-group" style="<?php echo $filtro_fecha !== 'custom' ? 'display:none;' : ''; ?>">
                                <label class="form-label fw-bold small text-muted">Desde</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $fecha_inicio->format('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-2 custom-date-group" style="<?php echo $filtro_fecha !== 'custom' ? 'display:none;' : ''; ?>">
                                <label class="form-label fw-bold small text-muted">Hasta</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $fecha_fin->format('Y-m-d'); ?>">
                            </div>

                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="solo_amazon" value="1" id="solo_amazon" <?php echo $filtro_amazon ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="solo_amazon">
                                        Solo Amazon
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="solo_problemas" value="1" id="solo_problemas" <?php echo $filtro_problemas ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-danger fw-bold" for="solo_problemas">
                                        Solo Sin Tag
                                    </label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Estadísticas -->
                <div class="row">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="stat-label">Total Clicks</div>
                            <div class="stat-value"><?php echo number_format($total_clicks); ?></div>
                            <div class="stat-detail">Periodo seleccionado</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card <?php echo $clicks_amazon > 0 ? 'success' : ''; ?>">
                            <div class="stat-label">Tráfico Amazon</div>
                            <div class="stat-value"><?php echo number_format($clicks_amazon); ?></div>
                            <div class="stat-detail">
                                <?php echo $total_clicks > 0 ? round(($clicks_amazon / $total_clicks) * 100, 1) : 0; ?>% del total
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card <?php echo $clicks_sin_tag > 0 ? 'danger' : 'success'; ?>">
                            <div class="stat-label">Sin Tag / Error</div>
                            <div class="stat-value"><?php echo number_format($clicks_sin_tag); ?></div>
                            <div class="stat-detail">
                                <?php echo $clicks_sin_tag > 0 ? '⚠️ NECESITA ATENCIÓN' : '✅ Todo Ok'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card <?php echo $tasa_exito >= 95 ? 'success' : ($tasa_exito >= 80 ? 'warning' : 'danger'); ?>">
                            <div class="stat-label">Tasa de Éxito</div>
                            <div class="stat-value"><?php echo $tasa_exito; ?>%</div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfica -->
                <?php if (count($clicks_grafica) > 0): ?>
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 text-muted fw-bold">
                            <i class="fas fa-chart-line me-2"></i>
                            Evolución <?php echo $agrupacion_por_hora ? '(Por Horas)' : '(Por Días)'; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="graficaClicks" style="height: 350px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tabla -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 text-muted fw-bold"><i class="fas fa-list me-2"></i>Detalle de Clicks</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle tracking-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Chollo / Referer</th>
                                        <th>Estado Tag</th>
                                        <th>IP</th>
                                        <th>URL Destino</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clicks_filtrados as $click): 
                                        $es_amazon = esClickAmazon($click);
                                        $tiene_tag = tieneTagAfiliado($click);
                                        $es_busqueda = esEnlaceBusqueda($click);
                                        
                                        $row_class = '';
                                        if ($es_amazon) {
                                            $url_check = $click['enlace_final'] ?? ($click['enlace_original'] ?? '');
                                            $es_shortener = (strpos($url_check, 'ganga.ad') !== false || strpos($url_check, 'chollo.biz') !== false || strpos($url_check, 'amz.tf') !== false || strpos($url_check, 'bit.ly') !== false || strpos($url_check, 't.co') !== false);
                                            
                                            // Si no tiene tag, marcamos error SALVO que sea un shortener (que se resolverá o es genérico)
                                            if (!$tiene_tag || $es_busqueda) {
                                                if ($es_shortener && !$es_busqueda) {
                                                    $row_class = 'table-warning'; // Amarillo para pendientes o externos
                                                } else {
                                                    $row_class = 'sin-tag-afiliado'; // Rojo para errores reales en Amazon
                                                }
                                            }
                                        }
                                        
                                        $fecha_click = $click['fecha']->toDateTime()->setTimezone(new DateTimeZone('Europe/Madrid'));
                                        
                                        // Obtener título chollo
                                        $chollo = null;
                                        if (isset($click['chollo_id'])) {
                                            try {
                                                $chollo = obtenerCholloPorId((string)$click['chollo_id'], false);
                                            } catch (Exception $e) {}
                                        }

                                        // Limpiar Referer para mostrar
                                        $referer = $click['referer'] ?? '';
                                        $referer_mostrar = '-';
                                        $referer_badge = '';
                                        $referer_icon = 'fa-link';
                                        
                                        if ($referer === 'Shorts Feed') {
                                            $referer_mostrar = 'Shorts Feed';
                                            $referer_badge = '<span class="badge bg-danger me-1" style="font-size: 0.65rem;">🎬 Shorts</span>';
                                            $referer_icon = 'fa-video';
                                        } elseif (!empty($referer)) {
                                            $parsed_ref = parse_url($referer);
                                            // Si es interno, mostrar solo path, si es externo mostrar host
                                            if (isset($parsed_ref['host']) && strpos($parsed_ref['host'], 'codigoamigo.com') !== false) {
                                                $referer_mostrar = 'Int: ' . ($parsed_ref['path'] ?? '/');
                                                $referer_badge = '<span class="badge bg-secondary me-1" style="font-size: 0.65rem;">🔗 Web</span>';
                                            } else {
                                                $referer_mostrar = $referer;
                                                $referer_badge = '<span class="badge bg-info me-1" style="font-size: 0.65rem;">🌐 Ext</span>';
                                            }
                                        } else {
                                            $referer_badge = '<span class="badge bg-dark me-1" style="font-size: 0.65rem;">➡️ Directo</span>';
                                            $referer_mostrar = 'Acceso directo';
                                        }
                                    ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td class="small"><?php echo $fecha_click->format('d/m/Y H:i:s'); ?></td>
                                        <td>
                                            <?php if ($chollo): 
                                                // Generar URL canónica con categoría
                                                $categoria_chollo = $chollo['categoria'] ?? ['general'];
                                                if (is_string($categoria_chollo)) {
                                                    $categoria_chollo = [$categoria_chollo];
                                                }
                                                $categoria_primera = reset($categoria_chollo) ?: 'general';
                                                $categoria_slug = categoriaToSlug($categoria_primera);
                                                $url_chollo = '/chollos/' . $categoria_slug . '/' . (string)$click['chollo_id'];
                                            ?>
                                                <a href="<?php echo htmlspecialchars($url_chollo); ?>" target="_blank" class="text-decoration-none fw-bold">
                                                    <?php echo htmlspecialchars(mb_substr($chollo['titulo'] ?? 'Sin título', 0, 40)); ?>...
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">ID: <?php echo substr((string)$click['chollo_id'], -6); ?></span>
                                            <?php endif; ?>
                                            
                                            <div class="url-cell text-muted x-small mt-1" title="Referer: <?php echo htmlspecialchars($referer); ?>" style="font-size: 0.75rem; opacity: 0.9;">
                                                <?php echo $referer_badge; ?>
                                                <i class="fas <?php echo $referer_icon; ?> me-1"></i><?php echo htmlspecialchars(mb_substr($referer_mostrar, 0, 60)) . (mb_strlen($referer_mostrar) > 60 ? '...' : ''); ?>
                                            </div>
                                        </td>

                                        <td>
                                            <?php 
                                            if ($es_amazon) {
                                                if ($es_busqueda) {
                                                    echo '<span class="badge badge-danger mb-1"><i class="fas fa-search me-1"></i>BÚSQUEDA</span><br>';
                                                }
                                                
                                                if ($tiene_tag) {
                                                    echo '<span class="badge badge-success"><i class="fas fa-check me-1"></i>spnfuryy-21</span>';
                                                } else {
                                                    echo '<span class="badge badge-danger"><i class="fas fa-times me-1"></i>SIN TAG</span>';
                                                }
                                        } else {
                                            // NO ES AMAZON (detectado por dominio ppal)
                                            // Revisamos manualmente si es shortener para informar del estado
                                            $url_check = $click['enlace_final'] ?? ($click['enlace_original'] ?? '');
                                            if (strpos($url_check, 'ganga.ad') !== false || strpos($url_check, 'chollo.biz') !== false || strpos($url_check, 'bit.ly') !== false) {
                                                 echo '<span class="badge bg-warning text-dark"><i class="fas fa-sync fa-spin me-1"></i>Resolviendo...</span>';
                                            } else {
                                                 echo '<span class="badge bg-light text-muted">N/A</span>';
                                            }
                                        }
                                            ?>
                                        </td>
                                        <td class="small text-muted"><?php echo htmlspecialchars($click['ip'] ?? '-'); ?></td>

                                        <td class="url-cell" title="<?php echo htmlspecialchars($click['enlace_final'] ?? ($click['enlace_original'] ?? '')); ?>">
                                            <?php echo htmlspecialchars($click['enlace_final'] ?? ($click['enlace_original'] ?? '-')); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle Sidebar
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
            
            // Cambiar icono
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-left');
            }
        });

        // Toggle Custom Date Inputs
        function toggleCustomDate() {
            const select = document.getElementById('filtro_fecha');
            const customGroups = document.querySelectorAll('.custom-date-group');
            
            if (select.value === 'custom') {
                customGroups.forEach(el => el.style.display = 'block');
            } else {
                customGroups.forEach(el => el.style.display = 'none');
                // Auto-submit al cambiar si no es custom
                document.getElementById('filterForm').submit();
            }
        }
        
        // Inicializar Gráfica
        <?php if (count($clicks_grafica) > 0): ?>
        const ctx = document.getElementById('graficaClicks');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_map(function($label) use ($agrupacion_por_hora) {
                        return $agrupacion_por_hora ? $label : date('d/m', strtotime($label));
                    }, $labels_grafica)); ?>,
                    datasets: [
                        {
                            label: 'Total',
                            data: <?php echo json_encode($datos_total); ?>,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 3
                        },
                        {
                            label: 'Amazon/Aff',
                            data: <?php echo json_encode($datos_amazon); ?>,
                            borderColor: '#f39c12',
                            backgroundColor: 'rgba(243, 156, 18, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 3
                        },
                        {
                            label: 'Con Tag ✓',
                            data: <?php echo json_encode($datos_con_tag); ?>,
                            borderColor: '#27ae60',
                            backgroundColor: 'rgba(39, 174, 96, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 3
                        },
                        {
                            label: 'SIN TAG ✗',
                            data: <?php echo json_encode($datos_sin_tag); ?>,
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            tension: 0.3,
                            fill: true,
                            borderWidth: 3,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { 
                        legend: { 
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { size: 12, weight: 'bold' }
                            }
                        } 
                    },
                    scales: { 
                        y: { 
                            beginAtZero: true, 
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
        
        // Auto-reload solo si es "hoy" para ver datos en tiempo real
        <?php if ($filtro_fecha === 'hoy'): ?>
        setTimeout(() => location.reload(), 60000);
        <?php endif; ?>
    </script>
</body>
</html>
