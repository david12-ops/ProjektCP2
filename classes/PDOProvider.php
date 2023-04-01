<?php

class PDOProvider
{
    private static ?PDO $instance = null;
    public static function get() : PDO
    {
        if (self::$instance == null)
            self::initialize();

        return self::$instance;
    }

    private static function initialize() : void
    {
        $host = AppConfig::get("db.host");
        $db = AppConfig::get("db.database");
        $user = AppConfig::get("db.user");
        $pass = AppConfig::get("db.password");
        $charset = AppConfig::get("db.charset");

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        self::$instance = new PDO($dsn, $user, $pass, $options);
    }
}