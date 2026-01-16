# Instrucciones para Aprobar el Script en Jenkins

## Problema Actual

El job está configurado correctamente (ya no intenta hacer checkout de Git), pero Jenkins requiere aprobación manual del script por seguridad.

## Solución: Aprobar el Script Manualmente

### Pasos:

1. **Abre Jenkins en tu navegador**
   - URL: `http://tu-servidor:8085/jenkins` (o la URL que uses)

2. **Ve a la página de aprobación de scripts**
   - Click en **"Manage Jenkins"** (Gestionar Jenkins)
   - Click en **"In-process Script Approval"** (Aprobación de Scripts en Proceso)
   - O accede directamente a: `http://tu-servidor:8085/jenkins/scriptApproval`

3. **Aprueba el script**
   - Deberías ver un script pendiente de aprobación
   - Click en **"Approve"** (Aprobar) para el script del pipeline

4. **Verifica que funciona**
   - Ve al job `telegram-sync-chollos`
   - Click en **"Build Now"** para probar
   - O espera al próximo ciclo automático (cada minuto)

## Alternativa: Deshabilitar Aprobación de Scripts (No Recomendado)

Si necesitas deshabilitar completamente la aprobación de scripts (menos seguro):

1. Ve a **Manage Jenkins** → **Configure System**
2. Busca la sección **"Script Security"**
3. Deshabilita la verificación de scripts

⚠️ **Nota de Seguridad**: Esto reduce la seguridad de Jenkins. Solo hazlo si es absolutamente necesario.

## Estado Actual

✅ **Resuelto:**
- Ya no intenta hacer checkout de Git
- La configuración del job está correcta (Pipeline script directo)
- El script está incluido en la configuración

⏳ **Pendiente:**
- Aprobación manual del script desde la interfaz web de Jenkins



