<?php

require_once __DIR__ . "/../vendor/autoload.php";

function my_autoloader($classname){
    $filename = __DIR__ . "/../classes/{$classname}.php";
    if (file_exists($filename))
        require_once $filename;
}
spl_autoload_register("my_autoloader");

spl_autoload_register(
    function ($classname) {
        $filename = __DIR__ . "/../models/{$classname}.php";
        if (file_exists($filename))
            require_once $filename;
    }
);

error_reporting(E_ALL);

use Tracy\Debugger;
Debugger::enable();