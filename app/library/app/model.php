<?php

namespace HTTP\Server;

use Logger;

class Model
{

    public static function load($name)
    {
        $path = __ROOT__ . "/app/model/" . $name . ".php";

        if (file_exists($path)) {
            require_once($path);
        } else {
            Logger::error("Model does not exist!", "MODEL", $path);
            return false;
        }
    }
}