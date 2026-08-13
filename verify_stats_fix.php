<?php
require_once __DIR__ . '/public_html/myphp/funciones.php';
require_once __DIR__ . '/public_html/myphp/funciones_video_cache.php';
require_once __DIR__ . '/public_html/myphp/funciones_youtube.php';
require_once __DIR__ . '/public_html/config/ai_config.php';

$videoId = '8zV0nEq7I1Y'; // Example video ID
echo "Testing Video ID: $videoId\n";

// 1. Test Scraping directly
echo "\n--- Testing Scraping ---\n";
$scraped = scrapeYoutubeVideoStats($videoId);
print_r($scraped);

// 2. Test getYoutubeVideoStatsWithCache
echo "\n--- Testing getYoutubeVideoStatsWithCache ---\n";
$stats = getYoutubeVideoStatsWithCache($videoId);
print_r($stats);

// 3. Check MongoDB
echo "\n--- Checking MongoDB Cache ---\n";
$collection = getCollectionVideoStatsCache();
if ($collection) {
    $doc = $collection->findOne(['video_id' => $videoId]);
    if ($doc) {
        echo "Cache entry found in MongoDB:\n";
        print_r((array)$doc);
    } else {
        echo "No cache entry found in MongoDB.\n";
    }
}
?>
