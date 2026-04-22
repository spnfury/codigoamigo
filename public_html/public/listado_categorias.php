<?php get_header_new($title, $description, $title_social, $description_social, $imagen_social); ?>

<div class="container">
	<div class="title">
		<h2>Categorías</h2>
	</div>

	<div class="ccp listado_categorias">



		<style>
			/* Diseño moderno con iconos */
			.categoria-card {
				display: inline-block;
				width: 48%;
				margin: 1%;
				background: linear-gradient(135deg, #2C2C2C 0%, #1a1a1a 100%);
				border-radius: 12px;
				padding: 20px;
				text-align: center;
				transition: all 0.3s ease;
				border: 1px solid rgba(227, 6, 19, 0.2);
				min-height: 120px;
				position: relative;
				overflow: hidden;
			}

			.categoria-card:hover {
				transform: translateY(-2px);
				box-shadow: 0 8px 25px rgba(227, 6, 19, 0.3);
				border-color: rgba(227, 6, 19, 0.5);
			}

			.categoria-card::before {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background: linear-gradient(135deg, rgba(227, 6, 19, 0.1) 0%, rgba(227, 6, 19, 0.05) 100%);
				opacity: 0;
				transition: opacity 0.3s ease;
			}

			.categoria-card:hover::before {
				opacity: 1;
			}

			.categoria-icon {
				font-size: 2.5rem;
				color: #E30613;
				margin-bottom: 12px;
				position: relative;
				z-index: 2;
			}

			.categoria-name {
				color: #ffffff;
				font-size: 14px;
				font-weight: 600;
				line-height: 1.3;
				margin: 0;
				position: relative;
				z-index: 2;
			}

			.categoria-link {
				text-decoration: none;
				display: block;
				height: 100%;
				width: 100%;
				position: relative;
				z-index: 3;
			}

			/* Responsive */
			@media (max-width: 768px) {
				.categoria-card {
					width: 98%;
					margin: 1%;
				}

				.categoria-icon {
					font-size: 2rem;
				}

				.categoria-name {
					font-size: 13px;
				}
			}

			@media (max-width: 480px) {
				.categoria-card {
					padding: 15px;
					min-height: 100px;
				}

				.categoria-icon {
					font-size: 1.8rem;
				}
			}
		</style>

		<div class="container container-top container-bottom text-center">
			<div class="ccp listado_categorias">
				<?php
				$lista_categorias = getCategorias();
				foreach($lista_categorias as $cat) {
					$cat_info = get_category_info($cat["nombre_clave"]);
					$icono = $cat_info['icono'] ?? 'fas fa-tag';
					?>
					<div class="categoria-card">
						<a href="<?php echo link_categoria($cat["nombre_clave"]); ?>" class="categoria-link" id="<?php echo $cat['nombre_clave'] ?>">
							<div class="categoria-icon">
								<i class="<?php echo $icono; ?>"></i>
							</div>
							<h3 class="categoria-name"><?php echo $cat["nombre"]; ?></h3>
						</a>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>