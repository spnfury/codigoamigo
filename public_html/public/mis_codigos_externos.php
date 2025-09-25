<?php 
    get_header_new($title, $description, $title_social, $description_social, $imagen_social); 
    
    $link_usuario = enlace_usuario($_SESSION["username"], $_SESSION["user_id"]);
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

:root {
    --primary-color: #4a6cf7;
    --secondary-color: #6c757d;
    --gradient-start: #4a6cf7;
    --gradient-end: #2541b2;
}

.codigos_ext {
    min-height: 100vh;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    font-family: 'Poppins', sans-serif;
    overflow-x: hidden;
}

/* Estilos para eliminar scrolls verticales */
.card-body {
    overflow: hidden;
}

.card-body .descripcion {
    max-height: none;
    overflow: visible;
    white-space: normal;
    text-overflow: initial;
}

.card-body .listado-codigos {
    overflow: visible;
}

#header_usuario {
    background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?php echo $datos_usuario["img"]; ?>');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    height: 500px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    margin-bottom: 2rem;
    clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
}

.img_circle {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 4px solid rgba(255, 255, 255, 0.8);
    overflow: hidden;
    display: inline-block;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
}

.img_circle::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 50%;
    background: linear-gradient(45deg, rgba(74, 108, 247, 0.3), rgba(37, 65, 178, 0.3));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.img_circle:hover {
    transform: scale(1.1) rotate(5deg);
    border-color: white;
}

.img_circle:hover::after {
    opacity: 1;
}

.img_circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.foto_usuario {
    padding: 3rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transform: translateY(0);
    transition: transform 0.3s ease;
}

.foto_usuario:hover {
    transform: translateY(-5px);
}

.foto_usuario h2 {
    font-size: 2.5rem;
    font-weight: 600;
    margin: 1rem 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.foto_usuario p {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 0;
}

.container-bottom {
    padding: 3rem;
    background: none !important;
    margin-top: -100px;
    position: relative;
    z-index: 1;
    transform: translateY(0);
    transition: transform 0.3s ease;
}

.container-bottom:hover {
    transform: translateY(-5px);
}

.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    #header_usuario {
        height: 400px;
        clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
    }
    
    .container-bottom {
        margin-top: -50px;
        padding: 2rem;
    }
    
    .img_circle {
        width: 120px;
        height: 120px;
    }
    
    .foto_usuario h2 {
        font-size: 2rem;
    }
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.foto_usuario, .container-bottom {
    animation: fadeIn 0.8s ease-out forwards;
}
</style>

<div class="codigos_ext">
    <section id="header_usuario">
        <div class="row">
            <div class="col-md-12 col-xs-12 text-center foto_usuario">
                <span class="img_circle">
                    <img src="<?php echo $datos_usuario["img"]; ?>" alt="User profile">
                </span>
                <h2 class="mt-4"><?php echo $datos_usuario["username"]; ?></h2>
                <p class="lead"><?php echo $num_codigos; ?> Códigos amigo</p>
            </div>
        </div>
    </section>

    <div class="container container-bottom">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="card">
                    <div class="card-body">
                        <?php block_listado_codigos_lite($listado_codigos, $tipo_block_codigos); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>