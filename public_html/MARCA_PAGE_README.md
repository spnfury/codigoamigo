# Página de Marca - Código Amigo

## Descripción
Página individual para cada marca que muestra todos los códigos de descuento disponibles, siguiendo el diseño exacto de la imagen proporcionada.

## Características

### Diseño
- **Esquema de colores**: Gris oscuro (#2C2C2C), naranja (#FF6B35), verde (#28a745)
- **Layout**: Diseño de dos columnas con contenido principal y sidebar
- **Responsive**: Completamente adaptable a dispositivos móviles
- **Animaciones**: Efectos suaves de hover y transiciones

### Funcionalidades
- **Header de marca**: Logo, nombre, estadísticas y descripción
- **Grid de códigos**: Tarjetas con información detallada de cada código
- **Sistema de votación**: Botones para votar positivamente o negativamente
- **Sidebar con widgets**:
  - Widget de anuncio (hamburguesa de Salamanca)
  - Estadísticas de la marca
  - Marcas relacionadas por categoría
- **SEO optimizado**: Meta tags completos para redes sociales

## Archivos creados

### PHP
- `marca.php` - Página principal de marca
- `test_marca.php` - Script para crear datos de prueba

### CSS
- `css/brand-page-new.css` - Estilos específicos para la página de marca

### JavaScript
- `assets/js/brand-page.js` - Funcionalidad interactiva (ya existía)

### Imágenes
- `img/burger-promo.jpg` - Imagen del widget de anuncio

## Uso

### Acceso a la página
```
/marca.php?marca=nombre-de-la-marca
/marca.php?id=objectid-de-mongodb
```

### Crear datos de prueba
1. Ejecutar `/test_marca.php` en el navegador
2. Esto creará:
   - Usuario de prueba: Sergio De La Rosa
   - Marca de prueba: Airbnb
   - 3 códigos de descuento de ejemplo
3. Acceder a `/marca.php?marca=airbnb` para ver el resultado

## Estructura de datos MongoDB

### Colección: marcas
```json
{
  "_id": ObjectId,
  "nombre": "Airbnb",
  "nombre_clave": "airbnb",
  "categoria": "VIAJES Y ALOJAMIENTO",
  "categoria_clave": "viajes-y-alojamiento",
  "imagen": "/img/airbnb-logo.png",
  "descripcion": "Descripción corta",
  "descripcion_larga": "Descripción completa",
  "estado": 1,
  "fecha_publicacion": "25-01-2024 10:30",
  "usuario_creador": "ObjectId",
  "url": "https://airbnb.com",
  "url_register": "https://airbnb.com/signup",
  "aviso": "Marca creada por administrador"
}
```

### Colección: codigos
```json
{
  "_id": ObjectId,
  "codigo": "AIRBNB20",
  "titulo": "20% de descuento en tu primera reserva",
  "descripcion": "Descripción del código",
  "beneficio": 50,
  "destacado": true,
  "marca_id": "ObjectId de la marca",
  "usuario_creador": "ObjectId del usuario",
  "fecha_publicacion": "2024-01-25 10:30:00",
  "estado": 1,
  "tipo": "descuento_porcentaje"
}
```

## Características técnicas

### Responsive Design
- **Desktop**: Grid de 3-4 columnas
- **Tablet**: Grid de 2 columnas
- **Mobile**: Grid de 1 columna, sidebar abajo

### Optimizaciones
- **Lazy loading** de imágenes
- **Preload** de recursos críticos
- **Animaciones CSS** optimizadas
- **SEO friendly** con meta tags completos

### Accesibilidad
- **Contraste** mejorado para legibilidad
- **Estados de enfoque** claramente definidos
- **Navegación por teclado** funcional
- **Alt texts** en todas las imágenes

## Personalización

### Colores
Los colores se pueden modificar en `:root` del archivo CSS:
```css
:root {
    --primary-orange: #FF6B35;
    --dark-gray: #2C2C2C;
    --light-gray: #404040;
    --success-green: #28a745;
}
```

### Widgets del sidebar
Los widgets se pueden personalizar editando las secciones correspondientes en `marca.php`:
- Widget de anuncio
- Widget de estadísticas
- Widget de marcas relacionadas

## Integración

### Con el sistema existente
- Usa la misma conexión MongoDB que el resto del sitio
- Compatible con el sistema de autenticación existente
- Integra con el sistema de votación actual
- Utiliza las funciones auxiliares existentes

### Analytics
- Tracking de visualización de página
- Tracking de interacciones con botones
- Compatible con Google Analytics 4

## Próximos pasos

1. **Integrar con sistema de votación real** (actualmente usa datos aleatorios)
2. **Implementar sistema de favoritos**
3. **Añadir filtros y ordenación** de códigos
4. **Integrar con sistema de comentarios**
5. **Añadir más widgets al sidebar**

## Soporte

Para cualquier problema o duda sobre la implementación, revisar:
1. Los logs de PHP para errores de base de datos
2. La consola del navegador para errores de JavaScript
3. Los datos de prueba creados con `test_marca.php`
