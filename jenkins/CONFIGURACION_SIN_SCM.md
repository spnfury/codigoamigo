# Configuración de Jenkins SIN SCM (Solución al Error de Checkout Local)

## Problema

Si ves este error:
```
ERROR: Checkout of Git remote 'file:///...' aborted because it references a local directory, which may be insecure.
```

Es porque Jenkins no permite checkouts desde rutas locales (`file://`) por seguridad.

## Solución: Usar Pipeline Script Directo

### Pasos para Configurar

1. **Abrir el job en Jenkins**
   - Ve a tu job `telegram-sync-chollos`
   - Click en **"Configure"** (Configurar)

2. **Cambiar la definición del Pipeline**
   - En la sección **"Pipeline"**
   - Cambiar de **"Pipeline script from SCM"** a **"Pipeline script"**

3. **Copiar el script del pipeline**
   - Abre el archivo `jenkins/telegram-sync-job.groovy` en tu servidor
   - Copia TODO el contenido del archivo
   - Pégalo en el campo **"Script"** de Jenkins

4. **Guardar la configuración**
   - Click en **"Save"** (Guardar)

5. **Probar la ejecución**
   - Click en **"Build Now"** para probar
   - Verifica que no haya errores en los logs

### Ventajas de este Método

✅ No requiere configuración de Git SCM  
✅ No hay problemas con checkouts locales  
✅ El script se actualiza manualmente cuando cambies el archivo  
✅ Más simple y directo  

### Desventajas

❌ Si cambias el archivo `telegram-sync-job.groovy`, debes actualizarlo manualmente en Jenkins  
❌ No hay sincronización automática con el repositorio  

### Actualizar el Script en Jenkins

Cuando hagas cambios en `jenkins/telegram-sync-job.groovy`:

1. Abre el archivo en el servidor
2. Copia el contenido actualizado
3. Ve a Jenkins → Configure → Pipeline
4. Pega el nuevo contenido en el campo "Script"
5. Guarda

## Alternativa: Script de Actualización Automática

Puedes crear un script que actualice automáticamente el pipeline en Jenkins usando la API de Jenkins:

```bash
#!/bin/bash
# update-jenkins-pipeline.sh

JENKINS_URL="http://localhost:8080"
JENKINS_USER="admin"
JENKINS_TOKEN="your-api-token"
JOB_NAME="telegram-sync-chollos"
SCRIPT_PATH="/home/admin/web/codigoamigo.com/jenkins/telegram-sync-job.groovy"

# Obtener el contenido del script
SCRIPT_CONTENT=$(cat "$SCRIPT_PATH")

# Actualizar el job usando la API de Jenkins
curl -X POST \
  -u "$JENKINS_USER:$JENKINS_TOKEN" \
  "$JENKINS_URL/job/$JOB_NAME/config.xml" \
  --data-urlencode "script=$SCRIPT_CONTENT" \
  -H "Content-Type: application/x-www-form-urlencoded"
```

Pero esto requiere configuración adicional de la API de Jenkins.



