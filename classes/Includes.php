<?php

/**
 * Centralized Include File
 * Loads all necessary classes and configurations automatically
 * Uses lazy-loading via SPL autoloader for better performance
 */

// Load configuration first
require_once __DIR__ . '/../config/config.php';

// SPL Autoloader - loads classes only when needed (lazy loading)
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
