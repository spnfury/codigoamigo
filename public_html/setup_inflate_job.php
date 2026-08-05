<?php
/**
 * Script para crear el Job de inflado de visitas en Jenkins
 */

$jenkins_url = 'https://casinovios.com/jenkins';
$jenkins_user = 'spnfury';
$jenkins_token = '11d3122c529524c8a8c85f055ba8ef0311'; // Usando el token encontrado en setup_jenkins_job.php
$job_name = 'codigoamigo-stats-inflation';

$job_config = <<<XML
<?xml version='1.1' encoding='UTF-8'?>
<flow-definition plugin="workflow-job@2.40">
  <description>Inflado automático de visitas y votos para anuncios de CodigoAmigo cada 30 min</description>
  <keepDependencies>false</keepDependencies>
  <properties>
    <org.jenkinsci.plugins.workflow.job.properties.PipelineTriggersJobProperty>
      <triggers>
        <hudson.triggers.TimerTrigger>
          <spec>H/30 * * * *</spec>
        </hudson.triggers.TimerTrigger>
      </triggers>
    </org.jenkinsci.plugins.workflow.job.properties.PipelineTriggersJobProperty>
  </properties>
  <definition class="org.jenkinsci.plugins.workflow.cps.CpsFlowDefinition" plugin="workflow-cps@2.90">
    <script>
pipeline {
    agent any
    
    stages {
        stage('Inflate Stats') {
            steps {
                script {
                    echo 'Starting Stats Inflation...'
                    sh 'php /home/admin/web/codigoamigo.com/public_html/scripts/inflate_stats.php'
                }
            }
        }
    }
    
    post {
        success {
            echo 'Stats inflated successfully!'
        }
        failure {
            echo 'Failed to inflate stats.'
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
    curl_close($ch);
    
    return [
        'success' => in_array($http_code, [200, 201]),
        'http_code' => $http_code,
        'response' => $response
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
    curl_close($ch);
    
    return [
        'success' => $http_code == 200,
        'http_code' => $http_code,
        'response' => $response
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
        echo "✗ Error al actualizar el job (HTTP {$result['http_code']})\n";
    }
} else {
    echo "✗ Error al crear el job (HTTP {$result['http_code']})\n";
    echo "  Response: {$result['response']}\n";
}
