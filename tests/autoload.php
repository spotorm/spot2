<?php
/**
 * @package Spot
 */
/**
 * init test namespace autoloader
 */
spl_autoload_register(function ($class) {
    // project-specific namespace prefix
    $prefix = 'SpotTest\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . \str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require($file);
    }
});
