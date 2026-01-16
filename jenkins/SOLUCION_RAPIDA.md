# Solución Rápida al Error de Checkout Local

## El Problema
```
ERROR: Checkout of Git remote 'file:///...' aborted because it references a local directory
```

## Solución Rápida: Habilitar Checkouts Locales

### Opción 1: Usar el Script Automático (Recomendado)

```bash
cd /home/admin/web/codigoamigo.com
./jenkins/fix-checkout-local.sh
```

El script te pedirá:
- URL de Jenkins (por defecto: http://localhost:8080)
- Usuario de Jenkins
- Token de API o contraseña

Después, reinicia Jenkins:
```bash
sudo systemctl restart jenkins
# o
sudo service jenkins restart
```

### Opción 2: Configuración Manual

1. **Acceder a Jenkins CLI:**
   ```bash
   cd /var/lib/jenkins
   java -jar jenkins-cli.jar -s http://localhost:8080/ -auth USUARIO:TOKEN groovy = <<'EOF'
   System.setProperty('hudson.plugins.git.GitSCM.ALLOW_LOCAL_CHECKOUT', 'true')
   println "✓ Checkouts locales habilitados"
   EOF
   ```

2. **O modificar directamente el archivo de propiedades:**
   ```bash
   echo "hudson.plugins.git.GitSCM.ALLOW_LOCAL_CHECKOUT=true" >> /var/lib/jenkins/jenkins.properties
   sudo systemctl restart jenkins
   ```

3. **Reiniciar Jenkins:**
   ```bash
   sudo systemctl restart jenkins
   ```

## Solución Definitiva: Cambiar Configuración del Job

Si prefieres no habilitar checkouts locales (más seguro), cambia la configuración del job:

1. Abre Jenkins en el navegador
2. Ve al job `telegram-sync-chollos`
3. Click en **"Configure"**
4. En **"Pipeline Definition"**, cambia:
   - De: `Pipeline script from SCM`
   - A: `Pipeline script`
5. Copia el contenido de `jenkins/telegram-sync-job.groovy` y pégalo en el campo "Script"
6. Guarda

## Verificar que Funciona

Después de aplicar la solución, ejecuta el job manualmente y verifica que no haya errores de checkout.



