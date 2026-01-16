<?php
/**
 * Script para crear el Job de sitemaps en Jenkins
 * Ejecutar UNA VEZ para configurar el job inicial
 */

$jenkins_url = 'https://casinovios.com/jenkins';
$jenkins_user = 'spnfury';
$jenkins_token = '11d3122c529524c8a8c85f055ba8ef0311';
$job_name = 'codigoamigo-sitemap-generation';

$job_config = <<<XML
<?xml version='1.1' encoding='UTF-8'?>
<flow-definition plugin="workflow-job@2.40">
  <description>Regeneración automática de sitemaps de CodigoAmigo (marcas, chollos, categorías)</description>
  <keepDependencies>false</keepDependencies>
  <properties>
    <org.jenkinsci.plugins.workflow.job.properties.PipelineTriggersJobProperty>
      <triggers>
        <hudson.triggers.TimerTrigger>
          <spec>H H/6 * * *</spec>
        </hudson.triggers.TimerTrigger>
      </triggers>
    </org.jenkinsci.plugins.workflow.job.properties.PipelineTriggersJobProperty>
  </properties>
  <definition class="org.jenkinsci.plugins.workflow.cps.CpsFlowDefinition" plugin="workflow-cps@2.90">
    <script>
pipeline {
    agent any
    
    stages {
        stage('Generate Sitemaps') {
            steps {
                script {
                    echo 'Starting Sitemap Generation...'
                    // Execute the PHP script to regenerate sitemaps
                    sh 'php /home/admin/web/codigoamigo.com/public_html/cron/generar_sitemap_chollos.php'
                }
            }
        }
    }
    
    post {
        success {
            echo 'Sitemaps generated successfully!'
        }
        failure {
            echo 'Failed to generate sitemaps.'
        }
    }
}
    </script>
    <sandbox>true</sandbox>
  </definition>
  <triggers/>
  <disabled>false</disabled>
</flow-definition>
XML;

function createJenkinsJob($jenkins_url, $jenkins_user, $jenkins_token, $job_name, $config) {
    $create_url = rtrim($jenkins_url, '/') . "/createItem?name=" . urlencode($job_name);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $create_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $config);
    curl_setopt($ch, CURLOPT_USERPWD, "{$jenkins_user}:{$jenkins_token}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/xml'
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => in_array($http_code, [200, 201]),
        'http_code' => $http_code,
        'response' => $response,
        'error' => $error
    ];
}

function updateJenkinsJob($jenkins_url, $jenkins_user, $jenkins_token, $job_name, $config) {
    $update_url = rtrim($jenkins_url, '/') . "/job/{$job_name}/config.xml";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $update_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $config);
    curl_setopt($ch, CURLOPT_USERPWD, "{$jenkins_user}:{$jenkins_token}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/xml'
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => $http_code == 200,
        'http_code' => $http_code,
        'response' => $response,
        'error' => $error
    ];
}

echo "Configurando job '{$job_name}' en Jenkins...\n";
$result = createJenkinsJob($jenkins_url, $jenkins_user, $jenkins_token, $job_name, $job_config);

if ($result['success']) {
    echo "✓ Job creado exitosamente!\n";
} elseif ($result['http_code'] == 400) {
    echo "⚠ El job ya existe. Actualizando configuración...\n";
    $result = updateJenkinsJob($jenkins_url, $jenkins_user, $jenkins_token, $job_name, $job_config);
    
    if ($result['success']) {
        echo "✓ Job actualizado exitosamente!\n";
    } else {
        echo "✗ Error al actualizar el job\n";
        echo "  HTTP Code: {$result['http_code']}\n";
        echo "  Error: {$result['error']}\n";
        exit(1);
    }
} else {
    echo "✗ Error al crear el job\n";
    echo "  HTTP Code: {$result['http_code']}\n";
    echo "  Error: {$result['error']}\n";
    echo "  Response: {$result['response']}\n";
    exit(1);
}

echo "\n✅ Configuración completada:\n";
echo "  URL: {$jenkins_url}/job/{$job_name}/\n";
echo "  Programado: 4 veces al día (cada 6 horas - cron: H H/6 * * *)\n";
echo "\n🚀 Disparar manualmente:\n";
echo "  {$jenkins_url}/job/{$job_name}/build\n";
