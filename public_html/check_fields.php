<?php
require_once "/home/admin/web/codigoamigo.com/public_html/myphp/funciones.php";
$db = createConnection();
$fields = ['url_video_youtube', 'youtube_comments_count', 'youtube_likes_count', 'youtube_updated_at'];
foreach ($fields as $field) {
    $count = $db->chollos->countDocuments([$field => ['$exists' => true]]);
    echo "$field: $count documents\n";
}
?>
