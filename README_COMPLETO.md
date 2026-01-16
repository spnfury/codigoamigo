# CodigoAmigo.com - README Completo para Recreación

## 📋 Descripción General

CodigoAmigo.com es una plataforma web de códigos de descuento y cupones que permite a los usuarios compartir, descubrir y utilizar códigos promocionales de diversas marcas. El sistema incluye funcionalidades de monetización, sistema de votación, gestión de usuarios y un panel de administración completo.

## 🏗️ Arquitectura del Sistema

### Tecnologías Actuales
- **Backend**: PHP 7.4+ con Slim Framework 3
- **Base de Datos**: MongoDB
- **Frontend**: HTML5, CSS3, JavaScript (jQuery, Bootstrap 4)
- **Servidor Web**: Apache/Nginx
- **Email**: SendGrid + PHP Mail
- **Notificaciones**: Telegram Bot API

### Tecnologías Objetivo (Recreación)
- **Frontend**: HTML5 + Tailwind CSS
- **Backend**: Node.js/Express o PHP 8+ con Slim 4
- **Base de Datos**: MongoDB (mantener)
- **Framework CSS**: Tailwind CSS
- **JavaScript**: Vanilla JS o Alpine.js
- **Deployment**: Vercel/Netlify (Frontend) + Railway/Heroku (Backend)

## 🗄️ Estructura de Base de Datos MongoDB

### Colecciones Principales

#### 1. `usuarios`
```javascript
{
  "_id": ObjectId,
  "username": String,
  "mail": String,
  "pass": String,
  "confirm_password": String,
  "estado": Number, // 0: activo, -1: baneado temporal, -2: baneado definitivo
  "type": String, // "web", "facebook", "googleonetap"
  "fecha_registro": String,
  "img": String,
  "saldo": Number, // Saldo en euros
  "pro_user": Number, // 0: normal, 1: premium
  "totalclicks": Number,
  "total_ganancias": Number
}
```

#### 2. `codigos`
```javascript
{
  "_id": ObjectId,
  "marca": String, // nombre_clave de la marca
  "num_beneficio": Number, // Beneficio en euros
  "tipo_descuento": String, // "porcentaje", "euros", "envio_gratis"
  "codigo": String, // Código promocional
  "descripcion": String,
  "provincia": String,
  "localidad": String,
  "fecha_caducidad": String,
  "id_usuario": ObjectId,
  "estado": Number, // 0: activo, -1: desactivado
  "destacado": Number, // 0: normal, >0: destacado
  "destacado_social": Number, // Para patrocinados
  "totalclicks": Number,
  "num_valoraciones": Number,
  "fecha_publicacion": Date,
  "visibilidad": String // "baja", "media", "alta"
}
```

#### 3. `marcas`
```javascript
{
  "_id": ObjectId,
  "nombre": String, // Nombre oficial de la marca
  "nombre_clave": String, // URL-friendly
  "imagen": String, // URL de la imagen
  "descripcion": String,
  "descripción_larga": String,
  "categoria": String,
  "estado": Number, // 0: activa, -1: inactiva
  "fecha_creacion": Date,
  "total_codigos": Number
}
```

#### 4. `transacciones`
```javascript
{
  "_id": ObjectId,
  "id_usuario": ObjectId,
  "tipo": String, // "ingreso", "retiro", "pago_codigo"
  "cantidad": Number,
  "descripcion": String,
  "fecha": Date,
  "estado": String // "pendiente", "completada", "cancelada"
}
```

#### 5. `logs`
```javascript
{
  "_id": ObjectId,
  "nivel": String, // "info", "warning", "error"
  "mensaje": String,
  "contexto": Object,
  "fecha": Date,
  "ip": String,
  "user_agent": String
}
```

## 📄 Páginas y Rutas Principales

### Páginas Públicas

#### 1. **Página Principal** (`/`)
- **Funcionalidad**: Lista de códigos destacados y recientes
- **Componentes**:
  - Header con búsqueda
  - Hero section con estadísticas
  - Grid de códigos destacados
  - Grid de códigos recientes
  - Sección de marcas populares
  - Footer con enlaces

#### 2. **Página de Marca** (`/de-{marca}`)
- **Funcionalidad**: Página individual para cada marca
- **Componentes**:
  - Header de marca con logo y descripción
  - Sección de autoridad de marca
  - Grid de códigos de la marca
  - Sidebar con estadísticas
  - Marcas relacionadas
  - FAQ específica de la marca
  - Guía de uso de códigos

#### 3. **Listado de Marcas** (`/listado-marcas`)
- **Funcionalidad**: Catálogo de todas las marcas
- **Componentes**:
  - Grid de marcas con filtros
  - Búsqueda por categoría
  - Paginación

#### 4. **Búsqueda** (`/buscar/{termino}`)
- **Funcionalidad**: Búsqueda de códigos y marcas
- **Componentes**:
  - Resultados de códigos
  - Resultados de marcas
  - Filtros avanzados

#### 5. **Páginas de Guías** (`/guias/*`)
- **Funcionalidad**: Contenido SEO sobre fintech y crypto
- **Ejemplos**:
  - `/guias/exchanges-que-no-informan-a-hacienda`
  - `/guias/neobancos-que-no-informan-a-hacienda`

### Páginas de Usuario

#### 1. **Registro** (`/registro`)
- **Funcionalidad**: Registro de nuevos usuarios
- **Métodos**: Web, Facebook, Google One Tap

#### 2. **Panel de Usuario** (`/usuario`)
- **Funcionalidad**: Dashboard personal del usuario
- **Componentes**:
  - Estadísticas personales
  - Lista de códigos publicados
  - Historial de transacciones
  - Configuración de perfil

#### 3. **Publicar Código** (`/publicar-codigo`)
- **Funcionalidad**: Formulario para publicar códigos
- **Validaciones**: Código único por marca/usuario

### Panel de Administración

#### 1. **Dashboard Admin** (`/admin`)
- **Funcionalidad**: Estadísticas generales del sistema
- **Métricas**:
  - Total usuarios, marcas, códigos
  - Códigos activos y destacados
  - Saldo total de usuarios
  - Gráficos de crecimiento

#### 2. **Gestión de Usuarios** (`/admin/usuarios`)
- **Funcionalidad**: CRUD completo de usuarios
- **Características**:
  - Gestión de saldo
  - Control de estado (activo/baneado)
  - Historial de transacciones

#### 3. **Gestión de Marcas** (`/admin/marcas`)
- **Funcionalidad**: CRUD de marcas
- **Características**:
  - Fusión de marcas
  - Gestión de categorías
  - Control de imágenes

#### 4. **Gestión de Códigos** (`/admin/codigos`)
- **Funcionalidad**: Administración de códigos
- **Características**:
  - Aprobación/desaprobación
  - Destacar códigos
  - Acciones masivas

## 🎨 Diseño y UI/UX

### Esquema de Colores
- **Primario**: #FF6B35 (Naranja)
- **Secundario**: #2C2C2C (Gris oscuro)
- **Acento**: #28a745 (Verde para éxito)
- **Fondo**: #f8f9fa (Gris claro)
- **Texto**: #333333 (Gris oscuro)

### Componentes de UI con Tailwind

#### 1. **Header**
```html
<header class="bg-gray-800 text-white shadow-lg">
  <div class="container mx-auto px-4 py-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <h1 class="text-2xl font-bold">
          <span class="text-orange-500">codigo</span>
          <span class="text-white">amigo</span>
        </h1>
        <p class="text-sm text-gray-300">códigos verificados, gente real</p>
      </div>
      <div class="flex items-center space-x-4">
        <input type="search" placeholder="Buscar códigos..." 
               class="px-4 py-2 rounded-lg bg-gray-700 text-white">
        <button class="bg-orange-500 hover:bg-orange-600 px-4 py-2 rounded-lg">
          Acceder
        </button>
      </div>
    </div>
  </div>
</header>
```

#### 2. **Tarjeta de Código**
```html
<div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
  <div class="flex items-start justify-between mb-4">
    <div class="flex items-center space-x-3">
      <img src="logo-marca.png" alt="Marca" class="w-12 h-12 rounded">
      <div>
        <h3 class="font-semibold text-lg">Código Descuento</h3>
        <p class="text-gray-600">Hasta 20€ de descuento</p>
      </div>
    </div>
    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
      Verificado
    </span>
  </div>
  
  <div class="bg-gray-50 p-3 rounded mb-4">
    <code class="text-lg font-mono">DESCUENTO20</code>
  </div>
  
  <div class="flex items-center justify-between">
    <div class="flex space-x-2">
      <button class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">
        Copiar Código
      </button>
      <button class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">
        Ver Código
      </button>
    </div>
    <div class="flex space-x-1">
      <button class="text-green-500 hover:text-green-600">👍 12</button>
      <button class="text-red-500 hover:text-red-600">👎 2</button>
    </div>
  </div>
</div>
```

#### 3. **Hero Section**
```html
<section class="bg-gradient-to-r from-gray-800 to-gray-900 text-white py-16">
  <div class="container mx-auto px-4 text-center">
    <h1 class="text-4xl md:text-6xl font-bold mb-6">
      💰 Códigos Descuento 2025
    </h1>
    <p class="text-xl mb-8 max-w-2xl mx-auto">
      Los mejores códigos descuento y cupones de las principales marcas. 
      Ahorra dinero con CodigoAmigo.com
    </p>
    <div class="flex flex-wrap justify-center gap-4 text-sm">
      <span class="bg-orange-500 px-4 py-2 rounded-full">1,234 códigos disponibles</span>
      <span class="bg-gray-700 px-4 py-2 rounded-full">Actualizados diariamente</span>
      <span class="bg-gray-700 px-4 py-2 rounded-full">100% gratuitos</span>
    </div>
  </div>
</section>
```

## 🔧 Funcionalidades Principales

### 1. **Sistema de Códigos**
- **Publicación**: Usuarios pueden publicar códigos de descuento
- **Validación**: Sistema de verificación de códigos
- **Categorización**: Por marca, tipo de descuento, provincia
- **Votación**: Sistema de likes/dislikes
- **Destacado**: Códigos patrocinados y destacados

### 2. **Sistema de Usuarios**
- **Registro**: Múltiples métodos (web, Facebook, Google)
- **Perfil**: Gestión de datos personales
- **Monetización**: Sistema de saldo y pagos
- **Estadísticas**: Tracking de clicks y ganancias

### 3. **Sistema de Marcas**
- **Gestión**: CRUD completo de marcas
- **Categorización**: Por tipo de negocio
- **Imágenes**: Gestión de logos y assets
- **Fusión**: Capacidad de fusionar marcas duplicadas

### 4. **Panel de Administración**
- **Dashboard**: Estadísticas en tiempo real
- **Gestión de Contenido**: CRUD de códigos, marcas, usuarios
- **Moderación**: Aprobación de contenido
- **Reportes**: Análisis de rendimiento

### 5. **Sistema de Búsqueda**
- **Búsqueda Global**: Códigos y marcas
- **Filtros**: Por categoría, provincia, tipo
- **Autocompletado**: Sugerencias en tiempo real
- **SEO**: URLs amigables

## 📱 Responsive Design

### Breakpoints Tailwind
- **Mobile**: `sm:` (640px+)
- **Tablet**: `md:` (768px+)
- **Desktop**: `lg:` (1024px+)
- **Large**: `xl:` (1280px+)

### Componentes Responsive
```html
<!-- Grid responsive -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
  <!-- Tarjetas de código -->
</div>

<!-- Header responsive -->
<header class="flex flex-col md:flex-row items-center justify-between p-4">
  <div class="mb-4 md:mb-0">
    <!-- Logo -->
  </div>
  <div class="w-full md:w-auto">
    <!-- Búsqueda -->
  </div>
</header>
```

## 🚀 Plan de Migración a Tailwind

### Fase 1: Setup Inicial
1. **Instalar Tailwind CSS**
   ```bash
   npm install -D tailwindcss
   npx tailwindcss init
   ```

2. **Configurar tailwind.config.js**
   ```javascript
   module.exports = {
     content: ["./src/**/*.{html,js,php}"],
     theme: {
       extend: {
         colors: {
           'orange-primary': '#FF6B35',
           'gray-dark': '#2C2C2C'
         }
       }
     },
     plugins: []
   }
   ```

### Fase 2: Componentes Base
1. **Header Component**
2. **Footer Component**
3. **Card Components**
4. **Button Components**
5. **Form Components**

### Fase 3: Páginas Principales
1. **Homepage** (`/`)
2. **Marca Page** (`/de-{marca}`)
3. **Listado Marcas** (`/listado-marcas`)
4. **Búsqueda** (`/buscar/{termino}`)

### Fase 4: Panel de Usuario
1. **Dashboard Usuario**
2. **Publicar Código**
3. **Configuración Perfil**

### Fase 5: Panel Admin
1. **Dashboard Admin**
2. **Gestión de Usuarios**
3. **Gestión de Marcas**
4. **Gestión de Códigos**

## 📊 APIs y Endpoints

### Endpoints Principales
```javascript
// Códigos
GET /api/codigos - Listar códigos
POST /api/codigos - Crear código
PUT /api/codigos/:id - Actualizar código
DELETE /api/codigos/:id - Eliminar código

// Marcas
GET /api/marcas - Listar marcas
POST /api/marcas - Crear marca
PUT /api/marcas/:id - Actualizar marca

// Usuarios
GET /api/usuarios - Listar usuarios
POST /api/usuarios - Crear usuario
PUT /api/usuarios/:id - Actualizar usuario

// Búsqueda
GET /api/buscar?q=termino - Buscar códigos/marcas
```

## 🔐 Seguridad

### Medidas Implementadas
- **Validación de entrada**: Sanitización de datos
- **Autenticación**: Sistema de sesiones
- **Autorización**: Control de acceso por roles
- **Rate Limiting**: Límites de requests
- **CSRF Protection**: Tokens de seguridad
- **XSS Protection**: Escape de output

## 📈 SEO y Performance

### Optimizaciones SEO
- **Meta Tags**: Dinámicos por página
- **URLs Amigables**: Estructura limpia
- **Sitemap**: Generación automática
- **Schema Markup**: Datos estructurados
- **Open Graph**: Redes sociales

### Optimizaciones Performance
- **Lazy Loading**: Imágenes y contenido
- **Minificación**: CSS/JS
- **Compresión**: Gzip
- **CDN**: Assets estáticos
- **Caching**: Redis/Memcached

## 🧪 Testing

### Estrategia de Testing
- **Unit Tests**: Funciones individuales
- **Integration Tests**: APIs y endpoints
- **E2E Tests**: Flujos completos
- **Performance Tests**: Carga y velocidad

### Herramientas Recomendadas
- **PHPUnit**: Para PHP
- **Jest**: Para JavaScript
- **Cypress**: Para E2E
- **Lighthouse**: Para performance

## 📦 Deployment

### Opciones de Hosting

#### Frontend (Vercel/Netlify)
```bash
# Vercel
npm install -g vercel
vercel --prod

# Netlify
npm run build
netlify deploy --prod --dir=dist
```

#### Backend (Railway/Heroku)
```bash
# Railway
railway login
railway init
railway up

# Heroku
heroku create codigoamigo-api
git push heroku main
```

#### Base de Datos (MongoDB Atlas)
- Configurar cluster en MongoDB Atlas
- Obtener connection string
- Configurar variables de entorno

## 🔄 Migración de Datos

### Script de Migración
```php
<?php
// migrate_to_new_system.php

// 1. Conectar a MongoDB existente
$oldDb = new MongoDB\Client("mongodb://old-server:27017");
$newDb = new MongoDB\Client("mongodb://new-server:27017");

// 2. Migrar usuarios
$usuarios = $oldDb->codigo_db->usuarios->find();
foreach($usuarios as $usuario) {
    $newDb->codigoamigo->usuarios->insertOne($usuario);
}

// 3. Migrar códigos
$codigos = $oldDb->codigo_db->codigos->find();
foreach($codigos as $codigo) {
    $newDb->codigoamigo->codigos->insertOne($codigo);
}

// 4. Migrar marcas
$marcas = $oldDb->codigo_db->marcas->find();
foreach($marcas as $marca) {
    $newDb->codigoamigo->marcas->insertOne($marca);
}
?>
```

## 📋 Checklist de Implementación

### Pre-requisitos
- [ ] Node.js 16+ instalado
- [ ] MongoDB 4.4+ configurado
- [ ] Git configurado
- [ ] Editor de código (VS Code recomendado)

### Setup Inicial
- [ ] Clonar repositorio
- [ ] Instalar dependencias (`npm install`)
- [ ] Configurar variables de entorno
- [ ] Configurar base de datos
- [ ] Ejecutar migraciones

### Desarrollo
- [ ] Implementar componentes base
- [ ] Crear páginas principales
- [ ] Implementar APIs
- [ ] Configurar autenticación
- [ ] Implementar panel admin

### Testing
- [ ] Tests unitarios
- [ ] Tests de integración
- [ ] Tests E2E
- [ ] Tests de performance

### Deployment
- [ ] Configurar CI/CD
- [ ] Deploy a staging
- [ ] Deploy a producción
- [ ] Configurar monitoreo
- [ ] Configurar backups

## 📞 Soporte y Contacto

### Documentación Adicional
- **API Docs**: `/docs/api`
- **Component Library**: `/docs/components`
- **Deployment Guide**: `/docs/deployment`

### Contacto
- **Email**: soporte@codigoamigo.com
- **Telegram**: @codigoamigo_support
- **GitHub**: github.com/codigoamigo

---

**Nota**: Este README es una guía completa para recrear CodigoAmigo.com con tecnologías modernas. Cada sección incluye ejemplos de código y mejores prácticas para asegurar una implementación exitosa.
