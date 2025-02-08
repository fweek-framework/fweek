<?php

namespace HTTP\Security;

use HTTP\Server\Hashing;
use HTTP\Server\Session;
use HTTP\Request\Management;

class CSRF
{

    public static function token()
    {
        $token = hashing::encrypt(uniqid(), uniqid());
        session::multiAdd([
            "csrf-token" => $token,
            "viewName" => management::value("viewName")
        ]);
        
        return $token;
    }

    public static function generateInput($name = "csrf-token")
    {
        return '<input type="hidden" name="' . $name . '" value="' . self::token() . '">';
    }

    public static function checkToken($token, $name = "csrf-token")
    {
        if ($token === session::read("verify-token") && management::value("viewName") === session::read("viewName")) {
            return true;
        } else {
            return false;
        }
    }
}
