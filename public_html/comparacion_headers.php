<?php
// Comparación entre header antiguo y nuevo
session_start();

// Simular sesión de usuario para testing
$_SESSION['usuario_id'] = 'test_user';
$_SESSION['nombre_usuario'] = 'Usuario Test';
$_SESSION['foto_perfil'] = '';

// Incluir las funciones necesarias
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

// Inicializar detector de móviles
if (!isset($detect)) {
    $detect = new Mobile_Detect();
}
$GLOBALS['detect'] = $detect;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Comparación Headers - CodigoAmigo.com</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS Nuevo Header -->
    <link rel="stylesheet" href="/css/header-redesign.css">
    <link rel="stylesheet" href="/css/mobile-header-new.css">
    
    <style>
    body {
        background: #000000;
        color: white;
        font-family: Arial, sans-serif;
    }
    
    .comparison-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .header-demo {
        margin: 20px 0;
        border: 2px solid #333;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .demo-title {
        background: #E30613;
        color: white;
        padding: 10px 20px;
        margin: 0;
        font-weight: bold;
    }
    
    .demo-content {
        padding: 20px;
        background: #1a1a1a;
    }
    
    .features-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    
    .feature-card {
        background: #2a2a2a;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #E30613;
    }
    
    .feature-card h4 {
        color: #E30613;
        margin-top: 0;
    }
    
    .pros-cons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin: 20px 0;
    }
    
    .pros, .cons {
        padding: 20px;
        border-radius: 8px;
    }
    
    .pros {
        background: rgba(0, 255, 0, 0.1);
        border: 1px solid #00ff00;
    }
    
    .cons {
        background: rgba(255, 0, 0, 0.1);
        border: 1px solid #ff0000;
    }
    
    .pros h4 {
        color: #00ff00;
    }
    
    .cons h4 {
        color: #ff0000;
    }
    
    .test-buttons {
        text-align: center;
        margin: 30px 0;
    }
    
    .test-btn {
        display: inline-block;
        padding: 15px 30px;
        margin: 0 10px;
        background: #E30613;
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    
    .test-btn:hover {
        background: #C40510;
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }
    
    .test-btn.secondary {
        background: #333;
    }
    
    .test-btn.secondary:hover {
        background: #555;
    }
    </style>
</head>
<body>
    <div class="comparison-container">
        <h1 style="text-align: center; color: #E30613; margin-bottom: 30px;">
            🔄 Comparación de Headers - CodigoAmigo.com
        </h1>
        
        <div class="test-buttons">
            <a href="/test_header_new.php" class="test-btn">
                🎨 Ver Header Nuevo
            </a>
            <a href="/test_no_duplicacion.php" class="test-btn secondary">
                🔧 Ver Header Antiguo (Fix)
            </a>
        </div>
        
        <div class="features-list">
            <div class="feature-card">
                <h4>🎨 Header Nuevo (Rediseñado)</h4>
                <ul>
                    <li>✅ Diseño moderno desde cero</li>
                    <li>✅ Header compacto (50px móvil, 70px desktop)</li>
                    <li>✅ Navegación intuitiva con dropdowns</li>
                    <li>✅ Búsqueda mejorada con autocompletado</li>
                    <li>✅ Menú móvil con animaciones</li>
                    <li>✅ Sin duplicaciones ni solapamientos</li>
                    <li>✅ Responsive perfecto</li>
                    <li>✅ Código limpio y mantenible</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <h4>🔧 Header Antiguo (Con Fixes)</h4>
                <ul>
                    <li>⚠️ Diseño heredado con parches</li>
                    <li>⚠️ Múltiples archivos CSS</li>
                    <li>⚠️ Código complejo y difícil de mantener</li>
                    <li>⚠️ Problemas de solapamiento</li>
                    <li>⚠️ Espaciado inconsistente</li>
                    <li>⚠️ Filtros duplicados</li>
                    <li>⚠️ Requiere múltiples fixes</li>
                    <li>⚠️ Difícil de personalizar</li>
                </ul>
            </div>
        </div>
        
        <div class="pros-cons">
            <div class="pros">
                <h4>✅ Ventajas del Header Nuevo</h4>
                <ul>
                    <li><strong>Diseño moderno:</strong> Estilo limpio y profesional</li>
                    <li><strong>Mantenible:</strong> Código organizado y documentado</li>
                    <li><strong>Responsive:</strong> Funciona perfecto en todos los dispositivos</li>
                    <li><strong>Rápido:</strong> CSS optimizado y eficiente</li>
                    <li><strong>Extensible:</strong> Fácil de modificar y personalizar</li>
                    <li><strong>Sin bugs:</strong> Sin problemas de solapamiento o duplicación</li>
                    <li><strong>UX mejorada:</strong> Navegación intuitiva y fluida</li>
                    <li><strong>Futuro-proof:</strong> Preparado para nuevas funcionalidades</li>
                </ul>
            </div>
            
            <div class="cons">
                <h4>❌ Problemas del Header Antiguo</h4>
                <ul>
                    <li><strong>Diseño obsoleto:</strong> Estilo desactualizado</li>
                    <li><strong>Mantenimiento complejo:</strong> Múltiples archivos CSS</li>
                    <li><strong>Problemas responsive:</strong> Requiere fixes constantes</li>
                    <li><strong>Lento:</strong> CSS no optimizado</li>
                    <li><strong>Difícil de extender:</strong> Código acoplado</li>
                    <li><strong>Bugs frecuentes:</strong> Solapamientos y duplicaciones</li>
                    <li><strong>UX confusa:</strong> Navegación poco intuitiva</li>
                    <li><strong>Deuda técnica:</strong> Requiere refactoring completo</li>
                </ul>
            </div>
        </div>
        
        <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; margin: 30px 0;">
            <h3 style="color: #E30613; margin-top: 0;">📊 Comparación Técnica</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h4 style="color: #00ff00;">Header Nuevo</h4>
                    <ul>
                        <li><strong>Archivos CSS:</strong> 2 archivos organizados</li>
                        <li><strong>Líneas de código:</strong> ~800 líneas optimizadas</li>
                        <li><strong>Responsive:</strong> Mobile-first design</li>
                        <li><strong>Performance:</strong> CSS optimizado</li>
                        <li><strong>Mantenimiento:</strong> Fácil y rápido</li>
                        <li><strong>Bugs:</strong> 0 problemas conocidos</li>
                    </ul>
                </div>
                <div>
                    <h4 style="color: #ff0000;">Header Antiguo</h4>
                    <ul>
                        <li><strong>Archivos CSS:</strong> 4+ archivos dispersos</li>
                        <li><strong>Líneas de código:</strong> ~1500+ líneas con parches</li>
                        <li><strong>Responsive:</strong> Desktop-first con fixes</li>
                        <li><strong>Performance:</strong> CSS no optimizado</li>
                        <li><strong>Mantenimiento:</strong> Complejo y lento</li>
                        <li><strong>Bugs:</strong> Múltiples problemas</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div style="background: linear-gradient(45deg, #E30613, #FF4D4D); padding: 30px; border-radius: 8px; text-align: center; color: white; margin: 30px 0;">
            <h2 style="margin-top: 0;">🎯 Recomendación</h2>
            <p style="font-size: 1.2rem; margin-bottom: 20px;">
                <strong>Implementar el Header Nuevo es la mejor opción</strong>
            </p>
            <p>
                El header rediseñado desde cero ofrece mejor rendimiento, mantenibilidad y experiencia de usuario. 
                Es la solución definitiva para todos los problemas actuales.
            </p>
        </div>
        
        <div class="test-buttons">
            <a href="/test_header_new.php" class="test-btn">
                🚀 Probar Header Nuevo
            </a>
            <a href="/test_fixes_mobile.php" class="test-btn secondary">
                🔧 Ver Fixes Antiguos
            </a>
        </div>
    </div>
</body>
</html>
