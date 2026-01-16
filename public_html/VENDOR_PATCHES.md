# Parches aplicados a archivos de vendor

## Slim Framework 3.12.5 - Request.php

### Problema
Error de PHP Deprecated en PHP 8.1+:
```
explode(): Passing null to parameter #2 ($string) of type string is deprecated
```

### Archivo modificado
`vendor/slim/slim/Slim/Http/Request.php`

### Cambios aplicados

#### 1. Línea 1019 - Función getParsedBody()
**Antes:**
```php
$parts = explode('+', $mediaType);
```

**Después:**
```php
if ($mediaType !== null) {
    $parts = explode('+', $mediaType);
    if (count($parts) >= 2) {
        $mediaType = 'application/' . $parts[count($parts)-1];
    }
}
```

#### 2. Línea 651 - Función getMediaTypeParams()
**Antes:**
```php
$paramParts = explode('=', $contentTypeParts[$i]);
```

**Después:**
```php
if ($contentTypeParts[$i] !== null) {
    $paramParts = explode('=', $contentTypeParts[$i]);
    $contentTypeParams[strtolower($paramParts[0])] = $paramParts[1];
}
```

### Razón del parche
- PHP 8.1+ no permite pasar `null` a funciones que esperan `string`
- Slim Framework 3.12.5 no es compatible con PHP 8.1+ sin este parche
- Este parche mantiene la funcionalidad original pero evita los errores de deprecación

### Nota importante
Este parche se perderá si se actualiza Slim Framework via Composer. 
Se recomienda actualizar a Slim Framework 4.x cuando sea posible para compatibilidad completa con PHP 8.1+.

### Fecha de aplicación
26 de septiembre de 2025
