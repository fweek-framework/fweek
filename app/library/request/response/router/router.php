<?php

namespace Fweek\Core;

use HTTP\Server\Model;
use HTTP\Request\Management;

class Router
{
    private $type;

    /*
    Model
    */

    public static function model()
    {
        self::$type = "model";
        return new self();
    }

    public function load(string $name, string $location = "")
    {
        if (self::$type === "model") {
            return model::load($name, $location);
        } else {
            return false;
        }
    }

    /*
    Router
    */

    public static function router()
    {
        self::$type = "router";
        return new self();
    }

    public function get(string $name)
    {
        if (self::$type === "router") {
            return management::value("routeList")[$name];
        } else {
            return false;
        }
    }
}
