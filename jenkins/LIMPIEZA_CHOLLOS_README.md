# Pipeline de Jenkins para Limpieza de Chollos con #Publicidad

Este pipeline ejecuta el script PHP `limpiar_chollos_publicidad.php` para limpiar chollos antiguos que contienen etiquetas de publicidad (#Publicidad) en el título o descripción.

## Configuración en Jenkins

### 1. Crear un nuevo Pipeline Job

1. En Jenkins, crear un nuevo item de tipo "Pipeline"
2. Nombre sugerido: `limpiar-chollos-publicidad`

### 2. Configurar el Pipeline

**IMPORTANTE**: Para evitar problemas con checkouts locales de Git, usa la opción "Pipeline script" directamente:

#### Opción A: Pipeline Script Directo (Recomendado)

1. En la configuración del job, selecciona:
   - **Definition**: `Pipeline script`
   - **Script**: Copia y pega el contenido completo del archivo `jenkins/limpiar-chollos-job.groovy`

2. **NO** uses "Pipeline script from SCM" con rutas locales (`file://`), ya que Jenkins no permite checkouts locales por seguridad.

#### Opción B: Usar SCM con Repositorio Remoto

Si tu código está en un repositorio Git remoto (GitHub, GitLab, etc.):

1. **Definition**: `Pipeline script from SCM`
2. **SCM**: Git
3. **Repository URL**: URL remota de tu repositorio (ej: `https://github.com/usuario/repo.git`)
4. **Script Path**: `jenkins/limpiar-chollos-job.groovy`
5. **Credentials**: Si el repositorio es privado, agrega las credenciales necesarias

### 3. Configurar ejecución periódica

El pipeline ya está configurado para ejecutarse cada hora automáticamente mediante el trigger cron:

```groovy
triggers {
    cron('H * * * *') // Ejecutar cada hora
}
```

El `H` significa que Jenkins distribuirá la ejecución aleatoriamente dentro de la hora para evitar que todos los jobs se ejecuten al mismo tiempo.

Si prefieres configurar la frecuencia manualmente desde la interfaz de Jenkins:

1. En "Build Triggers", desmarcar "Build periodically" si está marcado
2. O configurar una frecuencia diferente:
   - `H * * * *` (cada hora) - **Recomendado**
   - `H */2 * * *` (cada 2 horas)
   - `H */6 * * *` (cada 6 horas)
   - `H 0 * * *` (una vez al día a medianoche)

**Nota**: Si configuras la frecuencia manualmente desde Jenkins, puedes comentar o eliminar la sección `triggers` del archivo `.groovy`.

### 4. Requisitos del servidor

- PHP CLI instalado y en el PATH
- Acceso de lectura/escritura al directorio `public_html/scripts`
- Acceso de lectura/escritura a la base de datos MongoDB
- Archivos de funciones PHP disponibles:
  - `myphp/funciones.php`
  - `myphp/funciones_chollos.php`
  - `myphp/funciones_chollos_groq.php`

## Uso manual

También puedes ejecutar el pipeline manualmente desde Jenkins:

1. Ir al job `limpiar-chollos-publicidad`
2. Click en "Build Now"
3. El script se ejecutará inmediatamente

O ejecutar el script directamente desde la línea de comandos:

```bash
cd /home/admin/web/codigoamigo.com/public_html/scripts
php limpiar_chollos_publicidad.php
```

## Qué hace el script

El script `limpiar_chollos_publicidad.php` realiza las siguientes acciones:

1. **Busca chollos** que contengan:
   - `#Publicidad` en el título o descripción
   - Enlaces markdown truncados o mal formateados
   - Referencias a enlaces después de "Enlace:", "Link:", etc.

2. **Limpia el contenido**:
   - Elimina etiquetas `#Publicidad` del título y descripción
   - Elimina menciones `@usuario`
   - Elimina etiquetas `#canal`
   - Elimina líneas que empiezan con "Enlace:", "Link:", "URL:", etc.
   - Elimina enlaces markdown truncados o mal formateados
   - Normaliza espacios en blanco

3. **Actualiza la base de datos**:
   - Solo actualiza los chollos que tuvieron cambios
   - Mantiene el resto de campos intactos

## Logs

Los logs se muestran en:
- **Jenkins**: Ver "Console Output" del build para ver el resultado completo
- El script muestra información detallada en la consola:
  - Chollos encontrados
  - Chollos actualizados
  - Chollos sin cambios
  - Errores si los hay

## Solución de problemas

### Error: "No se pudo conectar a MongoDB"
- Verificar que MongoDB está corriendo
- Verificar la configuración de conexión en `myphp/funciones.php`
- Verificar que el usuario de Jenkins tiene permisos para acceder a MongoDB

### Error: "PHP no está instalado"
- Instalar PHP CLI en el servidor Jenkins
- Verificar que está en el PATH: `which php`
- Verificar la versión: `php -v` (debe ser PHP 7.4 o superior)

### Error: "No se encontraron archivos de funciones"
- Verificar que las rutas en `require_once` del script son correctas
- Verificar que los archivos existen en `myphp/`
- Verificar que el workspace de Jenkins apunta al directorio correcto

### El script no encuentra chollos para limpiar
- Esto es normal si no hay chollos con `#Publicidad`
- El script mostrará: "✅ No hay chollos para limpiar"
- No es un error, simplemente no hay trabajo que hacer

## Ejemplo de salida

```
🔍 Buscando chollos con #Publicidad o enlaces markdown...

📊 Encontrados 15 chollos con #Publicidad

✅ Actualizado: 507f1f77bcf86cd799439011
   Título: 'Producto #Publicidad' → 'Producto'
   Descripción limpiada

✅ Actualizado: 507f1f77bcf86cd799439012
   Título: 'Oferta Especial' → 'Oferta Especial'
   Descripción limpiada

...

📊 Resumen:
   Total encontrados: 15
   Actualizados: 12
   Errores: 0
   Sin cambios: 3

✅ Proceso completado
```





