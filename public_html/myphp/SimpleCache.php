<?php

class SimpleCache {
    private static $cacheDir = __DIR__ . '/../cache/';
    private static $ttl = 600; // 10 minutes

    public static function set($key, $data, $ttl = null) {
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0777, true);
        }

        $ttl = $ttl ?? self::$ttl;
        $file = self::$cacheDir . md5($key) . '.cache';
        $cacheData = [
            'expires' => time() + $ttl,
            'data' => $data
        ];

        return @file_put_contents($file, serialize($cacheData));
    }

    public static function get($key) {
        $file = self::$cacheDir . md5($key) . '.cache';
        if (!file_exists($file)) {
            return null;
        }

        $cacheData = @unserialize(file_get_contents($file));
        if (!$cacheData || time() > $cacheData['expires']) {
            @unlink($file);
            return null;
        }

        return $cacheData['data'];
    }

    public static function clear($key = null) {
        if ($key) {
            $file = self::$cacheDir . md5($key) . '.cache';
            if (file_exists($file)) {
                @unlink($file);
            }
        } else {
            $files = glob(self::$cacheDir . '*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
