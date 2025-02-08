<?php

namespace Fweek\Core;

use HTTP\Server\Model;
use HTTP\Request\Management;
use Logger;

class Router
{
    private static $uri;
    private static $type;

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
        $routeList = management::value("routeList");
        if (self::$type === "router") {
            if (preg_match('/\{.*?\}/', $routeList[$name])) {
                self::$uri = $routeList[$name];
                return $this;
            } else {
                return $routeList[$name];
            }
        } else {
            return false;
        }
    }

    public function params(array $parameters)
    {
        $parsedURI = explode("/", self::$uri);

        foreach ($parsedURI as $key => $value) {
            if (preg_match('/\{.*?\}/', $value)) {
                $cleanParameter = str_replace(["{", "}", "?", "*"], "", $value);

                if (!isset($parameters[$cleanParameter])) {
                    Logger::error("There are some missing GET parameters while generating link! -> " . $cleanParameter . "", "Router");
                    return false;
                }

                self::$uri = str_replace($cleanParameter, $parameters[$cleanParameter], str_replace(["{", "}", "?", "*"], "", self::$uri));
            }
        }

        return self::$uri;
    }
}
