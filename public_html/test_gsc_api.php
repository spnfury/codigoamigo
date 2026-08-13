<?php
require_once __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig(dirname(__DIR__) . '/private/google_credentials.json');
$client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

$service = new Google_Service_Webmasters($client);

try {
    $sites = $service->sites->listSites();
    echo "Webmaster Tools API connected successfully!\n";
    echo "Sites connected to this service account:\n";
    foreach ($sites->getSiteEntry() as $site) {
        echo "- " . $site->getSiteUrl() . "\n";
    }
} catch (Exception $e) {
    echo "Error connecting to Webmaster API: " . $e->getMessage() . "\n";
}
