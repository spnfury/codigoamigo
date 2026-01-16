<?php
get_header_new($title, $description, $title_social, $description_social, $imagen_social);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card bg-dark border-orange text-center p-5">
                <div class="mb-4">
                    <i class="fas fa-check-circle fa-5x text-success"></i>
                </div>
                <h1 class="text-white mb-3">¡Bienvenido a Premium!</h1>
                <p class="text-gray lead mb-4">Tu suscripción premium ha sido activada correctamente.</p>
                <p class="text-gray mb-4">Ahora puedes crear promociones temporales para tus códigos y aumentar su visibilidad.</p>
                <div class="mt-4">
                    <a href="/premium-dashboard" class="btn btn-orange btn-lg me-3">
                        <i class="fas fa-tachometer-alt"></i> Ir al Panel Premium
                    </a>
                    <a href="/premium" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-info-circle"></i> Ver Características
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer_new(); ?>

