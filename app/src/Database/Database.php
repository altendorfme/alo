<?php

namespace alo\Database;

use MeekroDB;
use alo\Container\ContainerFactory;

class Database
{
    private static ?MeekroDB $instance = null;

    public static function getInstance(): MeekroDB
    {
        if (self::$instance === null) {
            self::$instance = new MeekroDB();
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
