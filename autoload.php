<?php
/**
 * Autoloader — automatically includes class files from the /classes/ directory.
 * spl_autoload_register() calls this closure whenever an unknown class is used.
 */
spl_autoload_register(function (string $class_name): bool {
    $class_file = __DIR__ . '/classes/' . $class_name . '.php';

    if (file_exists($class_file)) {
        require_once $class_file;
        return true;
    }

    // Class file not found; let PHP handle the error naturally
    return false;
});
?>