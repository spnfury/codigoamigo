<?php
include_once __DIR__ . '/../inc/logger.php';
/**
 * Jenkins Job Trigger
 * Script para disparar la regeneración de sitemaps desde Jenkins
 */

// Configuración de Jenkins
$jenkins_url = getenv('JENKINS_URL') ?: 'https://casinovios.com/jenkins';
$jenkins_user = getenv('JENKINS_USER') ?: 'spnfury';
$jenkins_token = getenv('JENKINS_TOKEN') ?: '11d3122c529524c8a8c85f055ba8ef0311';
$job_name = 'codigoamigo-sitemap-generation'; // Nombre del job en Jenkins

function triggerJenkinsJob($jenkins_url, $jenkins_user, $jenkins_token, $job_name) {
    $job_url = rtrim($jenkins_url, '/') . "/job/{$job_name}/build";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $job_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$jenkins_user}:{$jenkins_token}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => in_array($http_code, [200, 201]),
        'http_code' => $http_code,
        'response' => $response
    ];
}

// Si se llama desde CLI o como script independiente
if (php_sapi_name() === 'cli' || (isset($_GET['trigger']) && $_GET['trigger'] === '1')) {
    $result = triggerJenkinsJob($jenkins_url, $jenkins_user, $jenkins_token, $job_name);
    
    if ($result['success']) {
        $message = "✓ Job de Jenkins '{$job_name}' disparado exitosamente.";
        log_info($message);
        if (php_sapi_name() === 'cli') {
            echo $message . "\n";
        }
    } else {
        $message = "✗ Error al disparar job de Jenkins. HTTP Code: {$result['http_code']}";
        log_error($message);
        if (php_sapi_name() === 'cli') {
            echo $message . "\n";
        }
    }
    
    if (isset($_GET['trigger'])) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
