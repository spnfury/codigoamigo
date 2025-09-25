<?php 
// Definir variables sociales para evitar errores
$title_social = $title ?? "Listado de marcas - CodigoAmigo.com";
$description_social = $description ?? "Descubre todas las marcas disponibles en CodigoAmigo.com";
$imagen_social = "https://www.codigoamigo.com/img/logo_codigoamigo_real4.png";

get_header_new($title, $description, $title_social, $description_social, $imagen_social); 
?>

<div class="container-fluid main_entremedio brands-page">
    <div class="container text-center bloque_titulo_home">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12 mensaje">
                    <h1>Todas las Marcas</h1>
                    <p>Descubre todas las marcas disponibles con códigos de descuento</p><br>
                </div>
            </div>
        </div>
    </div>

    <div class="row empieza_home">
        <div class="container">
            <div class="col-md-12 columns small-12 slider">
                <div class="title">
                    <h2>¡Destacadas!</h2>
                </div>

                <div class="destacado_div">
                    <div class="listado_codigos">
                        <?php 
                        $lista_marcas = getMarcas(null); 
                        
                        // Mostrar solo las primeras 12 marcas como destacadas
                        $marcas_destacadas = array_slice($lista_marcas, 0, 12);
                        
                        foreach($marcas_destacadas as $marca) {
                            if($marca["imagen"] != '' && $marca["imagen"] != 'Sin imagen') {
                                $marca["imagen"] = str_replace("http://", "https://", $marca["imagen"]);
                                
                                if($marca["imagen"] != '') {
                                    $marca_url = "/de-" . $marca["nombre_clave"];
                                    $marca_nombre = htmlspecialchars($marca["nombre"]);
                                    $marca_descripcion = htmlspecialchars($marca["descripción"] ?? '');
                                    $marca_imagen = htmlspecialchars($marca["imagen"]);
                                    $codigos_count = $marca["numero_codigos"] ?? 0;
                        ?>
                        <div class="pre_card destacado">
                            <div class="card_real destacado">
                                <div class="featured-badge">
                                    <i class="fas fa-star"></i> Destacado
                                </div>
                                
                                <div style="position: relative;height: 160px;">
                                    <a title="Códigos de <?php echo $marca_nombre; ?>" href="<?php echo $marca_url; ?>">
                                        <img loading="lazy" style="object-fit:scale-down;left:0px;position:absolute;" alt="<?php echo $marca_nombre; ?>" class="post-card imagen lazyload" src="<?php echo $marca_imagen; ?>">
                                    </a>
                                </div>

                                <div class="middle">
                                    <div class="avatar" item-start="">
                                        <div class="pre_avatar lazyload" data-src="https://www.codigoamigo.com/img/logo_codigoamigo_real4.png">
                                            <div rel="nofollow" class="link_usuario a_link_us" title="Códigos de <?php echo $marca_nombre; ?>" data-href="<?php echo $marca_url; ?>"></div>
                                        </div>
                                    </div>
                                    <div class="item-inner">
                                        <div class="input-wrapper">
                                            <div class="label">
                                                <div class="a_link_us" title="Códigos de <?php echo $marca_nombre; ?>" data-href="<?php echo $marca_url; ?>">
                                                    <p><?php echo $marca_nombre; ?></p>
                                                    <small class="ico hidden-xs">
                                                        <span showwhen="core"><i class="fas fa-tag"></i></span> 
                                                        <?php echo $codigos_count; ?> códigos
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 col-xs-12 container">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="beneficio-destacado">
                                                <div class="beneficio-icono">🏷️</div>
                                                <div class="beneficio-contenido">
                                                    <div class="beneficio-cantidad"><?php echo $codigos_count; ?></div>
                                                    <div class="beneficio-tipo">códigos disponibles</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class='div_inside'>
                                        <div class='text_inside' style='display: -webkit-box !important; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 10px; height: inherit !important;'>
                                            <?php echo $marca_descripcion ?: 'Descubre los mejores códigos de descuento para ' . $marca_nombre . '. Ahorra dinero en tus compras favoritas.'; ?>
                                        </div>
                                    </div>
                                    
                                    <a class="btn btn_codigo_amigo ir_codigo" title="Ver códigos de <?php echo $marca_nombre; ?>" href="<?php echo $marca_url; ?>">
                                        Ver códigos <i class="fas fa-angle-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php 
                                }
                            }
                        } 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="container">
            <div class="col-md-12">
                <div class="cd-home-title titulo_zona_home">Todas las marcas disponibles</div>
                <?php bloque_marcas_home(); ?>
            </div>
        </div>
    </div>
</div>

<!-- CSS personalizado para la página de marcas -->
<style>
/* Estilos específicos para la página de marcas */
.brands-page .destacado_div {
    margin-top: 20px;
}

.brands-page .pre_card.destacado {
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.brands-page .pre_card.destacado:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(255, 107, 53, 0.2);
}

.brands-page .featured-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(45deg, #ff6b35, #e55a2b);
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.brands-page .beneficio-destacado {
    background: linear-gradient(135deg, #ff6b35, #e55a2b);
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 15px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);
    transition: all 0.3s ease;
}

.brands-page .beneficio-destacado:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 53, 0.3);
}

.brands-page .beneficio-icono {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.brands-page .beneficio-cantidad {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.brands-page .beneficio-tipo {
    font-size: 0.9rem;
    opacity: 0.9;
}

.brands-page .btn_codigo_amigo {
    background: linear-gradient(135deg, #ff6b35, #e55a2b);
    border: none;
    color: white;
    padding: 12px 20px;
    border-radius: 25px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
    width: 100%;
    text-align: center;
}

.brands-page .btn_codigo_amigo:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
    color: white;
    text-decoration: none;
}

.brands-page .text_inside {
    color: #666;
    line-height: 1.5;
    font-size: 0.9rem;
}

.brands-page .title h2 {
    color: #ff6b35;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.brands-page .titulo_zona_home {
    color: #333;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #ff6b35;
}

/* Mejoras para la sección de todas las marcas */
.brands-page .panel.panel-default {
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.brands-page .panel.panel-default:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    border-color: #ff6b35;
}

.brands-page .panel-body a {
    text-decoration: none;
    color: inherit;
}

.brands-page .panel-body h2 {
    color: #333;
    font-weight: 600;
    margin-bottom: 10px;
}

.brands-page .panel-body .desc {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Responsive para la página de marcas */
@media (max-width: 768px) {
    .brands-page .pre_card.destacado {
        margin-bottom: 15px;
    }
    
    .brands-page .beneficio-destacado {
        padding: 12px;
    }
    
    .brands-page .beneficio-cantidad {
        font-size: 1.5rem;
    }
    
    .brands-page .featured-badge {
        font-size: 0.7rem;
        padding: 4px 8px;
    }
}

@media (max-width: 480px) {
    .brands-page .featured-badge {
        font-size: 0.6rem;
        padding: 3px 6px;
    }
    
    .brands-page .beneficio-destacado {
        padding: 10px;
    }
    
    .brands-page .btn_codigo_amigo {
        padding: 10px 16px;
        font-size: 0.9rem;
    }
    
    .brands-page .beneficio-cantidad {
        font-size: 1.3rem;
    }
}
</style>

<?php get_footer(); ?>