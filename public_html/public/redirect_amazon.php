<?php
/**
 * Redirect to malprecio.com after migration
 */
$url = $_GET['url'] ?? '';
$tag = $_GET['tag'] ?? 'spnfuryy-21';

if ($url) {
    header("Location: https://www.malprecio.com/public/redirect_amazon.php?url=" . urlencode($url) . "&tag=" . urlencode($tag), true, 301);
} else {
    header("Location: https://www.malprecio.com/chollos", true, 301);
}
exit;
