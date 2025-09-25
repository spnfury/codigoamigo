<?php
/**
 * Funciones auxiliares para la página de marca con diseño de home
 */

/**
 * Bloque de información de la marca (versión desktop)
 */
function bloque_info_marca($marca, $marca_info) {
    $nombre_marca = $marca_info['nombre'] ?? ucfirst($marca);
    $imagen_marca = $marca_info['imagen'] ?? '';
    $descripcion_marca = $marca_info['descripcion'] ?? '';
    ?>
    
    <div class="row">
        <div class="container text-center bloque_primero_home">
            <div class="row">
                <!-- Video de la marca si existe -->
                <div class="collapse" id="video_marca_<?php echo $marca; ?>">
                    <div class="card card-block">
                        <video id="video_<?php echo $marca; ?>" width="640" height="360" controls preload="none">
                            <source src="/img/marcas/<?php echo $marca; ?>.mp4" />
                        </video>
                    </div>
                </div>
            </div>
            
            <div class="row bloque_1_home panel panel-default" style="padding:10px;">
                <!-- Información de la marca -->
                <div class="col-md-4 col-xs-6 text-center">
                    <?php if($imagen_marca): ?>
                        <img class="lazyload" data-src="<?php echo htmlspecialchars($imagen_marca); ?>" alt="<?php echo htmlspecialchars($nombre_marca); ?>" style="max-width: 100px; max-height: 100px; object-fit: contain;" />
                    <?php else: ?>
                        <img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img1.png" />
                    <?php endif; ?>
                    <br>
                    <p class="text-justify">
                        <span class="number">1</span> 
                        Sube tu código amigo de <?php echo $nombre_marca; ?>. ¡No te llevará más de 10 segundos y es completamente <b>gratuito</b>!
                    </p>
                </div>
                
                <div class="col-md-4 col-xs-6 text-center">
                    <img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img2.png" />
                    <br>
                    <p class="text-justify">
                        <span class="number">2</span> 
                        ¡Comprueba como tu código de <?php echo $nombre_marca; ?> llega a muchísima gente! <b>Los usuarios podrán acceder a tu código</b> y obtendréis muchísimas ventajas !!
                    </p>
                </div>
                
                <div class="col-md-4 col-xs-12 text-center">
                    <img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img3.png" />
                    <br>
                    <p class="text-justify">
                        <span class="number">3</span> 
                        Olvídate de ir buscando amigos para ganar descuentos con <?php echo $nombre_marca; ?> y deja que nosotros te hagamos el trabajo <b>¡A disfrutar!</b>
                    </p>
                </div>
                
                <div class="txt-center col-md-12">
                    <a class="btn btn_codigo_amigo" data-toggle="collapse" href="#video_marca_<?php echo $marca; ?>" aria-expanded="false" aria-controls="video_marca_<?php echo $marca; ?>">
                        <i class="fa fa-play" aria-hidden="true"></i> 
                        <span>Ver video - ¿Qué es <?php echo $nombre_marca; ?>?</span>
                    </a>
                </div>
            </div>
            
            <?php 
            // Mostrar botón de publicar código específico para la marca
            publica_tu_codigo_marca($marca, $marca_info);
            ?>
        </div>
    </div>
    
    <?php
}

/**
 * Bloque de información de la marca (versión móvil)
 */
function bloque_info_marca_mobile($marca, $marca_info) {
    $nombre_marca = $marca_info['nombre'] ?? ucfirst($marca);
    $imagen_marca = $marca_info['imagen'] ?? '';
    ?>
    
    <div class="container text-center bloque_primero_home">
        <div class="row">
            <a class="btn btn_codigo_amigo" data-toggle="collapse" href="#video_marca_<?php echo $marca; ?>" aria-expanded="false" aria-controls="video_marca_<?php echo $marca; ?>">
                <i class="fa fa-play" aria-hidden="true"></i> 
                <span>¿Qué es <?php echo $nombre_marca; ?>?</span>
            </a>
            
            <br><br>
            
            <div class="collapse" id="video_marca_<?php echo $marca; ?>">
                <div class="card card-block">
                    <video id="video_<?php echo $marca; ?>" width="100%" height="auto" controls preload="none">
                        <source src="/img/marcas/<?php echo $marca; ?>.mp4" />
                    </video>
                </div>
            </div>
            
            <div class="row bloque_1_home">
                <div class="col-md-4 col-xs-6 text-center">
                    <?php if($imagen_marca): ?>
                        <img class="lazyload" data-src="<?php echo htmlspecialchars($imagen_marca); ?>" alt="<?php echo htmlspecialchars($nombre_marca); ?>" style="max-width: 80px; max-height: 80px; object-fit: contain;" />
                    <?php else: ?>
                        <img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img1.png" />
                    <?php endif; ?>
                    <br>
                    <p class="text-justify">
                        <span class="number">1</span> 
                        Sube tu código amigo de <?php echo $nombre_marca; ?>. ¡No te llevará más de 10 segundos y es completamente <b>gratuito</b>!
                    </p>
                </div>
                
                <div class="col-md-4 col-xs-6 text-center">
                    <img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img2.png" />
                    <br>
                    <p class="text-justify">
                        <span class="number">2</span> 
                        ¡Comprueba como tu código de <?php echo $nombre_marca; ?> llega a muchísima gente! <b>Los usuarios podrán acceder a tu código</b> y obtendréis muchísimas ventajas !!
                    </p>
                </div>
                
                <div class="col-md-4 col-xs-12 text-center">
                    <img class="lazyload" data-src="https://www.codigoamigo.com/img/home/img3.png" />
                    <br>
                    <p class="text-justify">
                        <span class="number">3</span> 
                        Olvídate de ir buscando amigos para ganar descuentos con <?php echo $nombre_marca; ?> y deja que nosotros te hagamos el trabajo <b>¡A disfrutar!</b>
                    </p>
                </div>
            </div>
            
            <?php 
            // Mostrar botón de publicar código específico para la marca
            publica_tu_codigo_marca($marca, $marca_info);
            ?>
        </div>
    </div>
    
    <?php
}

/**
 * Función para mostrar el botón de publicar código específico para una marca
 */
function publica_tu_codigo_marca($marca, $marca_info) {
    $nombre_marca = $marca_info['nombre'] ?? ucfirst($marca);
    ?>
    
    <div class="text-center col-md-12" style="margin-top: 20px;">
        <?php if(!empty($_SESSION["user_id"])): ?>
            <a class="btn btn_codigo_amigo" href="<?php echo link_nuevo_codigo(); ?>?marca=<?php echo urlencode($marca); ?>">
                <i class="fas fa-plus"></i> Publicar código de <?php echo $nombre_marca; ?>
            </a>
        <?php else: ?>
            <a class="btn btn_codigo_amigo" href="<?php echo link_registro(); ?>">
                <i class="fas fa-user-plus"></i> Regístrate para publicar códigos de <?php echo $nombre_marca; ?>
            </a>
        <?php endif; ?>
    </div>
    
    <?php
}

// La función get_related_brands() ya existe en funciones_modern.php

// La función generate_chollometro_filter_menu() ya existe en funciones_modern.php

/**
 * Función para obtener el enlace de registro
 */
function link_registro() {
    return (isset($GLOBALS["website"]) ? $GLOBALS["website"] : 'https://www.codigoamigo.com/') . "registro";
}
