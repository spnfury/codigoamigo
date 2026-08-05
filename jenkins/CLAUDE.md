# jenkins — pipelines CI

Scripts Groovy + helpers bash para jobs en Jenkins.

## Jobs principales

- `telegram-sync-job.groovy` / `telegram-sync-chollos.groovy` — sincroniza chollos con canal Telegram
- `limpiar-chollos-job.groovy` — limpieza periódica de chollos caducados
- `create-*.groovy` / `create-*.sh` — scripts para aprovisionar jobs nuevos vía API Jenkins
- `update-job-via-api.sh` — actualiza job sin entrar a la UI
- `approve-script.sh` — aprueba scripts pendientes de sandbox

## Documentación interna

README y `CONFIGURACION_*.md`, `INSTRUCCIONES_*.md`, `SOLUCION_RAPIDA.md` son referencias operativas. Consultar antes de tocar pipelines nuevos.

## Reglas

- **Credenciales:** usar Jenkins credentials store, nunca hardcodear en `.groovy`
- **Cron expr:** documentar cambios en README del job y avisar al usuario
- **Scripts sandbox:** nuevos usos de APIs Groovy requieren aprobación — pasar por `approve-script.sh`
- **Token Telegram:** mismo canal/bot que error handler de `app.php` (chat `-563343505`) — verificar antes de cambiar
- **Retries / timeouts:** conservar defaults salvo justificación

## Gotchas

- SCM desactivado en algunos jobs — ver `CONFIGURACION_SIN_SCM.md`
- Checkout local roto en algunos workers → `fix-checkout-local.sh`
