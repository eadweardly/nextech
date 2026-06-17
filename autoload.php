<?php

spl_autoload_register(function (string $class_name): bool {
    $class_file = __DIR__ . '/classes/' . $class_name . '.php';

    if (file_exists($class_file)) {
        require_once $class_file;
        return true;
    }

    return false;
});
?>