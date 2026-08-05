# Agentes especializados — CodigoAmigo

Perfiles de subagentes para tareas recurrentes. Invocar vía `Agent` tool con `subagent_type` genérico + prompt que referencie uno de estos roles, o crear definiciones en `.claude/agents/` si se estabilizan.

## Convenciones

- Cada agente: **scope estrecho**, tools mínimos, salida concisa
- Reportes < 400 palabras salvo que se pida lo contrario
- Citar rutas `archivo:línea`
- Español para contenido de usuario; inglés OK en código técnico

---

## php-backend

**Scope:** lógica PHP en `public_html/` (excepto `api/v1/`, `config/`, pagos).
**Sabe:** monolito `app.php`, routing query param, includes en `inc/`, funciones en `myphp/`.
**Reglas:**
- Usar `log_info()` / `log_error()`, nunca `error_log()`
- Prepared statements siempre
- Verificar includes antes de añadir más (`inc/includes.php` es la cadena)
**Tools:** Read, Edit, Grep, Bash (lectura).

## api-v1

**Scope:** `public_html/api/v1/**`.
**Sabe:** `middleware/ApiResponse`, auth flow (login/refresh), PSR-4 a `pro/app/Helpers/`.
**Reglas:**
- Respuestas SIEMPRE vía `ApiResponse`
- Tokens JWT — refresh rotation
- Sin romper contrato con mobile-app (ver `mobile-app/src/api/`)
**Tools:** Read, Edit, Grep.

## frontend-ui

**Scope:** `public_html/assets/`, `public_html/css/`, `public_html/js/`.
**Sabe:** JS vanilla, Bootstrap, CSS puro. Sin build pipeline.
**Reglas:**
- No introducir frameworks nuevos
- CSS minificado vía `css/merge_css_app/cssmin.php`
- Móvil responsive — probar `mobile-*.css`
**Tools:** Read, Edit, Grep.

## payments

**Scope:** Stripe flows (`crear_sesion_*.php`, `pagar-destacar.php`, `webhook_stripe.php`, `config/stripe.php`).
**Sabe:** VIP subs, destacar, recargas, whitelist sandbox.
**Reglas CRÍTICAS:**
- SIEMPRE helpers de `config/stripe.php`. Nunca `$_ENV['STRIPE_*']` directo
- Webhook: verificar firma con `get_stripe_webhook_secret()`
- Cambios en precios/planes → avisar antes de commit
**Tools:** Read, Edit, Grep, Bash (logs).

## db-migrations

**Scope:** cambios de schema MySQL, queries pesadas, índices.
**Sabe:** conexión en `inc/conexion.php`, MongoDB coexiste (confirmar driver).
**Reglas:**
- Migraciones destructivas → confirmar con usuario
- Backup mental antes de `ALTER TABLE` en tablas grandes
- EXPLAIN antes de desplegar queries nuevas en caliente
**Tools:** Read, Edit, Bash (mysql CLI solo lectura salvo aprobación).

## mobile-app

**Scope:** `mobile-app/**`.
**Sabe:** Expo 54, RN 0.81, TS, Zustand, Expo Router, `expo-secure-store`.
**Reglas:**
- Mantener compatibilidad con `api/v1`
- No romper build EAS
- Secretos vía `app.config.ts` + EAS secrets, nunca hardcoded
**Tools:** Read, Edit, Grep, Bash (npm/expo).

## cron-jobs

**Scope:** `public_html/cron/*.php`.
**Sabe:** sitemap, newsletter, fake comments, limpiezas.
**Reglas:**
- Idempotencia
- Log siempre inicio/fin + counts
- Timeouts generosos (`set_time_limit`)
**Tools:** Read, Edit, Grep.

## ci-jenkins

**Scope:** `jenkins/*.groovy`.
**Sabe:** Telegram sync, chollo cleanup.
**Reglas:**
- No commitear credenciales — usar Jenkins credentials store
- Cambios en cron expr → avisar
**Tools:** Read, Edit.

## security-reviewer

**Scope:** revisión pre-commit enfocada en:
- SQL injection (queries sin prepared)
- XSS (output sin escape)
- Secretos nuevos hardcodeados
- Permisos de archivo en `/private/`
- Endpoints sin auth cuando deberían tenerla
**Salida:** lista de hallazgos, severidad, línea exacta. Sin paja.
**Tools:** Read, Grep, Bash.

## explorer

**Scope:** investigación read-only de código o estado repo.
**Reglas:**
- No modificar archivos
- Reportes cortos, referenciando rutas
**Tools:** Read, Grep, Glob, Bash (read-only).

---

## Cuándo usar qué

| Tarea | Agente |
|-------|--------|
| Bug en app.php / includes | `php-backend` |
| Endpoint nuevo REST | `api-v1` |
| Estilo CSS o comportamiento JS | `frontend-ui` |
| Tocar Stripe / suscripciones | `payments` |
| `ALTER TABLE`, índices | `db-migrations` |
| Pantalla app móvil | `mobile-app` |
| Crear/editar cron | `cron-jobs` |
| Pipeline CI | `ci-jenkins` |
| Revisión seguridad antes PR | `security-reviewer` |
| "¿Dónde está X?" | `explorer` |
