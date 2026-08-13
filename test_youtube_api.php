<?php
require_once __DIR__ . '/public_html/config/ai_config.php';
require_once __DIR__ . '/public_html/myphp/funciones_youtube.php';

$videoId = '8zV0nEq7I1Y'; // Example video ID from get_shorts_feed.php emergency fallback
echo "Testing Video ID: $videoId\n";
echo "API KEY: " . (defined('YOUTUBE_API_KEY') ? YOUTUBE_API_KEY : 'NOT DEFINED') . "\n";

$url = "https://www.googleapis.com/youtube/v3/videos?part=statistics&id={$videoId}&key=" . YOUTUBE_API_KEY;
echo "URL: $url\n";

$opts = [
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'header' => 'Accept: application/json'
    ]
];
$context = stream_context_create($opts);
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "Error: file_get_contents failed.\n";
    $error = error_get_last();
    print_r($error);
} else {
    echo "Response received:\n";
    $data = json_decode($response, true);
    print_r($data);
    
    if (isset($data['items'][0]['statistics'])) {
        echo "Stats found!\n";
        print_r($data['items'][0]['statistics']);
    } else {
        echo "No stats found in response.\n";
    }
}
?>
