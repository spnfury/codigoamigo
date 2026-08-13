# autofix — loop auto-reparador

Sistema autónomo que detecta errores `critical` en producción, los diagnostica
con Claude Code headless, aplica un fix mínimo y lo vigila. Sin intervención humana.

## Flujo (cada 30 min vía cron)

```
logs/critical/HOY.log
   │  detect.php  (parsea + deduplica + clasifica)
   ▼
orchestrator.php
   1. CANARIO   revisa fixes en observación → auto-rollback si el error reapareció
   2. BREAKER   si >N rollbacks hoy → se pausa y avisa
   3. DETECTAR  errores nuevos no vistos
   4. REPARAR   backup → claude headless → php -l → marca "en observación"
   │  notify.php (Telegram, chat admin)
   ▼
tú (solo miras Telegram)
```

## Guardarraíles (por qué es seguro ser autónomo)

1. **Whitelist de tipos** (`tipos_seguros`): solo errores mecánicos de bajo riesgo
   (TypeError, undefined, casts). El resto → revisión manual.
2. **Rutas prohibidas** (`rutas_sensibles`): stripe/auth/pagos/api/config nunca se
   tocan solos, aunque el tipo sea seguro.
3. **Lint obligatorio**: si `php -l` falla tras el fix → restaura backup al instante.
4. **Canario + auto-rollback**: si el mismo error reaparece dentro de la ventana,
   se restaura el original solo.
5. **Circuit breaker**: `max_rollbacks_dia` rollbacks → el loop se pausa hasta mañana.
6. **Backup por archivo** (no git): rollback fiable aunque el working tree esté sucio.
7. **Tools restringidas**: el agente headless solo puede `Edit/Read/Grep/Bash(php -l)`.
   No puede ejecutar comandos arbitrarios, ni git push, ni tocar red.

## Modo

`config.php` → `'modo'`:
- `dry`  (por defecto ahora): detecta y notifica, **no edita nada**. Burn-in seguro.
- `live`: autónomo real (aplica fixes).

**Pasar a live** cuando confíes en las notificaciones dry:
```php
// autofix/config.php
'modo' => 'live',
```

## Estado

- `state/seen.json` — errores ya procesados y su estado
  (`healing`/`healed`/`rollback`/`failed`/`manual`).
- `state/rollbacks.json` — contador diario del circuit breaker.
- `state/backups/` — copias previas para rollback.
- `logs/autofix-YYYY-MM-DD.log` — traza de cada ciclo.

## Probar a mano

```bash
php autofix/detect.php          # ver errores nuevos (JSON)
php autofix/orchestrator.php    # correr un ciclo ahora
```

## Motor MULTI-PORTAL (otros sitios)

`orchestrator_portal.php` + `parsers.php` extienden el mismo cerebro (canario,
breaker, lint, backup, whitelist) a **cualquier portal PHP**, sin depender del
logger específico de CodigoAmigo. Es un sistema **paralelo e independiente**:
no toca `config.php`/`detect.php`/`orchestrator.php` (el autofix original de
CodigoAmigo sigue corriendo exactamente igual).

**Cómo cubre stacks distintos sin un parser por sitio:** en vez de parsear el
formato propio de cada logger, `parsers.php` usa un **regex universal** que
reconoce el núcleo `PHP <tipo>: <mensaje> in <archivo> on line <N>` esté como
esté envuelto — Apache+proxy_fcgi (`AH01071: ... PHP message: ...`), nginx
(`FastCGI sent in stderr: PHP message: ...`), o el `php_errors.log` nativo.
Un solo extractor cubre los tres.

Los logs de nginx/apache **crecen sin parar** (vistos hasta 90MB) — se leen por
**offset de bytes** (`state/<portal>/offsets.json`), con baseline en la primera
vista (no lee histórico, no revienta memoria, no re-alerta lo viejo). Mismo
patrón que `fleet_watch.php`.

### Añadir un portal nuevo

1. Crear `autofix/portals/<nombre>.php` — copiar uno existente
   (`portals/camarerooo.php` o `portals/malprecio.php`) y ajustar:
   `raiz` (para que claude pueda editar), `state_dir` (aislado por portal),
   `sources` (ruta al error log real — mirar `ErrorLog`/`error_log` del vhost),
   `rutas_sensibles` (paths de pago/auth propios de ese sitio).
2. `mkdir -p state/<nombre>/backups`.
3. Probar: `php orchestrator_portal.php --portal=<nombre>` (arranca en `dry`,
   el primer run solo hace baseline — no detecta nada del histórico).
4. Cron **escalonado** — minuto distinto a los demás portales y a `fleet_watch`
   para no correr todo a la vez:
   `M,M+30 * * * * .../run_portal.sh <nombre> >> .../logs/cron-<nombre>.log 2>&1`
5. Burn-in en `dry` unos días, luego `'modo' => 'live'` en su config.

**Portales activos hoy:** `camarerooo` (Laravel, error log Apache), `malprecio`
(PHP plano, error log nginx). Ambos en `dry` — pendiente burn-in.

**Pendiente / fuera de alcance actual:** Laravel (`currofacil`) y Python
(`money17`) tienen formatos de traza propios (excepciones Laravel, tracebacks
Python) que el regex universal PHP no cubre — necesitarían su propio extractor
en `parsers.php` si se quiere autofix de código ahí (hoy solo tienen vigía vía
`fleet_watch.php`, sin reparación automática).
