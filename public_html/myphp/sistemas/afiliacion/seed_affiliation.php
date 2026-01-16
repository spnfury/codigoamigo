<?php
/**
 * Script de inicialización (Seeding) para el nuevo sistema de afiliación
 */

require_once __DIR__ . '/../../../inc/conexion.php';

if (php_sapi_name() !== 'cli' && !isset($_GET['force'])) {
    die("Este script solo debe ejecutarse por CLI.");
}

function seed_affiliation() {
    echo "Iniciando seeding de afiliación...\n";

    // 1. Redes de Afiliación
    $col_networks = getCollectionAffiliationNetworks();
    
    $networks = [
        [
            'name' => 'Impact',
            'type' => 'impact',
            'status' => 'active',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Manual',
            'type' => 'manual',
            'status' => 'active',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Awin',
            'type' => 'awin',
            'status' => 'inactive',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]
    ];

    foreach ($networks as $net) {
        $exists = $col_networks->findOne(['name' => $net['name']]);
        if (!$exists) {
            $col_networks->insertOne($net);
            echo "Red creada: {$net['name']}\n";
        }
    }

    // 2. Ejemplo de Regla Global (Filtro por defecto)
    // Se puede implementar una regla especial con brand_id 'default'
    $col_rules = getCollectionAffiliationRules();
    $default_rule = [
        'brand_id' => 'default',
        'priority_order' => [
            // IDs de las redes en orden. Aquí necesitaríamos los IDs reales generados arriba
        ],
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ];
    
    // Obtenemos IDs de redes
    $impact = $col_networks->findOne(['name' => 'Impact']);
    $manual = $col_networks->findOne(['name' => 'Manual']);
    
    if ($impact && $manual) {
        $default_rule['priority_order'] = [
            (string)$impact['_id'],
            (string)$manual['_id']
        ];
        
        $exists = $col_rules->findOne(['brand_id' => 'default']);
        if (!$exists) {
            $col_rules->insertOne($default_rule);
            echo "Regla por defecto creada.\n";
        }
    }

    // 3. Programa de Test
    $col_programs = getCollectionAffiliationPrograms();
    if ($impact) {
        $test_program = [
            'brand_id' => 'default_test',
            'network_id' => $impact['_id'],
            'program_id' => '12345',
            'status' => 'active',
            'url' => 'https://impact.com/test_link',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $exists_prog = $col_programs->findOne(['brand_id' => 'default_test']);
        if (!$exists_prog) {
            $col_programs->insertOne($test_program);
            echo "Programa de test creado para 'default_test'.\n";
        }
    }

    echo "Seeding completado.\n";
}

seed_affiliation();
