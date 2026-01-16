<?php
// Ensure $categorias is available
if (!isset($categorias)) {
    if (function_exists('obtenerCategoriasChollos')) {
        $categorias = obtenerCategoriasChollos();
    } else {
        // Try including the helper file if function not found
        $helper_path = __DIR__ . '/../funciones_chollos_helpers.php';
        if (file_exists($helper_path)) {
            include_once $helper_path;
        }
        
        if (function_exists('obtenerCategoriasChollos')) {
            $categorias = obtenerCategoriasChollos();
        } else {
            $categorias = [];
        }
    }
}
?>

<!-- Sidebar Filters -->
<aside class="chollos-sidebar" id="filterSidebar">
    <!-- Mobile Sidebar Header -->
    <div class="mobile-sidebar-header">
        <span class="mobile-sidebar-title">Filtrar y Ordenar</span>
        <button class="mobile-sidebar-close" onclick="closeMobileSidebar()">&times;</button>
    </div>
    
    <!-- Filter: Sort -->
    <div class="filter-section">
        <div class="filter-title">Ordenar por</div>
        <select class="sort-select" onchange="applySort(this.value)">
            <?php 
            $current_sort = $_GET['sort'] ?? 'recientes';
            $options = [
                'recientes' => 'Más recientes',
                'antiguos' => 'Más antiguos',
                'populares' => 'Más populares',
                'precio_asc' => 'Precio: Bajo a Alto',
                'precio_desc' => 'Precio: Alto a Bajo'
            ];
            foreach($options as $val => $label): 
            ?>
                <option value="<?php echo $val; ?>" <?php echo $current_sort == $val ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <script>
        function applySort(val) {
            // If on detail page, redirect to list page with sort
            const isDetailPage = window.location.pathname.match(/\/chollos\/[^\/]+$/) && !window.location.pathname.endsWith('/chollos');
            let url;
            
            if (isDetailPage) {
                url = new URL(window.location.origin + '/chollos');
            } else {
                url = new URL(window.location.href);
            }
            
            url.searchParams.set('sort', val);
            window.location.href = url.toString();
        }
        </script>
    </div>

    <!-- Filter: Category List (Main Categories) -->
    <div class="filter-section">
        <div class="filter-title">Categorías</div>
        <ul class="filter-list">
            <li>
                <a href="/chollos" class="filter-link <?php echo !isset($_GET['categoria']) ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Todas
                </a>
            </li>
            <?php 
            foreach ($categorias as $slug => $nom): 
                // Check if active (handle simple slug matching)
                $current_path = $_SERVER['REQUEST_URI'];
                $is_active = (isset($_GET['categoria']) && $_GET['categoria'] == $slug) || 
                             (strpos($current_path, "/chollos/$slug") !== false && strpos($current_path, "/chollos/$slug/") === false);
                $active_class = $is_active ? 'active' : '';
            ?>
                <li>
                    <a href="/chollos/<?php echo $slug; ?>" class="filter-link <?php echo $active_class; ?>">
                        <?php echo htmlspecialchars($nom); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Filter: Price -->
    <div class="filter-section">
        <div class="filter-title">Precio</div>
        <form id="priceFilterForm" onsubmit="applyPriceFilter(event)">
            <div class="price-inputs">
                <input type="number" id="minPrice" placeholder="Min €" class="price-input" min="0" 
                       value="<?php echo $_GET['min_price'] ?? ''; ?>">
                <span style="color: #999;">-</span>
                <input type="number" id="maxPrice" placeholder="Max €" class="price-input" min="0"
                       value="<?php echo $_GET['max_price'] ?? ''; ?>">
            </div>
            <button type="submit" class="price-btn">Aplicar precio</button>
            <!-- Clear filters link if active -->
            <?php if(isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
                <div style="text-align: center; margin-top: 8px;">
                    <a href="javascript:clearPriceFilter()" style="font-size: 0.85em; color: #999; text-decoration: underline;">Limpiar</a>
                </div>
            <?php endif; ?>
        </form>
        <script>
        function applyPriceFilter(e) {
            e.preventDefault();
            const min = document.getElementById('minPrice').value;
            const max = document.getElementById('maxPrice').value;
            
            const isDetailPage = window.location.pathname.match(/\/chollos\/[^\/]+$/) && !window.location.pathname.endsWith('/chollos');
            let url;
            
            if (isDetailPage) {
                url = new URL(window.location.origin + '/chollos');
            } else {
                url = new URL(window.location.href);
            }
            
            if(min) url.searchParams.set('min_price', min);
            else url.searchParams.delete('min_price');
            
            if(max) url.searchParams.set('max_price', max);
            else url.searchParams.delete('max_price');
            
            window.location.href = url.toString();
        }
        function clearPriceFilter() {
            const isDetailPage = window.location.pathname.match(/\/chollos\/[^\/]+$/) && !window.location.pathname.endsWith('/chollos');
            let url;
            
            if (isDetailPage) {
                url = new URL(window.location.origin + '/chollos');
            } else {
                url = new URL(window.location.href);
            }
            
            url.searchParams.delete('min_price');
            url.searchParams.delete('max_price');
            window.location.href = url.toString();
        }
        </script>
    </div>
    
    <!-- Filter: Discount -->
    <div class="filter-section">
        <div class="filter-title">Descuento</div>
        <ul class="filter-list">
            <?php 
            $discounts = [50, 70, 85, 90, 95];
            $current_discount = $_GET['min_discount'] ?? 0;
            foreach($discounts as $disc): 
                $active = ($current_discount == $disc) ? 'active' : '';
            ?>
            <li>
                <a href="javascript:applyDiscount(<?php echo $disc; ?>)" class="filter-link <?php echo $active; ?>">
                    Más de <?php echo $disc; ?>%
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if(isset($_GET['min_discount'])): ?>
            <div style="margin-top: 8px;">
                <a href="javascript:applyDiscount(null)" style="font-size: 0.85em; color: #999; text-decoration: underline;">Borrar filtro</a>
            </div>
        <?php endif; ?>
        <script>
        function applyDiscount(val) {
            const isDetailPage = window.location.pathname.match(/\/chollos\/[^\/]+$/) && !window.location.pathname.endsWith('/chollos');
            let url;
            
            if (isDetailPage) {
                url = new URL(window.location.origin + '/chollos');
            } else {
                url = new URL(window.location.href);
            }
            
            if(val) url.searchParams.set('min_discount', val);
            else url.searchParams.delete('min_discount');
            window.location.href = url.toString();
        }
        </script>
    </div>
    
     <!-- Filter: Brand Search -->
    <div class="filter-section">
        <div class="filter-title">Marca</div>
         <form onsubmit="applyBrandFilter(event)">
            <div style="position: relative;">
                 <input type="text" id="brandInput" placeholder="Buscar marca..." class="price-input" 
                        value="<?php echo htmlspecialchars($_GET['marca'] ?? ''); ?>">
                 <button type="submit" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #999; cursor: pointer;">
                     <i class="fas fa-search"></i>
                 </button>
            </div>
        </form>
        <script>
        function applyBrandFilter(e) {
            e.preventDefault();
            const brand = document.getElementById('brandInput').value;
            
            const isDetailPage = window.location.pathname.match(/\/chollos\/[^\/]+$/) && !window.location.pathname.endsWith('/chollos');
            let url;
            
            if (isDetailPage) {
                url = new URL(window.location.origin + '/chollos');
            } else {
                url = new URL(window.location.href);
            }
            
            if(brand) url.searchParams.set('marca', brand);
            else url.searchParams.delete('marca');
            window.location.href = url.toString();
        }
        </script>
    </div>
</aside>

<script>
function openMobileSidebar() {
    document.getElementById('filterSidebar').classList.add('open');
    document.getElementById('mobileOverlay').classList.add('open');
    document.body.style.overflow = 'hidden'; // Prevent scrolling
}

function closeMobileSidebar() {
    document.getElementById('filterSidebar').classList.remove('open');
    document.getElementById('mobileOverlay').classList.remove('open');
    document.body.style.overflow = ''; // Restore scrolling
}
</script>
