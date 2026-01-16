<?php
// Incluir funciones necesarias
if (!function_exists('esUsuarioPremium')) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_premium.php';
}

// Verificar si el usuario ya es premium
$es_premium = false;
if (!empty($_SESSION["user_id"])) {
    $es_premium = esUsuarioPremium($_SESSION["user_id"]);
}

get_header_new($title, $description, $title_social, $description_social, $imagen_social);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold text-white mb-3">Suscripción Premium</h1>
                <p class="lead text-gray">Desbloquea funciones exclusivas por solo 40€/mes</p>
            </div>

            <?php if ($es_premium): ?>
                <div class="alert alert-success text-center mb-4">
                    <i class="fas fa-check-circle"></i> Ya tienes una suscripción premium activa
                    <a href="/premium-dashboard" class="btn btn-outline-light btn-sm ms-3">Ir al Panel Premium</a>
                </div>
            <?php endif; ?>

            <!-- Características -->
            <div class="row mb-5">
                <div class="col-md-4 mb-4">
                    <div class="card bg-dark border-orange h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-tag fa-3x text-orange"></i>
                            </div>
                            <h4 class="text-white">Promociones Temporales</h4>
                            <p class="text-gray">Crea promociones por tiempo limitado para tus códigos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card bg-dark border-orange h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-eye fa-3x text-orange"></i>
                            </div>
                            <h4 class="text-white">Mayor Visibilidad</h4>
                            <p class="text-gray">Precios tachados y destacados aumentan las conversiones</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card bg-dark border-orange h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-chart-line fa-3x text-orange"></i>
                            </div>
                            <h4 class="text-white">Mejores Resultados</h4>
                            <p class="text-gray">Aumenta tus clicks y conversiones con ofertas especiales</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Precio -->
            <div class="text-center mb-5">
                <div class="pricing-card bg-dark border-orange p-5 rounded">
                    <h2 class="text-white mb-3">40€/mes</h2>
                    <p class="text-gray mb-4">Suscripción mensual recurrente</p>
                    <?php if (!$es_premium && !empty($_SESSION["user_id"])): ?>
                        <a href="/crear-sesion-premium" class="btn btn-orange btn-lg px-5">
                            <i class="fas fa-credit-card"></i> Suscribirse Ahora
                        </a>
                    <?php elseif (empty($_SESSION["user_id"])): ?>
                        <a href="/login" class="btn btn-orange btn-lg px-5">
                            <i class="fas fa-sign-in-alt"></i> Inicia Sesión para Suscribirte
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ejemplo Visual -->
            <div class="bg-dark border-orange p-4 rounded mb-5">
                <h3 class="text-white mb-4">¿Cómo funciona?</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-orange mb-3">Sin Promoción</h5>
                        <div class="benefit-display bg-light-gray p-3 rounded">
                            <div class="precio-normal">
                                <span class="precio-standard">50€</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-orange mb-3">Con Promoción Premium</h5>
                        <div class="benefit-display bg-light-gray p-3 rounded position-relative">
                            <div class="promocion-badge" style="position: absolute; top: -10px; right: -10px;">
                                <span class="promo-text">PROMO</span>
                                <span class="promo-days">3 días</span>
                            </div>
                            <div class="precio-con-promocion">
                                <span class="precio-promocional">35€</span>
                                <span class="precio-original-tachado">50€</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer_new(); ?>

