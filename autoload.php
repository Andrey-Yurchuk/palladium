<?php

spl_autoload_register(function ($className) {
    $baseDir = __DIR__ . '/src/';
    $classPath = str_replace('\\', '/', $className) . '.php';
    $fullPath = $baseDir . $classPath;

    if (file_exists($fullPath)) {
        require $fullPath;
    } else {
        throw new \RuntimeException("Класс {$className} не найден в {$fullPath}");
    }
});
