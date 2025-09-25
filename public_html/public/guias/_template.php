<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/header.php'; ?>

<div class="container guia-container">
    <div class="row">
        <div class="col-md-8">
            <article class="guia-content">
                <header>
                    <h1><?php echo $title; ?></h1>
                    <div class="meta">
                        <span class="date">Actualizado: <?php echo date('d/m/Y'); ?></span>
                        <?php if (isset($categoria)): ?>
                        <span class="category"><?php echo ucfirst($categoria); ?></span>
                        <?php endif; ?>
                    </div>
                </header>
                
                <!-- Contenido específico de cada guía -->
                <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/public/guias/contenido/' . $name_page . '_content.php'; ?>
                
                <footer class="author-info">
                    <p>Autor: Equipo de <?php echo $author_web; ?></p>
                </footer>
            </article>
        </div>
        
        <div class="col-md-4">
            <!-- Sidebar común para todas las guías -->
            <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/public/guias/_sidebar.php'; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>