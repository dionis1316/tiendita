<?php
if (!function_exists('asset_url')) {
    function asset_url($path) {
        if ($path === '' || $path === null) return '';
        if (preg_match('#^https?://#i', $path)) return $path;
        if (strpos($path, '/') === 0) return $path;
        return BASE_URL . ltrim($path, '/');
    }
}
