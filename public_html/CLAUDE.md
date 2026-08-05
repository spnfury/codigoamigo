# public_html — webapp monolítico

Raíz servida por Apache. Entry `app.php`.

## Arquitectura

- Routing por query param: `/?page=nombre` → `app.php` resuelve
- Includes encadenados desde `inc/includes.php` → `inc/conexion.php`, `inc/funciones.php`, `inc/sentry_bootstrap.php`, etc.
- Sesiones PHP nativas
- Output HTML directo (no templating formal)

## Zonas

- `api/v1/` — REST moderna (ver `api/CLAUDE.md` si existe)
- `ajax/` — endpoints AJAX legacy (respuesta JSON simple)
- `config/` — configs globales
- `cron/` — tareas programadas
- `css/`, `js/`, `assets/` — estáticos
- `inc/` — includes core
- `myphp/` — funciones de negocio (`funciones_codigo.php`, `funciones_marca.php`, ...)
- `logs/` — logs escritos por `inc/logger.php`
- `admin_*.php` — panel admin
- `debug_*.php`, `test_*.php`, `verify_*.php` — scripts de debugging (heredados, no crear nuevos)

## Patrones obligatorios

- **SQL:** `mysqli` con prepared statements. Conexión vía `$conn` de `inc/conexion.php`
- **Log:** `log_info()`, `log_error()`, nunca `error_log()`
- **Escape output:** `htmlspecialchars($x, ENT_QUOTES, 'UTF-8')`
- **Sesión:** comprobar `session_status()` antes de `session_start()` si el flujo es raro
- **reCAPTCHA:** configurado en `config/app.php`. Ver `debug_recaptcha.php` como referencia

## Gotchas

- `app.php` mezcla routing + bootstrap + lógica → editar por bloques, no reescribir
- Varios `*.php` en raíz hacen `include 'inc/includes.php'` primero — mantener patrón
- Cambios en `.htaccess` afectan todo — probar con cuidado
- `config/app_simple.php` es versión reducida para contextos ligeros (AJAX, cron) — no confundir con `config/app.php`
