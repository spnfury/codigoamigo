<?php
/**
 * Legacy URL — consolidado en /public/mis_viewers.php
 * Redirect 301 a embudo único (landing + gestión + leads).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$target = '/public/mis_viewers.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}

header('Location: ' . $target, true, 301);
exit;
