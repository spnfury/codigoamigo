# Sistema de FAQs para Marcas - CodigoAmigo

## Descripción
Sistema completo para gestionar preguntas frecuentes (FAQs) de marcas con formato desplegable estilo Google, generación automática con IA y administración completa.

## Características

### ✅ Funcionalidades Implementadas

1. **Base de Datos**
   - Colección MongoDB `marcas_faqs`
   - Campos: marca_clave, titulo, respuesta, orden, activa, fecha_creacion, fecha_actualizacion, generada_ia

2. **Panel de Administración**
   - Gestión completa de FAQs por marca
   - Crear, editar, eliminar, activar/desactivar
   - Estadísticas por marca
   - Reordenamiento

3. **Generación con IA**
   - Soporte para Perplexity AI y Groq AI
   - Generación automática de 8-10 FAQs por marca
   - Configuración flexible de prompts

4. **Importación Masiva**
   - Formato copiar-pegar simple
   - Detección automática de preguntas y respuestas
   - Procesamiento inteligente de texto

5. **Frontend**
   - Acordeón estilo Google con animaciones suaves
   - Diseño responsive
   - Integración automática en páginas de marca

6. **SEO**
   - Schema JSON-LD para FAQs
   - Microformatos optimizados para buscadores

## Archivos Creados

### Backend
- `/myphp/funciones_faq.php` - Funciones principales de gestión
- `/myphp/funciones_faq_frontend.php` - Funciones para mostrar FAQs
- `/config/ai_config.php` - Configuración de APIs de IA

### Frontend
- `/public/admin_faqs.php` - Panel de administración
- `/public/ejemplo_importar_faqs.php` - Ejemplo de importación
- Integración en `/public/marca.php`

## Configuración

### 1. APIs de IA
Edita `/config/ai_config.php`:

```php
// Perplexity AI
define('PERPLEXITY_API_KEY', 'pplx-tu-api-key-aqui');

// Groq AI  
define('GROQ_API_KEY', 'gsk-tu-api-key-aqui');
```

### 2. Base de Datos
El sistema crea automáticamente la colección `marcas_faqs` en MongoDB.

## Uso

### Panel de Administración
1. Accede a `/public/admin_faqs.php`
2. Selecciona una marca
3. Gestiona las FAQs existentes o crea nuevas

### Generación con IA
1. En el panel de administración
2. Selecciona la marca
3. Elige el proveedor de IA (Perplexity o Groq)
4. Haz clic en "Generar con IA"

### Importación Masiva
1. Ve a `/public/ejemplo_importar_faqs.php`
2. Selecciona la marca
3. Pega el texto en formato:
   ```
   ¿Pregunta 1?
   Respuesta a la pregunta 1
   
   ¿Pregunta 2?
   Respuesta a la pregunta 2
   ```
4. Haz clic en "Importar FAQs"

### Formato de Importación
```
¿Qué es esta marca?
Esta marca es una plataforma que ofrece...

¿Cómo me registro?
Para registrarte, ve a la página web...

¿Dónde encuentro códigos de descuento?
Los códigos están disponibles en...
```

## Funciones Principales

### Backend
- `getFAQsByMarca($marca_clave, $activas_solo)` - Obtener FAQs de una marca
- `crearFAQ($marca_clave, $titulo, $respuesta, $orden)` - Crear nueva FAQ
- `actualizarFAQ($faq_id, $titulo, $respuesta, $orden, $activa)` - Actualizar FAQ
- `eliminarFAQ($faq_id)` - Eliminar FAQ
- `toggleFAQActiva($faq_id)` - Activar/desactivar FAQ
- `importarFAQsBulk($marca_clave, $texto_faqs)` - Importación masiva
- `generarFAQsConIA($marca_clave, $marca_nombre, $api_provider)` - Generación con IA

### Frontend
- `mostrarFAQsMarca($marca_clave, $titulo_seccion)` - Mostrar FAQs en página
- `generarSchemaFAQs($marca_clave, $marca_nombre)` - Generar schema SEO
- `incluirFAQsEnMarca($marca_clave, $marca_nombre)` - Función helper

## Estructura de la Base de Datos

### Colección: marcas_faqs
```javascript
{
  _id: ObjectId,
  marca_clave: String,        // Clave de la marca (ej: "amazon")
  titulo: String,             // Pregunta
  respuesta: String,          // Respuesta
  orden: Number,              // Orden de visualización
  activa: Boolean,            // Si está activa
  generada_ia: Boolean,       // Si fue generada por IA
  fecha_creacion: Date,       // Fecha de creación
  fecha_actualizacion: Date   // Fecha de última actualización
}
```

## Personalización

### Estilos CSS
Los estilos están incluidos en `mostrarFAQsMarca()` y se pueden personalizar modificando la función.

### Prompts de IA
Edita `FAQ_PROMPT_TEMPLATE` en `/config/ai_config.php` para personalizar los prompts.

### Comportamiento del Acordeón
Por defecto, múltiples FAQs pueden estar abiertas simultáneamente. Para cambiar a comportamiento tipo Google (solo una abierta), descomenta las líneas en el JavaScript de `mostrarFAQsMarca()`.

## Seguridad

- Verificación de permisos de administrador
- Sanitización de entrada HTML
- Validación de datos
- Timeout en llamadas a APIs externas

## Rendimiento

- FAQs se cargan solo cuando existen
- Schema SEO se genera dinámicamente
- Caché de marcas implementado
- Timeout configurable para APIs

## Mantenimiento

### Logs
Los errores de IA se registran en el log de PHP:
```bash
tail -f /var/log/php_errors.log | grep "Error.*IA"
```

### Limpieza
Para limpiar FAQs inactivas:
```php
$collection_faqs = getCollectionFAQs();
$collection_faqs->deleteMany(['activa' => false]);
```

## Troubleshooting

### FAQs no aparecen
1. Verificar que la marca existe
2. Verificar que las FAQs están activas
3. Revisar logs de errores

### IA no genera FAQs
1. Verificar API keys en `/config/ai_config.php`
2. Revisar logs de errores
3. Verificar conectividad a APIs externas

### Importación falla
1. Verificar formato del texto
2. Asegurar que las preguntas terminan con "?"
3. Revisar permisos de base de datos

## Próximas Mejoras

- [ ] Categorización de FAQs
- [ ] Búsqueda en FAQs
- [ ] Analytics de FAQs más visitadas
- [ ] Exportación de FAQs
- [ ] Integración con más proveedores de IA
- [ ] FAQs por idioma
- [ ] Moderación automática de contenido

## Soporte

Para soporte técnico, contacta al equipo de desarrollo o revisa los logs del sistema.
