<?php
$html = file_get_contents('/home/admin/web/codigoamigo.com/yt_dump.html');

// Try to find ytInitialPlayerResponse
if (preg_match('/ytInitialPlayerResponse\s*=\s*(\{.*?\});/', $html, $matches)) {
    $data = json_decode($matches[1], true);
    echo "--- videoDetails ---\n";
    print_r($data['videoDetails'] ?? 'NOT FOUND');
    echo "\n--- statistics ---\n";
    // Sometimes it's in a different place
    print_r($data['statistics'] ?? 'NOT FOUND');
} else {
    echo "ytInitialPlayerResponse not found\n";
}

// Try to find ytInitialData
if (preg_match('/ytInitialData\s*=\s*(\{.*?\});/', $html, $matches)) {
    $data = json_decode($matches[1], true);
    // This is huge, so let's just search for keys
    echo "\n--- ytInitialData search ---\n";
    // Check for likes in a more generic way
    echo "Searching for viewCount...\n";
} else {
    echo "ytInitialData not found\n";
}
?>
