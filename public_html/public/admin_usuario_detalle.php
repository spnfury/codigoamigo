<?php
session_start();

// Incluir archivos necesarios
include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../myphp/funciones.php';
include_once __DIR__ . '/../inc/funciones.php';
include_once __DIR__ . '/../myphp/funciones_usuario.php';
include_once __DIR__ . '/../myphp/funciones_email.php';
include_once __DIR__ . '/../myphp/email_helper.php';
include_once __DIR__ . '/admin_sidebar_menu.php';

// Verificar permisos de administrador
$array_codigos_acceso[] = "58bd851da54e295b8b52f702"; //thevega82@gmail.com
$array_codigos_acceso[] = "5e78170e6b68e6519b7c5df2"; //edna
$array_codigos_acceso[] = "639899bc6321ee0d0e4010d2"; //aron
$array_codigos_acceso[] = "5c8a10ce2f55c86d6e707d82"; //jose

// Verificar que el usuario esté logueado y tenga permisos
if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('Location: https://www.codigoamigo.com');
    die();
}

$collection_usuarios = getCollectionUsuarios();
$collection_transacciones = getCollectionTransacciones();
$collection_codigos = getCollectionCodigos();
$collection_email_logs = getCollectionEmailLogs();

// Obtener ID del usuario desde parámetro GET
$user_id = $_GET['id'] ?? '';
if (!$user_id) {
    $_SESSION['error_message'] = "ID de usuario no especificado";
    header('Location: admin_usuarios.php');
    exit;
}

// Obtener información del usuario
try {
    $usuario = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
    if (!$usuario) {
        $_SESSION['error_message'] = "Usuario no encontrado";
        header('Location: admin_usuarios.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error al obtener información del usuario";
    header('Location: admin_usuarios.php');
    exit;
}

// Obtener transacciones del usuario
$transacciones = $collection_transacciones->find(
    ['usuario_id' => $user_id],
    ['sort' => ['fecha' => -1]]
)->toArray();

// Obtener logs de emails enviados a este usuario
$email_logs = $collection_email_logs->find(
    ['usuario_id' => $user_id],
    ['sort' => ['fecha' => -1], 'limit' => 50]
)->toArray();

// Procesar acciones
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'toggle_vip':
            include_once __DIR__ . '/../myphp/funciones_usuario.php';
            $accion_vip = $_POST['accion_vip'] ?? '';
            $meses = max(1, (int)($_POST['meses'] ?? 1));
            $cancelar_stripe = !empty($_POST['cancelar_stripe']);
            $enviar_email_decline = !empty($_POST['enviar_email_decline']);
            $mensajes = [];
            try {
                if ($accion_vip === 'activar') {
                    $expires = new DateTime();
                    $expires->modify("+{$meses} month");
                    $sub_id_manual = 'direct_activation_' . time();
                    $ok = activar_vip($user_id, $sub_id_manual, $expires);
                    $mensajes[] = $ok
                        ? "VIP activado manualmente hasta " . $expires->format('d/m/Y') . " (+10€ saldo bonus)"
                        : "No se pudo activar VIP";
                    $_SESSION[$ok ? 'success_message' : 'error_message'] = implode(' · ', $mensajes);
                } elseif ($accion_vip === 'desactivar') {
                    $usuario_pre = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
                    $sub_id_actual = $usuario_pre['vip_subscription_id'] ?? '';
                    $es_sub_real = $sub_id_actual && strpos($sub_id_actual, 'direct_activation_') !== 0;

                    if ($cancelar_stripe && $es_sub_real) {
                        include_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';
                        include_once __DIR__ . '/../config/stripe.php';
                        try {
                            \Stripe\Stripe::setApiKey(get_stripe_live_secret_key());
                            $sub = \Stripe\Subscription::retrieve($sub_id_actual);
                            if ($sub->status !== 'canceled') {
                                $sub->cancel();
                                $mensajes[] = "Suscripción Stripe cancelada ($sub_id_actual)";
                            } else {
                                $mensajes[] = "Stripe ya estaba canceled";
                            }
                            $invs = \Stripe\Invoice::all(['subscription' => $sub_id_actual, 'limit' => 5]);
                            foreach ($invs->data as $inv) {
                                if ($inv->status === 'open') {
                                    $inv->voidInvoice();
                                    $mensajes[] = "Factura {$inv->id} voided";
                                }
                            }
                        } catch (Throwable $e) {
                            $mensajes[] = "⚠️ Error Stripe: " . $e->getMessage();
                        }
                    }

                    $ok = desactivar_vip($user_id);
                    $mensajes[] = $ok ? "VIP desactivado en MongoDB" : "No se pudo desactivar VIP";

                    if ($enviar_email_decline && $ok) {
                        $usuario_post = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
                        $motivo = $_POST['motivo_decline'] ?? 'Pago rechazado';
                        $card_last4 = $_POST['card_last4'] ?? '';
                        $card_brand = $_POST['card_brand'] ?? '';
                        $res_email = enviarEmailVIPPagoFallido($usuario_post, $motivo, $card_last4, $card_brand);
                        $mensajes[] = $res_email['success'] ? "Email decline enviado" : "⚠️ Fallo email: " . ($res_email['error'] ?? '?');
                    }

                    $_SESSION[$ok ? 'success_message' : 'error_message'] = implode(' · ', $mensajes);
                }
            } catch (Throwable $e) {
                $_SESSION['error_message'] = "Error toggle VIP: " . $e->getMessage();
            }
            break;

        case 'patrocinar_codigo':
            $codigo_id = $_POST['codigo_id'];
            $tipo_destacado = $_POST['tipo_destacado']; // 'destacado' o 'destacado_social'

            try {
                $update_data = [
                    $tipo_destacado => strtotime('now'),
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];

                $result = $collection_codigos->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($codigo_id)],
                    ['$set' => $update_data]
                );

                if ($result->getModifiedCount() > 0) {
                    $_SESSION['success_message'] = "Código patrocinado correctamente";
                } else {
                    $_SESSION['error_message'] = "No se pudo patrocinar el código";
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error al patrocinar el código: " . $e->getMessage();
            }
            break;

        case 'recargar_saldo':
            $cantidad = floatval($_POST['cantidad'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? 'Recarga administrativa');

            if ($cantidad <= 0) {
                $_SESSION['error_message'] = "La cantidad debe ser mayor a 0";
            } else {
                try {
                    // Obtener saldo anterior antes de actualizar
                    $usuario_actual = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
                    $saldo_anterior = $usuario_actual['saldo'] ?? 0;
                    
                    // Actualizar saldo del usuario
                    $resultado = $collection_usuarios->updateOne(
                        ['_id' => new MongoDB\BSON\ObjectId($user_id)],
                        ['$inc' => ['saldo' => $cantidad]]
                    );

                    if ($resultado->getModifiedCount() > 0) {
                        // Registrar la transacción
                        $transaccion = [
                            'usuario_id' => $user_id,
                            'tipo' => 'recarga_admin',
                            'cantidad' => $cantidad,
                            'descripcion' => $motivo,
                            'fecha' => new MongoDB\BSON\UTCDateTime(),
                            'estado' => 'completada',
                            'admin_id' => $_SESSION["user_id"],
                            'admin_username' => $_SESSION["username"] ?? 'Admin',
                            'saldo_anterior' => $saldo_anterior,
                            'saldo_nuevo' => $saldo_anterior + $cantidad
                        ];
                        
                        $collection_transacciones->insertOne($transaccion);
                        
                        // Obtener el saldo actualizado para enviar email
                        $usuario_actualizado = $collection_usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
                        $saldo_actualizado = $usuario_actualizado['saldo'] ?? 0;
                        
                        // Obtener información para el email
                        $to_email = $usuario_actualizado['mail'] ?? '';
                        $to_name = $usuario_actualizado['username'] ?? 'Usuario';
                        $subject = "¡Felicidades! Tu saldo ha sido incrementado - CodigoAmigo";
                        $html_content = crearPlantillaEmailSaldo($to_name, $cantidad, $saldo_actualizado, $motivo);
                        
                        // Enviar email usando el método avanzado (Brevo con fallback) y registrar en el log
                        $resultado_email = enviarEmailConBrevoYRegistrar(
                            $to_email, 
                            $to_name, 
                            $subject, 
                            $html_content, 
                            'recarga_saldo', 
                            $user_id, 
                            ['cantidad' => $cantidad, 'saldo_anterior' => $saldo_actualizado - $cantidad, 'saldo_nuevo' => $saldo_actualizado, 'motivo' => $motivo]
                        );
                        $email_enviado = $resultado_email['success'];
                        
                        if ($email_enviado) {
                            $_SESSION['success_message'] = "Saldo recargado correctamente. Email de notificación enviado al usuario.";
                        } else {
                            $_SESSION['success_message'] = "Saldo recargado correctamente, pero no se pudo enviar el email.";
                        }
                    } else {
                        $_SESSION['error_message'] = "No se pudo actualizar el saldo";
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error al recargar saldo: " . $e->getMessage();
                }
            }
            break;
    }
    
    header('Location: admin_usuario_detalle.php?id=' . $user_id);
    exit;
}

// Obtener códigos del usuario - intentar ambos formatos
try {
    // Primero intentar como ObjectId
    $codigos = $collection_codigos->find(
        ['id_usuario' => new MongoDB\BSON\ObjectId($user_id)],
        ['sort' => ['fecha_publicacion' => -1]]
    )->toArray();
} catch (Exception $e) {
    // Si hay error con el ObjectId, intentar buscar por string
    $codigos = $collection_codigos->find(
        ['id_usuario' => $user_id],
        ['sort' => ['fecha_publicacion' => -1]]
    )->toArray();
}

// Si no encontramos códigos, intentar búsqueda alternativa
if (empty($codigos)) {
    // Buscar códigos donde id_usuario coincida como string
    $codigos_alternativa = $collection_codigos->find(
        ['id_usuario' => (string)$user_id],
        ['sort' => ['fecha_publicacion' => -1]]
    )->toArray();

    if (!empty($codigos_alternativa)) {
        $codigos = $codigos_alternativa;
    }
}

// Estadísticas del usuario
$estadisticas = [
    'total_codigos' => count($codigos),
    'codigos_activos' => count(array_filter($codigos, function($c) { return ($c['estado'] ?? 0) == 0; })),
    'codigos_inactivos' => count(array_filter($codigos, function($c) { return ($c['estado'] ?? 0) != 0; })),
    'codigos_sin_marca' => count(array_filter($codigos, function($c) { return !isset($c['marca']) || $c['marca'] === null || $c['marca'] === ''; })),
    'codigos_con_marca_invalida' => 0,
    'total_vistas' => array_sum(array_column($codigos, 'totalclicks')),
    'total_recargas' => count(array_filter($transacciones, function($t) {
        return in_array($t['tipo'] ?? '', ['recarga', 'recarga_admin', 'recarga_vip']);
    })),
    'total_saldo_recargado' => array_sum(array_column(
        array_filter($transacciones, function($t) {
            return in_array($t['tipo'] ?? '', ['recarga', 'recarga_admin', 'recarga_vip']);
        }),
        'cantidad'
    )),
    'total_patrocinios' => count(array_filter($transacciones, function($t) {
        return in_array($t['tipo'] ?? '', ['destacado', 'destacado_splash']);
    })),
    'saldo_actual' => $usuario['saldo'] ?? 0
];

// Contar códigos con marca inválida (marca existe pero no se encuentra en la BD)
$codigos_con_marca_invalida = 0;
foreach ($codigos as $codigo) {
    if (isset($codigo['marca']) && !empty($codigo['marca'])) {
        $marca = getObjectMarca('nombre_clave', $codigo['marca']);
        if (!$marca) {
            $codigos_con_marca_invalida++;
        }
    }
}
$estadisticas['codigos_con_marca_invalida'] = $codigos_con_marca_invalida;

// Función para formatear fecha
function formatearFecha($fecha) {
    if ($fecha instanceof MongoDB\BSON\UTCDateTime) {
        return date('d/m/Y H:i', $fecha->toDateTime()->getTimestamp());
    } elseif (is_string($fecha)) {
        return date('d/m/Y H:i', strtotime($fecha));
    }
    return 'N/A';
}

// Función para obtener el tipo de transacción formateado
function formatearTipoTransaccion($tipo) {
    $tipos = [
        'recarga' => 'Recarga de Saldo',
        'recarga_admin' => 'Recarga Administrativa',
        'recarga_vip' => 'Recarga Mensual VIP',
        'ajuste_admin' => 'Ajuste Administrativo',
        'destacado' => 'Patrocinio Código',
        'destacado_splash' => 'Patrocinio Masivo',
        'publicacion' => 'Publicación Código',
        'registro' => 'Registro Usuario'
    ];
    return $tipos[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
}

$title = "Detalle del Usuario - " . ($usuario['username'] ?? 'Usuario');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .info-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cantidad-positiva { color: #28a745; }
        .cantidad-negativa { color: #dc3545; }
        .cantidad-cero { color: #6c757d; }
        .badge-custom {
            font-size: 0.8em;
        }
        .codigo-text {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.85em;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php echo get_admin_sidebar_menu('admin_usuarios.php'); ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-admin">
                    <div class="container-fluid">
                        <h5 class="mb-0">Detalle del Usuario</h5>
                        <div class="d-flex align-items-center">
                            <a href="admin_usuarios.php" class="btn btn-outline-secondary btn-sm me-2">
                                <i class="fas fa-arrow-left me-1"></i>Volver
                            </a>
                            <span class="text-muted me-3">Bienvenido, <?php echo $_SESSION["username"] ?? 'Admin'; ?></span>
                            <a href="https://www.codigoamigo.com/logout" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Salir
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="p-4">
                    <!-- Debug info (solo visible para administradores) -->
                    <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['user_id'], ['58bd851da54e295b8b52f702', '5e78170e6b68e6519b7c5df2'])): ?>
                    <div class="alert alert-info small">
                        <strong>Debug:</strong> Usuario ID: <?php echo $user_id; ?> |
                        Códigos totales: <?php echo count($codigos); ?> |
                        Códigos activos: <?php echo $estadisticas['codigos_activos']; ?> |
                        Códigos inactivos: <?php echo $estadisticas['codigos_inactivos']; ?> |
                        Transacciones: <?php echo count($transacciones); ?>
                        <?php if (!empty($codigos)): ?>
                        <br><strong>Problemas encontrados:</strong>
                        Sin marca: <?php echo $estadisticas['codigos_sin_marca']; ?> |
                        Marca inválida: <?php echo $estadisticas['codigos_con_marca_invalida']; ?> |
                        <strong>Página pública mostraría:</strong> <?php echo count($codigos) - $estadisticas['codigos_sin_marca'] - $estadisticas['codigos_con_marca_invalida']; ?> códigos
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Mensajes -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Información del usuario -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="info-card p-4">
                                <div class="text-center mb-3">
                                    <?php if (isset($usuario['img']) && $usuario['img']): ?>
                                        <img src="<?php echo htmlspecialchars($usuario['img']); ?>"
                                             class="rounded-circle mb-3" width="80" height="80"
                                             onerror="this.src='https://via.placeholder.com/80'">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                             style="width: 80px; height: 80px;">
                                            <i class="fas fa-user text-white fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h4><?php echo htmlspecialchars($usuario['username'] ?? 'Sin nombre'); ?></h4>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($usuario['mail'] ?? 'Sin email'); ?></p>
                                    <?php if (isset($usuario['telefono'])): ?>
                                        <p class="text-muted mb-2">
                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($usuario['telefono']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="row text-center">
                                    <div class="col-6">
                                        <h6 class="text-muted">Estado</h6>
                                        <?php if (($usuario['estado'] ?? 0) == 1): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted">Tipo</h6>
                                        <span class="badge bg-info"><?php echo ucfirst($usuario['type'] ?? 'usuario'); ?></span>
                                    </div>
                                </div>

                                <hr>

                                <div class="row text-center">
                                    <div class="col-6">
                                        <h6 class="text-muted">Saldo Actual</h6>
                                        <span class="h5 <?php
                                            $saldo = $usuario['saldo'] ?? 0;
                                            echo $saldo > 0 ? 'text-success' : ($saldo < 0 ? 'text-danger' : 'text-muted');
                                        ?>">
                                            €<?php echo number_format($saldo, 2); ?>
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted">Zumbidos</h6>
                                        <span class="h5 text-primary"><?php echo $usuario['zumbido_saldo'] ?? 0; ?></span>
                                    </div>
                                </div>

                                <hr>

                                <!-- Botón de recarga de saldo -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalRecargarSaldo">
                                        <i class="fas fa-plus me-1"></i>Recargar Saldo
                                    </button>
                                </div>

                                <!-- Sección VIP -->
                                <?php
                                $es_vip_actual = !empty($usuario['is_vip']);
                                $vip_expires = $usuario['vip_expires_at'] ?? null;
                                if ($vip_expires instanceof MongoDB\BSON\UTCDateTime) $vip_expires = $vip_expires->toDateTime();
                                $vip_started = $usuario['vip_started_at'] ?? null;
                                if ($vip_started instanceof MongoDB\BSON\UTCDateTime) $vip_started = $vip_started->toDateTime();
                                $vip_cancelled = $usuario['vip_cancelled_at'] ?? null;
                                if ($vip_cancelled instanceof MongoDB\BSON\UTCDateTime) $vip_cancelled = $vip_cancelled->toDateTime();
                                $vip_sub_id = $usuario['vip_subscription_id'] ?? '';
                                $vip_cancel_pending = !empty($usuario['vip_cancel_pending']);
                                $vip_retention = !empty($usuario['vip_retention_applied']);
                                $es_sub_real = $vip_sub_id && strpos($vip_sub_id, 'direct_activation_') !== 0;
                                $stripe_sub_url = $es_sub_real ? 'https://dashboard.stripe.com/subscriptions/' . urlencode($vip_sub_id) : '';
                                ?>
                                <div class="mb-3 p-3" style="background:linear-gradient(135deg,#fff8e1 0%,#fffaed 100%);border:1px solid #ffd700;border-radius:10px;">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="mb-0"><i class="fas fa-crown" style="color:#d4a017"></i> Suscripción VIP</h6>
                                        <?php if ($es_vip_actual): ?>
                                            <span class="badge" style="background:#d4a017;color:#fff;">ACTIVO</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($es_vip_actual || $vip_sub_id): ?>
                                    <div style="font-size:12px;line-height:1.7;">
                                        <?php if ($vip_started): ?>
                                            <div><span class="text-muted">Desde:</span> <strong><?php echo $vip_started->format('d/m/Y'); ?></strong></div>
                                        <?php endif; ?>
                                        <?php if ($vip_expires): ?>
                                            <div><span class="text-muted">Expira:</span> <strong><?php echo $vip_expires->format('d/m/Y H:i'); ?></strong></div>
                                        <?php endif; ?>
                                        <?php if ($vip_cancelled && !$es_vip_actual): ?>
                                            <div><span class="text-muted">Cancelado:</span> <strong><?php echo $vip_cancelled->format('d/m/Y H:i'); ?></strong></div>
                                        <?php endif; ?>
                                        <?php if ($vip_sub_id): ?>
                                            <div>
                                                <span class="text-muted">Sub:</span>
                                                <?php if ($es_sub_real): ?>
                                                    <a href="<?php echo $stripe_sub_url; ?>" target="_blank" style="font-size:11px;font-family:monospace;">
                                                        <?php echo htmlspecialchars($vip_sub_id); ?> <i class="fas fa-external-link-alt" style="font-size:9px;"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <code style="font-size:11px;"><?php echo htmlspecialchars($vip_sub_id); ?></code>
                                                    <span class="badge bg-info" style="font-size:9px;">MANUAL</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($vip_cancel_pending): ?>
                                            <div class="text-warning"><i class="fas fa-clock"></i> Cancelación programada fin de período</div>
                                        <?php endif; ?>
                                        <?php if ($vip_retention): ?>
                                            <div class="text-info"><i class="fas fa-gift"></i> Retención aplicada</div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="mt-3">
                                    <?php if ($es_vip_actual): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100"
                                                data-bs-toggle="modal" data-bs-target="#modalDesactivarVIP">
                                            <i class="fas fa-times"></i> Desactivar VIP
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm w-100"
                                                style="background:#d4a017;color:#fff;"
                                                data-bs-toggle="modal" data-bs-target="#modalActivarVIP">
                                            <i class="fas fa-crown"></i> Activar VIP manualmente
                                        </button>
                                    <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Modal Activar VIP -->
                                <div class="modal fade" id="modalActivarVIP" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST">
                                            <div class="modal-content">
                                                <div class="modal-header" style="background:#d4a017;color:#fff;">
                                                    <h5 class="modal-title"><i class="fas fa-crown"></i> Activar VIP manualmente</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="toggle_vip">
                                                    <input type="hidden" name="accion_vip" value="activar">
                                                    <p><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario['username'] ?? ''); ?> (<?php echo htmlspecialchars($usuario['mail'] ?? ''); ?>)</p>
                                                    <div class="mb-3">
                                                        <label class="form-label">Meses de VIP</label>
                                                        <input type="number" name="meses" value="1" min="1" max="36" class="form-control" required>
                                                        <small class="text-muted">Se añadirán +10€ de saldo (bonus VIP, primera vez).</small>
                                                    </div>
                                                    <div class="alert alert-warning" style="font-size:13px;">
                                                        <i class="fas fa-info-circle"></i> Activación manual (no crea suscripción en Stripe). El sub_id será <code>direct_activation_&lt;timestamp&gt;</code>.
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn" style="background:#d4a017;color:#fff;">Activar VIP</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Modal Desactivar VIP -->
                                <div class="modal fade" id="modalDesactivarVIP" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title"><i class="fas fa-times-circle"></i> Desactivar VIP</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="toggle_vip">
                                                    <input type="hidden" name="accion_vip" value="desactivar">
                                                    <p><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario['username'] ?? ''); ?> (<?php echo htmlspecialchars($usuario['mail'] ?? ''); ?>)</p>

                                                    <?php if ($es_sub_real): ?>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" name="cancelar_stripe" value="1" id="chkCancelStripe" checked>
                                                        <label class="form-check-label" for="chkCancelStripe">
                                                            <strong>Cancelar suscripción en Stripe</strong> (<?php echo htmlspecialchars($vip_sub_id); ?>) y anular facturas abiertas
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" name="enviar_email_decline" value="1" id="chkEmailDecline">
                                                        <label class="form-check-label" for="chkEmailDecline">
                                                            Enviar email de pago fallido al usuario
                                                        </label>
                                                    </div>
                                                    <div id="camposEmailDecline" style="display:none; border-left:3px solid #ffc107; padding-left:12px;">
                                                        <div class="mb-2">
                                                            <label class="form-label" style="font-size:12px;">Motivo decline (texto banco)</label>
                                                            <input type="text" name="motivo_decline" class="form-control form-control-sm" placeholder="Your card does not support this type of purchase">
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-7">
                                                                <label class="form-label" style="font-size:12px;">Marca</label>
                                                                <input type="text" name="card_brand" class="form-control form-control-sm" placeholder="mastercard">
                                                            </div>
                                                            <div class="col-5">
                                                                <label class="form-label" style="font-size:12px;">Last4</label>
                                                                <input type="text" name="card_last4" class="form-control form-control-sm" placeholder="1234" maxlength="4">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="alert alert-info" style="font-size:13px;">
                                                        <i class="fas fa-info-circle"></i> Activación manual (sin suscripción Stripe). Solo se desactiva en MongoDB.
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-danger">Desactivar VIP</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <?php if ($es_sub_real): ?>
                                <script>
                                (function(){
                                    var chk = document.getElementById('chkEmailDecline');
                                    var box = document.getElementById('camposEmailDecline');
                                    if (chk && box) chk.addEventListener('change', function(){ box.style.display = chk.checked ? 'block' : 'none'; });
                                })();
                                </script>
                                <?php endif; ?>

                                <div class="text-center">
                                    <small class="text-muted">
                                        <strong>ID:</strong> <code class="d-block"><?php echo $usuario['_id']; ?></code>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <strong>Registro:</strong><br>
                                        <?php echo formatearFecha($usuario['fecha_registro'] ?? null); ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="card card-stat h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-code fa-2x text-primary mb-2"></i>
                                            <h4 class="text-primary"><?php echo number_format($estadisticas['total_codigos']); ?></h4>
                                            <p class="text-muted mb-0">Códigos Publicados</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card card-stat h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-eye fa-2x text-success mb-2"></i>
                                            <h4 class="text-success"><?php echo number_format($estadisticas['total_vistas']); ?></h4>
                                            <p class="text-muted mb-0">Vistas Totales</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card card-stat h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-credit-card fa-2x text-warning mb-2"></i>
                                            <h4 class="text-warning"><?php echo number_format($estadisticas['total_recargas']); ?></h4>
                                            <p class="text-muted mb-0">Recargas</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card card-stat h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-star fa-2x text-info mb-2"></i>
                                            <h4 class="text-info"><?php echo number_format($estadisticas['total_patrocinios']); ?></h4>
                                            <p class="text-muted mb-0">Patrocinios</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="info-card p-3">
                                        <h6 class="mb-3"><i class="fas fa-euro-sign me-2"></i>Saldo Recargado Total</h6>
                                        <h3 class="text-success mb-0">€<?php echo number_format($estadisticas['total_saldo_recargado'], 2); ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-card p-3">
                                        <h6 class="mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Problemas de Marca</h6>
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <h5 class="text-warning mb-0"><?php echo $estadisticas['codigos_sin_marca']; ?></h5>
                                                <small class="text-muted">Sin Marca</small>
                                            </div>
                                            <div class="col-6">
                                                <h5 class="text-danger mb-0"><?php echo $estadisticas['codigos_con_marca_invalida']; ?></h5>
                                                <small class="text-muted">Marca Inválida</small>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Estos códigos no se muestran en la página pública
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de movimientos -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Historial de Movimientos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>Marca</th>
                                            <th>Saldo Anterior</th>
                                            <th>Cantidad</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($transacciones)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                No hay movimientos registrados para este usuario
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($transacciones as $transaccion): ?>
                                        <tr style="cursor: pointer;" 
                                            onclick="window.location.href='admin_transacciones.php?transaccion_id=<?php echo (string)$transaccion['_id']; ?>'"
                                            title="Click para ver detalles de la transacción">
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo formatearFecha($transaccion['fecha'] ?? null); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo formatearTipoTransaccion($transaccion['tipo'] ?? 'desconocido'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($transaccion['descripcion'] ?? 'Sin descripción'); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($transaccion['marca'])): ?>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($transaccion['marca']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($transaccion['saldo_anterior'])): ?>
                                                    <span class="text-muted">€<?php echo number_format($transaccion['saldo_anterior'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="fw-bold <?php
                                                    $cantidad = $transaccion['cantidad'] ?? 0;
                                                    echo $cantidad > 0 ? 'cantidad-positiva' : ($cantidad < 0 ? 'cantidad-negativa' : 'cantidad-cero');
                                                ?>">
                                                    <?php if (($transaccion['tipo'] ?? '') == 'recarga' || ($transaccion['tipo'] ?? '') == 'recarga_admin' || ($transaccion['tipo'] ?? '') == 'ajuste_admin' || ($transaccion['tipo'] ?? '') == 'recarga_vip'): ?>
                                                        +€<?php echo number_format(abs($cantidad), 2); ?>
                                                    <?php else: ?>
                                                        €<?php echo number_format($cantidad, 2); ?>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Completada</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Códigos publicados -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-code me-2"></i>Códigos Publicados (<?php echo count($codigos); ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($codigos)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Este usuario no ha publicado ningún código
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Código</th>
                                            <th>Marca</th>
                                            <th>Descripción</th>
                                            <th>Estado</th>
                                            <th>Vistas</th>
                                            <th>ID Usuario</th>
                                            <th>Tipo Pago</th>
                                            <th>Destacado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($codigos as $codigo): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    <?php
                                                    $fecha_pub = $codigo['fecha_publicacion'] ?? null;
                                                    echo formatearFecha($fecha_pub);
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $codigo_texto = $codigo['codigo'] ?? '';
                                                if (strpos($codigo_texto, 'http') === 0 || strpos($codigo_texto, 'www') === 0) {
                                                    // Es una URL externa, mostrar acortada
                                                    echo '<span class="codigo-text text-muted" title="' . htmlspecialchars($codigo_texto) . '">URL externa</span>';
                                                } else {
                                                    // Es un código normal, mostrarlo
                                                    echo '<span class="codigo-text">' . htmlspecialchars($codigo_texto) . '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($codigo['marca'])): ?>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($codigo['marca']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin marca</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div title="<?php echo htmlspecialchars($codigo['descripcion'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars(substr($codigo['descripcion'] ?? '', 0, 50)); ?>
                                                    <?php if (strlen($codigo['descripcion'] ?? '') > 50): ?>...<?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $estado = $codigo['estado'] ?? 0;
                                                $estado_class = $estado == 0 ? 'success' : 'danger';
                                                $estado_text = $estado == 0 ? 'Activo' : 'Inactivo';
                                                ?>
                                                <span class="badge bg-<?php echo $estado_class; ?>"><?php echo $estado_text; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $codigo['totalclicks'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted font-monospace">
                                                    <?php echo htmlspecialchars($codigo['id_usuario'] ?? 'N/A'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                // La publicación inicial de códigos es gratuita
                                                // Solo cuando se destaca se puede pagar con tarjeta o saldo
                                                $tipo_pago = 'Gratuito';
                                                if (($codigo['destacado'] ?? 0) > 0 || ($codigo['destacado_social'] ?? 0) > 0) {
                                                    // Si está destacado, buscar en transacciones el método de pago
                                                    $transaccion_destacado = $collection_transacciones->findOne([
                                                        'codigo_id' => (string)$codigo['_id'],
                                                        'tipo' => 'destacado'
                                                    ], ['sort' => ['fecha' => -1]]);

                                                    if ($transaccion_destacado) {
                                                        // Verificar si fue con tarjeta (session_id) o con saldo (cantidad negativa)
                                                        if (isset($transaccion_destacado['session_id']) && !empty($transaccion_destacado['session_id'])) {
                                                            $tipo_pago = 'Tarjeta';
                                                        } elseif (($transaccion_destacado['cantidad'] ?? 0) < 0) {
                                                            $tipo_pago = 'Saldo';
                                                        }
                                                    }
                                                }
                                                ?>
                                                <span class="badge bg-info"><?php echo $tipo_pago; ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $destacado = $codigo['destacado'] ?? 0;
                                                $destacado_social = $codigo['destacado_social'] ?? 0;

                                                if ($destacado > 0) {
                                                    echo '<span class="badge bg-warning" title="Destacado: ' . date('d/m/Y H:i', $destacado) . '"><i class="fas fa-star me-1"></i>' . date('d/m/Y', $destacado) . '</span>';
                                                }
                                                if ($destacado_social > 0) {
                                                    echo '<span class="badge bg-success" title="Premium: ' . date('d/m/Y H:i', $destacado_social) . '"><i class="fas fa-crown me-1"></i>' . date('d/m/Y', $destacado_social) . '</span>';
                                                }
                                                if ($destacado == 0 && $destacado_social == 0) {
                                                    echo '<span class="text-muted">No patrocinado</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="admin_codigos.php?id_codigo=<?php echo $codigo['_id']; ?>"
                                                       class="btn btn-sm btn-outline-primary" title="Ver en admin">
                                                        <i class="fas fa-search"></i>
                                                    </a>
                                                    <?php if (!empty($codigo['marca'])): ?>
                                                        <a href="/de-<?php echo htmlspecialchars(strtolower($codigo['marca'])); ?>?codigo=<?php echo $codigo['_id']; ?>"
                                                           class="btn btn-sm btn-outline-info" target="_blank" title="Ver código público">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="btn btn-sm btn-outline-secondary disabled" title="Sin marca definida">
                                                            <i class="fas fa-question"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                                            onclick="patrocinarCodigo('<?php echo $codigo['_id']; ?>', 'destacado')"
                                                            title="Patrocinar código">
                                                        <i class="fas fa-star"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                            onclick="patrocinarCodigo('<?php echo $codigo['_id']; ?>', 'destacado_social')"
                                                            title="Patrocinar código premium">
                                                        <i class="fas fa-crown"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Historial de Emails Enviados -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-envelope me-2"></i>Historial de Emails Enviados (<?php echo count($email_logs); ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Asunto</th>
                                            <th>Destinatario</th>
                                            <th>Estado</th>
                                            <th>Método</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($email_logs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                No se han enviado emails a este usuario
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($email_logs as $email_log): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    $fecha_email = $email_log['fecha'] ?? $email_log['fecha_humana'] ?? '';
                                                    if ($fecha_email instanceof MongoDB\BSON\UTCDateTime) {
                                                        echo date('d/m/Y H:i', $fecha_email->toDateTime()->getTimestamp());
                                                    } elseif (is_string($fecha_email)) {
                                                        echo date('d/m/Y H:i', strtotime($fecha_email));
                                                    } else {
                                                        echo $fecha_email;
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php 
                                                    $tipos = [
                                                        'recarga_saldo' => 'Recarga Saldo',
                                                        'notificacion' => 'Notificación',
                                                        'activacion' => 'Activación',
                                                        'restablecimiento' => 'Restablecimiento',
                                                        'notificacion_codigo' => 'Notificación Código'
                                                    ];
                                                    echo $tipos[$email_log['tipo'] ?? ''] ?? ucfirst(str_replace('_', ' ', $email_log['tipo'] ?? 'desconocido'));
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div title="<?php echo htmlspecialchars($email_log['subject'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars(substr($email_log['subject'] ?? '', 0, 50)); ?>
                                                    <?php if (strlen($email_log['subject'] ?? '') > 50): ?>...<?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($email_log['to_email'] ?? ''); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($email_log['enviado'] ?? false): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Enviado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>Error
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($email_log['metodo'] ?? 'Desconocido'); ?>
                                                </small>
                                                <?php if (isset($email_log['error']) && !empty($email_log['error'])): ?>
                                                    <br><small class="text-danger" title="<?php echo htmlspecialchars($email_log['error']); ?>">
                                                        <i class="fas fa-exclamation-triangle"></i> Error
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para recargar saldo -->
    <div class="modal fade" id="modalRecargarSaldo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Recargar Saldo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="recargar_saldo">
                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad (€)</label>
                            <input type="number" class="form-control" id="cantidad" name="cantidad" step="0.01" min="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="3" placeholder="Ej: Recarga administrativa por..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Recargar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Función para patrocinar código
        function patrocinarCodigo(codigoId, tipoDestacado) {
            if (confirm('¿Estás seguro de patrocinar este código?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="patrocinar_codigo">
                    <input type="hidden" name="codigo_id" value="${codigoId}">
                    <input type="hidden" name="tipo_destacado" value="${tipoDestacado}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

<?php get_footer(); ?>
</body>
</html>
