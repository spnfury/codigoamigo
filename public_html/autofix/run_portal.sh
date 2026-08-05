#!/bin/bash
# autofix/run_portal.sh — entrypoint cron del motor multi-portal.
# flock por portal (no bloquea entre portales ni con el autofix de CodigoAmigo).
# Uso: run_portal.sh <nombre-portal>
set -uo pipefail

PORTAL="${1:?Uso: run_portal.sh <nombre-portal>}"
DIR="/home/admin/web/codigoamigo.com/public_html/autofix"
export PATH="/usr/local/bin:/usr/bin:/bin:/root/.local/bin:$PATH"

exec flock -n "$DIR/state/.lock-$PORTAL" -c "php $DIR/orchestrator_portal.php --portal=$PORTAL"
