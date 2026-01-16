<?php
// Página de marca renovada y optimizada
get_header_new($title, $description, $title_social, $description_social, $imagen_social, $links_meta);

global $detect_device, $codigo_existente, $u;

$numero_codigos_format = number_format($numero_codigos, 0, ',', '.');
?>

<!-- Incluir estilos CSS externos -->
<link rel="stylesheet" href="/assets/css/brand-page.css">

<div class="brand-page-container">
    <div class="container">
        
        <!-- Header de la marca -->
        <div class="brand-header fade-in-up">
            <div class="brand-logo-section">
                <img src="<?php echo $marca["imagen"]; ?>" alt="<?php echo $marca["nombre"]; ?>" class="brand-logo">
                <div class="brand-info">
                    <h1><?php echo $marca["nombre"]; ?></h1>
                    <?php if($marca["beneficio"]): ?>
                        <div class="brand-benefit">
                            <i class="fas fa-gift"></i> <?php echo $marca["beneficio"]; ?>€ Beneficio
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if($marca["descripción"]): ?>
                <div class="brand-description">
                    <?php echo $marca["descripción"]; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sección de códigos -->
        <div class="codes-section fade-in-up">
            <h2 class="section-title">
                <?php echo $numero_codigos_format; ?> Códigos de Descuento para <?php echo $marca["nombre"]; ?>
            </h2>
            
            <?php if($numero_codigos == 0): ?>
                <div class="no-codes-message">
                    <i class="fas fa-search"></i>
                    <h3>Aún no hay códigos de esta marca</h3>
                    <p>Sé el primero en publicar un código y ayuda a la comunidad</p>
                </div>
            <?php else: ?>
                <div class="codes-grid">
                    <?php 
                    // Mostrar códigos destacados primero
                    if($lista_codigos_patrocinados && !empty($lista_codigos_patrocinados)):
                        foreach($lista_codigos_patrocinados as $item):
                            $usuario = getObjectUser('_id', $item["id_usuario"]);
                            $datos_usuario = $usuario ? get_array_de_usuario($usuario) : array();
                    ?>
                        <div class="code-card featured">
                            <div class="featured-badge">
                                <i class="fas fa-star"></i> Destacado
                            </div>
                            
                            <div class="code-header">
                                <img src="<?php echo $datos_usuario["img"] ?? '/assets/img/default-avatar.png'; ?>" 
                                     alt="<?php echo $datos_usuario["username"] ?? 'Usuario'; ?>" 
                                     class="user-avatar">
                                <div class="user-info">
                                    <h4><?php echo $datos_usuario["username"] ?? 'Usuario'; ?></h4>
                                    <small>
                                        <i class="fas fa-eye"></i> <?php echo $item["totalclicks"] ?? 0; ?> vistas
                                        <span class="mx-2">•</span>
                                        <i class="far fa-clock"></i> <?php echo formatDateAgoLarge($item["fecha_publicacion"]); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="benefit-display">
                                <?php 
                                // Incluir funciones premium
                                if (!function_exists('generarHTMLPrecioConPromocion')) {
                                    include_once $_SERVER['DOCUMENT_ROOT'] . '/myphp/funciones_premium.php';
                                }
                                echo generarHTMLPrecioConPromocion($item["_id"], $item["num_beneficio"] ?? 0, $item["tipo_descuento"] ?? 'euros', true);
                                ?>
                                <div class="benefit-type"><?php echo $item["tipo_descuento"]; ?></div>
                            </div>
                            
                            <div class="code-description">
                                <?php echo substr($item["descripcion"], 0, 150) . (strlen($item["descripcion"]) > 150 ? '...' : ''); ?>
                            </div>
                            
                            <div class="code-actions">
                                <a href="<?php echo link_codigo($item["_id"], $marca["nombre_clave"]); ?>" 
                                   class="btn-primary">
                                    <i class="fas fa-arrow-right"></i> Ver Código
                                </a>
                                <a href="<?php echo link_usuario($datos_usuario["username"] ?? 'usuario', $datos_usuario["_id"] ?? ''); ?>" 
                                   class="btn-secondary">
                                    <i class="fas fa-user"></i> Perfil
                                </a>
                            </div>
                            
                            <div class="stats-bar">
                                <div class="vote-section">
                                    <button class="vote-btn negative" data-codigo-id="<?php echo $item["_id"]; ?>">-</button>
                                    <span class="vote-count"><?php echo ($item["votos_positivos"] ?? 0) - ($item["votos_negativos"] ?? 0); ?>°</span>
                                    <button class="vote-btn positive" data-codigo-id="<?php echo $item["_id"]; ?>">+</button>
                                </div>
                                <small>
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo ucfirst($item["provincia"] ?? 'España'); ?>
                                </small>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    endif;
                    
                    // Mostrar códigos normales
                    if($lista_codigos && !empty($lista_codigos)):
                        $codigos_normales = array_slice($lista_codigos, 0, 6); // Limitar a 6 códigos normales
                        foreach($codigos_normales as $item):
                            $usuario = getObjectUser('_id', $item["id_usuario"]);
                            $datos_usuario = $usuario ? get_array_de_usuario($usuario) : array();
                    ?>
                        <div class="code-card">
                            <div class="code-header">
                                <img src="<?php echo $datos_usuario["img"] ?? '/assets/img/default-avatar.png'; ?>" 
                                     alt="<?php echo $datos_usuario["username"] ?? 'Usuario'; ?>" 
                                     class="user-avatar">
                                <div class="user-info">
                                    <h4><?php echo $datos_usuario["username"] ?? 'Usuario'; ?></h4>
                                    <small>
                                        <i class="fas fa-eye"></i> <?php echo $item["totalclicks"] ?? 0; ?> vistas
                                        <span class="mx-2">•</span>
                                        <i class="far fa-clock"></i> <?php echo formatDateAgoLarge($item["fecha_publicacion"]); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="benefit-display">
                                <div class="benefit-amount"><?php echo $item["num_beneficio"]; ?>€</div>
                                <div class="benefit-type"><?php echo $item["tipo_descuento"]; ?></div>
                            </div>
                            
                            <div class="code-description">
                                <?php echo substr($item["descripcion"], 0, 150) . (strlen($item["descripcion"]) > 150 ? '...' : ''); ?>
                            </div>
                            
                            <div class="code-actions">
                                <a href="<?php echo link_codigo($item["_id"], $marca["nombre_clave"]); ?>" 
                                   class="btn-primary">
                                    <i class="fas fa-arrow-right"></i> Ver Código
                                </a>
                                <a href="<?php echo link_usuario($datos_usuario["username"] ?? 'usuario', $datos_usuario["_id"] ?? ''); ?>" 
                                   class="btn-secondary">
                                    <i class="fas fa-user"></i> Perfil
                                </a>
                            </div>
                            
                            <div class="stats-bar">
                                <div class="vote-section">
                                    <button class="vote-btn negative" data-codigo-id="<?php echo $item["_id"]; ?>">-</button>
                                    <span class="vote-count"><?php echo ($item["votos_positivos"] ?? 0) - ($item["votos_negativos"] ?? 0); ?>°</span>
                                    <button class="vote-btn positive" data-codigo-id="<?php echo $item["_id"]; ?>">+</button>
                                </div>
                                <small>
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo ucfirst($item["provincia"] ?? 'España'); ?>
                                </small>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
                
                <?php if(count($lista_codigos) > 6): ?>
                    <div class="text-center mt-4">
                        <a href="<?php echo link_marca($marca["nombre_clave"]); ?>?page=2" class="btn-primary">
                            <i class="fas fa-list"></i> Ver Todos los Códigos
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Sección para publicar código -->
        <?php if(!empty($_SESSION["user_id"])): ?>
            <div class="publish-section fade-in-up">
                <h3><i class="fas fa-plus-circle"></i> ¿Tienes un código de <?php echo $marca["nombre"]; ?>?</h3>
                <p>Comparte tu código y ayuda a la comunidad a ahorrar dinero</p>
                <a href="<?php echo link_nuevo_codigo(); ?>?marca=<?php echo urlencode($marca["nombre"]); ?>" 
                   class="btn-publish">
                    <i class="fas fa-plus"></i> Publicar Mi Código
                </a>
            </div>
        <?php else: ?>
            <div class="publish-section fade-in-up">
                <h3><i class="fas fa-plus-circle"></i> ¿Tienes un código de <?php echo $marca["nombre"]; ?>?</h3>
                <p>Regístrate gratis y comparte tu código para ayudar a otros usuarios</p>
                <a href="/registro" class="btn-publish">
                    <i class="fas fa-user-plus"></i> Registrarse Gratis
                </a>
            </div>
        <?php endif; ?>

        <!-- Sección de marcas relacionadas -->
        <?php if($marca["categoria_clave"]): ?>
            <div class="codes-section fade-in-up">
                <h2 class="section-title">Marcas Relacionadas</h2>
                <div class="codes-grid">
                    <?php 
                    $marcas_relacionadas = get_all_marcas_panel_control(6, '', $marca["categoria_clave"]);
                    foreach($marcas_relacionadas as $marca_rel):
                        if($marca_rel["nombre_clave"] != $marca["nombre_clave"]): // Excluir la marca actual
                    ?>
                        <div class="code-card">
                            <div class="code-header">
                                <img src="<?php echo $marca_rel["imagen"]; ?>" 
                                     alt="<?php echo $marca_rel["nombre"]; ?>" 
                                     class="user-avatar">
                                <div class="user-info">
                                    <h4><?php echo $marca_rel["nombre"]; ?></h4>
                                    <small><?php echo $marca_rel["num_codigos"] ?? 0; ?> códigos disponibles</small>
                                </div>
                            </div>
                            
                            <div class="code-actions">
                                <a href="<?php echo link_marca($marca_rel["nombre_clave"]); ?>" 
                                   class="btn-primary">
                                    <i class="fas fa-arrow-right"></i> Ver Códigos
                                </a>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Incluir JavaScript externo -->
<script src="/assets/js/brand-page.js"></script>
