# Panel de Administración Completo - CodigoAmigo

## 🚀 Descripción

Se ha creado un sistema de administración completo y moderno para gestionar todos los aspectos de la plataforma CodigoAmigo. El panel incluye gestión de usuarios, marcas, códigos, transacciones, reportes, configuración y logs.

## 📁 Archivos Creados

### Panel Principal
- `admin_dashboard.php` - Dashboard principal con estadísticas generales
- `admin_usuarios.php` - Gestión completa de usuarios y saldos
- `admin_marcas.php` - Gestión de marcas con fusión y categorización
- `admin_codigos.php` - Gestión de códigos con acciones masivas
- `admin_transacciones.php` - Gestión de transacciones y pagos
- `admin_reportes.php` - Reportes y estadísticas avanzadas
- `admin_configuracion.php` - Configuración completa del sistema
- `admin_logs.php` - Sistema de logs y auditoría

## 🔧 Funcionalidades Implementadas

### 1. Dashboard Principal (`admin_dashboard.php`)
- **Estadísticas en tiempo real:**
  - Total de usuarios, marcas, códigos
  - Códigos activos y destacados
  - Saldo total de usuarios
  - Usuarios y códigos nuevos (últimos 30 días)
- **Gráficos interactivos** con Chart.js
- **Transacciones recientes**
- **Usuarios más activos**
- **Marcas más populares**

### 2. Gestión de Usuarios (`admin_usuarios.php`)
- **Listado completo** con paginación y filtros
- **Gestión de saldo:**
  - Ajustar saldo manualmente
  - Añadir saldo con motivo
  - Historial de transacciones
- **Control de estado** (activo/inactivo)
- **Filtros avanzados:**
  - Por estado, saldo, búsqueda de texto
- **Estadísticas** de usuarios

### 3. Gestión de Marcas (`admin_marcas.php`)
- **CRUD completo** de marcas
- **Fusión de marcas** (mover códigos entre marcas)
- **Gestión de categorías**
- **Control de imágenes**
- **Filtros por estado, categoría, búsqueda**
- **Estadísticas** de marcas

### 4. Gestión de Códigos (`admin_codigos.php`)
- **Listado completo** con información detallada
- **Acciones masivas:**
  - Activar/desactivar múltiples códigos
  - Destacar/quitar destacado
  - Eliminar códigos
- **Edición individual** de códigos
- **Filtros avanzados:**
  - Por estado, destacado, marca, usuario
- **Estadísticas** detalladas

### 5. Gestión de Transacciones (`admin_transacciones.php`)
- **Listado completo** de transacciones
- **Filtros por tipo, estado, usuario, fechas, cantidad**
- **Estadísticas financieras:**
  - Ingresos totales y del período
  - Transacciones por estado
- **Aprobación/rechazo** de transacciones pendientes
- **Detalles completos** de cada transacción

### 6. Reportes y Estadísticas (`admin_reportes.php`)
- **Dashboard de reportes** con filtros de fecha
- **Gráficos de evolución** diaria (últimos 30 días)
- **Rankings:**
  - Usuarios más activos
  - Marcas más populares
  - Códigos más vistos
- **Estadísticas financieras**
- **Transacciones recientes**

### 7. Configuración del Sistema (`admin_configuracion.php`)
- **Configuración general:**
  - Nombre del sitio, URL, contacto
  - Idioma, zona horaria, moneda
- **Configuración de usuarios:**
  - Registro, verificación, límites
- **Configuración de códigos:**
  - Moderación, costos, límites
- **Configuración de pagos:**
  - Stripe, PayPal
- **Configuración de email:**
  - SMTP, notificaciones
- **Configuración de SEO:**
  - Meta tags, analytics
- **Configuración de seguridad:**
  - Login, HTTPS, logs

### 8. Sistema de Logs (`admin_logs.php`)
- **Registro completo** de actividades
- **Filtros avanzados:**
  - Por tipo, nivel, usuario, fechas
- **Estadísticas** de logs
- **Detalles expandibles** de cada log
- **Auto-refresh** para logs en tiempo real

## 🎨 Características del Diseño

### Interfaz Moderna
- **Bootstrap 5** con diseño responsivo
- **Sidebar** con navegación intuitiva
- **Cards** con estadísticas visuales
- **Gradientes** y efectos modernos
- **Iconos Font Awesome** para mejor UX

### Funcionalidades UX
- **Paginación** en todas las listas
- **Filtros** en tiempo real
- **Modales** para acciones rápidas
- **Mensajes** de éxito/error
- **Confirmaciones** para acciones destructivas
- **Tooltips** y ayuda contextual

## 🔐 Seguridad

### Control de Acceso
- **Verificación de permisos** en cada página
- **Lista de administradores** autorizados
- **Redirección** automática si no tiene permisos

### Validación de Datos
- **Sanitización** de inputs
- **Validación** de tipos de datos
- **Escape** de HTML en outputs

## 📊 Base de Datos

### Colecciones Utilizadas
- `usuarios` - Datos de usuarios y saldos
- `marcas` - Información de marcas
- `codigos` - Códigos de descuento
- `transacciones` - Historial de pagos
- `configuracion` - Configuraciones del sistema
- `logs` - Registro de actividades

### Índices Recomendados
```javascript
// Para optimizar consultas
db.usuarios.createIndex({"estado": 1, "saldo": 1})
db.codigos.createIndex({"estado": 1, "destacado": 1, "marca": 1})
db.transacciones.createIndex({"fecha": -1, "tipo": 1})
db.logs.createIndex({"fecha": -1, "nivel": 1, "tipo": 1})
```

## 🚀 Instalación y Uso

### Acceso al Panel
1. **URL principal:** `https://www.codigoamigo.com/admin`
2. **URLs específicas:**
   - Dashboard: `/admin_dashboard.php`
   - Usuarios: `/admin_usuarios.php`
   - Marcas: `/admin_marcas.php`
   - Códigos: `/admin_codigos.php`
   - Transacciones: `/admin_transacciones.php`
   - Reportes: `/admin_reportes.php`
   - Configuración: `/admin_configuracion.php`
   - Logs: `/admin_logs.php`

### Permisos de Administrador
Los siguientes usuarios tienen acceso completo:
- `58bd851da54e295b8b52f702` (thevega82@gmail.com)
- `5e78170e6b68e6519b7c5df2` (edna)
- `639899bc6321ee0d0e4010d2` (aron)
- `5c8a10ce2f55c86d6e707d82` (jose)

## 🔄 Rutas Configuradas

Se han añadido las siguientes rutas en `app.php`:
- `/admin` → Dashboard principal
- `/admin_dashboard` → Dashboard
- `/admin_usuarios` → Gestión de usuarios
- `/admin_marcas` → Gestión de marcas
- `/admin_codigos` → Gestión de códigos
- `/admin_transacciones` → Gestión de transacciones
- `/admin_reportes` → Reportes
- `/admin_configuracion` → Configuración
- `/admin_logs` → Logs

## 📈 Próximas Mejoras

### Funcionalidades Adicionales
- [ ] **Exportación** de datos (CSV, Excel)
- [ ] **Notificaciones** en tiempo real
- [ ] **Backup** automático de configuraciones
- [ ] **API REST** para integraciones
- [ ] **Dashboard** personalizable
- [ ] **Temas** de colores
- [ ] **Multi-idioma** completo

### Optimizaciones
- [ ] **Caché** de consultas frecuentes
- [ ] **Paginación** infinita
- [ ] **Búsqueda** con autocompletado
- [ ] **Filtros** guardados
- [ ] **Atajos** de teclado

## 🛠️ Mantenimiento

### Logs del Sistema
- Todos los cambios se registran en la colección `logs`
- Niveles: `error`, `warning`, `info`, `debug`
- Tipos: `login`, `admin_action`, `codigo_create`, etc.

### Configuraciones
- Se almacenan en la colección `configuracion`
- Sección `sistema` para configuraciones generales
- Actualización automática de timestamps

### Monitoreo
- **Estadísticas** en tiempo real
- **Alertas** de errores
- **Métricas** de rendimiento

## 📞 Soporte

Para cualquier problema o mejora:
1. Revisar los **logs del sistema**
2. Verificar **configuraciones**
3. Comprobar **permisos** de usuario
4. Consultar **estadísticas** de rendimiento

---

**Desarrollado para CodigoAmigo** - Sistema de administración completo y moderno para la gestión integral de la plataforma.


