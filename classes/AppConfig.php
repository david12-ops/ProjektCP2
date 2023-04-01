<?php

use Noodlehaus\Config;
use Noodlehaus\Parser\Json;

class AppConfig
{
    private static ?Config $instance = null;
    public static function get(string $key) : mixed
    {
        if (self::$instance == null)
            self::initialize();

        return self::$instance->get($key);
    }

    private static function initialize() : void
    {
        $path = __DIR__ . "/../config/";
        self::$instance = Config::load([
            $path . 'config.json',
            $path . 'config_local.json'
        ]);
    }
}