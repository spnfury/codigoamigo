<?php
// Incluir funciones necesarias
if (!function_exists('getCodeByID')) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones.php';
}

get_header_new($title, $description, $title_social, $description_social, $imagen_social);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="text-white mb-4">Crear Promoción Temporal</h1>

            <?php if (isset($codigo_data)): ?>
                <div class="card bg-dark border-orange mb-4">
                    <div class="card-body">
                        <h5 class="text-white mb-3">Código seleccionado:</h5>
                        <p class="text-gray"><?php echo htmlspecialchars($codigo_data['descripcion']); ?></p>
                        <p class="text-gray">Precio actual: <strong class="text-white"><?php echo number_format($codigo_data['num_beneficio'] ?? 0, 2, ',', '.'); ?>€</strong></p>
                    </div>
                </div>

                <form method="POST" action="/crear-promocion">
                    <input type="hidden" name="codigo_id" value="<?php echo htmlspecialchars($codigo_data['_id']); ?>">
                    
                    <div class="card bg-dark border-orange">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="precio_promocional" class="form-label text-white">Precio Promocional (€)</label>
                                <input type="number" 
                                       class="form-control bg-light-gray border-orange text-white" 
                                       id="precio_promocional" 
                                       name="precio_promocional" 
                                       step="0.01" 
                                       min="0.01" 
                                       max="<?php echo ($codigo_data['num_beneficio'] ?? 0) - 0.01; ?>"
                                       required>
                                <small class="text-gray">Debe ser menor que el precio original (<?php echo number_format($codigo_data['num_beneficio'] ?? 0, 2, ',', '.'); ?>€)</small>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_fin" class="form-label text-white">Fecha de Finalización</label>
                                <input type="date" 
                                       class="form-control bg-light-gray border-orange text-white" 
                                       id="fecha_fin" 
                                       name="fecha_fin" 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                       required>
                                <small class="text-gray">La promoción debe terminar en el futuro</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-orange btn-lg">
                                    <i class="fas fa-check"></i> Crear Promoción
                                </button>
                                <a href="/mis-promociones" class="btn btn-outline-light">
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    No se pudo cargar la información del código.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer_new(); ?>

