<?php
const __ROOT__ = __DIR__;
require_once(__ROOT__ . "/app/bootstrap/start.php");

request::loadENV("env.php");
request::handle(
    [
        "middlewares" => [
            "auth" => ["HTTP\User\Auth" => "/auth.php"]
        ],
        "controllers" => [
            "home" => ["controller" => "/base.php"]
        ]
    ]
);
