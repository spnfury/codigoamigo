#!/usr/bin/env python3
"""Script de prueba para verificar get_active_sources"""

import sys
import logging
from telegram_monitor import TelegramMonitor

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

logger = logging.getLogger(__name__)

try:
    monitor = TelegramMonitor()
    logger.info("Probando obtención de fuentes activas...")
    sources = monitor.get_active_sources('telegram')
    
    logger.info(f"\n{'='*60}")
    logger.info(f"RESULTADO: {len(sources)} fuentes encontradas")
    logger.info(f"{'='*60}")
    
    if sources:
        for i, source in enumerate(sources, 1):
            logger.info(f"{i}. {source.get('nombre', 'N/A')} - ID: {source.get('id', 'N/A')}")
    else:
        logger.warning("No se encontraron fuentes activas")
        
    sys.exit(0 if sources else 1)
    
except Exception as e:
    logger.error(f"Error durante la prueba: {e}", exc_info=True)
    sys.exit(1)
