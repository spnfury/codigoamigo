#!/bin/bash
# autofix/run.sh — entrypoint del cron. Envuelve orchestrator.php con flock
# para que nunca corran dos ciclos a la vez.
set -uo pipefail

DIR="/home/admin/web/codigoamigo.com/public_html/autofix"
export PATH="/usr/local/bin:/usr/bin:/bin:/root/.local/bin:$PATH"

exec flock -n "$DIR/state/.lock" -c "php $DIR/orchestrator.php"
