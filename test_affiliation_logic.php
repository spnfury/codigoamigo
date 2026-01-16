<?php
/**
 * Test script for Affiliation Redirection Logic
 */

require_once __DIR__ . '/public_html/inc/conexion.php';
require_once __DIR__ . '/public_html/myphp/sistemas/afiliacion/AffiliationService.php';

use CodigoAmigo\Systems\Affiliation\AffiliationService;

function test_affiliation() {
    $service = AffiliationService::getInstance();
    
    // Test 1: Marca sin programa ni regla
    echo "Test 1: Marca inexistente... ";
    $res = $service->getBestLinkForBrand('marca_falsa');
    echo ($res === null) ? "OK (null)" : "FAIL";
    echo "\n";

    // Test 2: Marca con programa en red por defecto
    // Usaremos una marca real si es posible, o insertaremos una de prueba
    echo "Test 2: Verificando regla por defecto... ";
    $res = $service->getBestLinkForBrand('default_test'); // Esto debería usar la regla 'default'
    echo ($res === null) ? "NOT CONFIGURED" : "FOUND";
    echo "\n";
    
    // Mostrando colecciones actuales para debug
    $col_nets = getCollectionAffiliationNetworks();
    echo "Redes configuradas: " . $col_nets->countDocuments([]) . "\n";
    
    $col_rules = getCollectionAffiliationRules();
    echo "Reglas configuradas: " . $col_rules->countDocuments([]) . "\n";
}

test_affiliation();
