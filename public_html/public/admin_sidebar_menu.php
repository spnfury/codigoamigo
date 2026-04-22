<?php
/**
 * Genera el menú lateral del panel de administración con grupos colapsables
 * @param string $active_page Nombre del archivo de la página activa (ej: 'admin_dashboard.php')
 * @return string HTML del menú lateral
 */
function get_admin_sidebar_menu($active_page = '') {
    // Definir grupos de menú con sus items
    $menu_groups = [
        'main' => [
            'label' => null, // Sin etiqueta, items principales
            'icon' => null,
            'items' => [
                ['url' => 'admin_dashboard.php', 'icon' => 'fa-tachometer-alt', 'text' => 'Dashboard'],
            ]
        ],
        'content' => [
            'label' => 'Contenido',
            'icon' => 'fa-cube',
            'items' => [
                ['url' => 'admin_marcas.php', 'icon' => 'fa-tags', 'text' => 'Marcas'],
                ['url' => 'admin_faqs.php', 'icon' => 'fa-question-circle', 'text' => 'FAQs de Marcas'],
                ['url' => 'admin_codigos.php', 'icon' => 'fa-code', 'text' => 'Códigos'],
                ['url' => 'clean_orphan_codes.php', 'icon' => 'fa-broom', 'text' => 'Limpiar Códigos'],
            ]
        ],
        'users' => [
            'label' => 'Usuarios',
            'icon' => 'fa-users',
            'items' => [
                ['url' => 'admin_usuarios.php', 'icon' => 'fa-users', 'text' => 'Gestionar Usuarios'],
                ['url' => 'admin_chat.php', 'icon' => 'fa-comments', 'text' => 'Chat'],
                ['url' => 'admin_email_logs.php', 'icon' => 'fa-envelope', 'text' => 'Email Logs'],
            ]
        ],
        'finance' => [
            'label' => 'Finanzas',
            'icon' => 'fa-euro-sign',
            'items' => [
                ['url' => 'admin_finanzas.php', 'icon' => 'fa-chart-line', 'text' => 'Control Financiero'],
                ['url' => 'admin_transacciones.php', 'icon' => 'fa-credit-card', 'text' => 'Transacciones'],
            ]
        ],
        'affiliate' => [
            'label' => 'Afiliación',
            'icon' => 'fa-handshake',
            'items' => [
                ['url' => 'admin_amazon_tracking.php', 'icon' => 'fa-amazon', 'text' => 'Amazon Tracking'],
                ['url' => 'admin_affiliation_networks.php', 'icon' => 'fa-network-wired', 'text' => 'Redes'],
                ['url' => 'admin_affiliation_programs.php', 'icon' => 'fa-briefcase', 'text' => 'Programas y Mapeos'],
                ['url' => 'admin_affiliation_rules.php', 'icon' => 'fa-sort-amount-down', 'text' => 'Reglas de Prioridad'],
            ]
        ],
        'analytics' => [
            'label' => 'Analítica & SEO',
            'icon' => 'fa-chart-bar',
            'items' => [
                ['url' => 'admin_reportes.php', 'icon' => 'fa-chart-bar', 'text' => 'Reportes'],
                ['url' => 'admin_seo.php', 'icon' => 'fa-search', 'text' => 'SEO (GSC)'],
                ['url' => 'admin_seo_keywords.php', 'icon' => 'fa-key', 'text' => 'Keywords SEO'],
                ['url' => 'admin_analytics.php', 'icon' => 'fa-chart-area', 'text' => 'Analytics (GA4)'],
            ]
        ],
        'system' => [
            'label' => 'Sistema',
            'icon' => 'fa-cogs',
            'items' => [
                ['url' => 'admin_sitemaps.php', 'icon' => 'fa-sitemap', 'text' => 'Sitemaps'],
                ['url' => 'admin_configuracion.php', 'icon' => 'fa-cog', 'text' => 'Configuración'],
                ['url' => 'admin_logs.php', 'icon' => 'fa-file-alt', 'text' => 'Logs'],
            ]
        ],
    ];
    
    // Determinar qué grupo está activo
    $active_group = '';
    foreach ($menu_groups as $group_key => $group) {
        foreach ($group['items'] as $item) {
            if ($active_page === $item['url']) {
                $active_group = $group_key;
                break 2;
            }
        }
    }
    
    // CSS inline para el menú colapsable
    $html = '<style>
        .sidebar-menu-group {
            margin-bottom: 4px;
        }
        .sidebar-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sidebar-group-header:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .sidebar-group-header.active {
            color: white;
        }
        .sidebar-group-header .group-icon {
            margin-right: 10px;
            width: 18px;
            text-align: center;
        }
        .sidebar-group-header .chevron {
            transition: transform 0.2s ease;
            font-size: 0.7rem;
        }
        .sidebar-group-header.collapsed .chevron {
            transform: rotate(-90deg);
        }
        .sidebar-group-items {
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .sidebar-group-items.collapsed {
            max-height: 0 !important;
        }
        .sidebar-group-items .nav-link {
            padding: 8px 16px 8px 44px !important;
            font-size: 0.9rem;
        }
        .sidebar-group-items .nav-link i {
            width: 18px;
            text-align: center;
            font-size: 0.85rem;
        }
        .sidebar .nav-link.main-item {
            padding: 12px 16px !important;
        }
    </style>';
    
    $html .= '<div class="col-md-3 col-lg-2 sidebar p-0">';
    $html .= '<div class="p-3">';
    $html .= '<h4 class="text-white mb-4">';
    $html .= '<i class="fas fa-cogs me-2"></i>Admin Panel';
    $html .= '</h4>';
    $html .= '<nav class="nav flex-column">';
    
    foreach ($menu_groups as $group_key => $group) {
        if ($group['label'] === null) {
            // Items principales sin grupo
            foreach ($group['items'] as $item) {
                $is_active = ($active_page === $item['url']) ? ' active' : '';
                $html .= '<a class="nav-link main-item' . $is_active . '" href="' . $item['url'] . '">';
                $html .= '<i class="fas ' . $item['icon'] . ' me-2"></i>' . $item['text'];
                $html .= '</a>';
            }
        } else {
            // Grupo colapsable
            $is_group_active = ($active_group === $group_key);
            $collapsed_class = $is_group_active ? '' : ' collapsed';
            $group_id = 'menu-group-' . $group_key;
            
            $html .= '<div class="sidebar-menu-group">';
            
            // Header del grupo
            $html .= '<div class="sidebar-group-header' . ($is_group_active ? ' active' : '') . $collapsed_class . '" ';
            $html .= 'onclick="toggleSidebarGroup(\'' . $group_id . '\', this)" role="button">';
            $html .= '<span><i class="fas ' . $group['icon'] . ' group-icon"></i>' . $group['label'] . '</span>';
            $html .= '<i class="fas fa-chevron-down chevron"></i>';
            $html .= '</div>';
            
            // Items del grupo
            $items_count = count($group['items']);
            $max_height = $items_count * 40; // Aproximado para la animación
            $html .= '<div id="' . $group_id . '" class="sidebar-group-items' . $collapsed_class . '" ';
            $html .= 'style="max-height: ' . ($is_group_active ? $max_height . 'px' : '0') . ';">';
            
            foreach ($group['items'] as $item) {
                $is_active = ($active_page === $item['url']) ? ' active' : '';
                $html .= '<a class="nav-link' . $is_active . '" href="' . $item['url'] . '">';
                $html .= '<i class="fas ' . $item['icon'] . ' me-2"></i>' . $item['text'];
                $html .= '</a>';
            }
            
            $html .= '</div>'; // sidebar-group-items
            $html .= '</div>'; // sidebar-menu-group
        }
    }
    
    $html .= '<hr class="text-white my-3">';
    $html .= '<a class="nav-link main-item" href="https://www.codigoamigo.com">';
    $html .= '<i class="fas fa-home me-2"></i>Volver al sitio';
    $html .= '</a>';
    $html .= '</nav>';
    $html .= '</div>';
    $html .= '</div>';
    
    // JavaScript para el toggle
    $html .= '<script>
    function toggleSidebarGroup(groupId, headerEl) {
        const items = document.getElementById(groupId);
        const isCollapsed = items.classList.contains("collapsed");
        
        if (isCollapsed) {
            items.classList.remove("collapsed");
            headerEl.classList.remove("collapsed");
            items.style.maxHeight = items.scrollHeight + "px";
        } else {
            items.classList.add("collapsed");
            headerEl.classList.add("collapsed");
            items.style.maxHeight = "0";
        }
    }
    </script>';
    
    return $html;
}
?>
