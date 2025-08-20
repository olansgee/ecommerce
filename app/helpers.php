<?php

if (!function_exists('url')) {
    /**
     * Generate a full URL from a path.
     *
     * @param string $path
     * @return string
     */
    function url($path = '') {
        // Remove leading/trailing slashes from the path to avoid double slashes
        $path = trim($path, '/');
        return BASE_URL . $path;
    }
}
