<?php

namespace HTTP\Request;

use HTTP\Request\Management;
use HTTP\Security\CSRF;
use HTTP\Server\Session;
use Logger;

class Engine
{
    public static function parse($content)
    {
        if (preg_match('/@csrf/', $content)) {
            session::add("verify-token", session::read("csrf-token"));
            $content = preg_replace('/@csrf/', csrf::generateInput(), $content);
        }

        return $content;
    }

    public static function response($data)
    {
        if (is_array($data)) {
            echo management::json($data);
        } else {
            echo $data;
        }
    }

    public static function view($name, $location = "")
    {
        $path = __ROOT__ . "/app/content/views/" . $location . "/" . $name . ".php";
        if (file_exists($path)) {
            $content  = file_get_contents($path);

            $tempFile = tempnam(sys_get_temp_dir(), 'tpl_') . '.php';

            management::set([
                "viewName" => $name
            ]);

            $parsedContent = self::parse($content);

            file_put_contents($tempFile, $parsedContent);

            ob_start();

            require_once $tempFile;
            self::response(ob_get_contents());

            ob_get_clean();
            unlink($tempFile);
        } else {
            Logger::error("This view does not exist!", "ENGINE", $path);
            return false;
        }
    }
}
