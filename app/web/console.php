<?php

use SQL\Process\TF;

class Console
{
    public static function sendCommand($parameters)
    {
        if ($parameters[1] === "install") {
            tf::createDatabase(request::env("APP_DB_NAME"));
            tf::createTable("sessions", [
                "id" => [
                    "INT" => 11,
                    "ai" => true
                ],
                "sessionToken" => [
                    "VARCHAR" => 255
                ],
                "userAgent" => [
                    "VARCHAR" => 255
                ],
                "sessionIP" => [
                    "VARCHAR" => 255
                ],
                "creationTime" => [
                    "VARCHAR" => 255
                ],
                "sessionContent" => [
                    "LONGTEXT"
                ],
            ], request::env("APP_DB_NAME"));
        }
    }
}
