# api/ — endpoints HTTP

Dos generaciones conviven:

- `api/v1/` — REST moderna, usada por `mobile-app/`
- `api/*.php` y `ajax/*.php` — endpoints legacy para webapp

## api/v1 — convenciones

### Estructura

- `api/v1/auth/` — login, refresh, logout
- `api/v1/codes/` — códigos / chollos
- `api/v1/user/` — perfil, acciones usuario
- `api/v1/middleware/` — `ApiResponse`, auth checks, rate limit

### Contrato respuesta

SIEMPRE vía clase `ApiResponse` del middleware. Ejemplo:

```php
ApiResponse::success($data, $message = null);
ApiResponse::error($message, $code = 400, $details = null);
```

Formato JSON uniforme:
```json
{ "success": true|false, "data": ..., "message": "...", "errors": ... }
```

### Auth

- JWT bearer en header `Authorization: Bearer <token>`
- Access token corto, refresh token rotado en `auth/refresh.php`
- Mobile guarda tokens en `expo-secure-store`

### Reglas

- Validar input, responder 400 con detalle
- Log con `log_info()` en entry point, `log_error()` en fallos
- CORS: configurado a nivel servidor, no duplicar en cada endpoint
- Romper contrato = romper `mobile-app`. Coordinar versionado si cambia shape

## api/ legacy (no v1)

Endpoints sueltos (`chat_api.php`, `notificaciones.php`, ...) — respuesta JSON ad-hoc.
No mover a v1 sin pedirlo; respetar estilo existente al editar.

## ajax/

Endpoints AJAX para webapp (no API pública). Respuesta JSON o HTML parcial.
Autenticados por sesión PHP, no JWT.
