<?php
$videoId = '8zV0nEq7I1Y';
$url = "https://www.youtube.com/watch?v={$videoId}";
$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                    "Accept-Language: es-ES,es;q=0.9\r\n",
        "timeout" => 5
    ]
];
$context = stream_context_create($opts);
$html = file_get_contents($url, false, $context);
file_put_contents('/home/admin/web/codigoamigo.com/yt_dump.html', $html);
echo "Dumped HTML to yt_dump.html\n";
?>
