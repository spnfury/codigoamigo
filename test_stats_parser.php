<?php
$html = file_get_contents('/home/admin/web/codigoamigo.com/yt_dump.html');

echo "--- Testing Likes ---\n";
// Pattern 1: accessibility label
if (preg_match('/"label":"([\d\.,\s]+likes?)"/', $html, $matches)) {
    echo "Found P1: " . $matches[1] . "\n";
}

// Pattern 2: fullLikeCount
if (preg_match('/"fullLikeCount":"([\d\.,]+)"/', $html, $matches)) {
    echo "Found P2: " . $matches[1] . "\n";
}

// Pattern 3: likeCount
if (preg_match('/"likeCount":"(\d+)"/', $html, $matches)) {
    echo "Found P3: " . $matches[1] . "\n";
}

echo "\n--- Testing Views ---\n";
// Pattern 1: viewCount
if (preg_match('/"viewCount":"(\d+)"/', $html, $matches)) {
    echo "Found V1: " . $matches[1] . "\n";
}

// Pattern 2: viewCount in text
if (preg_match('/"viewCountText":\{"simpleText":"([\d\.,\s]+)visualizaciones"/', $html, $matches)) {
    echo "Found V2: " . $matches[1] . "\n";
}

echo "\n--- Testing Comments ---\n";
// Pattern 1: commentCount
if (preg_match('/"commentCount":\{"simpleText":"([\d\.,]+)"\}/', $html, $matches)) {
    echo "Found C1: " . $matches[1] . "\n";
}
?>
