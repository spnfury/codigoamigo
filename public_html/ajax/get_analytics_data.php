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
require_once __DIR__ . '/../myphp/GoogleAnalyticsService.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $site = $input['site'] ?? 'codigoamigo';
    $dateRange = $input['dateRange'] ?? '30daysAgo';
    $action = $input['action'] ?? 'overview';
    
    // Validate site
    if (!in_array($site, ['codigoamigo', 'casinuevo'])) {
        throw new Exception('Sitio inválido');
    }
    
    $gaService = new GoogleAnalyticsService();
    
    switch ($action) {
        case 'overview':
            $data = $gaService->getAnalyticsData($site, $dateRange);
            echo json_encode([
                'success' => true,
                'site' => $site,
                'dateRange' => $dateRange,
                'data' => $data
            ]);
            break;
            
        case 'top_pages':
            $topPages = $gaService->getTopPages($site, $dateRange, 10);
            echo json_encode([
                'success' => true,
                'data' => $topPages
            ]);
            break;
            
        case 'traffic_sources':
            $sources = $gaService->getTrafficSources($site, $dateRange);
            echo json_encode([
                'success' => true,
                'data' => $sources
            ]);
            break;
            
        case 'devices':
            $devices = $gaService->getDeviceBreakdown($site, $dateRange);
            echo json_encode([
                'success' => true,
                'data' => $devices
            ]);
            break;
            
        case 'all':
            // Get all data in one request
            $overview = $gaService->getAnalyticsData($site, $dateRange);
            $topPages = $gaService->getTopPages($site, $dateRange, 10);
            $sources = $gaService->getTrafficSources($site, $dateRange);
            $devices = $gaService->getDeviceBreakdown($site, $dateRange);
            
            echo json_encode([
                'success' => true,
                'site' => $site,
                'dateRange' => $dateRange,
                'overview' => $overview,
                'topPages' => $topPages,
                'trafficSources' => $sources,
                'devices' => $devices
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
