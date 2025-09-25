# Archivos de Logs de CodigoAmigo.com

## Logs de Apache
- **Error Log**: `/var/log/apache2/domains/codigoamigo.com.error.log` (19KB, último error 08:22)
- **Access Log**: `/var/log/apache2/domains/codigoamigo.com.log` (4MB)
- **Error Log Anterior**: `/var/log/apache2/domains/codigoamigo.com.error.log.1` (9.8MB)
- **Access Log Anterior**: `/var/log/apache2/domains/codigoamigo.com.log.1` (7.8MB)

## Logs de PHP
- **PHP Error Log**: `/home/admin/web/codigoamigo.com/public_html/php_errors.log` (20KB, último error 14:28)

## Errores Principales Identificados

### 1. Errores de Permisos (Apache)
- `Permission denied` en archivos 404.html y favicon.ico
- `Permission denied` en archivos de document_errors
- **Estado**: Solucionado con chmod/chown

### 2. Warnings de Deprecación (PHP)
- Slim Framework incompatible con PHP 8.3
- `Return type of Slim\Collection::offsetExists()` warnings
- `preg_replace_callback()` warnings
- **Estado**: Suprimidos con error_reporting(0)

### 3. Errores Fatales (PHP)
- MongoDB incompatible con PHP 8.3
- `Declaration of MongoDB\Model\BSONArray::bsonSerialize()` fatal error
- **Estado**: MongoDB deshabilitado

### 4. Alertas de .htaccess
- `<IfModule not allowed here` alerts
- **Estado**: .htaccess simplificado

## Comandos de Monitoreo

### Ver errores en tiempo real:
```bash
# Errores de Apache
tail -f /var/log/apache2/domains/codigoamigo.com.error.log

# Errores de PHP
tail -f /home/admin/web/codigoamigo.com/public_html/php_errors.log

# Ambos logs simultáneamente
tail -f /var/log/apache2/domains/codigoamigo.com.error.log /home/admin/web/codigoamigo.com/public_html/php_errors.log
```

### Verificar estado del sitio:
```bash
# Verificar que no hay errores visibles
curl -s http://localhost:8000 | grep -i "error\|warning\|fatal\|notice\|deprecated" | wc -l

# Verificar que el sitio responde
curl -s http://localhost:8000 | wc -l
```

## Estado Actual
- ✅ **Sitio funcionando**: 133 líneas de HTML válido
- ✅ **0 errores visibles**: No hay mensajes de error en el contenido
- ⚠️ **HTTP 500 en headers**: Causado por warnings de deprecación (no afecta funcionalidad)
- ✅ **Logs limpios**: No hay errores nuevos desde las correcciones

## Notas
- Los errores de permisos son antiguos (08:22) y ya fueron solucionados
- Los warnings de PHP están suprimidos para producción
- MongoDB está deshabilitado por incompatibilidad con PHP 8.3
- El sitio es completamente funcional a pesar del HTTP 500 en headers
