<?php
    if (!empty($lista_chollos)) {

        
        // CSS mejorado para chollos en búsqueda
        echo '<style>
        .chollos-search-section {
            background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
        }
        .chollos-search-section .chollos-grid-container {
            width: 100%;
            margin-bottom: 40px;
        }
        .chollos-search-section .chollos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            width: 100%;
            align-items: stretch;
        }
        .chollos-search-section .chollo-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid #f0f0f0;
        }
        .chollos-search-section .chollo-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 24px rgba(227, 6, 19, 0.2);
            border-color: #E30613;
        }
        .chollos-search-section .chollo-image-link {
            text-decoration: none;
            display: block;
            transition: opacity 0.3s;
        }
        .chollos-search-section .chollo-image-link:hover {
            opacity: 0.9;
        }
        .chollos-search-section .chollo-title-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .chollos-search-section .chollo-title-link:hover .chollo-title {
            color: #E30613;
        }
        .chollos-search-section .chollo-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #E30613 0%, #C40510 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.9em;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(227, 6, 19, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .chollos-search-section .chollo-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: #f0f0f0;
        }
        .chollos-search-section .chollo-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .chollos-search-section .chollo-image-link:hover .chollo-image img {
            transform: scale(1.05);
        }
        .chollos-search-section .chollo-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            min-height: 0;
        }
        .chollos-search-section .chollo-title {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
            line-height: 1.4;
            min-height: 50px;
            transition: color 0.3s;
        }
        .chollos-search-section .chollo-description {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .chollos-search-section .chollo-prices {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .chollos-search-section .chollo-price-original {
            font-size: 0.9em;
            color: #999;
            text-decoration: line-through;
        }
        .chollos-search-section .chollo-price-discount {
            font-size: 1.5em;
            font-weight: 800;
            color: #E30613;
            text-shadow: 0 1px 2px rgba(227, 6, 19, 0.1);
        }
        .chollos-search-section .chollo-clicks {
            font-size: 0.85em;
            color: #E30613;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .chollos-search-section .chollo-buttons {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }
        .chollos-search-section .chollo-button {
            flex: 1;
            padding: 12px 16px;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
            font-size: 0.95em;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            cursor: pointer;
        }
        .chollos-search-section .chollo-button-primary {
            background: linear-gradient(135deg, #E30613 0%, #C40510 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(227, 6, 19, 0.3);
        }
        .chollos-search-section .chollo-button-primary:hover {
            background: linear-gradient(135deg, #C40510 0%, #d44a1f 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(227, 6, 19, 0.4);
        }
        .chollos-search-section .chollo-button-secondary {
            background: #ffffff;
            color: #333;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        .chollos-search-section .chollo-button-secondary:hover {
            background: #E30613;
            border-color: #E30613;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(227, 6, 19, 0.3);
        }
        .chollos-search-section .chollo-card:hover .chollo-button-primary {
            background: #C40510;
        }
        @media (max-width: 768px) {
            .chollos-search-section .chollos-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 15px;
            }
            .chollos-search-section .chollos-grid-container {
                width: 100%;
            }
            .chollos-search-section .chollo-buttons {
                flex-direction: column;
                gap: 8px;
            }
        .chollos-search-section .chollo-button {
                width: 100%;
                padding: 10px 14px;
                font-size: 0.9em;
            }
            .telegram-channel-link {
                margin-top: 30px !important;
                padding: 20px !important;
            }
            .telegram-channel-link h3 {
                font-size: 1.2em !important;
            }
            .telegram-button {
                padding: 12px 24px !important;
                font-size: 1em !important;
            }
        }
        
        /* Premium Voting Styles via Inline */
        .chollo-voting-premium {
            display: flex;
            align-items: center;
            background: #252525;
            border-radius: 20px;
            padding: 4px;
            width: fit-content;
            border: 1px solid #333;
            gap: 2px;
            margin-bottom: 12px;
        }

        .vote-btn-premium {
            background: transparent;
            border: none;
            color: #888;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9em;
            padding: 0;
            line-height: 1;
        }

        .vote-btn-premium:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .vote-up-premium:hover {
            color: #ff5252;
            background: rgba(255, 82, 82, 0.1);
        }

        .vote-down-premium:hover {
            color: #81d4fa;
            background: rgba(129, 212, 250, 0.1);
        }

        .temp-premium {
            font-weight: 800;
            font-size: 0.95em;
            padding: 0 8px;
            min-width: 40px;
            text-align: center;
            color: #fff;
        }

        .vote-btn-premium.active.vote-up-premium {
            color: #ff5252;
            background: rgba(255, 82, 82, 0.1);
        }

        .vote-btn-premium.active.vote-down-premium {
            color: #81d4fa;
            background: rgba(129, 212, 250, 0.1);
        }
        </style>';
        
        echo '<div class="chollos-search-section" style="margin-top: 3rem; padding: 2rem 0;">';
        
        // Título mejorado con contador
        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">';
        echo '<div>';
        echo '<h3 class="section-subtitle" style="font-size: 1.75rem; color: #2c3e50; margin-bottom: 0.5rem; font-weight: 700; display: inline-flex; align-items: center; gap: 12px;">';
        echo '<i class="fas fa-fire" style="color: #E30613; font-size: 1.5rem;"></i>';
        echo 'Chollos encontrados';
        echo '</h3>';
        if ($total_chollos > 0) {
            $mostrando_desde = $chollos_skip + 1;
            $mostrando_hasta = min($chollos_skip + $chollos_per_page, $total_chollos);
            echo '<p style="color: #6c757d; font-size: 0.95rem; margin: 0;">Mostrando ' . $mostrando_desde . '-' . $mostrando_hasta . ' de ' . $total_chollos . ' chollos</p>';
        }
        echo '</div>';
        echo '</div>';
        
        // Incluir funciones para mostrar chollos
        if (!function_exists('imprimir_grid_chollos')) {
            include_once __DIR__ . '/myphp/funciones_modern.php';
        }
        
        if (function_exists('imprimir_grid_chollos')) {
            echo '<div class="chollos-grid-container">';
            imprimir_grid_chollos($lista_chollos, 3);
            echo '</div>';
        } else {
            // Fallback si la función no existe
            echo '<div class="chollos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">';
            foreach ($lista_chollos as $chollo) {
                if (function_exists('imprimir_tarjeta_chollo')) {
                    imprimir_tarjeta_chollo($chollo);
                }
            }
            echo '</div>';
        }
        
        // Paginación
        if ($total_chollos_pages > 1) {
            echo '<div class="chollos-pagination" style="margin-top: 3rem; display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap;">';
            
            // Botón anterior
            if ($chollos_page > 1) {
                $prev_page = $chollos_page - 1;
                $prev_url = '/ofertas/' . urlencode($termino) . ($prev_page > 1 ? '?chollos_page=' . $prev_page : '');
                echo '<a href="' . $prev_url . '" class="pagination-btn" style="';
                echo 'padding: 10px 20px; ';
                echo 'background: #ffffff; ';
                echo 'color: #E30613; ';
                echo 'border: 2px solid #E30613; ';
                echo 'border-radius: 8px; ';
                echo 'text-decoration: none; ';
                echo 'font-weight: 600; ';
                echo 'transition: all 0.3s; ';
                echo 'display: inline-flex; ';
                echo 'align-items: center; ';
                echo 'gap: 6px;';
                echo '">';
                echo '<i class="fas fa-chevron-left"></i>';
                echo 'Anterior';
                echo '</a>';
            } else {
                echo '<span class="pagination-btn disabled" style="';
                echo 'padding: 10px 20px; ';
                echo 'background: #f8f9fa; ';
                echo 'color: #adb5bd; ';
                echo 'border: 2px solid #e9ecef; ';
                echo 'border-radius: 8px; ';
                echo 'cursor: not-allowed; ';
                echo 'display: inline-flex; ';
                echo 'align-items: center; ';
                echo 'gap: 6px;';
                echo '">';
                echo '<i class="fas fa-chevron-left"></i>';
                echo 'Anterior';
                echo '</span>';
            }
            
            // Números de página
            $max_pages_to_show = 7;
            $start_page = max(1, $chollos_page - floor($max_pages_to_show / 2));
            $end_page = min($total_chollos_pages, $start_page + $max_pages_to_show - 1);
            
            if ($start_page > 1) {
                echo '<a href="/ofertas/' . urlencode($termino) . '" class="pagination-number" style="';
                echo 'padding: 10px 16px; ';
                echo 'background: #ffffff; ';
                echo 'color: #333; ';
                echo 'border: 2px solid #e9ecef; ';
                echo 'border-radius: 8px; ';
                echo 'text-decoration: none; ';
                echo 'font-weight: 600; ';
                echo 'transition: all 0.3s;';
                echo '">1</a>';
                if ($start_page > 2) {
                    echo '<span style="padding: 10px 8px; color: #6c757d;">...</span>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $chollos_page) {
                    echo '<span class="pagination-number active" style="';
                    echo 'padding: 10px 16px; ';
                    echo 'background: #E30613; ';
                    echo 'color: #ffffff; ';
                    echo 'border: 2px solid #E30613; ';
                    echo 'border-radius: 8px; ';
                    echo 'font-weight: 700; ';
                    echo 'cursor: default;';
                    echo '">' . $i . '</span>';
                } else {
                    $page_url = '/ofertas/' . urlencode($termino) . ($i > 1 ? '?chollos_page=' . $i : '');
                    echo '<a href="' . $page_url . '" class="pagination-number" style="';
                    echo 'padding: 10px 16px; ';
                    echo 'background: #ffffff; ';
                    echo 'color: #333; ';
                    echo 'border: 2px solid #e9ecef; ';
                    echo 'border-radius: 8px; ';
                    echo 'text-decoration: none; ';
                    echo 'font-weight: 600; ';
                    echo 'transition: all 0.3s;';
                    echo '">' . $i . '</a>';
                }
            }
            
            if ($end_page < $total_chollos_pages) {
                if ($end_page < $total_chollos_pages - 1) {
                    echo '<span style="padding: 10px 8px; color: #6c757d;">...</span>';
                }
                $last_url = '/ofertas/' . urlencode($termino) . '?chollos_page=' . $total_chollos_pages;
                echo '<a href="' . $last_url . '" class="pagination-number" style="';
                echo 'padding: 10px 16px; ';
                echo 'background: #ffffff; ';
                echo 'color: #333; ';
                echo 'border: 2px solid #e9ecef; ';
                echo 'border-radius: 8px; ';
                echo 'text-decoration: none; ';
                echo 'font-weight: 600; ';
                echo 'transition: all 0.3s;';
                echo '">' . $total_chollos_pages . '</a>';
            }
            
            // Botón siguiente
            if ($chollos_page < $total_chollos_pages) {
                $next_page = $chollos_page + 1;
                $next_url = '/ofertas/' . urlencode($termino) . '?chollos_page=' . $next_page;
                echo '<a href="' . $next_url . '" class="pagination-btn" style="';
                echo 'padding: 10px 20px; ';
                echo 'background: #E30613; ';
                echo 'color: #ffffff; ';
                echo 'border: 2px solid #E30613; ';
                echo 'border-radius: 8px; ';
                echo 'text-decoration: none; ';
                echo 'font-weight: 600; ';
                echo 'transition: all 0.3s; ';
                echo 'display: inline-flex; ';
                echo 'align-items: center; ';
                echo 'gap: 6px;';
                echo '">';
                echo 'Siguiente';
                echo '<i class="fas fa-chevron-right"></i>';
                echo '</a>';
            } else {
                echo '<span class="pagination-btn disabled" style="';
                echo 'padding: 10px 20px; ';
                echo 'background: #f8f9fa; ';
                echo 'color: #adb5bd; ';
                echo 'border: 2px solid #e9ecef; ';
                echo 'border-radius: 8px; ';
                echo 'cursor: not-allowed; ';
                echo 'display: inline-flex; ';
                echo 'align-items: center; ';
                echo 'gap: 6px;';
                echo '">';
                echo 'Siguiente';
                echo '<i class="fas fa-chevron-right"></i>';
                echo '</span>';
            }
            
            echo '</div>';
            
            // CSS adicional para hover de paginación
            echo '<style>
            .chollos-pagination .pagination-btn:hover:not(.disabled),
            .chollos-pagination .pagination-number:hover:not(.active) {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            .chollos-pagination .pagination-btn:hover:not(.disabled) {
                background: #C40510 !important;
                border-color: #C40510 !important;
            }
            .chollos-pagination .pagination-number:hover:not(.active) {
                background: #f8f9fa !important;
                border-color: #E30613 !important;
                color: #E30613 !important;
            }
            @media (max-width: 768px) {
                .chollos-pagination {
                    gap: 6px !important;
                }
                .chollos-pagination .pagination-btn,
                .chollos-pagination .pagination-number {
                    padding: 8px 12px !important;
                    font-size: 0.9rem !important;
                }
            }
            </style>';
        }
        
        // Enlace al canal de Telegram al final de los resultados
        if (!empty($lista_chollos)) {
            echo '<div class="telegram-channel-link" style="text-align: center; margin-top: 50px; padding: 30px; background: linear-gradient(135deg, #0088cc 0%, #006699 100%); border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 136, 204, 0.3);">';
            echo '<h3 style="color: white; margin-bottom: 15px; font-size: 1.5em; display: flex; align-items: center; justify-content: center; gap: 10px;">';
            echo '<span style="font-size: 1.3em;">✈️</span>';
            echo '¡Únete a nuestro canal de Telegram!';
            echo '</h3>';
            echo '<p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 20px; font-size: 1.1em;">';
            echo 'Recibe las mejores ofertas y chollos directamente en tu móvil';
            echo '</p>';
            echo '<a href="https://t.me/cholloscodigoamigo" target="_blank" rel="noopener noreferrer" ';
            echo 'class="telegram-button" ';
            echo 'style="display: inline-flex; align-items: center; gap: 10px; padding: 15px 30px; background: white; color: #0088cc; text-decoration: none; border-radius: 30px; font-weight: 700; font-size: 1.1em; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);">';
            echo '<span style="font-size: 1.3em;">✈️</span>';
            echo 'Unirse al canal';
            echo '</a>';
            echo '</div>';
            echo '<style>
            .telegram-channel-link {
                animation: fadeInUp 0.6s ease;
            }
            .telegram-button:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3) !important;
            }
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            </style>';
        }
        
        echo '</div>';
        
        // AdSense DESPUÉS de chollos
        if (!empty($termino) && function_exists('get_adsense_search')) {
            echo '<div class="adsense-search-chollos-after mb-3 mt-3" style="text-align: center; margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6; width: 100%; max-width: 100%; overflow: hidden; display: block; position: relative;">';
            echo '<div style="min-height: 100px; width: 100%; max-width: 100%; display: block; position: relative;">';
            echo '<h4 style="color: #333; margin-bottom: 10px; font-size: 16px; margin-top: 0;">Más anuncios sobre: <strong style="color: #E30613;">' . htmlspecialchars($termino) . '</strong></h4>';
            echo '<p style="font-size: 12px; color: #666; margin-bottom: 10px; margin-top: 0;">Ofertas especiales de ' . htmlspecialchars($termino) . '</p>';
            echo get_adsense_search($termino, null, 'chollos-after');
            echo '</div>';
            echo '</div>';
        }
    }
