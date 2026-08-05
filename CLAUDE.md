# CodigoAmigo — Contexto raíz

Sitio español de cupones/chollos. Monolito PHP + app móvil React Native.

## Stack

- **Backend:** PHP 8.3, MySQL (principal) + MongoDB (driver presente, uso parcial)
- **Frontend:** JS vanilla + Bootstrap. Sin build tool
- **Móvil:** `mobile-app/` — Expo 54 / React Native 0.81 / TypeScript / Zustand / Expo Router
- **Pagos:** Stripe 10.x
- **Observabilidad:** Sentry + logger propio (`inc/logger.php`)
- **CI:** Jenkins (`jenkins/*.groovy`)
- **Composer:** Slim 3 declarado pero no usado activamente

## Layout

- `public_html/` — webapp monolítico. Entry point `app.php` (~104KB)
- `public_html/api/v1/` — REST moderna (`auth/`, `codes/`, `user/`, `middleware/`)
- `public_html/config/` — configs (`app.php`, `stripe.php`, `sentry.php`, `ai_config.php`)
- `public_html/myphp/` — lógica de negocio (`funciones*.php`, integraciones Brevo)
- `public_html/inc/` — includes base (`logger.php`, `conexion.php`, `funciones.php`)
- `public_html/cron/` — tareas programadas (sitemap, newsletter, fake comments)
- `public_html/logs/` — logs por nivel y fecha
- `mobile-app/` — app Expo, standalone (no submódulo)
- `jenkins/` — pipelines CI
- `private/` — secretos fuera de webroot (`stripe_secrets.php`, chmod 600)

## Routing

Flat query params: `/?page=...`. No framework. `app.php` enruta.

## Config y secretos

- **Stripe:** usar SIEMPRE helpers de `public_html/config/stripe.php`:
  - `get_stripe_secret_key($email, $user_id)` — enruta admins sandbox a clave test
  - `get_stripe_live_secret_key()` — siempre live
  - `get_stripe_webhook_secret()` — webhook signing
  - Sandbox whitelist: `thevega82@gmail.com` + 3 user IDs
  - Keys en `$_ENV` o `/private/stripe_secrets.php`
- **Sentry DSN:** hardcoded en `config/sentry.php` (pendiente mover a env)
- **APIs IA/externas (Groq, Perplexity, YouTube, Amazon, Telegram):** hardcoded en `ai_config.php` (deuda técnica; rotar a env cuando toque)
- **No hay `.env`.** Variables vienen de FPM pool + archivos privados

## Logging

- Logger: `public_html/inc/logger.php` — clase `Logger` + funciones globales
- Funciones: `log_info()`, `log_error()`, `log_critical()`, etc.
- **Nunca usar `error_log()` directamente** — usar `log_info()` / `log_error()`
- Salida: `public_html/logs/{level}/YYYY-MM-DD.log` + combinado
- `log_critical()` manda a Telegram (chat `-563343505`)

## Idioma

- Código, comentarios, nombres de variables, UI: **español**
- Vocabulario: `chollos` (deals), `marcas` (brands), `códigos` (coupons), `destacar` (feature), `VIP` (suscripción)
- Excepción: vendor y código nuevo puede ser inglés

## Comandos comunes

- No hay `composer test`, `npm run test`, ni Makefile global
- Tests ad-hoc
- Mobile: `cd mobile-app && npm start` (Expo)
- Deploy: Jenkins pipelines
- Cron: `public_html/cron/*.php` vía crontab servidor

## Reglas clave

- **No tocar `/home/casinuevo_user`** — otro sitio en servidor compartido
- **Nunca hardcodear secretos nuevos.** Env o `/private/`
- **Commits Stripe-related:** pasar por helpers, nunca leer claves directo
- **Debug files en raíz (`debug_*.php`, `test_*.php`, `verify_*.php`):** heredados, no crear más. Si se crea uno temporal, borrar al terminar
- **Fragmentos SQL:** parametrizar siempre (prepared statements). Nada de concatenación
- **Idioma en commits:** libre (inglés o español, coherente por PR)

## Gotchas

- `app.php` y `config/app.php` son gigantes — leer por tramos, no entero
- Slim 3 en `composer.json` pero código no lo usa — ignorar
- Mezcla MongoDB + MySQL — confirmar antes de elegir driver
- Handler de errores fatales de `app.php` (líneas 85–100) postea a Telegram antes de renderizar error
- Muchos comentarios "TODOS LOS CODIGOS" son ruido, no TODOs reales

## Subcarpetas con contexto propio

- `public_html/CLAUDE.md` — detalles backend/webapp
- `public_html/api/CLAUDE.md` — convenciones API v1
- `mobile-app/CLAUDE.md` — app Expo
- `jenkins/CLAUDE.md` — pipelines CI

## Memoria dinámica

`~/.claude/projects/-home-admin-web-codigoamigo-com/memory/` — indexado en `MEMORY.md`.
Categorías: `user_*`, `feedback_*`, `project_*`, `reference_*`.

## Skill routing

When the user's request matches an available skill, invoke it via the Skill tool. When in doubt, invoke the skill.

Key routing rules:
- Product ideas/brainstorming → invoke /office-hours
- Strategy/scope → invoke /plan-ceo-review
- Architecture → invoke /plan-eng-review
- Design system/plan review → invoke /design-consultation or /plan-design-review
- Full review pipeline → invoke /autoplan
- Bugs/errors → invoke /investigate
- QA/testing site behavior → invoke /qa or /qa-only
- Code review/diff check → invoke /review
- Visual polish → invoke /design-review
- Ship/deploy/PR → invoke /ship or /land-and-deploy
- Save progress → invoke /context-save
- Resume context → invoke /context-restore
- Author a backlog-ready spec/issue → invoke /spec
