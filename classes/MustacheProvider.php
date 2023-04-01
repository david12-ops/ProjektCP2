<?php

class MustacheProvider
{
    private static ?Mustache_Engine $instance = null;
    public static function get() : Mustache_Engine
    {
        if (self::$instance == null)
            self::initialize();

        return self::$instance;
    }

    private static function initialize() : void
    {
        self::$instance = new Mustache_Engine([
            'entity_flags' => ENT_QUOTES,
            "loader" => new Mustache_Loader_FilesystemLoader(__DIR__ . "/../templates")
        ]);
    }
}