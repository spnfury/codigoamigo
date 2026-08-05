<?php
session_start();

// Validar acceso (mismos permisos que admin_seo.php)
$array_codigos_acceso = [
    "58bd851da54e295b8b52f702", 
    "5e78170e6b68e6519b7c5df2", 
    "639899bc6321ee0d0e4010d2", 
    "5c8a10ce2f55c86d6e707d82"
];

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"]) || !in_array($_SESSION["user_id"], $array_codigos_acceso)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    die();
}

include_once __DIR__ . '/../inc/includes.php';
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../pro/app/Services/SeoService.php';

use Casinuevo\Services\SeoService;

header('Content-Type: application/json');

try {
    if (!isset($_GET['keyword'])) {
        throw new Exception("Keyword requerida");
    }

    $keyword = $_GET['keyword'];
    $authJsonPath = dirname(__DIR__, 2) . '/private/google_credentials.json';
    
    if (!file_exists($authJsonPath)) {
        throw new Exception("Error de credenciales");
    }

    $seoService = new SeoService($authJsonPath);
    $siteUrl = 'sc-domain:codigoamigo.com';
    
    // Últimos 90 días para ver tendencia
    $endDate = date('Y-m-d', strtotime('-2 days'));
    $startDate = date('Y-m-d', strtotime('-90 days')); 

    $rows = $seoService->getKeywordEvolution($siteUrl, $keyword, $startDate, $endDate);

    if (isset($rows['error'])) {
        throw new Exception($rows['error']);
    }

    // Formatear para Chart.js
    $labels = [];
    $clicks = [];
    $impressions = [];
    $position = [];
    $ctr = [];

    foreach ($rows as $row) {
        $labels[] = date('d/m', strtotime($row->keys[0]));
        $clicks[] = $row->clicks;
        $impressions[] = $row->impressions;
        $position[] = round($row->position, 1);
        $ctr[] = round($row->ctr * 100, 2);
    }

    echo json_encode([
        'success' => true,
        'keyword' => $keyword,
        'data' => [
            'labels' => $labels,
            'clicks' => $clicks,
            'impressions' => $impressions,
            'position' => $position,
            'ctr' => $ctr
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
