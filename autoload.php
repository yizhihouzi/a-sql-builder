<?php
(function () {
    $autoloadFunction = function ($class) {
        if (strpos($class, 'DBOperate\\') === 0) {
            $classPrefixPath = dirname(__FILE__);
            $class           = substr($class, strpos($class, '\\')+1);
            $classPath = $classPrefixPath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR,
                    $class) . '.php';
            if (file_exists($classPath)) {
                require $classPath;
                if (class_exists($class, false)) {
                    return true;
                }
            }
        }
        return false;
    };
    spl_autoload_register($autoloadFunction, true, true);
})();