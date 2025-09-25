# Diseño Moderno - CodigoAmigo.com

## Descripción
Nuevo diseño moderno para CodigoAmigo.com basado en el mockup proporcionado, con un esquema de colores gris oscuro y naranja.

## Características Principales

### 🎨 Diseño Visual
- **Esquema de colores**: Gris oscuro (#2C2C2C) y naranja (#FF6B35)
- **Tipografía**: Segoe UI, Tahoma, Geneva, Verdana, sans-serif
- **Estilo**: Moderno, limpio y profesional
- **Responsive**: Adaptable a todos los dispositivos

### 🏗️ Estructura
- **Header**: Logo, navegación y búsqueda
- **Hero Section**: Título principal y búsqueda central
- **Contenido Principal**: Grid de códigos con tarjetas modernas
- **Footer**: Feed de actividad y enlaces de recompensas

### 📱 Páginas Implementadas
1. **Página Principal** (`/`) - Lista de códigos con diseño moderno
2. **Categorías** (`/public/categorias_modern.php`) - Grid de categorías
3. **Tiendas** (`/public/tiendas_modern.php`) - Lista de tiendas
4. **Demostración** (`/public/demo_modern.php`) - Showcase del diseño

## Archivos Creados/Modificados

### Nuevos Archivos
- `css/modern-design.css` - Estilos principales del diseño moderno
- `myphp/_header_modern.php` - Header con nuevo diseño
- `myphp/funciones_modern.php` - Funciones para generar contenido moderno
- `public/categorias_modern.php` - Página de categorías
- `public/tiendas_modern.php` - Página de tiendas
- `public/demo_modern.php` - Página de demostración

### Archivos Modificados
- `app_with_mongo.php` - Actualizado para usar el nuevo diseño

## Componentes del Diseño

### Header Moderno
- Logo "codigo amigo" con colores diferenciados
- Tagline "códigos verificados, gente real"
- Navegación horizontal con enlaces activos
- Búsqueda con icono y placeholder
- Botón "Acceder" con icono de usuario

### Hero Section
- Título principal en naranja
- Descripción del servicio
- Búsqueda central con botón de acción
- Fondo con gradiente sutil

### Tarjetas de Códigos
- Diseño de tarjeta con sombras
- Información de marca, descripción y estadísticas
- Botón de acción con icono
- Efectos hover para interactividad

### Paginación
- Información de resultados
- Controles de navegación
- Botones con iconos
- Diseño responsive

### Categorías
- Grid de tarjetas con iconos
- Información de cantidad de códigos
- Efectos hover
- Iconos representativos por categoría

### Tiendas
- Lista de tiendas con estadísticas
- Información de descuentos máximos
- Botones de acción
- Diseño de tarjeta moderno

## Tecnologías Utilizadas

### Frontend
- **CSS3**: Variables CSS, Grid, Flexbox, Animaciones
- **JavaScript**: jQuery para interactividad
- **Font Awesome**: Iconos vectoriales
- **Responsive Design**: Media queries para móviles

### Backend
- **PHP**: Funciones para generar contenido dinámico
- **MongoDB**: Base de datos para códigos
- **Slim Framework**: Framework PHP para rutas

## Características Responsive

### Desktop (>768px)
- Grid de 3-4 columnas para códigos
- Navegación horizontal completa
- Hero section con búsqueda central

### Tablet (768px)
- Grid de 2 columnas
- Navegación adaptada
- Elementos reorganizados

### Móvil (<768px)
- Grid de 1 columna
- Navegación vertical
- Botones de tamaño táctil
- Texto optimizado

## Personalización

### Colores
Los colores se pueden modificar en las variables CSS:
```css
:root {
    --primary-orange: #FF6B35;
    --dark-gray: #2C2C2C;
    --light-gray: #404040;
    --text-white: #FFFFFF;
    --text-gray: #CCCCCC;
}
```

### Iconos
Los iconos se pueden cambiar en las funciones de categorías:
```php
function get_category_icon($category_name) {
    $icons = [
        'moda' => 'tshirt',
        'tecnología' => 'laptop',
        // ... más iconos
    ];
}
```

## Próximas Mejoras

1. **Búsqueda en tiempo real** con AJAX
2. **Filtros avanzados** por categoría y tienda
3. **Sistema de favoritos** para códigos
4. **Notificaciones** de nuevos códigos
5. **Modo oscuro/claro** toggle
6. **Animaciones** más avanzadas
7. **PWA** (Progressive Web App)

## Uso

Para usar el nuevo diseño, simplemente accede a:
- Página principal: `http://localhost:8000/`
- Categorías: `http://localhost:8000/public/categorias_modern.php`
- Tiendas: `http://localhost:8000/public/tiendas_modern.php`
- Demostración: `http://localhost:8000/public/demo_modern.php`

El diseño se aplica automáticamente a todas las páginas que usen `_header_modern.php`.
