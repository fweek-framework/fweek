<?php

use Fweek\Core\Core;

class Model
{
    public static function start()
    {
        $null = 1;

        if ($null == 1) {
            core::var()->set("null", "true");
        } else {
            core::var()->set("null", "false");
        }
    }
}

Model::start();
