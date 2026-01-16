<?php
/**
 * Funciones para el menú de categorías de chollos
 */

/**
 * Obtiene la estructura de categorías para el mega menú
 */
function getCategoriasChollosMenu() {
    return [
        'electronica' => [
            'nombre' => 'Electrónica',
            'icon' => 'laptop',
            'subcategorias' => [
                'moviles' => 'Móviles y Smartphones',
                'ordenadores' => 'Ordenadores y Portátiles',
                'tablets' => 'Tablets',
                'audio' => 'Audio y Auriculares',
                'smartwatch' => 'Smartwatches',
                'accesorios' => 'Accesorios Electrónicos'
            ]
        ],
        'moda' => [
            'nombre' => 'Moda',
            'icon' => 'tshirt',
            'subcategorias' => [
                'hombre' => 'Ropa de Hombre',
                'mujer' => 'Ropa de Mujer',
                'zapatillas' => 'Zapatillas y Calzado',
                'relojes' => 'Relojes',
                'complementos' => 'Complementos'
            ]
        ],
        'hogar' => [
            'nombre' => 'Hogar y Jardín',
            'icon' => 'home',
            'subcategorias' => [
                'muebles' => 'Muebles',
                'decoracion' => 'Decoración',
                'cocina' => 'Cocina',
                'jardin' => 'Jardín',
                'electrodomesticos' => 'Electrodomésticos'
            ]
        ],
        'deportes' => [
            'nombre' => 'Deportes',
            'icon' => 'running',
            'subcategorias' => [
                'fitness' => 'Fitness',
                'outdoor' => 'Outdoor',
                'bicicletas' => 'Bicicletas',
                'natacion' => 'Natación',
                'ropa-deportiva' => 'Ropa Deportiva'
            ]
        ],
        'infantil' => [
            'nombre' => 'Infantil',
            'icon' => 'child',
            'subcategorias' => [
                'juguetes' => 'Juguetes',
                'ropa-bebe' => 'Ropa de Bebé',
                'puericultura' => 'Puericultura',
                'educacion' => 'Educación'
            ]
        ],
        'videojuegos' => [
            'nombre' => 'Videojuegos',
            'icon' => 'gamepad',
            'subcategorias' => [
                'playstation' => 'PlayStation',
                'xbox' => 'Xbox',
                'nintendo' => 'Nintendo',
                'pc-gaming' => 'PC Gaming',
                'accesorios-gaming' => 'Accesorios Gaming'
            ]
        ],
        'libros' => [
            'nombre' => 'Libros y Música',
            'icon' => 'book',
            'subcategorias' => [
                'libros' => 'Libros',
                'ebooks' => 'eBooks',
                'musica' => 'Música',
                'vinilo' => 'Vinilos'
            ]
        ],
        'belleza' => [
            'nombre' => 'Belleza y Salud',
            'icon' => 'spa',
            'subcategorias' => [
                'cosmetica' => 'Cosmética',
                'perfumes' => 'Perfumes',
                'cuidado-personal' => 'Cuidado Personal',
                'salud' => 'Salud y Bienestar'
            ]
        ]
    ];
}

/**
 * Renderiza el selector de categorías estilo Amazon
 */
function renderCategorySelector() {
    $categorias = getCategoriasChollosMenu();
    
    // Construir items del menú
    $menu_items = '';
    foreach ($categorias as $slug => $cat) {
        $nombre = $cat['nombre'];
        $icon = $cat['icon'];
        $tiene_subcats = !empty($cat['subcategorias']);
        
        $subcats_html = '';
        if ($tiene_subcats) {
            foreach ($cat['subcategorias'] as $subslug => $subnombre) {
                $url_subcat = '/chollos/' . $slug . '/' . $subslug;
                $subcats_html .= '<a href="' . htmlspecialchars($url_subcat) . '" class="dropdown-subitem-modern">' . htmlspecialchars($subnombre) . '</a>';
            }
        }
        
        $url_cat = '/chollos/' . $slug;
        $arrow = $tiene_subcats ? '<i class="fas fa-chevron-right" style="margin-left: auto; font-size: 0.8em; color: #999;"></i>' : '';
        
        $menu_items .= <<<HTML
        <a href="{$url_cat}" class="dropdown-item-modern category-item-main">
            <i class="fas fa-{$icon}" style="color: #E30613; width: 20px;"></i>
            <span>{$nombre}</span>
            {$arrow}
        </a>
        {$subcats_html}
HTML;
    }
    
    return <<<HTML
    <div class="dropdown-modern">
        <button class="nav-link dropdown-toggle" type="button">
            Chollos <i class="fas fa-chevron-down"></i>
        </button>
        <div class="dropdown-menu-modern" style="max-height: 600px; overflow-y: auto;">
            <a href="/chollos" class="dropdown-item-modern">
                <i class="fas fa-tag"></i> Todos los chollos
            </a>
            <a href="https://t.me/cholloscodigoamigo" target="_blank" class="dropdown-item-modern telegram-link">
                <i class="fa-brands fa-telegram"></i> Canal de Telegram
            </a>
            <div style="border-top: 1px solid #eee; margin: 10px 0;"></div>
            {$menu_items}
        </div>
    </div>
    
    <style>
        .dropdown-subitem-modern {
            display: block;
            padding: 8px 20px 8px 50px;
            text-decoration: none;
            color: #666;
            font-size: 0.9em;
            transition: all 0.2s ease;
            background: #f8f9fa;
        }
        
        .dropdown-subitem-modern:hover {
            color: #E30613;
            background: white;
            padding-left: 55px;
        }
        
        .category-item-main {
            font-weight: 500;
        }
    </style>
HTML;
}

/**
 * Renderiza una barra horizontal de categorías visible (Estilo Amazon secundario)
 */
function renderHorizontalCategoryBar() {
    $categorias = getCategoriasChollosMenu();
    
    $items_html = '';
    // Añadir botón "Todo" al principio
    $items_html .= <<<HTML
    <a href="/chollos" class="h-cat-item h-cat-trigger">
        <i class="fas fa-bars"></i> Todo
    </a>
HTML;

    // Añadir las categorías principales
    foreach ($categorias as $slug => $cat) {
        $nombre = $cat['nombre'];
        $url = '/chollos/' . $slug;
        $items_html .= <<<HTML
        <a href="{$url}" class="h-cat-item">
            {$nombre}
        </a>
HTML;
    }
    
    // Añadir enlace a Telegram al final
    $items_html .= <<<HTML
    <a href="https://t.me/cholloscodigoamigo" target="_blank" class="h-cat-item h-cat-special">
        <i class="fa-brands fa-telegram"></i> Chollos Telegram
    </a>
HTML;

    return <<<HTML
    <div class="horizontal-category-bar">
        <div class="h-cat-container">
            {$items_html}
        </div>
    </div>
    
    <style>
        .horizontal-category-bar {
            background: linear-gradient(135deg, #E30613 0%, #c9050f 100%); /* CodigoAmigo red gradient */
            color: white;
            height: 40px;
            display: flex;
            align-items: center;
            overflow-x: auto;
            white-space: nowrap;
            padding: 0 20px;
            width: 100%;
            margin-top: 15px; /* Added spacing from header */
            /* Hide scrollbar */
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .horizontal-category-bar::-webkit-scrollbar {
            display: none;
        }
        
        .h-cat-container {
            display: flex;
            align-items: center;
            gap: 20px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .h-cat-item {
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 2px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid transparent;
        }
        
        .h-cat-item:hover {
            border-color: white;
            color: white;
            text-decoration: none;
        }
        
        .h-cat-trigger {
            font-weight: 700;
        }
        
        .h-cat-special {
            margin-left: auto;
            color: #fff;
            font-weight: 700;
        }
        
        .h-cat-special:hover {
            color: #ffd700;
            border-color: transparent;
        }
        
        @media (max-width: 768px) {
            .horizontal-category-bar {
                padding: 0 10px;
            }
            .h-cat-item {
                font-size: 13px;
                padding: 5px 8px;
            }
        }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Conectar el botón "Todo" con el dropdown principal si existe
        const hTrigger = document.getElementById('hCatTrigger');
        if(hTrigger) {
            hTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                // Simular click en el dropdown de categorías del header principal
                // Buscamos el botón que abre el menú de categorías
                const mainCatBtn = document.querySelector('.nav-link.dropdown-toggle'); 
                // Nota: esto es una aproximación, idealmente deberíamos tener un ID único
                if(mainCatBtn) mainCatBtn.click();
            });
        }
    });
    </script>
HTML;
}
