<?php
/**
 * Redirect to malprecio.com after migration
 */
$id = $_GET['id'] ?? null;
if ($id) {
    header("Location: https://www.malprecio.com/chollo/" . htmlspecialchars($id), true, 301);
} else {
    header("Location: https://www.malprecio.com/chollos", true, 301);
}
exit;
