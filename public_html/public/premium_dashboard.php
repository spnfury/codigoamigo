<?php
// Incluir funciones necesarias
if (!function_exists('obtenerPromocionesUsuario')) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_premium.php';
}
if (!function_exists('getCodeByID')) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones.php';
}

// Obtener promociones del usuario
$promociones_activas = obtenerPromocionesUsuario($_SESSION["user_id"], true);
$todas_promociones = obtenerPromocionesUsuario($_SESSION["user_id"], false);

get_header_new($title, $description, $title_social, $description_social, $imagen_social);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-white mb-4">Mis Promociones</h1>

            <div class="mb-4">
                <a href="/mis_codigos" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left"></i> Volver a Mis Códigos
                </a>
                <a href="/crear-promocion" class="btn btn-orange ms-2">
                    <i class="fas fa-plus"></i> Crear Nueva Promoción
                </a>
            </div>

            <!-- Promociones Activas -->
            <div class="card bg-dark border-orange mb-4">
                <div class="card-body">
                    <h3 class="text-white mb-3">
                        <i class="fas fa-fire text-orange"></i> Promociones Activas (<?php echo count($promociones_activas); ?>)
                    </h3>
                    
                    <?php if (empty($promociones_activas)): ?>
                        <p class="text-gray">No tienes promociones activas. <a href="/crear-promocion">Crea tu primera promoción</a></p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Precio Original</th>
                                            <th>Precio Promocional</th>
                                            <th>Días Restantes</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promociones_activas as $promocion): 
                                            $codigo = getCodeByID($promocion['codigo_id']);
                                            $dias_restantes = diasRestantesPromocion($promocion);
                                        ?>
                                            <tr>
                                                <td>
                                                    <?php if ($codigo): ?>
                                                        <a href="/de-<?php echo $codigo['marca']; ?>?codigo=<?php echo $promocion['codigo_id']; ?>" class="text-orange">
                                                            <?php echo htmlspecialchars(substr($codigo['descripcion'], 0, 50)); ?>...
                                                        </a>
                                                    <?php else: ?>
                                                        Código no encontrado
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="precio-original-tachado"><?php echo number_format($promocion['precio_original'], 2, ',', '.'); ?>€</span></td>
                                                <td><span class="text-orange fw-bold"><?php echo number_format($promocion['precio_promocional'], 2, ',', '.'); ?>€</span></td>
                                                <td>
                                                    <span class="badge bg-orange"><?php echo $dias_restantes; ?> días</span>
                                                </td>
                                                <td>
                                                    <form method="POST" action="/eliminar-promocion/<?php echo $promocion['_id']; ?>" style="display: inline;" onsubmit="return confirm('¿Seguro que quieres eliminar esta promoción?');">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i> Eliminar
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Historial de Promociones -->
                <?php if (count($todas_promociones) > count($promociones_activas)): ?>
                <div class="card bg-dark border-orange">
                    <div class="card-body">
                        <h3 class="text-white mb-3">
                            <i class="fas fa-history text-orange"></i> Historial de Promociones
                        </h3>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Precio Promocional</th>
                                        <th>Estado</th>
                                        <th>Fecha Fin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $promociones_expiradas = array_filter($todas_promociones, function($p) use ($promociones_activas) {
                                        $activa_ids = array_map(function($a) { return (string)$a['_id']; }, $promociones_activas);
                                        return !in_array((string)$p['_id'], $activa_ids);
                                    });
                                    foreach ($promociones_expiradas as $promocion): 
                                        $codigo = getCodeByID($promocion['codigo_id']);
                                        $fecha_fin = $promocion['fecha_fin'];
                                        if ($fecha_fin instanceof MongoDB\BSON\UTCDateTime) {
                                            $fecha_fin_ts = $fecha_fin->toDateTime()->getTimestamp();
                                        } else {
                                            $fecha_fin_ts = is_numeric($fecha_fin) ? $fecha_fin : strtotime($fecha_fin);
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ($codigo): ?>
                                                    <?php echo htmlspecialchars(substr($codigo['descripcion'], 0, 40)); ?>...
                                                <?php else: ?>
                                                    Código no encontrado
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format($promocion['precio_promocional'], 2, ',', '.'); ?>€</td>
                                            <td><span class="badge bg-secondary">Expirada</span></td>
                                            <td><?php echo date('d/m/Y', $fecha_fin_ts); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer_new(); ?>

