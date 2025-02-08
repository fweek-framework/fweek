<?php

use Fweek\Core\Core;
use Fweek\Core\Router;

class controller
{

    public static function main()
    {
        core::response()->view("main");
    }
}
