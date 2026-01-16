<?php
session_start();
header('Content-Type: application/json');

// Admin whitelist
$admin_ids = [
    "58bd851da54e295b8b52f702", //thevega82@gmail.com
    "5e78170e6b68e6519b7c5df2", //edna
    "639899bc6321ee0d0e4010d2", //aron
    "5c8a10ce2f55c86d6e707d82"  //jose
];

if (!in_array($_SESSION["user_id"], $admin_ids)) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['keyword']) || !isset($input['pages']) || !is_array($input['pages'])) {
        throw new Exception('Datos incompletos');
    }
    
    $keyword = $input['keyword'];
    $pages = $input['pages'];
    
    if (count($pages) < 2) {
        throw new Exception('Se necesitan al menos 2 páginas para resolver canibalización');
    }
    
    // Ordenar páginas por clicks (descendente) para identificar la más fuerte
    usort($pages, function($a, $b) {
        return $b['clicks'] - $a['clicks'];
    });
    
    $winner = $pages[0]; // La página con más clicks
    $losers = array_slice($pages, 1); // El resto
    
    // Conectar a MongoDB
    $mongo = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $redirects_collection = $mongo->codigo_db->redirects;
    
    $redirects_created = [];
    $winner_path = parse_url($winner['page'], PHP_URL_PATH);
    
    foreach ($losers as $loser) {
        $loser_path = parse_url($loser['page'], PHP_URL_PATH);
        
        // Verificar si ya existe un redirect
        $existing = $redirects_collection->findOne([
            'old_url' => $loser_path,
            'is_active' => true
        ]);
        
        if (!$existing) {
            // Crear nuevo redirect
            $redirect_doc = [
                'type' => 'seo_cannibalization',
                'old_url' => $loser_path,
                'new_url' => $winner_path,
                'keyword' => $keyword,
                'reason' => 'Auto-fix canibalización SEO',
                'old_clicks' => $loser['clicks'],
                'old_impressions' => $loser['impressions'],
                'winner_clicks' => $winner['clicks'],
                'winner_impressions' => $winner['impressions'],
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION["user_id"],
                'is_active' => true
            ];
            
            $redirects_collection->insertOne($redirect_doc);
            $redirects_created[] = [
                'from' => $loser_path,
                'to' => $winner_path
            ];
        }
    }
    
    // Generar archivo .htaccess con los redirects (opcional, para Apache)
    $htaccess_path = __DIR__ . '/../.htaccess_seo_redirects';
    $htaccess_content = "# SEO Cannibalization Auto-Fixes\n";
    $htaccess_content .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    $all_redirects = $redirects_collection->find(['is_active' => true, 'type' => 'seo_cannibalization']);
    foreach ($all_redirects as $redirect) {
        $htaccess_content .= "Redirect 301 {$redirect['old_url']} {$redirect['new_url']}\n";
    }
    
    file_put_contents($htaccess_path, $htaccess_content);
    
    echo json_encode([
        'success' => true,
        'message' => count($redirects_created) . ' redirección(es) 301 creada(s)',
        'winner' => $winner_path,
        'redirects' => $redirects_created,
        'htaccess_file' => '.htaccess_seo_redirects'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
